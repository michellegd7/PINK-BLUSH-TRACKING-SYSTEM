<?php
include 'database.php';
session_start();

// Check if staff is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: staff_login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: staff_dashboard.php");
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
$new_status = $_POST['status'] ?? '';

// Validate status
$allowed_statuses = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    die("Invalid status!");
}

// Update order status
$updateQuery = "UPDATE orders SET status = ? WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);

if (mysqli_stmt_execute($stmt)) {
    // Also update order_item status to match
    $updateItemQuery = "UPDATE order_item SET status = ? WHERE order_id = ?";
    $itemStmt = mysqli_prepare($conn, $updateItemQuery);
    mysqli_stmt_bind_param($itemStmt, "si", $new_status, $order_id);
    mysqli_stmt_execute($itemStmt);

    // Redirect back to dashboard with success message
    header("Location: staff_dashboard.php?success=1");
} else {
    // Redirect back with error
    header("Location: staff_dashboard.php?error=1");
}

exit();
