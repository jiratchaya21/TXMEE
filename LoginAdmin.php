<?php
session_start();
include 'Datasql.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Email_A = $_POST['Email_A'];
    $Password_A = $_POST['PASSWORD_A'];

    $sql = "SELECT Staff_id, Email_A, Password_A FROM admin WHERE Email_A = ? AND Password_A = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $Email_A, $Password_A);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // ส่วนที่แก้ไข: เพิ่ม Staff_id เข้าไปใน session
        $_SESSION['Staff_id'] = $row['Staff_id'];
        $_SESSION['Email_A'] = $row['Email_A'];
        
        header("Location: BookAdmin.php");
        exit();
    } else {
        $_SESSION["error"] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        header("Location: LoginAdmin.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .admin-login-container { background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); text-align: center; max-width: 400px; width: 100%; box-sizing: border-box; }
        .admin-login-container h2 { font-size: 24px; margin-bottom: 25px; color: #333; }
        .admin-login-form .input-group { margin-bottom: 20px; text-align: left; }
        .admin-login-form label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .admin-login-form input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        .admin-login-form button { width: 100%; padding: 15px; border: none; background-color: #007bff; color: white; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; transition: background-color 0.3s ease; }
        .admin-login-form button:hover { background-color: #0056b3; }
        .admin-login-container .back-to-home { margin-top: 25px; font-size: 14px; }
        .admin-login-container .back-to-home a { color: #007bff; text-decoration: none; }
        .text-danger { color: #dc3545; margin-top: 15px; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <h2>เข้าสู่ระบบผู้ดูแลระบบ</h2>
        <form action="LoginAdmin.php" method="POST" class="admin-login-form">
            <div class="input-group">
                <label for="admin_email">อีเมล</label>
                <input type="text" name="Email_A" placeholder="อีเมล" required>
            </div>
            <div class="input-group">
                <label for="admin_password">รหัสผ่าน</label>
                <input type="password" name="PASSWORD_A" placeholder="รหัสผ่าน" required>
            </div>
            <button type="submit">เข้าสู่ระบบ</button>
            <?php
            if (isset($_SESSION["error"])) {
                echo "<div class='text-danger'>";
                echo $_SESSION["error"];
                echo "</div>";
                unset($_SESSION["error"]);
            }
            ?>
        </form>
        <div class="back-to-home">
            <a href="index.php">กลับหน้าหลัก</a>
        </div>
    </div>
</body>
</html>