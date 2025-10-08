<?php
header('Content-Type: application/json');
session_start();

// Include database connection file
include "Datasql.php";

$response = array('success' => false, 'message' => '');

// กำหนด Service ID สำหรับบริการสตูดิโอ (ต้องตรงกับค่าใน $location_mapping ใน BookAdmin.php)
// กำหนด Service ID และค่ามัดจำ
$STUDIO_SERVICE_ID = 1; 
$SAYCHEEZE_DEPOSIT = 5000; // ค่ามัดจำสำหรับบริการ SayCheeze

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $cus_name = isset($_POST['new_cus_name']) ? $_POST['new_cus_name'] : '';
    $cus_lname = isset($_POST['new_cus_lname']) ? $_POST['new_cus_lname'] : '';
    $cus_phone = isset($_POST['new_cus_phone']) ? $_POST['new_cus_phone'] : '';
    $cus_email = isset($_POST['new_cus_email']) ? $_POST['new_cus_email'] : '';
    $service_type = isset($_POST['new_service_type']) ? $_POST['new_service_type'] : '';
    
    // ใช้ชื่อตัวแปรรับค่าตาม form input
    $date_book_input = isset($_POST['new_date']) ? $_POST['new_date'] : ''; 
    $start_time_input = isset($_POST['new_time']) ? $_POST['new_time'] : ''; 
    
    $hours = isset($_POST['new_hours']) ? (int)$_POST['new_hours'] : 0;
    // ใช้ 's' (string) สำหรับ Total_P ตามโค้ดเก่าที่ใช้งานได้
    $total_price = isset($_POST['new_total_price']) ? $_POST['new_total_price'] : 0; 
    
    $service_id = isset($_POST['new_service_id']) && $_POST['new_service_id'] !== '' ? (int)$_POST['new_service_id'] : NULL;
    $location = isset($_POST['new_location']) ? $_POST['new_location'] : '';
    $payment_status_input = isset($_POST['new_payment_status']) ? $_POST['new_payment_status'] : 'ชำระเงินเต็มจำนวนแล้ว';
    $note = isset($_POST['new_note']) ? $_POST['new_note'] : NULL;
    $slip_file = isset($_POST['new_slip_file']) ? $_POST['new_slip_file'] : NULL;
    
    // Validate required fields
    if (empty($cus_name) || empty($cus_lname) || empty($cus_phone) || empty($service_type) || empty($date_book_input) || empty($start_time_input) || $hours <= 0 || empty($location)) {
        $response['message'] = 'กรุณากรอกข้อมูลสำคัญให้ครบถ้วน (รวมถึงสถานที่และเวลา)';
        echo json_encode($response);
        exit;
    }

    // --- [แก้ไข 1]: แปลงเวลาเพื่อแก้ปัญหา 00:00:13 ---
    $start_time_sql = null;
    $date_book = $date_book_input; 

    try {
        // ใช้ DateTime เพื่อแปลงเวลาเป็น H:i:s ที่ถูกต้องสำหรับ MySQL TIME
        $start_datetime_obj = new DateTime($date_book_input . ' ' . $start_time_input);
        $start_time_sql = $start_datetime_obj->format('H:i:s'); 
        
    } catch (Exception $e) {
        $response['message'] = 'เกิดข้อผิดพลาดในการประมวลผลเวลา: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
    // -------------------------------------------------------------------

    // --- [แก้ไข 2]: กำหนดค่า Selected_P (ค่ามัดจำ) ตามเงื่อนไข ---
    $selected_price = null;
    if ($service_type === 'SayCheeze') {
        // SayCheeze: มัดจำ 5,000 บาท
        $selected_price = $SAYCHEEZE_DEPOSIT;
    } else {
        // Studio หรืออื่นๆ: มัดจำเท่ากับ Total_P (เต็มจำนวน)
        $selected_price = $total_price;
    }
    // -------------------------------------------------------------------

    try {
        $conn->begin_transaction();

        // 1. Check/Insert Customer
        $sql_check_customer = "SELECT Cus_id FROM Customer WHERE phone = ?";
        $stmt_check = $conn->prepare($sql_check_customer);
        $stmt_check->bind_param("s", $cus_phone);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        $cus_id = null;
        if ($result_check->num_rows > 0) {
            $row = $result_check->fetch_assoc();
            $cus_id = $row['Cus_id'];
        } else {
            $sql_insert_customer = "INSERT INTO Customer (F_name, L_name, phone, Email_C) VALUES (?, ?, ?, ?)";
            $stmt_insert_customer = $conn->prepare($sql_insert_customer);
            // ใช้ 'ssss' สำหรับ bind_param ของลูกค้า
            $stmt_insert_customer->bind_param("ssss", $cus_name, $cus_lname, $cus_phone, $cus_email);
            
            if ($stmt_insert_customer->execute()) {
                $cus_id = $conn->insert_id;
            } else {
                throw new Exception('ไม่สามารถเพิ่มข้อมูลลูกค้าได้: ' . $stmt_insert_customer->error);
            }
            $stmt_insert_customer->close();
        }
        $stmt_check->close();

        if ($cus_id) {
            // 3. Insert new booking into Booking table
            $booking_status = 'ยืนยันแล้ว';
            $payment_status = $payment_status_input; 
            $staff_id = isset($_SESSION['Staff_id']) ? (int)$_SESSION['Staff_id'] : 1;

            // [เพิ่ม Selected_P ใน Query]
            $sql_insert_booking = "INSERT INTO Booking (Cus_id, ServiceType, Date_Book, StartTime, Hours, Total_P, Selected_P, Staff_id, Booking_Status, Payment_Status, Service_ID, Location, Note, Slip_File) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_insert_booking = $conn->prepare($sql_insert_booking);
            
            // Bind String: i,s,s,s,i,s,s,i,s,s,i,s,s,s
            // (Total_P และ Selected_P ใช้ 's' (string) เนื่องจากเป็นค่าเงินที่รับมาจากฟอร์ม)
            $stmt_insert_booking->bind_param("issssssissssss", 
                $cus_id, 
                $service_type, 
                $date_book, 
                $start_time_sql, // ใช้ตัวแปรที่แปลงเป็น H:i:s แล้ว
                $hours, 
                $total_price, 
                $selected_price, // [เพิ่ม] ค่ามัดจำที่คำนวณแล้ว
                $staff_id, 
                $booking_status, 
                $payment_status,
                $service_id, 
                $location,   
                $note,       
                $slip_file   
            );
            
            if ($stmt_insert_booking->execute()) {
                
                $booking_id = $conn->insert_id;

                // 3. บันทึกในตาราง studio_schedule ถ้าเป็นบริการสตูดิโอ
                if ($service_id === $STUDIO_SERVICE_ID) {
                    
                    // 1. แก้ไข SQL: เพิ่ม Booking_ID เข้าไป และตรวจสอบให้แน่ใจว่ามี 5 คอลัมน์ และ 5 placeholders (?)
                    $sql_insert_schedule = "INSERT INTO studio_schedule (Booking_ID, service_id, booking_date, start_time, status) 
                                            VALUES (?, ?, ?, ?, ?)";
                                            
                    $stmt_insert_schedule = $conn->prepare($sql_insert_schedule);
                    
                    // กำหนดค่าสถานะ 'booked' ชัดเจน
                    $schedule_status = 'จองแล้ว'; 

                    // 2. แก้ไข bind_param: ต้องมี 5 parameters (iisss) และเพิ่ม $booking_id เข้าไปเป็นตัวแรก
                    $stmt_insert_schedule->bind_param("iisss", 
                        $booking_id,        // <<< 1. (i: Integer) Booking ID ที่เพิ่งสร้าง
                        $service_id,        // <<< 2. (i: Integer) Service ID
                        $date_book,         // <<< 3. (s: String) วันที่จอง
                        $start_time_sql,    // <<< 4. (s: String) เวลาเริ่มต้น
                        $schedule_status    // <<< 5. (s: String) สถานะ
                    );

                    if (!$stmt_insert_schedule->execute()) {
                        throw new Exception('ไม่สามารถเพิ่มข้อมูลในตาราง Studio Schedule ได้: ' . $stmt_insert_schedule->error);
                    }
                    $stmt_insert_schedule->close();
                }
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'การจองถูกเพิ่มเรียบร้อยแล้ว';
            } else {
                $conn->rollback();
                $response['message'] = 'ข้อผิดพลาดในการเพิ่มการจอง: ' . $stmt_insert_booking->error;
            }
            $stmt_insert_booking->close();
        } else {
            $conn->rollback();
            $response['message'] = 'ไม่สามารถดำเนินการจองได้เนื่องจากไม่มีรหัสลูกค้า';
        }

    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'เมธอดไม่ถูกต้อง';
}

$conn->close();
echo json_encode($response);
?>