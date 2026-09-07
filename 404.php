<?php include 'inc/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h1 class="display-1 text-danger mb-4">404</h1>
                    <h2 class="mb-4">عذراً، الصفحة غير موجودة</h2>
                    <p class="lead mb-5">الصفحة التي تبحث عنها غير موجودة أو تم نقلها</p>
                    <a href="index.php" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-home me-2"></i>
                        العودة للرئيسية
                    </a>
                </div>
            </div>
        </div>
    </div>
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
</style>

<?php include 'inc/footer.php'; ?>