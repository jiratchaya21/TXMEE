<?php
    include 'Datasql.php';
    session_start();

    // ทำลาย session เดิมทั้งหมดก่อนที่จะดำเนินการต่อ
    session_unset();
    session_destroy();
    
    // เริ่ม session ใหม่
    session_start();

    // รับข้อมูลจากฟอร์ม
    $Email_C = $_POST['Email_C'];
    $Password_C= $_POST['Password_C'];

    // เช็คข้อมูลที่ผู้ใช้กรอกตรงกับฐานข้อมูล
    $sql = "SELECT Cus_id, Email_C, Password_C, F_name FROM customer WHERE Email_C = ? AND Password_C = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ss", $Email_C, $Password_C);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            // เมื่อล็อกอินสำเร็จ ให้ตั้งค่า session ใหม่
            $_SESSION["Cus_id"] = $row['Cus_id'];
            $_SESSION["Email_C"] = $row['Email_C'];
            $_SESSION["F_name"] = $row['F_name'];
            
            header("location: Homepage1.php");
            exit(); 
        } else {
            // หากล็อกอินไม่สำเร็จ
            $_SESSION["error"] = "Your username or password is invalid";
            header("location: Login.php");
            exit();
        }
    } else {
        // หากเกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL
        $_SESSION["error"] = "Error preparing statement: " . $conn->error;
        header("location: Login.php");
        exit();
    }
?>