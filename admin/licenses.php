<?php
require_once '../config/auth.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

// إضافة معالجة حذف الترخيص
if (isset($_POST['delete_license'])) {
    $license_id = $_POST['license_id'];
    
    try {
        // بدء المعاملة
        $pdo->beginTransaction();
        
        // حذف سجلات الاستخدام المرتبطة أولاً
        $stmt = $pdo->prepare("DELETE FROM usage_log WHERE license_id = ?");
        $stmt->execute([$license_id]);
        
        // ثم حذف الترخيص
        $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
        $stmt->execute([$license_id]);
        
        // تأكيد المعاملة
        $pdo->commit();
        
        header('Location: licenses.php?msg=deleted');
        exit();
    } catch (PDOException $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $pdo->rollBack();
        header('Location: licenses.php?msg=error');
        exit();
    }
}

// إضافة ترخيص جديد
if (isset($_POST['add_license'])) {
    $user_id = $_POST['user_id'];
    $total_cards = $_POST['total_cards'];
    $expiry_date = $_POST['expiry_date'];
    $license_key = bin2hex(random_bytes(16)); // إنشاء مفتاح عشوائي

    $stmt = $pdo->prepare("INSERT INTO licenses (license_key, user_id, total_cards, expiry_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$license_key, $user_id, $total_cards, $expiry_date]);
    header('Location: licenses.php?msg=added');
    exit();
}

// تعليق/تفعيل ترخيص
if (isset($_POST['toggle_license'])) {
    $license_id = $_POST['license_id'];
    $new_status = $_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE licenses SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $license_id]);
    header('Location: licenses.php?msg=updated');
    exit();
}

// جلب قائمة المستخدمين للقائمة المنسدلة
$users = $pdo->query("SELECT id, username FROM users WHERE is_admin = 0")->fetchAll();

// جلب قائمة التراخيص
$licenses = $pdo->query("
    SELECT l.*, u.username, u.email,
           (l.total_cards - l.used_cards) as remaining_cards
    FROM licenses l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
")->fetchAll();
?>

<?php include '../inc/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
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
                    <a href="packages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box ms-2"></i>الباقات
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt ms-2"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Add License Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>إضافة ترخيص جديد</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">المستخدم</label>
                            <select name="user_id" class="form-select form-select-lg">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">عدد الكروت</label>
                            <input type="number" name="total_cards" class="form-control form-control-lg" required min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">تاريخ الانتهاء</label>
                            <input type="date" name="expiry_date" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_license" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-plus me-2"></i>إضافة ترخيص
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Licenses List Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>قائمة التراخيص</h5>
                </div>
                <div class="card-body">
                    <?php
                    // إضافة معالجة حذف الترخيص بعد الكود الموجود في بداية الملف
                    if (isset($_POST['delete_license'])) {
                        $license_id = $_POST['license_id'];
                        $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
                        $stmt->execute([$license_id]);
                        header('Location: licenses.php?msg=deleted');
                        exit();
                    }

                    // تعديل رسالة النجاح لتشمل حالة الحذف
                    if (isset($_GET['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php
                            // تعديل رسالة الخطأ في switch
                            switch ($_GET['msg']) {
                                case 'added':
                                    echo 'تم إضافة الترخيص بنجاح';
                                    break;
                                case 'updated':
                                    echo 'تم تحديث حالة الترخيص بنجاح';
                                    break;
                                case 'deleted':
                                    echo 'تم حذف الترخيص بنجاح';
                                    break;
                                case 'error':
                                    echo 'حدث خطأ أثناء حذف الترخيص';
                                    break;
                                case 'email_sent':
                                    echo 'تم إرسال مفتاح الترخيص بنجاح إلى البريد الإلكتروني';
                                    break;
                                case 'email_error':
                                    echo 'حدث خطأ أثناء إرسال البريد الإلكتروني';
                                    break;
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>المستخدم</th>
                                    <th><i class="fas fa-key me-2"></i>مفتاح الترخيص</th>
                                    <th><i class="fas fa-print me-2"></i>الكروت المطبوعة</th>
                                    <th><i class="fas fa-credit-card me-2"></i>الكروت المتبقية</th>
                                    <th><i class="fas fa-calendar me-2"></i>تاريخ الانتهاء</th>
                                    <th><i class="fas fa-info-circle me-2"></i>الحالة</th>
                                    <th><i class="fas fa-cogs me-2"></i>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($licenses as $license): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($license['username']); ?></td>
                                        <td><code class="bg-light px-2 py-1 rounded"><?php echo $license['license_key']; ?></code></td>
                                        <td>
                                            <?php
                                            echo '<span class="badge bg-primary">' . $license['used_cards'] . '</span>';
                                            ?>
                                        </td>
                                        <td><span class="badge bg-info"><?php echo number_format($license['remaining_cards']); ?></span></td>
                                        <td><?php echo date('Y-m-d', strtotime($license['expiry_date'])); ?></td>
                                        <td>
                                            <?php
                                            $is_expired = strtotime($license['expiry_date']) < time();
                                            
                                            if ($license['status'] == 'suspended') {
                                                $status_class = 'bg-warning';
                                                $status_text = 'معلق';
                                            } elseif ($is_expired) {
                                                $status_class = 'bg-danger';
                                                $status_text = 'منتهي';
                                            } else {
                                                $status_class = 'bg-success';
                                                $status_text = 'نشط';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        
                                        <td>
                                            <div class="btn-group">
                                                <form method="POST" action="send_license_email.php" class="d-inline">
                                                    <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                                    <button type="submit" name="send_email" class="btn btn-info btn-sm ms-1" title="إرسال بالبريد">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $license['status'] == 'active' ? 'suspended' : 'active'; ?>">
                                                    <button type="submit" name="toggle_license" class="ms-1 btn btn-<?php echo $license['status'] == 'active' ? 'warning' : 'success'; ?> btn-sm">
                                                        <i class="fas fa-<?php echo $license['status'] == 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline ms-1" onsubmit="return confirm('هل أنت متأكد من حذف هذا الترخيص؟');">
                                                    <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                                    <button type="submit" name="delete_license" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
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

<?php include '../inc/footer.php'; ?>