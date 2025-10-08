<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SayCheeze - Photobooth & 360 Video Booth | Xmee Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            color: #333;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header .logo {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            text-decoration: none;
        }
        .header .nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .header .nav-menu ul li {
            margin-left: 30px;
        }
        .header .nav-menu ul li a {
            text-decoration: none;
            color: #555;
            font-weight: 400;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        .header .nav-menu ul li a:hover {
            color: #007bff;
        }
        .header .auth-buttons a {
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            padding: 8px 15px;
            border-radius: 5px;
            margin-left: 10px;
            font-size: 15px;
            transition: background-color 0.3s ease;
        }

        /* SayCheeze Hero Section */
        .saycheeze-hero {
            background: url('Saycheeze.jpg') no-repeat center center/cover;
            color: #fff;
            text-align: center;
            padding: 150px 20px;
            position: relative;
        }
        .saycheeze-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Overlay สีดำโปร่งแสง */
        }
        .saycheeze-hero .hero-content {
            position: relative;
            z-index: 10;
        }
        .saycheeze-hero h1 {
            font-size: 64px;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.8);
        }
        .saycheeze-hero p {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 40px;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.6);
        }
        .saycheeze-hero .cta-button {
            background-color: #f39c12;
            color: #fff;
            padding: 15px 30px;
            /* border-radius: 8px; */
            text-decoration: none;
            font-size: 20px;
            font-weight: 300;
            transition: background-color 0.3s ease;
        }
        .saycheeze-hero .cta-button:hover {
            background-color: #e67e22;
        }

        /* Services Grid */
        .saycheeze-services {
            padding: 80px 5%;
            text-align: center;
        }
        .saycheeze-services h2 {
            font-size: 42px;
            margin-bottom: 50px;
            color: #2c3e50;
            font-weight: 700;
        }
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
        }
        .service-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: left;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .service-card:hover {
            transform: translateY(-10px);
        }
        .service-card img {
            width: 100%;
            max-height: 250px; /* กำหนดความสูงสูงสุดเพื่อให้รูปภาพไม่ใหญ่เกินไป */
            object-fit: contain; /* สำคัญมาก: ทำให้รูปภาพปรับขนาดพอดี โดยแสดงผลทั้งภาพ ไม่มีการครอป */
            border-bottom: 3px solid #f4f7f6;
        }
        .service-card .content {
            padding: 30px;
        }
        .service-card h3 {
            font-size: 28px;
            color: #d35400;
            margin-bottom: 15px;
        }
        .service-card p {
            font-size: 18px;
            line-height: 1.6;
            color: #555;
        }

        /* Features Section */
        .saycheeze-features {
            background-color: #fff;
            padding: 80px 5%;
            text-align: center;
        }
        .saycheeze-features h2 {
            font-size: 42px;
            margin-bottom: 50px;
            color: #2c3e50;
            font-weight: 700;
        }
        .features-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        .feature-box {
            text-align: center;
        }
        .feature-box .icon {
            font-size: 50px;
            color: #007bff;
            margin-bottom: 15px;
        }
        .feature-box h4 {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .feature-box p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background-color: #2c3e50;
            color: #fff;
            padding: 40px 5%;
            text-align: center;
            font-size: 14px;
        }
        .footer p {
            margin: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .header .nav-menu {
                margin-top: 10px;
            }
            .saycheeze-hero h1 {
                font-size: 48px;
            }
            .saycheeze-hero p {
                font-size: 20px;
            }
            .service-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="Homepage1.php" class="logo">Say Cheeze</a>
        <nav class="nav-menu">
            <ul>
                <li><a href="#photobooth">Photobooth</a></li>
                <li><a href="#360booth">360 Video Booth</a></li>
                <li><a href="#contact">ติดต่อเรา</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <a href="Homepage1.php">กลับหน้าหลัก</a>
        </div>
    </header>

    <section class="saycheeze-hero">
        <div class="hero-content">
            <h1>SayCheeze</h1>
            <p>Photobooth และ 360 Video Booth ที่จะทำให้งานของคุณสนุกยิ่งขึ้น!</p>
            <a href="BookSayCheeze.php" class="cta-button">จองบริการ SayCheeze</a>
        </div>
    </section>

    <section id="photobooth" class="saycheeze-services">
        <h2>บริการ SayCheeze Photobooth</h2>
        <div class="service-grid">
            <div class="service-card">
                <img src="Saycheeze5.jpg" alt="บริการตู้ Photobooth">
                <div class="content">
                    <h3>ตู้ Photobooth แบบทันสมัย</h3>
                    <p>
                        ให้แขกในงานของคุณได้เก็บภาพความประทับใจกลับไปเป็นที่ระลึก ด้วยตู้ Photobooth ที่ใช้งานง่าย ดีไซน์สวยงาม พร้อมพร็อพสนุกๆ มากมาย เรามีเลย์เอาท์ภาพให้เลือกหลากหลายแบบตามธีมงานของคุณ
                    </p>
                </div>
            </div>
            <div class="service-card">
                <img src="SayCheeze4.jpg" alt="ตัวอย่างภาพ Photobooth">
                <div class="content">
                    <h3>สิ่งที่คุณจะได้รับ</h3>
                    <ul>
                        <li>รูปถ่ายคุณภาพสูง พิมพ์ทันที</li>
                        <li>พร็อพสำหรับถ่ายภาพหลากหลาย</li>
                        <li>ดีไซน์กรอบรูปตามธีมงาน</li>
                        <li>ทีมงานคอยดูแลตลอดงาน</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="360booth" class="saycheeze-services" style="background-color: #fff;">
        <h2>SayCheeze 360 Video Booth</h2>
        <div class="service-grid">
            <div class="service-card">
                <img src="Saycheeze1.jpg" alt="บริการ 360 Video Booth">
                <div class="content">
                    <h3>Smile in Every Spin ยิ้มวนไปให้โลกหมุนตาม</h3>
                    <p>
                        สร้างความตื่นเต้นและประสบการณ์ที่ไม่เหมือนใครให้กับแขกด้วย 360 Video Booth แพลตฟอร์มที่หมุนได้รอบตัว ให้คุณได้สร้างสรรค์วิดีโอแบบสโลว์โมชั่นสุดเจ๋ง
                    </p>
                </div>
            </div>
            <div class="service-card">
                <img src="Saycheeze2.jpg" alt="ตัวอย่างวิดีโอ 360">
                <div class="content">
                    <h3>คุณสมบัติเด่น</h3>
                    <ul>
                        <li>ถ่ายวิดีโอ 360 องศา พร้อมเอฟเฟกต์ Slow Motion</li>
                        <li>ไฟส่องสว่างระดับสตูดิโอ</li>
                        <li>แชร์วิดีโอไปยังโซเชียลมีเดียได้ทันที</li>
                        <li>เหมาะสำหรับงานปาร์ตี้ งานแต่งงาน หรืออีเวนต์ต่างๆ</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="saycheeze-features">
        <h2>แพ็กเกจและราคา</h2>
        <div class="features-list">
            <div class="feature-box">
                <div class="icon">&#128176;</div>
                <h4>ราคาเริ่มต้น</h4>
                <p>สอบถามรายละเอียดแพ็กเกจ Photobooth และ 360 Video Booth ได้โดยตรง</p>
            </div>
            <div class="feature-box">
                <div class="icon">&#128222;</div>
                <h4>ติดต่อเรา</h4>
                <p>โทร: 093 961 4655</p>
                <p>อีเมล: xmeestudio@gmail.com</p>
            </div>
            <div class="feature-box">
                <div class="icon">&#128248;</div>
                <h4>ช่องทางโซเชียล</h4>
                <p><a href="https://www.facebook.com/XMEEstudio/?locale=th_TH" style="color: #666;">Facebook: Xmee Studio</a></p>
                <p><a href="http://instagram.com/explore/locations/635243489902414/xmee-studio/" style="color: #666;">Instagram: @xmeestudio</a></p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; 2025 Xmee Studio. All rights reserved.</p>
    </footer>

</body>
</html>