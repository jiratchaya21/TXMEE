<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    include "Datasql.php"; 
    session_start();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!isset($_POST['booking_id']) || !isset($_FILES['slip_file']) || !isset($_POST['amount_paid']) || !isset($_POST['payment_option'])) {
            die("Error: Invalid request. Missing required data.");
        }
        
        $booking_id = $_POST['booking_id'];
        $file = $_FILES['slip_file'];
        $amount_paid = $_POST['amount_paid'];
        $payment_option = $_POST['payment_option'];
        
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_file_name = 'slip_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_file_name;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            die("Upload Error: " . $file['error']);
        }
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            die("Error: Failed to move uploaded file. Please check folder permissions.");
        }
        
        // กำหนดสถานะการชำระเงินตามตัวเลือกที่ลูกค้าเลือก
        $payment_status_db_booking = '';
        if ($payment_option === 'เต็มจำนวน' || $payment_option === 'ยอดคงเหลือ') {
            $payment_status_db_booking = 'รอตรวจสอบ (เต็มจำนวน)';
        } else if ($payment_option === 'มัดจำ') { 
            $payment_status_db_booking = 'รอตรวจสอบ (มัดจำ)';
        } else {
            // กรณีที่ไม่ตรงกับเงื่อนไขข้างต้น
            $payment_status_db_booking = 'รอดำเนินการ';
        }
        
        // อัปเดตตาราง Booking
        $sql_update_booking = "UPDATE Booking SET Payment_Status = ?, Slip_File = ? WHERE Booking_ID = ?";
        $stmt_update_booking = $conn->prepare($sql_update_booking);
        $stmt_update_booking->bind_param("ssi", $payment_status_db_booking, $unique_file_name, $booking_id);
        
        if (!$stmt_update_booking->execute()) {
             die("Error: Could not update booking status. " . $conn->error);
        }
        $stmt_update_booking->close();
        
        // บันทึกข้อมูลลงในตาราง Payment
        $stmt_insert_payment = $conn->prepare("INSERT INTO Payment (Booking_ID, PayDate, A_Price, P_Method, P_Payment, P_Status) VALUES (?, NOW(), ?, ?, ?, ?)");
        
        if ($stmt_insert_payment) {
            $payment_method = 'โอนเงิน';
            $payment_status_db_payment = 'รอดำเนินการ';
            
            $stmt_insert_payment->bind_param("idsss", $booking_id, $amount_paid, $payment_method, $unique_file_name, $payment_status_db_payment);
            $stmt_insert_payment->execute();
            $stmt_insert_payment->close();

            header("Location: Book_Cus.php?upload_status=success");
            exit();
        } else {
            die("Error: Could not prepare SQL statement for payment. " . $conn->error);
        }
    } else {
        die("Error: Invalid request method.");
    }
?>