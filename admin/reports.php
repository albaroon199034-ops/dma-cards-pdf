<?php
require_once '../config/auth.php';
require_once '../config/db.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../index.php');
    exit();
}

// استعلام لإحصائيات النظام
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
    'total_cards' => $pdo->query("SELECT SUM(used_cards) FROM licenses")->fetchColumn(),
    'active_licenses' => $pdo->query("SELECT COUNT(*) FROM licenses WHERE used_cards < total_cards")->fetchColumn(),
    'expired_licenses' => $pdo->query("SELECT COUNT(*) FROM licenses WHERE used_cards >= total_cards")->fetchColumn()
];

// استعلام لأكثر المستخدمين نشاطاً
$top_users = $pdo->query("
    SELECT u.username, 
           COUNT(l.id) as total_licenses,
           SUM(l.used_cards) as total_cards_used
    FROM users u
    LEFT JOIN licenses l ON u.id = l.user_id
    WHERE u.is_admin = 0
    GROUP BY u.id
    ORDER BY total_cards_used DESC
    LIMIT 5
")->fetchAll();

// استعلام لآخر العمليات
$recent_activities = $pdo->query("
    SELECT u.username, l.license_key, ul.cards_used, ul.usage_date
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
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-2">
                                <i class="fas fa-users"></i>
                            </div>
                            <h6 class="text-muted">إجمالي المستخدمين</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-success mb-2">
                                <i class="fas fa-print"></i>
                            </div>
                            <h6 class="text-muted">الكروت المطبوعة</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_cards']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-info mb-2">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h6 class="text-muted">التراخيص النشطة</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['active_licenses']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-gradient shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-4 text-warning mb-2">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <h6 class="text-muted">التراخيص المنتهية</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['expired_licenses']); ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Users -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>أكثر المستخدمين نشاطاً</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>المستخدم</th>
                                    <th><i class="fas fa-key me-2"></i>عدد التراخيص</th>
                                    <th><i class="fas fa-print me-2"></i>الكروت المطبوعة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_users as $user): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo number_format($user['total_licenses']); ?></span></td>
                                    <td><span class="badge bg-success"><?php echo number_format($user['total_cards_used']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>آخر النشاطات</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>المستخدم</th>
                                    <th><i class="fas fa-key me-2"></i>مفتاح الترخيص</th>
                                    <th><i class="fas fa-print me-2"></i>عدد الكروت</th>
                                    <th><i class="fas fa-calendar me-2"></i>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_activities as $activity): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($activity['username']); ?></td>
                                    <td><code class="bg-light px-2 py-1 rounded"><?php echo substr($activity['license_key'], 0, 8) . '...'; ?></code></td>
                                    <td><span class="badge bg-success"><?php echo number_format($activity['cards_used']); ?></span></td>
                                    <td><small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($activity['usage_date'])); ?></small></td>
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