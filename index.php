<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$category_filter = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chacha Market | Explore Products</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
    <div class="admin-bar">
        <span>Logged in as Administrator</span>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>
<?php endif; ?>

<div class="navbar">
    <h2 style="margin:0;">Chacha Market</h2>
    <div class="nav-links">
        <span style="opacity: 0.8;">Welcome, <?php echo $_SESSION['username']; ?></span>
        <a href="wallet.php">💰 Wallet</a>
        <a href="my_orders.php">📦 My Orders</a>
        <a href="cart.php">🛒 Cart (<span id="cart-counter"><?php echo $cart_count; ?></span>)</a>
        <a href="logout.php" style="color: #e74c3c;">Logout</a>
    </div>
</div>

<div class="category-nav">
    <a href="index.php?cat=all" class="<?php echo $category_filter == 'all' ? 'active' : ''; ?>">All Products</a>
    <?php
    $cat_res = mysqli_query($conn, "SELECT * FROM categories");
    while($c = mysqli_fetch_assoc($cat_res)){
        $active = ($category_filter == $c['id']) ? 'active' : '';
        echo "<a href='index.php?cat={$c['id']}' class='$active'>{$c['category_name']}</a>";
    }
    ?>
</div>

<div class="container">
    <div class="product-grid">
        <?php
        $query = ($category_filter == 'all') ? "SELECT * FROM products" : "SELECT * FROM products WHERE category_id = '$category_filter'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $img = !empty($row['image_url']) ? "uploads/".$row['image_url'] : "assets/no-image.jpg";
                $in_cart = isset($_SESSION['cart'][$row['id']]) ? $_SESSION['cart'][$row['id']] : 0;
                $stock = $row['stock_quantity'];
                ?>
                <div class="product-card">
                    <img src="<?php echo $img; ?>" class="product-image" alt="Product">
                    <div style="font-weight:bold; height: 40px; overflow:hidden;"><?php echo $row['product_name']; ?></div>
                    
                    <div style="font-size: 11px; margin: 8px 0; color: <?php echo ($stock > 0) ? '#27ae60' : '#e74c3c'; ?>;">
                        <?php echo ($stock > 0) ? "● $stock items available" : "● Sold Out"; ?>
                    </div>

                    <div class="price-tag">
                        <?php if($row['discount_price'] > 0): ?>
                            <span class="original-price"><?php echo number_format($row['original_price'], 2); ?> ETB</span><br>
                            <span class="discount-price"><?php echo number_format($row['discount_price'], 2); ?> ETB</span>
                        <?php else: ?>
                            <span class="discount-price"><?php echo number_format($row['original_price'], 2); ?> ETB</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($stock > 0): ?>
                        <button id="btn_<?php echo $row['id']; ?>" 
                                onclick="addToCart(<?php echo $row['id']; ?>)" 
                                class="buy-btn">
                            <?php echo ($in_cart > 0) ? "Added ($in_cart)" : "Add to Cart"; ?>
                        </button>
                    <?php else: ?>
                        <button class="buy-btn" style="background: #bdc3c7; cursor: not-allowed;" disabled>Out of Stock</button>
                    <?php endif; ?>
                </div>
                <?php
            }
        } else {
            echo "<h3 style='grid-column: 1/-1; text-align: center; color: #7f8c8d; padding: 50px;'>No products found in this category.</h3>";
        }
        ?>
    </div>
</div>

<script>
function addToCart(productId) {
    var btn = document.getElementById('btn_' + productId);
    btn.disabled = true;
    btn.innerText = "Processing...";

    var xhr = new XMLHttpRequest();
    xhr.open("GET", "cart_action.php?add=" + productId, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); 

    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var response = xhr.responseText.trim();
            
            if (response == "success") {
                btn.style.background = "#27ae60";
                btn.innerText = "Added ✓";
                setTimeout(() => { location.reload(); }, 400);
            } 
            else if (response == "limit_reached") {
                alert("You have reached the purchase limit for this item!");
                btn.innerText = "Limit Reached";
                btn.style.background = "#e74c3c";
                btn.disabled = false;
            }
            else if (response == "out_of_stock") {
                alert("Sorry, this item is out of stock!");
                location.reload();
            }
            else {
                alert("Error: " + response);
                btn.disabled = false;
                btn.innerText = "Add to Cart";
            }
        }
    };
    xhr.send();
}
</script>
</body>
</html>