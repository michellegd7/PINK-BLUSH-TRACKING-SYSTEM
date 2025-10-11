<?php
include 'database.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
$success_message = '';
$error_message = '';

// Fetch product details
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM uniform_options WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE uniform_options SET 
        school_id = ?, 
        uniform_type = ?, 
        uniform_name = ?, 
        available_sizes = ?, 
        customizable = ?,
        price_xs = ?,
        price_s = ?,
        price_m = ?,
        price_l = ?,
        price_xl = ?
        WHERE id = ?");

    $stmt->bind_param(
        "issssdddddi",
        $_POST['school_id'],
        $_POST['uniform_type'],
        $_POST['uniform_name'],
        $_POST['available_sizes'],
        $_POST['customizable'],
        $_POST['price_xs'],
        $_POST['price_s'],
        $_POST['price_m'],
        $_POST['price_l'],
        $_POST['price_xl'],
        $product_id
    );

    if ($stmt->execute()) {
        $success_message = "Product updated successfully!";
        // Refresh product data
        $stmt = $conn->prepare("SELECT * FROM uniform_options WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error_message = "Error updating product: " . $stmt->error;
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            padding: 40px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .main-content {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2d3748;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #4a5568;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: #ccc;
            color: #333;
            text-decoration: none;
        }

        .btn-primary {
            background: #4299e1;
            color: #fff;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .price-section {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .price-section h3 {
            margin-bottom: 10px;
            color: #2d3748;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <h1>Edit Product</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($product): ?>
            <form method="POST">
                <div class="form-group">
                    <label>School ID</label>
                    <input type="number" name="school_id" value="<?= htmlspecialchars($product['school_id']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Uniform Type</label>
                    <input type="text" name="uniform_type" value="<?= htmlspecialchars($product['uniform_type']) ?>">
                </div>

                <div class="form-group">
                    <label>Uniform Name</label>
                    <input type="text" name="uniform_name" value="<?= htmlspecialchars($product['uniform_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Available Sizes (comma-separated)</label>
                    <input type="text" name="available_sizes" value="<?= htmlspecialchars($product['available_sizes']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Customizable</label>
                    <select name="customizable">
                        <option value="1" <?= $product['customizable'] ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$product['customizable'] ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="price-section">
                    <h3>Size-Based Prices (₱)</h3>

                    <div class="form-group">
                        <label>XS</label>
                        <input type="number" step="0.01" name="price_xs" value="<?= htmlspecialchars($product['price_xs']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>S</label>
                        <input type="number" step="0.01" name="price_s" value="<?= htmlspecialchars($product['price_s']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>M</label>
                        <input type="number" step="0.01" name="price_m" value="<?= htmlspecialchars($product['price_m']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>L</label>
                        <input type="number" step="0.01" name="price_l" value="<?= htmlspecialchars($product['price_l']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>XL</label>
                        <input type="number" step="0.01" name="price_xl" value="<?= htmlspecialchars($product['price_xl']) ?>" required>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="admin_products.php" class="btn">Back</a>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        <?php else: ?>
            <p>Product not found.</p>
        <?php endif; ?>
    </div>
</body>

</html>
