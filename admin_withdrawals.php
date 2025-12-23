<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); 
    exit();
}

// Handle Admin Actions (Approve or Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $new_status = ($action == 'approve') ? 'Approved' : 'Rejected';

    $update_query = "UPDATE withdraw_requests SET status = '$new_status' WHERE id = $id";
    
    if (mysqli_query($conn, $update_query)) {
        echo "<script>alert('Status updated to $new_status!'); window.location.href='admin_withdrawals.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Withdrawal Management - Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 1100px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #eee; padding: 15px; text-align: left; }
        th { background: #2c3e50; color: white; }
        .status-pending { color: #e67e22; font-weight: bold; }
        .status-approved { color: #27ae60; font-weight: bold; }
        .status-rejected { color: #e74c3c; font-weight: bold; }
        .btn-approve { background: #27ae60; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; margin-right: 5px; }
        .btn-reject { background: #e74c3c; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_dashboard.php" style="text-decoration:none; color:#34495e;">← Dashboard</a>
    <h2>Withdrawal Management</h2>
    
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Account/Phone</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT withdraw_requests.*, users.username FROM withdraw_requests 
                    JOIN users ON withdraw_requests.user_id = users.id ORDER BY id DESC";
            $res = mysqli_query($conn, $sql);

            while ($row = mysqli_fetch_assoc($res)) {
                $s = $row['status'];
                $s_class = ($s == 'Pending') ? 'status-pending' : (($s == 'Approved') ? 'status-approved' : 'status-rejected');
                
                echo "<tr>
                        <td>{$row['username']}</td>
                        <td>" . number_format($row['amount'], 2) . " ETB</td>
                        <td>{$row['bank_name']}</td>
                        <td><code>{$row['account_number']}</code></td>
                        <td><span class='$s_class'>$s</span></td>
                        <td>";
                
                if ($s == 'Pending') {
                    echo "<a href='admin_withdrawals.php?action=approve&id={$row['id']}' class='btn-approve' onclick='return confirm(\"Approve this?\")'>Approve</a>";
                    echo "<a href='admin_withdrawals.php?action=reject&id={$row['id']}' class='btn-reject' onclick='return confirm(\"Reject this?\")'>Reject</a>";
                } else {
                    echo "Processed";
                }
                
                echo "</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</body>
</html>