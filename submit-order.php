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
    die("Error: Customer not found! Please ensure you are logged in properly.");
}

$customer_id = $customer['customer_id'];

// Get form data
$uniform_type = $_POST['uniform_type'] ?? '';
$size = $_POST['size'] ?? '';
$custom_size = $_POST['custom_size'] ?? '';
$unit_price = floatval($_POST['selected_price'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$downpayment = floatval($_POST['downpayment'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'cash';
$school_id = intval($_POST['school_id'] ?? 0);
$uniform_id = intval($_POST['uniform_id'] ?? 0); // IMPORTANT: Must be passed from order form

// Validate required fields
if (empty($uniform_type)) {
    die("Error: Uniform type is required!");
}

if ($school_id <= 0) {
    die("Error: School ID is required!");
}

if ($unit_price <= 0) {
    die("Error: Invalid price!");
}

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
    $downpayment = $total_amount;
} elseif ($downpayment > 0) {
    $payment_status = 'partial';
}

// Determine measurement
$measurement = ($size === 'custom') ? $custom_size : $size;

if (empty($measurement)) {
    die("Error: Size/measurement is required!");
}

// Check if this is a standard size order (needs stock update)
$standard_sizes = ['XS', 'S', 'M', 'L', 'XL'];
$measurement_upper = strtoupper(trim($measurement));
$is_standard_size = in_array($measurement_upper, $standard_sizes);

// Begin transaction - ensures all database changes happen together or not at all
mysqli_begin_transaction($conn);

try {
    // STEP 1: Handle stock update for standard sizes
    if ($is_standard_size && $uniform_id > 0) {
        // Map size to correct database column
        $stock_column = 'stock_' . strtolower($measurement_upper);

        // Get current stock for the selected size from uniform_options table
        $stockQuery = "SELECT id, uniform_name, $stock_column as current_stock 
                       FROM uniform_options 
                       WHERE id = ?";
        $stockStmt = mysqli_prepare($conn, $stockQuery);

        if (!$stockStmt) {
            throw new Exception("Failed to prepare stock query: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stockStmt, "i", $uniform_id);
        mysqli_stmt_execute($stockStmt);
        $stockResult = mysqli_stmt_get_result($stockStmt);
        $stock = mysqli_fetch_assoc($stockResult);

        if (!$stock) {
            throw new Exception("Uniform item (ID: {$uniform_id}) not found in database!");
        }

        $current_stock = intval($stock['current_stock']);
        $uniform_name = $stock['uniform_name'];

        // Validate sufficient stock
        if ($current_stock < $quantity) {
            throw new Exception("Insufficient stock for {$uniform_name} (Size: {$measurement_upper})! Available: {$current_stock}, Requested: {$quantity}");
        }

        // Calculate new stock after deduction based on customer's ordered quantity
        // Example: Current stock = 20, Customer orders 3, New stock = 17
        $new_stock = $current_stock - $quantity;

        // Ensure stock doesn't go negative (double-check safety)
        if ($new_stock < 0) {
            throw new Exception("Stock calculation error - result would be negative");
        }

        // Update stock in uniform_options table (this updates the SQL database directly)
        $updateStockQuery = "UPDATE uniform_options SET $stock_column = ? WHERE id = ?";
        $updateStockStmt = mysqli_prepare($conn, $updateStockQuery);

        if (!$updateStockStmt) {
            throw new Exception("Failed to prepare stock update: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($updateStockStmt, "ii", $new_stock, $uniform_id);

        if (!mysqli_stmt_execute($updateStockStmt)) {
            throw new Exception("Failed to update stock: " . mysqli_error($conn));
        }

        $affected_rows = mysqli_stmt_affected_rows($updateStockStmt);

        if ($affected_rows === 0) {
            throw new Exception("Stock update failed - no rows affected");
        }

        // Log the stock change for tracking
        error_log("[STOCK UPDATE] Uniform ID: {$uniform_id}, Name: {$uniform_name}, Size: {$measurement_upper}, Old Stock: {$current_stock}, New Stock: {$new_stock}, Quantity Ordered: {$quantity}");

        mysqli_stmt_close($updateStockStmt);
        mysqli_stmt_close($stockStmt);
    }

    // STEP 2: Insert into orders table with customer_id and school_id
    $orderInsert = "INSERT INTO orders (customer_id, staff_id, order_date, due_date, status, school_id) 
                    VALUES (?, NULL, NOW(), NULL, 'Pending', ?)";

    $orderStmt = mysqli_prepare($conn, $orderInsert);

    if (!$orderStmt) {
        throw new Exception("Failed to prepare order statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($orderStmt, "ii", $customer_id, $school_id);

    if (!mysqli_stmt_execute($orderStmt)) {
        throw new Exception("Failed to insert order: " . mysqli_error($conn));
    }

    // Get the inserted order_id
    $order_id = mysqli_insert_id($conn);

    if ($order_id <= 0) {
        throw new Exception("Failed to get order ID");
    }

    // STEP 3: Insert into payment table with calculated total_amount
    $paymentInsert = "INSERT INTO payment (order_id, total_amount, downpayment, payment_date, payment_method, payment_status) 
                      VALUES (?, ?, ?, NOW(), ?, ?)";

    $paymentStmt = mysqli_prepare($conn, $paymentInsert);

    if (!$paymentStmt) {
        throw new Exception("Failed to prepare payment statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $paymentStmt,
        "iddss",
        $order_id,
        $total_amount,
        $downpayment,
        $payment_method,
        $payment_status
    );

    if (!mysqli_stmt_execute($paymentStmt)) {
        throw new Exception("Failed to insert payment: " . mysqli_error($conn));
    }

    // STEP 4: Insert into order_item table with customer_id, school_id, quantity and unit price
    $itemInsert = "INSERT INTO order_item (order_id, customer_id, item_type, measurement, quantity, status, school_id, price) 
                   VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)";

    $itemStmt = mysqli_prepare($conn, $itemInsert);

    if (!$itemStmt) {
        throw new Exception("Failed to prepare order item statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $itemStmt,
        "iissiid",
        $order_id,
        $customer_id,    // Populates customer_id in order_item
        $uniform_type,
        $measurement,
        $quantity,
        $school_id,      // Populates school_id in order_item
        $unit_price
    );

    if (!mysqli_stmt_execute($itemStmt)) {
        throw new Exception("Failed to insert order item: " . mysqli_error($conn));
    }

    // Verify the insert was successful
    $order_item_id = mysqli_insert_id($conn);
    if ($order_item_id <= 0) {
        throw new Exception("Failed to get order item ID");
    }

    // STEP 5: Commit transaction - All changes are now permanent in database
    mysqli_commit($conn);

    // Close all statements
    mysqli_stmt_close($orderStmt);
    mysqli_stmt_close($paymentStmt);
    mysqli_stmt_close($itemStmt);

    // Prepare success message with stock info
    if ($is_standard_size) {
        $success_message = "Order placed successfully! Stock updated: {$uniform_name} (Size {$measurement_upper}) - Remaining stock: {$new_stock}";
        error_log("[ORDER SUCCESS] Order ID: {$order_id}, Customer ID: {$customer_id}, Stock updated successfully");
    } else {
        $success_message = "Custom order placed successfully! Your measurements have been recorded.";
        error_log("[ORDER SUCCESS] Order ID: {$order_id}, Customer ID: {$customer_id}, Custom measurement order");
    }

    // Redirect with success to profile page
    header("Location: profile.php?order=success&order_id=" . $order_id . "&message=" . urlencode($success_message));
    exit();
} catch (Exception $e) {
    // Rollback on error - This automatically reverts ALL changes including stock updates
    mysqli_rollback($conn);

    // Log the error for debugging
    error_log("[ORDER ERROR] Customer ID: {$customer_id}, Error: " . $e->getMessage());

    // Show user-friendly error
    die("Order failed: " . htmlspecialchars($e->getMessage()) . "<br><br><a href='uni_offered.php'>← Go Back to Uniforms</a>");
}
