<?php
// ไฟล์: updateBooking.php (สำหรับแอดมิน)
// Include database connection file
include "Datasql.php"; // Datasql.php ได้รับการแก้ไขให้ใช้ utf8mb4 แล้ว
session_start();

// Set response header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// 1. ตรวจสอบสิทธิ์เจ้าหน้าที่
if (!isset($_SESSION['Staff_id'])) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

// 2. ตรวจสอบข้อมูล POST ที่จำเป็น
if (!isset($_POST['booking_id']) || !isset($_POST['date']) || !isset($_POST['time']) || !isset($_POST['hours']) || !isset($_POST['price'])) {
    $response['message'] = 'Invalid request. Missing data.';
    echo json_encode($response);
    exit();
}

$booking_id = (int)$_POST['booking_id'];
$new_date = $_POST['date'];
$new_time = $_POST['time'];
$new_hours = (int)$_POST['hours']; // Cast to int
$new_price = (int)$_POST['price']; // Cast to int (ตามที่ยืนยันว่า Total_P เป็น int)

// 3. ดึง Service_ID เพื่อตรวจสอบว่าเป็นสตูดิโอหรือไม่
$service_id = null;
$sql_fetch = "SELECT Service_ID FROM Booking WHERE Booking_ID = ?";
$stmt_fetch = $conn->prepare($sql_fetch);

if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $booking_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($row_fetch = $result_fetch->fetch_assoc()) {
        $service_id = $row_fetch['Service_ID'];
    }
    $stmt_fetch->close();
}

if ($service_id === null) {
    $response['message'] = 'Booking not found or failed to fetch Service ID.';
    echo json_encode($response);
    exit();
}

// เริ่มต้น Transaction เพื่อความสมบูรณ์ของข้อมูล
$conn->begin_transaction();

try {
    // ----------------------------------------------------------------------
    // ขั้นตอน A: อัปเดตตาราง Booking 
    // *ส่วนนี้จะไม่แก้ไข Booking_Status เพื่อให้แอดมินจัดการสถานะหลักแยกต่างหาก*
    // ----------------------------------------------------------------------
    $sql_booking = "UPDATE Booking SET Date_Book = ?, StartTime = ?, Hours = ?, Total_P = ? WHERE Booking_ID = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    
    if (!$stmt_booking) {
        throw new Exception("Error preparing Booking statement: " . $conn->error);
    }

    $stmt_booking->bind_param("ssiii", $new_date, $new_time, $new_hours, $new_price, $booking_id);
    
    if (!$stmt_booking->execute()) {
        throw new Exception("Error executing Booking update: " . $stmt_booking->error);
    }
    $stmt_booking->close();
    
    // ----------------------------------------------------------------------
    // ขั้นตอน B: จัดการตาราง studio_schedule (เฉพาะ Service_ID = 1)
    // ----------------------------------------------------------------------
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
        // *** FIX: กำหนดค่าสถานะเป็น 'จองแล้ว' เสมอตามหลักการทำงานที่ต้องการ ***
        $status = 'จองแล้ว'; // <-- แก้ไขให้เป็นค่าคงที่ตามที่คุณต้องการ
        
        $interval = new DateInterval('PT1H'); 
        $startTime = new DateTime($new_date . ' ' . $new_time);
        
        $sql_insert_new = "INSERT INTO studio_schedule (booking_date, start_time, status, Booking_ID, service_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_new);
        
        if (!$stmt_insert) {
            throw new Exception("Error preparing insert schedule statement: " . $conn->error);
        }

        for ($i = 0; $i < $new_hours; $i++) {
            $insert_time = $startTime->format('H:i:s');
            $insert_date = $startTime->format('Y-m-d');
            
            // s (booking_date) s (start_time) s (status) i (Booking_ID) i (service_id)
            $stmt_insert->bind_param("sssii", $insert_date, $insert_time, $status, $booking_id, $service_id);
            
            if (!$stmt_insert->execute()) {
                throw new Exception("Error executing insert schedule for time {$insert_time}: " . $stmt_insert->error);
            }
            
            $startTime->add($interval); // เลื่อนไปชั่วโมงถัดไป
        }
        $stmt_insert->close();
    }
    
    // ----------------------------------------------------------------------
    // ขั้นตอน C: ยืนยัน Transaction
    // ----------------------------------------------------------------------
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Booking and Schedule updated successfully.';
    
} catch (Exception $e) {
    // ยกเลิกการเปลี่ยนแปลงทั้งหมดหากมีข้อผิดพลาด
    $conn->rollback();
    $response['message'] = 'Error saving data: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>