<?php
require('fpdf.php');
include 'ini.php';
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['license_id'])) {
    die("جلسة غير صالحة");
}

if (!isset($_POST['csv_data'], $_POST['card_design_path'], $_POST['username_x'], $_POST['username_y'], $_POST['password_x'], $_POST['password_y'], $_POST['font_size'], $_POST['cards_layout'])) {
    die("بيانات غير كافية.");
}

// تعريف متغيرات الخط واللون في بداية الملف
$font_size = intval($_POST['font_size']);
$font_color = $_POST['font_color'] ?? '#ffffff';

// تحويل لون الخط من Hex إلى RGB
$font_color = sscanf($font_color, "#%02x%02x%02x");
$text_color = array($font_color[0], $font_color[1], $font_color[2]);

$csv_data_encoded = $_POST['csv_data'];
$csv_data = base64_decode($csv_data_encoded);
$temp_csv = tempnam(sys_get_temp_dir(), 'csv');
file_put_contents($temp_csv, $csv_data);
$arr = array();
$file = fopen($temp_csv, 'r');
// تجاوز السطر الأول (العناوين)
fgetcsv($file);
while (($row = fgetcsv($file)) !== FALSE) {
    if (empty($row[0])) continue;
    $explode = explode(';', $row[0]);
    if (empty($explode[1])) continue;
    $username = str_replace('"', '', $explode[1]);
    $password = str_replace('"', '', $explode[2]);
    $arr[] = array('username' => $username, 'password' => $password);
}
fclose($file);
unlink($temp_csv);
$card_design_path = $_POST['card_design_path'];
list($imgWidth, $imgHeight) = getimagesize($card_design_path);
if (!$imgWidth || !$imgHeight) {
    die("لا يمكن قراءة أبعاد الصورة.");
}
$username_x = floatval($_POST['username_x']);
$username_y = floatval($_POST['username_y']);
$password_x = floatval($_POST['password_x']);
$password_y = floatval($_POST['password_y']);
$conversion = 0.264583;
$username_x_mm = $username_x * $conversion;
$username_y_mm = $username_y * $conversion;
$password_x_mm = $password_x * $conversion;
$password_y_mm = $password_y * $conversion;
$cards_layout = intval($_POST['cards_layout']);

$layouts = [
    10 => [5, 2, 1, 1, 104, 58.4],
    21 => [7, 3, 1, 1, 69, 41.4],
    24 => [8, 3, 1, 1, 69, 36.1],
    40 => [10, 4, 1, 1, 51.5, 28.7],
];
[$rows, $cols, $margin, $spacing, $cardWidth, $cardHeight] = $layouts[$cards_layout] ?? $layouts[10];

$pageWidth = 210;
$pageHeight = 297;
$totalWidthUsed = ($cardWidth * $cols) + ($spacing * ($cols - 1));
$totalHeightUsed = ($cardHeight * $rows) + ($spacing * ($rows - 1));
$margin_left = ($pageWidth - $totalWidthUsed) / 2;
$margin_top = ($pageHeight - $totalHeightUsed) / 2;

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(false);
$cardsPerPage = $rows * $cols;
$totalPages = ceil(count($arr) / $cardsPerPage);
$cardIndex = 0;

for ($page = 0; $page < $totalPages; $page++) {
    $pdf->AddPage();
    for ($row = 0; $row < $rows; $row++) {
        for ($col = 0; $col < $cols; $col++) {
            if ($cardIndex >= count($arr)) break;
            $x = $margin_left + ($col * ($cardWidth + $spacing));
            $y = $margin_top + ($row * ($cardHeight + $spacing));
            $pdf->Image($card_design_path, $x, $y, $cardWidth, $cardHeight);
            $fontSize = $font_size * ($cards_layout >= 21 ? 0.8 : 1);
            $pdf->SetFont('Arial', 'B', $fontSize);
            
            // تطبيق لون الخط
            $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);

            $scaleX = $cardWidth / $imgWidth;
            $scaleY = $cardHeight / $imgHeight;
            $text_x = $x + ($username_x * $scaleX);
            $text_y = $y + ($username_y * $scaleY);
            $pass_x = $x + ($password_x * $scaleX);
            $pass_y = $y + ($password_y * $scaleY);
            $pdf->SetXY($text_x, $text_y);
            $pdf->Cell(($cardWidth * 0.2), ($cardHeight * 0.1), strtoupper($arr[$cardIndex]['username']), 0, 0, 'C');
            
            $pdf->SetXY($pass_x, $pass_y);
            $pdf->Cell(($cardWidth * 0.2), ($cardHeight * 0.1), strtoupper($arr[$cardIndex]['password']), 0, 0, 'C');
            $cardIndex++;
        }
    }
}

$cards_count = count($arr);
// التحقق من عدد الكروت المتبقية في الترخيص
$stmt = $pdo->prepare("SELECT (total_cards - used_cards) as remaining FROM licenses WHERE id = ? AND status = 'active'");
$stmt->execute([$_SESSION['license_id']]);
$remaining = $stmt->fetchColumn();

if ($remaining < $cards_count) {
    die("عدد الكروت المطلوبة ({$cards_count}) يتجاوز العدد المتبقي في الترخيص ({$remaining})");
    echo 'سيتم تحويلك إلى الصفحة الرئيسية بعد 3 ثوان ...';
    header("REfresh:3;url=index.php");
}

try {
    $pdo->beginTransaction();
    
    // تحديث عدد الكروت المستخدمة في الترخيص
    $stmt = $pdo->prepare("UPDATE licenses SET used_cards = used_cards + ? WHERE id = ? AND (total_cards - used_cards) >= ?");
    $result = $stmt->execute([$cards_count, $_SESSION['license_id'], $cards_count]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("تم تجاوز الحد المسموح به من الكروت");
    }
    
    // تسجيل الاستخدام
    $stmt = $pdo->prepare("INSERT INTO usage_log (license_id, cards_used) VALUES (?, ?)");
    $stmt->execute([$_SESSION['license_id'], $cards_count]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}

$font_size = $_POST['font_size'] ?? 12;
$font_color = $_POST['font_color'] ?? '#ffffff';

// تحويل لون الخط من Hex إلى RGB
$font_color = sscanf($font_color, "#%02x%02x%02x");
$text_color = array($font_color[0], $font_color[1], $font_color[2]);

for ($page = 0; $page < $totalPages; $page++) {
    $pdf->AddPage();
    for ($row = 0; $row < $rows; $row++) {
        for ($col = 0; $col < $cols; $col++) {
            if ($cardIndex >= count($arr)) break;
            $x = $margin_left + ($col * ($cardWidth + $spacing));
            $y = $margin_top + ($row * ($cardHeight + $spacing));
            $pdf->Image($card_design_path, $x, $y, $cardWidth, $cardHeight);
            $fontSize = $font_size * ($cards_layout >= 21 ? 0.8 : 1);
            $pdf->SetFont('Arial', 'B', $fontSize);
            
            // تطبيق لون الخط
            $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);

            $scaleX = $cardWidth / $imgWidth;
            $scaleY = $cardHeight / $imgHeight;
            $text_x = $x + ($username_x * $scaleX);
            $text_y = $y + ($username_y * $scaleY);
            $pass_x = $x + ($password_x * $scaleX);
            $pass_y = $y + ($password_y * $scaleY);
            $pdf->SetXY($text_x, $text_y);
            $pdf->Cell(($cardWidth * 0.2), ($cardHeight * 0.1), strtoupper($arr[$cardIndex]['username']), 0, 0, 'C');
            
            $pdf->SetXY($pass_x, $pass_y);
            $pdf->Cell(($cardWidth * 0.2), ($cardHeight * 0.1), strtoupper($arr[$cardIndex]['password']), 0, 0, 'C');
            $cardIndex++;
        }
    }
}

$cards_count = count($arr);
// التحقق من عدد الكروت المتبقية في الترخيص
$stmt = $pdo->prepare("SELECT (total_cards - used_cards) as remaining FROM licenses WHERE id = ? AND status = 'active'");
$stmt->execute([$_SESSION['license_id']]);
$remaining = $stmt->fetchColumn();

if ($remaining < $cards_count) {
    die("عدد الكروت المطلوبة ({$cards_count}) يتجاوز العدد المتبقي في الترخيص ({$remaining})");
    echo 'سيتم تحويلك إلى الصفحة الرئيسية بعد 3 ثوان ...';
    header("REfresh:3;url=index.php");
}

try {
    $pdo->beginTransaction();
    
    // تحديث عدد الكروت المستخدمة في الترخيص
    $stmt = $pdo->prepare("UPDATE licenses SET used_cards = used_cards + ? WHERE id = ? AND (total_cards - used_cards) >= ?");
    $result = $stmt->execute([$cards_count, $_SESSION['license_id'], $cards_count]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("تم تجاوز الحد المسموح به من الكروت");
    }
    
    // تسجيل الاستخدام
    $stmt = $pdo->prepare("INSERT INTO usage_log (license_id, cards_used) VALUES (?, ?)");
    $stmt->execute([$_SESSION['license_id'], $cards_count]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}

$font_size = $_POST['font_size'] ?? 12;
$font_color = $_POST['font_color'] ?? '#ffffff';
$cards_layout = $_POST['cards_layout'] ?? 10;

// تحويل لون الخط من Hex إلى RGB
$font_color = sscanf($font_color, "#%02x%02x%02x");
$text_color = array($font_color[0], $font_color[1], $font_color[2]);

$filename = UPLOAD_DIR . 'cards_' . time() . '.pdf';
$pdf->Output('F', $filename);
header("Location: download.php?file=" . urlencode($filename));

// تطبيق لون الخط عند إنشاء النص
$pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
$pdf->SetFont('DejaVuSans', '', $font_size);

// عند كتابة النص على الكارت
$pdf->Text($username_x, $username_y, $username);
$pdf->Text($password_x, $password_y, $password);
