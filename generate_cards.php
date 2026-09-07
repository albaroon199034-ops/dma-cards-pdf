<?php
require('fpdf.php');
include 'ini.php';
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['license_id'])) {
    echo "لم يتم تسجيل الدخول.";
    echo '<br>';
    echo 'سيتم تحويلك إلى الصفحة الرئيسية بعد 2 ثانية...';
    header("Refresh:2;url=login.php");
    exit();
}

if (!isset($_POST['csv_data'], $_POST['card_design_path'], $_POST['username_x'], $_POST['username_y'], $_POST['password_x'], $_POST['password_y'], $_POST['font_size'], $_POST['cards_layout'])) {
    echo "لم يتم تقديم البيانات المطلوبة.";
    echo '<br>';
    echo 'سيتم تحويلك إلى الصفحة الرئيسية بعد 2 ثانية...';
    header("Refresh:2;url=index.php");
    exit();
}

// تعريف متغيرات الخط واللون في بداية الملف
$font_size = intval($_POST['font_size'] ?? '10'); // حجم الخط الافتراضي هو 10
$font_color = $_POST['font_color'] ?? '#000000'; // اللون الافتراضي هو الأسود

// تحويل لون الخط من Hex إلى RGB
$font_color = sscanf($font_color, "#%02x%02x%02x");
$text_color = array($font_color[0], $font_color[1], $font_color[2]);

$csv_data_encoded = $_POST['csv_data'];
$csv_data = base64_decode($csv_data_encoded);
$temp_csv = tempnam(sys_get_temp_dir(), 'csv');
file_put_contents($temp_csv, $csv_data);

// تحديد نوع النظام من الجلسة
$system_type = $_SESSION['system_type'] ?? 'redis';

$arr = array();
$file = fopen($temp_csv, 'r');

if ($system_type === 'hawai') {
    // معالجة ملف PDF الهوائي
    require_once 'vendor/autoload.php';
    $parser = new \Smalot\PdfParser\Parser();
    
    try {
        // تحليل ملف PDF
        $pdf = $parser->parseFile($temp_csv);
        
        // استخراج النص من كل صفحة
        $pages = $pdf->getPages();
        foreach ($pages as $page) {
            $text = $page->getText();
            
            // تقسيم النص إلى أسطر
            $lines = explode("\n", $text);
            
            // معالجة كل سطرين (username & password)
            for ($i = 0; $i < count($lines); $i += 2) {
                if (isset($lines[$i]) && isset($lines[$i + 1])) {
                    $username = trim($lines[$i]);
                    $password = trim($lines[$i + 1]);
                    
                    // التحقق من أن البيانات ليست فارغة
                    if (!empty($username) && !empty($password)) {
                        $arr[] = array(
                            'username' => $username,
                            'password' => $password
                        );
                    }
                }
            }
        }
    } catch (Exception $e) {
        die('خطأ في قراءة ملف PDF: ' . $e->getMessage());
    }
} elseif ($system_type === 'pallet') {
    // معالجة ملف باليت
    while (($row = fgetcsv($file, 0, '"')) !== FALSE) {
        if (empty($row[0])) continue;
        
        // تنظيف البيانات من علامات الاقتباس
        $line = trim($row[0]);
        
        // تقسيم السطر إلى مصفوفة باستخدام المسافات كفاصل
        $parts = preg_split('/\s+/', $line);
        
        // معالجة كل 2 عناصر (username & password)
        for ($i = 1; $i < count($parts); $i += 2) {
            if ($parts[$i-1] === 'Username' && isset($parts[$i])) {
                $username = $parts[$i];
                // قراءة السطر التالي للحصول على كلمة المرور
                $next_row = fgetcsv($file, 0, '"');
                if ($next_row) {
                    $password_parts = preg_split('/\s+/', trim($next_row[0]));
                    // الحصول على كلمة المرور المقابلة
                    $password_index = ceil($i/2) * 2 - 1;
                    if (isset($password_parts[$password_index])) {
                        $arr[] = array(
                            'username' => $username,
                            'password' => $password_parts[$password_index]
                        );
                    }
                }
            }
        }
    }
} else {
    // المعالجة الحالية لنظام رديس
    fgetcsv($file); // تجاوز السطر الأول
    while (($row = fgetcsv($file)) !== FALSE) {
        if (empty($row[0])) continue;
        $explode = explode(';', $row[0]);
        if (empty($explode[1])) continue;
        $username = str_replace('"', '', $explode[1]);
        $password = str_replace('"', '', $explode[2]);
        $arr[] = array('username' => $username, 'password' => $password);
    }
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

$cards_count = count($arr);
// التحقف فقط من توفر العدد دون خصم
$stmt = $pdo->prepare("SELECT (total_cards - used_cards) as remaining FROM licenses WHERE id = ? AND status = 'active'");
$stmt->execute([$_SESSION['license_id']]);
$remaining = $stmt->fetchColumn();

if ($remaining < $cards_count) {
    echo "عدد الكروت المطلوبة ({$cards_count}) يتجاوز العدد المتبقي في الترخيص ({$remaining})";
    echo '<br>';
    echo 'سيتم تحويلك إلى الصفحة الرئيسية بعد 2 ثانية ...';
    header("Refresh:2;url=index.php");
    exit();
}

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

// تغيير طريقة إنشاء اسم الملف
$preview_filename = 'preview_' . time() . '.pdf';
$filename = UPLOAD_DIR . $preview_filename;

// تخزين جميع البيانات المطلوبة في الجلسة
$_SESSION['preview_data'] = [
    'arr' => $arr,
    'card_design_path' => $card_design_path,
    'username_x' => $username_x,
    'username_y' => $username_y,
    'password_x' => $password_x,
    'password_y' => $password_y,
    'font_size' => $font_size,
    'cards_layout' => $cards_layout,
    'text_color' => $text_color,
    'cards_count' => $cards_count
];

$pdf->Output('F', $filename);

?>
<!DOCTYPE html>
<html dir="rtl">

<head>
    <title>معاينة الكروت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e9ecef;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .preview-container {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .card-grid {
            position: absolute;
            display: grid;
            gap: <?php echo $spacing; ?>mm;
            padding: <?php echo $margin_top; ?>mm <?php echo $margin_left; ?>mm;
            width: calc(100% - <?php echo $margin_left * 2; ?>mm);
            height: calc(100% - <?php echo $margin_top * 2; ?>mm);
            background: #ffffff;
        }

        .card-item {
            width: <?php echo $cardWidth; ?>mm;
            height: <?php echo $cardHeight; ?>mm;
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .card-username,
        .card-password {
            position: absolute;
            font-weight: bold;
            font-size: <?php echo $fontSize; ?>pt;
            color: <?php echo sprintf('#%02x%02x%02x', $text_color[0], $text_color[1], $text_color[2]); ?>;
        }

        .btn {
            padding: 12px 35px;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }

        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
        }

        .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }

        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }

        .mt-4 {
            margin-top: 2.5rem !important;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-weight: 700;
            font-size: 2.2rem;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.5px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="text-center">
            <h2 class="mb-4">معاينة الكروت</h2>
            <h2 class="mb-4">نظام الكروت: <?php echo $system_type; ?></h2>
            <p>يتم عرض بيانات وهميه على الكروت بدلا من البيانات الحقيقة</p>
            
            <div class="row justify-content-center mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">إجمالي عدد الكروت</h5>
                            <p class="card-text display-6"><?php echo count($arr); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">عدد الصفحات</h5>
                            <p class="card-text display-6"><?php echo $totalPages; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">كروت في كل صفحة</h5>
                            <p class="card-text display-6"><?php echo $cardsPerPage; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="preview-container mb-4">
                <div class="card-grid" style="grid-template-columns: repeat(<?php echo $cols; ?>, <?php echo $cardWidth; ?>mm);">
                    <?php
                    $page = 0; // تعيين الصفحة الأولى
                    for ($i = 0; $i < $cardsPerPage && ($page * $cardsPerPage + $i) < count($arr); $i++):
                        $cardData = $arr[$page * $cardsPerPage + $i];
                        $scaleX = $cardWidth / $imgWidth;
                        $scaleY = $cardHeight / $imgHeight;
                        $username_pos_x = ($username_x * $scaleX);
                        $username_pos_y = ($username_y * $scaleY);
                        $password_pos_x = ($password_x * $scaleX);
                        $password_pos_y = ($password_y * $scaleY);
                    ?>
                        <div class="card-item" style="background-image: url('<?php echo $card_design_path; ?>')">
                            <span class="card-username" style="left: <?php echo $username_pos_x; ?>mm; top: <?php echo $username_pos_y; ?>mm;">
                                <!-- <?php echo strtoupper($cardData['username']); ?> -->
                                <?php echo '01234567'; ?>
                            </span>
                            <span class="card-password" style="left: <?php echo $password_pos_x; ?>mm; top: <?php echo $password_pos_y; ?>mm;">
                                <!-- <?php echo strtoupper($cardData['password']); ?> -->
                                <?php echo '01234'; ?>
                            </span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="mt-4">
                <form action="process_preview.php" method="POST" class="d-inline">
                    <button type="submit" class="btn btn-success btn-lg mx-2">تأكيد وطباعة</button>
                </form>
                <a href="index.php" class="btn btn-danger btn-lg mx-2">إلغاء</a>
            </div>
        </div>
    </div>
</body>

</html>