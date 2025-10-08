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

// Check if Booking_ID and payment_type are received via POST
if (!isset($_POST['booking_id']) || !isset($_POST['payment_type'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$booking_id = $_POST['booking_id'];
$payment_type = $_POST['payment_type'];

// Determine the new status based on the payment type
$new_status_booking = '';
$new_status_payment = '';

if ($payment_type === 'deposit') {
    $new_status_booking = 'ชำระเงินมัดจำแล้ว';
    $new_status_payment = 'ชำระเงินมัดจำแล้ว';
} else if ($payment_type === 'full') {
    $new_status_booking = 'ชำระเงินเต็มจำนวนแล้ว';
    $new_status_payment = 'ชำระเงินเต็มจำนวนแล้ว';
} else {
    // Fallback for any other type, though the provided code doesn't use it.
    $new_status_booking = 'ชำระเงินแล้ว';
    $new_status_payment = 'ชำระเงินแล้ว';
}

// Use a transaction to ensure both updates succeed or fail together.
$conn->begin_transaction();

try {
    // 1. Update the Payment_Status in the Booking table
    $sql_booking = "UPDATE Booking SET Payment_Status = ? WHERE Booking_ID = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    if (!$stmt_booking) {
        throw new Exception("Error preparing booking update statement: " . $conn->error);
    }
    $stmt_booking->bind_param("si", $new_status_booking, $booking_id);
    $stmt_booking->execute();
    $stmt_booking->close();

    // 2. Update the P_Status in the Payment table
    $sql_payment = "UPDATE Payment SET P_Status = ? WHERE Booking_ID = ?";
    $stmt_payment = $conn->prepare($sql_payment);
    if (!$stmt_payment) {
        throw new Exception("Error preparing payment update statement: " . $conn->error);
    }
    $stmt_payment->bind_param("si", $new_status_payment, $booking_id);
    $stmt_payment->execute();
    $stmt_payment->close();

    // Commit the transaction if both updates were successful.
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'สถานะการชำระเงินได้รับการอัปเดตแล้ว']);

} catch (Exception $e) {
    // Rollback the transaction on error.
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>