<?php
session_start();
include 'db_config.php';

// ገጹ ካሽ (Cache) እንዳይደረግ የሚከለክል
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['submit_order'])) {
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address_details']);
    
    $target_dir = "uploads/receipts/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_name = time() . "_" . basename($_FILES["receipt"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
        
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            
            // 1. መጀመሪያ ወደ 'orders' ሰንጠረዥ ማስገባት (አንድ ጊዜ)
            $sql_order = "INSERT INTO orders (user_id, phone, address, receipt_image, status, order_date) 
                          VALUES ('$user_id', '$phone', '$address', '$file_name', 'Pending Verification', NOW())";
            
            if (mysqli_query($conn, $sql_order)) {
                $new_order_id = mysqli_insert_id($conn); // የትዕዛዙን ቁጥር መያዝ

                // 2. ከዚያ እያንዳንዱን ምርት ወደ 'order_items' ማስገባት
                foreach ($_SESSION['cart'] as $p_id => $qty) {
                    $sql_items = "INSERT INTO order_items (order_id, product_id, quantity) 
                                  VALUES ('$new_order_id', '$p_id', '$qty')";
                    mysqli_query($conn, $sql_items);
                }

                unset($_SESSION['cart']); // ካርቶኑን ማጥፋት
                
                echo "<script>
                        alert('Order submitted successfully!');
                        window.location.href='my_orders.php';
                      </script>";
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
    } else {
        echo "<script>alert('Error uploading receipt.'); window.history.back();</script>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>