<?php
include 'database.php';

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;
if (!$order_id) {
  header("Location: admin_orders.php");
  exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stmt = $conn->prepare("UPDATE orders SET customer_id = ?, status = ?, order_date = ?, due_date = ?, school_id = ? WHERE order_id = ?");
  $stmt->bind_param(
    "isssii",
    $_POST['customer_id'],
    $_POST['status'],
    $_POST['order_date'],
    $_POST['due_date'],
    $_POST['school_id'],
    $order_id
  );
  $stmt->execute();
  $stmt->close();
  header("Location: admin_orders.php?updated=true");
  exit();
}

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

// Fetch customers for dropdown
$customers = [];
$result = $conn->query("SELECT customer_id, first_name, last_name FROM customer ORDER BY first_name");
if ($result) {
  $customers = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Order</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      padding: 40px;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }

    input,
    select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }

    button {
      margin-top: 20px;
      padding: 10px 20px;
      background: #4299e1;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    button:hover {
      background: #3182ce;
    }

    .back-link {
      display: inline-block;
      margin-top: 20px;
      text-decoration: none;
      color: #4299e1;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>Edit Order #<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></h2>
    <form method="POST">
      <label for="customer_id">Customer</label>
      <select name="customer_id" required>
        <option value="">Select Customer</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= $c['customer_id'] ?>" <?= $c['customer_id'] == $order['customer_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="status">Status</label>
      <select name="status" required>
        <?php foreach (['Pending', 'Processing', 'Completed', 'Delivered', 'Cancelled'] as $status): ?>
          <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
      </select>

      <label for="order_date">Order Date</label>
      <input type="date" name="order_date" value="<?= $order['order_date'] ?>" required>

      <label for="due_date">Due Date</label>
      <input type="date" name="due_date" value="<?= $order['due_date'] ?? '' ?>">

      <label for="school_id">School ID</label>
      <input type="number" name="school_id" value="<?= $order['school_id'] ?>">

      <button type="submit">Update Order</button>
    </form>
    <a href="admin_orders.php" class="back-link">← Back to Orders</a>
  </div>
</body>

</html>