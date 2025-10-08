<?php
error_reporting(E_ALL); // แสดงข้อผิดพลาดทั้งหมด
ini_set('display_errors', 1); // แสดงข้อผิดพลาดบนหน้าจอ
session_start();
include 'Datasql.php';

// ตรวจสอบว่าเชื่อมต่อฐานข้อมูลสำเร็จหรือไม่
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); // หากเชื่อมต่อไม่ได้ ให้หยุดและแสดงข้อผิดพลาด
} else {
    echo "DEBUG 1: เชื่อมต่อฐานข้อมูลสำเร็จ<br>";
    // ไม่ต้องใส่ die() ตรงนี้ เพราะต้องการให้โค้ดทำงานต่อ
}

$Email_A = $_POST['Email_A'];
$Password_A = $_POST['PASSWORD_A']; // ตรวจสอบว่าตรงกับ name ใน LoginAdmin.php

echo "DEBUG 2: Email ที่รับมา: " . $Email_A . "<br>";
echo "DEBUG 3: Password ที่รับมา: " . $Password_A . "<br>";
// ลองใส่ die() ตรงนี้เพื่อดูว่ารับค่าอีเมลและรหัสผ่านมาถูกหรือไม่
// ถ้าเห็นข้อความ DEBUG 1, 2, 3 แสดงว่ารับค่ามาได้
// ถ้าไม่เห็น แสดงว่ามีปัญหาก่อนหน้านี้
// die("DEBUG STOP: ตรวจสอบค่าที่รับมา"); // uncomment บรรทัดนี้เพื่อทดสอบ

// ใช้ Prepared Statements เพื่อป้องกัน SQL Injection (สำคัญมาก!)
$sql = "SELECT * FROM admin WHERE Email_A = ? AND PASSWORD_A = ?"; // ชื่อคอลัมน์ PASSWORD_A ต้องตรงกับใน DB
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // หาก prepare() ล้มเหลว ให้แสดงข้อผิดพลาด
    echo "DEBUG 4: เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error . "<br>";
    die("DEBUG STOP: เตรียม SQL ล้มเหลว"); // หยุดและแสดงข้อผิดพลาด
}

$stmt->bind_param("ss", $Email_A, $Password_A);

// ตรวจสอบว่า bind_param สำเร็จหรือไม่
if ($stmt->errno) {
    echo "DEBUG 5: bind_param ล้มเหลว: " . $stmt->error . "<br>";
    die("DEBUG STOP: bind_param ล้มเหลว"); // หยุดและแสดงข้อผิดพลาด
}

$execute_success = $stmt->execute();

// ตรวจสอบว่า execute() สำเร็จหรือไม่
if ($execute_success) {
    echo "DEBUG 6: คำสั่ง SQL ทำงานสำเร็จ<br>";
} else {
    echo "DEBUG 7: คำสั่ง SQL ทำงานล้มเหลว: " . $stmt->error . "<br>";
    die("DEBUG STOP: execute ล้มเหลว"); // หยุดและแสดงข้อผิดพลาด
}

$result = $stmt->get_result();
$num_rows = $result->num_rows; // นับจำนวนแถวที่พบ
echo "DEBUG 8: พบ " . $num_rows . " แถวในฐานข้อมูล<br>";

// *** สำคัญ: ถ้า $num_rows เป็น 0, $row จะเป็น null ทำให้เข้าเงื่อนไข else
if ($num_rows > 0) { // ตรวจสอบว่ามีแถวข้อมูลที่พบ
    $row = $result->fetch_assoc(); // ดึงข้อมูลแถวแรก

    echo "DEBUG 9: พบข้อมูล Admin: " . json_encode($row) . "<br>"; // แสดงข้อมูลที่ดึงได้
    
    // ตั้งค่า session สำหรับแอดมิน
    $_SESSION["Email_A"] = $row['Email_A'];
    $_SESSION["PASSWORD_A"] = $row['PASSWORD_A']; // แก้ไขตรงนี้ให้เป็น 'PASSWORD_A' เพื่อให้ตรงกับคอลัมน์ใน DB
    
    // ล้างข้อความ error ก่อน
    unset($_SESSION["error"]);

    echo "DEBUG 10: กำลังจะเปลี่ยนหน้าไป BookAdmin.php<br>";
    // หากโค้ดมาถึงตรงนี้ แสดงว่าสำเร็จ แต่ถ้ายังไม่เปลี่ยนหน้าอาจมีปัญหา HTTP header
    header("Location: BookAdmin.php");
    exit(); // ใช้ exit() แทน die() หลัง header เพื่อให้มั่นใจว่าไม่มีโค้ดอื่นทำงานต่อ
} else {
    // เก็บข้อความ error ลง session และ redirect กลับไปหน้าล็อกอิน
    echo "DEBUG 11: ไม่พบข้อมูล Admin ที่ตรงกัน<br>";
    $_SESSION["error"] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    header("Location: LoginAdmin.php");
    exit();
}
$stmt->close();
$conn->close();
?>