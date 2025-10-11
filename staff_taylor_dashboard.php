<?php
session_start();
include 'database.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: staff_login.php');
    exit();
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['user'];
$staff_role = $_SESSION['staff_role'];
$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET status = ?, staff_id = ? WHERE order_id = ?");
    $stmt->bind_param("sii", $new_status, $staff_id, $order_id);

    if ($stmt->execute()) {
        $success = "Order #$order_id status updated to '$new_status'!";
    } else {
        $error = "Error updating order status.";
    }
    $stmt->close();
}

// Handle order completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $order_id = $_POST['order_id'];

    $stmt = $conn->prepare("UPDATE orders SET status = 'Completed', staff_id = ? WHERE order_id = ?");
    $stmt->bind_param("ii", $staff_id, $order_id);

    if ($stmt->execute()) {
        $success = "Order #$order_id marked as completed!";
    } else {
        $error = "Error completing order.";
    }
    $stmt->close();
}

// Get available orders for staff with order items
$available_orders_query = "
    SELECT o.*, 
           CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
           c.contact_number,
           GROUP_CONCAT(DISTINCT CONCAT(oi.item_type, ' (', oi.measurement, ')') ORDER BY oi.item_type SEPARATOR ', ') as order_items
    FROM orders o 
    LEFT JOIN customer c ON o.customer_id = c.customer_id 
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    WHERE o.status NOT IN ('Completed', 'Cancelled')
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT 50
";
$available_orders = $conn->query($available_orders_query);

// Get completed orders with order items
$completed_orders_query = "
    SELECT o.*, 
           CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
           c.contact_number,
           GROUP_CONCAT(DISTINCT CONCAT(oi.item_type, ' (', oi.measurement, ')') ORDER BY oi.item_type SEPARATOR ', ') as order_items
    FROM orders o 
    LEFT JOIN customer c ON o.customer_id = c.customer_id 
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    WHERE o.status = 'Completed' AND o.staff_id = ? 
    GROUP BY o.order_id
    ORDER BY o.order_date DESC 
    LIMIT 20
";
$stmt = $conn->prepare($completed_orders_query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$completed_orders = $stmt->get_result();
$stmt->close();

// Count statistics
$available_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status NOT IN ('Completed', 'Cancelled')")->fetch_assoc()['count'];
$completed_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed' AND staff_id = $staff_id")->fetch_assoc()['count'];
$total_completed = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Pink Blush Tailoring</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
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

        .stat-icon.available {
            background: #e0f2fe;
        }

        .stat-icon.completed {
            background: #d1fae5;
        }

        .stat-icon.total {
            background: #fef3c7;
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
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .section-body {
            padding: 25px;
        }

        .orders-grid {
            display: grid;
            gap: 20px;
        }

        .order-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }

        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-id {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .status-sewing {
            background: #fecaca;
            color: #991b1b;
        }

        .status-finishing {
            background: #e9d5ff;
            color: #6b21a8;
        }

        .status-quality-checking {
            background: #fef3c7;
            color: #854d0e;
        }

        .status-final-pressing---packaging {
            background: #ddd6fe;
            color: #5b21b6;
        }

        .status-ready-for-pickup {
            background: #bfdbfe;
            color: #1e40af;
        }

        .status-final-payment {
            background: #fed7aa;
            color: #9a3412;
        }

        .status-pickup-by-the-customer {
            background: #bbf7d0;
            color: #15803d;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-in-progress {
            background: #e0e7ff;
            color: #3730a3;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-item.full-width {
            grid-column: 1 / -1;
        }

        .detail-label {
            font-size: 12px;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 14px;
            color: #2d3748;
            font-weight: 500;
        }

        .order-items-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-top: 4px;
        }

        .order-items-box .detail-value {
            color: #4a5568;
            line-height: 1.6;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .status-select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .status-select:hover {
            border-color: #667eea;
        }

        .status-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state-text {
            font-size: 16px;
            font-weight: 500;
        }

        .due-date-warning {
            color: #dc2626;
            font-weight: 600;
        }

        .due-date-normal {
            color: #059669;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <h1>✂️ Pink Blush Tailoring</h1>
            <div class="role-badge">
                Tailor Portal
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($staff_name); ?></div>
                <div class="user-role">Tailor Staff</div>
            </div>
            <a href="staff_logout.php" class="logout-btn">Logout</a>
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
                <div class="stat-icon available">📋</div>
                <div class="stat-info">
                    <h3><?php echo $available_count; ?></h3>
                    <p>Available Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon completed">✅</div>
                <div class="stat-info">
                    <h3><?php echo $completed_count; ?></h3>
                    <p>My Completed</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon total">📊</div>
                <div class="stat-info">
                    <h3><?php echo $total_completed; ?></h3>
                    <p>Total Completed</p>
                </div>
            </div>
        </div>

        <!-- Available Orders -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">
                    📋 Available Orders
                    <span class="badge"><?php echo $available_count; ?></span>
                </div>
            </div>
            <div class="section-body">
                <?php if ($available_orders->num_rows > 0): ?>
                    <div class="orders-grid">
                        <?php while ($order = $available_orders->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">Order #<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                    <span class="status-badge status-<?php echo strtolower(str_replace([' ', '/'], ['-', '---'], $order['status'])); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Customer</span>
                                        <span class="detail-value">👤 <?php echo htmlspecialchars($order['customer_name'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Contact</span>
                                        <span class="detail-value">📞 <?php echo htmlspecialchars($order['contact_number'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Order Date</span>
                                        <span class="detail-value">📅 <?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Due Date</span>
                                        <?php if ($order['due_date'] && $order['due_date'] != '0000-00-00'): ?>
                                            <span class="detail-value <?php echo (strtotime($order['due_date']) < strtotime('+3 days')) ? 'due-date-warning' : 'due-date-normal'; ?>">
                                                ⏰ <?php echo date('M d, Y', strtotime($order['due_date'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="detail-value">⏰ Not set</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="detail-item full-width">
                                        <span class="detail-label">Order Items & Measurements</span>
                                        <div class="order-items-box">
                                            <span class="detail-value">
                                                <?php
                                                if (!empty($order['order_items'])) {
                                                    echo "📦 " . htmlspecialchars($order['order_items']);
                                                } else {
                                                    echo "📦 No items found";
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-actions">
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="status" class="status-select" required style="margin-bottom: 10px;">
                                            <option value="">Select Status</option>
                                            <option value="Order Confirmation" <?php echo ($order['status'] == 'Order Confirmation') ? 'selected' : ''; ?>>Order Confirmation</option>
                                            <option value="Payment Confirmation" <?php echo ($order['status'] == 'Payment Confirmation') ? 'selected' : ''; ?>>Payment Confirmation</option>
                                            <option value="Measurement" <?php echo ($order['status'] == 'Measurement') ? 'selected' : ''; ?>>Measurement</option>
                                            <option value="Material Preparation" <?php echo ($order['status'] == 'Material Preparation') ? 'selected' : ''; ?>>Material Preparation</option>
                                            <option value="Cutting" <?php echo ($order['status'] == 'Cutting') ? 'selected' : ''; ?>>Cutting</option>
                                            <option value="Sewing" <?php echo ($order['status'] == 'Sewing') ? 'selected' : ''; ?>>Sewing</option>
                                            <option value="Finishing" <?php echo ($order['status'] == 'Finishing') ? 'selected' : ''; ?>>Finishing</option>
                                            <option value="Quality Checking" <?php echo ($order['status'] == 'Quality Checking') ? 'selected' : ''; ?>>Quality Checking</option>
                                            <option value="Final Pressing / Packaging" <?php echo ($order['status'] == 'Final Pressing / Packaging') ? 'selected' : ''; ?>>Final Pressing / Packaging</option>
                                            <option value="Ready for Pickup" <?php echo ($order['status'] == 'Ready for Pickup') ? 'selected' : ''; ?>>Ready for Pickup</option>
                                            <option value="Final Payment" <?php echo ($order['status'] == 'Final Payment') ? 'selected' : ''; ?>>Final Payment</option>
                                            <option value="Pickup by the Customer" <?php echo ($order['status'] == 'Pickup by the Customer') ? 'selected' : ''; ?>>Pickup by the Customer</option>
                                            <option value="Completed" <?php echo ($order['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                                            🔄 Update Status
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <div class="empty-state-text">No available orders at the moment</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recently Completed Orders -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">
                    ✅ Recently Completed
                    <span class="badge"><?php echo $completed_count; ?></span>
                </div>
            </div>
            <div class="section-body">
                <?php if ($completed_orders->num_rows > 0): ?>
                    <div class="orders-grid">
                        <?php while ($order = $completed_orders->fetch_assoc()): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-id">Order #<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                    <span class="status-badge status-completed">Completed</span>
                                </div>
                                <div class="order-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Customer</span>
                                        <span class="detail-value">👤 <?php echo htmlspecialchars($order['customer_name'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Contact</span>
                                        <span class="detail-value">📞 <?php echo htmlspecialchars($order['contact_number'] ?: 'N/A'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Order Date</span>
                                        <span class="detail-value">📅 <?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Completed By</span>
                                        <span class="detail-value">👤 You</span>
                                    </div>
                                    <div class="detail-item full-width">
                                        <span class="detail-label">Order Items & Measurements</span>
                                        <div class="order-items-box">
                                            <span class="detail-value">
                                                <?php
                                                if (!empty($order['order_items'])) {
                                                    echo "📦 " . htmlspecialchars($order['order_items']);
                                                } else {
                                                    echo "📦 No items found";
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-text">No completed orders yet</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
