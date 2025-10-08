<?php
// Start the session to access user data
session_start();

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get and sanitize data from the URL parameters
$service_type = isset($_GET['service_type']) ? sanitize_input($_GET['service_type']) : 'ไม่ระบุ';
$duration = isset($_GET['duration']) ? sanitize_input($_GET['duration']) : 'ไม่ระบุ';
$event_date = isset($_GET['event_date']) ? sanitize_input($_GET['event_date']) : 'ไม่ระบุ';
$event_time = isset($_GET['event_time']) ? sanitize_input($_GET['event_time']) : 'ไม่ระบุ';
$address = isset($_GET['address']) ? sanitize_input($_GET['address']) : 'ไม่ระบุ';
$total_price_raw = isset($_GET['total_price']) ? sanitize_input($_GET['total_price']) : '0';
$notes = isset($_GET['notes']) ? sanitize_input($_GET['notes']) : 'ไม่มี';

// Get and sanitize payment option from the URL parameters
$payment_type = isset($_GET['payment_type']) ? sanitize_input($_GET['payment_type']) : 'full';

// Calculate duration surcharge and base price
$total_price = (float) $total_price_raw;
$duration_surcharge = 0;
$base_price = $total_price;

// Determine base price based on service and calculate surcharge if needed
if ($duration > 4) {
    $duration_surcharge = ($duration - 4) * 3000;
    $base_price = $total_price - $duration_surcharge;
}

// Calculate the amount to be paid and remaining balance based on payment type
$payment_amount = $total_price;
$payment_label = 'ยอดที่ต้องชำระ (เต็มจำนวน)';
$remaining_balance = 0;

if ($payment_type === 'deposit') {
    $payment_amount = 5000;
    $payment_label = 'ยอดชำระเบื้องต้น (มัดจำ)';
    $remaining_balance = $total_price - $payment_amount;
}

// Format prices with commas
$base_price_formatted = number_format($base_price);
$duration_surcharge_formatted = number_format($duration_surcharge);
$total_price_formatted = number_format($total_price);
$payment_amount_formatted = number_format($payment_amount);
$remaining_balance_formatted = number_format($remaining_balance);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสนอราคา - Xmee Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .quotation-container {
            width: 100%;
            max-width: 800px;
            background-color: #fff;
            margin: 40px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            font-size: 36px;
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
        }
        .header p {
            font-size: 16px;
            color: #666;
            margin: 5px 0 0;
        }
        .quotation-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
        }
        .quotation-info div {
            flex: 1;
        }
        .quotation-info h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .quotation-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .details-table th, .details-table td {
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .details-table th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #555;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .total-box {
            width: 100%;
            max-width: 400px; /* เพิ่มความกว้างเล็กน้อยเพื่อให้ดูดีขึ้น */
            padding: 20px;
            background-color: #f8f8f8; /* เปลี่ยนสีพื้นหลังให้ดูเป็นทางการมากขึ้น */
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .total-box h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #2c3e50;
            text-align: center;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 16px;
        }
        .summary-table td {
            padding: 8px 0;
        }
        .summary-table .price-value {
            text-align: right;
            font-weight: 500;
        }
        .summary-table .final-total {
            border-top: 2px solid #2c3e50; /* ใช้เส้นหนาเพื่อเน้นยอดรวม */
            margin-top: 10px;
        }
        .summary-table .final-total .total-label {
            font-weight: 700;
            padding-top: 10px;
        }
        .summary-table .final-total .total-value {
            font-size: 20px;
            font-weight: 700;
            color: #007bff;
            padding-top: 10px;
        }
        .total-box {
            width: 100%;
            max-width: 300px;
            padding: 20px;
            background-color: #e9ecef;
            border-radius: 8px;
            border-left: 5px solid #007bff;
        }
        .total-box h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .total-box .price-item {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .total-box .final-price {
            font-size: 24px;
            font-weight: 700;
            color: #007bff;
            border-top: 1px dashed #ced4da;
            padding-top: 10px;
            margin-top: 10px;
            text-align: right;
        }
        .notes {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .notes p {
            font-size: 14px;
            color: #888;
            line-height: 1.6;
        }
        .print-button {
            display: block;
            width: 100%;
            padding: 15px;
            margin-top: 30px;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .print-button:hover {
            background-color: #34495e;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .quotation-container {
                box-shadow: none;
                margin: 0;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="quotation-container">
    <div class="header">
        <h1>ใบเสนอราคา</h1>
        <p>Xmee Studio</p>
    </div>

    <div class="quotation-info">
        <div>
            <h2>รายละเอียดลูกค้า</h2>
            <p><strong>ชื่อ-นามสกุล:</strong> 
                <?php 
                if (isset($_SESSION['F_name']) && isset($_SESSION['L_name'])) {
                    echo htmlspecialchars($_SESSION['F_name'] . ' ' . $_SESSION['L_name']);
                } else {
                    echo 'ไม่ระบุ (ลูกค้าทั่วไป)';
                }
                ?>
            </p>
            <p><strong>อีเมล:</strong> 
                <?php echo isset($_SESSION['Email_C']) ? htmlspecialchars($_SESSION['Email_C']) : 'ไม่ระบุ'; ?>
            </p>
            <p><strong>เบอร์โทร:</strong> 
                <?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : 'ไม่ระบุ'; ?>
            </p>
        </div>
        <div>
            <h2>รายละเอียดงาน</h2>
            <p><strong>วันที่ออกใบเสนอราคา:</strong> <?php echo date("d/m/Y"); ?></p>
            <p><strong>วันที่จัดงาน:</strong> <?php echo $event_date; ?></p>
            <p><strong>เวลา:</strong> <?php echo $event_time; ?></p>
            <p><strong>สถานที่:</strong> <?php echo $address; ?></p>
        </div>
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th>รายการ</th>
                <th>รายละเอียด</th>
                <th>จำนวน</th>
                <th>ราคา</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>ค่าบริการแพ็กเกจ</td>
                <td><?php echo $service_type; ?></td>
                <td><?php echo $duration; ?> ชั่วโมง</td>
                <td><?php echo $base_price_formatted; ?> บาท</td>
            </tr>
            <?php if ($duration_surcharge > 0): ?>
            <tr>
                <td>ค่าบริการชั่วโมงเสริม</td>
                <td>ชั่วโมงเกินจาก 4 ชั่วโมง</td>
                <td><?php echo ($duration - 4); ?> ชั่วโมง</td>
                <td><?php echo $duration_surcharge_formatted; ?> บาท</td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>ค่าเดินทาง</td>
                <td></td>
                <td></td>
                <td>จะแจ้งให้ทราบภายหลัง</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-box">
            <h3>สรุปค่าใช้จ่าย</h3>
            <table class="summary-table">
                <tbody>
                    <tr>
                        <td>ค่าบริการแพ็กเกจ</td>
                        <td class="price-value"><?php echo $base_price_formatted; ?> บาท</td>
                    </tr>
                    <?php if ($duration_surcharge > 0): ?>
                    <tr>
                        <td>ค่าบริการชั่วโมงเสริม</td>
                        <td class="price-value"><?php echo $duration_surcharge_formatted; ?> บาท</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>ค่าเดินทาง</td>
                        <td class="price-value">แจ้งภายหลัง</td>
                    </tr>
                    <tr class="final-total">
                        <td class="total-label">รวมเป็นเงินทั้งสิ้น</td>
                        <td class="total-value"><?php echo $total_price_formatted; ?> บาท</td>
                    </tr>
                    <tr class="final-total">
                        <td class="total-label">
                            <?php echo $payment_label; ?>
                        </td>
                        <td class="total-value"><?php echo $payment_amount_formatted; ?> บาท</td>
                    </tr>
                    <?php if ($remaining_balance > 0): ?>
                    <tr class="final-total">
                        <td class="total-label" style="font-size: 16px; font-weight: normal; color: #555;">ยอดค้างชำระ</td>
                        <td class="total-value" style="font-size: 18px; color: #dc3545;"><?php echo $remaining_balance_formatted; ?> บาท</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="notes">
        <h3>หมายเหตุ</h3>
        <p>
            - ใบเสนอราคานี้เป็นค่าใช้จ่ายโดยประมาณเท่านั้น ราคาจริงอาจมีการเปลี่ยนแปลงขึ้นอยู่กับรายละเอียดเพิ่มเติมและค่าเดินทาง<br>
            - ค่าเดินทางจะถูกคำนวณและแจ้งให้ทราบภายหลัง<br>
            - รายละเอียดเพิ่มเติม: <?php echo $notes; ?>
        </p>
    </div>

    <button onclick="window.print()" class="print-button">พิมพ์ใบเสนอราคา</button>
</div>

</body>
</html>