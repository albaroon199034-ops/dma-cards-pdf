<?php
require_once '../config/auth.php';
require_once '../config/db.php';
require '../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    die("غير مصرح");
}

if (isset($_POST['send_email'])) {
    $license_id = $_POST['license_id'];
    // جلب بيانات الترخيص والمستخدم
    $stmt = $pdo->prepare("
        SELECT l.*, u.email, u.username 
        FROM licenses l 
        JOIN users u ON l.user_id = u.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$license_id]);
    $license = $stmt->fetch();
    
    if ($license) {
        $mail = new PHPMailer(true);
        
        try {
            // إعدادات السيرفر            
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'shekoom484@gmail.com';
            $mail->Password   = 'cckf fdyu whyg ratk';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            // المرسل والمستقبل
            $mail->setFrom('shekoom484@gmail.com', 'print.gazawy.com');
            $mail->addAddress($license['email'], $license['username']);

            // المحتوى
            $mail->isHTML(true);
            $mail->Subject = "مفتاح ترخيص نظام طباعة الكروت";
            $mail->Body = "
            <html dir='rtl'>
            <head>
                <style>
                    body { 
                        font-family: 'Segoe UI', Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        background: #2c3e50;
                        color: white;
                        padding: 20px;
                        border-radius: 8px 8px 0 0;
                        text-align: center;
                    }
                    .content {
                        background: #fff;
                        padding: 20px;
                        border: 1px solid #ddd;
                        border-radius: 0 0 8px 8px;
                    }
                    .key {
                        background: #f8f9fa;
                        border: 2px dashed #2c3e50;
                        padding: 15px;
                        margin: 20px 0;
                        font-family: monospace;
                        font-size: 18px;
                        text-align: center;
                        color: #2c3e50;
                        border-radius: 6px;
                    }
                    .details {
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 6px;
                        margin: 20px 0;
                    }
                    .details ul {
                        list-style: none;
                        padding: 0;
                    }
                    .details li {
                        padding: 10px 0;
                        border-bottom: 1px solid #eee;
                    }
                    .details li:last-child {
                        border: none;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        color: #666;
                    }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>dma-csv-to-pdf</h2>
                </div>
                <div class='content'>
                    <h2>مرحباً {$license['username']}</h2>
                    <p>مرفق مفتاح الترخيص الخاص بك:</p>
                    <div class='key'>{$license['license_key']}</div>
                    <div class='details'>
                        <h3>تفاصيل الترخيص:</h3>
                        <ul>
                            <li>👤 المستخدم: {$license['username']}</li>
                            <li>🎫 عدد الكروت: {$license['total_cards']}</li>
                            <li>📅 تاريخ الانتهاء: {$license['expiry_date']}</li>
                        </ul>
                    </div>
                </div>
                <div class='footer'>
                    <p>dma-csv-to-pdf شكراً لاستخدامك نظامنا</p>
                    
                </div>
            </body>
            </html>
            ";

            $mail->send();
            header("Location: licenses.php?msg=email_sent");
        } catch (Exception $e) {
            header("Location: licenses.php?msg=email_error&error=" . urlencode($mail->ErrorInfo));
        }
    }
}