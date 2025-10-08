<?php
    include "Datasql.php";
    session_start();

    // Check for booking ID in the URL
    if (!isset($_GET['booking_id'])) {
        die("Error: Booking ID not found.");
    }

    $booking_id = $_GET['booking_id'];
    $booking_data = null;

    // Fetch booking details from the database
    // แก้ไขคำสั่ง SQL เพื่อดึงคอลัมน์ Selected_P และ Service_ID
    $sql = "SELECT Booking_ID, Service_ID, ServiceType, Date_Book, StartTime, Hours, Total_P, Selected_P FROM Booking WHERE Booking_ID = ?";
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
    $conn->close();

    // กำหนดยอดเงินที่ต้องชำระและประเภทการชำระเงิน
    $amount_to_pay = $booking_data['Selected_P'];
    $payment_option_value = ($amount_to_pay < $booking_data['Total_P']) ? 'มัดจำ' : 'เต็มจำนวน';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการชำระเงิน</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f4f7f6;
        }
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .payment-container h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .payment-info {
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .payment-info h2 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .price-display {
            font-size: 40px;
            font-weight: 700;
            color: #007bff;
            margin: 10px 0;
        }
        .qr-code {
            width: 200px;
            height: 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .booking-details {
            text-align: left;
            margin-bottom: 30px;
        }
        .booking-details .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ccc;
        }
        .booking-details .detail-item:last-child {
            border-bottom: none;
        }
        .detail-item .label {
            font-weight: 600;
            color: #555;
        }
        .upload-section {
            border-top: 2px solid #007bff;
            padding-top: 20px;
        }
        .upload-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        .btn-upload {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-upload:hover {
            background-color: #218838;
        }
        .modal-body .modal-text {
            color: #2c3e50;
        }
        .modal-body .modal-icon {
            font-size: 50px;
            color: #28a745;
        }
        .payment-summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            text-align: left;
        }
        .payment-summary h4 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        .payment-summary .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .summary-item .label {
            color: #555;
        }
        .summary-item .value {
            font-weight: 500;
        }
        .summary-item.total-to-pay .value {
            font-weight: 700;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1>รายละเอียดการชำระเงิน</h1>
        <div class="payment-info">
            <h2>ยอดที่ต้องชำระ</h2>
            <p class="price-display"><?php echo number_format($amount_to_pay, 2); ?> บาท</p>
        </div>

        <div class="booking-details">
            <h2 class="text-center">รายละเอียดการจอง</h2>
            <div class="detail-item">
                <span class="label">Booking ID:</span>
                <span class="value"><?php echo htmlspecialchars($booking_data['Booking_ID']); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">บริการ:</span>
                <span class="value"><?php echo htmlspecialchars($booking_data['ServiceType']); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">วันที่:</span>
                <span class="value"><?php echo htmlspecialchars($booking_data['Date_Book']); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">เวลา:</span>
                <span class="value"><?php echo htmlspecialchars(substr($booking_data['StartTime'], 0, 5)); ?> น.</span>
            </div>
            <div class="detail-item">
                <span class="label">จำนวนชั่วโมง:</span>
                <span class="value"><?php echo htmlspecialchars($booking_data['Hours']); ?> ชั่วโมง</span>
            </div>
        </div>

        <div class="upload-section">
            <h3>อัปโหลดหลักฐานการชำระเงิน</h3>
            <img class="qr-code" src="QR.jpg" alt="QR Code">
            <form action="upload_slip.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_data['Booking_ID']); ?>">
                <input type="hidden" name="payment_option" value="<?php echo htmlspecialchars($payment_option_value); ?>">
                <input type="hidden" name="amount_paid" value="<?php echo htmlspecialchars($amount_to_pay); ?>">
                <div class="mb-3">
                    <label for="slip_file" class="form-label"><i class="fas fa-camera"></i> แนบสลิปเพื่อยืนยันการชำระเงิน</label>
                    <input class="form-control" type="file" id="slip_file" name="slip_file" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-upload"><i class="fas fa-paper-plane"></i> ส่งสลิป</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>