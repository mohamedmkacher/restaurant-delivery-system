<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    flash('Please login to view your orders', 'warning');
    redirect('/auth/login.php');
}

$conn = connectDB();

// Get user's orders with delivery agent info
$stmt = $conn->prepare("
    SELECT o.*, 
           u.username as delivery_agent_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.delivery_agent_id = u.id
    WHERE o.client_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result();

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <h1 class="mb-4">My Orders</h1>

    <?php if ($orders->num_rows === 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>You haven't placed any orders yet.
            <a href="menu.php" class="alert-link">Browse our menu</a> to place your first order.
        </div>
    <?php else: ?>
        <div class="row">
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>
                                Order #<?php echo $order['id']; ?>
                            </span>
                            <span class="badge bg-<?php
                                switch ($order['status']) {
                                    case 'pending':
                                        echo 'warning';
                                        break;
                                    case 'confirmed':
                                        echo 'info';
                                        break;
                                    case 'preparing':
                                        echo 'primary';
                                        break;
                                    case 'out_for_delivery':
                                        echo 'info';
                                        break;
                                    case 'delivered':
                                        echo 'success';
                                        break;
                                    case 'cancelled':
                                        echo 'danger';
                                        break;
                                }
                            ?>">
                                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="card-subtitle mb-2 text-muted">Order Details</h6>
                                <p class="card-text">
                                    <strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?><br>
                                    <strong>Items:</strong> <?php echo $order['item_count']; ?><br>
                                    <strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?><br>
                                    <strong>Delivery Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                                </p>
                            </div>

                            <?php if ($order['status'] !== 'pending' && $order['status'] !== 'cancelled'): ?>
                                <div class="order-timeline mb-3">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php
                                            switch ($order['status']) {
                                                case 'confirmed':
                                                    echo '25%';
                                                    break;
                                                case 'preparing':
                                                    echo '50%';
                                                    break;
                                                case 'out_for_delivery':
                                                    echo '75%';
                                                    break;
                                                case 'delivered':
                                                    echo '100%';
                                                    break;
                                            }
                                        ?>"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="text-muted">Confirmed</small>
                                        <small class="text-muted">Preparing</small>
                                        <small class="text-muted">Out for Delivery</small>
                                        <small class="text-muted">Delivered</small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($order['status'] === 'out_for_delivery' && $order['delivery_agent_id']): ?>
                                <div class="delivery-info">
                                    <h6 class="card-subtitle mb-2 text-muted">Delivery Information</h6>
                                    <p class="card-text">
                                        <i class="fas fa-motorcycle me-2"></i>
                                        Your order is being delivered by <?php echo htmlspecialchars($order['delivery_agent_name']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Order Items -->
                            <?php
                            $stmt = $conn->prepare("
                                SELECT oi.*, mi.name 
                                FROM order_items oi
                                JOIN menu_items mi ON oi.menu_item_id = mi.id
                                WHERE oi.order_id = ?
                            ");
                            $stmt->bind_param("i", $order['id']);
                            $stmt->execute();
                            $items = $stmt->get_result();
                            ?>
                            <div class="order-items">
                                <h6 class="card-subtitle mb-2 text-muted">Order Items</h6>
                                <ul class="list-group list-group-flush">
                                    <?php while ($item = $items->fetch_assoc()): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            <span>
                                                <?php echo $item['quantity']; ?> × 
                                                $<?php echo number_format($item['price_at_time'], 2); ?>
                                            </span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.order-timeline {
    padding: 10px 0;
}

.progress {
    height: 5px !important;
    background-color: #e9ecef;
}

.card {
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.list-group-item {
    background-color: transparent;
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
