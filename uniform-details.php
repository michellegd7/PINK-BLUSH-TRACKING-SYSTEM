<?php
include 'database.php';
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

$orderSuccess = isset($_GET['order']) && $_GET['order'] === 'success';
$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;
$user = $_SESSION['username'];

$query = "SELECT * FROM uniform_options WHERE school_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$uniforms = [];
$sizes = [];
$prices = [];

while ($row = mysqli_fetch_assoc($result)) {
  $uniforms[] = $row['uniform_name'];
  $available_sizes = explode(",", $row['available_sizes']);
  $sizes[$row['uniform_name']] = array_map('trim', $available_sizes);
  $size_prices = [
    'XS' => $row['price_xs'] ?? 0,
    'S' => $row['price_s'] ?? 0,
    'M' => $row['price_m'] ?? 0,
    'L' => $row['price_l'] ?? 0,
    'XL' => $row['price_xl'] ?? 0
  ];
  $prices[$row['uniform_name']] = $size_prices;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Uniform Options</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      padding: 20px;
    }

    .container {
      max-width: 500px;
      margin: 0 auto;
      background: white;
      padding: 25px;
      border-radius: 8px;
    }

    h2 {
      color: #e63946;
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-top: 12px;
      font-weight: bold;
      font-size: 14px;
    }

    select,
    input[type="text"],
    input[type="number"] {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border: 1px solid #ddd;
      border-radius: 4px;
      box-sizing: border-box;
    }

    button {
      margin-top: 15px;
      padding: 10px;
      width: 100%;
      background: #e63946;
      color: white;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
    }

    button:hover {
      background: #d62828;
    }

    .price {
      color: #2a9d8f;
      font-weight: bold;
      font-size: 18px;
      margin: 5px 0;
    }

    .success {
      background: #d4edda;
      color: #155724;
      padding: 12px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .back {
      display: inline-block;
      margin-bottom: 15px;
      background: #2a9d8f;
      color: white;
      padding: 8px 15px;
      border-radius: 4px;
      text-decoration: none;
    }

    .quantity-selector {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 8px;
    }

    .qty-btn {
      width: 35px;
      height: 35px;
      border: 2px solid #e63946;
      background: white;
      color: #e63946;
      font-size: 18px;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      margin: 0;
    }

    .qty-btn:hover {
      background: #e63946;
      color: white;
    }

    .qty-btn:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }

    .qty-input {
      width: 60px;
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      padding: 8px;
      margin: 0;
    }

    .downpayment-box {
      background: #fff9e6;
      border: 1px solid #ffc107;
      border-radius: 4px;
      padding: 12px;
      margin-top: 15px;
    }

    .downpayment-box h4 {
      margin: 0 0 8px 0;
      font-size: 15px;
      color: #856404;
    }

    .btn-group {
      display: flex;
      gap: 8px;
      margin: 10px 0;
    }

    .btn-group button {
      margin: 0;
      padding: 8px 12px;
      background: white;
      color: #856404;
      border: 2px solid #ffc107;
      font-size: 13px;
    }

    .btn-group button.active {
      background: #ffc107;
      color: white;
    }

    .breakdown {
      background: #f9f9f9;
      padding: 12px;
      border-radius: 4px;
      margin-top: 12px;
    }

    .row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      border-bottom: 1px solid #ddd;
    }

    .row:last-child {
      border: none;
      font-weight: bold;
      color: #e63946;
    }

    .payment-box {
      margin-top: 15px;
    }

    .payment-option {
      display: flex;
      align-items: center;
      padding: 10px;
      margin: 8px 0;
      border: 2px solid #ddd;
      border-radius: 4px;
      cursor: pointer;
    }

    .payment-option:hover {
      border-color: #2a9d8f;
      background: #f0f9f8;
    }

    .payment-option.selected {
      border-color: #2a9d8f;
      background: #e8f5f3;
    }

    .payment-option input {
      margin: 0 10px 0 0;
    }

    #custom-size,
    #custom-amount-box {
      display: none;
      margin-top: 10px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h2>Choose Your Uniform</h2>

    <?php if ($orderSuccess): ?>
      <div class="success">✅ Order placed successfully!</div>
      <a href="uni_offered.php" class="back">⬅️ Back</a>
    <?php endif; ?>

    <?php if (!empty($uniforms)): ?>
      <form method="POST" action="submit-order.php">
        <label>Uniform Type</label>
        <select name="uniform_type" id="uniform-select">
          <?php foreach ($uniforms as $name): ?>
            <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Size</label>
        <select name="size" id="size-select"></select>

        <label>Quantity</label>
        <div class="quantity-selector">
          <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
          <input type="number" name="quantity" id="qty-input" class="qty-input" value="1" min="1" max="99" readonly>
          <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
        </div>

        <label>Price per Item</label>
        <div class="price" id="price-display">₱0.00</div>

        <div id="custom-size">
          <label>Custom Measurements</label>
          <input type="text" name="custom_size" placeholder="e.g., Chest: 34in, Waist: 28in">
        </div>

        <!-- Downpayment -->
        <div class="downpayment-box">
          <h4>💰 Downpayment</h4>
          <div class="btn-group">
            <button type="button" class="active" onclick="setDP('full', event)">Full</button>
            <button type="button" onclick="setDP('50', event)">50%</button>
            <button type="button" onclick="setDP('30', event)">30%</button>
            <button type="button" onclick="setDP('custom', event)">Custom</button>
          </div>
          <div id="custom-amount-box">
            <input type="number" id="custom-amount" placeholder="Enter amount" min="0" step="0.01" oninput="updateBreakdown()">
          </div>

          <div class="breakdown">
            <div class="row">
              <span>Subtotal:</span>
              <span id="subtotal">₱0.00</span>
            </div>
            <div class="row">
              <span>Downpayment:</span>
              <span id="dp-amount">₱0.00</span>
            </div>
            <div class="row">
              <span>Balance:</span>
              <span id="balance">₱0.00</span>
            </div>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="payment-box">
          <label>Payment Method</label>
          <div class="payment-option" onclick="selectPay('cash')">
            <input type="radio" name="payment_method" value="cash" id="pay-cash" required>
            <span>💵 Cash</span>
          </div>
          <div class="payment-option" onclick="selectPay('paypal')">
            <input type="radio" name="payment_method" value="paypal" id="pay-paypal" required>
            <span>💳 PayPal</span>
          </div>
        </div>

        <input type="hidden" name="selected_price" id="selected-price" value="0.00">
        <input type="hidden" name="downpayment" id="downpayment-input" value="0.00">
        <input type="hidden" name="school_id" value="<?= $school_id ?>">
        <button type="submit">Submit Order</button>
      </form>
    <?php else: ?>
      <p>No uniforms available.</p>
    <?php endif; ?>
  </div>

  <script>
    const sizeMap = <?= json_encode($sizes) ?>;
    const priceMap = <?= json_encode($prices) ?>;
    let currentPrice = 0;
    let dpType = 'full';

    function changeQty(change) {
      const input = document.getElementById('qty-input');
      let qty = parseInt(input.value) || 1;
      qty += change;
      if (qty < 1) qty = 1;
      if (qty > 99) qty = 99;
      input.value = qty;
      updateBreakdown();
    }

    function updateSizes() {
      const uniform = document.getElementById('uniform-select').value;
      const sizes = sizeMap[uniform] || [];
      const prices = priceMap[uniform] || {};
      const select = document.getElementById('size-select');

      select.innerHTML = '';
      sizes.forEach(size => {
        const s = size.trim();
        const p = prices[s] || 0;
        const opt = document.createElement('option');
        opt.value = s;
        opt.textContent = `${s} - ₱${parseFloat(p).toFixed(2)}`;
        opt.dataset.price = p;
        select.appendChild(opt);
      });

      const custom = document.createElement('option');
      custom.value = 'custom';
      custom.textContent = 'Customize';
      custom.dataset.price = '0';
      select.appendChild(custom);

      updatePrice();
    }

    function updatePrice() {
      const select = document.getElementById('size-select');
      const opt = select.options[select.selectedIndex];
      const size = select.value;

      if (size === 'custom') {
        document.getElementById('custom-size').style.display = 'block';
        currentPrice = 0;
      } else {
        document.getElementById('custom-size').style.display = 'none';
        currentPrice = parseFloat(opt.dataset.price || 0);
      }

      document.getElementById('price-display').textContent = `₱${currentPrice.toFixed(2)}`;
      document.getElementById('selected-price').value = currentPrice.toFixed(2);
      updateBreakdown();
    }

    function setDP(type, event) {
      event.preventDefault();
      document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
      event.target.classList.add('active');
      dpType = type;
      document.getElementById('custom-amount-box').style.display = type === 'custom' ? 'block' : 'none';
      if (type === 'custom') document.getElementById('custom-amount').focus();
      updateBreakdown();
    }

    function updateBreakdown() {
      const qty = parseInt(document.getElementById('qty-input').value) || 1;
      const subtotal = currentPrice * qty;

      let dp = 0;
      if (dpType === 'full') dp = subtotal;
      else if (dpType === '50') dp = subtotal * 0.5;
      else if (dpType === '30') dp = subtotal * 0.3;
      else if (dpType === 'custom') {
        dp = parseFloat(document.getElementById('custom-amount').value) || 0;
        if (dp > subtotal) {
          dp = subtotal;
          document.getElementById('custom-amount').value = subtotal.toFixed(2);
        }
      }

      const balance = subtotal - dp;
      document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
      document.getElementById('dp-amount').textContent = `₱${dp.toFixed(2)}`;
      document.getElementById('balance').textContent = `₱${balance.toFixed(2)}`;
      document.getElementById('downpayment-input').value = dp.toFixed(2);
    }

    function selectPay(method) {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      const radio = document.getElementById(`pay-${method}`);
      radio.checked = true;
      radio.closest('.payment-option').classList.add('selected');
    }

    document.getElementById('uniform-select').addEventListener('change', updateSizes);
    document.getElementById('size-select').addEventListener('change', updatePrice);

    updateSizes();
  </script>
</body>

</html>