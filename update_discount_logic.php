<?php
include 'db_config.php';

if (isset($_POST['id']) && isset($_POST['discount'])) {
    $id = (int)$_POST['id'];
    $discount = (float)$_POST['discount'];

    $sql = "UPDATE products SET discount_price = '$discount' WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>