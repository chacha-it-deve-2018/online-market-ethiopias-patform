<?php
session_start();
include 'db_config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart - Chacha Market</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f4f4f4; }
        .cart-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .cart-table th, .cart-table td { padding: 15px; border-bottom: 1px solid #ddd; text-align: left; }
        .cart-table th { background: #2c3e50; color: white; }
        .qty-btn { text-decoration: none; padding: 5px 12px; background: #eee; color: #333; border-radius: 3px; font-weight: bold; border: 1px solid #ccc; }
        .delete-btn { color: #e74c3c; text-decoration: none; font-weight: bold; }
        .total { font-size: 1.5rem; font-weight: bold; text-align: right; margin-top: 20px; color: #2c3e50; }
        .checkout-btn { background: #27ae60; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; float: right; font-weight: bold; text-decoration: none; transition: 0.3s; }
        .checkout-btn:hover { background: #219150; }
    </style>
</head>
<body>

<h2>🛒 My Shopping Cart</h2>
<table class="cart-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Subtotal</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $total_all = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $qty) {
            $id = (int)$id; 
            $res = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
            $product = mysqli_fetch_assoc($res);
            
            if ($product) {
                // መጀመሪያ ቅናሽ ዋጋውን ይፈትሻል፣ 0 ከሆነ ዋናውን ዋጋ ይወስዳል
                $unit_price = ($product['discount_price'] > 0) ? $product['discount_price'] : $product['original_price'];
                
                $subtotal = $unit_price * $qty;
                $total_all += $subtotal;
                
                echo "<tr>
                        <td>{$product['product_name']}</td>
                        <td>
                            <a href='cart_action.php?minus=$id' class='qty-btn'>-</a>
                            <span style='margin: 0 10px; font-weight: bold;'>$qty</span>
                            <a href='cart_action.php?add=$id' class='qty-btn'>+</a>
                        </td>
                        <td>" . number_format($unit_price, 2) . " ETB</td>
                        <td>" . number_format($subtotal, 2) . " ETB</td>
                        <td><a href='cart_action.php?delete=$id' class='delete-btn' onclick='return confirm(\"Are you sure you want to remove this item?\")'>❌ Remove</a></td>
                      </tr>";
            }
        }
    } else {
        echo "<tr><td colspan='5' style='text-align:center; padding: 20px;'>Your cart is empty!</td></tr>";
    }
    ?>
    </tbody>
</table>

<div class="total">Grand Total: <?php echo number_format($total_all, 2); ?> ETB</div>
<br>
<div style="overflow: hidden; margin-top: 20px;">
    <a href="index.php" style="text-decoration:none; color:#2c3e50; font-weight:bold; line-height: 50px;">← Back to Shop</a>
    
    <?php if ($total_all > 0): ?>
        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
    <?php endif; ?>
</div>

</body>
</html>