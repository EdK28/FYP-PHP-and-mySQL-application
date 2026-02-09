<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,  // Only use with HTTPS
    'use_strict_mode' => true
]);

// Include DB from config folder
require_once '../config/db.php';

$error = '';
$success = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error = 'Password must be at least 8 characters, include 1 uppercase letter, 1 number, and 1 symbol.';
    } else {
        // Check if email or username already exists
        $stmt = $db->prepare("SELECT email, username FROM users WHERE email = :email OR username = :username");
        $stmt->execute([
            'email' => $email,
            'username' => $username
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['email'] === $email) {
                $error = 'Email already exists';
            } elseif ($user['username'] === $username) {
                $error = 'Username already exists';
            }
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into database with default role = customer (role_id = 2)
            $role_id = 2;
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password,
                'role_id' => $role_id
            ]);

            $success = 'Successfully registered! You can now login.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <!-- Link to CSS file -->
    <link rel="stylesheet" href="../assets/css/signup.css">
    <link rel="stylesheet" href="../assets/css/signup-responsive.css">
</head>
<body>
    <h2>Sign Up Here!</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="signupForm">

        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group password-container">
            <label>Password:</label>
            <input type="password" name="password" id="password" required>
            <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
        </div>

        <div class="form-group password-container">
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm-password" required>
            <span class="toggle-password" onclick="togglePassword('confirm-password')">👁️</span>
        </div>

        <button type="submit">Sign Up</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php" class="clickhere">Login Here</a>
    </div>

    <!-- Password error popup -->
    <div id="passwordErrorPopup">
    <i>Password must be at least 8 characters, include 1 uppercase letter, 1 number, and 1 symbol!</i>
    <span id="closePopup" style="margin-left: 10px; cursor:pointer; font-weight:bold;">&times;</span>
    </div>


    <script>

        const passwordInput = document.getElementById("password");
        const signupForm = document.getElementById("signupForm");
        const popup = document.getElementById("passwordErrorPopup");
        const closePopup = document.getElementById("closePopup");

        signupForm.addEventListener("submit", function(e) {
        const password = passwordInput.value;
        const regex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    
        if (!regex.test(password)) {
        e.preventDefault();
        popup.style.display = "block";
        }
        });

        // Close the popup when the × is clicked
        closePopup.addEventListener("click", function() {
        popup.style.display = "none";
        });

        // Optional: close popup when clicking outside of it
        window.addEventListener("click", function(e) {
        if (e.target === popup) {
        popup.style.display = "none";
        }
        });

        function togglePassword(id) {
        const input = document.getElementById(id);

        input.type = input.type === "password"
        ? "text"
        : "password";
        }
    </script>
</body>
</html>
