<?php
include 'database.php';
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

$user = $_SESSION['username'];

// Fetch customer profile
$profileQuery = "SELECT * FROM customer WHERE username = ?";
$profileStmt = mysqli_prepare($conn, $profileQuery);
mysqli_stmt_bind_param($profileStmt, "s", $user);
mysqli_stmt_execute($profileStmt);
$profileResult = mysqli_stmt_get_result($profileStmt);
$profile = mysqli_fetch_assoc($profileResult);

if (!$profile) {
  echo "Profile not found!";
  exit();
}

$customer_id = $profile['customer_id'];

// Fetch complete order information with proper JOINs
$orderQuery = "SELECT 
    o.order_id,
    o.order_date,
    o.due_date,
    o.status as order_status,
    p.payment_method,
    p.payment_status,
    p.downpayment,
    p.total_amount,
    p.payment_date,
    oi.order_item_id,
    oi.item_type,
    oi.measurement,
    oi.quantity,
    oi.price as item_price,
    oi.status as item_status
FROM orders o 
LEFT JOIN payment p ON o.order_id = p.order_id 
LEFT JOIN order_item oi ON o.order_id = oi.order_id
WHERE o.customer_id = ? 
ORDER BY o.order_date DESC, o.order_id DESC";

$orderStmt = mysqli_prepare($conn, $orderQuery);
mysqli_stmt_bind_param($orderStmt, "i", $customer_id);
mysqli_stmt_execute($orderStmt);
$orderResult = mysqli_stmt_get_result($orderStmt);

// Group orders with their items and calculate totals
$ordersData = [];
while ($row = mysqli_fetch_assoc($orderResult)) {
  $order_id = $row['order_id'];

  if (!isset($ordersData[$order_id])) {
    $ordersData[$order_id] = [
      'order_id' => $row['order_id'],
      'order_date' => $row['order_date'],
      'due_date' => $row['due_date'],
      'order_status' => $row['order_status'],
      'payment_method' => $row['payment_method'],
      'payment_status' => $row['payment_status'],
      'downpayment' => floatval($row['downpayment'] ?? 0),
      'total_amount' => floatval($row['total_amount'] ?? 0),
      'payment_date' => $row['payment_date'],
      'items' => [],
      'calculated_total' => 0
    ];
  }

  if ($row['order_item_id']) {
    $item_subtotal = floatval($row['item_price']) * intval($row['quantity']);
    $ordersData[$order_id]['calculated_total'] += $item_subtotal;

    $ordersData[$order_id]['items'][] = [
      'order_item_id' => $row['order_item_id'],
      'item_type' => $row['item_type'],
      'measurement' => $row['measurement'],
      'quantity' => $row['quantity'],
      'item_price' => floatval($row['item_price']),
      'item_status' => $row['item_status'],
      'item_subtotal' => $item_subtotal
    ];
  }
}

// Use payment table total if available, otherwise use calculated total
foreach ($ordersData as $order_id => &$order) {
  if ($order['total_amount'] == 0 && $order['calculated_total'] > 0) {
    $order['total_amount'] = $order['calculated_total'];
  }

  // Calculate balance
  $order['balance'] = max(0, $order['total_amount'] - $order['downpayment']);
}

// Separate active and completed orders
$activeOrders = [];
$completedOrders = [];

foreach ($ordersData as $order) {
  $status = strtolower($order['order_status']);
  if (in_array($status, ['completed', 'pickup'])) {
    $completedOrders[] = $order;
  } else {
    $activeOrders[] = $order;
  }
}

// Define order tracking stages
$trackingStages = [
  'order_confirmation' => 'Order Confirmation',
  'payment_confirmation' => 'Payment Confirmation',
  'measurement' => 'Measurement',
  'material_prep' => 'Material Preparation',
  'cutting' => 'Cutting',
  'sewing' => 'Sewing',
  'finishing' => 'Finishing',
  'quality_checking' => 'Quality Checking',
  'final_pressing' => 'Final Pressing / Packaging',
  'ready_for_pickup' => 'Ready for Pickup',
  'final_payment' => 'Final Payment',
  'pick_up' => 'Pickup by the Customer'
];

// Function to get current stage index
function getCurrentStageIndex($status)
{
  $statusMap = [
    'pending' => 0,
    'order_confirmation' => 0,
    'order confirmation' => 0,
    'payment_confirmation' => 1,
    'payment confirmation' => 1,
    'measurement' => 2,
    'material_prep' => 3,
    'material preparation' => 3,
    'cutting' => 4,
    'sewing' => 5,
    'finishing' => 6,
    'quality_checking' => 7,
    'quality checking' => 7,
    'final_pressing' => 8,
    'final pressing / packaging' => 8,
    'ready_for_pickup' => 9,
    'ready for pickup' => 9,
    'ready' => 9,
    'final_payment' => 10,
    'final payment' => 10,
    'pick_up' => 11,
    'pickup by the customer' => 11,
    'completed' => 11
  ];

  $status = strtolower(str_replace(['  ', '_'], [' ', ' '], $status));
  return isset($statusMap[$status]) ? $statusMap[$status] : 0;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Customer Profile</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f8f8f8, #ffe5ec);
      padding: 40px;
      margin: 0;
    }

    .success-message {
      background: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: slideIn 0.3s ease;
    }

    .success-message::before {
      content: "✓";
      background: #28a745;
      color: white;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .profile-container {
      max-width: 900px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
    }

    h2 {
      color: #e63946;
      text-align: center;
      margin-bottom: 30px;
    }

    .section {
      margin-bottom: 40px;
    }

    .section h3 {
      color: #333;
      margin-bottom: 15px;
      border-bottom: 2px solid #e63946;
      padding-bottom: 5px;
    }

    .profile-info p {
      margin: 8px 0;
      color: #555;
    }

    .tracking-box {
      background: white;
      border: 1px solid #ddd;
      padding: 20px;
      border-radius: 6px;
      margin-bottom: 25px;
    }

    .tracking-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid #eee;
    }

    .tracking-header h4 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }

    .tracking-order-id {
      background: #e63946;
      color: white;
      padding: 4px 12px;
      border-radius: 3px;
      font-size: 13px;
      font-weight: 600;
    }

    .order-items-list {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 6px;
      margin: 15px 0;
    }

    .order-items-list h5 {
      margin: 0 0 10px 0;
      font-size: 14px;
      color: #495057;
    }

    .item-detail {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 10px;
      padding: 10px;
      background: white;
      border-radius: 4px;
      margin-bottom: 8px;
      font-size: 13px;
    }

    .item-detail:last-child {
      margin-bottom: 0;
    }

    .item-detail strong {
      color: #2d3748;
    }

    .tracking-timeline {
      padding: 10px 0;
    }

    .tracking-step {
      display: flex;
      align-items: flex-start;
      padding: 12px 15px;
      margin-bottom: 10px;
      background: #f8f9fa;
      border-radius: 4px;
      border-left: 3px solid #dee2e6;
    }

    .tracking-step.completed {
      background: #d4edda;
      border-left-color: #28a745;
    }

    .tracking-step.current {
      background: #fff3cd;
      border-left-color: #ffc107;
    }

    .tracking-icon {
      width: 24px;
      height: 24px;
      min-width: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: bold;
      margin-right: 12px;
      background: white;
      border: 2px solid #dee2e6;
      margin-top: 2px;
    }

    .tracking-step.completed .tracking-icon {
      background: #28a745;
      border-color: #28a745;
      color: white;
    }

    .tracking-step.current .tracking-icon {
      background: #ffc107;
      border-color: #ffc107;
      color: #333;
    }

    .tracking-content {
      flex: 1;
    }

    .tracking-content h5 {
      margin: 0 0 3px 0;
      font-size: 14px;
      font-weight: 600;
      color: #333;
    }

    .tracking-content p {
      margin: 0;
      font-size: 12px;
      color: #6c757d;
      line-height: 1.4;
    }

    .tracking-status {
      font-size: 12px;
      font-weight: 600;
      margin-top: 4px;
      color: #495057;
    }

    .tracking-step.current .tracking-status {
      color: #856404;
    }

    .tracking-summary {
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid #eee;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 15px;
    }

    .tracking-summary>div {
      background: #f8f9fa;
      padding: 12px;
      border-radius: 6px;
    }

    .tracking-summary strong {
      color: #495057;
      display: block;
      margin-bottom: 6px;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .tracking-summary span {
      color: #2d3748;
      font-size: 15px;
      font-weight: 600;
    }

    .payment-status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

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

    .order-card {
      background: #fefefe;
      border-left: 6px solid #2a9d8f;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }

    .order-card h4 {
      margin: 0 0 10px 0;
      font-size: 18px;
      color: #333;
    }

    .order-card p {
      margin: 5px 0;
      color: #555;
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .payment-info-box {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-top: 15px;
      border: 1px solid #e9ecef;
    }

    .payment-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #e9ecef;
    }

    .payment-row:last-child {
      border-bottom: none;
      padding-top: 12px;
      font-weight: bold;
      font-size: 16px;
    }

    .payment-row label {
      color: #6c757d;
      font-size: 14px;
    }

    .payment-row .value {
      color: #2d3748;
      font-weight: 600;
    }

    .no-orders {
      text-align: center;
      font-style: italic;
      color: #888;
      padding: 20px;
    }

    .dashboard-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      background: #e63946;
      color: #fff;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      border-radius: 4px;
      transition: background 0.2s ease;
    }

    .dashboard-btn:hover {
      background: #d62828;
    }

    .back-btn-container {
      margin-bottom: 20px;
    }

    @media (max-width: 768px) {
      body {
        padding: 20px;
      }

      .profile-container {
        padding: 20px;
      }

      .tracking-summary {
        grid-template-columns: 1fr;
      }

      .order-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .item-detail {
        grid-template-columns: 1fr;
        gap: 5px;
      }
    }
  </style>
</head>

<body>
  <div class="profile-container">
    <div class="back-btn-container">
      <a href="customer-dashboard.php" class="dashboard-btn">
        <span>←</span>
        <span>Back to Menu</span>
      </a>
    </div>

    <?php if (isset($_GET['order']) && $_GET['order'] === 'success'): ?>
      <div class="success-message">
        <span>Order placed successfully! Your order is now being processed.</span>
      </div>
    <?php endif; ?>

    <h2>Welcome, <?= htmlspecialchars($user) ?>!</h2>

    <div class="section profile-info">
      <h3>Your Profile</h3>
      <p><strong>Customer ID:</strong> #<?= str_pad($profile['customer_id'], 3, '0', STR_PAD_LEFT) ?></p>
      <p><strong>Full Name:</strong> <?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($profile['email']) ?></p>
      <p><strong>Contact:</strong> <?= htmlspecialchars($profile['contact_number']) ?></p>
      <p><strong>Address:</strong> <?= htmlspecialchars($profile['address']) ?></p>
    </div>

    <!-- Order Tracking Section -->
    <?php if (!empty($activeOrders)): ?>
      <div class="section">
        <h3>Active Order Tracking</h3>

        <?php foreach ($activeOrders as $order):
          $currentStageIndex = getCurrentStageIndex($order['order_status']);
        ?>
          <div class="tracking-box">
            <div class="tracking-header">
              <h4>Order #<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></h4>
              <div class="tracking-order-id">
                <?= count($order['items']) ?> Item<?= count($order['items']) != 1 ? 's' : '' ?>
              </div>
            </div>

            <?php if (!empty($order['items'])): ?>
              <div class="order-items-list">
                <h5>Order Items:</h5>
                <?php foreach ($order['items'] as $item): ?>
                  <div class="item-detail">
                    <div><strong><?= htmlspecialchars($item['item_type']) ?></strong></div>
                    <div>Size: <?= htmlspecialchars($item['measurement']) ?></div>
                    <div>Quantity: <?= htmlspecialchars($item['quantity']) ?></div>
                    <div>₱<?= number_format($item['item_subtotal'], 2) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="tracking-timeline">
              <?php
              $stageIndex = 0;
              foreach ($trackingStages as $key => $stageName):
                $stepClass = 'pending';
                $icon = $stageIndex + 1;
                $statusText = 'Pending';

                if ($stageIndex < $currentStageIndex) {
                  $stepClass = 'completed';
                  $icon = '✓';
                  $statusText = 'Completed';
                } elseif ($stageIndex == $currentStageIndex) {
                  $stepClass = 'current';
                  $icon = $stageIndex + 1;
                  $statusText = 'In Progress';
                }
              ?>
                <div class="tracking-step <?= $stepClass ?>">
                  <div class="tracking-icon"><?= $icon ?></div>
                  <div class="tracking-content">
                    <h5><?= htmlspecialchars($stageName) ?></h5>
                    <p>
                      <?php
                      switch ($key) {
                        case 'order_confirmation':
                          echo 'Order has been confirmed and accepted';
                          break;
                        case 'payment_confirmation':
                          echo 'Downpayment received and verified';
                          break;
                        case 'measurement':
                          echo 'Taking and recording measurements';
                          break;
                        case 'material_prep':
                          echo 'Gathering and preparing fabrics and materials';
                          break;
                        case 'cutting':
                          echo 'Cutting fabric according to measurements';
                          break;
                        case 'sewing':
                          echo 'Assembling and stitching the uniform';
                          break;
                        case 'finishing':
                          echo 'Adding final details and touches';
                          break;
                        case 'quality_checking':
                          echo 'Thorough inspection for defects and fit';
                          break;
                        case 'final_pressing':
                          echo 'Ironing and preparing for pick-up';
                          break;
                        case 'ready_for_pickup':
                          echo 'Order is ready for collection';
                          break;
                        case 'final_payment':
                          echo 'Complete remaining payment balance';
                          break;
                        case 'pick_up':
                          echo 'Order successfully collected';
                          break;
                      }
                      ?>
                    </p>
                    <div class="tracking-status"><?= $statusText ?></div>
                  </div>
                </div>
              <?php
                $stageIndex++;
              endforeach;
              ?>
            </div>

            <div class="tracking-summary">
              <div>
                <strong>Order Date</strong>
                <span><?= date('M j, Y', strtotime($order['order_date'])) ?></span>
              </div>

              <div>
                <strong>Payment Status</strong>
                <span class="payment-status-badge payment-<?= strtolower($order['payment_status'] ?? 'unpaid') ?>">
                  <?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?>
                </span>
              </div>

              <div>
                <strong>Payment Method</strong>
                <span><?= htmlspecialchars(ucfirst($order['payment_method'] ?? 'Not specified')) ?></span>
              </div>

              <div>
                <strong>Total Amount</strong>
                <span>₱<?= number_format($order['total_amount'], 2) ?></span>
              </div>

              <?php if ($order['downpayment'] > 0): ?>
                <div>
                  <strong>Downpayment</strong>
                  <span style="color: #2e7d32;">₱<?= number_format($order['downpayment'], 2) ?></span>
                </div>
                <div>
                  <strong>Balance</strong>
                  <span style="color: <?= $order['balance'] > 0 ? '#c05621' : '#2e7d32' ?>;">
                    ₱<?= number_format($order['balance'], 2) ?>
                  </span>
                </div>
              <?php endif; ?>

              <?php if (isset($order['due_date']) && $order['due_date'] && $order['due_date'] != '0000-00-00'): ?>
                <div>
                  <strong>Due Date</strong>
                  <span><?= date('F j, Y', strtotime($order['due_date'])) ?></span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- All Orders Section -->
    <div class="section">
      <h3>All Orders</h3>
      <?php if (!empty($ordersData)): ?>
        <?php foreach ($ordersData as $order): ?>
          <div class="order-card">
            <div class="order-header">
              <h4>Order #<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></h4>
              <span class="payment-status-badge payment-<?= strtolower($order['payment_status'] ?? 'unpaid') ?>">
                <?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?>
              </span>
            </div>

            <p><strong>Order Date:</strong> <?= date('F j, Y', strtotime($order['order_date'])) ?></p>
            <p><strong>Status:</strong> <span style="color: #e63946; font-weight: bold;"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span></p>

            <?php if (!empty($order['items'])): ?>
              <div class="order-items-list" style="margin-top: 15px;">
                <h5>Items in this order:</h5>
                <?php foreach ($order['items'] as $item): ?>
                  <div class="item-detail">
                    <div><strong><?= htmlspecialchars($item['item_type']) ?></strong></div>
                    <div>Size: <?= htmlspecialchars($item['measurement']) ?></div>
                    <div>Qty: <?= htmlspecialchars($item['quantity']) ?> × ₱<?= number_format($item['item_price'], 2) ?></div>
                    <div style="text-align: right;"><strong>₱<?= number_format($item['item_subtotal'], 2) ?></strong></div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="payment-info-box">
              <div class="payment-row">
                <label>Payment Method:</label>
                <span class="value"><?= htmlspecialchars(ucfirst($order['payment_method'] ?? 'Not specified')) ?></span>
              </div>
              <div class="payment-row">
                <label>Payment Status:</label>
                <span class="value"><?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?></span>
              </div>
              <div class="payment-row">
                <label>Total Amount:</label>
                <span class="value">₱<?= number_format($order['total_amount'], 2) ?></span>
              </div>
              <?php if ($order['downpayment'] > 0): ?>
                <div class="payment-row">
                  <label>Downpayment Paid:</label>
                  <span class="value" style="color: #2e7d32;">₱<?= number_format($order['downpayment'], 2) ?></span>
                </div>
                <div class="payment-row">
                  <label>Balance Due:</label>
                  <span class="value" style="color: <?= $order['balance'] > 0 ? '#c05621' : '#2e7d32' ?>; font-size: 18px;">
                    ₱<?= number_format($order['balance'], 2) ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-orders">No orders found</div>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>
