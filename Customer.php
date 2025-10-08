<?php
    include 'Datasql.php';
    session_start(); 
    $sql_customer = "SELECT Cus_id,F_name,L_name,Email_C,Phone FROM customer"
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - รายชื่อลูกค้า</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            width: 100%;
            position: relative;
        }

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
        
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e9f5ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ส่วนหัวของหน้า ประกอบด้วยปุ่มย้อนกลับและหัวข้อ -->
        <div class="header">
            <!-- ปุ่มลูกศรย้อนกลับที่ลิงก์ไปยังหน้า BookAdmin -->
            <a href="BookAdmin.php" class="back-button" aria-label="กลับหน้าแรก">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                    <path d="m12 19-7-7 7-7"/>
                    <path d="M19 12H5"/>
                </svg>
            </a>
            <h1>รายชื่อลูกค้า</h1>
        </div>

        <!-- ตารางแสดงข้อมูลลูกค้า -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <table class="table table-solid">
                        <tr> 
                            <th>ลำดับ</th>
                            <th>ชื่อ</th>
                            <th>นามสกุล</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทร</th>
                        </tr>
                        <?php
                            $sql = "select * from customer";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>
                                    <td>'.$row["Cus_id"].'</td>
                                    <td>'.$row["F_name"].'</td>
                                    <td>'.$row["L_name"].'</td>
                                    <td>'.$row["Email_C"].'</td>
                                    <td>'.$row["Phone"].'</td>
                                </tr>';
                            }
                        ?>
                    </table>
                </div>
            </div>
        </div>

    </div>
    
</body>
</html>
