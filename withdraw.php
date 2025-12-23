<?php
session_start();
include 'db_config.php';
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT balance FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_query);
$balance = $user_data['balance'];
if (isset($_POST['request_withdraw'])) {
    $amount = (float)$_POST['amount'];
    $bank = mysqli_real_escape_string($conn, $_POST['method']);
    $account = mysqli_real_escape_string($conn, $_POST['account_details']);
    if ($amount >= 250 && $amount <= $balance) {
        
        mysqli_query($conn, "UPDATE users SET balance = balance - $amount WHERE id = '$user_id'");

        $sql = "INSERT INTO withdraw_requests (user_id, amount, bank_name, account_number, status) 
                VALUES ('$user_id', '$amount', '$bank', '$account', 'Pending')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Withdrawal request sent successfully!'); window.location.href='wallet.php';</script>";
        }
    } else {
        if ($amount < 250) {
            echo "<script>alert('Minimum withdrawal amount is 250 ETB!');</script>";
        } else {
            echo "<script>alert('Insufficient balance!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Withdraw Funds</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .withdraw-box { max-width: 400px; width: 100%; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 5px; }
        .balance-view { text-align: center; color: #27ae60; font-weight: bold; font-size: 1.2rem; margin-bottom: 20px; background: #e8f5e9; padding: 10px; border-radius: 8px; }
        label { font-weight: bold; color: #34495e; display: block; margin-top: 15px; }
        input, select { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        .btn-submit { width: 100%; padding: 15px; background: #2c3e50; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1rem; margin-top: 25px; transition: 0.3s; }
        .btn-submit:hover { background: #1a252f; }
        .back-link { display: block; text-align: center; color: #7f8c8d; text-decoration: none; margin-top: 15px; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="withdraw-box">
    <h2>Withdraw Money</h2>
    <div class="balance-view">
        Balance: <?php echo number_format($balance, 2); ?> ETB
    </div>
    
    <form method="POST">
        <label>Amount (Min. 250 ETB)</label>
        <input type="number" name="amount" step="0.01" min="250" max="<?php echo $balance; ?>" placeholder="Enter amount" required>
        
        <label>Select Bank / Method</label>
        <select name="method" required>
            <option value="CBE">CBE (Commercial Bank)</option>
            <option value="Abyssinia">Bank of Abyssinia</option>
            <option value="Telebirr">Telebirr</option>
            <option value="Awash">Awash Bank</option>
        </select>
        
        <label>Account Number / Phone</label>
        <input type="text" name="account_details" placeholder="Enter account or phone number" required>
        
        <button type="submit" name="request_withdraw" class="btn-submit">Submit Withdrawal</button>
    </form>
    
    <a href="wallet.php" class="back-link">← Back to Wallet</a>
</div>

</body>
</html>