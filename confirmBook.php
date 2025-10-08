<?php
// Include database connection file
include "Datasql.php"; 
session_start();

// Set response header to JSON
header('Content-Type: application/json');

// Check if user is logged in as staff
if (!isset($_SESSION['Staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Check if Booking_ID is received via POST
if (!isset($_POST['Booking_ID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$booking_id = $_POST['Booking_ID'];

// Update the Booking_Status in the database to 'ยืนยันแล้ว'
$sql = "UPDATE Booking SET Booking_Status = 'ยืนยันแล้ว', Staff_id = ? WHERE Booking_ID = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $staff_id = $_SESSION['Staff_id'];

    $stmt->bind_param("ii", $staff_id, $booking_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'สถานะการจองได้รับการอัปเดตแล้ว']);
        } else {
            echo json_encode(['success' => false, 'message' => 'สถานะการจองไม่เปลี่ยนแปลง']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error executing statement.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement.']);
}
$conn->close();
?>