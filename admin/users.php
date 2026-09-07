<?php
require_once '../config/auth.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

// معالجة تعديل المستخدم
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['edit_user_id'];
    $first_name = trim($_POST['edit_first_name']);
    $last_name = trim($_POST['edit_last_name']);
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);
    $phone = trim($_POST['edit_phone']);

    // التحقق من وجود اسم المستخدم
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: users.php?msg=username_exists');
        exit();
    }

    // التحقق من وجود البريد الإلكتروني
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: users.php?msg=email_exists');
        exit();
    }

    // تحديث البيانات الأساسية
    $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ? WHERE id = ?";
    $params = [$username, $email, $first_name, $last_name, $phone, $user_id];

    // إذا تم إدخال كلمة مرور جديدة
    if (!empty($_POST['edit_password'])) {
        $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?, password = ? WHERE id = ?";
        $params = [$username, $email, $first_name, $last_name, $phone, $password, $user_id];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    header('Location: users.php?msg=updated');
    exit();
}

// حذف مستخدم
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0")->execute([$user_id]);
    header('Location: users.php?msg=deleted');
    exit();
}

// جلب قائمة المستخدمين
$users = $pdo->query("
    SELECT u.*, 
           COUNT(l.id) as total_licenses,
           SUM(CASE WHEN l.status = 'active' THEN 1 ELSE 0 END) as active_licenses
    FROM users u
    LEFT JOIN licenses l ON u.id = l.user_id
    WHERE u.is_admin = 0
    GROUP BY u.id
    ORDER BY u.created_at DESC
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
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt ms-2"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm">
                <?php
                // تحديث معالجة إضافة مستخدم جديد
                if (isset($_POST['add_user'])) {
                    $first_name = trim($_POST['first_name']);
                    $last_name = trim($_POST['last_name']);
                    $username = trim($_POST['username']);
                    $email = trim($_POST['email']);
                    $phone = trim($_POST['phone']);
                
                    // التحقق من اسم المستخدم والبريد الإلكتروني
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetchColumn() > 0) {
                        header('Location: users.php?msg=username_exists');
                        exit();
                    }
                
                    // التحقق من وجود البريد الإلكتروني
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        header('Location: users.php?msg=email_exists');
                        exit();
                    }
                
                    // إضافة المستخدم الجديد
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, phone, password, is_admin) VALUES (?, ?, ?, ?, ?, ?, 0)");
                    $stmt->execute([$first_name, $last_name, $username, $email, $phone, $password]);
                    header('Location: users.php?msg=added');
                    exit();
                }
                ?>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>إضافة مستخدم جديد</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">الاسم الأول</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">الاسم الأخير</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">رقم الهاتف</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">اسم المستخدم</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">كلمة المرور</label>
                                <input type="text" name="password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="add_user" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>إضافة مستخدم
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-<?php echo in_array($_GET['msg'], ['added', 'deleted', 'updated']) ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo in_array($_GET['msg'], ['added', 'deleted', 'updated']) ? 'check' : 'exclamation'; ?>-circle me-2"></i>
                        <?php
                        switch ($_GET['msg']) {
                            case 'added':
                                echo 'تم إضافة المستخدم بنجاح';
                                break;
                            case 'updated':
                                echo 'تم تحديث بيانات المستخدم بنجاح';
                                break;
                            case 'deleted':
                                echo 'تم حذف المستخدم بنجاح';
                                break;
                            case 'username_exists':
                                echo 'اسم المستخدم موجود مسبقاً';
                                break;
                            case 'email_exists':
                                echo 'البريد الإلكتروني مستخدم مسبقاً';
                                break;
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>إدارة المستخدمين
                        </h5>
                        <span class="badge bg-primary">
                            إجمالي المستخدمين: <?php echo count($users); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>تم حذف المستخدم بنجاح
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>اسم المستخدم</th>
                                    <th><i class="fas fa-envelope me-2"></i>البريد الإلكتروني</th>
                                    <th><i class="fas fa-check-circle me-2"></i>التراخيص النشطة</th>
                                    <th><i class="fas fa-key me-2"></i>إجمالي التراخيص</th>
                                    <th><i class="fas fa-calendar me-2"></i>تاريخ التسجيل</th>
                                    <th><i class="fas fa-cogs me-2"></i>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </a>
                                        </td>
                                        <td><span class="badge bg-success"><?php echo $user['active_licenses']; ?></span></td>
                                        <td><span class="badge bg-primary"><?php echo $user['total_licenses']; ?></span></td>
                                        <td><small class="text-muted"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></small></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editUser<?php echo $user['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal تعديل المستخدم -->
                                    <div class="modal fade" id="editUser<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تعديل بيانات المستخدم</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="edit_user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">الاسم الأول</label>
                                                            <input type="text" name="edit_first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">الاسم الأخير</label>
                                                            <input type="text" name="edit_last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">اسم المستخدم</label>
                                                            <input type="text" name="edit_username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">البريد الإلكتروني</label>
                                                            <input type="email" name="edit_email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">رقم الهاتف</label>
                                                            <input type="tel" name="edit_phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">كلمة المرور الجديدة (اتركها فارغة إذا لم ترد تغييرها)</label>
                                                            <input type="text" name="edit_password" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" name="edit_user" class="btn btn-primary">حفظ التغييرات</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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