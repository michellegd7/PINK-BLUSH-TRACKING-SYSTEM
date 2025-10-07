<?php
include 'database.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: uni_offered.php");
    exit();
}

$user = $_SESSION['username'];

// Get customer ID from username
$customerQuery = "SELECT customer_id FROM customer WHERE username = ?";
$customerStmt = mysqli_prepare($conn, $customerQuery);
mysqli_stmt_bind_param($customerStmt, "s", $user);
mysqli_stmt_execute($customerStmt);
$customerResult = mysqli_stmt_get_result($customerStmt);
$customer = mysqli_fetch_assoc($customerResult);

if (!$customer) {
    die("Customer not found!");
}

$customer_id = $customer['customer_id'];

// Get form data
$uniform_type = $_POST['uniform_type'] ?? '';
$size = $_POST['size'] ?? '';
$custom_size = $_POST['custom_size'] ?? '';
$unit_price = floatval($_POST['selected_price'] ?? 0); // Price per item
$quantity = intval($_POST['quantity'] ?? 1); // Get quantity from form
$downpayment = floatval($_POST['downpayment'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'cash';
$school_id = intval($_POST['school_id'] ?? 0);

// Validate quantity
if ($quantity < 1) {
    $quantity = 1;
} elseif ($quantity > 99) {
    $quantity = 99;
}

// Calculate total amount (unit price × quantity)
$total_amount = $unit_price * $quantity;

// Calculate payment status
$payment_status = 'unpaid';
if ($downpayment >= $total_amount) {
    $payment_status = 'paid';
    $downpayment = $total_amount; // Cap downpayment at total amount
} elseif ($downpayment > 0) {
    $payment_status = 'partial';
}

// Determine measurement
$measurement = ($size === 'custom') ? $custom_size : $size;

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // 1. Insert into orders table
    $orderInsert = "INSERT INTO orders (customer_id, staff_id, order_date, due_date, status, school_id) 
                    VALUES (?, NULL, NOW(), NULL, 'Pending', ?)";

    $orderStmt = mysqli_prepare($conn, $orderInsert);
    mysqli_stmt_bind_param($orderStmt, "ii", $customer_id, $school_id);

    if (!mysqli_stmt_execute($orderStmt)) {
        throw new Exception("Failed to insert order: " . mysqli_error($conn));
    }

    // Get the inserted order_id
    $order_id = mysqli_insert_id($conn);

    // 2. Insert into payment table with calculated total_amount
    $paymentInsert = "INSERT INTO payment (order_id, total_amount, downpayment, payment_date, payment_method, payment_status) 
                      VALUES (?, ?, ?, NOW(), ?, ?)";

    $paymentStmt = mysqli_prepare($conn, $paymentInsert);
    mysqli_stmt_bind_param(
        $paymentStmt,
        "iddss",
        $order_id,
        $total_amount,  // Total = unit_price × quantity
        $downpayment,
        $payment_method,
        $payment_status
    );

    if (!mysqli_stmt_execute($paymentStmt)) {
        throw new Exception("Failed to insert payment: " . mysqli_error($conn));
    }

    // 3. Insert into order_item table with quantity and unit price
    $itemInsert = "INSERT INTO order_item (order_id, customer_id, item_type, measurement, quantity, status, school_id, price) 
                   VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)";

    $itemStmt = mysqli_prepare($conn, $itemInsert);
    mysqli_stmt_bind_param(
        $itemStmt,
        "iissiid",
        $order_id,
        $customer_id,
        $uniform_type,
        $measurement,
        $quantity,      // Store the quantity
        $school_id,
        $unit_price     // Store unit price (price per item)
    );

    if (!mysqli_stmt_execute($itemStmt)) {
        throw new Exception("Failed to insert order item: " . mysqli_error($conn));
    }

    // Commit transaction
    mysqli_commit($conn);

    // Redirect with success to profile page
    header("Location: profile.php?order=success");
    exit();
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    die("Order failed: " . $e->getMessage());
}
