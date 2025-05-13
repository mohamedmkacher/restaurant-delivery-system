<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="hero-section text-center py-5 mb-5" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/images/hero-bg.jpg') center/cover; color: white;">
    <div class="container py-5">
        <h1 class="display-4 mb-4">Welcome to FoodExpress</h1>
        <p class="lead mb-4">Your favorite restaurants, delivered to your doorstep.</p>
        <?php if (!isLoggedIn()): ?>
            <div class="d-flex justify-content-center gap-3">
                <a href="/auth/register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Sign Up
                </a>
                <a href="/auth/login.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        <?php else: ?>
            <a href="/client/menu.php" class="btn btn-primary btn-lg">
                <i class="fas fa-utensils me-2"></i>Browse Menu
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <!-- Features Section -->
    <section class="features-section mb-5">
        <h2 class="text-center mb-4">Why Choose FoodExpress?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-truck-fast fa-3x text-primary mb-3"></i>
                        <h3 class="card-title h5">Fast Delivery</h3>
                        <p class="card-text">Quick and reliable delivery service right to your doorstep.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-star fa-3x text-primary mb-3"></i>
                        <h3 class="card-title h5">Quality Food</h3>
                        <p class="card-text">Carefully selected restaurants offering the best quality meals.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                        <h3 class="card-title h5">Real-time Tracking</h3>
                        <p class="card-text">Track your order in real-time from kitchen to delivery.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section mb-5">
        <h2 class="text-center mb-4">How It Works</h2>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="text-center">
                    <div class="circle-step mb-3">1</div>
                    <h3 class="h5">Browse Menu</h3>
                    <p>Explore our wide selection of dishes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="circle-step mb-3">2</div>
                    <h3 class="h5">Place Order</h3>
                    <p>Select your favorite items and checkout</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="circle-step mb-3">3</div>
                    <h3 class="h5">Track Order</h3>
                    <p>Follow your order in real-time</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center">
                    <div class="circle-step mb-3">4</div>
                    <h3 class="h5">Enjoy!</h3>
                    <p>Receive and enjoy your meal</p>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.hero-section {
    margin-top: -1.5rem;
    padding: 100px 0;
}

.circle-step {
    width: 50px;
    height: 50px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0 auto;
}

.features-section .card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.features-section .card:hover {
    transform: translateY(-10px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.how-it-works-section {
    position: relative;
}

.how-it-works-section::before {
    content: '';
    position: absolute;
    top: 100px;
    left: 25%;
    right: 25%;
    height: 2px;
    background-color: var(--primary-color);
    z-index: -1;
}

@media (max-width: 768px) {
    .how-it-works-section::before {
        display: none;
    }
}
</style>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
