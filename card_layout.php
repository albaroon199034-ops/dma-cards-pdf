<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_FILES['csv_file']) || !isset($_FILES['card_design'])) {
    header('Location: 404.php');
    exit();
}

include 'ini.php';
include 'config/db.php';
$csv_file = $_FILES['csv_file']['tmp_name'];
$row_count = count(file($csv_file)) - 1; 
if ($row_count > $_SESSION['remaining_cards']) {
    die("عذراً، عدد السجلات في ملف CSV يتجاوز عدد الكروت المتبقية في ترخيصك");
}
$_SESSION['cards_to_print'] = $row_count;
if ($_FILES['csv_file']['error'] != 0 || $_FILES['card_design']['error'] != 0) {
    die("حدث خطأ أثناء رفع الملفات.");
}
$csv_file = $_FILES['csv_file']['tmp_name'];
$csv_content = file_get_contents($csv_file);
$csv_encoded = base64_encode($csv_content);
$card_file = $_FILES['card_design']['tmp_name'];
$allowed_types = ["image/jpeg", "image/png"];
$card_mime = mime_content_type($card_file);
if (!in_array($card_mime, $allowed_types)) {
    die("يرجى رفع صورة صالحة لتصميم الكارت.");
}
$card_design_path = UPLOAD_DIR . "card_design_" . time() . "_" . basename($_FILES['card_design']['name']);
if (!move_uploaded_file($card_file, $card_design_path)) {
    die("فشل رفع صورة تصميم الكارت.");
}

if (isset($_POST['system_type'])) {
    $_SESSION['system_type'] = $_POST['system_type'];
}
?>
<?php include 'inc/header.php'; ?>
<div class="container py-0">
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">تحديد مواقع العناصر على الكارت</h2>
                <div class="badge bg-primary fs-5">
                     عدد الكروت اللتى سيتم تصميمها: <?php echo number_format($_SESSION['cards_to_print']); ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <div id="cardContainer" class="position-relative d-inline-block">
                                <img id="cardImage" src="<?php echo $card_design_path; ?>" alt="تصميم الكارت" class="img-fluid shadow-sm">
                                <div id="username" class="draggable badge bg-primary bg-opacity-75" style="top: 10%; left: 50%; transform: translate(-50%, -50%);">user name</div>
                                <div id="password" class="draggable badge bg-success bg-opacity-75" style="top: 10%; left: 65%; transform: translate(-50%, -50%);">password</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">إعدادات التصميم</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label fw-bold">حجم الخط</label>
                                <select id="fontSize" class="form-select">
                                    <?php for ($i = 10; $i <= 32; $i += 2): ?>
                                        <option value="<?php echo $i ?>" <?php echo ($i == 10 ? 'selected' : '') ?>><?php echo $i ?>px</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">لون الخط</label>
                                <input type="color" id="fontColor" class="form-control form-control-color w-100" value="#000000">
                                <script>
                                    // تعيين القيم الافتراضية
                                    $(document).ready(function() {
                                        $("#fontSize").val("10").trigger("change");
                                        $("#fontColor").val("#000000").trigger("change");
                                    });
                                </script>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">تخطيط الكروت</label>
                                <select id="cardsPerPage" class="form-select">
                                    <option value="10">صفحة (A4) 2×5 كروت</option>
                                    <option value="21">صفحة (A4) 3×7 كروت</option>
                                    <option value="24">صفحة (A4) 3×8 كروت</option>
                                    <option value="40">صفحة (A4) 4×10 كروت</option>
                                </select>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right me-2"></i>رجوع
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<form action="generate_cards.php" method="POST">
    <input type="hidden" name="font_size" id="font_size" value="10">
    <input type="hidden" name="cards_layout" id="cards_layout" value="10">
    <input type="hidden" name="csv_data" value="<?php echo $csv_encoded; ?>">
    <input type="hidden" name="card_design_path" value="<?php echo $card_design_path; ?>">
    <input type="hidden" name="username_x" id="username_x" value="10">
    <input type="hidden" name="username_y" id="username_y" value="10">
    <input type="hidden" name="password_x" id="password_x" value="10">
    <input type="hidden" name="password_y" id="password_y" value="40">
    <input type="hidden" name="font_color" id="font_color" value="#000000">

    <div class="text-center">
        <button type="submit" class="btn btn-danger btn-lg px-5">
            <i class="fas fa-magic me-2"></i>توليد الكروت
        </button>
    </div>
</form>
<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
        min-height: 100vh;
    }
    .card {
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }
    .draggable {
        position: absolute;
        cursor: move;
        padding: 8px 8px;
        border-radius: 4px;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    #cardContainer {
        max-width: 100%;
        margin: 0 auto;
    }
    #cardImage {
        max-width: 100%;
        border-radius: 8px;
    }
    .container {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }
</style>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script>
    $(document).ready(function() {
        $("#cardsPerPage").change(function() {
            var layout = $(this).val();
            $("#cards_layout").val(layout);
        });
        $("#fontSize").change(function() {
            var newSize = $(this).val();
            $(".draggable").css('font-size', newSize + 'px');
            $("#font_size").val(newSize);
        });

        $("#fontColor").change(function() {
            var newColor = $(this).val();
            $(".draggable").css('color', newColor);
            $("#font_color").val(newColor);
        });

        $(".draggable").draggable({
            containment: "#cardContainer",
            cursor: "move",
            stop: function(event, ui) {
                var id = $(this).attr('id');
                var cardImage = $("#cardImage");
                var imageNaturalWidth = cardImage[0].naturalWidth;
                var imageDisplayWidth = cardImage.width();
                var scaleFactor = imageNaturalWidth / imageDisplayWidth;
                var imageOffset = cardImage.offset();
                var elementOffset = $(this).offset();
                var relativeX = (elementOffset.left - imageOffset.left) * scaleFactor;
                var relativeY = (elementOffset.top - imageOffset.top) * scaleFactor;
                $("#" + id + "_x").val(Math.round(relativeX * 100) / 100);
                $("#" + id + "_y").val(Math.round(relativeY * 100) / 100);
                console.log(id + " real position:", {
                    x: relativeX,
                    y: relativeY,
                    scale: scaleFactor
                });
            }
        });
    });
</script>
<?php include 'inc/footer.php'; ?>