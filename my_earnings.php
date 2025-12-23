<?php
session_start();
include 'db_config.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// 2. Claim Profit Logic
if (isset($_POST['claim_profit'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    
    // Check if profit is available for today and order is active
    $check_query = "SELECT * FROM orders 
                    WHERE id='$order_id' 
                    AND user_id='$user_id' 
                    AND status='Confirmed' 
                    AND (last_claim_date < '$today' OR last_claim_date IS NULL) 
                    AND end_date >= '$today'";
    
    $check_result = mysqli_query($conn, $check_query); 
    
    if ($check_result && $row = mysqli_fetch_assoc($check_result)) {
        $profit = $row['daily_profit'];
        
        // A. Update User Balance
        mysqli_query($conn, "UPDATE users SET balance = balance + $profit WHERE id = '$user_id'");
        
        // B. Update Last Claim Date to Today
        mysqli_query($conn, "UPDATE orders SET last_claim_date = '$today' WHERE id = '$order_id'");
        
        echo "<script>alert('{$profit} ETB added to your wallet!'); window.location.href='my_earnings.php';</script>";
    } else {
        echo "<script>alert('Error: Already claimed or order expired!'); window.location.href='my_earnings.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Profits - Chacha Market</title>
    <link rel="stylesheet" href="css/user_style.css">
</head>
<body>

<div class="container">
    <div class="header">
        <h2>💰 Daily Profits</h2>
        <a href="index.php" class="back-link">← Market</a>
    </div>

    <?php
    // Fetch user confirmed orders
    $orders = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = '$user_id' AND status = 'Confirmed' ORDER BY id DESC");

    if ($orders && mysqli_num_rows($orders) > 0) {
        while ($order_row = mysqli_fetch_assoc($orders)) {
            $is_expired = ($order_row['end_date'] < $today);
            ?>
            <div class="earnings-card" style="<?php echo $is_expired ? 'border-left-color: #e74c3c; opacity: 0.7;' : ''; ?>">
                <div class="order-info">
                    <strong>Package #<?php echo $order_row['id']; ?></strong>
                    <div class="profit-amt"><?php echo number_format($order_row['daily_profit'], 2); ?> ETB</div>
                    <small>Expires on: <?php echo $order_row['end_date']; ?></small>
                    <?php if($is_expired) echo "<small class='expiry-text'>Expired</small>"; ?>
                </div>

                <div>
                    <?php if ($is_expired): ?>
                        <button class="btn-claimed" disabled>Expired</button>
                    <?php elseif ($order_row['last_claim_date'] < $today || is_null($order_row['last_claim_date'])): ?>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order_row['id']; ?>">
                            <button type="submit" name="claim_profit" class="btn-claim">Claim Profit</button>
                        </form>
                    <?php else: ?>
                        <button class="btn-claimed" disabled>Collected ✓</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<div style='text-align:center; padding:50px;'>
                <p>No active profit packages found.</p>
                <a href='index.php' class='btn-claim' style='text-decoration:none;'>Go to Market</a>
              </div>";
    }
    ?>
</div>

</body>
</html>