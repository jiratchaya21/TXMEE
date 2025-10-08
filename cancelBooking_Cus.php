<?php
// ไฟล์: cancelBooking_Cus.php (ฉบับแก้ไข)
session_start();
include "Datasql.php";

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// [FIX] 1. ตรวจสอบสิทธิ์ลูกค้า
if (!isset($_SESSION['Cus_id'])) {
    $response['message'] = 'กรุณาเข้าสู่ระบบเพื่อดำเนินการ';
    echo json_encode($response);
    exit();
}

$cus_id = (int)$_SESSION['Cus_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'] ?? null;
    
    if ($bookingId) {
        $bookingId = (int)$bookingId;
        
        // 2. ตรวจสอบความเป็นเจ้าของการจอง
        $sql_owner_check = "SELECT Service_ID, Cus_id FROM Booking WHERE Booking_ID = ?";
        $stmt_owner = $conn->prepare($sql_owner_check);
        
        if (!$stmt_owner) {
            $response['message'] = 'Error preparing ownership check: ' . $conn->error;
            echo json_encode($response);
            exit();
        }
        $stmt_owner->bind_param("i", $bookingId);
        $stmt_owner->execute();
        $result_owner = $stmt_owner->get_result();
        $row_owner = $result_owner->fetch_assoc();
        $stmt_owner->close();
        
        if (!$row_owner || $row_owner['Cus_id'] !== $cus_id) {
            $response['message'] = 'ไม่พบการจอง หรือคุณไม่มีสิทธิ์ยกเลิกการจองนี้';
            echo json_encode($response);
            exit();
        }

        // 3. เริ่ม Transaction
        $conn->begin_transaction();

        try {
            // A. อัปเดตตาราง Booking: เปลี่ยนสถานะเป็น 'ยกเลิกแล้ว'
            $sql_booking = "UPDATE Booking SET Booking_Status = 'ยกเลิกแล้ว' WHERE Booking_ID = ?";
            $stmt_booking = $conn->prepare($sql_booking);

            if (!$stmt_booking) {
                throw new Exception("Failed to prepare Booking update statement: " . $conn->error);
            }
            $stmt_booking->bind_param("i", $bookingId);
            if (!$stmt_booking->execute()) {
                throw new Exception("Failed to execute Booking update: " . $stmt_booking->error);
            }
            $stmt_booking->close();
            
            // B. [FIX] อัปเดตตาราง Studio_Schedule: เปลี่ยนสถานะเป็น 'ว่าง'
            $sql_schedule = "UPDATE studio_schedule SET status = 'ว่าง' WHERE Booking_ID = ?";
            $stmt_schedule = $conn->prepare($sql_schedule);
            
            if (!$stmt_schedule) {
                throw new Exception("Failed to prepare Schedule update statement: " . $conn->error);
            }

            $stmt_schedule->bind_param("i", $bookingId);
            if (!$stmt_schedule->execute()) {
                throw new Exception("Failed to execute Schedule update: " . $stmt_schedule->error);
            }
            $stmt_schedule->close();

            // 4. Commit Transaction
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "การจองถูกยกเลิกและช่วงเวลาถูกปล่อยเป็น 'ว่าง' เรียบร้อยแล้ว";

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Error during cancellation process: ' . $e->getMessage();
        }
    } else {
        $response['message'] = "Missing booking ID.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
$conn->close();
?>