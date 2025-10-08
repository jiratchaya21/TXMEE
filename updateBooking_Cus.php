<?php
// ไฟล์: updateBooking_Cus.php (ฉบับแก้ไข)
include "Datasql.php";
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// 1. [FIX] ตรวจสอบสิทธิ์ลูกค้า
if (!isset($_SESSION['Cus_id'])) {
    $response['message'] = 'กรุณาเข้าสู่ระบบเพื่อดำเนินการ';
    echo json_encode($response);
    exit();
}

// 2. ตรวจสอบข้อมูล POST ที่จำเป็น (ไม่ต้องใช้ schedule_id)
if (!isset($_POST['booking_id']) || !isset($_POST['date']) || !isset($_POST['time']) || !isset($_POST['hours']) || !isset($_POST['price'])) {
    $response['message'] = 'Invalid request. Missing data.';
    echo json_encode($response);
    exit();
}

$cus_id = (int)$_SESSION['Cus_id'];
$booking_id = (int)$_POST['booking_id'];
$new_date = $_POST['date'];
$new_time = $_POST['time'];
$new_hours = (int)$_POST['hours']; 
$new_price = (int)$_POST['price']; 
$status_booked = 'จองแล้ว'; // สถานะที่ใช้ตรวจสอบ/ตั้งค่า

// 3. ตรวจสอบความเป็นเจ้าของการจอง & ดึง Service_ID
$service_id = null;
$sql_fetch = "SELECT Service_ID, Cus_id FROM Booking WHERE Booking_ID = ?";
$stmt_fetch = $conn->prepare($sql_fetch);

if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $booking_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $row_fetch = $result_fetch->fetch_assoc();
    $stmt_fetch->close();
    
    // [FIX] ตรวจสอบความเป็นเจ้าของ
    if (!$row_fetch || $row_fetch['Cus_id'] !== $cus_id) {
        $response['message'] = 'คุณไม่มีสิทธิ์แก้ไขการจองนี้';
        echo json_encode($response);
        exit();
    }
    $service_id = $row_fetch['Service_ID'];
} else {
    $response['message'] = 'Error preparing ownership check statement: ' . $conn->error;
    echo json_encode($response);
    exit();
}

if ($service_id === null) {
    $response['message'] = 'Booking not found.';
    echo json_encode($response);
    exit();
}


// ----------------------------------------------------------------------
// 4. (เฉพาะ Studio) ตรวจสอบความพร้อมใช้งานของช่วงเวลาใหม่
// ----------------------------------------------------------------------
if ($service_id == 1) {
    $isAvailable = true;
    $checkStartTime = new DateTime($new_date . ' ' . $new_time);
    $interval = new DateInterval('PT1H'); 

    for ($i = 0; $i < $new_hours; $i++) {
        $checkTime = $checkStartTime->format('H:i:s');
        $checkDate = $checkStartTime->format('Y-m-d');
        
        // ตรวจสอบว่าช่วงเวลาใหม่นี้มี Booking_ID อื่นจองอยู่หรือไม่ (ยกเว้น ID ของตัวเอง)
        $sql_check = "SELECT COUNT(*) FROM studio_schedule 
                      WHERE booking_date = ? AND start_time = ? AND status = ? AND service_id = ? AND Booking_ID != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ssiii", $checkDate, $checkTime, $status_booked, $service_id, $booking_id);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            $isAvailable = false;
            $response['message'] = "ช่วงเวลา {$checkTime} ของวันที่ {$checkDate} ถูกจองโดยผู้อื่นแล้ว";
            echo json_encode($response);
            exit();
        }
        
        $checkStartTime->add($interval);
    }
}


// ----------------------------------------------------------------------
// 5. เริ่มต้น Transaction และดำเนินการบันทึก (ใช้ logic DELETE+INSERT)
// ----------------------------------------------------------------------
$conn->begin_transaction();

try {
    // ขั้นตอน A: อัปเดตตาราง Booking 
    $sql_booking = "UPDATE Booking SET Date_Book = ?, StartTime = ?, Hours = ?, Total_P = ? WHERE Booking_ID = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    
    if (!$stmt_booking) {
        throw new Exception("Error preparing Booking statement: " . $conn->error);
    }

    // [FIX] ประเภทข้อมูล: ssiii (Total_P และ Booking_ID เป็น integer)
    $stmt_booking->bind_param("ssiii", $new_date, $new_time, $new_hours, $new_price, $booking_id);
    
    if (!$stmt_booking->execute()) {
        throw new Exception("Error executing Booking update: " . $stmt_booking->error);
    }
    $stmt_booking->close();
    
    // ขั้นตอน B: จัดการตาราง studio_schedule (เฉพาะ Service_ID = 1)
    if ($service_id == 1) {
        
        // B1. ลบตารางเวลาเก่าทั้งหมดที่เกี่ยวข้องกับ Booking_ID นี้
        $sql_delete_old = "DELETE FROM studio_schedule WHERE Booking_ID = ?";
        $stmt_delete = $conn->prepare($sql_delete_old);
        
        if (!$stmt_delete) {
            throw new Exception("Error preparing delete schedule statement: " . $conn->error);
        }
        $stmt_delete->bind_param("i", $booking_id);
        
        if (!$stmt_delete->execute()) {
            throw new Exception("Error executing delete schedule: " . $stmt_delete->error);
        }
        $stmt_delete->close();
        
        
        // B2. ใส่ช่วงเวลาใหม่เข้าไปใน studio_schedule
        $status = 'จองแล้ว'; 
        $startTime = new DateTime($new_date . ' ' . $new_time);
        
        $sql_insert_new = "INSERT INTO studio_schedule (booking_date, start_time, status, Booking_ID, service_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_new);
        
        if (!$stmt_insert) {
            throw new Exception("Error preparing insert schedule statement: " . $conn->error);
        }

        for ($i = 0; $i < $new_hours; $i++) {
            $insert_time = $startTime->format('H:i:s');
            $insert_date = $startTime->format('Y-m-d');
            
            // sssii
            $stmt_insert->bind_param("sssii", $insert_date, $insert_time, $status, $booking_id, $service_id); 
            
            if (!$stmt_insert->execute()) {
                throw new Exception("Error executing insert schedule for time {$insert_time}: " . $stmt_insert->error);
            }
            
            $startTime->add($interval); 
        }
        $stmt_insert->close();
    }
    
    // ขั้นตอน C: ยืนยัน Transaction
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'การจองถูกแก้ไขเรียบร้อยแล้ว';
    
} catch (Exception $e) {
    // ยกเลิกการเปลี่ยนแปลงทั้งหมดหากมีข้อผิดพลาด
    $conn->rollback();
    $response['message'] = 'Transaction failed: ' . $e->getMessage();
}

$conn->close();

echo json_encode($response);
?>