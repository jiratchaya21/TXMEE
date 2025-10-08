<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Xmee Studio</title>
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
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h2 {
            font-size: 32px;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .login-form .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .login-form .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .login-form .input-group input {
            width: calc(100% - 22px); /* Adjust for padding and border */
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .login-form .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }
        .login-form .forgot-password a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .login-form button:hover {
            background-color: #0056b3;
        }
        .login-container .divider {
            margin: 30px 0;
            position: relative;
            text-align: center;
            color: #aaa;
        }
        .login-container .divider::before,
        .login-container .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background-color: #eee;
        }
        .login-container .divider::before {
            left: 0;
        }
        .login-container .divider::after {
            right: 0;
        }
        .social-login button {
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
        .social-login button.google {
            background-color: #db4437; /* Google red */
            color: #fff;
            border-color: #db4437;
        }
        .social-login button.google:hover {
            background-color: #c0392b;
        }
        .social-login button.facebook {
            background-color: #4267b2; /* Facebook blue */
            color: #fff;
            border-color: #4267b2;
        }
        .social-login button.facebook:hover {
            background-color: #365899;
        }
        .social-login button i {
            font-size: 20px;
        }
        .login-container .signup-link {
            margin-top: 25px;
            font-size: 15px;
        }
        .login-container .signup-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        /* Basic FontAwesome for social icons (requires actual FontAwesome CDN in a real project) */
        .fa-google::before { content: 'G'; font-family: sans-serif; font-weight: bold; }
        .fa-facebook::before { content: 'f'; font-family: sans-serif; font-weight: bold; }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>เข้าสู่ระบบ</h2>
        <form class="login-form" action = "Logincheck.php" method = "post">
            <div class="input-group">
                <label for="email">อีเมล</label>
                <input type="text" name ="Email_C" placeholder="อีเมล" required>
            </div>
            <div class="input-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" name ="Password_C" placeholder="รหัสผ่าน" required>
            </div>
            <!-- <div class="forgot-password">
                <a href="#">ลืมรหัสผ่าน?</a>
            </div> -->
            <button type="submit">เข้าสู่ระบบ</button>
        </form>

        <!-- <div class="divider">หรือ</div> -->

        <!-- <div class="social-login">
    <div id="g_id_onload"
        data-client_id="YOUR_GOOGLE_CLIENT_ID" 
        data-context="signin" data-ux_mode="popup" 
        data-callback="handleCredentialResponse" 
        data-auto_prompt="false">
    </div>

    <div class="g_id_signin"
        data-type="standard"
        data-size="large"
        data-theme="outline"
        data-text="continue_with"
        data-shape="rectangular"
        data-logo_alignment="left">
    </div>
    
    </div> -->

<script>
    // 3. ฟังก์ชัน JavaScript ที่จะถูกเรียกใช้เมื่อลงชื่อเข้าใช้สำเร็จ
    function handleCredentialResponse(response) {
        // ในส่วนนี้:
        // 1. นำค่า response.credential (ซึ่งเป็น JWT) ไปถอดรหัส
        // 2. ส่งข้อมูลอีเมล/ชื่อผู้ใช้ ที่ได้ ไปยัง PHP Script บนเซิร์ฟเวอร์ของคุณ (Backend)
        // 3. PHP Script จะตรวจสอบว่าผู้ใช้มีบัญชีหรือไม่
        //    - ถ้ามี: เข้าสู่ระบบ
        //    - ถ้าไม่มี: สร้างบัญชีใหม่และเข้าสู่ระบบ
        
        // ตัวอย่างการส่งไปหลังบ้าน
        // fetch('LoginWithGoogle.php', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        //     body: `credential=${response.credential}`
        // });
        
        console.log("Credential ID: " + response.credential);
    }
</script>

        <div class="signup-link">
            ยังไม่มีบัญชี? <a href="Singup.php">สมัครสมาชิกที่นี่</a>
        </div>
    </div>

</body>
</html>




