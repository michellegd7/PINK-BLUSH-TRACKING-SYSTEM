<?php
session_start();
include 'database.php';

// Check if user is logged in and is a master cutter
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff' || $_SESSION['staff_role'] !== 'master cutter') {
    header('Location: staff_login.php');
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['user'];
$success = '';
$error = '';

// Handle status update for custom orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $item_type = $_POST['item_type'];
    $new_status = $_POST['new_status'];

    // Update the order_item status
    $stmt = $conn->prepare("UPDATE order_item SET status = ? WHERE order_id = ? AND item_type = ?");
    $stmt->bind_param("sis", $new_status, $order_id, $item_type);

    if ($stmt->execute()) {
        // Also update the main order status if it's a major milestone
        $major_statuses = ['Cutting', 'Material Preparation', 'Measurement'];
        if (in_array($new_status, $major_statuses)) {
            $update_order = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $update_order->bind_param("si", $new_status, $order_id);
            $update_order->execute();
            $update_order->close();
        }

        $success = "Order status updated successfully! Status is now visible across all dashboards.";
    } else {
        $error = "Error updating order status.";
    }
    $stmt->close();
}

// Handle stock quantity update for all sizes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $uniform_id = $_POST['uniform_id'];
    $stock_xs = intval($_POST['stock_xs']);
    $stock_s = intval($_POST['stock_s']);
    $stock_m = intval($_POST['stock_m']);
    $stock_l = intval($_POST['stock_l']);
    $stock_xl = intval($_POST['stock_xl']);

    $stmt = $conn->prepare("UPDATE uniform_options SET stock_xs = ?, stock_s = ?, stock_m = ?, stock_l = ?, stock_xl = ? WHERE id = ?");
    $stmt->bind_param("iiiiii", $stock_xs, $stock_s, $stock_m, $stock_l, $stock_xl, $uniform_id);

    if ($stmt->execute()) {
        $success = "Stock quantities updated successfully!";
    } else {
        $error = "Error updating stock quantities.";
    }
    $stmt->close();
}

// Get custom orders with measurements - Updated to get all custom measurements
$custom_orders_query = "
    SELECT 
        o.order_id,
        o.order_date,
        c.first_name,
        c.last_name,
        c.contact_number,
        oi.order_item_id,
        oi.item_type,
        oi.measurement,
        oi.quantity,
        oi.price,
        oi.status as item_status,
        o.status as order_status
    FROM order_item oi
    LEFT JOIN orders o ON oi.order_id = o.order_id
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    WHERE oi.measurement NOT IN ('XS', 'S', 'M', 'L', 'XL', '', 'NULL')
    AND oi.measurement IS NOT NULL
    ORDER BY o.order_date DESC
    LIMIT 50
";
$custom_orders = $conn->query($custom_orders_query);

// Get uniform stock information
$uniform_stock_query = "
    SELECT * FROM uniform_options 
    ORDER BY school_id, uniform_type, uniform_name
";
$uniform_stock = $conn->query($uniform_stock_query);

// Count statistics
$total_uniforms = $conn->query("SELECT COUNT(*) as count FROM uniform_options")->fetch_assoc()['count'];
$total_stock = $conn->query("SELECT SUM(stock_xs + stock_s + stock_m + stock_l + stock_xl) as total FROM uniform_options")->fetch_assoc()['total'];
$custom_orders_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM order_item 
    WHERE measurement NOT IN ('XS', 'S', 'M', 'L', 'XL', '', 'NULL')
    AND measurement IS NOT NULL
")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Cutter Dashboard - Pink Blush Tailoring</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .role-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .header h1 {
            font-size: 24px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-role {
            font-size: 13px;
            opacity: 0.9;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: white;
            color: #f59e0b;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            font-size: 40px;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .stat-icon.total {
            background: #dbeafe;
        }

        .stat-icon.items {
            background: #fef3c7;
        }

        .stat-icon.custom {
            background: #fce7f3;
        }

        .stat-info h3 {
            font-size: 32px;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #718096;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px 25px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge {
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .section-body {
            padding: 25px;
        }

        .info-banner {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 13px;
            color: #0c4a6e;
        }

        .info-banner strong {
            color: #075985;
        }

        /* Table Styles */
        .stock-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stock-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .stock-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #2d3748;
            vertical-align: top;
        }

        .stock-table tr:hover {
            background: #f7fafc;
        }

        .uniform-name {
            font-weight: 600;
            color: #2d3748;
        }

        .uniform-type {
            font-size: 12px;
            color: #718096;
            margin-top: 2px;
        }

        .customer-name {
            font-weight: 600;
            color: #2d3748;
        }

        .customer-contact {
            font-size: 12px;
            color: #718096;
            margin-top: 2px;
        }

        .measurement-text {
            font-size: 13px;
            color: #2d3748;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.5;
            max-height: 150px;
            overflow-y: auto;
        }

        .size-stocks {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .size-stock-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .size-label {
            font-size: 11px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
        }

        .stock-input {
            width: 60px;
            padding: 6px 8px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
            font-weight: 600;
        }

        .stock-input:focus {
            outline: none;
            border-color: #f59e0b;
        }

        .btn-update {
            padding: 8px 16px;
            font-size: 13px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-update:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .stock-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customizable-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }

        .badge-yes {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-no {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-order-confirmation {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-payment-confirmation {
            background: #fef3c7;
            color: #92400e;
        }

        .status-measurement {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-material-preparation {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-cutting {
            background: #fed7aa;
            color: #9a3412;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .status-select {
            padding: 6px 10px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            background: white;
            color: #2d3748;
            cursor: pointer;
            min-width: 160px;
        }

        .status-select:focus {
            outline: none;
            border-color: #f59e0b;
        }

        .status-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-update-status {
            padding: 6px 12px;
            font-size: 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-update-status:hover {
            background: #059669;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <h1>✂️ Pink Blush Tailoring</h1>
            <div class="role-badge">
                Master Cutter Portal
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($staff_name); ?></div>
                <div class="user-role">Master Cutter Staff</div>
            </div>
            <a href="staff_login.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">📊</div>
                <div class="stat-info">
                    <h3><?php echo $total_stock ?: 0; ?></h3>
                    <p>Total Stock Items</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon items">👕</div>
                <div class="stat-info">
                    <h3><?php echo $total_uniforms; ?></h3>
                    <p>Uniform Types</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon custom">📐</div>
                <div class="stat-info">
                    <h3><?php echo $custom_orders_count; ?></h3>
                    <p>Custom Orders</p>
                </div>
            </div>
        </div>

        <!-- Custom Orders Section -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">
                    📐 Custom Measurement Orders
                    <span class="badge"><?php echo $custom_orders_count; ?></span>
                </div>
            </div>
            <div class="section-body" style="padding: 0; overflow-x: auto;">
                <?php if ($custom_orders && $custom_orders->num_rows > 0): ?>
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Item Type</th>
                                <th style="min-width: 300px;">Custom Measurements</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Current Status</th>
                                <th>Order Date</th>
                                <th style="min-width: 200px;">Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $custom_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="customer-name">
                                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        </div>
                                        <div class="customer-contact">
                                            <?php echo htmlspecialchars($order['contact_number'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['item_type']); ?></td>
                                    <td>
                                        <div class="measurement-text">
                                            <?php
                                            // Display measurements simply
                                            $measurements = $order['measurement'];
                                            echo htmlspecialchars($measurements);
                                            ?>
                                        </div>
                                    </td>
                                    <td><?php echo $order['quantity']; ?></td>
                                    <td>₱<?php echo number_format($order['price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace([' ', '/'], ['-', '-'], $order['item_status'])); ?>">
                                            <?php echo htmlspecialchars($order['item_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($order['item_type']); ?>">
                                            <select name="new_status" class="status-select" required>
                                                <option value="">Select Status</option>
                                                <option value="Order Confirmation" <?php echo $order['item_status'] == 'Order Confirmation' ? 'selected' : ''; ?>>Order Confirmation</option>
                                                <option value="Payment Confirmation" <?php echo $order['item_status'] == 'Payment Confirmation' ? 'selected' : ''; ?>>Payment Confirmation</option>
                                                <option value="Measurement" <?php echo $order['item_status'] == 'Measurement' ? 'selected' : ''; ?>>Measurement</option>
                                                <option value="Material Preparation" <?php echo $order['item_status'] == 'Material Preparation' ? 'selected' : ''; ?>>Material Preparation</option>
                                                <option value="Cutting" <?php echo $order['item_status'] == 'Cutting' ? 'selected' : ''; ?>>Cutting</option>
                                                <option value="Sewing" <?php echo $order['item_status'] == 'Sewing' ? 'selected' : ''; ?>>Sewing</option>
                                                <option value="Finishing" <?php echo $order['item_status'] == 'Finishing' ? 'selected' : ''; ?>>Finishing</option>
                                                <option value="Quality Checking" <?php echo $order['item_status'] == 'Quality Checking' ? 'selected' : ''; ?>>Quality Checking</option>
                                                <option value="Final Pressing / Packaging" <?php echo $order['item_status'] == 'Final Pressing / Packaging' ? 'selected' : ''; ?>>Final Pressing / Packaging</option>
                                                <option value="Ready for Pickup" <?php echo $order['item_status'] == 'Ready for Pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                                <option value="Processing" <?php echo $order['item_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Completed" <?php echo $order['item_status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <button type="submit" name="update_order_status" class="btn-update-status">✓ Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>📐 No custom measurement orders found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Uniform Stock Management -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">
                    📦 Uniform Stock Management
                </div>
            </div>
            <div class="section-body" style="padding: 0; overflow-x: auto;">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Uniform Name</th>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 10%;">Customizable</th>
                            <th style="width: 35%;">Stock Quantities by Size</th>
                            <th style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($uniform_stock->num_rows > 0): ?>
                            <?php while ($uniform = $uniform_stock->fetch_assoc()):
                                $sizes = explode(',', str_replace(' ', '', $uniform['available_sizes']));
                            ?>
                                <tr>
                                    <td>
                                        <div class="uniform-name"><?php echo htmlspecialchars($uniform['uniform_name']); ?></div>
                                        <div class="uniform-type">School ID: <?php echo $uniform['school_id']; ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($uniform['uniform_type']); ?></td>
                                    <td>
                                        <span class="customizable-badge <?php echo $uniform['customizable'] ? 'badge-yes' : 'badge-no'; ?>">
                                            <?php echo $uniform['customizable'] ? '✓ Yes' : '✗ No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="stock-form">
                                            <input type="hidden" name="uniform_id" value="<?php echo $uniform['id']; ?>">
                                            <div class="size-stocks">
                                                <?php if (in_array('XS', $sizes)): ?>
                                                    <div class="size-stock-item">
                                                        <span class="size-label">XS</span>
                                                        <input type="number" name="stock_xs" class="stock-input" value="<?php echo $uniform['stock_xs']; ?>" min="0">
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="stock_xs" value="0">
                                                <?php endif; ?>

                                                <?php if (in_array('S', $sizes)): ?>
                                                    <div class="size-stock-item">
                                                        <span class="size-label">S</span>
                                                        <input type="number" name="stock_s" class="stock-input" value="<?php echo $uniform['stock_s']; ?>" min="0">
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="stock_s" value="0">
                                                <?php endif; ?>

                                                <?php if (in_array('M', $sizes)): ?>
                                                    <div class="size-stock-item">
                                                        <span class="size-label">M</span>
                                                        <input type="number" name="stock_m" class="stock-input" value="<?php echo $uniform['stock_m']; ?>" min="0">
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="stock_m" value="0">
                                                <?php endif; ?>

                                                <?php if (in_array('L', $sizes)): ?>
                                                    <div class="size-stock-item">
                                                        <span class="size-label">L</span>
                                                        <input type="number" name="stock_l" class="stock-input" value="<?php echo $uniform['stock_l']; ?>" min="0">
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="stock_l" value="0">
                                                <?php endif; ?>

                                                <?php if (in_array('XL', $sizes)): ?>
                                                    <div class="size-stock-item">
                                                        <span class="size-label">XL</span>
                                                        <input type="number" name="stock_xl" class="stock-input" value="<?php echo $uniform['stock_xl']; ?>" min="0">
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="stock_xl" value="0">
                                                <?php endif; ?>
                                            </div>
                                    </td>
                                    <td>
                                        <button type="submit" name="update_stock" class="btn-update">💾 Update Stock</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #718096;">
                                    No uniform items found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
