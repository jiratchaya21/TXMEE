<?php
header('Content-Type: application/json');
include "Datasql.php";

$response = ['available' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data from $_POST
    $date = isset($_POST['date']) ? $_POST['date'] : '';
    $time = isset($_POST['time']) ? $_POST['time'] : '';
    $hours = isset($_POST['hours']) ? (int)$_POST['hours'] : 0;

    if (empty($date) || empty($time) || $hours <= 0) {
        $response['message'] = 'Missing required data.';
        echo json_encode($response);
        exit;
    }

    try {
        // Calculate the start and end time of the proposed booking
        $start_datetime = new DateTime($date . ' ' . $time);
        $end_datetime = clone $start_datetime;
        $end_datetime->modify("+{$hours} hours");
        
        $new_start_time = $start_datetime->format('H:i:s');
        $new_end_time = $end_datetime->format('H:i:s');

        // Universal Overlap Check: (Existing Start < New End) AND (Existing End > New Start)
        $sql = "SELECT COUNT(*) AS count
                FROM Booking
                WHERE Date_Book = ?
                  AND Booking_Status != 'ยกเลิกแล้ว'
                  AND (\r\n
                    StartTime < ?   /* Existing Start is before New End */
                    AND ADDTIME(StartTime, SEC_TO_TIME(Hours * 3600)) > ?  /* Existing End is after New Start */
                  )";

        $stmt = $conn->prepare($sql);

        // *** 1. ตรวจสอบ Prepare Error ***
        if (!$stmt) {
             throw new Exception("SQL Prepare failed: " . $conn->error);
        }
        
        // Bindings: $date (s), $new_end_time (s), $new_start_time (s)
        if (!$stmt->bind_param("sss", $date, $new_end_time, $new_start_time)) {
            // *** 2. ตรวจสอบ Bind Error ***
            throw new Exception("SQL Bind failed: " . $stmt->error);
        }
        
        $count = 0;
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $count = $row['count'];
            }
        } else {
             // *** 3. ตรวจสอบ Execute Error ***
            throw new Exception("SQL execution failed: " . $stmt->error);
        }
        $stmt->close();

        if ($count == 0) {
            $response['available'] = true;
            $response['message'] = 'Time slot is available.';
        } else {
            $response['message'] = 'This time slot is already booked.';
        }

    } catch (Exception $e) {
        // ข้อความนี้ควรแสดง Error ที่ชัดเจนจาก MySQL
        $response['message'] = 'เกิดข้อผิดพลาดในการตรวจสอบเวลาว่าง: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'เมธอดไม่ถูกต้อง';
}

$conn->close();
echo json_encode($response);
?>