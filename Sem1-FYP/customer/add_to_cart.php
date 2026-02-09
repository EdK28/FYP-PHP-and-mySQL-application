<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$product_id = (int)($data['product_id'] ?? 0);
$quantity = (int)($data['quantity'] ?? 1);

if(!$product_id || $quantity < 1){
    echo json_encode(['status'=>0,'message'=>'Invalid data.']);
    exit;
}

if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if(isset($_SESSION['cart'][$product_id])){
    $_SESSION['cart'][$product_id] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = $quantity;
}

echo json_encode(['status'=>1,'message'=>"Added to cart!"]);
