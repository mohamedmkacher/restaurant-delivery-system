<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in and is a delivery agent
checkRole(['delivery_agent']);

$conn = connectDB();

// Get assigned deliveries
$stmt = $conn->prepare("
    SELECT o.*, 
           u.username as client_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN users u ON o.client_id = u.id
    WHERE o.delivery_agent_id = ? 
    AND o.status IN ('out_for_delivery', 'delivered')
    ORDER BY 
        CASE o.status
            WHEN 'out_for_delivery' THEN 1
            WHEN 'delivered' THEN 2
        END,
        o.created_at DESC
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$deliveries = $stmt->get_result();

// Get delivery statistics
$stats = [
    'total_deliveries' => 0,
    'completed_today' => 0,
    'in_progress' => 0,
    'total_earnings' => 0
];

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN status = 'delivered' AND DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'delivered' THEN total_amount * 0.1 ELSE 0 END) as total_earnings
    FROM orders 
    WHERE delivery_agent_id = ?
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <h1 class="mb-4">Delivery Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-truck-fast fa-2x text-primary mb-3"></i>
                    <h3 class="card-title h2"><?php echo $stats['total_deliveries']; ?></h3>
                    <p class="card-text text-muted mb-0">Total Deliveries</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                    <h3 class="card-title h2"><?php echo $stats['completed_today']; ?></h3>
                    <p class="card-text text-muted mb-0">Completed Today</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-motorcycle fa-2x text-warning mb-3"></i>
                    <h3 class="card-title h2"><?php echo $stats['in_progress']; ?></h3>
                    <p class="card-text text-muted mb-0">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-dollar-sign fa-2x text-success mb-3"></i>
                    <h3 class="card-title h2">$<?php echo number_format($stats['total_earnings'], 2); ?></h3>
                    <p class="card-text text-muted mb-0">Total Earnings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Deliveries -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Current Deliveries</h5>
        </div>
        <div class="card-body">
            <?php if ($deliveries->num_rows === 0): ?>
                <p class="text-muted mb-0">No active deliveries at the moment.</p>
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
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($delivery = $deliveries->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $delivery['id']; ?></td>
                                    <td><?php echo htmlspecialchars($delivery['client_name']); ?></td>
                                    <td><?php echo $delivery['item_count']; ?></td>
                                    <td>$<?php echo number_format($delivery['total_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($delivery['status'] === 'out_for_delivery'): ?>
                                            <span class="badge bg-warning">Out for Delivery</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Delivered</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link" 
                                                data-bs-toggle="modal" data-bs-target="#addressModal"
                                                data-address="<?php echo htmlspecialchars($delivery['delivery_address']); ?>">
                                            View Address
                                        </button>
                                    </td>
                                    <td>
                                        <?php if ($delivery['status'] === 'out_for_delivery'): ?>
                                            <form method="POST" action="update_delivery.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $delivery['id']; ?>">
                                                <button type="submit" name="mark_delivered" class="btn btn-sm btn-success"
                                                        onclick="return confirm('Mark this order as delivered?')">
                                                    <i class="fas fa-check me-1"></i>Mark Delivered
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#itemsModal"
                                                data-order-id="<?php echo $delivery['id']; ?>">
                                            <i class="fas fa-list"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delivery Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="deliveryAddress"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="openMaps()">
                    <i class="fas fa-map-marker-alt me-2"></i>Open in Maps
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Items Modal -->
<div class="modal fade" id="itemsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Items will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentAddress = '';

// Handle address modal
document.getElementById('addressModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    currentAddress = button.dataset.address;
    document.getElementById('deliveryAddress').textContent = currentAddress;
});

// Open address in maps
function openMaps() {
    const encodedAddress = encodeURIComponent(currentAddress);
    window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank');
}

// Load order items
document.getElementById('itemsModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const orderId = button.dataset.orderId;
    const tbody = this.querySelector('tbody');
    
    tbody.innerHTML = '<tr><td colspan="2" class="text-center">Loading...</td></tr>';
    
    fetch(`../restaurant/get_order_items.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(items => {
            tbody.innerHTML = items.map(item => `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-center">${item.quantity}</td>
                </tr>
            `).join('');
        })
        .catch(error => {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Error loading items</td></tr>';
        });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
