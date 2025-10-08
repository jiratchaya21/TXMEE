<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - เพิ่มสตูดิโอ</title>
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
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
            margin-right: 40px; /* เพื่อชดเชยพื้นที่ของปุ่มย้อนกลับ */
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.2);
        }

        .button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ส่วนหัวของฟอร์ม ประกอบด้วยปุ่มย้อนกลับและหัวข้อ -->
        <div class="header">
            <!-- ปุ่มลูกศรย้อนกลับที่ลิงก์ไปยังหน้า BookAdmin -->
            <a href="BookAdmin.php" class="back-button" aria-label="กลับหน้าแรก">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                    <path d="m12 19-7-7 7-7"/>
                    <path d="M19 12H5"/>
                </svg>
            </a>
            <h1>เพิ่มข้อมูลสตูดิโอ</h1>
        </div>

        <form action="Studio.php" method="post">
            <!-- ฟิลด์รหัสสตูดิโอ (Stu_ID) จะถูกสร้างโดยระบบ ไม่ต้องแสดงในฟอร์ม -->
            
            <div class="form-group">
                <label for="desStu">คำอธิบาย</label>
                <textarea id="desStu" name="Des_Stu" rows="4" placeholder="รายละเอียดเกี่ยวกับสตูดิโอทั้งหมด" required></textarea>
            </div>

            <div class="form-group">
                <label for="priceStu">ราคาต่อชั่วโมง (บาท)</label>
                <input type="text" id="priceStu" name="Price_Stu" required>
            </div>
            
            <div class="form-group">
                <label for="statusStu">สถานะ</label>
                <select id="statusStu" name="Status_Stu" required>
                    <option value="ว่าง">ว่าง</option>
                    <option value="ไม่ว่าง">ไม่ว่าง</option>
                    <option value="อยู่ระหว่างปรับปรุง">อยู่ระหว่างปรับปรุง</option>
                </select>
            </div>

            <button type="submit" class="button">บันทึก</button>
        </form>
    </div>
</body>
</html>
