<?php
session_name("landlord_session");
session_start();
include '../db_connection.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate received data
    if (
        empty($data['booked_house_id']) || 
        empty($data['landlord_id']) || 
        empty($data['tenant_id']) || 
        empty($data['tenancy_period']) || 
        empty($data['start_date']) || 
        empty($data['monthly_rent']) || 
        empty($data['deposit'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Assign variables from incoming data
    $booked_house_id = $data['booked_house_id'];
    $landlord_id = $data['landlord_id'];
    $tenant_id = $data['tenant_id'];
    $tenancy_period = $data['tenancy_period'];
    $start_date = $data['start_date'];
    $monthly_rent = $data['monthly_rent'];
    $deposit = $data['deposit'];

    try {
        // Check if an agreement already exists for the given booking
        $query_check = "SELECT agreement_id FROM tenancy_agreements WHERE booked_house_id = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("i", $booked_house_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Agreement already exists for this booking.']);
            $stmt_check->close();
            $conn->close();
            exit;
        }
        $stmt_check->close();

        // Insert new agreement into the database
        $query_insert = "
            INSERT INTO tenancy_agreements 
            (booked_house_id, landlord_id, tenant_id, tenancy_period, start_date, monthly_rent, deposit) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($query_insert);

        if (!$stmt_insert) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare the insert statement.']);
            $conn->close();
            exit;
        }

        $stmt_insert->bind_param(
            "iiissdd", 
            $booked_house_id, 
            $landlord_id, 
            $tenant_id, 
            $tenancy_period, 
            $start_date, 
            $monthly_rent, 
            $deposit
        );

        if ($stmt_insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tenancy agreement saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to insert the agreement into the database.']);
        }

        $stmt_insert->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
?>
