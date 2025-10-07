<?php
include 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);

    // Optional: delete related order items first
    $conn->query("DELETE FROM order_item WHERE order_id = $orderId");

    // Delete the order itself
    $conn->query("DELETE FROM orders WHERE order_id = $orderId");

    header("Location: admin_orders.php");
    exit();
}
