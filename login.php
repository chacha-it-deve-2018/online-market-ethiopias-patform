<?php
include 'db_config.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Verification: Supports Hashed password or direct '123456' for testing
        if (password_verify($password, $user['password']) || $password == '123456') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid password! Please try again.";
        }
    } else {
        $error = "Username not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chacha Market</title>
    <link rel="stylesheet" href="css/auth_style.css">
</head>
<body>

<div class="login-container">
    <h2>Login</h2>
    
    <?php if(isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Username</label>
        <input type="text" name="username" required placeholder="Enter your username">
        
        <label>Password</label>
        <input type="password" name="password" required placeholder="Enter your password">
        
        <button type="submit" name="login">Sign In</button>
    </form>
    
    <div class="footer">
        Don't have an account? <a href="signup.php">Register here</a>
    </div>
</div>

</body>
</html>