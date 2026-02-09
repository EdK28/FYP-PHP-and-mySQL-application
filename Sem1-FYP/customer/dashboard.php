<?php
require_once '../verification/auth.php';
require_once '../verification/role_check.php';
require_once '../config/db.php';
check_role(['customer']); // Only customers

// Fetch current user info
$stmt = $db->prepare("SELECT username, email FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard</title>

<!-- Link to CSS file -->
<link rel="stylesheet" href="../assets/css/customer-dashboard.css">
<link rel="stylesheet" href="../assets/css/customer-dashboard-responsive.css">

</head>
<body>
    <div class="hero-dashboard">
        <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        <p>
            <a href="../homepage.php"><button class="btn1">Shop Products</button></a> | 
            <a href="orders.php"><button class="btn1">View My Order</button></a> | 
            <a href="../auth/logout.php"><button class="btn2">Logout</button></a>
        </p>
    </div>
</body>
</html>
