<?php
require_once 'config/db.php';

// NOTE: Only one admin should be created...
$username = 'admin';
$email = 'admin28@gmail.com';
$password = password_hash('Edward@123', PASSWORD_DEFAULT);
$role_id = 1; // admin role

$stmt = $db->prepare("INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)");
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'role_id' => $role_id
]);

echo "Admin user created!";
?>
