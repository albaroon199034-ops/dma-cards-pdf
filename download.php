<?php
session_start();
include 'ini.php';

if (!isset($_GET['file']) || !file_exists($_GET['file'])) {
    die("الملف غير موجود");
}

$file = $_GET['file'];

// التأكد من أن المسار آمن
$realPath = realpath($file);
$uploadDir = realpath(UPLOAD_DIR);

if (strpos($realPath, $uploadDir) !== 0) {
    die("مسار غير صالح");
}

if (pathinfo($file, PATHINFO_EXTENSION) !== 'pdf') {
    die("نوع ملف غير صالح");
}

$isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';

header('Content-Type: application/pdf');

if (!$isPreview) {
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
}

readfile($file);
exit;
?>
