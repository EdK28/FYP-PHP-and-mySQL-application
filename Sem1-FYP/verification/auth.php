<?php
// ----------------------
// START SESSION & DB CONNECTION
// ----------------------
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => false,  // change to true only if using HTTPS
        'use_strict_mode' => true
    ]);
}

// Correct path to db.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Sem1-FYP/config/db.php';

// ----------------------
// AUTHENTICATION CHECK
// ----------------------
// Redirect to login if not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// ----------------------
// ADDITIONAL FUNCTIONS
// ----------------------
// Example: role check function can go below
?>
