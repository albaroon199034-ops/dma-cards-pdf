<?php
session_start();
require_once 'config/db.php';

// جلب جميع الباقات
try {
    $stmt = $pdo->query("SELECT * FROM packages ORDER BY price");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
    $error = "حدث خطأ أثناء جلب الباقات";
}

?>

<!DOCTYPE html>
<html dir="rtl">

<head>
    <title>باقات النظام</title>
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
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .back-btn i {
            margin-left: 8px;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .header p {
            color: #666;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
            text-align: center;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .pricing-header {
            margin-bottom: 30px;
        }

        .pricing-title {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .pricing-price {
            color: #3498db;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .pricing-period {
            color: #666;
            font-size: 1rem;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 30px;
        }

        .pricing-features li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            color: #666;
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .pricing-features li i {
            color: #2ecc71;
            margin-left: 10px;
        }

        .pricing-button {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            /* position: absolute; */
            bottom: 30px;

        }

        .pricing-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .pricing-card.featured .pricing-button {
            background: #2ecc71;
        }

        .pricing-card.featured .pricing-button:hover {
            background: #27ae60;
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .contact-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 50px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }

        .contact-section h2 {
            color: #2c3e50;
            font-size: 2.2rem;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }

        .contact-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 50%;
            transform: translateX(50%);
            width: 100px;
            height: 3px;
            background: #3498db;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .contact-method {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px 25px;
            background: #f8f9fa;
            border-radius: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2c3e50;
            position: relative;
            overflow: hidden;
        }

        .contact-method::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(52, 152, 219, 0.1), rgba(46, 204, 113, 0.1));
            z-index: 0;
        }

        .contact-method:hover {
            background: #e9ecef;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .contact-method i {
            font-size: 1.8rem;
            color: #3498db;
            position: relative;
            z-index: 1;
        }

        .contact-method span {
            font-size: 1.2rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .contact-method.whatsapp i {
            color: #25D366;
        }

        .contact-method.telegram i {
            color: #0088cc;
        }

        .contact-method.facebook i {
            color: #1877f2;
        }

        .contact-method.phone i {
            color: #3498db;
        }

        @media (max-width: 1200px) {
            .pricing-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .pricing-card.featured {
                transform: none;
            }

            .pricing-card.featured:hover {
                transform: translateY(-10px);
            }

            .contact-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="login.php" class="back-btn">
            <i class="fas fa-arrow-right"></i>
            العودة لتسجيل الدخول
        </a>

        <div class="header">
            <h1>باقات النظام</h1>
            <p>اختر الباقة المناسبة لك واستمتع بمميزات النظام</p>
        </div>

        <div class="pricing-grid">
            <?php foreach ($packages as $pkg): ?>
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3 class="pricing-title"><?php echo htmlspecialchars($pkg['name']); ?></h3>
                        <div class="pricing-price"><?php echo number_format($pkg['price'], 2); ?> شيكل</div>
                    </div>
                    
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> <?php echo $pkg['cards_count']; ?> كرت</li>
                        <li><i class="fas fa-check"></i> صلاحية <?php echo $pkg['duration']; ?> <?php echo $pkg['duration'] > 1 ? 'أشهر' : 'شهر'; ?></li>
                    </ul>
                    
                    <button class="pricing-button" onclick="scrollToContact()">
                        اشترك الآن
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="contact-section">
            <h2>تواصل معنا لطلب الباقة</h2>
            <div class="contact-info">
                <a href="tel:+970592669188" class="contact-method phone">
                    <i class="fas fa-phone"></i>
                    <span>اتصل بنا</span>
                </a>
                <a href="https://wa.me/970592669188" class="contact-method whatsapp" target="_blank">
                    <i class="fab fa-whatsapp"></i>
                    <span>واتساب</span>
                </a>
                <a href="https://t.me/+970592669188" class="contact-method telegram" target="_blank">
                    <i class="fab fa-telegram"></i>
                    <span>تيليجرام</span>
                </a>
                <a href="https://www.facebook.com/algrob" class="contact-method facebook" target="_blank">
                    <i class="fab fa-facebook"></i>
                    <span>فيسبوك</span>
                </a>
            </div>
        </div>
    </div>

    <script>
    function scrollToContact() {
        const contactSection = document.querySelector('.contact-section');
        contactSection.scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>

</html> 