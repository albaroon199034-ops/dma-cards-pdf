<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user || password_verify($password, $user['password'])) {
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
<html dir="rtl">

<head>
    <title> تحويل ملفات csv الى pdf لكروت الانترنت </title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .main-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 20px;
        }

        /* System Introduction Section */
        .intro-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .intro-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .intro-text {
            text-align: center;
        }

        .intro-text h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .intro-text p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .intro-image {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            height: 370px;
            width: 100%;
            margin-bottom: 10px;
        }

        .intro-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .intro-video {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            margin-bottom: 20px;
            background: #000;
        }

        .intro-video video {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 15px;
        }

        .video-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 0 0 15px 15px;
        }

        .video-controls button {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-controls button:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .video-controls button i {
            font-size: 1.2rem;
        }

        .video-controls .progress-bar {
            flex-grow: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            margin: 0 10px;
            cursor: pointer;
            position: relative;
        }

        .video-controls .progress {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #3498db;
            border-radius: 2px;
        }

        .video-controls .time {
            color: white;
            font-size: 0.9rem;
            min-width: 80px;
            text-align: center;
        }

        /* System Features */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
            width: 100%;
        }

        .features-grid h2 {
            grid-column: 1 / -1;
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 25px 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
        }

        .feature-card:nth-child(3) {
            border: 2px solid #3498db;
            transform: scale(1.02);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .pricing-title {
            color: #2c3e50;
            font-size: 1.4rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .pricing-price {
            font-size: 2.2rem;
            color: #3498db;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .pricing-period {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .feature-card ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            flex-grow: 1;
        }

        .feature-card li {
            padding: 8px 0;
            color: #666;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-card li:last-child {
            border-bottom: none;
        }

        .feature-card li:before {
            content: "✓";
            color: #3498db;
            margin-left: 8px;
            font-weight: bold;
        }

        .subscribe-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 200px;
            margin: 15px auto 0;
        }

        .subscribe-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 20px 15px;
            }

            .pricing-title {
                font-size: 1.2rem;
            }

            .pricing-price {
                font-size: 1.8rem;
            }

            .subscribe-btn {
                width: 100%;
            }
        }

        /* Login Section */
        .login-section {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-section h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .auth-links {
            margin-top: 20px;
            text-align: center;
        }

        .auth-links a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: #2980b9;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .intro-image {
                height: 200px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .intro-text h1 {
                font-size: 1.8rem;
            }
        }

        .pricing-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 20px auto;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            text-align: center;
        }

        .pricing-btn:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .pricing-btn i {
            margin-left: 8px;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Login Section -->
        <section class="login-section">
            <h2>تسجيل الدخول</h2>
            <?php if (isset($error))
                echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>اسم المستخدم</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">تسجيل الدخول</button>
            </form>
            <div class="auth-links">
                <p>ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
                <p><a href="forgot_password.php">نسيت كلمة المرور؟</a></p>
            </div>
        </section>
        <!-- System Introduction Section -->
        <section class="intro-section">
            <div class="intro-content">
                <div class="intro-text">
                    <h1>نظام إنشاء كروت باقات الإنترنت</h1>
                    <p>نظام متكامل لإنشاء وإدارة كروت باقات الإنترنت للأنظمة التالية: ردياس، بالتل، وهوائي. يمكنك رفع
                        تصميم الكروت وإضافة البيانات بسهولة من خلال واجهة السحب والإفلات، ثم تصديرها كملفات PDF جاهزة
                        للطباعة.</p>

                    <div class="intro-video">
                        <video id="demoVideo" poster="video-poster.jpg">
                            <source src="#########" type="video/mp4">
                            متصفحك لا يدعم تشغيل الفيديو
                        </video>
                        <div class="video-controls">
                            <button id="playPauseBtn">
                                <i class="fas fa-play"></i>
                            </button>
                            <div class="progress-bar">
                                <div class="progress"></div>
                            </div>
                            <span class="time">00:00 / 00:00</span>
                            <button id="muteBtn">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <button id="fullscreenBtn">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const video = document.getElementById('demoVideo');
                            const playPauseBtn = document.getElementById('playPauseBtn');
                            const muteBtn = document.getElementById('muteBtn');
                            const fullscreenBtn = document.getElementById('fullscreenBtn');
                            const progressBar = document.querySelector('.progress-bar');
                            const progress = document.querySelector('.progress');
                            const timeDisplay = document.querySelector('.time');

                            // Play/Pause
                            playPauseBtn.addEventListener('click', function() {
                                if (video.paused) {
                                    video.play();
                                    playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                                } else {
                                    video.pause();
                                    playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                                }
                            });

                            // Mute/Unmute
                            muteBtn.addEventListener('click', function() {
                                if (video.muted) {
                                    video.muted = false;
                                    muteBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                                } else {
                                    video.muted = true;
                                    muteBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                                }
                            });

                            // Fullscreen
                            fullscreenBtn.addEventListener('click', function() {
                                if (!document.fullscreenElement) {
                                    video.requestFullscreen();
                                    fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
                                } else {
                                    document.exitFullscreen();
                                    fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
                                }
                            });

                            // Progress Bar
                            video.addEventListener('timeupdate', function() {
                                const percent = (video.currentTime / video.duration) * 100;
                                progress.style.width = percent + '%';
                                
                                // Update time display
                                const currentMinutes = Math.floor(video.currentTime / 60);
                                const currentSeconds = Math.floor(video.currentTime % 60);
                                const durationMinutes = Math.floor(video.duration / 60);
                                const durationSeconds = Math.floor(video.duration % 60);
                                
                                timeDisplay.textContent = 
                                    `${currentMinutes.toString().padStart(2, '0')}:${currentSeconds.toString().padStart(2, '0')} / 
                                     ${durationMinutes.toString().padStart(2, '0')}:${durationSeconds.toString().padStart(2, '0')}`;
                            });

                            // Click on progress bar to seek
                            progressBar.addEventListener('click', function(e) {
                                const percent = e.offsetX / this.offsetWidth;
                                video.currentTime = percent * video.duration;
                            });

                            // Update play/pause button when video ends
                            video.addEventListener('ended', function() {
                                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                            });
                        });
                    </script>

                    <a href="pricing.php" class="pricing-btn">
                        <i class="fas fa-tags"></i>
                        عرض الباقات والأسعار
                    </a>
                </div>
            </div>
        </section>
    </div>
</body>

</html>