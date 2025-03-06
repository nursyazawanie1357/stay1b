<?php
session_name("house_leader_session");
session_start();
include '../db_connection.php'; // Database connection
include 'leader_sidebar.php'; // Sidebar for leaders

// Check if leader is logged in
if (!isset($_SESSION['housemate_id'])) {
    header("Location: leader_login.php");
    exit();
}

$housemate_id = $_SESSION['housemate_id'];
$search_house = $_GET['house'] ?? '';

// Get the house the user is associated with
$current_house_id = null;
$stmt = $conn->prepare("SELECT house_id FROM house_booking WHERE housemate_id = ?");
$stmt->bind_param("i", $housemate_id);
$stmt->execute();
$stmt->bind_result($current_house_id);
$stmt->fetch();
$stmt->close();

$sort = $_GET['sort'] ?? 'created_at DESC';

// Handle delete
if (isset($_GET['delete'])) {
    $review_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM house_reviews WHERE review_id = ? AND reviewed_by = ?");
    $stmt->bind_param("ii", $review_id, $housemate_id);
    if ($stmt->execute()) {
        $success_message = "Review deleted successfully.";
    } else {
        $error_message = "Failed to delete review.";
    }
    $stmt->close();
    header("Location: leader_house_review.php"); // Redirect to avoid resubmission
    exit();
}

// Handle form submission for review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_content'], $_POST['rating']) && $current_house_id) {
    $review_content = $_POST['review_content'];
    $rating = $_POST['rating'];

    $stmt = $conn->prepare("INSERT INTO house_reviews (house_id, reviewed_by, review_content, rating) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisd", $current_house_id, $housemate_id, $review_content, $rating);
    if ($stmt->execute()) {
        $success_message = "Review submitted successfully.";
    } else {
        $error_message = "Failed to submit review.";
    }
    $stmt->close();
    header("Refresh:0"); // Refresh page to show updates
    exit();
}

// Fetch all houses
$houses = [];
$stmt = $conn->prepare("SELECT house_id, house_number FROM landlord_house");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $houses[$row['house_id']] = $row['house_number'];
}
$stmt->close();

// Fetch reviews based on selected house or all houses
$reviews = [];
$query = "
    SELECT hr.review_id, hr.review_content, hr.created_at, hr.reviewed_by, hr.rating, hr.house_id, lh.house_number
    FROM house_reviews hr
    JOIN landlord_house lh ON lh.house_id = hr.house_id
    WHERE (? = '' OR lh.house_id = ?)
    ORDER BY " . ($sort === 'rating DESC' ? 'hr.rating DESC' : 'hr.created_at DESC');
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $search_house, $search_house);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Reviews - Stay1B</title>
    <link rel="stylesheet" href="../css/global.css">
    <style>
        .review-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .rating {
            color: #ccc;
            font-size: 30px;
            direction: ltr;
            margin: 10px 0;
        }
        .form-container .rating {
            display: flex;
            justify-content: center; /* Centers the stars horizontally */
            margin-bottom: 25px;
        }

        .star {
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .star.selected {
            color: #f5d430;
        }
        .delete-link {
            display: inline-block;
            padding: 5px 10px;
            color: dc3545;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .form-container, .search-container {
            margin-top: 20px;
        }
        textarea, input[type="text"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const allStars = document.querySelectorAll('.rating .star');
            allStars.forEach(star => {
                star.addEventListener('click', function () {
                    if (this.closest('form')) { // Only trigger in forms
                        const rating = this.getAttribute('data-value');
                        document.getElementById('rating').value = rating;
                        const formStars = this.closest('.rating').querySelectorAll('.star');
                        formStars.forEach(s => s.classList.remove('selected'));
                        for (let i = 1; i <= rating; i++) {
                            document.querySelector(`form .star[data-value="${i}"]`).classList.add('selected');
                        }
                    }
                });
            });
        });
    </script>
</head>
<body>
<div class="dashboard-container">
    <main>
        <header>
            <h1>House Reviews</h1>
        </header>

        <div>
            <?php if (isset($success_message)) echo "<p style='color: green;'>$success_message</p>"; ?>
            <?php if (isset($error_message)) echo "<p style='color: red;'>$error_message</p>"; ?>
            <form method="GET" class="search-container">
                <select name="house">
                    <option value="">All Houses</option>
                    <?php foreach ($houses as $id => $number): ?>
                    <option value="<?php echo $id; ?>" <?php echo $search_house == $id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($number); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="sort">
                    <option value="created_at DESC">Most Recent</option>
                    <option value="rating DESC" <?php echo $sort == 'rating DESC' ? 'selected' : ''; ?>>Highest Rating</option>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>

        <section class="reviews-section">
            <h2>Reviews</h2>
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <h3>House <?php echo htmlspecialchars($review['house_number']); ?></h3>
                <p><?php echo htmlspecialchars($review['review_content']); ?></p>
                <div class="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?php echo $i <= $review['rating'] ? 'selected' : ''; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <?php if ($review['reviewed_by'] == $housemate_id): ?>
                <small>Posted on <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($review['created_at']))); ?></small>
                <a href="?delete=<?php echo $review['review_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </section>

        <div class="form-container">
            <form action="" method="POST">
                <textarea name="review_content" placeholder="Write your review here..." required></textarea>
                <div class="rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star" data-value="<?php echo $i; ?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="rating" value="0">
                <button type="submit">Submit Review</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
