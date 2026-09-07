<?php
require_once '../config/auth.php';
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

// تحسين استعلامات الإحصائيات
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
    'total_licenses' => $pdo->query("SELECT COUNT(*) FROM licenses")->fetchColumn(),
    'active_licenses' => $pdo->query("SELECT COUNT(*) FROM licenses WHERE status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())")->fetchColumn(),
    'total_cards_printed' => $pdo->query("SELECT COALESCE(SUM(cards_used), 0) FROM usage_log")->fetchColumn()
];

// تحسين استعلام النشاطات
$activities = $pdo->query("
    SELECT 
        u.username,
        l.license_key,
        ul.cards_used,
        ul.usage_date
    FROM usage_log ul
    JOIN licenses l ON ul.license_id = l.id
    JOIN users u ON l.user_id = u.id
    ORDER BY ul.usage_date DESC
    LIMIT 10
")->fetchAll();

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
                    <a href="packages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-box ms-2"></i>الباقات
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt ms-2"></i>تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-2">
                                <i class="fas fa-users"></i>
                            </div>
                            <h6 class="text-muted">المستخدمين</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-success mb-2">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h6 class="text-muted">التراخيص النشطة</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['active_licenses']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-info mb-2">
                                <i class="fas fa-key"></i>
                            </div>
                            <h6 class="text-muted">إجمالي التراخيص</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_licenses']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-warning mb-2">
                                <i class="fas fa-print"></i>
                            </div>
                            <h6 class="text-muted">الكروت المطبوعة</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_cards_printed']); ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>آخر النشاطات</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>المستخدم</th>
                                    <th><i class="fas fa-key me-2"></i>الترخيص</th>
                                    <th><i class="fas fa-print me-2"></i>عدد الكروت</th>
                                    <th><i class="fas fa-calendar me-2"></i>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                        <td><code><?php echo substr(htmlspecialchars($activity['license_key']), 0, 8) . '...'; ?></code>
                                        </td>
                                        <td><span
                                                class="badge bg-success"><?php echo number_format($activity['cards_used']); ?></span>
                                        </td>
                                        <td><small
                                                class="text-muted"><?php echo date('Y-m-d H:i', strtotime($activity['usage_date'])); ?></small>
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

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
    }

    .card {
        border: none;
        transition: transform 0.2s;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .bg-gradient {
        background: linear-gradient(to right, #ffffff, #f8f9fa);
    }

    .list-group-item-action:hover {
        background-color: #f8f9fa;
        transform: translateX(-5px);
        transition: all 0.2s;
    }

    .table td {
        vertical-align: middle;
    }
</style>

<?php include '../inc/footer.php'; ?>