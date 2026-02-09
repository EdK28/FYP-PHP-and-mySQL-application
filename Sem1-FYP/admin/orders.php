<?php
require_once '../verification/auth.php';
require_once '../verification/role_check.php';
require_once '../config/db.php';
check_role(['admin']); // Only admin can access

// Constants for pagination
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Handle cancel order action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];

    // Only allow cancelling
    if ($status === 'Cancelled') {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        header("Location: orders.php?page=$page");
        exit;
    }
}

// Search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

// Validate sort column
$allowedSort = ['created_at','total_price','status','order_id'];
if (!in_array($sortColumn, $allowedSort)) $sortColumn = 'created_at';

// Build query with search/filter
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Apply status filter if selected
if ($statusFilter !== '' && in_array($statusFilter,['Cancelled','Completed'])) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

// **Always show only Completed or Cancelled orders**
$where[] = "o.status IN ('Completed','Cancelled')";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total orders for pagination
$countStmt = $db->prepare("
    SELECT COUNT(*) as total
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $whereSQL
");
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Fetch orders with limit
$stmt = $db->prepare("
    SELECT o.id AS order_id, o.total_price, o.status, o.created_at, u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $whereSQL
    ORDER BY $sortColumn $sortOrder
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to fetch order items
function getOrderItems($db, $orderId) {
    $stmt = $db->prepare("
        SELECT oi.product_id, p.name, oi.quantity, oi.price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Orders</title>
<!-- Link to CSS -->
<link rel="stylesheet" href="../assets/css/order-manager.css">
<link rel="stylesheet" href="../assets/css/order-responsive.css">

<script>
function toggleDetails(orderId) {
    const row = document.getElementById('items-' + orderId);
    
    if (row.style.display === 'table-row') {
        row.style.setProperty('display', 'none', 'important');
    } else {
        row.style.setProperty('display', 'table-row', 'important');
    }
}
</script>
</head>
<body>
<h1 style="text-align: center";>Order Manager</h1>
<p style="text-align: center";><a href="dashboard.php"><button class="btn-nav">Back to Dashboard</button></a> | <a href="../auth/logout.php"><button class="btn-nav">Logout</a></button></p>

<div class="filter">
<form method="GET">
    <input type="text" name="search" placeholder="Search Order ID, Username, Email" value="<?= htmlspecialchars($search) ?>">
    <select name="status_filter">
        <option value="">All Statuses</option>
        <option value="Completed" <?= $statusFilter==='Completed'?'selected':'' ?>>Completed</option>
        <option value="Cancelled" <?= $statusFilter==='Cancelled'?'selected':'' ?>>Cancelled</option>
    </select>
    <button type="submit" class="btn-filter">Filter</button>
</form>
</div>

<table>
    <thead>
        <tr>
            <th><a href="?<?= http_build_query(array_merge($_GET,['sort'=>'order_id','order'=>($sortColumn=='order_id' && $sortOrder=='ASC')?'desc':'asc'])) ?>">Order ID</a></th>
            <th>User</th>
            <th>Email</th>
            <th><a href="?<?= http_build_query(array_merge($_GET,['sort'=>'total_price','order'=>($sortColumn=='total_price' && $sortOrder=='ASC')?'desc':'asc'])) ?>">Total Price</a></th>
            <th>Status</th>
            <th><a href="?<?= http_build_query(array_merge($_GET,['sort'=>'created_at','order'=>($sortColumn=='created_at' && $sortOrder=='ASC')?'desc':'asc'])) ?>">Created At</a></th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= htmlspecialchars($order['order_id']); ?></td>
            <td><?= htmlspecialchars($order['username']); ?></td>
            <td><?= htmlspecialchars($order['email']); ?></td>
            <td>$<?= number_format($order['total_price'],2); ?></td>
            <td>
                <span class="status-<?= ucfirst($order['status']) ?>"><?= ucfirst($order['status']) ?></span>
            </td>
            <td><?= htmlspecialchars($order['created_at']); ?></td>
            <td>
                <button onclick="toggleDetails(<?= $order['order_id']; ?>)">View Products</button>
            </td>
        </tr>
        <!-- Order items row -->
        <tr id="items-<?= $order['order_id']; ?>" class="order-items">
            <td colspan="7">
                <table>
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                    <?php foreach (getOrderItems($db, $order['order_id']) as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_id']); ?></td>
                        <td><?= htmlspecialchars($item['name']); ?></td>
                        <td><?= htmlspecialchars($item['quantity']); ?></td>
                        <td>$<?= number_format($item['price'],2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="pagination">
    <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>

</body>
</html>
