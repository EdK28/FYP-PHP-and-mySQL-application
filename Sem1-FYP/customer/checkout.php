<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

require_once '../config/db.php';
require_once '../verification/auth.php';
require_once '../verification/role_check.php';

// Only customers can access
check_role(['customer']);

// Ensure cart exists
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['message'] = "Your cart is empty!";
    header('Location: shop.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart']; // array: product_id => quantity

$total_price = 0;

// Calculate total price and validate stock
foreach ($cart as $product_id => $quantity) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['message'] = "Product ID $product_id does not exist!";
        header('Location: shop.php');
        exit;
    }

    if ($product['stock'] < $quantity) {
        $_SESSION['message'] = "Not enough stock for {$product['name']}!";
        header('Location: shop.php');
        exit;
    }

    $total_price += $product['price'] * $quantity;
}

// Insert new order
$stmt = $db->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (:user_id, :total_price, 'pending')");
$stmt->execute([
    'user_id' => $user_id,
    'total_price' => $total_price
]);

$order_id = $db->lastInsertId();

// Insert order items and update stock
foreach ($cart as $product_id => $quantity) {
    $stmt = $db->prepare("SELECT price FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
    $stmt->execute([
        'order_id' => $order_id,
        'product_id' => $product_id,
        'quantity' => $quantity,
        'price' => $product['price']
    ]);

    // Reduce stock
    $stmt = $db->prepare("UPDATE products SET stock = stock - :quantity WHERE id = :id");
    $stmt->execute([
        'quantity' => $quantity,
        'id' => $product_id
    ]);
}

// Clear cart
unset($_SESSION['cart']);

$_SESSION['message'] = "Order #$order_id placed successfully!";
header('Location: orders.php');
exit;
?>
