<?php
// เริ่มคำสั่ง Export ไฟล์ PDF
require_once __DIR__ . '/vendor/autoload.php';

$defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'fontDir' => array_merge($fontDirs, [
        __DIR__ . '/tmp',
    ]),
    'fontdata' => $fontData + [
        'sarabun' => [
            'R' => 'THSarabunNew.ttf',
            'I' => 'THSarabunNew Italic.ttf',
            'B' => 'THSarabunNew Bold.ttf',
            'BI' => 'THSarabunNew BoldItalic.ttf'
        ]
    ],
    'default_font' => 'sarabun'
]);
 
// ตั้งค่าฟอนต์
$mpdf->SetFont('sarabun','',14);
ob_start();

// เช็คว่ามีข้อมูลช่วงวันที่ส่งมาหรือไม่
if (isset($_GET['report_start_date']) && isset($_GET['report_end_date'])) {
    $start_date = $_GET['report_start_date'];
    $end_date = $_GET['report_end_date'];
    
    // ดึงข้อมูลการจองตามช่วงวันที่
    include 'Datasql.php';
    $service_counts = [];
    $service_percentages = [];
    
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
    <title>Booking Report</title>
    <style>
        body { font-family: 'sarabun', sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; color: #1a73e8; }
        h3 { color: #555; }
    </style>
</head>
<body>
    <h2>รายงานการจอง</h2>
    <h3>สรุปรายงานตามประเภทบริการ (<?php echo htmlspecialchars($start_date); ?> ถึง <?php echo htmlspecialchars($end_date); ?>)</h3>
    <?php if (!empty($service_percentages)): ?>
        <table>
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
    <?php else: ?>
        <p>ไม่พบข้อมูลการจองในช่วงวันที่ที่เลือก</p>
    <?php endif; ?>
</body>
</html>

<?php
    $html = ob_get_clean();
    $mpdf->WriteHTML($html);
    $mpdf->Output('Report.pdf', 'I');
?>