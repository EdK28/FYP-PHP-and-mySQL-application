<?php
require_once '../verification/auth.php';
require_once '../verification/role_check.php';
require_once '../config/db.php';
check_role(['admin']); // Only admin can access

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];

    // Prevent self-delete
    if ($deleteId === $_SESSION['user_id']) {
        die('You cannot delete your own account.');
    }

    // Prevent deleting other admins
    $checkStmt = $db->prepare("
        SELECT r.role_name 
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $checkStmt->execute([$deleteId]);
    $targetUser = $checkStmt->fetch();

    if ($targetUser && $targetUser['role_name'] === 'admin') {
        die('You cannot delete another admin.');
    }

    // Delete user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$deleteId]);

    header("Location: users.php");
    exit;
}

// Handle save edited user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_id'])) {
    $editId = (int) $_POST['save_id'];
    $newUsername = trim($_POST['username']);
    $newEmail = trim($_POST['email']);

    if ($newUsername === '' || $newEmail === '') {
        die('Username and email cannot be empty.');
    }

    // Optional: prevent editing other admins
    $checkStmt = $db->prepare("
        SELECT r.role_name 
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $checkStmt->execute([$editId]);
    $targetUser = $checkStmt->fetch();

    if ($targetUser && $targetUser['role_name'] === 'admin' && $editId !== $_SESSION['user_id']) {
        die('You cannot edit another admin.');
    }

    $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->execute([$newUsername, $newEmail, $editId]);

    header("Location: users.php");
    exit;
}

// Fetch all users
$stmt = $db->query("SELECT u.id, u.username, u.email, r.role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id
                    ORDER BY u.id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine which row is being edited
$editRow = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users</title>
<link rel="stylesheet" href="../assets/css/user-manager.css">
<link rel="stylesheet" href="../assets/css/user-manager-responsive.css">
</head>
<body>
<h1 style="text-align: center";>User Manager</h1>
<p style="text-align: center";><a href="dashboard.php"><button class="btn-nav">Back to Dashboard</button></a> | <a href="../auth/logout.php"><button class="btn-nav">Logout</a></button></p>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']); ?></td>

            <?php if ($editRow === $user['id']): ?>
                <!-- Edit mode -->
                <form method="POST" style="display:inline;">
                    <td><input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>"></td>
                    <td><input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>"></td>
                    <td><?= htmlspecialchars($user['role_name']); ?></td>
                    <td>
                        <input type="hidden" name="save_id" value="<?= $user['id']; ?>">
                        <button type="submit">Save</button>
                        <a href="users.php"><button type="button">Cancel</button></a>
                    </td>
                </form>
            <?php else: ?>
                <!-- Display mode -->
                <td><?= htmlspecialchars($user['username']); ?></td>
                <td><?= htmlspecialchars($user['email']); ?></td>
                <td><?= htmlspecialchars($user['role_name']); ?></td>
                <td>
                    <?php if ($user['role_name'] !== 'admin' || $user['id'] === $_SESSION['user_id']): ?>
                        <a href="users.php?edit=<?= $user['id']; ?>"><button>Edit</button></a>
                    <?php endif; ?>

                    <?php if ($user['role_name'] !== 'admin' && $user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;" 
                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="delete_id" value="<?= $user['id']; ?>">
                            <button type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
