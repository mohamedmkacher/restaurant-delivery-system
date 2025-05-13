<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle text-warning display-1 mb-4"></i>
                <h1 class="display-4 mb-3">Access Denied</h1>
                <p class="lead mb-4">Sorry, you don't have permission to access this page.</p>
                <?php if (isLoggedIn()): ?>
                    <p class="mb-4">
                        You are logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong><br>
                        Role: <strong><?php echo ucwords(str_replace('_', ' ', $_SESSION['role'])); ?></strong>
                    </p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <?php
                        switch ($_SESSION['role']) {
                            case 'client':
                                $redirect_url = '/client/menu.php';
                                break;
                            case 'restaurant_manager':
                                $redirect_url = '/restaurant/dashboard.php';
                                break;
                            case 'order_manager':
                                $redirect_url = '/order-manager/dashboard.php';
                                break;
                            case 'delivery_agent':
                                $redirect_url = '/delivery/dashboard.php';
                                break;
                            default:
                                $redirect_url = '/';
                        }
                        ?>
                        <a href="<?php echo $redirect_url; ?>" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Go to Dashboard
                        </a>
                        <a href="/auth/logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a href="/auth/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="/" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Go Home
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.error-content {
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
