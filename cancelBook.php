<?php
// Include database connection file
include "Datasql.php"; 
session_start();

// Set response header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in as staff
if (!isset($_SESSION['Staff_id'])) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

// Check if Booking_ID is received via POST
if (!isset($_POST['Booking_ID'])) {
    $response['message'] = 'Invalid request. Missing Booking ID.';
    echo json_encode($response);
    exit();
}

$booking_id = (int)$_POST['Booking_ID']; // Cast to int for safety

// ----------------------------------------------------------------------
// เริ่มต้น Transaction เพื่อความสมบูรณ์ของข้อมูล (ACID)
// ----------------------------------------------------------------------
$conn->begin_transaction();

try {
    // 1. อัปเดตตาราง Booking: เปลี่ยนสถานะเป็น 'ยกเลิกแล้ว'
    $sql_booking = "UPDATE Booking SET Booking_Status = 'ยกเลิกแล้ว' WHERE Booking_ID = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    
    if (!$stmt_booking) {
        throw new Exception("Failed to prepare Booking update statement: " . $conn->error);
    }
    
    $stmt_booking->bind_param("i", $booking_id);
    if (!$stmt_booking->execute()) {
        throw new Exception("Failed to execute Booking update: " . $stmt_booking->error);
    }
    
    // ตรวจสอบว่ามีการจองถูกอัปเดตสถานะหรือไม่ (affected_rows ต้องมาจากการจองครั้งก่อน)
    $rows_affected = $stmt_booking->affected_rows;
    $stmt_booking->close();
    
    if ($rows_affected === 0) {
        // ถ้าไม่มีการอัปเดต (อาจเป็นเพราะสถานะเป็น 'ยกเลิกแล้ว' อยู่แล้ว หรือไม่พบ ID)
        $conn->commit(); 
        $response['message'] = 'ไม่พบการจองที่ต้องการยกเลิก หรือสถานะการจองเป็น "ยกเลิกแล้ว" อยู่แล้ว';
        echo json_encode($response);
        exit();
    }
    
    // 2. อัปเดตตาราง Studio_Schedule: เปลี่ยนสถานะเป็น 'ว่าง'
    // ทำให้ช่วงเวลาที่ถูกจองกลับมาใช้งานได้อีกครั้ง
    $sql_schedule = "UPDATE studio_schedule SET status = 'ว่าง' WHERE Booking_ID = ?";
    $stmt_schedule = $conn->prepare($sql_schedule);
    
    if (!$stmt_schedule) {
        throw new Exception("Failed to prepare Schedule update statement: " . $conn->error);
    }

    $stmt_schedule->bind_param("i", $booking_id);
    if (!$stmt_schedule->execute()) {
        throw new Exception("Failed to execute Schedule update: " . $stmt_schedule->error);
    }
    $stmt_schedule->close();

    // ----------------------------------------------------------------------
    // ยืนยัน Transaction: บันทึกการเปลี่ยนแปลงทั้งสองตาราง
    // ----------------------------------------------------------------------
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'การจองถูกยกเลิกและช่วงเวลาถูกปล่อยเป็น "ว่าง" เรียบร้อยแล้ว';
    
} catch (Exception $e) {
    // ยกเลิกการเปลี่ยนแปลงทั้งหมดหากมีข้อผิดพลาด
    $conn->rollback();
    $response['message'] = 'Error during cancellation process: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>