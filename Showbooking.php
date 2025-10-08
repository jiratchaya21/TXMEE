<?php
    // คำสั่งการ Export ไฟล์เป็น PDF ในไฟล์นี้จะถูกลบออกไป
    include 'Datasql.php';

    $service_percentages = [];
    $service_counts = [];

    if (isset($_GET['report_start_date']) && isset($_GET['report_end_date'])) {
        $start_date = $_GET['report_start_date'];
        $end_date = $_GET['report_end_date'];
        
        $query = "SELECT ServiceType FROM booking WHERE Date_Book BETWEEN '$start_date' AND '$end_date'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $total_bookings = $result->num_rows;
            while($row = $result->fetch_assoc()) {
                $service_type = $row['ServiceType'];
                if (!isset($service_counts[$service_type])) {
                    $service_counts[$service_type] = 0;
                }
                $service_counts[$service_type]++;
            }

            foreach ($service_counts as $type => $count) {
                $service_percentages[$type] = ($count / $total_bookings) * 100;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowBooking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .main-content {
            width: 100%;
            max-width: 900px;
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* CSS สำหรับปุ่มย้อนกลับ */
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .back-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #e9ecef;
            border-radius: 50%;
            text-decoration: none;
            color: #495057;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .back-button:hover {
            background-color: #dee2e6;
            transform: scale(1.05);
        }
        
        .back-button svg {
            width: 24px;
            height: 24px;
        }
        
        h1 {
            flex-grow: 1;
            text-align: center;
            color: #333;
            margin: 0;
            margin-right: 40px;
            font-size: 24px;
        }

        h3 {
            color: #1a73e8;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }

        .report-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .report-form .form-label {
            font-weight: bold;
            color: #555;
            margin-right: 5px;
            min-width: 30px;
        }

        input[type="date"] {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input[type="date"]:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 5px rgba(26, 115, 232, 0.3);
        }
        
        .btn-primary {
            background-color: #1a73e8;
            border-color: #1a73e8;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #145cb9;
            border-color: #145cb9;
        }
        
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }

        .booking-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .booking-table th, .booking-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .booking-table thead {
            background-color: #1a73e8;
            color: #fff;
        }

        .booking-table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .booking-table tbody tr:hover {
            background-color: #e9e9e9;
        }

        .report-results {
            padding: 15px;
            border: 1px dashed #ccc;
            border-radius: 8px;
        }
        
        p {
            color: #666;
            text-align: center;
        }

        .no-data-message {
            color: #d9534f !important;
            font-weight: bold;
            padding: 20px;
            background-color: #fdf5f5;
            border: 1px solid #f2dede;
            border-radius: 8px;
            margin-top: 20px;
        }

        .export-button-container {
            margin-top: 30px;
            text-align: center;
        }
        
        .btn-export {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .btn-export:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="dashboard-container">
            <div id="reports" class="tab-content">
                <div class="header">
                    <a href="BookAdmin.php" class="back-button" aria-label="กลับหน้าหลัก">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                            <path d="m12 19-7-7 7-7"/>
                            <path d="M19 12H5"/>
                        </svg>
                    </a>
                    <h1>รายงานการจอง</h1>
                </div>

                <div class="report-form">
                    <form action="" method="GET" class="d-flex align-items-center">
                        <label for="report_start_date" class="form-label me-2">จาก:</label>
                        <input type="date" id="report_start_date" name="report_start_date" required class="me-3" value="<?php echo isset($_GET['report_start_date']) ? htmlspecialchars($_GET['report_start_date']) : ''; ?>">
                        <label for="report_end_date" class="form-label me-2">ถึง:</label>
                        <input type="date" id="report_end_date" name="report_end_date" required class="me-3" value="<?php echo isset($_GET['report_end_date']) ? htmlspecialchars($_GET['report_end_date']) : ''; ?>">
                        <button type="submit" class="btn btn-primary">สร้างรายงาน</button>
                    </form>
                </div>

                <?php if (isset($_GET['report_start_date']) && isset($_GET['report_end_date'])): ?>
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
                        </div>
                    <?php else: ?>
                        <p class="no-data-message">ไม่พบข้อมูลการจองในช่วงวันที่ที่เลือก</p>
                    <?php endif; ?>
                    
                    <div class="export-button-container">
                        <a href="generate-report.php?report_start_date=<?php echo htmlspecialchars($_GET['report_start_date']); ?>&report_end_date=<?php echo htmlspecialchars($_GET['report_end_date']); ?>" class="btn btn-export">Export PDF</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>