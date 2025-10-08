<?php
//ติดต่อฐานข้อมูล mysql
$servername = "localhost"; //ชื่อ Server
$username = "root"; //Username ของ Database
$password = ""; //password ของ Database
$dbname = "Xmeetest1"; //ชื่อ Database

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า charset เป็น utf8mb4 เพื่อรองรับภาษาไทยอย่างสมบูรณ์
if (!$conn->set_charset("utf8mb4")) {
    die("Error loading character set utf8mb4: " . $conn->error);
}

?>