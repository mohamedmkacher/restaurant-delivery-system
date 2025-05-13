<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in and is a restaurant manager
checkRole(['restaurant_manager']);

$conn = connectDB();

// Get order statistics
$stats = [
    'pending' => 0,
    'confirmed' => 0,
    'preparing' => 0,
    'out_for_delivery' => 0,
    'delivered' => 0,
    'total_revenue' => 0
];

$result = $conn->query("
    SELECT status, COUNT(*) as count, SUM(total_amount) as revenue
    FROM orders
    GROUP BY status
");

while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
    if ($row['status'] === 'delivered') {
        $stats['total_revenue'] = $row['revenue'];
    }
}

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.username as client_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN users u ON o.client_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

// Get low stock menu items (for demonstration, we'll consider items that aren't available)
$low_stock = $conn->query("
    SELECT name, category
    FROM menu_items
    WHERE is_available = 0
    ORDER BY name
");

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Restaurant Dashboard</h1>
        <a href="menu.php" class="btn btn-primary">
            <i class="fas fa-utensils me-2"></i>Manage Menu
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h3 class="card-title h2 mb-3 text-warning"><?php echo $stats['pending']; ?></h3>
                    <p class="card-text text-muted mb-0">Pending Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h3 class="card-title h2 mb-3 text-info"><?php echo $stats['confirmed']; ?></h3>
                    <p class="card-text text-muted mb-0">Confirmed Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h3 class="card-title h2 mb-3 text-primary"><?php echo $stats['preparing']; ?></h3>
                    <p class="card-text text-muted mb-0">Preparing</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h3 class="card-title h2 mb-3 text-info"><?php echo $stats['out_for_delivery']; ?></h3>
                    <p class="card-text text-muted mb-0">Out for Delivery</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h3 class="card-title h2 mb-3 text-success"><?php echo $stats['delivered']; ?></h3>
                    <p class="card-text text-muted mb-0">Delivered</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h3 class="card-title h2 mb-3">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    <p class="card-text text-muted mb-0">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_orders->num_rows === 0): ?>
                        <p class="text-muted mb-0">No orders yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                            <td><?php echo $order['item_count']; ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
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
                                            </td>
                                            <td><?php echo date('M j, g:i A', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="orders.php" class="btn btn-sm btn-link">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Unavailable Items</h5>
                </div>
                <div class="card-body">
                    <?php if ($low_stock->num_rows === 0): ?>
                        <p class="text-muted mb-0">All items are currently available.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php while ($item = $low_stock->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                    </div>
                                    <span class="badge bg-danger">Unavailable</span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
