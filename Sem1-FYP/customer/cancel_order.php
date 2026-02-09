<?php
session_start();

require_once '../config/db.php';
require_once '../verification/auth.php';
require_once '../verification/role_check.php';

check_role(['customer']);

if (!isset($_POST['order_id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int) $_POST['order_id'];
$user_id  = $_SESSION['user_id'];

/* Verify order belongs to user & is pending */
$stmt = $db->prepare("
    SELECT * FROM orders 
    WHERE id = :id AND user_id = :user_id AND status = 'pending'
");
$stmt->execute([
    'id' => $order_id,
    'user_id' => $user_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['message'] = "Order cannot be cancelled.";
    header('Location: orders.php');
    exit;
}

/* Restore stock */
$stmt = $db->prepare("
    SELECT product_id, quantity 
    FROM order_items 
    WHERE order_id = :order_id
");
$stmt->execute(['order_id' => $order_id]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    $stmt = $db->prepare("
        UPDATE products 
        SET stock = stock + :qty 
        WHERE id = :pid
    ");
    $stmt->execute([
        'qty' => $item['quantity'],
        'pid' => $item['product_id']
    ]);
}

/* Update order status */
$stmt = $db->prepare("
    UPDATE orders 
    SET status = 'cancelled' 
    WHERE id = :id
");
$stmt->execute(['id' => $order_id]);

$_SESSION['message'] = "Order #$order_id cancelled successfully.";
header('Location: orders.php');
exit;
