<?php
    include "Datasql.php";
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['Cus_id'])) {
        header("Location: login.php"); // Redirect to login page if not logged in
        exit();
    }

    $Cus_id = $_SESSION['Cus_id'];
    $unpaid_bookings = [];

    // Fetch bookings with 'รอการชำระเงิน' status for the logged-in user
    $sql = "SELECT Booking_ID, ServiceType, Date_Book, StartTime, Total_P 
            FROM Booking 
            WHERE Cus_id = ? AND Payment_Status = 'รอการชำระเงิน'";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $Cus_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $unpaid_bookings[] = $row;
            }
        }
        $stmt->close();
    }
    $conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการจองที่ยังไม่ได้ชำระเงิน | Xmee Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            max-width: 900px;
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
        .unpaid-list {
            margin-top: 30px;
        }
        .booking-item {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .booking-details {
            flex-grow: 1;
            margin-right: 20px;
        }
        .booking-details p {
            margin: 5px 0;
            font-size: 16px;
        }
        .booking-details p strong {
            color: #007bff;
        }
        .payment-form {
            text-align: right;
        }
        .payment-form input[type="file"] {
            display: block;
            margin-bottom: 10px;
        }
        .payment-form button {
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .payment-form button:hover {
            background-color: #218838;
        }
        .no-bookings {
            text-align: center;
            font-size: 18px;
            color: #6c757d;
            padding: 50px;
        }
        .back-button {
            display: block;
            width: fit-content;
            margin: 20px auto 0;
            padding: 10px 25px;
            background-color: #6c757d;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="Homepage1.php" class="logo">Xmee Studio</a>
    </header>

    <div class="container">
        <h1>รายการจองที่รอการชำระเงิน</h1>

        <?php if (!empty($unpaid_bookings)): ?>
            <div class="unpaid-list">
                <?php foreach ($unpaid_bookings as $booking): ?>
                    <div class="booking-item">
                        <div class="booking-details">
                            <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['Booking_ID']); ?></p>
                            <p><strong>ประเภทบริการ:</strong> <?php echo htmlspecialchars($booking['ServiceType']); ?></p>
                            <p><strong>วันที่:</strong> <?php echo htmlspecialchars($booking['Date_Book']); ?></p>
                            <p><strong>เวลา:</strong> <?php echo htmlspecialchars($booking['StartTime']); ?></p>
                            <p><strong>ยอดรวม:</strong> <?php echo number_format($booking['Total_P']); ?> บาท</p>
                        </div>
                        <div class="payment-form">
                            <form action="upload_slip.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['Booking_ID']); ?>">
                                <input type="file" name="slip_file" accept="image/*" required>
                                <button type="submit">อัปโหลดสลิป</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-bookings">คุณไม่มีรายการจองที่ยังไม่ได้ชำระเงิน</p>
        <?php endif; ?>
        
        <a href="Book_Cus.php" class="back-button">กลับไปหน้ารายการจอง</a>
    </div>

</body>
</html>