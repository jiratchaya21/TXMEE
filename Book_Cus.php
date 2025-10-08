<?php
    // Include database connection file and start session
    session_start();
    include "Datasql.php"; // Assuming Datasql.php contains the database connection

    $all_bookings = [];
    $has_unpaid = false;

    // Check if customer ID exists in the session
    if (isset($_SESSION['Cus_id'])) {
        $Cus_id = $_SESSION['Cus_id'];
        
        // Prepare and execute a SQL query to fetch all bookings for the customer
        // Order by the most recent booking ID to get the latest booking first
        $sql = "SELECT Booking_ID, ServiceType, Date_Book, StartTime, Hours, Total_P, Booking_Status, Payment_Status 
                FROM booking 
                WHERE Cus_id = ? ORDER BY Booking_ID DESC";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $Cus_id);
            $stmt->execute();
            $result = $stmt->get_result();

            // Store all bookings in a single array
            while ($row = $result->fetch_assoc()) {
                $all_bookings[] = $row;
            }
            $stmt->close();
        }

        // Check for any unpaid bookings
        $sql_check_unpaid = "SELECT COUNT(*) AS unpaid_count FROM Booking WHERE Cus_id = ? AND Payment_Status = 'รอการชำระเงิน'";
        $stmt_check_unpaid = $conn->prepare($sql_check_unpaid);
        if ($stmt_check_unpaid) {
            $stmt_check_unpaid->bind_param("i", $Cus_id);
            $stmt_check_unpaid->execute();
            $result_unpaid = $stmt_check_unpaid->get_result();
            $row_unpaid = $result_unpaid->fetch_assoc();
            if ($row_unpaid['unpaid_count'] > 0) {
                $has_unpaid = true;
            }
            $stmt_check_unpaid->close();
        }
    }
    $conn->close();

    // Separate the latest booking from the rest of the bookings
    $latest_booking = !empty($all_bookings) ? $all_bookings[0] : null;
    $past_bookings = !empty($all_bookings) ? array_slice($all_bookings, 1) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Customer Bookings</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #1a237e;
            font-weight: 600;
            text-align: center;
            margin-bottom: 25px;
        }
        .tab-menu {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background-color: #e8ebf0;
            border-radius: 15px;
            padding: 10px;
        }
        .tab-menu button {
            border: none;
            background: none;
            padding: 12px 25px;
            cursor: pointer;
            font-weight: 600;
            color: #555;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .tab-menu button.active {
            background-color: #282d68ff;
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .booking-details.card {
            border: none;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .booking-details.card h2 {
            font-size: 1.25rem;
            color: #3949ab;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 500;
            color: #555;
        }
        .value {
            font-weight: 400;
            text-align: right;
            color: #222;
        }
        .past-booking-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .past-booking-info {
            display: flex;
            flex-direction: column;
        }
        .past-booking-info .date {
            font-weight: 600;
            color: #777;
            font-size: 0.9rem;
        }
        .past-booking-info .service {
            font-weight: 500;
            color: #333;
            margin-top: 5px;
        }
        .status {
            font-weight: 600;
            color: #28a745;
            padding: 5px 12px;
            border-radius: 20px;
            background-color: #e9f5ed;
            font-size: 0.9rem;
        }
        /* New CSS for different statuses */
        .status.status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .status.status-confirmed {
            background-color: #0e6d24ff;
            color: #fff;
        }
        .status.status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }
        /* New CSS to fix horizontal display */
        .unpaid-alert {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            font-weight: bold;
            color: #856404;
            display: flex; /* <-- ทำให้เป็น Flexbox container */
            justify-content: space-between; /* <-- จัดวางข้อความและลิงก์ให้ห่างกัน */
            align-items: center; /* <-- จัดแนวให้อยู่ตรงกลางตามแนวตั้ง */
        }
        .unpaid-alert a {
            color: #856404;
            text-decoration: underline;
            white-space: nowrap; /* <-- ป้องกันลิงก์ตกบรรทัด */
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        .btn-edit, .btn-cancel, .btn-save, .btn-light {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-edit {
            background-color: #17a2b8;
            color: white;
            border: none;
        }
        .btn-edit:hover {
            background-color: #138496;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        .btn-cancel:hover {
            background-color: #c82333;
        }
        .btn-save {
            background-color: #28a745;
            color: white;
            border: none;
        }
        .btn-save:hover {
            background-color: #218838;
        }
        .btn-light {
            background-color: #f8f9fa;
            color: #343a40;
            border: 1px solid #ccc;
        }
        .btn-light:hover {
            background-color: #e2e6ea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">การจองของฉัน</h1>
        <?php if ($has_unpaid): ?>
            <div class="unpaid-alert">
                <span>คุณมีรายการจองที่ยังไม่ได้ชำระเงิน!</span>
                <a href="UnpaidBookings.php">คลิกเพื่อไปที่หน้ารายการที่ค้างชำระ</a>
            </div>
        <?php endif; ?>
        <div class="tab-menu">
            <button id="upcoming-tab" class="active">การจองล่าสุด</button>
            <button id="past-tab">ประวัติการจอง</button>
        </div>
        
        <div id="upcoming-bookings">
            <?php if (!empty($latest_booking)): ?>
                <section class="booking-details card">
                    <h2>รายละเอียดการจอง</h2>
                    <div class="detail-item">
                        <span class="label">หมายเลขการจอง:</span>
                        <span class="value" id="booking-id" data-booking-id="<?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>">
                            <?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">บริการ:</span>
                        <span class="value" id="service-type" data-original="<?php echo htmlspecialchars($latest_booking['ServiceType']); ?>">
                            <?php echo htmlspecialchars($latest_booking['ServiceType']); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">วันที่:</span>
                        <span class="value" id="date-book" data-original="<?php echo htmlspecialchars($latest_booking['Date_Book']); ?>">
                            <?php echo htmlspecialchars($latest_booking['Date_Book']); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">เวลา:</span>
                        <span class="value" id="start-time" data-original="<?php echo htmlspecialchars($latest_booking['StartTime']); ?>">
                            <?php echo htmlspecialchars(substr($latest_booking['StartTime'], 0, 5)); ?> น.
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">จำนวนชั่วโมง:</span>
                        <span class="value" id="hours" data-original="<?php echo htmlspecialchars($latest_booking['Hours']); ?>">
                            <?php echo htmlspecialchars($latest_booking['Hours']); ?> ชั่วโมง
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">ยอดชำระ:</span>
                        <span class="value" id="total-price" data-original="<?php echo htmlspecialchars($latest_booking['Total_P']); ?>">
                            <?php echo number_format($latest_booking['Total_P'], 2); ?> บาท
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">สถานะการจอง:</span>
                        <span class="value status <?php 
                            if ($latest_booking['Booking_Status'] == 'ยืนยันแล้ว') echo 'status-confirmed'; 
                            else if ($latest_booking['Booking_Status'] == 'รอการยืนยัน') echo 'status-pending'; 
                            else echo 'status-cancelled';
                        ?>">
                            <?php echo htmlspecialchars($latest_booking['Booking_Status']); ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="label">สถานะการชำระเงิน:</span>
                        <span class="value status <?php 
                            if ($latest_booking['Payment_Status'] == 'ชำระเงินเต็มจำนวนแล้ว') echo 'status-confirmed'; 
                            else if ($latest_booking['Payment_Status'] == 'ชำระเงินมัดจำแล้ว') echo 'status-confirmed'; 
                            else if ($latest_booking['Payment_Status'] == 'รอการชำระเงิน') echo 'status-pending'; 
                            else echo 'status-cancelled';
                        ?>">
                            <?php echo htmlspecialchars($latest_booking['Payment_Status']); ?>
                        </span>
                    </div>

                    <div class="action-buttons">
                        <?php
    // ตรวจสอบสถานะการจอง
    if ($latest_booking['Booking_Status'] == 'รอการยืนยัน') {
        ?>
        <button class="btn-edit" onclick="toggleEdit()">แก้ไข</button>
        <button class="btn-cancel" onclick="cancelBooking()">ยกเลิก</button>
        <div class="action-buttons" id="save-cancel-buttons-container" style="display: none;">
            <button class="btn-save" onclick="saveChanges()">บันทึก</button>
            <button class="btn-light" onclick="cancelEdit()">ยกเลิกการแก้ไข</button>
        </div>
        <?php
    }

    // ตรวจสอบสถานะการชำระเงิน
    if ($latest_booking['Payment_Status'] == 'รอการชำระเงิน') {
    // แยกเงื่อนไขตามประเภทบริการ
    if ($latest_booking['ServiceType'] == 'SayCheeze') {
        ?>
        <a href="Payment_Cus.php?booking_id=<?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>&type=deposit" class="btn btn-primary btn-sm">ชำระเงินมัดจำ</a>
        <a href="Payment_Cus.php?booking_id=<?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>&type=full" class="btn btn-success btn-sm">ชำระเงินเต็มจำนวน</a>
        <?php
    } else {
        // สำหรับบริการอื่น ๆ เช่นสตูดิโอ ให้ชำระเงินเต็มจำนวนเท่านั้น
        ?>
        <a href="Payment.php?booking_id=<?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>" class="btn btn-success btn-sm">ชำระเงิน</a>
        <?php
    }
}

    if ($latest_booking['Payment_Status'] == 'ชำระเงินมัดจำแล้ว') {
        ?>
        <a href="Payment_Remaining.php?booking_id=<?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>" class="btn btn-warning btn-sm">ชำระยอดคงเหลือ</a>
        <a href="Cus_receipt.php?booking_id=<?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>&type=deposit" target="_blank" class="btn btn-info btn-sm">ดูใบเสร็จ (มัดจำ)</a>
        <?php
    }

    if ($latest_booking['Payment_Status'] == 'ชำระเงินเต็มจำนวนแล้ว') {
        ?>
        <a href="Cus_receipt.php?booking_id=<?php echo htmlspecialchars($latest_booking['Booking_ID']); ?>&type=full" target="_blank" class="btn btn-success btn-sm">ดูใบเสร็จ (ชำระเต็ม)</a>
        <?php
    }
    ?>
                    </div>
                </section>
            <?php else: ?>
                <p class="text-center">ไม่มีการจอง</p>
            <?php endif; ?>
        </div>
        
        <div id="receipt-section" class="card" style="display: none; padding: 25px; margin-top: 20px;">
            <h2 class="text-center">ใบเสร็จ</h2>
            <hr>
            <div class="detail-item">
                <span class="label">หมายเลขใบเสร็จ:</span>
                <span class="value" id="receipt-id"></span>
            </div>
            <div class="detail-item">
                <span class="label">วันที่ออก:</span>
                <span class="value" id="receipt-date"></span>
            </div>
            <div class="detail-item">
                <span class="label">บริการ:</span>
                <span class="value" id="receipt-service"></span>
            </div>
            <div class="detail-item">
                <span class="label">ยอดชำระ:</span>
                <span class="value" id="receipt-total"></span>
            </div>
            <p class="text-center mt-4">
                <small>ขอบคุณที่ใช้บริการ</small>
            </p>
        </div>

        <div id="past-bookings" style="display: none;">
            <?php if (empty($past_bookings)): ?>
                <p class="text-center">ไม่มีประวัติการจอง</p>
            <?php else: ?>
                <?php foreach ($past_bookings as $booking): ?>
                    <div class="past-booking-card">
                        <div class="past-booking-info">
                            <span class="date"><?php echo htmlspecialchars($booking['Date_Book']); ?></span>
                            <span class="service"><?php echo htmlspecialchars($booking['ServiceType']); ?></span>
                        </div>
                        <span class="status <?php 
                            if ($booking['Booking_Status'] == 'ยืนยันแล้ว') echo 'status-confirmed'; 
                            else if ($booking['Booking_Status'] == 'รอการยืนยัน') echo 'status-pending'; 
                            else echo 'status-cancelled';
                        ?>"><?php echo htmlspecialchars($booking['Booking_Status']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Tab switching logic (unchanged)
    document.getElementById('upcoming-tab').addEventListener('click', function() {
        document.getElementById('upcoming-bookings').style.display = 'block';
        document.getElementById('receipt-section').style.display = 'none';
        document.getElementById('past-bookings').style.display = 'none';
        this.classList.add('active');
        document.getElementById('past-tab').classList.remove('active');
    });

    document.getElementById('past-tab').addEventListener('click', function() {
        document.getElementById('upcoming-bookings').style.display = 'none';
        document.getElementById('receipt-section').style.display = 'none';
        document.getElementById('past-bookings').style.display = 'block';
        this.classList.add('active');
        document.getElementById('upcoming-tab').classList.remove('active');
    });

    // --------------------------------------------------------------------------------
    // [FIX] แก้ไข Logic การซ่อน/แสดงปุ่ม และการคืนค่า
    // --------------------------------------------------------------------------------
    function toggleEdit() {
        // อ้างอิงถึงปุ่มเดิม (แก้ไข/ยกเลิก)
        const editBtn = document.querySelector('.btn-edit');
        const cancelBtn = document.querySelector('.btn-cancel');
        
        // อ้างอิงถึง Container ของปุ่มใหม่ (บันทึก/ยกเลิกการแก้ไข)
        const saveCancelContainer = document.getElementById('save-cancel-buttons-container');

        // 1. ซ่อนปุ่มเดิม
        if (editBtn) editBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';

        // 2. แสดงปุ่มใหม่ (บันทึก/ยกเลิกการแก้ไข)
        if (saveCancelContainer) {
            // [FIX]: ใช้ display = 'flex' เพื่อให้ปุ่มจัดเรียงแนวนอน
            saveCancelContainer.style.display = 'flex';
        }

        // 3. เปลี่ยนค่าแสดงผลเป็น input fields
        const dateSpan = document.getElementById('date-book');
        const timeSpan = document.getElementById('start-time');
        const hoursSpan = document.getElementById('hours');
        
        // ตรวจสอบให้แน่ใจว่า value ที่ใส่ใน input ถูกต้อง
        dateSpan.innerHTML = `<input type="date" class="form-control" id="edit-date" value="${dateSpan.dataset.original}">`;
        
        // เวลาต้องใส่ค่าให้ถูกต้องตาม format input type="time" (H:i)
        // Note: dataset.original สำหรับ start-time คือ HH:MM:SS ซึ่งใช้ได้กับ input type="time"
        timeSpan.innerHTML = `<input type="time" class="form-control" id="edit-time" value="${timeSpan.dataset.original.substring(0, 5)}">`; 
        
        // Number input for hours
        hoursSpan.innerHTML = `<input type="number" class="form-control" id="edit-hours" min="1" value="${hoursSpan.dataset.original}">`;
    }

    function cancelEdit() {
        // อ้างอิงถึงปุ่มเดิม (แก้ไข/ยกเลิก)
        const editBtn = document.querySelector('.btn-edit');
        const cancelBtn = document.querySelector('.btn-cancel');
        
        // อ้างอิงถึง Container ของปุ่มใหม่ (บันทึก/ยกเลิกการแก้ไข)
        const saveCancelContainer = document.getElementById('save-cancel-buttons-container');
        
        // 1. ซ่อนปุ่มใหม่
        if (saveCancelContainer) {
            saveCancelContainer.style.display = 'none';
        }
        
        // 2. แสดงปุ่มเดิม
        if (editBtn) editBtn.style.display = ''; // ใช้ '' เพื่อคืนค่าเดิม (เช่น block/inline-block/flex)
        if (cancelBtn) cancelBtn.style.display = ''; // ใช้ '' เพื่อคืนค่าเดิม

        // 3. เปลี่ยน input fields กลับเป็นค่าเดิม
        const dateSpan = document.getElementById('date-book');
        const timeSpan = document.getElementById('start-time');
        const hoursSpan = document.getElementById('hours');

        dateSpan.innerHTML = dateSpan.dataset.original;
        // [FIX]: แสดงเวลาแค่ HH:MM และเพิ่ม 'น.' กลับไป
        timeSpan.innerHTML = timeSpan.dataset.original.substring(0, 5) + ' น.';
        
        // [FIX]: ต้องเพิ่ม 'ชั่วโมง' กลับไป
        hoursSpan.innerHTML = hoursSpan.dataset.original + ' ชั่วโมง'; 
    }
    // --------------------------------------------------------------------------------

    function saveChanges() {
        const bookingId = document.getElementById('booking-id').dataset.bookingId;
        const newDate = document.getElementById('edit-date').value;
        const newTime = document.getElementById('edit-time').value;
        const newHours = document.getElementById('edit-hours').value;
        
        // [WARNING] ข้อมูลราคา (Total_P) หายไปจาก logic การส่งข้อมูล
        // เนื่องจากข้อมูลราคาอาจต้องคำนวณใหม่ตามจำนวนชั่วโมงและประเภทบริการ
        // แต่เนื่องจากคุณไม่ได้ส่งตรรกะการคำนวณราคามา ผมจะใช้ราคาเดิมไปก่อน
        const newPrice = document.getElementById('total-price').dataset.original; 
        
        if (confirm('คุณต้องการบันทึกการเปลี่ยนแปลงนี้ใช่หรือไม่?')) {
            fetch('updateBooking_Cus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                // [FIX] เพิ่ม price เข้าไปในการส่งข้อมูล
                body: `booking_id=${bookingId}&date=${newDate}&time=${newTime}&hours=${newHours}&price=${newPrice}` 
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว');
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

    function cancelBooking() {
        const bookingId = document.getElementById('booking-id').dataset.bookingId;
        
        if (confirm('คุณต้องการยกเลิกการจองนี้ใช่หรือไม่?')) {
            fetch('cancelBooking_Cus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `booking_id=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('การจองถูกยกเลิกเรียบร้อยแล้ว');
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

    // ฟังก์ชันใหม่สำหรับแสดงใบเสร็จ (unchanged)
    function showReceipt() {
        // ดึงค่าจาก element ที่มีอยู่
        const bookingId = document.getElementById('booking-id').dataset.bookingId;
        const serviceType = document.getElementById('service-type').dataset.original;
        const totalPrice = document.getElementById('total-price').dataset.original;
        const dateBook = document.getElementById('date-book').dataset.original;

        // นำค่าที่ได้มาแสดงในส่วนใบเสร็จ
        document.getElementById('receipt-id').textContent = bookingId;
        document.getElementById('receipt-date').textContent = dateBook;
        document.getElementById('receipt-service').textContent = serviceType;
        document.getElementById('receipt-total').textContent = `${parseFloat(totalPrice).toLocaleString()} บาท`;

        // ซ่อนส่วนการจองล่าสุดและแสดงส่วนใบเสร็จ
        document.getElementById('upcoming-bookings').style.display = 'none';
        document.getElementById('receipt-section').style.display = 'block';
    }
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>