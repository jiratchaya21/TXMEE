<?php
    // Include database connection file
    include "Datasql.php";

    session_start();
    $staff_id = isset($_SESSION['Staff_id']) ? $_SESSION['Staff_id'] : NULL;

    // Fetch all bookings with customer details from the database
    $sql = "SELECT B.*, C.F_name, C.L_name, C.Email_C, C.phone, B.Location
        FROM Booking B
        INNER JOIN Customer C ON B.Cus_id = C.Cus_id
        ORDER BY B.Booking_ID DESC";
    $result = $conn->query($sql);
    $bookings = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }

    // Fetch bookings with deposit paid for the new table
    $sql_deposit_bookings = "SELECT B.*, C.F_name, C.L_name, C.Email_C, C.phone
        FROM Booking B
        INNER JOIN Customer C ON B.Cus_id = C.Cus_id
        WHERE B.Payment_Status = 'ชำระเงินมัดจำแล้ว'
        ORDER BY B.Booking_ID DESC";
    $result_deposit = $conn->query($sql_deposit_bookings);
    $deposit_bookings = [];
    if ($result_deposit->num_rows > 0) {
        while($row = $result_deposit->fetch_assoc()) {
            $deposit_bookings[] = $row;
        }
    }

    // Fetch all services for the Add Booking section
    $sql_services = "SELECT Service_ID, ServiceType FROM service ORDER BY Service_ID ASC"; 
    $result_services = $conn->query($sql_services);
    $services = [];
    $service_data_js = []; // ตัวแปรสำหรับเก็บข้อมูล Service ID/Location สำหรับ JS

    // [เพิ่ม]: กำหนด Location/Service ID แบบ Hardcoded โดยผูกกับ ServiceType
    // นี่คือค่าที่ถูกล็อคสำหรับบริการที่ทำในสถานที่เท่านั้น
    $location_mapping = [
    // 1. บริการสตูดิโอ: ล็อค Location และ ID
    'Studio' => [
        'id' => 1, // สมมติว่ารหัสบริการสตูดิโอคือ 1
        'location' => 'ซอยท่าตะโก หมู่บ้านเดอะเวิร์คสเปซ อิน-ทาวน์ Nakhon Ratchasima, Thailand, Nakhon Ratchasima 30000',
        'is_locked' => true // ล็อคฟิลด์ Location
    ],
    // 2. บริการนอกสถานที่ (ที่ต้องกรอก Location เอง)
    'photobooth' => [ // ตัวอย่างบริการ Photobooth
        'id' => 2, // สมมติรหัสบริการคือ 2
        'location' => 'กรุณาระบุสถานที่ติดตั้ง Photobooth',
        'is_locked' => false // ไม่ล็อคฟิลด์ Location (ให้กรอกเอง)
    ],
    '360Photo' => [ // ตัวอย่างบริการ 360 Photo
        'id' => 3, // สมมติรหัสบริการคือ 3
        'location' => 'กรุณาระบุสถานที่ติดตั้ง 360 Photo Booth',
        'is_locked' => false // ไม่ล็อคฟิลด์ Location (ให้กรอกเอง)
    ],
    'special package' => [ // ตัวอย่างบริการ 360 Photo
        'id' => 4, // สมมติรหัสบริการคือ 3
        'location' => 'กรุณาระบุสถานที่ติดตั้ง special package',
        'is_locked' => false // ไม่ล็อคฟิลด์ Location (ให้กรอกเอง)
    ]
];


if ($result_services->num_rows > 0) {
    while($row = $result_services->fetch_assoc()) {
        $service_type_key = $row['ServiceType'];
        $services[] = $row;
        
        // ค้นหา Location และ ID จาก Hardcoded Mapping หรือใช้ค่าเริ่มต้น
        $data = isset($location_mapping[$service_type_key]) 
                ? $location_mapping[$service_type_key] 
                : [
                    'id' => (int)$row['Service_ID'],
                    'location' => 'กรุณาระบุสถานที่',
                    'is_locked' => false
                  ];
        
        // เตรียมข้อมูลในรูปแบบ Key-Value สำหรับ JavaScript Lookup
        $service_data_js[$service_type_key] = $data;
    }
}
// แปลงข้อมูลเป็น JSON string เพื่อนำไปใช้ใน <script>
$service_data_json = json_encode($service_data_js);

        // ส่วนที่เพิ่มใหม่: อัปเดตสถานะในตาราง Payment
    $sql_update_payment = "UPDATE Payment SET P_Status = ? WHERE Booking_ID = ?";
    $stmt_update_payment = $conn->prepare($sql_update_payment);
    $stmt_update_payment->bind_param("si", $payment_status_confirmed, $booking_id);
    if (!$stmt_update_payment->execute()) {
        echo "Error updating payment record: " . $conn->error;
    }
    $stmt_update_payment->close();


    // Logic for the monthly calendar view
    $monthly_bookings = [];
    $month = isset($_GET['month']) ? $_GET['month'] : date('n');
    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');

    $sql_monthly = "SELECT Date_Book, StartTime, Hours FROM Booking WHERE YEAR(Date_Book) = ? AND MONTH(Date_Book) = ? AND Booking_Status != 'ยกเลิกแล้ว'";
    $stmt_monthly = $conn->prepare($sql_monthly);
    if ($stmt_monthly) {
        $stmt_monthly->bind_param("ii", $year, $month);
        $stmt_monthly->execute();
        $result_monthly = $stmt_monthly->get_result();
         while($row = $result_monthly->fetch_assoc()) {
            
            // [แก้ไข/เพิ่ม] คำนวณเวลาสิ้นสุดใน PHP เพื่อความแม่นยำ
            try {
                $start_time_dt = new DateTime($row['Date_Book'] . ' ' . $row['StartTime']);
                $end_time_dt = clone $start_time_dt;
                $end_time_dt->modify("+{$row['Hours']} hours");
                $end_time_str = $end_time_dt->format('H:i');
            } catch (Exception $e) {
                $end_time_str = 'N/A';
            }
            
            $monthly_bookings[] = [
                'Date_Book' => $row['Date_Book'], 
                'StartTime' => substr($row['StartTime'], 0, 5), // H:i
                'Hours' => $row['Hours'],
                'EndTime' => $end_time_str // ส่ง EndTime ที่คำนวณแล้วไป
            ];
        }
    }

    // Report generation logic
    $report_bookings = [];
    $service_counts = [];
    $service_percentages = [];
    $total_bookings_count = 0;

    // Check if report dates are set in the URL
    if (isset($_GET['report_start_date']) && isset($_GET['report_end_date'])) {
        $start_date = $_GET['report_start_date'];
        $end_date = $_GET['report_end_date'];

        // Fetch bookings for the specified date range
        $sql_report = "SELECT ServiceType, Total_P
                        FROM Booking
                        WHERE Date_Book BETWEEN ? AND ? AND Booking_Status != 'ยกเลิกแล้ว'";
        $stmt_report = $conn->prepare($sql_report);

        if ($stmt_report) {
            $stmt_report->bind_param("ss", $start_date, $end_date);
            $stmt_report->execute();
            $result_report = $stmt_report->get_result();

            if ($result_report->num_rows > 0) {
                while($row = $result_report->fetch_assoc()) {
                    $report_bookings[] = $row;
                }
            }
            $stmt_report->close();
        }

        // Process data for the report summary
        foreach ($report_bookings as $booking) {
            $service = $booking['ServiceType'];
            if (!isset($service_counts[$service])) {
                $service_counts[$service] = 0;
            }
            $service_counts[$service]++;
            $total_bookings_count++;
        }

        // Calculate percentages
        if ($total_bookings_count > 0) {
            foreach ($service_counts as $service => $count) {
                $service_percentages[$service] = ($count / $total_bookings_count) * 100;
            }
        }
    }

    if (isset($_GET['action']) && $_GET['action'] == 'fetch_monthly_bookings' && isset($_GET['month']) && isset($_GET['year'])) {
    $month = (int)$_GET['month'];
    $year = (int)$_GET['year'];
    $monthly_bookings = [];

    // ใช้ SQL Query เดิมที่คุณมี
    $sql_monthly = "SELECT Date_Book, StartTime, Hours FROM Booking WHERE YEAR(Date_Book) = ? AND MONTH(Date_Book) = ? AND Booking_Status != 'ยกเลิกแล้ว'";
    $stmt_monthly = $conn->prepare($sql_monthly);
    
    if ($stmt_monthly) {
        $stmt_monthly->bind_param("ii", $year, $month);
        $stmt_monthly->execute();
        $result_monthly = $stmt_monthly->get_result();

         while($row = $result_monthly->fetch_assoc()) {
            try {
                $start_time_dt = new DateTime($row['Date_Book'] . ' ' . $row['StartTime']);
                $end_time_dt = clone $start_time_dt;
                $end_time_dt->modify("+{$row['Hours']} hours");
                $end_time_str = $end_time_dt->format('H:i');
            } catch (Exception $e) {
                $end_time_str = 'N/A';
            }
            
            $monthly_bookings[] = [
                'Date_Book' => $row['Date_Book'], 
                'StartTime' => substr($row['StartTime'], 0, 5), 
                'Hours' => $row['Hours'],
                'EndTime' => $end_time_str 
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'bookings' => $monthly_bookings]);
    exit; // สำคัญมาก: หยุดการทำงานของโค้ด PHP ส่วนที่เหลือ
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - จัดการการจอง</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mitr:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="THSarabunNew.js"></script>
    <style>
        /*
         * CSS ถูกแก้ไขเพื่อเปลี่ยน font-weight ในหลายๆ องค์ประกอบ:
         * 1. font-weight: none; ถูกเปลี่ยนเป็น 400 หรือ 500
         * 2. font-weight: 600; หรือ bold ถูกเปลี่ยนเป็น 400 หรือ 500 เพื่อลดความหนา
         */
        
        :root {
            --primary-color: #004d99; /* Navy Blue for professionalism */
            --secondary-color: #f0f2f5;
            --text-color: #333;
            --light-grey: #e9ecef;
            --border-color: #ccc;
        }

        body {
            font-family: 'Mitr', sans-serif;
            margin: 0;
            background-color: var(--secondary-color);
            color: var(--text-color);
        }

        .dashboard-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar-top {
            background-color: var(--primary-color);
            color: #fff;
            padding: 15px 30px;
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .navbar-top .navbar-brand {
            color: #fff;
            font-size: 1.8rem;
            text-decoration: none;
            font-weight: 500; /* [FIXED] เปลี่ยนจาก 'none' เป็น 500 (Semi-bold) */
        }

        .nav-pills {
            display: flex;
            gap: 20px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s, background-color 0.3s;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .nav-link.active {
            background-color: #fff;
            color: var(--primary-color);
            font-weight: 500; /* [FIXED] เปลี่ยนจาก 'none' เป็น 500 */
        }

        .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active:hover {
            background-color: #fff;
        }

        .nav-link i {
            margin-right: 6px;
        }

        .main-content {
            flex-grow: 1;
            padding: 25px;
        }

        /* สำหรับการจำลอง Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .dashboard-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h1, h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 25px;
            font-weight: 400; /* [FIXED] เปลี่ยนจาก 600 เป็น 400 (Normal) */
        }

        .table-container {
            overflow-x: auto;
        }

        .booking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .booking-table th, .booking-table td {
            padding: 15px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        .booking-table th {
            background-color: var(--light-grey);
            color: #555;
            font-weight: 500; /* [FIXED] เปลี่ยนจาก 'none' เป็น 500 */
            text-transform: uppercase;
        }

        .booking-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .booking-table tr:hover {
            background-color: var(--light-grey);
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: #fff;
            transition: background-color 0.3s, transform 0.1s;
            font-weight: 400; /* [FIXED] เปลี่ยนจาก 200 เป็น 400 (Normal) */
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary { background-color: #007bff; }
        .btn-primary:hover { background-color: #0069d9; }
        .btn-success { background-color: #51cd6eff; }
        .btn-success:hover { background-color: #218838; }
        .btn-danger { background-color: #6a3f43ff; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-info { background-color: #577d83ff; }
        .btn-info:hover { background-color: #138496; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-warning { background-color: #ffc107; color: #333; }
        .btn-warning:hover { background-color: #cdaf57ff; }

        .badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 500; /* [FIXED] เปลี่ยนจาก 'none' เป็น 500 */
        }

        .badge-warning { background-color: #ffc107; color: #000 !important; }
        .badge-info { background-color: #17a2b8; color: #fff !important; }
        .badge-success { background-color: #3aa551ff; color: #fff !important; }
        .badge-danger { background-color: #dc3545; color: #fff !important; }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 25px;
            border: 1px solid #888;
            width: 90%;
            max-width: 700px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 32px;
            font-weight: 400; /* [FIXED] เปลี่ยนจาก 'none' เป็น 400 */
        }

        .close:hover, .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .report-form {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px;
            background-color: var(--light-grey);
            border-radius: 8px;
        }

        .report-form input[type="date"] {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
        }

        .report-results table {
            margin-top: 25px;
        }

        .button-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .add-booking-form {
            max-width: 650px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary-color);
        }

        .form-row {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 10px;
            font-weight: 500; /* [FIXED] เปลี่ยนจาก 600 เป็น 500 */
            color: #555;
            display: block;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 18px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
        }

        .form-actions {
            text-align: center;
            margin-top: 40px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn-submit {
            padding: 12px 30px;
            font-size: 18px;
            border-radius: 8px;
            font-weight: 500; /* [FIXED] เปลี่ยนจาก 'none' เป็น 500 */
        }

        .calendar-section {
            padding: 20px;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-nav h3 {
            margin: 0;
            color: var(--primary-color);
        }

        #calendar-grid {
            width: 100%;
            border-collapse: collapse;
        }

        #calendar-grid th, #calendar-grid td {
            text-align: center;
            padding: 12px;
            border: 1px solid var(--border-color);
        }

        #calendar-grid th {
            background-color: var(--light-grey);
            color: #555;
            font-weight: 400; /* แก้ไข: ทำให้หัวข้อวันในสัปดาห์เป็นความหนาปกติ */
            padding: 10px;
        }

        .booked-day {
            background-color: #d1e7dd;
            color: #0f5132;
            font-weight: 500; /* [FIXED] เปลี่ยนจาก 'bold' เป็น 500 */
        }

        .calendar-info {
            margin-top: 15px;
            font-size: 0.9em;
            text-align: center;
        }

        .calendar-info span {
            padding: 5px 10px;
            border-radius: 6px;
        }

        .calendar-info .booked {
            background-color: #d1e7dd;
        }

        .booking-label {
            font-size: 0.8em;
            color: #dc3545;
            display: block;
            margin-top: 5px;
            font-weight: normal;
        }

        @media (max-width: 768px) {
            .navbar-top {
                flex-direction: column;
                gap: 10px;
            }
            .nav-pills {
                justify-content: center;
                gap: 10px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<script>
// ตรวจสอบให้แน่ใจว่าโค้ดทำงานหลังจากที่ HTML โหลดเสร็จแล้ว
window.addEventListener('DOMContentLoaded', (event) => {
    // 1. รับข้อมูลบริการจาก PHP (ตรวจสอบว่าตัวแปร PHP ถูก echo ออกมาอย่างถูกต้อง)
    const serviceData = <?php echo $service_data_json; ?>;

    const serviceSelect = document.getElementById('new_service_type');
    const serviceIdInput = document.getElementById('new_service_id');
    const locationInput = document.getElementById('new_location');
    
    // หาก Element ใด Element หนึ่งหาไม่พบ จะไม่เกิด Error ใน Console
    if (!serviceSelect || !serviceIdInput || !locationInput) {
        console.error("Critical error: One or more form elements (new_service_type, new_service_id, or new_location) could not be found.");
        return; // หยุดการทำงานของ script ถ้าหา element ไม่พบ
    }

    // 2. ฟังก์ชันหลักในการอัปเดตค่าและสถานะการล็อค
    function updateLocationField() {
        const selectedServiceType = serviceSelect.value;
        
        // ล้างค่าเริ่มต้นทั้งหมดก่อน (เพื่อป้องกันค่าตกค้าง)
        serviceIdInput.value = '';
        locationInput.value = '';
        locationInput.removeAttribute('readonly');
        locationInput.style.backgroundColor = '#fff';

        if (selectedServiceType && serviceData[selectedServiceType]) {
            const data = serviceData[selectedServiceType];
            
            // ตั้งค่า Service ID และ Location โดยอัตโนมัติ
            serviceIdInput.value = data.id;
            locationInput.value = data.location;
            
            // Logic การล็อค/ปลดล็อคฟิลด์ Location
            if (data.is_locked) {
                // ล็อคฟิลด์ (สำหรับบริการสตูดิโอ)
                locationInput.setAttribute('readonly', true);
                locationInput.style.backgroundColor = '#e9ecef';
            } else {
                // ปลดล็อคฟิลด์ (สำหรับบริการนอกสถานที่)
                locationInput.removeAttribute('readonly');
                locationInput.style.backgroundColor = '#fff';
                // หาก Location เป็นค่าว่าง ให้ focus เพื่อกรอก
                if (locationInput.value === '') {
                    locationInput.focus();
                }
            }
        }
    }

    // 3. เพิ่ม Event Listener เมื่อประเภทบริการมีการเปลี่ยนแปลง
    serviceSelect.addEventListener('change', updateLocationField);
    
    // [แก้ไขเพิ่มเติม]: เรียกใช้ฟังก์ชันครั้งแรกเมื่อโหลดหน้า
    // เพื่อตั้งค่า Location ที่ถูกต้อง ถ้าฟอร์มมีการเลือกค่าเริ่มต้นไว้
    updateLocationField();
});

    async function fetchBookingsForCalendar(month, year) {
    const url = `BookAdmin.php?action=fetch_monthly_bookings&month=${month + 1}&year=${year}`; // +1 เพราะ month ใน JS เป็น 0-11
    
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();

        if (data.success) {
            // เคลียร์และสร้าง Map ข้อมูลใหม่จากผลลัพธ์ที่ได้
            bookedDates.clear(); 
            data.bookings.forEach(booking => {
                const date = booking.Date_Book;
                const startTime = booking.StartTime;
                const hours = booking.Hours;
                const endTime = booking.EndTime;
                const key = `${startTime} - ${endTime} น. (${hours} ชม.)`;

                if (!bookedDates.has(date)) {
                    bookedDates.set(date, []);
                }
                bookedDates.get(date).push(key);
            });
            // เมื่อได้ข้อมูลใหม่แล้ว ให้เรียกวาดปฏิทิน
            renderCalendar(); 
        } else {
            console.error('Failed to fetch bookings:', data);
        }
    } catch (error) {
        console.error('Error fetching data:', error);
    }
}
</script>
<body>

<div class="dashboard-wrapper">
    <nav class="navbar-top">
        <a class="navbar-brand" href="#">Xmee Studio</a>
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#all-bookings" onclick="openTab(event, 'all-bookings')"><i class="fas fa-calendar-check"></i> การจองทั้งหมด</a></li>
            <li class="nav-item"><a class="nav-link" href="#deposit-payments" onclick="openTab(event, 'deposit-payments')"><i class="fas fa-money-check-alt"></i> ยืนยันชำระเงินมัดจำ</a></li>
            <li class="nav-item"><a class="nav-link" href="#calendar" onclick="openTab(event, 'calendar')"><i class="fas fa-calendar-alt"></i> ปฏิทินการจอง</a></li>
            <li class="nav-item"><a class="nav-link" href="#add-booking" onclick="openTab(event, 'add-booking')"><i class="fas fa-plus-circle"></i> เพิ่มการจองใหม่</a></li>
            <li class="nav-item"><a class="nav-link" href="Showbooking.php"><i class="fas fa-chart-bar"></i> ดูรายงาน</a></li>
            <li class="nav-item"><a class="nav-link" href="Customer.php"><i class="fas fa-users"></i> ข้อมูลลูกค้า</a></li>
            <li class="nav-item"><a class="nav-link" href="ServiceAdmin.php"><i class="fas fa-tags"></i> บริการ/เสริม</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="dashboard-container">
            <div id="all-bookings" class="tab-content active">
                <h2>รายการจองทั้งหมด</h2>
                <div class="table-container">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>ID การจอง</th>
                                <th>ข้อมูลลูกค้า</th>
                                <th>บริการ</th>
                                <th>สถานที่</th> <th>วันที่ & เวลา</th>
                                <th>ชั่วโมง</th>
                                <th>ราคา</th>
                                <th>สถานะการจอง</th>
                                <th>สถานะการชำระเงิน</th>
                                <th>เอกสารแนบ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr><td colspan="10" style="text-align: center;">ยังไม่มีการจองในขณะนี้</td></tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr id="row-<?php echo htmlspecialchars($booking['Booking_ID']); ?>">
                                    <td><?php echo htmlspecialchars($booking['Booking_ID']); ?></td>
                                    <td>
                                        <span class="info-label">ชื่อ:</span> <?php echo htmlspecialchars($booking['F_name'] . " " . $booking['L_name']); ?><br>
                                        <span class="info-label">โทร:</span> <?php echo htmlspecialchars($booking['phone']); ?><br>
                                        <span class="info-label">อีเมล:</span> <?php echo htmlspecialchars($booking['Email_C']); ?>
                                    </td>
                                    <td>
                                        <span id="serviceType-<?php echo htmlspecialchars($booking['Booking_ID']); ?>"><?php echo htmlspecialchars($booking['ServiceType']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['Location']); ?> 
                                    </td>
                                    <td>
                                        <span id="date-<?php echo htmlspecialchars($booking['Booking_ID']); ?>" data-original="<?php echo htmlspecialchars($booking['Date_Book']); ?>"><?php echo htmlspecialchars($booking['Date_Book']); ?></span><br>
                                        เวลา <span id="time-<?php echo htmlspecialchars($booking['Booking_ID']); ?>" data-original="<?php echo htmlspecialchars($booking['StartTime']); ?>"><?php echo htmlspecialchars($booking['StartTime']); ?></span> น.
                                    </td>
                                    <td>
                                        <span id="hours-<?php echo htmlspecialchars($booking['Booking_ID']); ?>" data-original="<?php echo htmlspecialchars($booking['Hours']); ?>"><?php echo htmlspecialchars($booking['Hours']); ?></span>
                                    </td>
                                    <td>
                                        <span id="price-<?php echo htmlspecialchars($booking['Booking_ID']); ?>" data-original="<?php echo htmlspecialchars($booking['Total_P']); ?>"><?php echo number_format(htmlspecialchars($booking['Total_P'])); ?></span> บาท
                                    </td>
                                    <td>
                                        <span class="badge
                                            <?php
                                                if ($booking['Booking_Status'] == 'ยืนยันแล้ว' || $booking['Booking_Status'] == 'Confirmed') echo 'badge-success';
                                                else if ($booking['Booking_Status'] == 'รอการยืนยัน' || $booking['Booking_Status'] == 'Pending') echo 'badge-warning';
                                                else echo 'badge-danger';
                                            ?>">
                                            <?php echo htmlspecialchars($booking['Booking_Status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $paymentStatus = htmlspecialchars($booking['Payment_Status']);
                                            $badgeClass = '';
                                        if ($paymentStatus == 'ชำระเงินเต็มจำนวนแล้ว' || $paymentStatus == 'Paid') {
                                            $badgeClass = 'badge-success';
                                        } else if ($paymentStatus == 'ชำระเงินมัดจำแล้ว') {
                                            $badgeClass = 'badge-info';
                                        } else if ($paymentStatus == 'รอตรวจสอบ (มัดจำ)' || $paymentStatus == 'รอตรวจสอบ (เต็มจำนวน)') {
                                            $badgeClass = 'badge-warning';
                                        } else if ($paymentStatus == 'ยังไม่ชำระเงิน' || $paymentStatus == 'Unpaid') {
                                            $badgeClass = 'badge-danger';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>" style="color: #000;">
                                            <?php echo $paymentStatus; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($booking['Slip_File'])): ?>
                                            <!-- แก้ไข: เปลี่ยนเป็น "หลักฐาน" (หรือ "สลิป") และเพิ่มคลาส btn-sm เพื่อให้เล็กลง -->
                                            <button class="btn btn-secondary btn-sm" onclick="viewSlip('<?php echo htmlspecialchars($booking['Slip_File']); ?>')"> หลักฐาน </button>
                                        <?php else: ?>
                                            <span class="text-muted">ไม่มี</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="button-group" id="action-buttons-<?php echo htmlspecialchars($booking['Booking_ID']); ?>">
                                            <?php
                                                $bookingStatus = trim(htmlspecialchars($booking['Booking_Status']));
                                                $paymentStatus = trim(htmlspecialchars($booking['Payment_Status']));
                                            ?>
                                            <?php if (($bookingStatus == 'รอการยืนยัน' || $bookingStatus == 'Pending') && ($paymentStatus == 'ชำระเงินเต็มจำนวนแล้ว' || $paymentStatus == 'ชำระเงินมัดจำแล้ว' || $paymentStatus == 'Paid')): ?>
                                                <button class="btn btn-success btn-sm" onclick="confirmBooking(<?php echo htmlspecialchars($booking['Booking_ID']); ?>)">ยืนยันการจอง</button>
                                            <?php endif; ?>
                                            <?php if ($paymentStatus == 'รอตรวจสอบ (มัดจำ)'): ?>
                                                <button class="btn btn-warning btn-sm" onclick="confirmPayment(<?php echo htmlspecialchars($booking['Booking_ID']); ?>, 'deposit')">ยืนยันสลิป</button>
                                            <?php endif; ?>
                                            <?php if ($paymentStatus == 'รอตรวจสอบ (เต็มจำนวน)'): ?> 
                                                <button class="btn btn-warning btn-sm" onclick="confirmPayment(<?php echo htmlspecialchars($booking['Booking_ID']); ?>, 'full')">ยืนยันชำระส่วนที่เหลือ</button>
                                            <?php endif; ?>
                                            <?php if ($bookingStatus != 'ยกเลิกแล้ว'): ?>
                                                <button class="btn btn-warning btn-sm" onclick="toggleEdit(<?php echo htmlspecialchars($booking['Booking_ID']); ?>)">แก้ไข</button>
                                                <button class="btn btn-danger btn-sm" onclick="cancelBooking(<?php echo htmlspecialchars($booking['Booking_ID']); ?>)">ยกเลิกการจอง</button>
                                            <?php endif; ?>
                                        </div>
                                        <div class="button-group" id="save-cancel-buttons-<?php echo htmlspecialchars($booking['Booking_ID']); ?>" style="display:none;">
                                            <button class="btn btn-success btn-sm" onclick="saveChanges(<?php echo htmlspecialchars($booking['Booking_ID']); ?>)">บันทึก</button>
                                            <button class="btn btn-secondary btn-sm" onclick="cancelEdit(<?php echo htmlspecialchars($booking['Booking_ID']); ?>)">ยกเลิก</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="deposit-payments" class="tab-content">
                <h2>รายการชำระเงินมัดจำ</h2>
                <div class="table-container">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>ID การจอง</th>
                                <th>ข้อมูลลูกค้า</th>
                                <th>บริการ</th>
                                <th>ยอดรวม</th>
                                <th>เอกสารแนบ</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deposit_bookings)): ?>
                                <tr><td colspan="6" style="text-align: center;">ไม่มีรายการจองที่ชำระเงินมัดจำ</td></tr>
                            <?php else: ?>
                                <?php foreach ($deposit_bookings as $booking): ?>
                                <tr id="deposit-row-<?php echo htmlspecialchars($booking['Booking_ID']); ?>">
                                    <td><?php echo htmlspecialchars($booking['Booking_ID']); ?></td>
                                    <td>
                                        <strong>ชื่อ:</strong> <?php echo htmlspecialchars($booking['F_name'] . " " . $booking['L_name']); ?><br>
                                        <strong>โทร:</strong> <?php echo htmlspecialchars($booking['phone']); ?><br>
                                        <strong>อีเมล:</strong> <?php echo htmlspecialchars($booking['Email_C']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['ServiceType']); ?></td>
                                    <td><?php echo number_format(htmlspecialchars($booking['Total_P'])); ?> บาท</td>
                                    <td>
                                        <?php if (!empty($booking['Slip_File'])): ?>
                                            <button class="btn btn-info" onclick="viewSlip('<?php echo htmlspecialchars($booking['Slip_File']); ?>')">ดูสลิป</button>
                                        <?php else: ?>
                                            ไม่มี
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="button-group">
                                            <button class="btn btn-success btn-sm" onclick="confirmPayment(<?php echo htmlspecialchars($booking['Booking_ID']); ?>, 'full')">ยืนยันชำระส่วนที่เหลือ</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="calendar" class="tab-content">
                <h2>Booking calender</h2>
                <div class="calendar-section">
                    <div class="calendar-nav">
                        <button class="btn btn-secondary" id="prevMonthBtn"><i class="fas fa-chevron-left"></i> ก่อนหน้า</button>
                        <h3 id="currentMonthYear"></h3>
                        <button class="btn btn-secondary" id="nextMonthBtn">ถัดไป <i class="fas fa-chevron-right"></i></button>
                    </div>
                    <table id="calendar-grid">
                        <thead>
                            <tr>
                                <th>อาทิตย์</th>
                                <th>จันทร์</th>
                                <th>อังคาร</th>
                                <th>พุธ</th>
                                <th>พฤหัสบดี</th>
                                <th>ศุกร์</th>
                                <th>เสาร์</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                    <div class="calendar-info">
                        <span class="booked"></span> = วันที่มีการจองแล้ว
                    </div>
                </div>
            </div>

            <div id="add-booking" class="tab-content">
    <div class="add-booking-form">
        <div class="form-header">
            <h2>เพิ่มการจองใหม่</h2>
            <p>กรุณากรอกรายละเอียดการจองให้ครบถ้วน</p>
        </div>
        <form id="addBookingForm">
            <div class="form-row">
                <div class="form-group"><label for="new_cus_name">ชื่อ</label><input type="text" id="new_cus_name" name="new_cus_name" class="form-control" required></div>
                <div class="form-group"><label for="new_cus_lname">นามสกุล</label><input type="text" id="new_cus_lname" name="new_cus_lname" class="form-control" required></div>
                <div class="form-group"><label for="new_cus_phone">เบอร์โทรศัพท์</label><input type="tel" id="new_cus_phone" name="new_cus_phone" class="form-control" required></div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label for="new_cus_email">อีเมลลูกค้า</label><input type="email" id="new_cus_email" name="new_cus_email" class="form-control"></div>
                
                <div class="form-group">
                    <label for="new_payment_status">สถานะการชำระเงิน</label>
                    <select class="form-control" id="new_payment_status" name="new_payment_status" required>
                        <option value="ชำระเงินเต็มจำนวนแล้ว">ชำระเงินเต็มจำนวนแล้ว</option>
                        <option value="ชำระเงินมัดจำแล้ว">ชำระเงินมัดจำแล้ว</option>
                        <option value="รอดำเนินการ">รอตรวจสอบ (มัดจำ)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="new_service_type">ประเภทบริการ</label>
                    <select id="new_service_type" name="new_service_type" class="form-control" required>
                        <option value="">เลือกบริการ</option>
                        <?php
                        // ใช้ตัวแปร $services ที่ดึงมาในส่วน PHP ข้างบน
                        if (!empty($services)) {
                            foreach ($services as $service) {
                                echo '<option value="' . htmlspecialchars($service['ServiceType']) . '">' . htmlspecialchars($service['ServiceType']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <input type="hidden" id="new_service_id" name="new_service_id" value=""> 
    
                <div class="form-group">
                    <label for="new_location">สถานที่ (Location)</label>
                    <input type="text" id="new_location" name="new_location" class="form-control" readonly required> 
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="new_date">วันที่</label><input type="date" id="new_date" name="new_date" class="form-control" required></div>
                <div class="form-group"><label for="new_time">เวลา</label><input type="time" id="new_time" name="new_time" class="form-control" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="new_hours">ชั่วโมง</label><input type="number" id="new_hours" name="new_hours" class="form-control" required min="1"></div>
                <div class="form-group"><label for="new_total_price">ราคา</label><input type="number" id="new_total_price" name="new_total_price" class="form-control" required min="0"></div>
            </div>

            <div class="form-row">
                <div class="form-group full-width"><label for="new_note">หมายเหตุ (Note)</label><textarea id="new_note" name="new_note" class="form-control"></textarea></div>
            </div>
            <div class="form-row">
                <div class="form-group full-width"><label for="new_slip_file">ชื่อไฟล์สลิป (Slip File)</label><input type="text" id="new_slip_file" name="new_slip_file" class="form-control"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-submit">เพิ่มการจอง</button>
                <button type="reset" class="btn btn-secondary btn-submit">ล้างข้อมูล</button>
            </div>
        </form>
    </div>
</div>

            <div id="reports" class="tab-content">
                <h2>รายงานการจอง</h2>
                <div class="report-form">
                    <form action="BookAdmin.php" method="GET" class="d-flex">
                        <label for="report_start_date" class="form-label me-2">จาก:</label>
                        <input type="date" id="report_start_date" name="report_start_date" required class="me-3">
                        <label for="report_end_date" class="form-label me-2">ถึง:</label>
                        <input type="date" id="report_end_date" name="report_end_date" required class="me-3">
                        <button type="submit" class="btn btn-primary">สร้างรายงาน</button>
                        <input type="hidden" name="tab" value="reports">
                    </form>
                </div>

                <?php if (!empty($service_percentages)): ?>
                    <div class="report-results mt-4">
                        <h3>สรุปรายงานตามประเภทบริการ (<?php echo htmlspecialchars($_GET['report_start_date']); ?> ถึง <?php echo htmlspecialchars($_GET['report_end_date']); ?>)</h3>
                        <div class="table-container mb-3">
                            <table class="booking-table" id="service-report-table">
                                <thead>
                                    <tr>
                                        <th>ประเภทบริการ</th>
                                        <th>จำนวนการจอง</th>
                                        <th>เปอร์เซ็นต์</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($service_counts as $type => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($type); ?></td>
                                            <td><?php echo htmlspecialchars($count); ?></td>
                                            <td><?php echo number_format(htmlspecialchars($service_percentages[$type]), 2); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="text-align: center;">
                             <button class="btn btn-info" onclick="generatePDF()">พิมพ์รายงานเป็น PDF</button>
                        </div>
                    </div>
                <?php elseif(isset($_GET['report_start_date'])): ?>
                    <p style="text-align: center;">ไม่พบข้อมูลการจองในช่วงวันที่ที่เลือก</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="slipModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h4>สลิปการโอนเงิน</h4>
            <img id="slipImage" src="" alt="Slip of payment" style="width:100%;">
        </div>
    </div>

</div>

<script src="THSarabunNew.js"></script>

<script>
    function openTab(evt, tabName) {
        let i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("nav-pills")[0].getElementsByTagName("a");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.classList.add("active");
        window.location.hash = tabName;

        // Specific logic for Calendar tab
        if (tabName === 'calendar') {
             renderCalendar();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('.nav-pills li a');
        const sections = document.querySelectorAll('.tab-content');

        function showSectionFromHash() {
            let hash = window.location.hash || '#all-bookings';

            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                hash = `#${tabParam}`;
            }

            sections.forEach(section => {
                section.style.display = 'none';
            });
            links.forEach(link => {
                link.classList.remove("active");
            });

            const targetSection = document.querySelector(hash);
            if (targetSection) {
                targetSection.style.display = "block";
                const activeLink = document.querySelector(`.nav-pills a[href="${hash}"]`);
                if (activeLink) {
                    activeLink.classList.add("active");
                }
            }
        }

        window.addEventListener('hashchange', showSectionFromHash);
        showSectionFromHash();
    });


    function generatePDF() {
        if (typeof window.jsPDF === 'undefined' || typeof window.autoTable === 'undefined') {
            alert('ไม่สามารถสร้าง PDF ได้. กรุณาลองใหม่อีกครั้ง');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.addFileToVFS('THSarabunNew-normal.ttf', font);
        doc.addFont('THSarabunNew-normal.ttf', 'THSarabunNew', 'normal');

        const startDate = document.getElementById('report_start_date').value;
        const endDate = document.getElementById('report_end_date').value;

        doc.setFontSize(16);
        doc.text(`รายงานสรุปการจองตามประเภทบริการ`, 14, 20);
        doc.text(`ตั้งแต่วันที่ ${startDate} ถึง ${endDate}`, 14, 30);

        doc.autoTable({
            html: '#service-report-table',
            startY: 40,
            theme: 'striped',
            headStyles: {
                fillColor: [52, 73, 94],
                font: 'THSarabunNew',
                fontStyle: 'normal'
            },
            bodyStyles: {
                font: 'THSarabunNew',
                fontStyle: 'normal'
            },
            didDrawPage: function (data) {
                doc.setFont('THSarabunNew');
                doc.setFontSize(10);
                doc.setTextColor(40);
                doc.text("Generated by Admin System", data.settings.margin.left, doc.internal.pageSize.height - 10);
            }
        });

        doc.save('booking_report.pdf');
    }

    function viewSlip(slipUrl) {
        var modal = document.getElementById("slipModal");
        var img = document.getElementById("slipImage");
        img.src = "uploads/" + slipUrl;
        modal.style.display = "block";
    }

    function closeModal() {
        var modal = document.getElementById("slipModal");
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        var modal = document.getElementById("slipModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function confirmPayment(bookingId, paymentType) {
        let confirmMessage;
        if (paymentType === 'deposit') {
            confirmMessage = 'คุณต้องการยืนยันการชำระเงินมัดจำสำหรับการจองนี้หรือไม่?';
        } else if (paymentType === 'full') {
            confirmMessage = 'คุณต้องการยืนยันการชำระเงินเต็มจำนวนสำหรับการจองนี้หรือไม่?';
        } else {
            confirmMessage = 'คุณต้องการยืนยันการชำระเงินสำหรับการจองนี้หรือไม่?';
        }

        if (confirm(confirmMessage)) {
            fetch('updatePaymentStatus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `booking_id=${bookingId}&payment_type=${paymentType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ยืนยันการชำระเงินเรียบร้อยแล้ว');
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        }
    }

    function confirmBooking(bookingId) {
        if (confirm("ต้องการยืนยันการจองนี้หรือไม่?")) {
            const formData = new URLSearchParams();
            formData.append('Booking_ID', bookingId);

            fetch('confirmBook.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ยืนยันการจองเรียบร้อยแล้ว');
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        }
    }

    function cancelBooking(bookingId) {
        if (confirm(`คุณต้องการยกเลิกการจอง ID: ${bookingId} ใช่หรือไม่?`)) {
            fetch('cancelBook.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `Booking_ID=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`การจอง ID: ${bookingId} ถูกยกเลิกแล้ว`);
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดในการยกเลิกการจอง: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        }
    }

    function toggleEdit(bookingId) {
        const actionButtonGroup = document.getElementById(`action-buttons-${bookingId}`);
        const saveCancelButtonGroup = document.getElementById(`save-cancel-buttons-${bookingId}`);

        const dateSpan = document.getElementById(`date-${bookingId}`);
        const timeSpan = document.getElementById(`time-${bookingId}`);
        const hoursSpan = document.getElementById(`hours-${bookingId}`);
        const priceSpan = document.getElementById(`price-${bookingId}`);

        if (actionButtonGroup.style.display !== 'none') {
            actionButtonGroup.style.display = 'none';
            saveCancelButtonGroup.style.display = 'flex';
        } else {
            actionButtonGroup.style.display = 'flex';
            saveCancelButtonGroup.style.display = 'none';
        }

        dateSpan.innerHTML = `<input type="date" id="input-date-${bookingId}" value="${dateSpan.dataset.original}" class="form-control" />`;
        timeSpan.innerHTML = `<input type="time" id="input-time-${bookingId}" value="${timeSpan.dataset.original}" class="form-control" />`;
        hoursSpan.innerHTML = `<input type="number" id="input-hours-${bookingId}" value="${hoursSpan.dataset.original}" class="form-control" />`;
        priceSpan.innerHTML = `<input type="number" id="input-price-${bookingId}" value="${priceSpan.dataset.original}" class="form-control" />`;
    }

    function cancelEdit(bookingId) {
        const actionButtonGroup = document.getElementById(`action-buttons-${bookingId}`);
        const saveCancelButtonGroup = document.getElementById(`save-cancel-buttons-${bookingId}`);

        actionButtonGroup.style.display = 'flex';
        saveCancelButtonGroup.style.display = 'none';

        const dateSpan = document.getElementById(`date-${bookingId}`);
        const timeSpan = document.getElementById(`time-${bookingId}`);
        const hoursSpan = document.getElementById(`hours-${bookingId}`);
        const priceSpan = document.getElementById(`price-${bookingId}`);

        dateSpan.innerHTML = dateSpan.dataset.original;
        timeSpan.innerHTML = timeSpan.dataset.original;
        hoursSpan.innerHTML = hoursSpan.dataset.original;
        priceSpan.innerHTML = new Intl.NumberFormat().format(priceSpan.dataset.original);
    }

    function saveChanges(bookingId) {
        const newDate = document.getElementById(`input-date-${bookingId}`).value;
        const newTime = document.getElementById(`input-time-${bookingId}`).value;
        const newHours = document.getElementById(`input-hours-${bookingId}`).value;
        const newPrice = document.getElementById(`input-price-${bookingId}`).value;

        if (confirm(`คุณต้องการบันทึกการเปลี่ยนแปลงสำหรับการจอง ID: ${bookingId} ใช่หรือไม่?`)) {
            fetch('updateBooking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `booking_id=${bookingId}&date=${newDate}&time=${newTime}&hours=${newHours}&price=${newPrice}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว');
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        }
    }

    document.getElementById('addBookingForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const form = this;
        const formData = new FormData(form);

        // ดึงข้อมูลวันที่และเวลาจากฟอร์ม
        const bookingDate = formData.get('new_date');
        const bookingTime = formData.get('new_time');
        const bookingHours = formData.get('new_hours');

        // ตรวจสอบความพร้อมใช้งานก่อนส่งข้อมูลจริง
        fetch('checkAvailability.php', {
            method: 'POST',
            body: new URLSearchParams({
                date: bookingDate,
                time: bookingTime,
                hours: bookingHours
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                // ถ้ามีเวลาว่าง ก็ส่งข้อมูลการจองจริง
                submitBooking(formData);
            } else {
                // ถ้าไม่มีเวลาว่าง ให้แสดงข้อความแจ้งเตือน
                alert('ขออภัย! วันที่และเวลาที่เลือกไม่ว่าง กรุณาเลือกวันและเวลาใหม่');
            }
        })
        .catch(error => {
            console.error('Error checking availability:', error);
            alert('เกิดข้อผิดพลาดในการตรวจสอบเวลาว่าง');
        });
    });

    // ฟังก์ชันสำหรับส่งข้อมูลการจองจริง
    function submitBooking(formData) {
        fetch('addBooking.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('เพิ่มการจองเรียบร้อยแล้ว');
                window.location.reload();
            } else {
                alert('เกิดข้อผิดพลาดในการเพิ่มการจอง: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error adding booking:', error);
            alert('เกิดข้อผิดพลาดในการส่งข้อมูลการจอง');
        });
    }

    // ซิงค์ค่าเริ่มต้นของ JS กับ PHP ที่ใช้ในการดึงข้อมูล (currentMonth ใน JS เป็น 0-11, PHP $month เป็น 1-12)
    let currentMonth = <?php echo (int)$month - 1; ?>; 
    let currentYear = <?php echo (int)$year; ?>;
    const monthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];

    // PHP-provided data (convert to JS array)
    const phpBookedDates = <?php echo json_encode($monthly_bookings); ?>;
    let bookedDates = new Map();
    phpBookedDates.forEach(booking => {
        const date = booking.Date_Book;
        const startTime = booking.StartTime;
        const hours = booking.Hours;
        const endTime = booking.EndTime; // [CHANGE] ใช้ค่า EndTime ที่คำนวณจาก PHP โดยตรง

        // [CHANGE] จัดรูปแบบการแสดงผลให้แสดงเฉพาะช่วงเวลา
        const key = `${startTime} - ${endTime} น. (${hours} ชม.)`;

        if (!bookedDates.has(date)) {
            bookedDates.set(date, []);
        }
        bookedDates.get(date).push(key);
    });

    function renderCalendar() {
        const calendarGrid = document.getElementById('calendar-grid').querySelector('tbody');
        const currentMonthYear = document.getElementById('currentMonthYear');
        calendarGrid.innerHTML = ''; // Clear previous calendar

        currentMonthYear.textContent = `${monthNames[currentMonth]} ${currentYear}`;

        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        let date = 1;
        for (let i = 0; i < 6; i++) { // Max 6 rows
            const row = document.createElement('tr');

            for (let j = 0; j < 7; j++) { // 7 days in a week
                const cell = document.createElement('td');
                if (i === 0 && j < firstDay) {
                    cell.textContent = '';
                } else if (date > daysInMonth) {
                    cell.textContent = '';
                } else {
                    cell.innerHTML = `<div>${date}</div>`;
                    const formattedDate = `${currentYear}-${(currentMonth + 1).toString().padStart(2, '0')}-${date.toString().padStart(2, '0')}`;

                    if (bookedDates.has(formattedDate)) {
                        cell.classList.add('booked-day');
                        const bookingInfo = bookedDates.get(formattedDate);
                        bookingInfo.forEach(info => {
                            const label = document.createElement('span');
                            label.classList.add('booking-label');
                            label.textContent = info;
                            cell.appendChild(label);
                        });
                    }
                    date++;
                }
                row.appendChild(cell);
            }
            calendarGrid.appendChild(row);
            if (date > daysInMonth) {
                break; // Stop if all days have been added
            }
        }
    }

    document.getElementById('prevMonthBtn').addEventListener('click', () => {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    // [FIX] เรียก AJAX เพื่อดึงข้อมูลใหม่
    fetchBookingsForCalendar(currentMonth, currentYear);
});

document.getElementById('nextMonthBtn').addEventListener('click', () => {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    // [FIX] เรียก AJAX เพื่อดึงข้อมูลใหม่
    fetchBookingsForCalendar(currentMonth, currentYear);
});

    // Initial render when the page loads
    renderCalendar();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>