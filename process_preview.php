<?php
session_start();
require_once 'ini.php';
require_once 'config/db.php';
require('fpdf.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['license_id']) || !isset($_SESSION['preview_data'])) {
    echo "لم يتم تسجيل الدخول أو البيانات غير متوفرة.";
    echo '<br>';
    echo 'سيتم تحويلك إلى الصفحة الرئيسية بعد 2 ثانية...';
    header("Refresh:2;url=index.php");
    exit();
}

// استرجاع البيانات من الجلسة
$preview_data = $_SESSION['preview_data'];
$arr = $preview_data['arr'];
$card_design_path = $preview_data['card_design_path'];
$username_x = $preview_data['username_x'];
$username_y = $preview_data['username_y'];
$password_x = $preview_data['password_x'];
$password_y = $preview_data['password_y'];
$font_size = $preview_data['font_size'];
$cards_layout = $preview_data['cards_layout'];
$text_color = $preview_data['text_color'];
$cards_count = $preview_data['cards_count'];

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
    
    // إنشاء ملف PDF
    $layouts = [
        10 => [5, 2, 1, 1, 104, 58.4],
        21 => [7, 3, 1, 1, 69, 41.4],
        24 => [8, 3, 1, 1, 69, 36.1],
        40 => [10, 4, 1, 1, 51.5, 28.7],
    ];
    [$rows, $cols, $margin, $spacing, $cardWidth, $cardHeight] = $layouts[$cards_layout] ?? $layouts[10];

    list($imgWidth, $imgHeight) = getimagesize($card_design_path);
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

    // حفظ الملف
    $output_filename = 'cards_' . time() . '.pdf';
    $pdf->Output('F', UPLOAD_DIR . $output_filename);
    
    $pdo->commit();
    
    // تحويل المستخدم لتحميل الملف
    header("Location: download.php?file=" . urlencode(UPLOAD_DIR . $output_filename));
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo $e->getMessage();
    echo '<br>';
    echo 'سيتم تحويلك إلى الصفحة الرئيسية بعد 2 ثانية...';
    header("Refresh:2;url=index.php");
    exit();
}