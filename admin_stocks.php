<?php
session_start();
include 'database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Get logged-in user's full name
$admin_name = $_SESSION['user'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));

$success = '';
$error = '';

// Handle stock update
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
        $success = "Stock updated successfully!";
    } else {
        $error = "Error updating stock.";
    }
    $stmt->close();
}

// Get all uniforms with stock info
$uniforms_query = "SELECT * FROM uniform_options ORDER BY school_id, uniform_type, uniform_name";
$uniforms = $conn->query($uniforms_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
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

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #f7fafc;
            color: #2d3748;
            border-left: 4px solid #4a5568;
        }

        .alert-error {
            background: #f7fafc;
            color: #2d3748;
            border-left: 4px solid #4a5568;
        }

        /* Table Container */
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
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
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
            font-size: 0.9rem;
        }

        tr:hover {
            background: #f7fafc;
        }

        .product-name {
            font-weight: 500;
            color: #2d3748;
        }

        .school-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            color: #4a5568;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            color: #4a5568;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Stock Input */
        .stock-inputs {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .stock-input {
            width: 60px;
            padding: 0.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
        }

        .stock-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .stock-input.low {
            border-color: #cbd5e0;
            background: #f7fafc;
            color: #4a5568;
        }

        .size-label {
            font-size: 0.75rem;
            color: #718096;
            font-weight: 600;
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background: #3182ce;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-good {
            color: #4a5568;
        }

        .status-out {
            color: #4a5568;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
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
                font-size: 0.85rem;
            }

            .stock-input {
                width: 50px;
                padding: 0.4rem;
                font-size: 0.85rem;
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
                <li class="nav-item"><a href="admin_products.php">Products</a></li>
                <li class="nav-item active">Stocks</li>
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
            <h1 class="page-title">Stock Management</h1>
            <p class="page-subtitle">Manage uniform inventory and stock levels</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>✕</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Stock Table -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="card-title">Uniform Inventory</h3>
            </div>

            <?php if ($uniforms->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>School</th>
                            <th>Type</th>
                            <th>Product Name</th>
                            <th>XS</th>
                            <th>S</th>
                            <th>M</th>
                            <th>L</th>
                            <th>XL</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($uniform = $uniforms->fetch_assoc()):
                            $sizes = explode(',', str_replace(' ', '', $uniform['available_sizes']));
                            $total_stock = $uniform['stock_xs'] + $uniform['stock_s'] + $uniform['stock_m'] + $uniform['stock_l'] + $uniform['stock_xl'];
                            $has_low_stock = ($uniform['stock_xs'] < 5 && in_array('XS', $sizes)) ||
                                ($uniform['stock_s'] < 5 && in_array('S', $sizes)) ||
                                ($uniform['stock_m'] < 5 && in_array('M', $sizes)) ||
                                ($uniform['stock_l'] < 5 && in_array('L', $sizes)) ||
                                ($uniform['stock_xl'] < 5 && in_array('XL', $sizes));

                            $status_class = $total_stock == 0 ? 'status-out' : ($has_low_stock ? 'status-low' : 'status-good');
                            $status_text = $total_stock == 0 ? 'Out of Stock' : ($has_low_stock ? 'Low Stock' : 'In Stock');
                        ?>
                            <tr>
                                <td>#<?php echo str_pad($uniform['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td><span class="school-badge"><?php echo htmlspecialchars($uniform['school_id']); ?></span></td>
                                <td><span class="type-badge"><?php echo htmlspecialchars($uniform['uniform_type']); ?></span></td>
                                <td class="product-name"><?php echo htmlspecialchars($uniform['uniform_name']); ?></td>

                                <form method="POST" style="display: contents;">
                                    <input type="hidden" name="uniform_id" value="<?php echo $uniform['id']; ?>">

                                    <td>
                                        <?php if (in_array('XS', $sizes)): ?>
                                            <input type="number" name="stock_xs"
                                                class="stock-input <?php echo $uniform['stock_xs'] < 5 ? 'low' : ''; ?>"
                                                value="<?php echo $uniform['stock_xs']; ?>" min="0">
                                        <?php else: ?>
                                            <input type="hidden" name="stock_xs" value="0">
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (in_array('S', $sizes)): ?>
                                            <input type="number" name="stock_s"
                                                class="stock-input <?php echo $uniform['stock_s'] < 5 ? 'low' : ''; ?>"
                                                value="<?php echo $uniform['stock_s']; ?>" min="0">
                                        <?php else: ?>
                                            <input type="hidden" name="stock_s" value="0">
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (in_array('M', $sizes)): ?>
                                            <input type="number" name="stock_m"
                                                class="stock-input <?php echo $uniform['stock_m'] < 5 ? 'low' : ''; ?>"
                                                value="<?php echo $uniform['stock_m']; ?>" min="0">
                                        <?php else: ?>
                                            <input type="hidden" name="stock_m" value="0">
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (in_array('L', $sizes)): ?>
                                            <input type="number" name="stock_l"
                                                class="stock-input <?php echo $uniform['stock_l'] < 5 ? 'low' : ''; ?>"
                                                value="<?php echo $uniform['stock_l']; ?>" min="0">
                                        <?php else: ?>
                                            <input type="hidden" name="stock_l" value="0">
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (in_array('XL', $sizes)): ?>
                                            <input type="number" name="stock_xl"
                                                class="stock-input <?php echo $uniform['stock_xl'] < 5 ? 'low' : ''; ?>"
                                                value="<?php echo $uniform['stock_xl']; ?>" min="0">
                                        <?php else: ?>
                                            <input type="hidden" name="stock_xl" value="0">
                                            <span style="color: #cbd5e0;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <button type="submit" name="update_stock" class="btn btn-success">
                                            Update
                                        </button>
                                    </td>
                                </form>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No uniform items found in inventory</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
