<?php
require_once '../verification/auth.php';
require_once '../verification/role_check.php';
require_once '../config/db.php';
check_role(['admin']); // Only admin can access

$uploadDir = '../assets/media/';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ADD PRODUCT
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $image = null;

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
            $image = $imageName;
        }

        $stmt = $db->prepare("INSERT INTO products (name, description, price, stock, image) VALUES (:name, :description, :price, :stock, :image)");
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image' => $image
        ]);

        header('Location: products.php');
        exit;
    }

    // EDIT PRODUCT
    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $name = $_POST['name']?? null;
        $description = $_POST['description'] ?? null;
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);

        // Fallback to existing values if not editing text fields
        if ($name === null || $description === null) {
        $stmt = $db->prepare("SELECT name, description FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        $name = $current['name'];
        $description = $current['description'];
        }

        // Handle image update
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $stmt = $db->prepare("SELECT image FROM products WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);

            // Delete old image if exists
            if ($old && $old['image'] && file_exists($uploadDir . $old['image'])) {
                unlink($uploadDir . $old['image']);
            }

            $imageName = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);

            $stmt = $db->prepare("UPDATE products SET name = :name, description = :description, price = :price, stock = :stock, image = :image WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'image' => $imageName,
                'id' => $id
            ]);
        } else {
            // Only update price & stock
            $stmt = $db->prepare("UPDATE products SET name = :name, description = :description, price = :price, stock = :stock WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'id' => $id
            ]);
        }

        header('Location: products.php');
        exit;
    }

    // DELETE PRODUCT
    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);

        // Delete image file
        $stmt = $db->prepare("SELECT image FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prod && $prod['image'] && file_exists($uploadDir . $prod['image'])) {
            unlink($uploadDir . $prod['image']);
        }

        // Delete product
        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);

        header('Location: products.php');
        exit;
    }
}

// Fetch all products
$stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Products</title>

<link rel="stylesheet" href="../assets/css/product-manager.css">
<link rel="stylesheet" href="../assets/css/product-responsive.css">

</head>
<body>
    <div class="hero">
        <div class="headsec">
<h1>Product Manager</h1>
<p><a href="dashboard.php"><button class="btn-nav">Back to Dashboard</button></a> | <a href="../auth/logout.php"><button class="btn-nav">Logout</a></button></p>
    </div>
<!-- Add New Product Form -->
<div class="add-form">
    <h3 style="text-align: center; font-size:1.4rem">Add New Product</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <label>Name: <input type="text" name="name" required></label><br><br>
        <label>Description: <input type="text" name="description" required></label><br><br>
        <label>Price: <input type="number" name="price" step="0.01" required></label><br><br>
        <label>Stock: <input type="number" name="stock" required></label><br><br>
        <label>Image: <input type="file" name="image" accept="image/*"></label><br><br>
        <button class="add-product" type="submit">Add Product</button>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Description</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
        <form method="POST" enctype="multipart/form-data">
        <tr>
            <td><?= htmlspecialchars($product['id']) ?></td>
            <td>
                <?php if ($product['image']): ?>
                    <img src="../assets/media/<?= htmlspecialchars($product['image']) ?>" class="product-img">
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td>
                <div class="view-fields">
                    <?= htmlspecialchars($product['name']) ?>
                </div>

                 <div class="edit-fields">
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                </div>
            </td>
            <td>
                <div class="view-fields">
                    <?= htmlspecialchars($product['description']) ?>
                </div>

                <div class="edit-fields">
                    <input type="text" name="description" value="<?= htmlspecialchars($product['description']) ?>">
                </div>
            </td>
            <td>
                <form method="POST" enctype="multipart/form-data" style="display:inline-block;">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>">
            </td>
            <td>
                    <input type="number" name="stock" value="<?= $product['stock'] ?>">
            </td>
            <td>
                    <input type="file" name="image" accept="image/*"><br>
                    <button type="button" onclick="enableEdit(this)">Edit</button>
                    <button type="submit">Update</button>
                </form>
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <button type="submit" onclick="return confirm('Delete this product?')">Delete</button>
                </form>
            </td>
        </tr>
        </form>
        <?php endforeach; ?>
    </tbody>
</table>
    </div>

<script>
function enableEdit(button) {
    const row = button.closest('tr');
    row.classList.add('editing');
}
</script>

</body>
</html>
