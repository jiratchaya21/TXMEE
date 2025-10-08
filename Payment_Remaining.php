<?php
    include "Datasql.php";
    session_start();

    // ตรวจสอบว่ามี booking_id ส่งมาใน URL หรือไม่
    if (!isset($_GET['booking_id'])) {
        die("Error: Invalid request. No booking ID found.");
    }

    $booking_id = $_GET['booking_id'];
    $booking_data = null;

    // ดึงข้อมูลการจองจากฐานข้อมูล
    // แก้ไขเงื่อนไขให้ยืดหยุ่นขึ้นเพื่อรองรับสถานะ 'รอดำเนินการ'
    $sql = "SELECT Booking_ID, Cus_id, Total_P, Payment_Status, ServiceType 
            FROM Booking 
            WHERE Booking_ID = ? AND (Payment_Status = 'ชำระเงินมัดจำแล้ว' OR Payment_Status = 'รอยืนยัน')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
    } else {
        die("Error: Booking not found or payment status is incorrect. This page is for remaining balance payment.");
    }
    $stmt->close();
    
    // คำนวณยอดเงินที่ต้องชำระ
    $total_cost = $booking_data['Total_P'];
    $deposit_paid = 5000; // ยอดมัดจำคงที่ 5,000 บาท
    $remaining_balance = $total_cost - $deposit_paid;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระยอดคงเหลือ - Xmee Studio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f0f2f5;
            padding: 20px;
        }
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .summary-box {
            background-color: #f9f9f9;
            border: 1px solid #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-item .label {
            color: #6c757d;
        }
        .total-to-pay {
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
        }
        .upload-section {
            text-align: center;
            margin-top: 30px;
        }
        .qr-code {
            max-width: 250px;
            height: auto;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
        }
        .btn-upload {
            background-color: #2c3e50;
            color: #fff;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-upload:hover {
            background-color: #34495e;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <h2>ชำระยอดคงเหลือ</h2>
    <div class="summary-box">
        <div class="summary-item">
            <span class="label">หมายเลขการจอง:</span>
            <span class="value"><?php echo htmlspecialchars($booking_data['Booking_ID']); ?></span>
        </div>
        <div class="summary-item">
            <span class="label">ยอดรวมค่าบริการ:</span>
            <span class="value"><?php echo number_format($total_cost, 2); ?> บาท</span>
        </div>
        <div class="summary-item">
            <span class="label">ยอดเงินมัดจำที่ชำระแล้ว:</span>
            <span class="value"><?php echo number_format($deposit_paid, 2); ?> บาท</span>
        </div>
        <div class="summary-item total-to-pay">
            <span class="label">ยอดเงินคงเหลือที่ต้องชำระ:</span>
            <span class="value"><?php echo number_format($remaining_balance, 2); ?> บาท</span>
        </div>
    </div>
    
    <div class="upload-section">
        <h3>อัปโหลดหลักฐานการชำระเงิน</h3>
        <p>กรุณาโอนเงินตามยอดคงเหลือด้านบน</p>

        <form action="upload_slip.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_data['Booking_ID']); ?>">
            <input type="hidden" name="payment_option" value="ยอดคงเหลือ">
            
            <input type="hidden" name="amount_paid" value="<?php echo htmlspecialchars($remaining_balance); ?>">

            <img class="qr-code" src="QR.jpg" alt="QR Code">
            <div class="mb-3">
                <label for="slip_file" class="form-label">
                    <i class="fas fa-camera"></i> แนบสลิปเพื่อยืนยันการชำระเงิน
                </label>
                <input class="form-control" type="file" id="slip_file" name="slip_file" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-upload">
                <i class="fas fa-paper-plane"></i> ส่งสลิป
            </button>
        </form>
    </div>
    
    <a href="Book_Cus.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> กลับไปหน้ารายการจอง</a>
</div>

</body>
</html>