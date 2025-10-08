<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - เพิ่มบริการ</title>
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
            margin-right: 40px;
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
        .hidden-field {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="BookAdmin.php" class="back-button" aria-label="กลับหน้าแรก">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
                    <path d="m12 19-7-7 7-7"/>
                    <path d="M19 12H5"/>
                </svg>
            </a>
            <h1>เพิ่มข้อมูลบริการ</h1>
        </div>

        <form action="Service.php" method="post">
            <div class="form-group">
                <label for="serviceType">ประเภทบริการ</label>
                <select id="serviceType" name="ServiceType" required>
                    <option value="">-- เลือกประเภทบริการ --</option>
                    <option value="Studio">Studio</option>
                    <option value="Photobooth">Photobooth</option>
                    <option value="360 Photo">360 Photo</option>
                </select>
            </div>

            <div class="form-group" id="pac-name-group">
                <label for="pacName">ชื่อแพ็กเกจ/ชื่อสตูดิโอ</label>
                <input type="text" id="pacName" name="Pac_name">
            </div>

            <div class="form-group">
                <label for="detailP">รายละเอียดแพ็กเกจ</label>
                <textarea id="detailP" name="Detail_P" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="floatP">ราคาเริ่มต้น (บาท)</label>
                <input type="number" id="floatP" name="Float_P" step="0.01" required>
            </div>
            
            <div class="form-group" id="timeSetupGroup">
                <label for="timeSetup">ระยะเวลาการติดตั้ง (โดยประมาณ)</label>
                <input type="text" id="timeSetup" name="TimeSetup" placeholder="เช่น 1 ชั่วโมง">
            </div>
            
            <div class="form-group" id="serviceTimeGroup">
                <label for="serviceTime">ระยะเวลาให้บริการ</label>
                <input type="text" id="serviceTime" name="Servicetime" placeholder="เช่น 4 ชั่วโมง">
            </div>

            <div class="form-group">
                <label for="space">ข้อกำหนดด้านพื้นที่</label>
                <input type="text" id="space" name="Space" placeholder="เช่น 3x3 เมตร" required>
            </div>

            <button type="submit" class="button">บันทึก</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceTypeSelect = document.getElementById('serviceType');
            const pacNameGroup = document.getElementById('pac-name-group');
            const timeSetupGroup = document.getElementById('timeSetupGroup');
            const serviceTimeGroup = document.getElementById('serviceTimeGroup');
            const timeSetupInput = document.getElementById('timeSetup');
            const serviceTimeInput = document.getElementById('serviceTime');
            
            function toggleFields() {
                const selectedType = serviceTypeSelect.value;
                if (selectedType === 'Photobooth' || selectedType === '360 Photo') {
                    // สำหรับ Onsite Service
                    pacNameGroup.style.display = 'block';
                    pacNameGroup.querySelector('input').setAttribute('required', 'required');
                    timeSetupGroup.style.display = 'block';
                    timeSetupInput.setAttribute('required', 'required');
                    serviceTimeGroup.style.display = 'block';
                    serviceTimeInput.setAttribute('required', 'required');
                } else if (selectedType === 'Studio') {
                    // สำหรับ Studio
                    pacNameGroup.style.display = 'block';
                    pacNameGroup.querySelector('input').setAttribute('required', 'required');
                    // ตั้งค่าให้เป็น optional หรือซ่อน field เหล่านี้ หากไม่จำเป็น
                    timeSetupGroup.style.display = 'block'; // แสดง
                    timeSetupInput.removeAttribute('required'); // ไม่ต้องบังคับกรอก
                    timeSetupInput.value = ''; // ใส่ค่าเริ่มต้น
                    serviceTimeGroup.style.display = 'block'; // แสดง
                    serviceTimeInput.removeAttribute('required'); // ไม่ต้องบังคับกรอก
                    serviceTimeInput.value = ''; // ใส่ค่าเริ่มต้น
                } else {
                    // สำหรับตัวเลือก -- เลือกประเภทบริการ --
                    pacNameGroup.style.display = 'none';
                    pacNameGroup.querySelector('input').removeAttribute('required');
                    timeSetupGroup.style.display = 'none';
                    timeSetupInput.removeAttribute('required');
                    serviceTimeGroup.style.display = 'none';
                    serviceTimeInput.removeAttribute('required');
                }
            }

            serviceTypeSelect.addEventListener('change', toggleFields);
            toggleFields(); // เรียกใช้ครั้งแรกเมื่อหน้าเว็บโหลดเสร็จ
        });
    </script>
</body>
</html>