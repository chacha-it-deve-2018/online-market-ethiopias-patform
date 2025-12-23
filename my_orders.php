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

// 2. Profit Collection Logic
if (isset($_POST['collect_now'])) {
    $order_id = (int)$_POST['order_id'];
    
    $check = mysqli_query($conn, "SELECT daily_profit FROM orders 
                                 WHERE id=$order_id AND user_id=$user_id 
                                 AND status='Confirmed' 
                                 AND (last_claim_date < '$today' OR last_claim_date IS NULL)
                                 AND end_date >= '$today'");
    
    if ($row = mysqli_fetch_assoc($check)) {
        $profit = $row['daily_profit'];
        
        // Update user balance
        mysqli_query($conn, "UPDATE users SET balance = balance + $profit WHERE id = $user_id");
        
        // Update last claim date
        mysqli_query($conn, "UPDATE orders SET last_claim_date = '$today' WHERE id = $order_id");
        
        echo "<script>alert('{$profit} ETB added to your balance!'); window.location.href='my_orders.php';</script>";
        exit();
    } else {
        echo "<script>alert('Already claimed for today or order expired!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Chacha Market</title>
    <link rel="stylesheet" href="css/user_style.css">
</head>
<body>

<div class="container" style="max-width: 900px;">
    <div class="header-flex">
        <h2>My Orders</h2>
        <a href="index.php" class="back-link">← Back to Market</a>
    </div>

    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Start Date</th>
                    <th>Daily Profit</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY id DESC");
                
                if (mysqli_num_rows($result) > 0) {
                    while ($order = mysqli_fetch_assoc($result)) {
                        $status_class = ($order['status'] == 'Confirmed') ? 'confirmed' : 'pending';
                        ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['start_date'])); ?></td>
                            <td style="color: #27ae60; font-weight: bold;">+<?php echo number_format($order['daily_profit'], 2); ?> ETB</td>
                            <td><span class="status <?php echo $status_class; ?>"><?php echo $order['status']; ?></span></td>
                            <td>
                                <?php 
                                if ($order['status'] == 'Confirmed') {
                                    if ($order['last_claim_date'] < $today || is_null($order['last_claim_date'])) {
                                        ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="collect_now" class="btn-claim" style="padding: 5px 10px; font-size: 0.85em;">Collect Profit</button>
                                        </form>
                                        <?php
                                    } else {
                                        echo "<span class='done'>Claimed ✓</span>";
                                    }
                                } else {
                                    echo "<small style='color:gray;'>Awaiting Approval</small>";
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding: 20px;'>No orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>