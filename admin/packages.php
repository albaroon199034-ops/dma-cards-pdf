<?php
session_start();
require_once '../config/db.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$message = '';

// معالجة إضافة باقة جديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_package'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $cards_count = $_POST['cards_count'];

    try {
        $stmt = $pdo->prepare("INSERT INTO packages (name, price, duration, cards_count) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $price, $duration, $cards_count])) {
            $message = '<div class="alert alert-success">تمت إضافة الباقة بنجاح</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">حدث خطأ أثناء إضافة الباقة</div>';
    }
}

// معالجة تحديث باقة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_package'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $cards_count = $_POST['cards_count'];

    try {
        $stmt = $pdo->prepare("UPDATE packages SET name = ?, price = ?, duration = ?, cards_count = ? WHERE id = ?");
        if ($stmt->execute([$name, $price, $duration, $cards_count, $id])) {
            $message = '<div class="alert alert-success">تم تحديث الباقة بنجاح</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">حدث خطأ أثناء تحديث الباقة</div>';
    }
}

// معالجة حذف باقة
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">تم حذف الباقة بنجاح</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">حدث خطأ أثناء حذف الباقة</div>';
    }
}

// جلب جميع الباقات
try {
    $stmt = $pdo->query("SELECT * FROM packages ORDER BY price");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
    $message = '<div class="alert alert-danger">حدث خطأ أثناء جلب الباقات</div>';
}

?>

<?php include '../inc/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm admin-sidebar">
                <div class="card-header bg-gradient bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt ms-2"></i>لوحة التحكم</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-home ms-2"></i>الرئيسية
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users ms-2"></i>إدارة المستخدمين
                    </a>
                    <a href="licenses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-key ms-2"></i>إدارة التراخيص
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar ms-2"></i>التقارير
                    </a>
                    <a href="packages.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-box ms-2"></i>الباقات
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt ms-2"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <?php echo $message; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-plus ms-2"></i>إضافة باقة جديدة</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="addPackageForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">اسم الباقة</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">السعر (شيكل)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">مدة الصلاحية (بالأشهر)</label>
                                <input type="number" class="form-control" id="duration" name="duration" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cards_count" class="form-label">عدد الكروت المسموح به</label>
                                <input type="number" class="form-control" id="cards_count" name="cards_count" required>
                            </div>
                        </div>
                        <button type="submit" name="add_package" class="btn btn-primary">
                            <i class="fas fa-plus ms-2"></i>إضافة الباقة
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-gradient bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes ms-2"></i>قائمة الباقات</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>اسم الباقة</th>
                                    <th>السعر</th>
                                    <th>مدة الصلاحية</th>
                                    <th>عدد الكروت</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($packages as $pkg): ?>
                                    <tr data-package-id="<?php echo $pkg['id']; ?>">
                                        <td><?php echo htmlspecialchars($pkg['name']); ?></td>
                                        <td><?php echo number_format($pkg['price'], 2); ?> شيكل</td>
                                        <td><?php echo $pkg['duration']; ?>
                                            <?php echo $pkg['duration'] > 1 ? 'أشهر' : 'شهر'; ?></td>
                                        <td><?php echo $pkg['cards_count']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary"
                                                onclick="editPackage(<?php echo $pkg['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $pkg['id']; ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('هل أنت متأكد من حذف هذه الباقة؟')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة التعديل -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient bg-dark text-white">
                <h5 class="modal-title">تعديل الباقة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editPackageForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">اسم الباقة</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">السعر (شيكل)</label>
                        <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_duration" class="form-label">مدة الصلاحية (بالأشهر)</label>
                        <input type="number" class="form-control" id="edit_duration" name="duration" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cards_count" class="form-label">عدد الكروت المسموح به</label>
                        <input type="number" class="form-control" id="edit_cards_count" name="cards_count" required>
                    </div>
                    <button type="submit" name="update_package" class="btn btn-primary">
                        <i class="fas fa-save ms-2"></i>حفظ التغييرات
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>

<script>
    function editPackage(id) {
        // جلب بيانات الباقة المحددة
        const row = document.querySelector(`tr[data-package-id="${id}"]`);
        const name = row.querySelector('td:nth-child(1)').textContent;
        const price = row.querySelector('td:nth-child(2)').textContent.replace(' شيكل', '');
        const duration = row.querySelector('td:nth-child(3)').textContent.replace(' شهر', '');
        const cardsCount = row.querySelector('td:nth-child(4)').textContent;

        // تعبئة حقول النموذج
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit_duration').value = duration;
        document.getElementById('edit_cards_count').value = cardsCount;

        // فتح نافذة التعديل
        const modal = new bootstrap.Modal(document.getElementById('editModal'));
        modal.show();
    }
</script>