<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        if ($user['is_admin']) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: profile.php'); // تغيير المسار إلى صفحة الملف الشخصي
        }
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f5f5;
            padding: 20px;
        }
        .auth-container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            gap: 40px;
            align-items: center;
        }
        .login-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-image img {
            width: 100%;
            max-width: 600px;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            object-fit: cover;
        }
        .login-container {
            flex: 1;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            min-width: 320px;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                gap: 30px;
            }
            .login-image {
                width: 100%;
            }
            .login-image img {
                max-width: 100%;
            }
            .login-container {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="login-image">
            <img src="login.jpeg" alt="Service Logo">
        </div>
        <div class="login-container">
            <h2>Login</h2>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <div class="auth-links" style="margin-top: 20px; text-align: center;">
                <p>ليس لديك حساب؟ <a href="register.php" style="color: #007bff; text-decoration: none;">سجل الآن</a></p>
                <p><a href="forgot_password.php" style="color: #dc3545; text-decoration: none;">نسيت كلمة المرور؟</a></p>
            </div>
        </div>
    </div>
</body>
</html>