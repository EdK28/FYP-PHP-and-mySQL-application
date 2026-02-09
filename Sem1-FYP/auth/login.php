<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

// Include DB from config folder
require_once '../config/db.php';

$error = '';

// Redirect if already logged in
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../customer/dashboard.php');
    }
    exit;
}

// Check form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (empty($login) || empty($password)) {
        $error = 'All fields are required';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :login OR username = :login");
        $stmt->execute(['login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Set role based on role_id
            if ($user['role_id'] == 1) {
                $_SESSION['role'] = 'admin';
                header('Location: ../admin/dashboard.php');
            } else {
                $_SESSION['role'] = 'customer';
                header('Location: ../homepage.php');
            }
            exit;
        } else {
            $error = 'Invalid credentials!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN</title>
    <!-- Link to CSS file -->
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/login-responsive.css">

    <script src="https://kit.fontawesome.com/b97c8698d7.js" crossorigin="anonymous"></script>
    
</head>
<body>
    <h2>Login</h2>
    
    <!-- Success message -->
    <?php if(isset($_SESSION['message'])): ?>
    <div class="success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <!-- Error message -->
    <?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Email or Username:</label>
            <input type="text" name="login" required>
        </div>
        
        <div class="form-group password-container">
            <label>Password:</label>
            <input type="password" name="password" id="password" required>
            <span class="toggle-password"><i id="eye" class="fa-solid fa-eye-slash"></i></span>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <div class="link">
        Don't have an account? <a href="signup.php" class="clickhere">Sign Up Here</a>
    </div>
    <div class="link">
        <a href="../homepage.php"><button class="home-btn">Back to Home</button></a>
    </div>

    <script>
        const password = document.getElementById("password");
        const eye = document.getElementById("eye");
        const toggle = document.querySelector(".toggle-password");

        let isToggled = false; // keeps track of click state

        // CLICK to toggle (persistent)
        toggle.addEventListener("click", () => {
        isToggled = !isToggled; // flip state

        if (isToggled) {
            password.type = "text";
            eye.classList.remove("fa-eye-slash");
            eye.classList.add("fa-eye");
        } else {
            password.type = "password";
            eye.classList.remove("fa-eye");
            eye.classList.add("fa-eye-slash");
        }
        });

        // HOVER to temporarily show password
        toggle.addEventListener("mouseenter", () => {
        if (!isToggled) { // only if not clicked
            password.type = "text";
            eye.classList.remove("fa-eye-slash");
            eye.classList.add("fa-eye");
            }
        });

        toggle.addEventListener("mouseleave", () => {
        if (!isToggled) { // only if not clicked
            password.type = "password";
            eye.classList.remove("fa-eye");
            eye.classList.add("fa-eye-slash");
        }
        });
    </script>


</body>
</html>