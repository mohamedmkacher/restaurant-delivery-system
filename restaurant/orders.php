<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in and is a restaurant manager
checkRole(['restaurant_manager']);

$conn = connectDB();

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    validateToken($_POST['csrf_token']);
    
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        flash('Order status updated successfully!', 'success');
    } else {
        flash('Error updating order status.', 'danger');
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : 'all';

// Build query based on filters
$query = "
    SELECT o.*, u.username as client_name, 
           da.username as delivery_agent_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN users u ON o.client_id = u.id
    LEFT JOIN users da ON o.delivery_agent_id = da.id
    WHERE 1=1
";

if ($status_filter !== 'all') {
    $query .= " AND o.status = '$status_filter'";
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $query .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $query .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'this_week':
            $query .= " AND YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
            break;
        case 'this_month':
            $query .= " AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
            break;
    }
}

$query .= " ORDER BY o.created_at DESC";
$orders = $conn->query($query);

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <h1 class="mb-4">Order Management</h1>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="out_for_delivery" <?php echo $status_filter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="date" class="form-label">Date Range</label>
                    <select class="form-select" id="date" name="date">
                        <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?php echo $date_filter === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="this_week" <?php echo $date_filter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="this_month" <?php echo $date_filter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <?php if ($orders->num_rows === 0): ?>
                <p class="text-muted mb-0">No orders found matching the selected filters.</p>
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
                                <th>Delivery Agent</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['client_name']); ?>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" 
                                                data-bs-toggle="modal" data-bs-target="#orderDetailsModal"
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-delivery-address="<?php echo htmlspecialchars($order['delivery_address']); ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </td>
                                    <td><?php echo $order['item_count']; ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <form method="POST" action="" class="status-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select class="form-select form-select-sm status-select" name="status" 
                                                    style="width: 140px;" 
                                                    <?php echo $order['status'] === 'delivered' || $order['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                <option value="out_for_delivery" <?php echo $order['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php echo $order['delivery_agent_name'] ? htmlspecialchars($order['delivery_agent_name']) : 'Not assigned'; ?>
                                    </td>
                                    <td>
                                        <span title="<?php echo date('Y-m-d g:i A', strtotime($order['created_at'])); ?>">
                                            <?php echo date('M j, g:i A', strtotime($order['created_at'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary view-items"
                                                data-bs-toggle="modal" data-bs-target="#orderItemsModal"
                                                data-order-id="<?php echo $order['id']; ?>">
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

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Delivery Address</h6>
                <p class="delivery-address mb-0"></p>
            </div>
        </div>
    </div>
</div>

<!-- Order Items Modal -->
<div class="modal fade" id="orderItemsModal" tabindex="-1">
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
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
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
// Auto-submit status changes
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        if (confirm('Are you sure you want to update this order\'s status?')) {
            this.closest('form').submit();
        } else {
            // Reset to previous value if cancelled
            this.value = this.getAttribute('data-original-value');
        }
    });
    
    // Store original value for cancellation
    select.setAttribute('data-original-value', select.value);
});

// Load order details
document.getElementById('orderDetailsModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const deliveryAddress = button.dataset.deliveryAddress;
    this.querySelector('.delivery-address').textContent = deliveryAddress;
});

// Load order items
document.getElementById('orderItemsModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const orderId = button.dataset.orderId;
    const tbody = this.querySelector('tbody');
    
    // Clear previous items
    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';
    
    // Fetch order items
    fetch(`get_order_items.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(items => {
            tbody.innerHTML = items.map(item => `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">$${parseFloat(item.price_at_time).toFixed(2)}</td>
                    <td class="text-end">$${(item.quantity * item.price_at_time).toFixed(2)}</td>
                </tr>
            `).join('');
        })
        .catch(error => {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading items</td></tr>';
        });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
