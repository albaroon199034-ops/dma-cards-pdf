<?php
include 'ini.php';

if (isset($_GET['file']) && file_exists($_GET['file'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Converted_File.pdf"');
    readfile($_GET['file']);
    unlink($_GET['file']); // حذف الملف بعد التحميل
    exit;
} else {
    echo "الملف غير موجود!";
}
?>
