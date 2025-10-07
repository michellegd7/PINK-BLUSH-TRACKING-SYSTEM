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

// Fetch all orders with customer info
$query = "SELECT o.*, c.first_name, c.last_name, c.email 
          FROM orders o 
          LEFT JOIN customer c ON o.customer_id = c.customer_id 
          ORDER BY o.order_date DESC";


$result = mysqli_query($conn, $query);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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
            max-width: 1200px;
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

        .nav-item.active a {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            padding: 0.5rem 1rem;
        }


        .nav-item:hover,
        .nav-item.active {
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

        .btn-danger {
            background-color: #e63946;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-danger:hover {
            background-color: #d62828;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f7fafc;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Order Tracking Timeline */
        .timeline {
            position: relative;
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 40px;
            bottom: -15px;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            z-index: 1;
        }

        .timeline-icon.completed {
            background: #c6f6d5;
            color: #2f855a;
        }

        .timeline-icon.current {
            background: #bee3f8;
            color: #2b6cb0;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }

        .timeline-icon.pending {
            background: #f7fafc;
            color: #a0aec0;
        }

        .timeline-content h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .timeline-content p {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .timeline-date {
            color: #a0aec0;
            font-size: 0.8rem;
        }

        /* Order Table */
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .btn:hover {
            background: #f7fafc;
        }

        .btn-primary {
            background: #4299e1;
            border-color: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background: #3182ce;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }

        tr:hover {
            background: #f7fafc;
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-measuring {
            background: #fed7d7;
            color: #c53030;
        }

        .status-cutting {
            background: #feebc8;
            color: #dd6b20;
        }

        .status-sewing {
            background: #bee3f8;
            color: #2b6cb0;
        }

        .status-fitting {
            background: #e6fffa;
            color: #319795;
        }

        .status-completed {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-delivered {
            background: #e9d8fd;
            color: #805ad5;
        }

        .customer-info {
            font-weight: 500;
        }

        .customer-info small {
            display: block;
            color: #718096;
            font-weight: 400;
        }

        .garment-type {
            font-weight: 500;
            color: #4a5568;
        }

        .price {
            font-weight: 600;
            color: #2d3748;
        }

        .due-date {
            color: #e53e3e;
            font-weight: 500;
        }

        .due-date.safe {
            color: #38a169;
        }

        .btn-primary {
            background-color: #4a90e2;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-danger {
            background-color: #e63946;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #357ab8;
        }

        .btn-danger:hover {
            background-color: #d62828;
        }


        /* Recent Orders */
        .recent-order {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .recent-order:last-child {
            border-bottom: none;
        }

        .order-info h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .order-info p {
            color: #718096;
            font-size: 0.9rem;
        }

        .order-status {
            text-align: right;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 0 1rem;
            }

            th,
            td {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
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
                <li class="nav-item active">Orders</li>
                <li class="nav-item"><a href="admin_customer.php">Customers</a></li>
                <li class="nav-item"><a href="admin_products.php">Products</a></li>
                <li class="nav-item"><a href="admin_track_order.php">Tracking</a></li>
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
            <div class="form-container">
                <div class="form-header">
                    <h3 class="form-title">Orders List</h3>
                    <p class="form-subtitle">Click edit to update order details</p>
                </div>

                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#edf2f7;">
                            <th style="padding:12px; text-align:left;">Order ID</th>
                            <th style="padding:12px; text-align:left;">Customer</th>
                            <th style="padding:12px; text-align:left;">Email</th>
                            <th style="padding:12px; text-align:left;">Order Date</th>
                            <th style="padding:12px; text-align:left;">Status</th>
                            <th style="padding:12px; text-align:left;">School ID</th>
                            <th style="padding:12px; text-align:left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:12px;">#<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($order['email'] ?? '—') ?></td>
                                <td style="padding:12px;"><?= htmlspecialchars($order['order_date']) ?></td>
                                <td style="padding:12px;">
                                    <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                </td>
                                <td style="padding:12px;"><?= htmlspecialchars($order['order_id']) ?></td> <!-- School ID column showing order_id -->
                                <td style="padding:12px;">
                                    <a href="admin_edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-primary">Edit</a>
                                    <form action="admin_delete_order.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-error">No orders found.</div>
        <?php endif; ?>
    </div>

</body>

</html>