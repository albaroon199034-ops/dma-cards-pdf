<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config/db.php';
require_once 'ini.php';
if (isset($_POST['license_key'])) {
    $license_key = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($_POST['license_key'])));
    if (strlen($license_key) !== 32) {
        $error = "مفتاح الترخيص يجب أن يتكون من 32 حرف";
    } else {
        $stmt = $pdo->prepare("
            SELECT l.*, (l.total_cards - l.used_cards) as remaining_cards 
            FROM licenses l
            WHERE l.license_key = ? 
            AND l.user_id = ? 
            AND l.used_cards < l.total_cards 
            AND l.status = 'active'
            AND (l.expiry_date IS NULL OR l.expiry_date >= CURDATE())
        ");
        $stmt->execute([$license_key, $_SESSION['user_id']]);
        $license = $stmt->fetch();
        if ($license) {
            $_SESSION['license_id'] = $license['id'];
            $_SESSION['remaining_cards'] = $license['remaining_cards'];
        } else {
            $error = "مفتاح الترخيص غير صالح أو منتهي الصلاحية أو تم استنفاذ عدد الكروت المسموح بها";
        }
    }
}
$has_active_license = isset($_SESSION['license_id']);
?>
<?php include 'inc/header.php'; ?>
<div class="container py-0">
    <?php if (!$has_active_license): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h3 class="text-center mb-0">إدخال مفتاح الترخيص</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">مفتاح الترخيص</label>
                                <input type="text" name="license_key" class="form-control form-control-lg text-center"
                                    required maxlength="32" placeholder="أدخل مفتاح الترخيص هنا"
                                    oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                                    autocomplete="off">
                                <small class="text-muted">المفتاح يتكون من 32 حرف من الأرقام والحروف الإنجليزية فقط</small>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">تحقق من الترخيص</button>
                                <a href="profile.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-user me-2"></i>العودة للملف الشخصي
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h3 class="text-center mb-0">إنشاء كروت جديدة</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // إضافة استعلام لتحديث عدد الكروت المتبقية
                        if ($has_active_license) {
                            $stmt = $pdo->prepare("
                                SELECT (l.total_cards - l.used_cards) as remaining_cards 
                                FROM licenses l 
                                WHERE l.id = ? AND l.status = 'active'
                            ");
                            $stmt->execute([$_SESSION['license_id']]);
                            $remaining = $stmt->fetch();
                            $_SESSION['remaining_cards'] = $remaining['remaining_cards'];
                        }
                        ?>
                        <div
                            class="alert alert-success mb-4 d-flex align-items-center justify-content-between p-3 shadow-sm border-0">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                                <div>
                                    <h5 class="mb-1 fw-bold">الترخيص نشط</h5>
                                    <div class="text-success">
                                        عدد الكروت المتبقية:
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">
                                            <?php echo number_format($_SESSION['remaining_cards']); ?> كارت
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <a href="#" onclick="confirmLogout()" class="btn btn-outline-danger px-4">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                تسجيل خروج
                            </a>
                        </div>
                        <form action="card_layout.php" method="POST" enctype="multipart/form-data"
                            onsubmit="return validateCSVCount();">
                            <div class="mb-4">
                                <label class="form-label fw-bold">نوع النظام:</label>
                                <select class="form-select mb-4" name="system_type" required>
                                    <option value="">اختر نوع النظام</option>
                                    <option value="redis">ريدياس مانجر</option>
                                    <option value="pallet">بالتل</option>
                                    <option value="hawai">برنامج الهوائي bdf</option>
                                </select>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">اختر ملف البيانات (CSV أو Excel):</label>
                                        <div class="upload-area">
                                            <input type="file" class="file-input" name="csv_file" id="csv_file" accept=".csv,.xlsx,.pdf" required hidden>
                                            <div class="drop-zone text-center p-5 rounded" id="csvDropZone">
                                                <i class="fas fa-file-csv fa-3x mb-3 text-primary"></i>
                                                <h4>اسحب الملف هنا</h4>
                                                <p class="text-muted">أو</p>
                                                <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('csv_file').click()">
                                                    اختر ملف
                                                </button>
                                                <p class="selected-file mt-2 text-success" id="csvFileName"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">اختر تصميم الكارت:</label>
                                        <div class="upload-area">
                                            <input type="file" class="file-input" name="card_design" id="card_design"
                                                accept="image/jpeg,image/png,image/jpg" required hidden>
                                            <div class="drop-zone text-center p-5 rounded" id="imageDropZone">
                                                <i class="fas fa-image fa-3x mb-3 text-primary"></i>
                                                <h4>اسحب الصورة هنا</h4>
                                                <p class="text-muted">أو</p>
                                                <button type="button" class="btn btn-outline-dark"
                                                    onclick="document.getElementById('card_design').click()">
                                                    اختر صورة
                                                </button>
                                                <p class="selected-file mt-2 text-success" id="imageFileName"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-danger btn-lg px-5">
                                    متابعة لتحديد مواقع العناصر <i class="fas fa-arrow-left me-2"></i>
                                </button>
                                <a href="profile.php" class="btn btn-secondary btn-lg px-5 ms-2 mt-2">
                                    <i class="fas fa-user ms-2"></i>العودة للملف الشخصي
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
        min-height: 100vh;
    }

    .card {
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }

    .container {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .drop-zone {
        border: 2px dashed #ccc;
        transition: all 0.3s ease;
        background: #f8f9fa;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .drop-zone:hover {
        border-color: #0d6efd;
        background: #f8f9fa;
    }

    .drop-zone.dragover {
        border-color: #198754;
        background: #e8f5e9;
        transform: scale(1.02);
    }

    .selected-file {
        display: none;
        margin-top: 10px;
        padding: 8px 15px;
        background: #e8f5e9;
        border-radius: 4px;
        font-weight: 500;
    }

    .image-preview {
        max-width: 200px;
        max-height: 200px;
        margin: 10px auto;
        display: none;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .error-message {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 5px;
        display: none;
    }
</style>
<script>
    function confirmLogout() {
        if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
            window.location.href = 'logout.php';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const systemTypeSelect = document.querySelector('select[name="system_type"]');
        const csvFileInput = document.getElementById('csv_file');
        const csvDropZone = document.getElementById('csvDropZone');
        const csvFileName = document.getElementById('csvFileName');
        const csvIcon = csvDropZone.querySelector('i');
        const cardDesignInput = document.getElementById('card_design');
        const imageDropZone = document.getElementById('imageDropZone');
        const imageFileName = document.getElementById('imageFileName');

        function updateFileInputAccept() {
            const systemType = systemTypeSelect.value;
            if (systemType === 'hawai') {
                csvFileInput.accept = '.pdf';
                csvIcon.className = 'fas fa-file-pdf fa-3x mb-3 text-primary';
                csvDropZone.querySelector('h4').textContent = 'اسحب ملف PDF هنا';
            } else {
                csvFileInput.accept = '.csv,.xlsx';
                csvIcon.className = 'fas fa-file-csv fa-3x mb-3 text-primary';
                csvDropZone.querySelector('h4').textContent = 'اسحب ملف CSV أو Excel هنا';
            }
        }

        systemTypeSelect.addEventListener('change', updateFileInputAccept);
        updateFileInputAccept();

        function handleFileSelect(fileInput, fileNameElement, dropZone, isImage = false) {
            const errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            dropZone.parentElement.appendChild(errorElement);

            let imagePreview;
            if (isImage) {
                imagePreview = document.createElement('img');
                imagePreview.className = 'image-preview';
                dropZone.parentElement.insertBefore(imagePreview, dropZone);
            }

            function validateFile(file) {
                if (isImage) {
                    const maxSize = 15 * 1024 * 1024; // 15MB
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

                    if (!allowedTypes.includes(file.type)) {
                        errorElement.textContent = 'يرجى اختيار ملف صورة صالح (JPG, PNG)';
                        errorElement.style.display = 'block';
                        return false;
                    }
                    if (file.size > maxSize) {
                        errorElement.textContent = 'حجم الصورة يجب أن لا يتجاوز 5 ميجابايت';
                        errorElement.style.display = 'block';
                        return false;
                    }
                } else {
                    const systemType = systemTypeSelect.value;
                    if (systemType === 'hawai') {
                        if (!file.name.toLowerCase().endsWith('.pdf')) {
                            errorElement.textContent = 'يرجى اختيار ملف PDF صالح';
                            errorElement.style.display = 'block';
                            return false;
                        }
                    } else {
                        if (!/\.(csv|xlsx)$/i.test(file.name)) {
                            errorElement.textContent = 'يرجى اختيار ملف CSV أو Excel صالح';
                            errorElement.style.display = 'block';
                            return false;
                        }
                    }
                }
                errorElement.style.display = 'none';
                return true;
            }

            function handleFile(file) {
                if (!validateFile(file)) {
                    fileInput.value = '';
                    fileNameElement.style.display = 'none';
                    if (imagePreview) imagePreview.style.display = 'none';
                    return;
                }

                fileNameElement.textContent = file.name;
                fileNameElement.style.display = 'block';
                dropZone.style.borderColor = '#198754';

                if (isImage && imagePreview) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            }

            fileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    handleFile(this.files[0]);
                }
            });

            dropZone.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', function (e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('dragover');
                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    fileInput.files = e.dataTransfer.files;
                    handleFile(e.dataTransfer.files[0]);
                }
            });
        }

        handleFileSelect(csvFileInput, csvFileName, csvDropZone, false);
        handleFileSelect(cardDesignInput, imageFileName, imageDropZone, true);
    });
</script>

<?php include 'inc/footer.php'; ?>

<script>
    function validateCSVCount() {
        const csvFile = document.getElementById('csv_file').files[0];
        const remainingCards = <?php echo $_SESSION['remaining_cards']; ?>;

        // Excel files are counted securely on the server because they are binary files.
        if (csvFile && csvFile.name.toLowerCase().endsWith('.csv')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const text = e.target.result;
                const lines = text.split('\n').length - 1;

                if (lines > remainingCards) {
                    alert('عذراً، عدد السجلات في ملف CSV (' + lines + ') يتجاوز عدد الكروت المتبقية (' + remainingCards + ')');
                    return false;
                }
                document.querySelector('form').submit();
            };
            reader.readAsText(csvFile);
            return false;
        }
        return true;
    }
</script>
