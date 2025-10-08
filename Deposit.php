<?php
    include "Datasql.php"; // เรียกไฟล์เชื่อมต่อฐานข้อมูล
    session_start();

    // ส่วนที่ 1: ตรวจสอบและดึงข้อมูลการจอง
    // หากต้องการให้แสดงข้อมูลการจองของลูกค้าที่เข้าสู่ระบบ ให้ใช้ $_SESSION['Cus_id'] เพื่อค้นหา
    // ตัวอย่างนี้ใช้ $_GET['booking_id'] เพื่อให้ทดสอบได้ง่าย
    if (!isset($_GET['booking_id'])) {
        die("Error: ไม่พบหมายเลขการจอง.");
    }
    $booking_id = $_GET['booking_id'];

    // ดึงข้อมูลการจองจากฐานข้อมูล
    $sql = "SELECT B.*, C.F_name, C.L_name FROM Booking B INNER JOIN Customer C ON B.Cus_id = C.Cus_id WHERE B.Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
    } else {
        die("Error: ไม่พบข้อมูลการจองสำหรับ ID นี้.");
    }
    $stmt->close();

    // ส่วนที่ 2: คำนวณยอดเงิน
    $total_cost = $booking_data['Total_P'];
    $deposit_paid = 0;
    $remaining_balance = $total_cost;

    // ตรวจสอบสถานะการชำระเงินเพื่อคำนวณยอดที่ชำระแล้ว
    if ($booking_data['Payment_Status'] === 'ชำระเงินมัดจำแล้ว') {
        // ยอดมัดจำ 50%
        $deposit_paid = $total_cost * 0.50;
        $remaining_balance = $total_cost - $deposit_paid;
    } else if ($booking_data['Payment_Status'] === 'ชำระเงินเต็มจำนวนแล้ว') {
        // ยอดที่ชำระเต็ม
        $deposit_paid = $total_cost;
        $remaining_balance = 0;
    }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงินมัดจำคงเหลือ - Xmee Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS จากไฟล์ Homepage1.php เพื่อให้สไตล์คงที่ */
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: #333;
            background-color: #000000ff;
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
        .deposit-section {
            background-color: #f9f9f9;
            padding: 80px 5%;
            text-align: center;
        }
        .deposit-details-card {
            background-color: #fff;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: left;
        }
        .deposit-details-card h2 {
            font-size: 36px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        .deposit-details-card p {
            font-size: 18px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 15px;
        }
        .deposit-details-card p strong {
            color: #000;
        }
        .payment-button {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #28a745;
            color: #fff;
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 30px;
            transition: background-color 0.3s ease;
        }
        .payment-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="Homepage1.php" class="logo">Xmee Studio</a>
        <nav class="nav-menu">
            <ul>
                <li><a href="Homepage1.php#about-us">เกี่ยวกับเรา</a></li>
                <li><a href="Homepage1.php#features">จุดเด่นสตูดิโอ</a></li>
                <li><a href="Homepage1.php#services">บริการเพิ่มเติม</a></li>
                <li><a href="Homepage1.php#contact">ติดต่อเรา</a></li>
                <li><a href="Book_Cus.php">ข้อมูลการจอง</a></li>
            </ul>
        </nav>
        <?php
            if (isset($_SESSION["F_name"])) {
                echo "<div class='username text-black'>";
                echo 'สวัสดีคุณ '.$_SESSION["F_name"];
                echo "</div>";
            }
        ?>
        <div class="auth-buttons">
            <a href="Homepage.php" style="background-color: #a72828ff;">ออกจากระบบ</a>
        </div>
    </header>

    <section class="deposit-section">
        <div class="deposit-details-card">
            <h2>รายละเอียดการชำระเงินมัดจำคงเหลือ</h2>
            
            <p><strong>สวัสดีคุณ:</strong> 
                <?php echo htmlspecialchars($booking_data['F_name'] . ' ' . $booking_data['L_name']); ?>
            </p>
            <p><strong>หมายเลขการจอง:</strong> <?php echo htmlspecialchars($booking_data['Booking_ID']); ?></p>
            <p><strong>ยอดรวมค่าบริการ:</strong> <?php echo number_format($total_cost, 2); ?> บาท</p>
            <p><strong>ยอดเงินมัดจำที่ชำระแล้ว:</strong> <?php echo number_format($deposit_paid, 2); ?> บาท</p>
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
            <p style="font-size: 24px; font-weight: 700; color: #a72828ff;">
                <strong>ยอดเงินคงเหลือที่ต้องชำระ:</strong> <?php echo number_format($remaining_balance, 2); ?> บาท
            </p>

            <a href="Payment_Cus.php?booking_id=<?php echo htmlspecialchars($booking_data['Booking_ID']); ?>&type=full" class="payment-button">คลิกเพื่อชำระเงิน</a>
            <p style="font-size: 14px; color: #888; text-align: center; margin-top: 20px;">
                *กรุณาชำระเงินคงเหลือภายในวันที่ที่กำหนด เพื่อยืนยันการจอง
            </p>
        </div>
    </section>

</body>
</html>