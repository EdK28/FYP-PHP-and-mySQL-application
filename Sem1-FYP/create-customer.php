<?php
require_once 'config/db.php';

$username = 'watzefuk';
$email = 'zefuck99@gmail.com';
$password = password_hash('Zefuck*01', PASSWORD_DEFAULT);
$role_id = 2; // customer role

$stmt = $db->prepare("INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)");
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'role_id' => $role_id
]);

echo "Customer user created!";
?>
