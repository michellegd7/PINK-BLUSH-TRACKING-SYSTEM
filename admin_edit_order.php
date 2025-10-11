<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('Location: admin_login.php');
  exit();
}

include 'database.php';

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;
if (!$order_id) {
  header("Location: admin_orders.php");
  exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  mysqli_begin_transaction($conn);

  try {
    // Update orders table
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

    // Delete existing order items and re-insert (simpler approach)
    $stmt = $conn->prepare("DELETE FROM order_item WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    // Insert updated order items
    if (isset($_POST['item_types']) && is_array($_POST['item_types'])) {
      foreach ($_POST['item_types'] as $index => $item_type) {
        $measurement = $_POST['measurements'][$index] ?? '';
        $quantity = intval($_POST['quantities'][$index] ?? 1);
        $price = floatval($_POST['prices'][$index] ?? 0);
        $item_status = $_POST['item_statuses'][$index] ?? 'Pending';

        $stmt = $conn->prepare("INSERT INTO order_item (order_id, item_type, measurement, quantity, price, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issids", $order_id, $item_type, $measurement, $quantity, $price, $item_status);
        $stmt->execute();
        $stmt->close();
      }
    }

    // Update payment
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $downpayment = floatval($_POST['downpayment'] ?? 0);
    $balance = floatval($_POST['balance'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $payment_status = $_POST['payment_status'] ?? 'Unpaid';

    $stmt = $conn->prepare("UPDATE payment SET total_amount = ?, downpayment = ?, payment_method = ?, payment_status = ? WHERE order_id = ?");
    $stmt->bind_param("ddssi", $total_amount, $downpayment, $payment_method, $payment_status, $order_id);
    $stmt->execute();
    $stmt->close();

    mysqli_commit($conn);
    header("Location: admin_dashboard.php?updated=true");
    exit();
  } catch (Exception $e) {
    mysqli_rollback($conn);
    $error = "Update failed: " . $e->getMessage();
  }
}

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, c.first_name, c.last_name, c.contact_number 
                          FROM orders o 
                          LEFT JOIN customer c ON o.customer_id = c.customer_id 
                          WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
  header("Location: admin_orders.php");
  exit();
}

// Fetch order items
$stmt = $conn->prepare("SELECT oi.*, u.uniform_name 
                          FROM order_item oi 
                          LEFT JOIN uniform_options u ON oi.item_type = u.uniform_name 
                          WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch payment details
$stmt = $conn->prepare("SELECT * FROM payment WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

// Fetch customers for dropdown
$customers = [];
$result = $conn->query("SELECT customer_id, first_name, last_name FROM customer ORDER BY first_name");
if ($result) {
  $customers = $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch uniform options for dropdown
$uniforms = [];
$result = $conn->query("SELECT DISTINCT uniform_name FROM uniform_options ORDER BY uniform_name");
if ($result) {
  $uniforms = $result->fetch_all(MYSQLI_ASSOC);
}

// Calculate total from items
$calculated_total = 0;
foreach ($order_items as $item) {
  $calculated_total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Order #<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      padding: 20px;
      color: #2d3748;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    h2 {
      margin-bottom: 25px;
      color: #2d3748;
      font-size: 1.8rem;
      font-weight: 600;
    }

    .info-banner {
      background: #e0f2fe;
      border-left: 4px solid #0284c7;
      padding: 12px 15px;
      margin-bottom: 20px;
      border-radius: 6px;
      font-size: 13px;
      color: #0c4a6e;
    }

    .info-banner strong {
      color: #075985;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #4a5568;
      font-size: 0.9rem;
    }

    input,
    select,
    textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      font-size: 0.95rem;
      transition: border-color 0.2s;
    }

    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: #4299e1;
    }

    .section-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #2d3748;
      margin: 30px 0 15px 0;
      padding-bottom: 10px;
      border-bottom: 2px solid #e2e8f0;
    }

    .items-container {
      margin-top: 15px;
    }

    .item-row {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
      gap: 15px;
      margin-bottom: 15px;
      padding: 15px;
      background: #f7fafc;
      border-radius: 8px;
      align-items: end;
    }

    .item-status-badge {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      display: inline-block;
      margin-top: 8px;
    }

    .payment-summary {
      background: #f7fafc;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
    }

    .payment-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
      font-size: 0.95rem;
    }

    .payment-row.total {
      font-size: 1.2rem;
      font-weight: 700;
      color: #2d3748;
      padding-top: 12px;
      border-top: 2px solid #cbd5e0;
      margin-top: 12px;
    }

    .balance-due {
      color: #e53e3e;
    }

    .balance-paid {
      color: #38a169;
    }

    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 30px;
    }

    button {
      padding: 12px 30px;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-primary {
      background: #4299e1;
      color: white;
    }

    .btn-primary:hover {
      background: #3182ce;
    }

    .btn-secondary {
      background: #e2e8f0;
      color: #2d3748;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 12px 30px;
      border-radius: 6px;
      font-weight: 600;
    }

    .btn-secondary:hover {
      background: #cbd5e0;
    }

    .back-link {
      display: inline-block;
      margin-top: 15px;
      text-decoration: none;
      color: #4299e1;
      font-weight: 500;
    }

    .back-link:hover {
      color: #3182ce;
    }

    .error {
      background: #fed7d7;
      color: #c53030;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }

      .item-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>Edit Order #<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></h2>

    <?php if (isset($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <!-- Customer Information -->
      <div class="form-row">
        <div class="form-group">
          <label for="customer_id">Customer</label>
          <select name="customer_id" required>
            <option value="">Select Customer</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['customer_id'] ?>" <?= $c['customer_id'] == $order['customer_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="status">Order Status</label>
          <select name="status" required>
            <?php foreach (['Order Confirmation', 'Payment Confirmation', 'Measurement', 'Material Preparation', 'Cutting', 'Sewing', 'Finishing', 'Quality Checking', 'Final Pressing / Packaging', 'Ready for Pickup', 'Final Payment', 'Pickup by the Customer', 'Completed'] as $status): ?>
              <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="order_date">Order Date</label>
          <input type="date" name="order_date" value="<?= $order['order_date'] ?>" required>
        </div>

        <div class="form-group">
          <label for="due_date">Due Date</label>
          <input type="date" name="due_date" value="<?= $order['due_date'] ?? '' ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="school_id">School ID</label>
        <input type="number" name="school_id" value="<?= $order['school_id'] ?>">
      </div>

      <!-- Order Items Section -->
      <div class="section-title">Order Items & Status</div>
      <div class="items-container">
        <?php if (!empty($order_items)): ?>
          <?php foreach ($order_items as $index => $item): ?>
            <div class="item-row">
              <div class="form-group">
                <label>Item Type</label>
                <select name="item_types[]" required>
                  <option value="">Select Uniform</option>
                  <?php foreach ($uniforms as $u): ?>
                    <option value="<?= htmlspecialchars($u['uniform_name']) ?>"
                      <?= $item['item_type'] === $u['uniform_name'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($u['uniform_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Size/Measurement</label>
                <input type="text" name="measurements[]" value="<?= htmlspecialchars($item['measurement'] ?? '') ?>" placeholder="e.g., XS, S, M, or custom">
              </div>

              <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantities[]" value="<?= $item['quantity'] ?? 1 ?>" min="1" required>
              </div>

              <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" name="prices[]" value="<?= $item['price'] ?? 0 ?>" step="0.01" min="0" required>
              </div>

              <div class="form-group">
                <label>Item Status</label>
                <select name="item_statuses[]" required>
                  <option value="Pending" <?= ($item['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Order Confirmation" <?= ($item['status'] ?? '') === 'Order Confirmation' ? 'selected' : '' ?>>Order Confirmation</option>
                  <option value="Payment Confirmation" <?= ($item['status'] ?? '') === 'Payment Confirmation' ? 'selected' : '' ?>>Payment Confirmation</option>
                  <option value="Measurement" <?= ($item['status'] ?? '') === 'Measurement' ? 'selected' : '' ?>>Measurement</option>
                  <option value="Material Preparation" <?= ($item['status'] ?? '') === 'Material Preparation' ? 'selected' : '' ?>>Material Preparation</option>
                  <option value="Cutting" <?= ($item['status'] ?? '') === 'Cutting' ? 'selected' : '' ?>>Cutting</option>
                  <option value="Sewing" <?= ($item['status'] ?? '') === 'Sewing' ? 'selected' : '' ?>>Sewing</option>
                  <option value="Finishing" <?= ($item['status'] ?? '') === 'Finishing' ? 'selected' : '' ?>>Finishing</option>
                  <option value="Quality Checking" <?= ($item['status'] ?? '') === 'Quality Checking' ? 'selected' : '' ?>>Quality Checking</option>
                  <option value="Final Pressing / Packaging" <?= ($item['status'] ?? '') === 'Final Pressing / Packaging' ? 'selected' : '' ?>>Final Pressing / Packaging</option>
                  <option value="Ready for Pickup" <?= ($item['status'] ?? '') === 'Ready for Pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                  <option value="Processing" <?= ($item['status'] ?? '') === 'Processing' ? 'selected' : '' ?>>Processing</option>
                  <option value="Completed" <?= ($item['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color: #718096; padding: 15px; background: #f7fafc; border-radius: 6px;">No items found for this order.</p>
        <?php endif; ?>
      </div>

      <!-- Payment Section -->
      <div class="section-title">Payment Information</div>

      <div class="form-row">
        <div class="form-group">
          <label for="total_amount">Total Amount (₱)</label>
          <input type="number"
            id="total_amount"
            name="total_amount"
            step="0.01"
            min="0"
            value="<?= $payment['total_amount'] ?? $calculated_total ?>"
            required
            onchange="calculateBalance()">
        </div>

        <div class="form-group">
          <label for="downpayment">Downpayment (₱)</label>
          <input type="number"
            id="downpayment"
            name="downpayment"
            step="0.01"
            min="0"
            value="<?= $payment['downpayment'] ?? 0 ?>"
            onchange="calculateBalance()">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="balance">Balance (₱)</label>
          <input type="number"
            id="balance"
            name="balance"
            step="0.01"
            min="0"
            value="<?= ($payment['total_amount'] ?? $calculated_total) - ($payment['downpayment'] ?? 0) ?>"
            readonly
            style="font-weight: 600; color: #e53e3e;">
        </div>

        <div class="form-group">
          <label for="payment_method">Payment Method</label>
          <select name="payment_method">
            <option value="cash" <?= ($payment['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
            <option value="paypal" <?= ($payment['payment_method'] ?? '') === 'paypal' ? 'selected' : '' ?>>Paypal</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="payment_status">Payment Status</label>
          <select name="payment_status">
            <option value="Unpaid" <?= ($payment['payment_status'] ?? '') === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
            <option value="Partial" <?= ($payment['payment_status'] ?? '') === 'Partial' ? 'selected' : '' ?>>Partial</option>
            <option value="Paid" <?= ($payment['payment_status'] ?? '') === 'Paid' ? 'selected' : '' ?>>Paid</option>
          </select>
        </div>
        <div class="form-group">
          <!-- Empty div to maintain grid layout -->
        </div>
      </div>

      <!-- Payment Summary -->
      <div class="payment-summary">
        <div class="payment-row">
          <span>Subtotal:</span>
          <strong>₱<?= number_format($calculated_total, 2) ?></strong>
        </div>
        <div class="payment-row">
          <span>Downpayment:</span>
          <strong>₱<?= number_format($payment['downpayment'] ?? 0, 2) ?></strong>
        </div>
        <div class="payment-row total">
          <span>Balance:</span>
          <span class="<?= ($calculated_total - ($payment['downpayment'] ?? 0)) > 0 ? 'balance-due' : 'balance-paid' ?>">
            ₱<?= number_format($calculated_total - ($payment['downpayment'] ?? 0), 2) ?>
          </span>
        </div>
      </div>

      <div class="button-group">
        <button type="submit" class="btn-primary">💾 Update Order</button>
        <a href="admin_dashboard.php" class="btn-secondary">✕ Cancel</a>
      </div>
    </form>

    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
  </div>

  <!-- JavaScript for automatic balance calculation -->
  <script>
    function calculateBalance() {
      const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
      let downpayment = parseFloat(document.getElementById('downpayment').value) || 0;
      const paymentStatus = document.querySelector('select[name="payment_status"]').value;

      let balance;

      // If payment status is "Paid", balance should be 0
      if (paymentStatus === 'Paid') {
        balance = 0;
        // Automatically set downpayment to total amount when marked as Paid
        document.getElementById('downpayment').value = totalAmount.toFixed(2);
      } else if (paymentStatus === 'Partial') {
        // For Partial, calculate based on actual downpayment (must be > 0 and < total)
        balance = totalAmount - downpayment;
        // Ensure balance is not zero or negative for partial payment
        if (balance <= 0) {
          balance = totalAmount - downpayment;
        }
      } else {
        // For Unpaid, balance equals total amount
        balance = totalAmount - downpayment;
      }

      const balanceInput = document.getElementById('balance');
      balanceInput.value = balance.toFixed(2);

      // Change color based on balance
      if (balance > 0) {
        balanceInput.style.color = '#e53e3e'; // Red for outstanding balance
      } else {
        balanceInput.style.color = '#38a169'; // Green for fully paid
      }
    }

    // Add event listener to payment status dropdown
    document.addEventListener('DOMContentLoaded', function() {
      calculateBalance();

      // Listen for changes on payment status
      const paymentStatusSelect = document.querySelector('select[name="payment_status"]');
      if (paymentStatusSelect) {
        paymentStatusSelect.addEventListener('change', calculateBalance);
      }

      // Listen for changes on total amount and downpayment
      document.getElementById('total_amount').addEventListener('change', calculateBalance);
      document.getElementById('total_amount').addEventListener('input', calculateBalance);
      document.getElementById('downpayment').addEventListener('change', calculateBalance);
      document.getElementById('downpayment').addEventListener('input', calculateBalance);
    });
  </script>
</body>

</html>
