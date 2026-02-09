<?php
require_once 'auth.php'; // ensure user is logged in first

/*
 * Check if current user has one of the allowed roles
 * @param array $allowed_roles Example: ['admin'], ['customer'], or ['admin','customer']
 */
function check_role(array $allowed_roles = []) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // Redirect if role not allowed
        header('Location: ../auth/login.php');
        exit;
    }
}
?>
