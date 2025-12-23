<?php
include 'db_config.php';

if (isset($_POST['signup'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Password Match Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // 2. Secure Password Hashing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if username or email already exists
        $check_user = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' OR email='$email'");
        
        if (mysqli_num_rows($check_user) > 0) {
            $error = "Username or Email already exists!";
        } else {
            // 3. Database Insertion
            $sql = "INSERT INTO users (username, full_name, email, password, role, balance) 
                    VALUES ('$username', '$full_name', '$email', '$hashed_password', 'user', 0.00)";

            if (mysqli_query($conn, $sql)) {
                echo "<script>alert('Registration successful!'); window.location='login.php';</script>";
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Chacha Market</title>
    <link rel="stylesheet" href="css/auth_style.css">
</head>
<body>

<div class="login-container">
    <h2>Create Account</h2>
    
    <?php if(isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Enter your full name" required>

        <label>Username</label>
        <input type="text" name="username" placeholder="Choose a username" required>

        <label>Email Address</label>
        <input type="email" name="email" placeholder="example@gmail.com" required>
        
        <label>Password</label>
        <input type="password" name="password" placeholder="Create a password" required>
        
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat your password" required>
        
        <button type="submit" name="signup">Register Now</button>
    </form>
    
    <div class="footer">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>