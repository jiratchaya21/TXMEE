<?php
    include "Datasql.php";
    
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $Cus_id = $_POST['Cus_id'];
        $Service_ID = $_POST['Service_ID'];
        $Date_Book = $_POST['Date_Book'];
        $StartTime = $_POST['StartTime'];
        $Hours = $_POST['Hours'];

        // ตรวจสอบและแปลงค่าให้เป็น Integer 
        $Total_P = (int)$_POST['Total_P'];
        $Selected_P = (int)$_POST['Selected_P'];
        
        $Payment_Status = $_POST['Payment_Status'];
        
        // รับค่าอื่นๆ
        $Location = isset($_POST['Location']) ? $_POST['Location'] : null;
        $Note = isset($_POST['Note']) ? $_POST['Note'] : null;
        $ServiceType = isset($_POST['ServiceType']) ? $_POST['ServiceType'] : "N/A";
        
        // กำหนดสถานะการจองเริ่มต้นสำหรับตาราง Booking
        $Booking_Status = 'รอการยืนยัน';


        // --- Handle studio booking (Service_ID = 1) ---
        if ($Service_ID == 1) {
            
            $isAvailable = true;
            $booked_slots = [];
            
            // ----------------------------------------------------------------------
            // ขั้นตอน 1: ตรวจสอบความพร้อมใช้งาน (ใช้สถานะ 'จองแล้ว' ตรงตาม ENUM)
            // ----------------------------------------------------------------------
            $checkStartTime = new DateTime($StartTime);
            for ($i = 0; $i < $Hours; $i++) {
                $checkTime = $checkStartTime->format('H:i:s');
                $checkDate = $checkStartTime->format('Y-m-d');
                
                // ตรวจสอบเฉพาะสถานะ 'จองแล้ว' เท่านั้น (สถานะ 'ว่าง' ถือว่าจองได้)
                $sql_check = "SELECT COUNT(*) FROM studio_schedule 
                            WHERE booking_date = ? AND start_time = ? AND status = 'จองแล้ว' AND service_id = ?"; 
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ssi", $checkDate, $checkTime, $Service_ID);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $isAvailable = false;
                    $booked_slots[] = "{$checkDate} {$checkTime}";
                    break;
                }
                
                $checkStartTime->modify('+1 hour');
            }
            
            if (!$isAvailable) {
                echo "Transaction Failed: Time slot(s) already booked: " . implode(', ', $booked_slots);
                $conn->close();
                exit();
            }

            // ----------------------------------------------------------------------
            // ขั้นตอน 2: เริ่มต้น Transaction และบันทึกข้อมูล
            // ----------------------------------------------------------------------
            $conn->begin_transaction();
            try {
                // A. INSERT INTO Booking
                $sql = "INSERT INTO Booking (Cus_id, Service_ID, Date_Book, StartTime, Hours, Total_P, Selected_P, Payment_Status, Location, Note, Booking_Status, ServiceType) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param("iissiiisssss", $Cus_id, $Service_ID, $Date_Book, $StartTime, $Hours, $Total_P, $Selected_P, $Payment_Status, $Location, $Note, $Booking_Status, $ServiceType);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error during Booking insertion (Check data types or table schema): " . $stmt->error);
                    }
                    
                    $new_booking_id = $conn->insert_id;
                    $stmt->close();
                    
                    // *** FIX 1: Define interval for time calculations ***
                    $interval = new DateInterval('PT1H');

                    // ----------------------------------------------------------------------
                    // B0. [FIX] ลบแถวสถานะ 'ว่าง' ออกไปก่อน (เพื่อล้างร่องรอยการจองที่ถูกยกเลิก)
                    // ----------------------------------------------------------------------
                    // ใช้ DateTime object ใหม่สำหรับการลบ
                    $deleteStartTime = new DateTime($Date_Book . ' ' . $StartTime);
                    $sql_delete_vacant = "DELETE FROM studio_schedule 
                                          WHERE booking_date = ? AND start_time = ? AND status = 'ว่าง' AND service_id = ?";
                    $stmt_delete_vacant = $conn->prepare($sql_delete_vacant);

                    if (!$stmt_delete_vacant) {
                        throw new Exception("Error preparing delete vacant schedule statement: " . $conn->error);
                    }
                    
                    for ($i = 0; $i < $Hours; $i++) {
                        $delete_time = $deleteStartTime->format('H:i:s');
                        $delete_date = $deleteStartTime->format('Y-m-d');
                        
                        // ใช้ bind_param และ execute เพื่อลบข้อมูล 'ว่าง' ในแต่ละช่วงเวลา
                        $stmt_delete_vacant->bind_param("ssi", $delete_date, $delete_time, $Service_ID);
                        $stmt_delete_vacant->execute(); // ไม่ต้องตรวจสอบผลลัพธ์ เพราะอาจไม่มีแถวที่ตรงกัน
                        
                        $deleteStartTime->add($interval); // เลื่อนไปชั่วโมงถัดไป
                    }
                    $stmt_delete_vacant->close();
                    
                    
                    // B. INSERT INTO studio_schedule
                    // **แก้ไข: กำหนดค่า $status ให้ตรงกับ ENUM 'จองแล้ว' โดยไม่มีช่องว่าง**
                    $status = "จองแล้ว"; 
                    // ใช้ $startTime เดิมที่เริ่มต้นใหม่เพื่อให้แน่ใจว่าการนับเวลาถูกต้อง
                    $startTime = new DateTime($Date_Book . ' ' . $StartTime); 
                    
                    $sql_insert_schedule = "INSERT INTO studio_schedule (booking_date, start_time, status, Booking_ID, service_id) 
                                            VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert_schedule = $conn->prepare($sql_insert_schedule);
                    
                    if (!$stmt_insert_schedule) {
                         throw new Exception("Error preparing schedule statement: " . $conn->error);
                    }
                    
                    for ($i = 0; $i < $Hours; $i++) {
                        $insert_time = $startTime->format('H:i:s');
                        $insert_date = $startTime->format('Y-m-d');
                        
                        // sssii
                        $stmt_insert_schedule->bind_param("sssii", $insert_date, $insert_time, $status, $new_booking_id, $Service_ID);
                        
                        if (!$stmt_insert_schedule->execute()) {
                            throw new Exception("Error executing schedule insertion: " . $stmt_insert_schedule->error);
                        }
                        
                        $startTime->add($interval); // เลื่อนไปชั่วโมงถัดไป
                    }
                    $stmt_insert_schedule->close();

                    // C. COMMIT Transaction
                    $conn->commit();

                    // D. แจ้งเตือน Telegram และ Redirect
                    $telegram_token = "8307605022:AAFFG11BxtZGTtfniNfYulsIa761nuHvKxY"; // ต้องกำหนดค่า
                    $chat_id = "8218184387"; // ต้องกำหนดค่า
                    
                    if (!empty($telegram_token) && !empty($chat_id)) {
                        $message = "**การจองใหม่** \n" .
                                   "ID การจอง: " . $new_booking_id . "\n" .
                                   "บริการ: " . $ServiceType . "\n" .
                                   "วันที่: " . $Date_Book . "\n" .
                                   "จาก: ลูกค้า ID " . $Cus_id . "\n" .
                                   "รายละเอียด: " . $Note;
                        
                        $api_url = "https://api.telegram.org/bot" . $telegram_token . "/sendMessage";
                        $data = [
                            'chat_id' => $chat_id,
                            'text' => $message,
                            'parse_mode' => 'HTML'
                        ];

                        $options = [
                            'http' => [
                                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                                'method'  => 'POST',
                                'content' => http_build_query($data),
                            ],
                        ];
                        $context  = stream_context_create($options);
                        @file_get_contents($api_url, false, $context);
                    }

                    header('Location: Payment.php?booking_id=' . $new_booking_id);
                    exit();

                } else {
                    throw new Exception("Error preparing statement: " . $conn->error);
                }
            } catch (Exception $e) {
                // หากเกิดข้อผิดพลาดใด ๆ ใน Transaction จะยกเลิกการบันทึกทั้งหมด
                $conn->rollback();
                echo "Transaction Failed (Rollback): " . $e->getMessage();
            }
        } else {
            // --- Handle non-studio booking (Service_ID != 1) ---
            $sql = "INSERT INTO Booking (Cus_id, Service_ID, Date_Book, StartTime, Hours, Total_P, Selected_P, Payment_Status, Location, Note, Booking_Status, ServiceType) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("iissiiisssss", $Cus_id, $Service_ID, $Date_Book, $StartTime, $Hours, $Total_P, $Selected_P, $Payment_Status, $Location, $Note, $Booking_Status, $ServiceType);
                
                if ($stmt->execute()) {
                    $new_booking_id = $conn->insert_id;

                    // แจ้งเตือน Telegram และ Redirect
                    $telegram_token = "8307605022:AAFFG11BxtZGTtfniNfYulsIa761nuHvKxY"; // ต้องกำหนดค่า
                    $chat_id = "8218184387"; // ต้องกำหนดค่า

                    if (!empty($telegram_token) && !empty($chat_id)) {
                        $message = "**การจองใหม่** \n" .
                                   "ID การจอง: " . $new_booking_id . "\n" .
                                   "บริการ: " . $ServiceType . "\n" .
                                   "วันที่: " . $Date_Book . "\n" .
                                   "จาก: ลูกค้า ID " . $Cus_id . "\n" .
                                   "รายละเอียด: " . $Note;
                        
                        $api_url = "https://api.telegram.org/bot" . $telegram_token . "/sendMessage";
                        $data = [
                            'chat_id' => $chat_id,
                            'text' => $message,
                            'parse_mode' => 'HTML'
                        ];

                        $options = [
                            'http' => [
                                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                                'method'  => 'POST',
                                'content' => http_build_query($data),
                            ],
                        ];
                        $context  = stream_context_create($options);
                        @file_get_contents($api_url, false, $context);
                    }

                    header('Location: Payment.php?booking_id=' . $new_booking_id);
                    exit();
                } else {
                    echo "Error during insertion: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        }
        
        $conn->close();
    } else {
        echo "Invalid request method.";
    }
?>