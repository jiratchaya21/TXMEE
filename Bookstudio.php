<?php
    include "Datasql.php";
    session_start();

    $customer_data = null;
    $is_logged_in = false;
    $Cus_id = null;

    if (isset($_SESSION['Cus_id'])) {
        $is_logged_in = true;
        $Cus_id = $_SESSION['Cus_id'];

        $sql_customer = "SELECT F_name, L_name, Email_C, phone FROM Customer WHERE Cus_id = ?";
        $stmt_customer = $conn->prepare($sql_customer);
        $stmt_customer->bind_param("i", $Cus_id);
        $stmt_customer->execute();
        $result_customer = $stmt_customer->get_result();

        if ($result_customer->num_rows > 0) {
            $customer_data = $result_customer->fetch_assoc();
        }
        $stmt_customer->close();
    }

    // โค้ดใหม่ที่ดึงข้อมูลการจองจากตาราง studio_schedule
    $sql = "SELECT service_id, booking_date, start_time FROM studio_schedule WHERE status = 'จองแล้ว'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_slots_php = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $service_id = $row['service_id'];
            $booked_date_time = $row['booking_date'] . ' ' . substr($row['start_time'], 0, 5); // เอาแค่ชั่วโมงและนาที
            if (!isset($booked_slots_php[$service_id])) {
                $booked_slots_php[$service_id] = [];
            }
            $booked_slots_php[$service_id][] = $booked_date_time;
        }
    }
    $stmt->close();
    $conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองบริการทั้งหมด | Xmee Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            color: #333;
            background-color: #f4f7f6;
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

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }
        .studio-details-section {
            flex: 2;
            min-width: 600px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .booking-sidebar {
            flex: 1;
            min-width: 300px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 100px;
            align-self: flex-start;
            height: fit-content;
        }

        .booking-sidebar .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Kanit', sans-serif;
            font-size: 16px;
            box-sizing: border-box;
            resize: vertical;
        }

        .studio-details-section h1 {
            font-size: 40px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .studio-details-section .location-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .studio-details-section .location-price .location {
            font-size: 18px;
            color: #777;
        }
        .studio-details-section .location-price .price-per-hour {
            font-size: 28px;
            font-weight: 700;
            color: #007bff;
        }
        .studio-details-section .gallery {
            display: grid;
            grid-template-columns: 2fr 1fr;
            grid-template-rows: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 30px;
            max-height: 500px;
            overflow: hidden;
        }
        .studio-details-section .gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .studio-details-section .gallery img:hover {
            transform: scale(1.02);
        }
        .studio-details-section .gallery img:first-child {
            grid-column: 1 / 2;
            grid-row: 1 / 3;
            height: 100%;
        }
        .studio-details-section .description h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .studio-details-section .description p {
            font-size: 16px;
            line-height: 1.7;
            color: #555;
            margin-bottom: 30px;
        }
        .studio-details-section .amenities h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .studio-details-section .amenities ul {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .studio-details-section .amenities ul li {
            font-size: 16px;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .studio-details-section .amenities ul li i {
            color: #007bff;
            font-size: 20px;
        }

        .booking-sidebar h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
        }
        .booking-sidebar .form-group {
            margin-bottom: 20px;
        }
        .booking-sidebar .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 16px;
        }
        .booking-sidebar .form-group input[type="date"],
        .booking-sidebar .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .time-slot {
            padding: 10px 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            font-size: 15px;
            background-color: #f9f9f9;
        }
        .time-slot.selected {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .time-slot.booked {
            background-color: #e0e0e0;
            color: #aaa;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        .time-slot.booked:hover {
            background-color: #e0e0e0;
        }
        .booking-summary {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .booking-summary p {
            display: flex;
            justify-content: space-between;
            font-size: 17px;
            margin-bottom: 10px;
        }
        .booking-summary p span:first-child {
            font-weight: 500;
            color: #555;
        }
        .booking-summary .total-price {
            font-size: 24px;
            font-weight: 700;
            color: #28a745;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
        }
        .add-to-cart-button {
            width: 100%;
            padding: 15px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .add-to-cart-button:hover {
            background-color: #218838;
        }
        .add-to-cart-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        @media (max-width: 992px) {
            .container {
                flex-direction: column;
                margin: 20px auto;
                gap: 20px;
            }
            .studio-details-section, .booking-sidebar {
                min-width: unset;
                width: 100%;
            }
            .booking-sidebar {
                position: static;
                top: unset;
                align-self: auto;
            }
            .studio-details-section .gallery {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
                max-height: unset;
            }
            .studio-details-section .gallery img:first-child {
                grid-column: auto;
                grid-row: auto;
            }
            .studio-details-section .location-price {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
            .studio-details-section h1 {
                font-size: 32px;
            }
            .studio-details-section .location-price .price-per-hour {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="Homepage1.php" class="logo">Xmee Studio</a>
        <nav class="nav-menu">
            <ul>
                <li><a href="Homepage1.php">หน้าหลัก</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">

        <section class="studio-details-section">
            <h1>สตูดิโอ A: Minimal Loft</h1>
            <div class="location-price">
                <span class="location"><i class="fas fa-map-marker-alt"></i>ซอยท่าตะโก หมู่บ้านเดอะเวิร์คสเปซ อิน-ทาวน์ Nakhon Ratchasima, Thailand, Nakhon Ratchasima 30000</span>
                <span class="price-per-hour">฿500 / ชั่วโมง</span>
            </div>

            <div class="gallery">
                <img src="Xmee6.jpg" alt="Studio A Corner">
                <img src="Xmee3.jpg" alt="Studio A Details">
                <img src="Xmee4.jpg" alt="Studio A Main View">
            </div>

            <div class="description">
                <h3>รายละเอียดสตูดิโอ</h3>
                <p>
                    สตูดิโอ Minimal Loft ของเราถูกออกแบบมาเพื่อการถ่ายภาพที่เน้นความเรียบง่ายแต่แฝงไปด้วยสไตล์ ด้วยพื้นที่โล่งกว้างขวาง แสงธรรมชาติที่สวยงามตลอดวัน และการตกแต่งแบบอินดัสเทรียลลอฟท์ที่เข้ากันได้อย่างลงตัว เหมาะอย่างยิ่งสำหรับการถ่ายภาพบุคคล, แฟชั่น, สินค้าขนาดเล็ก, หรือแม้แต่วิดีโอบล็อก. เรามีมุมหลากหลายให้คุณเลือกใช้ ไม่ว่าจะเป็นผนังปูนเปลือย, อิฐแดง, หรือมุมแสงหน้าต่างบานใหญ่
                </p>
                <p>
                    สตูดิโอของเราได้รับการทำความสะอาดและฆ่าเชื้อโรคอย่างสม่ำเสมอ เพื่อให้คุณมั่นใจในความสะอาดและความปลอดภัยในการใช้งาน เราใส่ใจในทุกรายละเอียดเพื่อให้คุณมีประสบการณ์การถ่ายภาพที่ดีที่สุด
                </p>
            </div>

            <div class="amenities">
                <h3>สิ่งอำนวยความสะดวก</h3>
                <ul>
                    <li><i class="fas fa-wifi"></i> Wi-Fi ความเร็วสูง</li>
                    <li><i class="fas fa-toilet"></i> ห้องน้ำสะอาด</li>
                    <li><i class="fas fa-snowflake"></i> เครื่องปรับอากาศ</li>
                    <li><i class="fas fa-lightbulb"></i> ชุดไฟสตูดิโอ (Softbox, Reflector)</li>
                    <li><i class="fas fa-palette"></i> ฉากสตูดิโอ (ขาว/ดำ)</li>
                    <li><i class="fas fa-couch"></i> เฟอร์นิเจอร์และพร็อพ</li>
                </ul>
            </div>
            
        </section>

        <form id="booking-form" action="dataBook.php" method="POST">
            <input type="hidden" name="Service_ID" value="1">
            <aside class="booking-sidebar">
                <h2>จองสตูดิโอ</h2>
                <div class="form-group">
                    <label for="booking-date">เลือกวันที่จอง</label>
                    <input type="date" id="booking-date" name="Date_Book" required>
                </div>

                <div class="form-group">
                    <label for="booking-service">เลือกบริการ</label>
                    <select class="form-select" id="booking-service" name="ServiceChoice" required>
                        <option value="studio_only">จองสตูดิโออย่างเดียว (฿500/ชั่วโมง)</option>
                        <option value="studio_with_photographer">จองสตูดิโอพร้อมช่างภาพ (฿2,000/ชั่วโมง)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="booking-duration">เลือกจำนวนชั่วโมง</label>
                    <select class="form-select" id="booking-duration" name="Hours" required>
                        <option value="1">1 ชั่วโมง</option>
                        <option value="2">2 ชั่วโมง</option>
                        <option value="3">3 ชั่วโมง</option>
                        <option value="4">4 ชั่วโมง</option>
                        <option value="8">จองทั้งวัน</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>เลือกเวลาที่ต้องการ (เลือกช่วงเวลาต่อเนื่อง)</label>
                    <div class="time-slots">
                        <div class="time-slot" data-time="09:00">09:00 - 10:00</div>
                        <div class="time-slot" data-time="10:00">10:00 - 11:00</div>
                        <div class="time-slot" data-time="11:00">11:00 - 12:00</div>
                        <div class="time-slot" data-time="12:00">12:00 - 13:00</div> 
                        <div class="time-slot" data-time="13:00">13:00 - 14:00</div>
                        <div class="time-slot" data-time="14:00">14:00 - 15:00</div>
                        <div class="time-slot" data-time="15:00">15:00 - 16:00</div>
                        <div class="time-slot" data-time="16:00">16:00 - 17:00</div>
                    </div>
                </div>

                <input type="hidden" name="Cus_id" value="<?php echo htmlspecialchars($Cus_id); ?>"> 
                <input type="hidden" name="Service_ID" value="1"> 
                <input type="hidden" name="ServiceType" value="สตูดิโอ">
                <input type="hidden" name="Location" value="ซอยท่าตะโก หมู่บ้านเดอะเวิร์คสเปซ อิน-ทาวน์ Nakhon Ratchasima, Thailand, Nakhon Ratchasima 30000">
                <input type="hidden" name="Booking_Status" value="รอการยืนยัน">
                <input type="hidden" name="Payment_Status" value="รอการชำระเงิน">
                <input type="hidden" name="StartTime" id="start-time-input" value="">
                <input type="hidden" name="Total_P" id="total-price-input" value="">
                <input type="hidden" id="Selected_P" name="Selected_P">
                
                <div class="booking-summary">
                    <p><span>วันที่จอง:</span> <span id="summary-date">ยังไม่ได้เลือก</span></p>
                    <p><span>เวลาที่เลือก:</span> <span id="summary-time">ยังไม่ได้เลือก</span></p>
                    <p><span>ระยะเวลา:</span> <span id="summary-duration">0 ชั่วโมง</span></p>
                    <p><span>ค่าสตูดิโอ:</span> <span id="summary-studio-price">฿0</span></p>
                    <p><span>ค่าบริการเสริม:</span> <span id="summary-addons-price">฿0</span></p>
                    <p class="total-price"><span>รวมเป็นเงิน:</span> <span id="summary-total-price">฿0</span></p>
                </div>

                <div class="form-group">
                    <label for="admin-notes">ข้อความถึงแอดมิน (ไม่บังคับ)</label>
                    <textarea id="admin-notes" name="Note" rows="4" placeholder="ตัวอย่าง: ต้องการใช้ไฟสตูดิโอ 2 ชุด, ขอบคุณค่ะ" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="add-to-cart-button" id="submit-button" disabled>ดำเนินการต่อเพื่อชำระเงิน</button>
            </aside>
        </form>
    </div>

    <footer class="footer">
        <div class="social-links">
            <a href="https://www.facebook.com/XMEEstudio/?locale=th_TH">Facebook</a>
            <a href="http://instagram.com/explore/locations/635243489902414/xmee-studio/">Instagram</a>
        </div>
        <p>&copy; 2025 Xmee Studio . All rights reserved.</p>
        <p>ที่อยู่: ซอยท่าตะโก หมู่บ้านเดอะเวิร์คสเปซ อิน-ทาวน์ Nakhon Ratchasima, Thailand, Nakhon Ratchasima 30000 | โทร: 093 961 4655 | อีเมล: xmeestudio@gmail.com</p>
    </footer>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookedSlots = <?php echo json_encode($booked_slots_php); ?>;
            const bookingDateInput = document.getElementById('booking-date');
            const bookingDurationSelect = document.getElementById('booking-duration');
            const bookingServiceSelect = document.getElementById('booking-service');
            const timeSlotsContainer = document.querySelector('.time-slots');
            const summaryDate = document.getElementById('summary-date');
            const summaryTime = document.getElementById('summary-time');
            const summaryDuration = document.getElementById('summary-duration');
            const summaryStudioPrice = document.getElementById('summary-studio-price');
            const summaryAddonsPrice = document.getElementById('summary-addons-price');
            const summaryTotalPrice = document.getElementById('summary-total-price');
            const addToCartButton = document.getElementById('submit-button');

            const startTimeInput = document.getElementById('start-time-input');
            const totalPriceInput = document.getElementById('total-price-input');
            const serviceId = document.querySelector('input[name="Service_ID"]').value;

            // NEW: Hourly rates for different services
            const studioHourlyRate = 500;
            const photographerHourlyRate = 2000;
            
            let selectedTimeSlots = [];

            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            bookingDateInput.value = `${year}-${month}-${day}`;
            bookingDateInput.min = `${year}-${month}-${day}`;

            function isSlotBooked(date, time) {
                const dateTimeString = `${date} ${time}`;
                return bookedSlots[serviceId] && bookedSlots[serviceId].includes(dateTimeString);
            }

            // ฟังก์ชันเพื่อรับจำนวนชั่วโมงที่ถูกต้อง
            function getDuration() {
                const durationValue = bookingDurationSelect.value;
                if (durationValue === 'full_day') {
                    return 8; // จองเต็มวันคือ 8 ชั่วโมง (09:00-17:00)
                }
                return parseInt(durationValue);
            }

            function updateBookingSummary() {
                const selectedDate = bookingDateInput.value;
                const duration = getDuration();
                const selectedService = bookingServiceSelect.value;
                
                // กำหนดเรทราคาต่อชั่วโมงตามประเภทบริการ
                let hourlyRate = studioHourlyRate;
                if (selectedService === 'studio_with_photographer') {
                    hourlyRate = photographerHourlyRate;
                }

                // อัปเดตข้อมูลสรุปการจอง
                summaryDate.textContent = selectedDate || 'ยังไม่ได้เลือก';

                if (selectedTimeSlots.length > 0) {
                    const sortedSlots = [...selectedTimeSlots].sort();
                    const startTime = sortedSlots[0];
                    const endTimeHour = parseInt(sortedSlots[sortedSlots.length - 1].split(':')[0]) + 1;
                    const endTime = `${String(endTimeHour).padStart(2, '0')}:00`;
                    summaryTime.textContent = `${startTime} - ${endTime}`;
                    startTimeInput.value = startTime;
                } else {
                    summaryTime.textContent = 'ยังไม่ได้เลือก';
                    startTimeInput.value = '';
                }

                // คำนวณราคารวม
                let totalHoursToCharge = selectedTimeSlots.length;
                
                // **แก้ไขการคำนวณราคาสำหรับจองเต็มวัน (8 ชั่วโมง คิดราคา 7 ชั่วโมง)**
                if (totalHoursToCharge === 8) {
                    totalHoursToCharge = 7;
                }
                
                let totalStudioPrice = totalHoursToCharge * hourlyRate;
                let totalAddonsPrice = 0; // ในโค้ดนี้ไม่มี Add-ons อื่นๆ
                let totalToPay = totalStudioPrice + totalAddonsPrice;

                // อัปเดตค่าในช่อง summary และ hidden input
                summaryDuration.textContent = `${selectedTimeSlots.length} ชั่วโมง`;
                summaryStudioPrice.textContent = `฿${totalStudioPrice.toLocaleString()}`;
                summaryAddonsPrice.textContent = `฿${totalAddonsPrice.toLocaleString()}`;
                summaryTotalPrice.textContent = `฿${totalToPay.toLocaleString()}`;
                
                totalPriceInput.value = totalToPay;
                document.getElementById('Selected_P').value = totalToPay;
                
                // เปิดใช้งานปุ่มเมื่อเลือกชั่วโมงครบตามที่กำหนด
                if (selectedTimeSlots.length > 0 && selectedTimeSlots.length === duration) {
                    addToCartButton.disabled = false;
                } else {
                    addToCartButton.disabled = true;
                }
            }

            function resetTimeSlots() {
                const selectedDate = bookingDateInput.value;
                const allSlots = Array.from(timeSlotsContainer.querySelectorAll('.time-slot'));
                const duration = getDuration();

                selectedTimeSlots = [];
                const photographerOption = document.querySelector('option[value="studio_with_photographer"]');

                // ซ่อน/แสดงตัวเลือกพร้อมช่างภาพ
                if (bookingDurationSelect.value === 'full_day') {
                    photographerOption.disabled = true;
                    bookingServiceSelect.value = 'studio_only';
                } else {
                    photographerOption.disabled = false;
                }

                allSlots.forEach(slot => {
                    slot.classList.remove('selected', 'booked');
                    const slotTime = slot.dataset.time;

                    if (isSlotBooked(selectedDate, slotTime)) {
                        slot.classList.add('booked');
                    }
                });

                if (bookingDurationSelect.value === 'full_day') {
                    allSlots.forEach(slot => {
                        if (!slot.classList.contains('booked')) {
                            slot.classList.add('selected');
                            selectedTimeSlots.push(slot.dataset.time);
                        }
                    });
                }
            }

            bookingDateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const dayOfWeek = selectedDate.getDay(); 
                const optionOneHour = bookingDurationSelect.querySelector('option[value="1"]');

                if (dayOfWeek === 0) {
                    optionOneHour.disabled = true;
                    if (bookingDurationSelect.value === '1') {
                        bookingDurationSelect.value = '2';
                    }
                    alert('สำหรับวันอาทิตย์ กรุณาจองขั้นต่ำ 2 ชั่วโมง');
                } else {
                    optionOneHour.disabled = false;
                }
                
                resetTimeSlots();
                updateBookingSummary();
            });

            bookingDurationSelect.addEventListener('change', function() {
                resetTimeSlots();
                updateBookingSummary();
            });
            
            bookingServiceSelect.addEventListener('change', function() {
                updateBookingSummary();
            });

            timeSlotsContainer.addEventListener('click', function(event) {
                const clickedSlot = event.target.closest('.time-slot');
                if (!clickedSlot || clickedSlot.classList.contains('booked')) {
                    return;
                }

                const duration = getDuration();
                const allSlots = Array.from(timeSlotsContainer.querySelectorAll('.time-slot'));
                const startIndex = allSlots.indexOf(clickedSlot);
                
                // Clear previous selection
                allSlots.forEach(slot => slot.classList.remove('selected'));
                selectedTimeSlots = [];

                if (duration === 8) { // Logic for 'full_day'
                    const allAvailableSlots = allSlots.filter(slot => !slot.classList.contains('booked'));
                    if (allAvailableSlots.length >= 8) {
                        allAvailableSlots.slice(0, 8).forEach(slot => {
                            slot.classList.add('selected');
                            selectedTimeSlots.push(slot.dataset.time);
                        });
                    } else {
                        alert('ไม่สามารถจองเต็มวันได้ เนื่องจากมีช่วงเวลาที่ไม่ว่าง');
                    }
                } else { // Logic for specific hours
                    let isSelectionValid = true;
                    for (let i = 0; i < duration; i++) {
                        const currentSlot = allSlots[startIndex + i];
                        if (!currentSlot || currentSlot.classList.contains('booked')) {
                            isSelectionValid = false;
                            break;
                        }
                    }

                    if (isSelectionValid) {
                        for (let i = 0; i < duration; i++) {
                            const currentSlot = allSlots[startIndex + i];
                            currentSlot.classList.add('selected');
                            selectedTimeSlots.push(currentSlot.dataset.time);
                        }
                    } else {
                        alert('ไม่สามารถจองช่วงเวลาดังกล่าวได้ กรุณาเลือกช่วงเวลาที่ต่อเนื่องและว่าง');
                    }
                }

                updateBookingSummary();
            });

            resetTimeSlots();
            updateBookingSummary();
        });
    </script>
</body>
</html>