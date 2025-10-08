<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Xmee Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: #333;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .signup-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px; /* เพิ่มขนาดขึ้นเล็กน้อยเพื่อให้มีพื้นที่ */
            text-align: center;
        }
        .signup-container h2 {
            font-size: 32px;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .signup-form .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .signup-form .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .signup-form .input-group input {
            width: calc(100% - 22px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .signup-form button {
            width: 100%;
            padding: 12px;
            background-color: #28a745; /* สีเขียวสำหรับปุ่มสมัครสมาชิก */
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        .signup-form button:hover {
            background-color: #218838;
        }
        .signup-container .divider {
            margin: 30px 0;
            position: relative;
            text-align: center;
            color: #aaa;
        }
        .signup-container .divider::before,
        .signup-container .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background-color: #eee;
        }
        .signup-container .divider::before {
            left: 0;
        }
        .signup-container .divider::after {
            right: 0;
        }
        .social-signup button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.3s ease;
        }
        .social-signup button.google {
            background-color: #db4437; /* Google red */
            color: #fff;
            border-color: #db4437;
        }
        .social-signup button.google:hover {
            background-color: #c0392b;
        }
        .social-signup button.facebook {
            background-color: #4267b2; /* Facebook blue */
            color: #fff;
            border-color: #4267b2;
        }
        .social-signup button.facebook:hover {
            background-color: #365899;
        }
        .social-signup button i {
            font-size: 20px;
        }
        .signup-container .login-link {
            margin-top: 25px;
            font-size: 15px;
        }
        .signup-container .login-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .signup-container .terms-privacy {
            font-size: 13px;
            color: #777;
            margin-top: 20px;
            line-height: 1.5;
        }
        .signup-container .terms-privacy a {
            color: #007bff;
            text-decoration: none;
        }

        /* Basic FontAwesome for social icons (requires actual FontAwesome CDN in a real project) */
        .fa-google::before { content: 'G'; font-family: sans-serif; font-weight: bold; }
        .fa-facebook::before { content: 'f'; font-family: sans-serif; font-weight: bold; }
    </style>
</head>
<body>

    <div class="signup-container">
        <h2>สมัครสมาชิก</h2>
        <form class="signup-form" action = 'SingupCustomer.php' method = "post">
            <div class="input-group">
                <label for="full_name">ชื่อ</label>
                <input type="text" name ="F_name" placeholder="กรอกชื่อ-นามสกุล" required>
            </div>
            <div class="input-group">
                <label for="full_name">นามสกุล</label>
                <input type="text" name ="L_name" placeholder="กรอกชื่อ-นามสกุล" required>
            </div>
            <div class="input-group">
                <label for="email">อีเมล</label>
                <input type="email" name ="Email_C" placeholder="ตัวอย่าง: user@example.com" required>
            </div>
            <div class="input-group">
                <label for="phone">เบอร์โทรศัพท์</label>
                <input type="tel" name ="Phone" placeholder="ตัวอย่าง: 08XXXXXXXX" required>
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" name ="Password_C" placeholder="ตั้งรหัสผ่าน (อย่างน้อย 6 ตัวอักษร)" required>
            </div>
            <!-- <div class="input-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                <input type="password" id="confirm_password" placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>
            </div> -->
            <button type="submit">สร้างบัญชี</button>
        </form>

        <!-- <div class="divider">หรือสมัครด้วย</div> -->

        <!-- <div class="social-signup">
            <button class="google">
                <i class="fa-google"></i> สมัครด้วย Google
            </button> -->
            <!-- <button class="facebook">
                <i class="fa-facebook"></i> สมัครด้วย Facebook
            </button> -->
        <!-- </div> -->

        <!-- <div class="terms-privacy"> -->
            <!-- ใส่ลิ้งค์ -->
            <!-- การสมัครสมาชิกถือว่าคุณยอมรับ <a href="#">ข้อกำหนดและเงื่อนไข</a> และ <a href="#">นโยบายความเป็นส่วนตัว</a> ของเรา -->
        <!-- </div> -->

        <div class="login-link">
            มีบัญชีอยู่แล้ว? <a href="Login.php">เข้าสู่ระบบที่นี่</a>
        </div>
    </div>

</body>
</html>