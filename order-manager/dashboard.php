<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in and is an order manager
checkRole(['order_manager']);

$conn = connectDB();

// Handle order updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateToken($_POST['csrf_token']);
    
    if (isset($_POST['assign_delivery'])) {
        $order_id = (int)$_POST['order_id'];
        $delivery_agent_id = (int)$_POST['delivery_agent_id'];
        
        $stmt = $conn->prepare("
            UPDATE orders 
            SET delivery_agent_id = ?, status = 'out_for_delivery'
            WHERE id = ? AND status = 'preparing'
        ");
        $stmt->bind_param("ii", $delivery_agent_id, $order_id);
        
        if ($stmt->execute()) {
            flash('Delivery agent assigned successfully!', 'success');
        } else {
            flash('Error assigning delivery agent.', 'danger');
        }
    }
}

// Get order statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
        SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) as delivering,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM orders
")->fetch_assoc();

// Get active orders
$active_orders = $conn->query("
    SELECT o.*, 
           c.username as client_name,
           da.username as delivery_agent_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users da ON o.delivery_agent_id = da.id
    WHERE o.status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery')
    ORDER BY 
        CASE o.status
            WHEN 'pending' THEN 1
            WHEN 'confirmed' THEN 2
            WHEN 'preparing' THEN 3
            WHEN 'out_for_delivery' THEN 4
        END,
        o.created_at ASC
");

// Get available delivery agents
$delivery_agents = $conn->query("
    SELECT id, username,
           (SELECT COUNT(*) FROM orders 
            WHERE delivery_agent_id = users.id 
            AND status = 'out_for_delivery') as active_deliveries
    FROM users 
    WHERE role = 'delivery_agent'
    ORDER BY active_deliveries ASC, username
");

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <h1 class="mb-4">Order Management Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-warning mb-3">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h3 class="card-title h2"><?php echo $stats['pending']; ?></h3>
                    <p class="card-text text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-info mb-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h3 class="card-title h2"><?php echo $stats['confirmed']; ?></h3>
                    <p class="card-text text-muted mb-0">Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-primary mb-3">
                        <i class="fas fa-utensils fa-2x"></i>
                    </div>
                    <h3 class="card-title h2"><?php echo $stats['preparing']; ?></h3>
                    <p class="card-text text-muted mb-0">Preparing</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-warning mb-3">
                        <i class="fas fa-motorcycle fa-2x"></i>
                    </div>
                    <h3 class="card-title h2"><?php echo $stats['delivering']; ?></h3>
                    <p class="card-text text-muted mb-0">Delivering</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-success mb-3">
                        <i class="fas fa-check-double fa-2x"></i>
                    </div>
                    <h3 class="card-title h2"><?php echo $stats['delivered']; ?></h3>
                    <p class="card-text text-muted mb-0">Delivered</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="text-danger mb-3">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                    <h3 class="card-title h2"><?php echo $stats['cancelled']; ?></h3>
                    <p class="card-text text-muted mb-0">Cancelled</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Orders -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Active Orders</h5>
        </div>
        <div class="card-body">
            <?php if ($active_orders->num_rows === 0): ?>
                <p class="text-muted mb-0">No active orders at the moment.</p>
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
                                <th>Delivery Agent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $active_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['client_name']); ?>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" 
                                                data-bs-toggle="modal" data-bs-target="#addressModal"
                                                data-address="<?php echo htmlspecialchars($order['delivery_address']); ?>">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </button>
                                    </td>
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
                                                    echo 'warning';
                                                    break;
                                            }
                                        ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span title="<?php echo date('Y-m-d g:i A', strtotime($order['created_at'])); ?>">
                                            <?php 
                                            $minutes = round((time() - strtotime($order['created_at'])) / 60);
                                            echo $minutes . ' min ago';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order['status'] === 'preparing'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#assignDeliveryModal"
                                                    data-order-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-user-plus me-1"></i>Assign
                                            </button>
                                        <?php else: ?>
                                            <?php echo $order['delivery_agent_name'] ?: 'Not assigned'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#itemsModal"
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

<!-- Assign Delivery Modal -->
<div class="modal fade" id="assignDeliveryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                <input type="hidden" name="order_id" id="assignOrderId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Assign Delivery Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="delivery_agent_id" class="form-label">Select Delivery Agent</label>
                        <select class="form-select" id="delivery_agent_id" name="delivery_agent_id" required>
                            <option value="">Choose...</option>
                            <?php while ($agent = $delivery_agents->fetch_assoc()): ?>
                                <option value="<?php echo $agent['id']; ?>">
                                    <?php echo htmlspecialchars($agent['username']); ?>
                                    (<?php echo $agent['active_deliveries']; ?> active)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_delivery" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Assign
                    </button>
                </div>
            </form>
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

// Handle assign delivery modal
document.getElementById('assignDeliveryModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('assignOrderId').value = button.dataset.orderId;
});

// Load order items
document.getElementById('itemsModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const orderId = button.dataset.orderId;
    const tbody = this.querySelector('tbody');
    
    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';
    
    fetch(`../restaurant/get_order_items.php?order_id=${orderId}`)
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
