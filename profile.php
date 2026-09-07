<?php
require_once 'config/auth.php';
require_once 'config/db.php';

// جلب بيانات المستخدم
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(l.id) as total_licenses,
           SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active_licenses,
           SUM(l.total_cards) as total_cards,
           SUM(l.used_cards) as used_cards
    FROM users u
    LEFT JOIN licenses l ON u.id = l.user_id
    WHERE u.id = ?
    GROUP BY u.id, u.username, u.email, u.first_name, u.last_name, u.phone
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// معالجة تحديث البيانات الشخصية
if (isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // التحقق من عدم تكرار اسم المستخدم
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    if ($stmt->fetchColumn() > 0) {
        $error = "اسم المستخدم موجود مسبقاً";
    } else {
        $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?";
        $params = [$username, $email, $first_name, $last_name, $phone];

        if (!empty($_POST['new_password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $_SESSION['user_id'];

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $_SESSION['username'] = $username;
            $success = "تم تحديث البيانات بنجاح";
            header("Refresh:0");
        }
    }
}

include 'inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- بطاقة الملف الشخصي -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h4 class="mb-3"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user['username']); ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($user['phone']); ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-calendar me-2"></i>تاريخ التسجيل: <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                    </p>
                    <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-edit me-2"></i>تعديل البيانات الشخصية
                    </button>
                    <a href="logout.php" class="btn btn-danger mt-2">
                        <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <!-- إحصائيات المستخدم -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>إحصائيات النظام</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-primary"><?php echo $user['total_licenses'] ?? 0; ?></h3>
                                <p class="mb-0">إجمالي التراخيص</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-success"><?php echo $user['active_licenses'] ?? 0; ?></h3>
                                <p class="mb-0">التراخيص النشطة</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-info"><?php echo $user['total_cards'] ?? 0; ?></h3>
                                <p class="mb-0">إجمالي الكروت</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 text-center">
                                <h3 class="text-warning"><?php echo $user['used_cards'] ?? 0; ?></h3>
                                <p class="mb-0">الكروت المستخدمة</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-key me-2"></i>إدخال مفتاح ترخيص لطباعة الكروت
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول التراخيص -->
    <div class="card shadow-sm mt-4">
        <!-- ... كود جدول التراخيص ... -->
    </div>

    <!-- Modal تعديل البيانات الشخصية -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل البيانات الشخصية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">الاسم الأول</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الاسم الأخير</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">اسم المستخدم</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة (اتركها فارغة إذا لم ترد تغييرها)</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>