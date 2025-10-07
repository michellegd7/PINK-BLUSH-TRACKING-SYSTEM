<?php
include 'database.php';

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer = null;
$error_message = '';
$success_message = '';

// Fetch customer data
if ($customer_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE customer SET first_name = ?, last_name = ?, username = ?, email = ?, contact_number = ?, address = ? WHERE customer_id = ?");
    $stmt->bind_param(
        "ssssssi",
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['username'],
        $_POST['email'],
        $_POST['contact_number'],
        $_POST['address'],
        $customer_id
    );

    if ($stmt->execute()) {
        $success_message = "Customer updated successfully!";
        // Refresh customer data
        $stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error_message = "Error updating customer: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Customer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 40px;
        }

        .main-content {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #444;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 16px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #ccc;
            color: #333;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>

</head>

<body>
    <div class="main-content">
        <h1>Edit Customer</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if ($customer): ?>
            <form method="POST">
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($customer['first_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($customer['last_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($customer['username']) ?>">
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Contact Number:</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($customer['contact_number']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Address:</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($customer['address']) ?>" required>
                </div>

                <div class="action-buttons">
                    <a href="admin_customer.php" class="btn">Back</a>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
        <?php else: ?>
            <p>Customer not found.</p>
        <?php endif; ?>
    </div>
</body>

</html>