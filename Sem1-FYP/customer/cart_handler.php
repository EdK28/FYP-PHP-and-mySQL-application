<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['authenticated']) || $_SESSION['role'] !== 'customer'){
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
if($product_id <= 0){
    echo json_encode(['success'=>false, 'message'=>'Invalid product']);
    exit;
}

// Add to session cart
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if(isset($_SESSION['cart'][$product_id])){
    $_SESSION['cart'][$product_id]++;
} else {
    $_SESSION['cart'][$product_id] = 1;
}

echo json_encode([
    'success'=>true,
    'cartCount'=>array_sum($_SESSION['cart'])
]);
