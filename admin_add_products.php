<?php
include 'database.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $school_id = $_POST['school_id'];
    $uniform_type = $_POST['uniform_type'];
    $uniform_name = $_POST['uniform_name'];
    $available_sizes = $_POST['available_sizes'];
    $customizable = isset($_POST['customizable']) ? 1 : 0;
    $price_xs = $_POST['price_xs'];
    $price_s = $_POST['price_s'];
    $price_m = $_POST['price_m'];
    $price_l = $_POST['price_l'];
    $price_xl = $_POST['price_xl'];


    // Insert into database
    $sql = "INSERT INTO uniform_options (
    school_id, uniform_type, uniform_name, available_sizes, customizable,
    price_xs, price_s, price_m, price_l, price_xl)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssiddddd",
        $school_id,
        $uniform_type,
        $uniform_name,
        $available_sizes,
        $customizable,
        $price_xs,
        $price_s,
        $price_m,
        $price_l,
        $price_xl
    );

    if ($stmt->execute()) {
        $success_message = "Product added successfully!";
        // Redirect after 2 seconds
        header("refresh:2;url=admin_products.php");
    } else {
        $error_message = "Error adding product: " . $conn->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
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

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
        }

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

        .main-content {
            max-width: 800px;
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

        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5568;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-checkbox {
            width: 20px;
            height: 20px;
            margin-right: 0.5rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .price-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        .price-item {
            display: flex;
            flex-direction: column;
        }

        .price-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        @media (max-width: 768px) {
            .price-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .main-content {
                padding: 0 1rem;
            }
        }
    </style>
</head>

<body>
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
                <li class="nav-item"><a href="track_order.php">Tracking</a></li>
            </ul>
            <div class="user-info">
                <span>Maria Santos</span>
                <div class="avatar">MS</div>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Add New Product</h1>
            <p class="page-subtitle">Add a new uniform to your inventory</p>
        </div>

        <div class="form-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?= $error_message ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="school_id">School ID</label>
                    <input type="number" class="form-input" id="school_id" name="school_id" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="uniform_type">Uniform Type</label>
                    <input type="text" class="form-input" id="uniform_type" name="uniform_type" placeholder="e.g., Upper, Lower, SET" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="uniform_name">Uniform Name</label>
                    <input type="text" class="form-input" id="uniform_name" name="uniform_name" placeholder="e.g., SMC Men Uniform SET" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="available_sizes">Available Sizes</label>
                    <input type="text" class="form-input" id="available_sizes" name="available_sizes" placeholder="e.g., XS,S,M,L,XL" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" class="form-checkbox" name="customizable" value="1">
                        <span class="form-label" style="margin: 0;">Customizable</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Size Prices (₱)</label>
                    <div class="price-grid">
                        <div class="price-item">
                            <label class="price-label">XS</label>
                            <input type="number" class="form-input" name="price_xs" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="price-item">
                            <label class="price-label">S</label>
                            <input type="number" class="form-input" name="price_s" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="price-item">
                            <label class="price-label">M</label>
                            <input type="number" class="form-input" name="price_m" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="price-item">
                            <label class="price-label">L</label>
                            <input type="number" class="form-input" name="price_l" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="price-item">
                            <label class="price-label">XL</label>
                            <input type="number" class="form-input" name="price_xl" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='admin_products.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
