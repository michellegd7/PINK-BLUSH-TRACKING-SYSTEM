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

// Fetch uniform products
$products = [];
$sql = "SELECT * FROM uniform_options ORDER BY id DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    // If query fails or no results, keep $products as empty array
    if (!$result) {
        error_log("Database query error: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Uniform Products</title>
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

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }

        .alert-icon {
            font-size: 1.25rem;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Order Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
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
        }

        .btn-primary:hover {
            background: #3182ce;
        }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        .btn-danger {
            background: #f56565;
            border-color: #f56565;
            color: white;
        }

        .btn-danger:hover {
            background: #e53e3e;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
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
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-fitting {
            background: #e6fffa;
            color: #319795;
        }

        .status-cutting {
            background: #feebc8;
            color: #dd6b20;
        }

        .garment-type {
            font-weight: 500;
            color: #4a5568;
        }

        .price {
            font-weight: 600;
            color: #2d3748;
        }

        .price-display {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .size-price-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .size-label {
            font-weight: 600;
            min-width: 25px;
            font-size: 0.85rem;
            color: #4a5568;
        }

        .price-value {
            font-weight: 500;
            color: #2d3748;
            font-size: 0.9rem;
        }

        /* Responsive */
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
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="logo">
                <span>🧵</span>
                <span>Master Tailor</span>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a href="admin_orders.php">Orders</a></li>
                <li class="nav-item"><a href="admin_customer.php">Customers</a></li>
                <li class="nav-item active">Products</li>
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
            <h1 class="page-title">Uniform Products</h1>
            <p class="page-subtitle">Manage school uniform options and pricing</p>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✓</span>
                <span><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <span class="alert-icon">✕</span>
                <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-header">
                <h3 class="card-title">All Uniforms</h3>
                <a href="admin_add_products.php" class="btn btn-primary">+ Add Product</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>School</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Sizes</th>
                        <th>Customizable</th>
                        <th>Size Prices</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: #718096;">
                                No products found. Click "Add Product" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>#<?= str_pad($product['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($product['school_id']) ?></td>
                                <td><?= htmlspecialchars($product['uniform_type']) ?></td>
                                <td class="garment-type"><?= htmlspecialchars($product['uniform_name']) ?></td>
                                <td><?= htmlspecialchars($product['available_sizes']) ?></td>
                                <td>
                                    <span class="status <?= $product['customizable'] ? 'status-fitting' : 'status-cutting' ?>">
                                        <?= $product['customizable'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="price-display">
                                        <div class="size-price-row">
                                            <span class="size-label">XS:</span>
                                            <span class="price-value">₱<?= number_format($product['price_xs'], 2) ?></span>
                                        </div>
                                        <div class="size-price-row">
                                            <span class="size-label">S:</span>
                                            <span class="price-value">₱<?= number_format($product['price_s'], 2) ?></span>
                                        </div>
                                        <div class="size-price-row">
                                            <span class="size-label">M:</span>
                                            <span class="price-value">₱<?= number_format($product['price_m'], 2) ?></span>
                                        </div>
                                        <div class="size-price-row">
                                            <span class="size-label">L:</span>
                                            <span class="price-value">₱<?= number_format($product['price_l'], 2) ?></span>
                                        </div>
                                        <div class="size-price-row">
                                            <span class="size-label">XL:</span>
                                            <span class="price-value">₱<?= number_format($product['price_xl'], 2) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div class="action-buttons">
                                        <a href="admin_product_edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="admin_product_delete.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
