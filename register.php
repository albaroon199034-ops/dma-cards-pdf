<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // التحقق من تطابق كلمة المرور
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // التحقق من وجود اسم المستخدم
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username already exists";
        } else {
            // التحقق من وجود البريد الإلكتروني
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already exists";
            } else {
                // إنشاء المستخدم الجديد
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$first_name, $last_name, $username, $email, $phone, $hashed_password])) {
                    $_SESSION['success'] = "Registration successful! Please login.";
                    header('Location: login.php');
                    exit();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
    <style>
        .register-container {
            max-width: 500px !important;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #2a5298;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .login-link a:hover {
            color: #1e3c72;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container register-container">
        <h2>إنشاء حساب جديد</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>الاسم الأول</label>
                <input type="text" name="first_name" required minlength="2" maxlength="50"
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>الاسم الأخير</label>
                <input type="text" name="last_name" required minlength="2" maxlength="50"
                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" required minlength="3" maxlength="50"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>رقم الهاتف</label>
                <input type="tel" name="phone" required pattern="[0-9]+" minlength="10" maxlength="15"
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit">إنشاء الحساب</button>
        </form>
        <div class="login-link">
            لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
        </div>
    </div>
</body>
</html>