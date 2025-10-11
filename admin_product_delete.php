<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

include 'database.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID";
    header('Location: admin_products.php');
    exit();
}

$product_id = intval($_GET['id']);

// Check if confirmation is provided
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    // Show confirmation page
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Confirm Delete</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: #f5f7fa;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 1rem;
            }

            .confirm-box {
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                padding: 2rem;
                max-width: 500px;
                width: 100%;
                text-align: center;
            }

            .icon-warning {
                font-size: 4rem;
                color: #f56565;
                margin-bottom: 1rem;
            }

            h2 {
                color: #2d3748;
                margin-bottom: 1rem;
                font-size: 1.5rem;
            }

            p {
                color: #718096;
                margin-bottom: 2rem;
                line-height: 1.6;
            }

            .button-group {
                display: flex;
                gap: 1rem;
                justify-content: center;
            }

            .btn {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 1rem;
                font-weight: 500;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
            }

            .btn-cancel {
                background: #e2e8f0;
                color: #4a5568;
            }

            .btn-cancel:hover {
                background: #cbd5e0;
            }

            .btn-delete {
                background: #f56565;
                color: white;
            }

            .btn-delete:hover {
                background: #e53e3e;
            }
        </style>
    </head>

    <body>
        <div class="confirm-box">
            <div class="icon-warning">⚠️</div>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this product? This action cannot be undone.</p>
            <div class="button-group">
                <a href="admin_products.php" class="btn btn-cancel">Cancel</a>
                <a href="admin_product_delete.php?id=<?= $product_id ?>&confirm=yes" class="btn btn-delete">Delete Product</a>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// Proceed with deletion
$sql = "DELETE FROM uniform_options WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product deleted successfully";
    } else {
        $_SESSION['error_message'] = "Error deleting product: " . $conn->error;
    }

    $stmt->close();
} else {
    $_SESSION['error_message'] = "Database error: " . $conn->error;
}

$conn->close();
header('Location: admin_products.php');
exit();
?>
