<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    echo "login_required";
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. DELETE
if (isset($_GET['delete'])) {
    $p_id = (int)$_GET['delete'];
    unset($_SESSION['cart'][$p_id]);
    header("Location: cart.php");
    exit();
}

// 2. MINUS
if (isset($_GET['minus'])) {
    $p_id = (int)$_GET['minus'];
    if (isset($_SESSION['cart'][$p_id])) {
        $_SESSION['cart'][$p_id]--;
        if ($_SESSION['cart'][$p_id] <= 0) unset($_SESSION['cart'][$p_id]);
    }
    header("Location: cart.php");
    exit();
}

// 3. ADD TO CART
if (isset($_GET['add'])) {
    $p_id = (int)$_GET['add'];
    $qty_to_add = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

    $res = mysqli_query($conn, "SELECT max_limit, stock_quantity FROM products WHERE id = $p_id");
    $product = mysqli_fetch_assoc($res);
    
    if (!$product) { echo "error"; exit(); }
    $limit = $product['max_limit'];
    $stock = $product['stock_quantity'];

    // *** ማስተካከያ፡ Pending የሆኑትንም ይቆጥራል ***
    $order_sql = "SELECT SUM(oi.quantity) as total_bought 
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.id 
                  WHERE o.user_id = $user_id 
                  AND oi.product_id = $p_id 
                  AND (o.status = 'Confirmed' OR o.status = 'Pending Verification')";
    
    $order_check = mysqli_query($conn, $order_sql);
    $order_data = mysqli_fetch_assoc($order_check);
    $past_purchases = (int)$order_data['total_bought'];

    $current_in_cart = isset($_SESSION['cart'][$p_id]) ? $_SESSION['cart'][$p_id] : 0;
    $total_attempted = $past_purchases + $current_in_cart + $qty_to_add;

    if ($total_attempted > $limit) {
        echo "limit_reached";
    } 
    elseif (($current_in_cart + $qty_to_add) > $stock) {
        echo "out_of_stock";
    }
    else {
        if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = array(); }
        $_SESSION['cart'][$p_id] = $current_in_cart + $qty_to_add;
        echo "success";
    }
    exit();
}
?>