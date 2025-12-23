<?php
session_start();
include 'db_config.php';

// 1. ደንበኛው መግባቱን ማረጋገጥ
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first!'); window.location.href='login.php';</script>";
    exit();
}

if (isset($_POST['buy_product'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $price = (float)$_POST['price'];
    $daily_profit = (float)$_POST['daily_profit'];

    // 2. ከዳታቤዝ ውስጥ ይህ ደንበኛ ይህንን ምርት ስንት ጊዜ እንደገዛ መቁጠር
    // ይህም Logout ቢያደርግ እንኳ መረጃው እንዳይጠፋ ያደርጋል
    $check_sql = "SELECT * FROM orders WHERE user_id = '$user_id' AND product_id = '$product_id'";
    $check_query = mysqli_query($conn, $check_sql);
    $already_purchased = mysqli_num_rows($check_query);

    // 3. የገደብ ቁጥር (Limit) - ለምሳሌ 3 ጊዜ ብቻ
    $purchase_limit = 3; 

    if ($already_purchased >= $purchase_limit) {
        echo "<script>
                alert('Limit reached! You can only buy this product $purchase_limit times.');
                window.location.href='index.php';
              </script>";
        exit();
    }

    // 4. የባላንስ ፍተሻ
    $user_query = mysqli_query($conn, "SELECT balance FROM users WHERE id = '$user_id'");
    $user_row = mysqli_fetch_assoc($user_query);
    $current_balance = $user_row['balance'];

    if ($current_balance < $price) {
        echo "<script>alert('Insufficient Balance!'); window.location.href='index.php';</script>";
        exit();
    }

    // 5. ግዢውን መመዝገብ
    // ባላንስ መቀነስ
    $new_balance = $current_balance - $price;
    mysqli_query($conn, "UPDATE users SET balance = $new_balance WHERE id = '$user_id'");

    // ትዕዛዙን መመዝገብ
    $insert_sql = "INSERT INTO orders (user_id, product_id, price, daily_profit, status) 
                   VALUES ('$user_id', '$product_id', '$price', '$daily_profit', 'Confirmed')";
    
    if (mysqli_query($conn, $insert_sql)) {
        echo "<script>alert('Purchase Successful!'); window.location.href='wallet.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // በቀጥታ ፋይሉን ለመክፈት ከሞከሩ ወደ index ይመልሳቸዋል
    header("Location: index.php");
    exit();
}
?>