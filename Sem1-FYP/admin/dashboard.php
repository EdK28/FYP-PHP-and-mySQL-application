<?php
require_once '../verification/auth.php';
require_once '../verification/role_check.php';
check_role(['admin']); // Only admin can access
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<!-- Link to CSS file-->
<link rel="stylesheet" href="../assets/css/admin-dashboard.css">
<link rel="stylesheet" href="../assets/css/admin-responsive.css">
</head>
<body>
    <div class="hero-dashboard">
        <h1>Welcome, Admin! 😊</h1>
        <p>
            <a href="users.php"><button class="btn1">Manage Users</button></a> | 
            <a href="products.php"><button class="btn1">Manage Products</button></a> | 
            <a href="orders.php"><button class="btn1">View Orders</button></a> | 
            <a href="../auth/logout.php"><button class="btn2">Logout</button></a>
        </p>
    </div>
</body>
</html>
