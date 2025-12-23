<?php
session_start();
include 'db_config.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

// 2. Fetch Current User Balance
$u_res = mysqli_query($conn, "SELECT balance FROM users WHERE id = '$user_id'");
$u_row = mysqli_fetch_assoc($u_res);
$current_balance = isset($u_row['balance']) ? $u_row['balance'] : 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - Chacha Market</title>
    <link rel="stylesheet" href="css/user_style.css">
    <style>
        /* Internal styles for layout consistency */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; padding: 15px; }
        .header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .back-home-btn { text-decoration: none; color: #34495e; font-weight: bold; font-size: 0.9em; }
        
        .wallet-card { 
            background: linear-gradient(135deg, #2c3e50, #34495e); 
            color: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .investment-item { 
            background: white; 
            padding: 15px; 
            border-radius: 10px; 
            margin-bottom: 10px; 
            border: 1px solid #eee; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .history-card { 
            background: white; 
            padding: 15px; 
            border-radius: 12px; 
            margin-top: 10px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
        }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; color: #7f8c8d; font-size: 0.8em; padding: 10px; border-bottom: 1px solid #eee; }
        td { padding: 12px 10px; border-bottom: 1px solid #f9f9f9; font-size: 0.9em; }

        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75em; font-weight: bold; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        h3 { margin-top: 30px; color: #2c3e50; font-size: 1.1em; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-nav">
        <h2 style="margin: 0; color: #2c3e50;">My Wallet</h2>
        <a href="index.php" class="back-home-btn">← Back to Home</a>
    </div>

    <div class="wallet-card">
        <p style="margin: 0; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75em;">Available Balance</p>
        <h1 style="margin: 10px 0;"><?php echo number_format($current_balance, 2); ?> <span style="font-size: 0.4em;">ETB</span></h1>
        <hr style="border: 0.5px solid rgba(255,255,255,0.1); margin: 20px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="withdraw.php" style="color:#f1c40f; text-decoration:none; font-weight:bold;">Withdraw Funds →</a>
            <span style="font-size: 0.75em; opacity: 0.6;">Chacha Market Safe</span>
        </div>
    </div>

    <h3 style="border-left: 4px solid #27ae60; padding-left: 10px;">Active Investments</h3>
    <div class="investment-list">
        <?php
        $orders = mysqli_query($conn, "SELECT * FROM orders WHERE user_id='$user_id' AND status='Confirmed' ORDER BY id DESC");
        if (mysqli_num_rows($orders) > 0) {
            while ($r = mysqli_fetch_assoc($orders)) {
                ?>
                <div class="investment-item">
                    <span style="color: #7f8c8d; font-size: 0.75em;">Order #<?php echo $r['id']; ?></span>
                    <div style="font-weight: bold; margin-top: 5px; display: flex; justify-content: space-between;">
                        <span>Daily Income:</span>
                        <span style="color: #27ae60;">+<?php echo number_format($r['daily_profit'], 2); ?> ETB</span>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<div style='text-align:center; padding: 20px; color: #95a5a6; background: white; border-radius: 10px;'>No active investments found.</div>";
        }
        ?>
    </div>

    <h3 style="border-left: 4px solid #f1c40f; padding-left: 10px;">Withdrawal History</h3>
    <div class="history-card">
        <?php
        // Fetches from 'withdraw_requests' to sync with your admin page logic
        $w_query = mysqli_query($conn, "SELECT * FROM withdraw_requests WHERE user_id = '$user_id' ORDER BY id DESC");
        
        if (mysqli_num_rows($w_query) > 0) {
            echo "<table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>";
            while($w = mysqli_fetch_assoc($w_query)) {
                $status = $w['status'];
                
                // Logic to map database status to UI badges
                if ($status == 'Approved' || $status == 'Paid') {
                    $badge_class = "status-approved";
                    $display_text = "Success ✓";
                } elseif ($status == 'Rejected') {
                    $badge_class = "status-rejected";
                    $display_text = "Rejected";
                } else {
                    $badge_class = "status-pending";
                    $display_text = "Pending";
                }

                echo "<tr>
                        <td>".date('M d, Y', strtotime($w['request_date']))."</td>
                        <td><b>".number_format($w['amount'], 2)." ETB</b></td>
                        <td><span class='status-badge $badge_class'>$display_text</span></td>
                      </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p style='text-align:center; color:#95a5a6; padding: 10px; font-size: 0.9em;'>No withdrawal history found.</p>";
        }
        ?>
    </div>
</div>

</body>
</html>