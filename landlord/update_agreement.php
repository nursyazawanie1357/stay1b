<?php
session_name("landlord_session");
session_start();
include '../db_connection.php';

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Log received data for debugging
    file_put_contents("update_log.txt", "Received data: " . print_r($data, true), FILE_APPEND);

    // Extract and validate data
    $booked_house_id = $data['booked_house_id'] ?? null;
    $landlord_id = $data['landlord_id'] ?? null;
    $tenant_id = $data['tenant_id'] ?? null;
    $tenancy_period = $data['tenancy_period'] ?? null;
    $start_date = $data['start_date'] ?? null;
    $monthly_rent = $data['monthly_rent'] ?? null;
    $deposit = $data['deposit'] ?? null;

    if (!$booked_house_id || !$landlord_id || !$tenant_id || !$tenancy_period || !$start_date || !$monthly_rent || !$deposit) {
        echo json_encode(["success" => false, "message" => "Invalid or missing data."]);
        exit();
    }

    try {
        $query = "
            INSERT INTO tenancy_agreements (
                booked_house_id, 
                landlord_id, 
                tenant_id, 
                tenancy_period, 
                start_date, 
                monthly_rent, 
                deposit, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                tenancy_period = VALUES(tenancy_period),
                start_date = VALUES(start_date),
                monthly_rent = VALUES(monthly_rent),
                deposit = VALUES(deposit),
                updated_at = NOW()
        ";

        file_put_contents("update_log.txt", "SQL Query: $query\n", FILE_APPEND);

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "iiissdd", 
            $booked_house_id, 
            $landlord_id, 
            $tenant_id, 
            $tenancy_period, 
            $start_date, 
            $monthly_rent, 
            $deposit
        );

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Agreement successfully updated."]);
        } else {
            file_put_contents("update_log.txt", "SQL Error: " . $stmt->error . "\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "SQL Error: " . $stmt->error]);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}

$conn->close();
?>
