<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in and is a delivery agent
checkRole(['delivery_agent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_delivered'])) {
    validateToken($_POST['csrf_token']);
    
    $order_id = (int)$_POST['order_id'];
    $delivery_agent_id = $_SESSION['user_id'];
    
    $conn = connectDB();
    
    // Verify this order belongs to the current delivery agent
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM orders 
        WHERE id = ? AND delivery_agent_id = ? AND status = 'out_for_delivery'
    ");
    $stmt->bind_param("ii", $order_id, $delivery_agent_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 1) {
        // Update order status to delivered
        $stmt = $conn->prepare("UPDATE orders SET status = 'delivered' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            flash('Order marked as delivered successfully!', 'success');
        } else {
            flash('Error updating order status.', 'danger');
        }
    } else {
        flash('Invalid order or unauthorized access.', 'danger');
    }
}

// Redirect back to dashboard
redirect('/delivery/dashboard.php');
?>
