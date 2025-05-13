<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    flash('Please login to access the cart', 'warning');
    redirect('/auth/login.php');
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateToken($_POST['csrf_token']);
    
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $item_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $_SESSION['cart'][$item_id]['quantity'] = $quantity;
            } else {
                unset($_SESSION['cart'][$item_id]);
            }
        }
        flash('Cart updated successfully!', 'success');
    } elseif (isset($_POST['remove_item'])) {
        $item_id = $_POST['item_id'];
        unset($_SESSION['cart'][$item_id]);
        flash('Item removed from cart', 'success');
    } elseif (isset($_POST['place_order']) && !empty($_SESSION['cart'])) {
        $conn = connectDB();
        
        try {
            $conn->begin_transaction();
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }
            
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (client_id, status, total_amount, delivery_address)
                VALUES (?, 'pending', ?, ?)
            ");
            $client_id = $_SESSION['user_id'];
            $delivery_address = sanitize($_POST['delivery_address']);
            $stmt->bind_param("ids", $client_id, $total_amount, $delivery_address);
            $stmt->execute();
            
            $order_id = $conn->insert_id;
            
            // Create order items
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_time)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($_SESSION['cart'] as $item_id => $item) {
                $stmt->bind_param("iiid", $order_id, $item_id, $item['quantity'], $item['price']);
                $stmt->execute();
            }
            
            $conn->commit();
            
            // Clear cart after successful order
            $_SESSION['cart'] = [];
            
            flash('Order placed successfully! You can track it in your orders.', 'success');
            redirect('/client/orders.php');
            
        } catch (Exception $e) {
            $conn->rollback();
            flash('Error placing order. Please try again.', 'danger');
        }
    }
}

// Calculate cart totals
$subtotal = 0;
$delivery_fee = 5.00; // Fixed delivery fee
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal + $delivery_fee;

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <h1 class="mb-4">Shopping Cart</h1>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-info">
            <i class="fas fa-shopping-cart me-2"></i>Your cart is empty.
            <a href="menu.php" class="alert-link">Browse our menu</a> to add items.
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="" class="card mb-4">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                        
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $item_id => $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="text-center">
                                            <input type="number" name="quantity[<?php echo $item_id; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="0" max="10" class="form-control form-control-sm d-inline-block" 
                                                   style="width: 80px;">
                                        </td>
                                        <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                                <button type="submit" name="remove_item" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="text-end mt-3">
                            <a href="menu.php" class="btn btn-link">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                            <button type="submit" name="update_cart" class="btn btn-secondary">
                                <i class="fas fa-sync me-2"></i>Update Cart
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Order Summary</h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee:</span>
                            <span>$<?php echo number_format($delivery_fee, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong>$<?php echo number_format($total, 2); ?></strong>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Delivery Address</label>
                                <textarea name="delivery_address" id="delivery_address" class="form-control" 
                                          rows="3" required></textarea>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-primary w-100">
                                <i class="fas fa-shopping-bag me-2"></i>Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
