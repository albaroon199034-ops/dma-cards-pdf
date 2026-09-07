<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define("UPLOAD_DIR", "uploads/");

// حذف الملفات القديمة (أقدم من ساعة)
$one_hour_ago = time() - 3600; 

// حذف ملفات PDF القديمة
foreach (glob(UPLOAD_DIR . "*.pdf") as $file) {
    if (filemtime($file) < $one_hour_ago) {
        unlink($file);
    }
}

// حذف ملفات الصور القديمة
$image_extensions = ['jpg', 'jpeg', 'png', 'gif'];
foreach ($image_extensions as $ext) {
    foreach (glob(UPLOAD_DIR . "*." . $ext) as $file) {
        if (filemtime($file) < $one_hour_ago) {
            unlink($file);
        }
    }
}