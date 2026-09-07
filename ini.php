<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define("UPLOAD_DIR", "uploads/");
foreach (glob(UPLOAD_DIR . "*.pdf") as $file) {
    if (filemtime($file) < time() - 1800) {
        unlink($file);
    }
}
?>
