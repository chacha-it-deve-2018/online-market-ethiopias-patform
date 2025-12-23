<?php
session_start();
include 'db_config.php';

// 1. Admin Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Order Approval Logic (Including Daily Profit & Staking Dates)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $order_query = mysqli_query($conn, "SELECT total_amount FROM orders WHERE id = $order_id");
        $order_data = mysqli_fetch_assoc($order_query);
        
        if ($order_data) {
            $total = $order_data['total_amount'];
            $daily_profit = $total * 0.02; // 2% daily profit logic
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime('+60 days'));
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $update_sql = "UPDATE orders SET status = 'Confirmed', daily_profit = '$daily_profit', 
                           start_date = '$start_date', end_date = '$end_date', last_claim_date = '$yesterday' 
                           WHERE id = $order_id";
            
            mysqli_query($conn, $update_sql);
            echo "<script>alert('Order Approved and Staking Started!'); window.location.href='admin_dashboard.php';</script>";
        }
    }
}

// 3. Order Delete Logic
if (isset($_GET['delete_order'])) {
    $order_id = (int)$_GET['delete_order'];
    $del_sql = "DELETE FROM orders WHERE id = $order_id";
    if (mysqli_query($conn, $del_sql)) {
        echo "<script>alert('Order deleted successfully!'); window.location.href='admin_dashboard.php';</script>";
    }
}

// 4. Product Delete Logic
if (isset($_GET['delete_product'])) {
    $p_id = (int)$_GET['delete_product'];
    $img_query = mysqli_query($conn, "SELECT image_url FROM products WHERE id = $p_id");
    $img_data = mysqli_fetch_assoc($img_query);
    if ($img_data && !empty($img_data['image_url']) && file_exists("uploads/" . $img_data['image_url'])) {
        unlink("uploads/" . $img_data['image_url']);
    }

    $del_sql = "DELETE FROM products WHERE id = $p_id";
    if (mysqli_query($conn, $del_sql)) {
        echo "<script>alert('Product deleted!'); window.location.href='admin_dashboard.php';</script>";
    }
}

// 5. Register New Product Logic
if (isset($_POST['add_product'])) {
    $p_name = mysqli_real_escape_string($conn, $_POST['p_name']);
    $cat_id = (int)$_POST['cat_id'];
    $price = (float)$_POST['price'];
    $max_limit = (int)$_POST['max_limit'];
    $stock = (int)$_POST['stock'];
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $image = $_FILES['image']['name'];
    $new_image_name = time() . "_" . $image;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $new_image_name)) {
        $sql = "INSERT INTO products (category_id, product_name, description, original_price, discount_price, image_url, stock_quantity, max_limit) 
                VALUES ('$cat_id', '$p_name', '$desc', '$price', '0', '$new_image_name', '$stock', '$max_limit')";
        mysqli_query($conn, $sql);
        echo "<script>alert('Product registered!'); window.location.href='admin_dashboard.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Chacha Market</title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>

<div class="sidebar">
    <h2>CHACHA</h2>
    <p>Management Portal</p>
    <hr style="border: 0.5px solid #444; margin: 15px 0;">
    <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>
    <a href="admin_withdrawals.php">💰 Withdrawals</a>
    <a href="index.php">🛒 Market View</a>
    <a href="logout.php" style="color: #ff7675; margin-top: 50px;">Logout</a>
</div>

<div class="main-content">
    
    <div class="card">
        <h3>📦 Register New Product</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="p_name" placeholder="e.g. Premium Coffee" required>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Category</label>
                    <select name="cat_id">
                        <?php
                        $cats = mysqli_query($conn, "SELECT * FROM categories");
                        while($c = mysqli_fetch_assoc($cats)) { 
                            echo "<option value='{$c['id']}'>{$c['category_name']}</option>"; 
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Price (ETB)</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Stock Qty</label>
                    <input type="number" name="stock" value="100" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Purchase Limit</label>
                    <input type="number" name="max_limit" value="1" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Product Image</label>
                    <input type="file" name="image" required>
                </div>
            </div>
            <button type="submit" name="add_product" class="btn-save">REGISTER PRODUCT</button>
        </form>
    </div>

    <div class="card">
        <h3>✏️ Inventory & Prices</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Base Price</th>
                    <th>Limit</th>
                    <th>Discount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
                while ($p = mysqli_fetch_assoc($products)) {
                ?>
                <tr>
                    <td style="font-weight: bold;"><?php echo $p['product_name']; ?></td>
                    <td><?php echo number_format($p['original_price'], 2); ?></td>
                    <td><input type="number" id="limit_<?php echo $p['id']; ?>" value="<?php echo $p['max_limit']; ?>" class="fast-input"></td>
                    <td><input type="number" id="disc_<?php echo $p['id']; ?>" value="<?php echo $p['discount_price']; ?>" class="fast-input"></td>
                    <td>
                        <button onclick="updateProductData(<?php echo $p['id']; ?>)" class="btn-update">Update</button>
                        <a href="admin_dashboard.php?delete_product=<?php echo $p['id']; ?>" class="btn-delete" onclick="return confirm('Delete product?')">Delete</a>
                        <span id="msg_<?php echo $p['id']; ?>" style="font-size:11px; display:block;"></span>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>🔔 Recent Orders & Payment Proofs</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total Amt</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $orders = mysqli_query($conn, "SELECT orders.*, users.username FROM orders JOIN users ON orders.user_id = users.id ORDER BY id DESC LIMIT 15");
                while ($row = mysqli_fetch_assoc($orders)) {
                    $status = $row['status'];
                    $s_class = ($status == 'Confirmed') ? 'confirmed' : 'pending';
                    $receipt = $row['receipt_image'];
                ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><strong><?php echo number_format($row['total_amount'], 2); ?> ETB</strong></td>
                    
                    <td>
                        <?php if (!empty($receipt)): ?>
                            <a href="receipts/<?php echo $receipt; ?>" target="_blank">
                                <img src="receipts/<?php echo $receipt; ?>" width="40" height="40" style="object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                            </a>
                        <?php else: ?>
                            <span style="color: #999; font-size: 11px;">No Receipt</span>
                        <?php endif; ?>
                    </td>

                    <td><span class="status-pill <?php echo $s_class; ?>"><?php echo $status; ?></span></td>
                    <td>
                        <?php if ($status == 'Pending Verification') { ?>
                            <a href="admin_dashboard.php?action=approve&id=<?php echo $row['id']; ?>" style="color:#27ae60; font-weight:bold; text-decoration:none;">Approve</a> |
                        <?php } else { echo "Verified ✓ | "; } ?>
                        <a href="admin_dashboard.php?delete_order=<?php echo $row['id']; ?>" style="color:#e74c3c; text-decoration:none;" onclick="return confirm('Delete this order?')">Delete</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateProductData(pId) {
    var discVal = document.getElementById('disc_' + pId).value;
    var limitVal = document.getElementById('limit_' + pId).value;
    var msg = document.getElementById('msg_' + pId);
    msg.innerText = "Saving...";
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "update_product_logic.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            if(xhr.responseText.trim() == "success") {
                msg.innerText = "Saved! ✓"; msg.style.color = "green";
                setTimeout(() => { msg.innerText = ""; }, 2000);
            } else { msg.innerText = "Error!"; msg.style.color = "red"; }
        }
    };
    xhr.send("id=" + pId + "&discount=" + discVal + "&limit=" + limitVal);
}
</script>

</body>
</html>