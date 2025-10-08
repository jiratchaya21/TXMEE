<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xmee Studio - สตูดิโอถ่ายภาพครบวงจร</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: #333;
            background-color: #f0f2f5;
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
        .header .auth-buttons a.cart {
            background-color: #6c757d;
        }
        .header .auth-buttons a.cart:hover {
            background-color: #5a6268;
        }
        .header .auth-buttons a:hover {
            background-color: #0056b3;
        }
        .username {
            margin-right: 20px;
            font-weight: bold;
        }

        /* Main Content with Split View */
        .main-container {
            display: flex;
            flex-wrap: wrap;
            min-height: calc(100vh - 80px); /* Adjust based on header/footer height */
        }
        .split-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 50px 20px;
            color: #fff;
            background-size: cover;
            background-position: center;
            min-height: 500px;
            position: relative;
        }
        .split-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4); /* Overlay for text readability */
        }
        .split-content {
            position: relative;
            z-index: 1;
        }
        .split-section h1 {
            font-size: 58px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .split-section p {
            font-size: 24px;
            margin-bottom: 40px;
            font-weight: 300;
        }
        .split-section .button-primary {
            background-color: #53827e;
            color: #fff;
            padding: 5px 10px;
            /* border-radius: 8px; */
            text-decoration: none;
            font-size: 25px;
            font-weight: 300;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .split-section .button-primary:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        #studio-section {
            background-image: url('Page1.png');
        }
        #saycheeze-section {
            background-image: url('Page2.png');
        }

        /* About Us Section */
        .about-section {
            padding: 80px 5%;
            background-color: #fff;
            text-align: center;
        }
        .about-section h2 {
            font-size: 42px;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: 700;
        }
        .about-section p {
            font-size: 18px;
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto 40px auto;
            color: #666;
        }
        .about-section .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        .about-section .gallery img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Studio Features/Services Section */
        .features-section {
            padding: 80px 5%;
            background-color: #f9f9f9;
        }
        .features-section h2 {
            text-align: center;
            font-size: 42px;
            margin-bottom: 50px;
            color: #2c3e50;
            font-weight: 700;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            text-align: center;
        }
         .feature-item {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .feature-item:hover {
            transform: translateY(-8px);
        }
        .feature-item .icon {
            font-size: 50px;
            color: #007bff;
            margin-bottom: 20px;
        }
        .feature-item h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .feature-item p {
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
        .footer .social-links a {
            color: #fff;
            margin: 0 10px;
            font-size: 24px;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer .social-links a:hover {
            color: #007bff;
        }
        .footer p {
            margin-top: 15px;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .split-section h1 {
                font-size: 48px;
            }
            .split-section p {
                font-size: 20px;
            }
            .about-section h2, .features-section h2 {
                font-size: 36px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
            }
            .header .logo {
                margin-bottom: 10px;
            }
            .header .nav-menu ul {
                flex-direction: column;
                margin-top: 10px;
                width: 100%;
            }
            .header .nav-menu ul li {
                margin: 5px 0;
                width: 100%;
                text-align: center;
            }
            .header .auth-buttons {
                margin-top: 15px;
                width: 100%;
                display: flex;
                justify-content: center;
            }
            .header .auth-buttons a {
                flex: 1;
                text-align: center;
                margin: 0 5px;
            }

            .main-container {
                flex-direction: column;
            }
            .split-section {
                min-height: 400px;
            }
            .split-section h1 {
                font-size: 38px;
            }
            .split-section p {
                font-size: 18px;
            }
            .split-section .button-primary {
                padding: 12px 25px;
                font-size: 18px;
            }

            .about-section, .features-section {
                padding: 50px 20px;
            }
            .about-section h2, .features-section h2 {
                font-size: 32px;
            }
            .about-section p {
                font-size: 16px;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .split-section h1 {
                font-size: 30px;
            }
            .split-section p {
                font-size: 16px;
            }
            .header .auth-buttons {
                flex-direction: column;
            }
            .header .auth-buttons a {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="Homepage1.php" class="logo">Xmee Studio</a>
        <nav class="nav-menu">
            <ul>
                <li><a href="#about-us">เกี่ยวกับเรา</a></li>
                <li><a href="#features">จุดเด่นสตูดิโอ</a></li>
                <li><a href="Book_Cus.php">ข้อมูลการจอง</a></li>
            </ul>
        </nav>
            <?php
                    if (isset($_SESSION["F_name"])) {
                        echo "<div class = 'username text-black'>";
                        echo 'สวัสดีคุณ'.$_SESSION["F_name"];
                        echo "</div>";
                    }
            ?>
        <div class="auth-buttons">
            <a href="Homepage.php" style="background-color: #a72828ff;">ออกจากระบบ</a>
        </div>
    </header>

    <div class="main-container">
        <section id="studio-section" class="split-section">
            <div class="split-content">
                <!-- <h1>สตูดิโอถ่ายภาพระดับมืออาชีพ</h1>
                <p>สตูดิโอถ่ายภาพครบวงจร พร้อมอุปกรณ์และบรรยากาศที่คุณต้องการ</p> -->
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <a href="Bookstudio.php" class="button-primary">RESERVE | จอง</a>
            </div>
        </section>

        <section id="saycheeze-section" class="split-section">
            <div class="split-content">
                <!-- <h1>SayCheeze</h1>
                <p>บริการตู้ Photobooth และ 360 VideoBooth Smile in Every Spin ยิ้มวนไปให้โลกหมุนตาม</p> -->
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <a href="SayCheeze.php" class="button-primary">RESERVE | จอง</a>
            </div>
        </section>
    </div>

    <section id="about-us" class="about-section">
        <h2>เกี่ยวกับ Xmee Studio</h2>
        <p>
            Xmee Studio ก่อตั้งขึ้นด้วยความมุ่งมั่นที่จะเป็นพื้นที่สร้างสรรค์สำหรับการถ่ายภาพทุกประเภท ไม่ว่าจะเป็นภาพบุคคล, แฟชั่น, สินค้า, หรือวิดีโอ เรามีสตูดิโอที่หลากหลายสไตล์ พร้อมอุปกรณ์ที่ทันสมัย และทีมงานผู้เชี่ยวชาญที่พร้อมให้คำแนะนำ เพื่อให้ทุกไอเดียของคุณเป็นจริงได้ที่นี่
        </p>
        <div class="gallery">
            <img src="xmee.jpg" alt="XmeeStudio">
            <img src="Saycheeze.jpg" alt="SayCheeze">
            <img src="Saycheeze4.jpg" alt="Photobooth 360">
        </div>
    </section>

    <section id="features" class="features-section">
        <h2>จุดเด่นของสตูดิโอเรา</h2>
        <div class="features-grid">
            <div class="feature-item">
                <div class="icon">&#9733;</div> <h3>สตูดิโอหลากหลายสไตล์</h3>
                <p>เลือกสตูดิโอที่ตรงกับคอนเซ็ปต์ของคุณ ไม่ว่าจะเป็นมินิมอล, ลอฟท์, หรือกรีนสกรีน</p>
            </div>
            <div class="feature-item">
                <div class="icon">&#9881;</div> <h3>อุปกรณ์ครบครันทันสมัย</h3>
                <p>พร้อมใช้งานทั้งไฟสตูดิโอ, ฉาก, พร็อพ และอุปกรณ์เสริม</p>
            </div>
            <div class="feature-item">
                <div class="icon">&#128222;</div> <h3>ทีมงานมืออาชีพ</h3>
                <p>มีบริการช่างภาพถ้าคุณต้องการ คิดค่าบริการเพิ่มเติม</p>
            </div>
            <div class="feature-item">
                <div class="icon">&#128205;</div> <h3>ทำเลเดินทางสะดวก</h3>
                <p>ตั้งอยู่ในใจกลางเมือง พร้อมที่จอดรถ</p>
            </div>
        </div>
    </section>

    <footer id="contact" class="footer">
        <div class="social-links">
            <a href="https://www.facebook.com/XMEEstudio/?locale=th_TH">Facebook</a>
            <a href="http://instagram.com/explore/locations/635243489902414/xmee-studio/">Instagram</a>
        </div>
        <p>&copy; 2025 Xmee Studio. All rights reserved.</p>
        <p>ที่อยู่: ซอยท่าตะโก หมู่บ้านเดอะเวิร์คสเปซ อิน-ทาวน์ Nakhon Ratchasima, Thailand, Nakhon Ratchasima 30000 | โทร: 093 961 4655 | อีเมล: xmeestudio@gmail.com</p>
    </footer>

</body>
</html>