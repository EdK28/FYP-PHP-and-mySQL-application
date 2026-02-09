<?php
session_start();
require_once '../config/db.php';
require_once '../verification/auth.php';
require_once '../verification/role_check.php';
check_role(['customer']);

$user_id = $_SESSION['user_id'];

// If session cart has items, create pending order
if(!empty($_SESSION['cart'])){
    $cart = $_SESSION['cart'];

    // Check existing pending order
    $stmt = $db->prepare("SELECT id FROM orders WHERE user_id = :user_id AND status='pending' LIMIT 1");
    $stmt->execute(['user_id'=>$user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if($order){
        $order_id = $order['id'];
    } else {
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (:user_id, 0, 'pending')");
        $stmt->execute(['user_id'=>$user_id]);
        $order_id = $db->lastInsertId();
    }

    // Insert items
    foreach($cart as $product_id => $qty){
        $stmt = $db->prepare("SELECT price FROM products WHERE id=:id");
        $stmt->execute(['id'=>$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$product) continue;

        $stmt = $db->prepare("SELECT id FROM order_items WHERE order_id=:order_id AND product_id=:product_id");
        $stmt->execute(['order_id'=>$order_id, 'product_id'=>$product_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if($item){
            $stmt = $db->prepare("UPDATE order_items SET quantity=quantity+:qty WHERE id=:id");
            $stmt->execute(['qty'=>$qty,'id'=>$item['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)");
            $stmt->execute([
                'order_id'=>$order_id,
                'product_id'=>$product_id,
                'quantity'=>$qty,
                'price'=>$product['price']
            ]);
        }
    }

    // Update total
    $stmt = $db->prepare("UPDATE orders SET total_price=(SELECT SUM(quantity*price) FROM order_items WHERE order_id=:order_id) WHERE id=:order_id");
    $stmt->execute(['order_id'=>$order_id]);

    unset($_SESSION['cart']);
}

// Handle checkout action (marks order Completed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'], $_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];

    // Update order status to Completed
    $stmt = $db->prepare("UPDATE orders SET status='completed' WHERE id=:order_id AND user_id=:user_id");
    $stmt->execute(['order_id'=>$order_id, 'user_id'=>$user_id]);

    $_SESSION['message'] = "Order #$order_id has been completed!";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Fetch all orders for display (only Completed and Cancelled)
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id=:user_id AND status IN ('pending','completed','cancelled') ORDER BY created_at DESC");
$stmt->execute(['user_id'=>$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders</title>

<!--Link to CSS file-->
<link rel="stylesheet" href="../assets/css/customer-order.css">
<link rel="stylesheet" href="../assets/css/customer-order-responsive.css">

</head>
<body>

<h2 style="font-size: 2rem; color: white">My Orders</h2>

<?php if(isset($_SESSION['message'])): ?>
<div class="success-message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<?php if(!$orders): ?>
<p style="font-size: 1.2rem; color:white">You have not placed any orders yet! <button class="ordernone" style="color:green; padding:10px; border-radius:15px; font-size:1em"><a style="color:red; text-decoration:none" href="../homepage.php">Go to shop</a></button></p>
<?php else: ?>
<?php foreach($orders as $order): ?>
<div class="order">
    <h3>Order #<?php echo $order['id']; ?> — <span class="status"><?php echo ucfirst($order['status']); ?></span></h3>
    <p>Placed on: <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
    <p>Total: $<?php echo number_format($order['total_price'], 2); ?></p>

    <?php
    $stmt_items = $db->prepare("SELECT oi.quantity, oi.price, p.name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id");
    $stmt_items->execute(['order_id' => $order['id']]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table class="order-items">
        <thead>
            <tr>
                <th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?php echo $item['name']; ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if($order['status'] === 'pending'): ?>
    <button onclick="openPaymentModal(<?= $order['id'] ?>)" class="pay-btn">Checkout</button>
    <form method="POST" action="cancel_order.php" style="margin-top:15px; display:inline-block;">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <button type="submit" class="cancel-btn">Cancel Order</button>
    </form>
    <?php elseif($order['status'] === 'cancelled'): ?>
    <div style="margin-top:15px; text-align:right;">
        <a href="../homepage.php"><button class="home-btn">Back to Shop</button></a>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Hidden checkout form for modal -->
<form id="modalCheckoutForm" method="POST" style="display:none;">
    <input type="hidden" name="checkout" value="1">
    <input type="hidden" name="order_id" id="modalOrderId" value="">
</form>

<!-- Payment Modal (Demo Only) -->
<div id="paymentModal" class="payment-modal">
    <div class="modal-content">
        <span class="close" onclick="closePaymentModal()">&times;</span>
        <h2>Payment Options</h2>

        <p style="font-size:0.9rem; color:#555;">This is a demo payment page. Selecting Pay Now will mark the order as Completed.</p>

        <div style="margin-top:15px; text-align:left;">
            <label><input type="radio" name="payment_method" value="credit_card" required> Credit Card</label><br>
            <label><input type="radio" name="payment_method" value="paypal" required> PayPal</label><br>
            <label><input type="radio" name="payment_method" value="cod" required> Cash on Delivery</label>
        </div>

        <div style="margin-top:20px; text-align:center;">
            <button type="button" class="pay-btn" onclick="mockPayment()">Pay Now</button>
            <button type="button" class="cancel-btn" onclick="closePaymentModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
// Open and close modal
function openPaymentModal(orderId) {
    document.getElementById('modalOrderId').value = orderId; // set order ID
    document.getElementById('paymentModal').style.display = 'flex';
}
function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// Submit hidden checkout form when Pay Now is clicked
function mockPayment() {
    const paymentMethods = document.getElementsByName('payment_method');
    let selected = false;
    for (let pm of paymentMethods) {
        if (pm.checked) { selected = true; break; }
    }
    if(!selected){
        alert("Please select a payment method!");
        return;
    }

    // Submit hidden form to mark order Completed
    document.getElementById('modalCheckoutForm').submit();
}

// Close modal if clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) closePaymentModal();
});
</script>

</body>
</html>
