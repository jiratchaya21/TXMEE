<?php
    // Include database connection file
    include "Datasql.php";

    // Start the session to check for logged-in user
    session_start();

    // Placeholder for user data
    $customer_data = null;
    $is_logged_in = false;
    $Cus_id = null;

    // Check if a user is logged in (e.g., from a session)
    if (isset($_SESSION['Cus_id'])) {
        $is_logged_in = true;
        $Cus_id = $_SESSION['Cus_id'];
        
        $sql_customer = "SELECT F_name, L_name, Email_C, phone FROM Customer WHERE Cus_id = ?";
        
        // Check if the database connection object ($conn) exists
        if (isset($conn) && $stmt_customer = $conn->prepare($sql_customer)) {
            $stmt_customer->bind_param("i", $Cus_id);
            $stmt_customer->execute();
            $result_customer = $stmt_customer->get_result();

            if ($result_customer->num_rows > 0) {
                $customer_data = $result_customer->fetch_assoc();
                $_SESSION['F_name'] = $customer_data['F_name'];
                $_SESSION['L_name'] = $customer_data['L_name'];
                $_SESSION['Email_C'] = $customer_data['Email_C'];
                $_SESSION['phone'] = $customer_data['phone'];
            }
            $stmt_customer->close();
        }
    }
    
    // SQL query to get booked slots for all services
    $sql = "SELECT Service_ID, Date_Book, StartTime, Hours FROM Booking WHERE Booking_Status = 'ยืนยันแล้ว'";
    
    $booked_slots_php = [];

    // Check if the database connection object ($conn) exists
    if (isset($conn)) {
        if ($stmt = $conn->prepare($sql)) {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $service_id = $row['Service_ID'];
                    $date = $row['Date_Book'];
                    $start_time = $row['StartTime'];
                    $hours = $row['Hours'];

                    $current_timestamp = strtotime("$date $start_time");

                    // Add each 30-minute slot of the booked block to the array
                    for ($i = 0; $i < ($hours * 2); $i++) {
                        $booked_time_str = date('Y-m-d H:i', $current_timestamp + ($i * 1800)); // 1800 seconds = 30 minutes
                        
                        // Group by service ID
                        if (!isset($booked_slots_php[$service_id])) {
                            $booked_slots_php[$service_id] = [];
                        }
                        $booked_slots_php[$service_id][] = $booked_time_str;
                    }
                }
            }
            $stmt->close();
        }
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จอง SayCheeze | Xmee Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
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
        }
        .header .logo {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            text-decoration: none;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 36px;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
        }
        p.subtitle {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
        }
        .booking-form .form-group {
            margin-bottom: 25px;
        }
        .booking-form label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        .booking-form input[type="text"],
        .booking-form input[type="email"],
        .booking-form input[type="tel"],
        .booking-form input[type="date"],
        .booking-form input[type="time"],
        .booking-form input[type="number"],
        .booking-form select,
        .booking-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            font-family: 'Kanit', sans-serif;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .booking-form input:focus,
        .booking-form select:focus,
        .booking-form textarea:focus {
            border-color: #007bff;
            outline: none;
        }
        .booking-form textarea {
            resize: vertical;
            min-height: 100px;
        }
        .booking-form button, .booking-form .quotation-button {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
            text-decoration: none;
        }
        .booking-form button[type="submit"]:hover {
            background-color: #0056b3;
        }
        .booking-form button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .quotation-button {
            margin-top: 15px;
            background-color: #2c3e50 !important;
        }
        .quotation-button:hover {
            background-color: #34495e !important;
        }
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .time-slot-button {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .time-slot-button:hover:not(.disabled) {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }
        .time-slot-button.selected {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .time-slot-button.disabled {
            background-color: #f8f9fa;
            color: #ced4da;
            border-color: #e9ecef;
            cursor: not-allowed;
            pointer-events: none;
        }
        .pricing-summary {
            margin-top: 30px;
            padding: 20px;
            background-color: #e9ecef;
            border-radius: 8px;
            border-left: 5px solid #007bff;
        }
        .pricing-summary h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .pricing-summary .price-item {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            margin-bottom: 8px;
            border-bottom: 1px dashed #ced4da;
            padding-bottom: 5px;
        }
        .pricing-summary .price-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .pricing-summary .total-price {
            font-size: 24px;
            font-weight: 700;
            color: #007bff;
            margin-top: 15px;
            text-align: right;
        }
        .service-details {
            margin-top: 30px;
            display: none;
        }
        .service-details img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .service-details h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .service-details p {
            font-size: 16px;
            line-height: 1.7;
            color: #555;
        }
        .note-text {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
        }
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            h1 {
                font-size: 28px;
            }
            .time-slots {
                grid-template-columns: 1fr;
            }
        }
        /* Swiper CSS for vertical images */
        .swiper {
            width: 100%;
            height: 450px; /* Adjust height as needed for vertical images */
            border-radius: 8px;
        }
        .swiper-slide {
            text-align: center;
            font-size: 18px;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .swiper-slide img {
            display: block;
            width: auto; /* Allow width to adjust */
            height: 100%; /* Set height to 100% of container */
            object-fit: contain; /* Ensure the image fits within the container without cropping */
        }
        /* Style for Swiper navigation and pagination */
        .swiper-button-next, .swiper-button-prev {
            color: #007bff;
        }
        .swiper-pagination-bullet-active {
            background-color: #007bff;
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="Homepage1.php" class="logo">Xmee Studio</a>
    </header>

    <div class="container" >
        <h1>จองบริการ SayCheeze</h1>
        <p class="subtitle">เลือกประเภทบริการและกรอกรายละเอียดสำหรับงานของคุณ</p>
        <form action="dataBook.php" method="POST" class="booking-form">
            <div class="form-group">
                <label for="service_type">เลือกประเภทบริการ</label>
                <select id="service_type" name="Service_ID" required>
                    <option value="">-- กรุณาเลือกบริการ --</option>
                    <option value="2" data-baseprice="12000" data-servicetype="SayCheeze Photobooth">SayCheeze Photobooth</option>
                    <option value="3" data-baseprice="15900" data-servicetype="SayCheeze 360 Video Booth">SayCheeze 360 Video Booth</option>
                    <option value="4" data-baseprice="18900" data-servicetype="แพ็คคู่สุดคุ้ม Photobooth & 360 Video Booth">แพ็คคู่สุดคุ้ม Photobooth & 360 Video Booth</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="location_type">สถานที่จัดงาน</label>
                <select id="location_type" name="LocationType" required>
                    <option value="">-- เลือกสถานที่ --</option>
                    <option value="in_city">ในเขตอำเภอเมือง</option>
                    <option value="out_of_city">นอกเขตอำเภอเมือง</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="duration">ระยะเวลาการใช้บริการ</label>
                <select id="duration" name="Hours" required>
                    <option value="">-- กรุณาเลือกสถานที่ก่อน --</option>
                </select>
                <div class="note-text">
                    <p>สำหรับแพ็กเกจนี้ ระยะเวลาการใช้บริการครอบคลุม 4 ชั่วโมงแรก หากต้องการเกิน 4 ชั่วโมง ชั่วโมงถัดไปจะคิดเพิ่ม 3,000 บาท</p>
                </div>
            </div>

            <div class="form-group">
                <label for="address">ที่อยู่สถานที่จัดงาน</label>
                <input type="text" id="address-input" name="Location" required>
                <div class="note-text">
                    <p>ค่าเดินทางจะถูกคำนวณและแจ้งให้ทราบภายหลัง</p>
                </div>
            </div>

            <div class="form-group">
                <label for="event_date">วันที่จัดงาน</label>
                <input type="date" id="event_date" name="Date_Book" required>
            </div>
            
            <div class="form-group">
                <label for="event_time">เวลาที่ต้องการเริ่มต้น (เช่น 14:30)</label>
                <input type="time" id="event_time" name="StartTime" step="1800" required>
            </div>
            
            <div class="form-group">
                <label>รูปแบบการชำระเงิน</label>
                <div style="display: flex; gap: 20px;">
                    <label>
                        <input type="radio" name="payment_option" value="deposit" checked>
                        มัดจำ 5,000 บาท
                    </label>
                    <label>
                        <input type="radio" name="payment_option" value="full">
                        จ่ายเต็มจำนวน
                    </label>
                </div>
            </div>

            <input type="hidden" name="Cus_id" value="<?php echo htmlspecialchars($Cus_id); ?>">
            <input type="hidden" id="service-type-input" name="ServiceType">
            <input type="hidden" id="total-price-input" name="Total_P">
            <input type="hidden" id="selected-price-input" name="Selected_P">
            <input type="hidden" id="payment-status-input" name="Payment_Status">
            <input type="hidden" name="Booking_Status" value="รอการยืนยัน">
            
            <div id="photobooth-details" class="service-details">
                <h2>SayCheeze Photobooth 2x6</h2>
                <div class="swiper mySwiper-Photobooth">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide"><img src="S1.jpg" alt="SayCheeze Photobooth 1"></div>
                        <div class="swiper-slide"><img src="SA5.jpg" alt="SayCheeze Photobooth 2"></div>
                        <div class="swiper-slide"><img src="S3.jpg" alt="SayCheeze Photobooth 3"></div>
                        <div class="swiper-slide"><img src="S4.jpg" alt="SayCheeze Photobooth 4"></div>
                        <div class="swiper-slide"><img src="S8.jpg" alt="SayCheeze Photobooth 5"></div>
                        <div class="swiper-slide"><img src="S6.jpg" alt="SayCheeze Photobooth 6"></div>
                        <div class="swiper-slide"><img src="S7.jpg" alt="SayCheeze Photobooth 7"></div>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
                <p>
                    รายละเอียด SayCheeze
                    ราคาปกติ 12,000 บาท
                    - ถ่ายภาพ 4 ชั่วโมง สำหรับบูธถ่ายภาพ
                    - ดูตัวอย่างภาพแบบสัมผัส
                    - ถ่ายภาพไม่จำกัดจำนวน พร้อมพิมพ์
                    - ภาพคุณภาพสูงบนกระดาษ 4R (ภาพ 2 แผ่น)
                    - พิมพ์ภาพความเร็วสูง (12 วินาที)
                    - แกลเลอรีออนไลน์สำหรับภาพถ่ายทั้งหมดบนเว็บไซต์และโซเชียลมีเดียของเรา
                    - ฆ่าเชื้ออุปกรณ์ประกอบฉากสนุก ๆ
                    - ดาวน์โหลดคิวอาร์โค้ด
                    - ออกแบบเลย์เอาต์ฟรีตามทีมงาน
                    - พนักงาน 2 คน
                    - ฉากหลังเลื่อมสวยงาม
                    - มีค่าบริการขนส่งตามสถานที่
                    *** อำเภอเมืองนครราชสีมา ไม่มีค่าบริการขนส่ง
                </p>
            </div>

            <div id="360-details" class="service-details">
                <h2>SayCheeze 360 Video Booth</h2>
                <div class="swiper mySwiper-360">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide"><img src="Saycheeze.jpg" alt="SayCheeze 360 Video Booth 1"></div>
                        <div class="swiper-slide"><img src="Saycheeze1.jpg" alt="SayCheeze 360 Video Booth 2"></div>
                        <div class="swiper-slide"><img src="Saycheeze2.jpg" alt="SayCheeze 360 Video Booth 3"></div>
                        <div class="swiper-slide"><img src="Saycheeze3.jpg" alt="SayCheeze 360 Video Booth 4"></div>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
                <p>
                    สร้างวิดีโอ 360 องศาที่น่าทึ่งและไม่เหมือนใครสำหรับงานของคุณ แขกของคุณจะได้ขึ้นไปยืนบนแพลตฟอร์มและสนุกไปกับการบันทึกวิดีโอสโลว์โมชั่นและเอฟเฟกต์พิเศษ พร้อมแชร์ลงโซเชียลมีเดียได้ทันที
                </p>
            </div>

            <div id="combined-details" class="service-details">
                <h2>แพ็คคู่สุดคุ้ม Photobooth & 360 Video Booth</h2>
                <div class="swiper mySwiper-Combined">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide"><img src="S8.jpg" alt="Combined Package 1"></div>
                        <div class="swiper-slide"><img src="S6.jpg" alt="Combined Package 2"></div>
                        <div class="swiper-slide"><img src="S7.jpg" alt="Combined Package 3"></div>
                        <div class="swiper-slide"><img src="Saycheeze1.jpg" alt="SayCheeze 360 Video Booth 2"></div>
                        <div class="swiper-slide"><img src="Saycheeze2.jpg" alt="SayCheeze 360 Video Booth 3"></div>
                        <div class="swiper-slide"><img src="Saycheeze3.jpg" alt="SayCheeze 360 Video Booth 4"></div>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
                <p>
                    ยกระดับงานของคุณให้พิเศษยิ่งขึ้นด้วยแพ็คคู่ที่รวมทั้งบริการ SayCheeze Photobooth และ 360 Video Booth เข้าด้วยกัน มอบประสบการณ์ที่หลากหลายและความสนุกแบบไม่รู้จบให้กับแขกของคุณในราคาที่คุ้มค่ายิ่งกว่าเดิม
                </p>
            </div>

            <div id="pricing-summary" class="pricing-summary" style="display: none;">
                <h3>สรุปค่าใช้จ่ายโดยประมาณ</h3>
                <div class="price-item">
                    <span>ค่าบริการแพ็กเกจ</span>
                    <span id="price-per-hour">0 บาท</span>
                </div>
                <div class="price-item" id="duration-surcharge-item" style="display: none;">
                    <span>ค่าบริการชั่วโมงเสริม</span>
                    <span id="duration-surcharge-price">0 บาท</span>
                </div>
                <div class="price-item">
                    <span>ระยะเวลา</span>
                    <span id="duration-summary">4 ชั่วโมง</span>
                </div>
                <div class="price-item">
                    <span>ค่าเดินทาง</span>
                    <span id="travel-fee">จะแจ้งให้ทราบภายหลัง</span>
                </div>
                <div class="price-item" id="deposit-summary-item" style="display: block;">
                    <span>ยอดมัดจำ</span>
                    <span id="deposit-price">5,000 บาท</span>
                </div>
                <div class="total-price">
                    <span id="total-price-label">ยอดที่ต้องชำระ (มัดจำ):</span>
                    <span id="total-price-summary">0 บาท</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="message">รายละเอียดเพิ่มเติม (เช่น ธีมงาน)</label>
                <textarea id="message" name="Note"></textarea>
            </div>
                <button type="submit" id="submit-button" disabled>ส่งคำจอง</button>
                <button type="button" class="quotation-button" id="quotation-button" disabled>สร้างใบเสนอราคา</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const bookedSlots = <?php echo json_encode($booked_slots_php); ?>;

        const serviceTypeSelect = document.getElementById('service_type');
        const locationTypeSelect = document.getElementById('location_type');
        const addressInput = document.getElementById('address-input');
        const durationSelect = document.getElementById('duration');
        const eventDateInput = document.getElementById('event_date');
        const eventTimeInput = document.getElementById('event_time');
        const pricingSummary = document.getElementById('pricing-summary');
        const serviceTypeInput = document.getElementById('service-type-input');
        const totalPriceInput = document.getElementById('total-price-input');
        const selectedPriceInput = document.getElementById('selected-price-input');
        const paymentStatusInput = document.getElementById('payment-status-input');
        const paymentOptionRadios = document.querySelectorAll('input[name="payment_option"]');
        const depositSummaryItem = document.getElementById('deposit-summary-item');
        const depositPriceSpan = document.getElementById('deposit-price');
        const totalPriceLabel = document.getElementById('total-price-label');
        const totalPriceSummarySpan = document.getElementById('total-price-summary');
        const submitButton = document.getElementById('submit-button');
        const quotationButton = document.getElementById('quotation-button');
        const pricePerHourSpan = document.getElementById('price-per-hour');
        const durationSummarySpan = document.getElementById('duration-summary');
        const travelFeeSpan = document.getElementById('travel-fee');
        const durationSurchargeItem = document.getElementById('duration-surcharge-item');
        const durationSurchargePriceSpan = document.getElementById('duration-surcharge-price');
        
        // Service details sections
        const photoboothDetails = document.getElementById('photobooth-details');
        const threeSixtyDetails = document.getElementById('360-details');
        const combinedDetails = document.getElementById('combined-details');
        
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        eventDateInput.value = `${year}-${month}-${day}`;
        eventDateInput.min = `${year}-${month}-${day}`;
        
        // Initialize Swipers
        new Swiper('.mySwiper-Photobooth', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });

        new Swiper('.mySwiper-360', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
        
        new Swiper('.mySwiper-Combined', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
        
        function updateDurationOptions() {
            const locationType = locationTypeSelect.value;
            durationSelect.innerHTML = '';
            let options = '';

            if (locationType === 'in_city') {
                options += `<option value="3">3 ชั่วโมง</option>`;
                options += `<option value="4">4 ชั่วโมง</option>`;
            } else if (locationType === 'out_of_city') {
                options += `<option value="4">4 ชั่วโมง</option>`;
            } else {
                 options += `<option value="">-- กรุณาเลือกสถานที่ก่อน --</option>`;
            }
            durationSelect.innerHTML = options;
            updatePrice();
            updateSummary();
        }

        function updateVisibleDetails() {
            const selectedService = serviceTypeSelect.value;
            
            photoboothDetails.style.display = 'none';
            threeSixtyDetails.style.display = 'none';
            combinedDetails.style.display = 'none';
            
            if (selectedService === '2') {
                photoboothDetails.style.display = 'block';
            } else if (selectedService === '3') {
                threeSixtyDetails.style.display = 'block';
            } else if (selectedService === '4') {
                combinedDetails.style.display = 'block';
            }
        }

        function updatePrice() {
            const serviceSelect = serviceTypeSelect.options[serviceTypeSelect.selectedIndex];
            const basePrice = parseFloat(serviceSelect.dataset.baseprice);
            const serviceType = serviceSelect.dataset.servicetype;
            const duration = parseInt(durationSelect.value) || 0;
            const paymentOption = document.querySelector('input[name="payment_option"]:checked').value;

            if (isNaN(basePrice) || duration === 0) {
                pricingSummary.style.display = 'none';
                return;
            }

            const durationSurcharge = Math.max(0, duration - 4) * 3000;
            const totalPackagePrice = basePrice + durationSurcharge;
            
            let selectedPrice = totalPackagePrice;
            let paymentStatus = 'รอตรวจสอบ (เต็มจำนวน)';
            let deposit = 5000;

            if (paymentOption === 'deposit') {
                selectedPrice = deposit;
                paymentStatus = 'รอตรวจสอบ (มัดจำ)';
            }
            
            serviceTypeInput.value = serviceType;
            totalPriceInput.value = totalPackagePrice;
            selectedPriceInput.value = selectedPrice;
            paymentStatusInput.value = paymentStatus;

            pricePerHourSpan.textContent = basePrice.toLocaleString() + ' บาท';
            durationSummarySpan.textContent = duration + ' ชั่วโมง';
            travelFeeSpan.textContent = 'จะแจ้งให้ทราบภายหลัง';
            durationSurchargePriceSpan.textContent = durationSurcharge.toLocaleString() + ' บาท';
            
            if (durationSurcharge > 0) {
                durationSurchargeItem.style.display = 'flex';
            } else {
                durationSurchargeItem.style.display = 'none';
            }

            if (paymentOption === 'deposit') {
                depositSummaryItem.style.display = 'flex';
                depositPriceSpan.textContent = deposit.toLocaleString() + ' บาท';
                totalPriceLabel.textContent = 'ยอดที่ต้องชำระ (มัดจำ):';
                totalPriceSummarySpan.textContent = selectedPrice.toLocaleString() + ' บาท';
            } else {
                depositSummaryItem.style.display = 'none';
                totalPriceLabel.textContent = 'ยอดที่ต้องชำระ (เต็มจำนวน):';
                totalPriceSummarySpan.textContent = selectedPrice.toLocaleString() + ' บาท';
            }

            pricingSummary.style.display = 'block';
        }
        
        function checkTimeAvailability() {
            const selectedDate = eventDateInput.value;
            const startTime = eventTimeInput.value;
            const durationValue = parseInt(durationSelect.value);
            const serviceId = serviceTypeSelect.value;
            
            if (!selectedDate || !startTime || !durationValue || !serviceId) {
                return false;
            }

            const startDateTime = new Date(`${selectedDate}T${startTime}:00`);
            const durationInMs = durationValue * 60 * 60 * 1000;
            const endDateTime = new Date(startDateTime.getTime() + durationInMs);
            const minTime = new Date(`${selectedDate}T09:00:00`);
            const maxTime = new Date(`${selectedDate}T22:00:00`);

            if (startDateTime < minTime || endDateTime > maxTime) {
                alert('เวลาที่เลือกอยู่นอกช่วงที่อนุญาต (09:00 - 22:00)');
                return false;
            }

            const bookedSlotsForService = bookedSlots[serviceId] || [];
            const bookedSlotsPhotobooth = bookedSlots['2'] || [];
            const bookedSlots360 = bookedSlots['3'] || [];

            for (const bookedTime of bookedSlotsForService) {
                const bookedDateTime = new Date(bookedTime);
                const bookedEndDateTime = new Date(bookedDateTime.getTime() + 30 * 60 * 1000);
                
                if ((startDateTime >= bookedDateTime && startDateTime < bookedEndDateTime) ||
                    (endDateTime > bookedDateTime && endDateTime <= bookedEndDateTime) ||
                    (bookedDateTime >= startDateTime && bookedDateTime < endDateTime)) {
                    alert('ช่วงเวลาที่เลือกถูกจองแล้ว กรุณาเลือกเวลาอื่น');
                    return false;
                }
            }

            if (serviceId === '4') {
                for (const bookedTime of bookedSlotsPhotobooth.concat(bookedSlots360)) {
                    const bookedDateTime = new Date(bookedTime);
                    const bookedEndDateTime = new Date(bookedDateTime.getTime() + 30 * 60 * 1000);

                    if ((startDateTime >= bookedDateTime && startDateTime < bookedEndDateTime) ||
                        (endDateTime > bookedDateTime && endDateTime <= bookedEndDateTime) ||
                        (bookedDateTime >= startDateTime && bookedDateTime < endDateTime)) {
                        alert('ช่วงเวลาที่เลือกถูกจองแล้ว กรุณาเลือกเวลาอื่น');
                        return false;
                    }
                }
            }
            
            return true;
        }
        
        function updateSummary() {
            const isTimeAvailable = checkTimeAvailability();
    
            const durationValue = parseInt(durationSelect.value);
            const serviceId = serviceTypeSelect.value;
            const locationType = locationTypeSelect.value;
            const address = addressInput.value.trim();
            const totalPrice = totalPriceInput.value;

            // ตรวจสอบว่าข้อมูลทั้งหมดที่จำเป็นครบถ้วนแล้ว
            const isFormValid = isTimeAvailable && durationValue > 0 && serviceId && locationType && address !== '' && totalPrice !== '';

            // เปิด/ปิดการใช้งานปุ่มทั้งสอง
            submitButton.disabled = !isFormValid;
            quotationButton.disabled = !isFormValid;
        }

        function generateQuotation() {
            const serviceSelect = document.getElementById('service_type');
            const serviceType = serviceSelect.options[serviceSelect.selectedIndex].dataset.servicetype;
            const duration = document.getElementById('duration').value;
            const eventDate = document.getElementById('event_date').value;
            const eventTime = document.getElementById('event_time').value;
            const address = document.getElementById('address-input').value;
            const totalPrice = document.getElementById('total-price-input').value;
            const notes = document.getElementById('message').value;
            const paymentType = document.querySelector('input[name="payment_option"]:checked').value;
            
            if (!serviceType || !duration || !eventDate || !eventTime || !address || !totalPrice) {
                alert('กรุณากรอกข้อมูลการจองให้ครบถ้วนก่อนสร้างใบเสนอราคา');
                return;
            }

            const url = `generate_quotation.php?service_type=${encodeURIComponent(serviceType)}&duration=${encodeURIComponent(duration)}&event_date=${encodeURIComponent(eventDate)}&event_time=${encodeURIComponent(eventTime)}&address=${encodeURIComponent(address)}&total_price=${encodeURIComponent(totalPrice)}&notes=${encodeURIComponent(notes)}&payment_type=${encodeURIComponent(paymentType)}`;
            
            window.open(url, '_blank');
        }

        // Event listeners
        serviceTypeSelect.addEventListener('change', () => {
            updateVisibleDetails();
            updatePrice();
            updateSummary();
        });

        locationTypeSelect.addEventListener('change', updateDurationOptions);
        
        durationSelect.addEventListener('change', () => {
            updatePrice();
            updateSummary();
        });

        addressInput.addEventListener('input', () => {
            updatePrice();
            updateSummary();
        });
        
        eventDateInput.addEventListener('change', updateSummary);
        eventTimeInput.addEventListener('change', updateSummary);
        paymentOptionRadios.forEach(radio => {
            radio.addEventListener('change', updatePrice);
        });
        
        // เพิ่ม event listener ให้กับปุ่ม "สร้างใบเสนอราคา"
        quotationButton.addEventListener('click', generateQuotation);
        
        // Initial setup on page load
        updateDurationOptions();
        updateVisibleDetails();
        updatePrice();
        updateSummary();
    });
    </script>
</body>
</html>