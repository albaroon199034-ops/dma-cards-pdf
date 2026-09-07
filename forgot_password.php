<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND phone = ?");
    $stmt->execute([$username, $phone]);
    $user = $stmt->fetch();
    
    if ($user) {
        // إنشاء توكن مؤقت
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
        $stmt->execute([$token, $expiry, $user['id']]);
        
        // توجيه المستخدم إلى صفحة تغيير كلمة المرور
        header("Location: reset_password.php?token=" . $token);
        exit();
    } else {
        $error = "اسم المستخدم أو رقم الهاتف غير صحيح";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>استعادة كلمة المرور</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="login-container">
            <h2>استعادة كلمة المرور</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>اسم المستخدم</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>رقم الهاتف</label>
                    <input type="text" name="phone" required>
                </div>
                <button type="submit">استعادة كلمة المرور</button>
            </form>
            <div class="auth-links" style="margin-top: 20px; text-align: center;">
                <p><a href="login.php" style="color: #007bff; text-decoration: none;">العودة لتسجيل الدخول</a></p>
            </div>
        </div>
    </div>
</body>
</html>