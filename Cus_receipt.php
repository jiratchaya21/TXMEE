<?php
error_reporting(E_ALL & ~E_WARNING);
require('fpdf/fpdf.php');
include "Datasql.php";

if (isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];

    // ดึงข้อมูลการจองและลูกค้า
    $sql_booking = "SELECT b.Booking_ID, b.ServiceType, b.Date_Book, b.StartTime, b.Hours, b.Total_P, b.Payment_Status,
                   b.Location,
                   c.F_name, c.L_name
            FROM booking b
            JOIN customer c ON b.Cus_id = c.Cus_id
            WHERE b.Booking_ID = ?";
    $stmt_booking = $conn->prepare($sql_booking);
    $stmt_booking->bind_param("i", $booking_id);
    $stmt_booking->execute();
    $result_booking = $stmt_booking->get_result();
    $booking = $result_booking->fetch_assoc();
    $stmt_booking->close();
    
    // ดึงข้อมูลการชำระเงินล่าสุดจากตาราง payment
    $sql_payment = "SELECT A_Price, PayDate FROM payment WHERE Booking_ID = ? ORDER BY PayDate DESC LIMIT 1";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("i", $booking_id);
    $stmt_payment->execute();
    $result_payment = $stmt_payment->get_result();
    $payment = $result_payment->fetch_assoc();
    $stmt_payment->close();

    if ($booking && $payment) {

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        
        $pdf->AddFont('THSarabunNew','','THSarabunNew.php');
        $pdf->AddFont('THSarabunNew','B','THSarabunNew_b.php');

        $companyName = "XMEE STUDIO";
        $companyOwner = "นายรัฐพล บุญมี";
        $companyAddress = "258 ซ.มิตรภาพ 13 ต.ในเมือง อ.เมือง จ.นครราชสีมา";
        $companyPhone = "093 961 4655";
        $companyID = "3 3099 01602 63 9";

        $amount_for_this_receipt = $payment['A_Price']; // ดึงยอดเงินที่ชำระล่าสุดจากตาราง payment โดยตรง
        $pay_date = $payment['PayDate'];

        // Function to convert number to Thai text
        function convert_to_thai($number) {
            $thai_number = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
            $digit_unit = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
            $str = "";
            $number_str = strval(round($number, 2));
            if (strpos($number_str, '.') !== false) {
                list($baht, $satang) = explode('.', $number_str);
            } else {
                $baht = $number_str;
                $satang = "00";
            }
            
            // Convert baht part
            $len = strlen($baht);
            for ($i = 0; $i < $len; $i++) {
                $digit = $baht[$i];
                $unit_index = $len - $i - 1;
                if ($digit == 0 && $unit_index != 0 && $len > 1) continue; 
                if ($unit_index == 1 && $digit == 2) {
                    $str .= "ยี่";
                } elseif ($unit_index == 1 && $digit == 1) {
                    $str .= "";
                } elseif ($unit_index == 0 && $digit == 1 && $len > 1) {
                    $str .= "เอ็ด";
                } else {
                    $str .= $thai_number[$digit];
                }
                $str .= $digit_unit[$unit_index];
            }
            $str .= "บาท";
            
            // Convert satang part
            $satang_val = intval($satang);
            if ($satang_val > 0) {
                $str .= convert_to_thai_satang($satang) . "สตางค์";
            } else {
                $str .= "ถ้วน";
            }
            
            return $str;
        }

        function convert_to_thai_satang($satang_str) {
            $thai_number = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
            $str = "";
            
            if (strlen($satang_str) == 1) $satang_str .= "0"; 

            $digit1 = $satang_str[0]; 
            $digit2 = $satang_str[1]; 

            if ($digit1 == '2') {
                $str .= "ยี่";
            } elseif ($digit1 == '1') {
                // Do nothing for 'หนึ่งสิบ'
            } elseif ($digit1 != '0') {
                $str .= $thai_number[$digit1];
            }
            if ($digit1 != '0') {
                 $str .= "สิบ";
            }
            
            if ($digit2 == '1' && $digit1 != '0') { 
                $str .= "เอ็ด";
            } elseif ($digit2 != '0') {
                $str .= $thai_number[$digit2];
            }
            return $str;
        }

        // --- Header Section ---
        $pdf->SetFont('THSarabunNew','',14);
        $pdf->Cell(0, 5, iconv('UTF-8', 'TIS-620', 'นามผู้จัดทำ'), 0, 1);
        $pdf->Cell(0, 5, iconv('UTF-8', 'TIS-620', $companyOwner), 0, 1);
        $pdf->Cell(0, 5, iconv('UTF-8', 'TIS-620', $companyAddress), 0, 1);
        $pdf->Cell(0, 5, iconv('UTF-8', 'TIS-620', 'โทรศัพท์: ' . $companyPhone), 0, 1);
        $pdf->Cell(0, 5, iconv('UTF-8', 'TIS-620', 'หมายเลขบัตรประชาชน: ' . $companyID), 0, 1);
        $pdf->Ln(5);

        $pdf->SetY(10); 
        $pdf->SetX(100); 
        $pdf->SetFont('THSarabunNew','B',24);
        $pdf->Cell(0, 10, iconv('UTF-8', 'TIS-620', $companyName), 0, 1, 'R');
        $pdf->SetFont('THSarabunNew','',18);
        $pdf->Cell(0, 8, iconv('UTF-8', 'TIS-620', 'ใบเสร็จรับเงิน'), 0, 1, 'R');
        $pdf->Cell(0, 8, iconv('UTF-8', 'TIS-620', 'Receipt'), 0, 1, 'R');
        $pdf->Ln(10);

        // --- Customer and Date Section ---
        $pdf->SetFont('THSarabunNew','',14);
        $pdf->Cell(95, 8, iconv('UTF-8', 'TIS-620', 'ชื่อลูกค้า / Customer Name'), 'B', 0);
        $pdf->Cell(95, 8, iconv('UTF-8', 'TIS-620', 'ชื่องาน / Project Name'), 'B', 1, 'R');
        $pdf->Cell(95, 8, iconv('UTF-8', 'TIS-620', 'คุณ ' . $booking['F_name'] . ' ' . $booking['L_name']), 0, 0);
        $pdf->Cell(95, 8, iconv('UTF-8', 'TIS-620', $booking['ServiceType'] . ' (วันที่ ' . $booking['Date_Book'] . ')'), 0, 1, 'R');

        $pdf->Cell(95, 8, iconv('UTF-8', 'TIS-620', 'ที่อยู่: ' . $booking['Location']), 'B', 0);
        $pdf->Cell(95, 8, iconv('UTF-8', 'TIS-620', 'วันที่ / Date'), 'B', 1, 'R');
        $pdf->Cell(95, 8, '', 0, 0);
        $pdf->Cell(95, 8, iconv('UTF-8', 'TIS-620', date('d/m/Y', strtotime($pay_date))), 0, 1, 'R');
        $pdf->Ln(10);

        // --- Table Section ---
        $pdf->SetFont('THSarabunNew','B',16);
        $pdf->Cell(15, 10, iconv('UTF-8', 'TIS-620', 'ลำดับที่'), 1, 0, 'C');
        $pdf->Cell(75, 10, iconv('UTF-8', 'TIS-620', 'รายการ'), 1, 0, 'C');
        $pdf->Cell(20, 10, iconv('UTF-8', 'TIS-620', 'จำนวน'), 1, 0, 'C');
        $pdf->Cell(40, 10, iconv('UTF-8', 'TIS-620', 'ราคา/หน่วย'), 1, 0, 'C');
        $pdf->Cell(40, 10, iconv('UTF-8', 'TIS-620', 'ราคารวม'), 1, 1, 'C');

        // Table Rows
        $pdf->SetFont('THSarabunNew','',14);

        $row_counter = 1;

        // ดึงยอดชำระรวมทั้งหมดเพื่อใช้ใน logic
        $sql_total_paid = "SELECT SUM(A_Price) as total_paid FROM payment WHERE Booking_ID = ?";
        $stmt_total_paid = $conn->prepare($sql_total_paid);
        $stmt_total_paid->bind_param("i", $booking_id);
        $stmt_total_paid->execute();
        $result_total_paid = $stmt_total_paid->get_result();
        $total_paid_data = $result_total_paid->fetch_assoc();
        $stmt_total_paid->close();
        
        $label = ($amount_for_this_receipt == $booking['Total_P']) ? 'ยอดชำระเต็มจำนวน' : 'ยอดชำระมัดจำ / คงเหลือ';
        if ($total_paid_data['total_paid'] == $booking['Total_P'] && $amount_for_this_receipt != $booking['Total_P']) {
            $label = 'ยอดชำระคงเหลือ';
        }

        $pdf->Cell(15, 10, iconv('UTF-8', 'TIS-620', $row_counter++), 1, 0, 'C');
        $pdf->Cell(75, 10, iconv('UTF-8', 'TIS-620', $label), 1, 0);
        $pdf->Cell(20, 10, iconv('UTF-8', 'TIS-620', '1'), 1, 0, 'C');
        $pdf->Cell(40, 10, iconv('UTF-8', 'TIS-620', number_format($amount_for_this_receipt, 2)), 1, 0, 'R');
        $pdf->Cell(40, 10, iconv('UTF-8', 'TIS-620', number_format($amount_for_this_receipt, 2)), 1, 1, 'R');

        $pdf->Cell(190, 10, '', 1, 1); 
        
        // --- Summary Section ---
        $pdf->Cell(110, 8, iconv('UTF-8', 'TIS-620', '-' . convert_to_thai($amount_for_this_receipt) . '-'), 1, 0, 'C');
        $pdf->Cell(40, 8, iconv('UTF-8', 'TIS-620', 'จำนวนเงินรวม'), 1, 0, 'L');
        $pdf->Cell(40, 8, iconv('UTF-8', 'TIS-620', number_format($amount_for_this_receipt, 2) . '.-'), 1, 1, 'R');

        $pdf->Cell(110, 8, '', 0, 0);
        $pdf->Cell(40, 8, iconv('UTF-8', 'TIS-620', 'หัก Vat 3%'), 1, 0, 'L');
        $pdf->Cell(40, 8, iconv('UTF-8', 'TIS-620', '0.00.-'), 1, 1, 'R');

        $pdf->Cell(110, 8, '', 0, 0);
        $pdf->Cell(40, 8, iconv('UTF-8', 'TIS-620', 'จำนวนเงินสุทธิ'), 1, 0, 'L');
        $pdf->Cell(40, 8, iconv('UTF-8', 'TIS-620', number_format($amount_for_this_receipt, 2) . '.-'), 1, 1, 'R');
        $pdf->Ln(20);

        // --- Signature Section ---
        $pdf->Cell(95, 5, iconv('UTF-8', 'TIS-620', 'ผู้จัดทำ'), 'T', 0, 'C');
        $pdf->Cell(95, 5, iconv('UTF-8', 'TIS-620', 'ผู้อนุมัติ'), 'T', 1, 'C');
        $pdf->Cell(95, 5, iconv('UTF-8', 'TIS-620', '(นายรัฐพล บุญมี)'), 0, 0, 'C');
        $pdf->Cell(95, 5, '', 0, 1, 'C');

        $pdf->Output();
    } else {
        die("ไม่พบข้อมูลการจองหรือการชำระเงินที่เกี่ยวข้อง");
    }
} else {
    die("ไม่พบหมายเลขการจอง");
}
$conn->close();
?>