<?php
session_start();
require_once 'config/db.php';

if (!isset($_GET['token'])) {
    header('Location: login.php');
    exit();
}

$token = $_GET['token'];
// تعديل الاستعلام للتحقق من التوكن
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE reset_token = ? 
    AND reset_token_expiry > NOW() 
    AND reset_token IS NOT NULL
");
$stmt->execute([$token]);
$user = $stmt->fetch();

// إضافة تفاصيل الخطأ للتشخيص
if (!$user) {
    $stmt = $pdo->prepare("SELECT reset_token_expiry FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $expiry = $stmt->fetchColumn();
    
    if ($expiry) {
        if (strtotime($expiry) < time()) {
            die("انتهت صلاحية الرابط. الرجاء طلب رابط جديد من صفحة نسيت كلمة المرور");
        }
    } else {
        die("الرابط غير صالح. الرجاء التأكد من الرابط أو طلب رابط جديد");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "كلمتا المرور غير متطابقتين";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>تعيين كلمة مرور جديدة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="login-container">
            <h2>تعيين كلمة مرور جديدة</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>كلمة المرور الجديدة</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>تأكيد كلمة المرور</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit">حفظ كلمة المرور الجديدة</button>
            </form>
        </div>
    </div>
</body>
</html>