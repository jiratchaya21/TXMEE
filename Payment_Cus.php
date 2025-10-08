<?php
    include "Datasql.php";
    session_start();

    // Check for booking ID and payment type in the URL
    if (!isset($_GET['booking_id']) || !isset($_GET['type'])) {
        die("Error: Invalid request.");
    }

    $booking_id = $_GET['booking_id'];
    $payment_type = $_GET['type'];
    $booking_data = null;

    // Fetch booking details from the database
    $sql = "SELECT Booking_ID, ServiceType, Date_Book, Total_P FROM Booking WHERE Booking_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
    } else {
        die("Error: Booking not found.");
    }
    $stmt->close();
    // New code starts here
    $service_type = $booking_data['ServiceType'];
    $deposit_amount = 0;
    $remaining_balance = 0;
    $total_to_pay = 0;

    if ($payment_type === 'deposit' && $service_type === 'SayCheeze') {
        $deposit_amount = 5000;
        $total_to_pay = $deposit_amount;
        $remaining_balance = $booking_data['Total_P'] - $deposit_amount;
    } elseif ($payment_type === 'full') {
        $total_to_pay = $booking_data['Total_P'];
        $deposit_amount = 0;
        $remaining_balance = 0;
    } else {
        die("Error: Invalid payment type or service does not support this payment option.");
    }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - Xmee Studio</title>
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
    <h2>ชำระเงิน</h2>
    <div class="summary-box">
        <div class="summary-item">
            <span class="label">หมายเลขการจอง:</span>
            <span class="value"><?php echo htmlspecialchars($booking_data['Booking_ID']); ?></span>
        </div>
        <div class="summary-item">
            <span class="label">ยอดรวมค่าบริการ:</span>
            <span class="value"><?php echo number_format($booking_data['Total_P'], 2); ?> บาท</span>
        </div>
        <?php if ($payment_type === 'deposit'): ?>
        <div class="summary-item">
            <span class="label">ยอดเงินคงเหลือ:</span>
            <span class="value"><?php echo number_format($remaining_balance, 2); ?> บาท</span>
        </div>
        <?php endif; ?>
        <div class="summary-item total-to-pay">
            <span class="label">ยอดที่ต้องชำระ:</span>
            <span class="value"><?php echo number_format($total_to_pay, 2); ?> บาท</span>
        </div>
    </div>

    <div class="upload-section">
        <h3>อัปโหลดหลักฐานการชำระเงิน</h3>
        <p>กรุณาโอนเงินตามยอดที่ต้องชำระด้านบน</p>
        
        <form action="upload_slip.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_data['Booking_ID']); ?>">
            <input type="hidden" name="payment_option" value="<?php echo htmlspecialchars($payment_type === 'deposit' ? 'มัดจำ' : 'เต็มจำนวน'); ?>">
            
            <input type="hidden" name="amount_paid" value="<?php echo htmlspecialchars($total_to_pay); ?>">

            <div class="mb-3">
                <label for="slip_file" class="form-label"><i class="fas fa-camera"></i> แนบสลิปเพื่อยืนยันการชำระเงิน</label>
                <input class="form-control" type="file" id="slip_file" name="slip_file" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-upload"><i class="fas fa-paper-plane"></i> ส่งสลิป</button>
        </form>
    </div>
    
    <a href="Book_Cus.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> กลับไปหน้ารายการจอง</a>
</div>

</body>
</html>