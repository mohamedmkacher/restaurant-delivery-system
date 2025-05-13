<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in and is a restaurant manager
checkRole(['restaurant_manager']);

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$order_id = (int)$_GET['order_id'];
$conn = connectDB();

$stmt = $conn->prepare("
    SELECT oi.*, mi.name 
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE oi.order_id = ?
    ORDER BY mi.name
");

$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'name' => $row['name'],
        'quantity' => $row['quantity'],
        'price_at_time' => $row['price_at_time']
    ];
}

echo json_encode($items);
?>
