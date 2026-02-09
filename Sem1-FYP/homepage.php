<?php
session_start();
require_once 'config/db.php';

$isLoggedIn = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$isCustomer = $isLoggedIn && $_SESSION['role'] === 'customer';
$username = $isLoggedIn ? $_SESSION['username'] : null;

// Initialize cart session if not set
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Fetch products
$stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop Homepage</title>
<!-- CSS File Links-->
<link rel="stylesheet" href="assets/main-homepage.css">
<link rel="stylesheet" href="assets/responsive-homepage.css">

</head>
<body>

<header class="navbar">
    <h1>La Bonne Merde</h1>
    
    <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    
    <nav id="navMenu">
        <?php if (!$isLoggedIn): ?>
            <a href="auth/login.php"><button class="btn btn-primary">Login</button></a>
            <a href="auth/signup.php"><button class="btn btn-secondary">Signup</button></a>

        <?php elseif ($isCustomer): ?>
            <span style="color:white; margin-right:15px;">
                Welcome, <a href="customer/dashboard.php"><?= htmlspecialchars($username) ?></a>
            </span>

            <button class="btn btn-secondary" id="view-orders-btn">View Orders (<span id="cart-count">0</span>)</button>

            <a href="auth/logout.php"><button class="btn btn-primary">Logout</button></a>
        <?php endif; ?>
    </nav>
</header>

<section class="product-container">
    <?php foreach ($products as $product): ?>
    <div class="product-card">
        <img src="assets/media/<?= htmlspecialchars($product['image']) ?>">
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p class="prod-description"><?= htmlspecialchars($product['description']) ?></p>
        <p class="price">RM <?= number_format($product['price'], 2) ?></p>

        <?php if (!$isLoggedIn): ?>
            <button class="shop-btn" onclick="showAuthPopup()">Add to Cart</button>
        <?php elseif ($isCustomer): ?>
            <!-- Customer -->
            <button class="shop-btn" onclick="addToCart(<?= $product['id'] ?>)">Add to Cart</button>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</section>

<?php if(!$isLoggedIn): ?>
<div id="authPopupBackdrop">
    <div id="authPopup">
        <span class="close" id="closePopup">&times;</span>
        <h2>Oiiiii...non-user detected! 😡</h2>
        <p>Login to continue</p>
        <div class="buttons">
            <a href="auth/login.php">Login</a>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="assets/shop.js"></script>
<script>
// Hamburger menu toggle
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');

if (hamburger) {
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const isClickInsideNav = navMenu.contains(event.target);
        const isClickOnHamburger = hamburger.contains(event.target);
        
        if (!isClickInsideNav && !isClickOnHamburger && navMenu.classList.contains('active')) {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });

    // Close menu when clicking on a nav link
    navMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });
}
</script>
</body>
</html>
