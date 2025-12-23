<?php
session_start();
include 'db_config.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Redirect if Cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// 3. Calculate Total Price
$total_all = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    $id = (int)$id; 
    $res = mysqli_query($conn, "SELECT discount_price, original_price FROM products WHERE id = $id");
    if ($product = mysqli_fetch_assoc($res)) {
        $price = ($product['discount_price'] > 0) ? $product['discount_price'] : $product['original_price'];
        $total_all += ($price * $qty);
    }
}

// 4. Handle "Place Order"
if (isset($_POST['place_order'])) {
    $user_id = $_SESSION['user_id'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $address_details = mysqli_real_escape_string($conn, $_POST['address_details']);
    $full_address = $city . " - " . $address_details;
    
    // Receipt Image Handling
    $target_dir = "receipts/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $new_receipt_name = time() . "_" . basename($_FILES['receipt']['name']);
    $target_file = $target_dir . $new_receipt_name;

    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
        
        // A. Insert into 'orders' table
        $order_query = "INSERT INTO orders (user_id, total_amount, phone, address, receipt_image, status) 
                        VALUES ('$user_id', '$total_all', '$phone', '$full_address', '$new_receipt_name', 'Pending Verification')";
        
        if (mysqli_query($conn, $order_query)) {
            $order_id = mysqli_insert_id($conn); 

            // B. Loop through cart to insert items AND REDUCE STOCK
            foreach ($_SESSION['cart'] as $p_id => $qty) {
                $p_id = (int)$p_id;
                
                $p_res = mysqli_query($conn, "SELECT discount_price, original_price FROM products WHERE id = $p_id");
                $p_data = mysqli_fetch_assoc($p_res);
                $price = ($p_data['discount_price'] > 0) ? $p_data['discount_price'] : $p_data['original_price'];

                // 1. Insert into order_items
                mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, price_at_time) 
                                   VALUES ('$order_id', '$p_id', '$qty', '$price')");

                // 2. Reduce Stock
                mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity - $qty WHERE id = $p_id");
            }

            unset($_SESSION['cart']); 
            echo "<script>alert('Order submitted successfully! Waiting for admin approval.'); window.location.href='index.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Failed to upload receipt image.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Confirm Order</title>
    <link rel="stylesheet" href="css/index.css"> <style>
        .checkout-container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .summary-box { background: #fdf9ea; padding: 15px; border-radius: 8px; border: 1px solid #f39c12; margin-bottom: 20px; text-align: center; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #34495e; }
        input, select, textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-confirm { width: 100%; padding: 15px; background: #27ae60; color: white; border: none; border-radius: 8px; font-weight: bold; margin-top: 25px; cursor: pointer; font-size: 1.1em; }
        .btn-confirm:hover { background: #219150; }
        .bank-details { font-size: 0.85em; background: #f4f7f6; padding: 10px; border-radius: 5px; margin-top: 10px; border-left: 4px solid #2c3e50; }
    </style>
</head>
<body>

<div class="checkout-container">
    <h2 style="text-align: center; color: #2c3e50;">Checkout</h2>
    
    <div class="summary-box">
        Total to Pay: <b style="font-size: 1.4em; color: #e67e22;"><?php echo number_format($total_all, 2); ?> ETB</b>
    </div>

    <div class="bank-details">
        <strong>Bank Accounts for Payment:</strong><br>
        CBE: 1000XXXXXXXXX<br>
        Abyssinia: 999XXXXXXXXX
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label>Phone Number (09... or 07...)</label>
        <input type="tel" name="phone" placeholder="0912345678" pattern="^(09|07)[0-9]{8}$" required>
        
        <label>Select City</label>
        <select name="city" required>
            <option value="" disabled selected>Choose city...</option>
            <option value="Addis Ababa">Addis Ababa</option>
            <option value="Mekelle">Mekelle</option>
            <option value="Hawassa">Hawassa</option>
            <option value="Dire Dawa">Dire Dawa</option>
            <option value="Sodo">Sodo</option>
            <option value="Jimma">Jimma</option>
            <option value="Adama">Adama</option>
            <option value="Djibouti">Djibouti</option>
        </select>

        <label>Specific Address</label>
        <textarea name="address_details" rows="2" placeholder="House No, Neighborhood, Landmark..." required></textarea>

        <label>Upload Bank Receipt (Image)</label>
        <input type="file" name="receipt" accept="image/*" required>
        
        <button type="submit" name="place_order" class="btn-confirm">Submit Order & Receipt</button>
    </form>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="cart.php" style="color: #7f8c8d; text-decoration: none;">← Back to Cart</a>
    </div>
</div>

</body>
</html>