<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Get logged-in user's full name
$admin_name = $_SESSION['user'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));

include 'database.php';

// ✅ FIXED: Get recent orders with customer info AND payment data
$recent_orders = [];
$query = "SELECT o.*, c.first_name, c.last_name, c.contact_number,
          p.payment_status, p.payment_method, p.total_amount as payment_total
          FROM orders o 
          LEFT JOIN customer c ON o.customer_id = c.customer_id 
          LEFT JOIN payment p ON o.order_id = p.order_id
          ORDER BY o.order_date DESC 
          LIMIT 6";
$result = $conn->query($query);
if ($result) {
    $recent_orders = $result->fetch_all(MYSQLI_ASSOC);
}

// Get order items for each order and calculate total amount
foreach ($recent_orders as &$order) {
    $order_id = $order['order_id'];

    // Get order items with measurement (size) information
    $items_query = "SELECT oi.*, u.uniform_name AS product_name
                FROM order_item oi
                LEFT JOIN uniform_options u ON oi.item_type = u.uniform_name
                WHERE oi.order_id = ?";

    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ✅ FIXED: Use payment table's total_amount if available, otherwise calculate
    if (!isset($order['payment_total']) || $order['payment_total'] == 0) {
        $total_amount = 0;
        foreach ($order['items'] as $item) {
            $total_amount += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
        }
        $order['total_amount'] = $total_amount;
    } else {
        $order['total_amount'] = $order['payment_total'];
    }

    // ✅ FIXED: Payment status now comes from payment table (via JOIN)
    if (!isset($order['payment_status']) || empty($order['payment_status'])) {
        $order['payment_status'] = 'Unpaid';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }

        .top-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu {
            display: flex;
            gap: 1.5rem;
            list-style: none;
        }

        .nav-item {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .nav-item.active,
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatar {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }

        .main-content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #718096;
            font-size: 1.1rem;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1.5rem;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            color: #4a5568;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #f7fafc;
        }

        .btn-primary {
            background: #4299e1;
            border-color: #4299e1;
            color: white;
            margin-left: 0.5rem;
        }

        .btn-primary:hover {
            background: #3182ce;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th,
        td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            white-space: nowrap;
        }

        tr:hover {
            background: #f7fafc;
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #bee3f8;
            color: #2b6cb0;
        }

        .status-completed {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-delivered {
            background: #e9d8fd;
            color: #805ad5;
        }

        .payment-unpaid {
            background: #fed7d7;
            color: #c53030;
        }

        .payment-partial {
            background: #feebc8;
            color: #c05621;
        }

        .payment-paid {
            background: #c6f6d5;
            color: #2f855a;
        }

        .customer-info {
            font-weight: 500;
        }

        .customer-info small {
            display: block;
            color: #718096;
            font-weight: 400;
            font-size: 0.8rem;
        }

        .order-items {
            max-width: 180px;
        }

        .items-summary {
            font-size: 0.8rem;
            color: #718096;
            font-style: italic;
        }

        .payment-info {
            text-align: right;
        }

        .amount {
            font-weight: 600;
            color: #2d3748;
            display: block;
            margin-bottom: 0.25rem;
        }

        .payment-method {
            font-size: 0.75rem;
            color: #718096;
        }

        .due-date {
            color: #e53e3e;
            font-weight: 500;
        }

        .due-date.safe {
            color: #38a169;
        }

        @media (max-width: 1200px) {
            .order-items {
                max-width: 150px;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .main-content {
                padding: 0 1rem;
            }

            th,
            td {
                padding: 0.75rem 0.5rem;
                font-size: 0.8rem;
            }

            .order-items {
                max-width: 120px;
            }
        }
    </style>
</head>

<body>
    <nav class="top-nav">
        <div class="nav-container">
            <div class="logo">
                <span>✂️</span>
                <span>Master Tailor</span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item active">Dashboard</li>
                <li class="nav-item"><a href="admin_orders.php">Orders</a></li>
                <li class="nav-item"><a href="admin_customer.php">Customers</a></li>
                <li class="nav-item"><a href="admin_products.php">Products</a></li>
                <li class="nav-item"><a href="admin_stocks.php">Stocks</a></li>
            </ul>

            <div class="user-info">
                <span><?php echo htmlspecialchars($admin_name); ?></span>
                <div class="avatar"><?php echo $admin_initial; ?></div>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Pink Blush Tailoring Dashboard</h1>
            <p class="page-subtitle">Track custom orders and manage your tailoring workflow</p>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3 class="card-title">Recent Orders</h3>
                <div>
                    <a href="admin_orders.php" class="btn">View All</a>
                    <a href="admin_create_order.php" class="btn btn-primary">+ New Order</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Items</th>
                        <th>Quantity</th>
                        <th>Size</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?></td>

                                <td class="customer-info">
                                    <?php if ($order['first_name']): ?>
                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        <small><?php echo htmlspecialchars($order['contact_number'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <span style="color: #a0aec0;">No customer</span>
                                    <?php endif; ?>
                                </td>

                                <td class="order-items">
                                    <?php if (!empty($order['items'])): ?>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <span class="item-badge">
                                                <?php echo htmlspecialchars($item['product_name'] ?? 'Unknown'); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="items-summary">No items</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($order['items'])): ?>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <span style="font-weight: 500;"> <?php echo $item['quantity']; ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #a0aec0;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($order['items'])): ?>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <?php if (!empty($item['measurement'])): ?>
                                                <span class="size-badge"><?php echo htmlspecialchars($item['measurement']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #a0aec0; font-size: 0.75rem;">N/A</span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #a0aec0; font-size: 0.75rem;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>

                                <td>
                                    <span class="status status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status payment-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo htmlspecialchars($order['payment_status']); ?>
                                    </span>
                                    <?php if (isset($order['payment_method']) && $order['payment_method']): ?>
                                        <div class="payment-method"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                                    <?php endif; ?>
                                </td>

                                <td class="payment-info">
                                    <span class="amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                </td>

                                <td class="<?php echo (isset($order['due_date']) && $order['due_date'] && $order['due_date'] != '0000-00-00' && strtotime($order['due_date']) > time()) ? 'due-date safe' : 'due-date'; ?>">
                                    <?php if (isset($order['due_date']) && $order['due_date'] && $order['due_date'] != '0000-00-00'): ?>
                                        <?php echo date('M d, Y', strtotime($order['due_date'])); ?>
                                    <?php else: ?>
                                        <span style="color: #a0aec0;">Not set</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="admin_edit_order.php?id=<?php echo $order['order_id']; ?>" class="btn">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center; color: #718096; font-style: italic; padding: 2rem;">No orders found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
