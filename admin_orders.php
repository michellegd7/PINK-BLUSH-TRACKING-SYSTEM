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

// Fetch all orders with customer info, order items, and payment details
$query = "SELECT 
    o.order_id,
    o.customer_id,
    o.order_date,
    o.due_date,
    o.status,
    o.school_id,
    c.first_name,
    c.last_name,
    c.email,
    c.contact_number,
    GROUP_CONCAT(DISTINCT oi.item_type SEPARATOR ', ') as order_items,
    SUM(oi.quantity) as total_quantity,
    GROUP_CONCAT(DISTINCT oi.measurement SEPARATOR ', ') as sizes,
    p.payment_status,
    p.total_amount,
    p.downpayment,
    p.balance
FROM orders o 
LEFT JOIN customer c ON o.customer_id = c.customer_id 
LEFT JOIN order_item oi ON o.order_id = oi.order_id
LEFT JOIN payment p ON o.order_id = p.order_id
GROUP BY o.order_id
ORDER BY o.order_date DESC";

$result = mysqli_query($conn, $query);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders</title>
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

        /* Top Navigation */
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
            color: white;
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
        }

        /* Main Content */
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

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .table-subtitle {
            color: #718096;
            font-size: 0.9rem;
        }

        /* Table Styles */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .orders-table thead {
            background: #f7fafc;
        }

        .orders-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .orders-table tbody tr:hover {
            background: #f7fafc;
        }

        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Customer Info */
        .customer-name {
            font-weight: 600;
            color: #2d3748;
            display: block;
        }

        .customer-contact {
            font-size: 0.85rem;
            color: #718096;
            display: block;
            margin-top: 2px;
        }

        /* Order Items */
        .order-items {
            font-weight: 500;
            color: #2d3748;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-pending {
            background: #feebc8;
            color: #c05621;
        }

        .status-payment_confirmation,
        .status-order_confirmation {
            background: #bee3f8;
            color: #2c5282;
        }

        .status-measurement,
        .status-material_preparation,
        .status-cutting,
        .status-sewing {
            background: #e9d8fd;
            color: #553c9a;
        }

        .status-finishing,
        .status-quality_checking {
            background: #fef5e7;
            color: #d68910;
        }

        .status-ready_for_pickup,
        .status-final_payment {
            background: #d4edda;
            color: #2f855a;
        }

        .status-completed {
            background: #c6f6d5;
            color: #22543d;
        }

        /* Payment Status */
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

        /* Amount */
        .amount {
            font-weight: 600;
            color: #2d3748;
        }

        /* Due Date */
        .due-date {
            color: #718096;
            font-size: 0.9rem;
        }

        .due-date.urgent {
            color: #e53e3e;
            font-weight: 600;
        }

        .due-date.soon {
            color: #dd6b20;
            font-weight: 500;
        }

        /* Action Buttons */
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3182ce;
        }

        .btn-danger {
            background-color: #e63946;
            color: white;
            margin-left: 5px;
        }

        .btn-danger:hover {
            background-color: #d62828;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            white-space: nowrap;
        }

        .action-buttons form {
            display: inline;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .orders-table {
                font-size: 0.85rem;
            }

            .orders-table th,
            .orders-table td {
                padding: 10px 8px;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .main-content {
                padding: 0 1rem;
            }

            .table-container {
                overflow-x: auto;
            }

            .orders-table {
                min-width: 1200px;
            }
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="logo">
                <span>📦</span>
                <span>Order Manager</span>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item active"><a href="admin_orders.php">Orders</a></li>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Customer Orders</h1>
            <p class="page-subtitle">View and manage all submitted orders</p>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Orders List</h3>
                    <p class="table-subtitle">Click edit to update order details</p>
                </div>

                <table class="orders-table">
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
                        <?php foreach ($orders as $order):
                            // Calculate days until due date
                            $dueDate = $order['due_date'];
                            $dueDateClass = '';
                            $dueDateText = 'Not set';

                            if ($dueDate && $dueDate != '0000-00-00') {
                                $today = new DateTime();
                                $due = new DateTime($dueDate);
                                $diff = $today->diff($due);
                                $daysUntil = (int)$diff->format('%R%a');

                                if ($daysUntil < 0) {
                                    $dueDateClass = 'urgent';
                                    $dueDateText = 'Overdue';
                                } elseif ($daysUntil <= 3) {
                                    $dueDateClass = 'soon';
                                    $dueDateText = date('M j, Y', strtotime($dueDate));
                                } else {
                                    $dueDateText = date('M j, Y', strtotime($dueDate));
                                }
                            }
                        ?>
                            <tr>
                                <td><strong>#<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></strong></td>

                                <td>
                                    <span class="customer-name"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></span>
                                    <span class="customer-contact"><?= htmlspecialchars($order['contact_number'] ?? '') ?></span>
                                </td>

                                <td>
                                    <span class="order-items"><?= htmlspecialchars($order['order_items'] ?? '—') ?></span>
                                </td>

                                <td><?= htmlspecialchars($order['total_quantity'] ?? 0) ?></td>

                                <td><?= htmlspecialchars($order['sizes'] ?? '—') ?></td>

                                <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>

                                <td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '_', $order['status'])) ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status-badge payment-<?= strtolower($order['payment_status'] ?? 'unpaid') ?>">
                                        <?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="amount">₱<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                                </td>

                                <td>
                                    <span class="due-date <?= $dueDateClass ?>">
                                        <?= $dueDateText ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <a href="admin_edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-primary">Edit</a>
                                        <form action="admin_delete_order.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3>No orders found</h3>
                    <p>There are no orders in the system yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
