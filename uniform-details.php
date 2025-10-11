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
while ($row = mysqli_fetch_assoc($result)) {
  $uniforms[] = $row;
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

    button:disabled {
      background: #999;
      cursor: not-allowed;
    }

    .price {
      color: #2a9d8f;
      font-weight: bold;
      font-size: 18px;
      margin: 5px 0;
    }

    .stock-info {
      color: #666;
      font-size: 14px;
      margin-top: 5px;
    }

    .stock-info.out-of-stock {
      color: #e63946;
      font-weight: bold;
    }

    .stock-info.low-stock {
      color: #ff9800;
      font-weight: bold;
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
      border-color: #999;
      color: #999;
    }

    .qty-btn:disabled:hover {
      background: white;
      color: #999;
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

    .btn-group button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
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

    .payment-option.disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .payment-option.disabled:hover {
      border-color: #ddd;
      background: white;
    }

    .payment-option input {
      margin: 0 10px 0 0;
    }

    .payment-option input:disabled {
      cursor: not-allowed;
    }

    #custom-size,
    #custom-amount-box {
      display: none;
      margin-top: 10px;
    }

    .out-of-stock-btn {
      background: #999 !important;
      cursor: not-allowed !important;
    }

    .out-of-stock-btn:hover {
      background: #999 !important;
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
      <form method="POST" action="submit-order.php" id="order-form">
        <label>Uniform Type</label>
        <select name="uniform_type" id="uniform-select" onchange="updateSizes()">
          <?php foreach ($uniforms as $uniform): ?>
            <option value="<?= htmlspecialchars($uniform['uniform_name']) ?>"
              data-id="<?= $uniform['id'] ?>"
              data-sizes="<?= htmlspecialchars($uniform['available_sizes']) ?>"
              data-price-xs="<?= $uniform['price_xs'] ?>"
              data-price-s="<?= $uniform['price_s'] ?>"
              data-price-m="<?= $uniform['price_m'] ?>"
              data-price-l="<?= $uniform['price_l'] ?>"
              data-price-xl="<?= $uniform['price_xl'] ?>"
              data-stock-xs="<?= $uniform['stock_xs'] ?>"
              data-stock-s="<?= $uniform['stock_s'] ?>"
              data-stock-m="<?= $uniform['stock_m'] ?>"
              data-stock-l="<?= $uniform['stock_l'] ?>"
              data-stock-xl="<?= $uniform['stock_xl'] ?>">
              <?= htmlspecialchars($uniform['uniform_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Size</label>
        <select name="size" id="size-select" onchange="updatePrice()"></select>
        <div class="stock-info" id="stock-info"></div>

        <label>Quantity</label>
        <div class="quantity-selector">
          <button type="button" class="qty-btn" id="qty-minus" onclick="changeQty(-1)">−</button>
          <input type="number" name="quantity" id="qty-input" class="qty-input" value="1" min="1" max="99" readonly>
          <button type="button" class="qty-btn" id="qty-plus" onclick="changeQty(1)">+</button>
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
            <button type="button" class="active" id="dp-full" onclick="setDP('full', event)">Full</button>
            <button type="button" id="dp-50" onclick="setDP('50', event)">50%</button>
            <button type="button" id="dp-30" onclick="setDP('30', event)">30%</button>
            <button type="button" id="dp-custom" onclick="setDP('custom', event)">Custom</button>
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
          <div class="payment-option" id="pay-cash-option" onclick="selectPay('cash')">
            <input type="radio" name="payment_method" value="cash" id="pay-cash" required>
            <span>💵 Cash</span>
          </div>
          <div class="payment-option" id="pay-paypal-option" onclick="selectPay('paypal')">
            <input type="radio" name="payment_method" value="paypal" id="pay-paypal" required>
            <span>💳 PayPal</span>
          </div>
        </div>

        <!-- CRITICAL: Hidden fields for submit-order.php -->
        <input type="hidden" name="selected_price" id="selected-price" value="0.00">
        <input type="hidden" name="downpayment" id="downpayment-input" value="0.00">
        <input type="hidden" name="school_id" value="<?= $school_id ?>">
        <input type="hidden" name="uniform_id" id="uniform-id-input" value="0">

        <button type="submit" id="submit-btn">Submit Order</button>
      </form>
    <?php else: ?>
      <p>No uniforms available.</p>
    <?php endif; ?>
  </div>

  <script>
    let currentPrice = 0;
    let currentStock = 0;
    let dpType = 'full';
    let isOutOfStock = false;
    let currentUniformId = 0;

    function changeQty(change) {
      if (isOutOfStock) return;

      const input = document.getElementById('qty-input');
      let qty = parseInt(input.value) || 1;
      qty += change;
      if (qty < 1) qty = 1;
      if (qty > currentStock) qty = currentStock;
      if (qty > 99) qty = 99;
      input.value = qty;
      updateBreakdown();
    }

    function updateSizes() {
      const uniformSelect = document.getElementById('uniform-select');
      const selectedOption = uniformSelect.options[uniformSelect.selectedIndex];

      // IMPORTANT: Get and store the uniform ID
      currentUniformId = parseInt(selectedOption.getAttribute('data-id')) || 0;
      document.getElementById('uniform-id-input').value = currentUniformId;

      const sizesStr = selectedOption.getAttribute('data-sizes');
      const sizes = sizesStr.split(',').map(s => s.trim());

      const sizeSelect = document.getElementById('size-select');
      sizeSelect.innerHTML = '';

      sizes.forEach(size => {
        const priceAttr = 'data-price-' + size.toLowerCase();
        const stockAttr = 'data-stock-' + size.toLowerCase();
        const price = parseFloat(selectedOption.getAttribute(priceAttr)) || 0;
        const stock = parseInt(selectedOption.getAttribute(stockAttr)) || 0;

        const opt = document.createElement('option');
        opt.value = size;
        opt.textContent = size + ' - ₱' + price.toFixed(2);
        opt.setAttribute('data-price', price);
        opt.setAttribute('data-stock', stock);

        if (stock === 0) {
          opt.textContent += ' (Out of Stock)';
          opt.disabled = true;
        }

        sizeSelect.appendChild(opt);
      });

      const custom = document.createElement('option');
      custom.value = 'custom';
      custom.textContent = 'Customize';
      custom.setAttribute('data-price', '0');
      custom.setAttribute('data-stock', '999');
      sizeSelect.appendChild(custom);

      updatePrice();
    }

    function updatePrice() {
      const select = document.getElementById('size-select');
      const opt = select.options[select.selectedIndex];
      const size = select.value;

      if (size === 'custom') {
        document.getElementById('custom-size').style.display = 'block';
        currentPrice = 0;
        currentStock = 999;
        isOutOfStock = false;
      } else {
        document.getElementById('custom-size').style.display = 'none';
        currentPrice = parseFloat(opt.getAttribute('data-price')) || 0;
        currentStock = parseInt(opt.getAttribute('data-stock')) || 0;
        isOutOfStock = currentStock === 0;
      }

      // Update stock info
      const stockInfo = document.getElementById('stock-info');
      if (size !== 'custom') {
        if (currentStock === 0) {
          stockInfo.textContent = '⚠️ Out of Stock';
          stockInfo.className = 'stock-info out-of-stock';
        } else if (currentStock < 5) {
          stockInfo.textContent = '⚠️ Only ' + currentStock + ' items left in stock';
          stockInfo.className = 'stock-info low-stock';
        } else {
          stockInfo.textContent = '✓ ' + currentStock + ' items available';
          stockInfo.className = 'stock-info';
        }
      } else {
        stockInfo.textContent = '';
      }

      // Update quantity input max
      const qtyInput = document.getElementById('qty-input');
      if (isOutOfStock) {
        qtyInput.value = 0;
        qtyInput.max = 0;
      } else {
        qtyInput.value = 1;
        qtyInput.max = Math.min(currentStock, 99);
      }

      // Disable/enable quantity buttons
      document.getElementById('qty-minus').disabled = isOutOfStock;
      document.getElementById('qty-plus').disabled = isOutOfStock;

      // Disable/enable downpayment buttons
      document.getElementById('dp-full').disabled = isOutOfStock;
      document.getElementById('dp-50').disabled = isOutOfStock;
      document.getElementById('dp-30').disabled = isOutOfStock;
      document.getElementById('dp-custom').disabled = isOutOfStock;

      // Disable/enable payment options
      const cashOption = document.getElementById('pay-cash-option');
      const paypalOption = document.getElementById('pay-paypal-option');
      const cashRadio = document.getElementById('pay-cash');
      const paypalRadio = document.getElementById('pay-paypal');

      if (isOutOfStock) {
        cashOption.classList.add('disabled');
        paypalOption.classList.add('disabled');
        cashRadio.disabled = true;
        paypalRadio.disabled = true;
      } else {
        cashOption.classList.remove('disabled');
        paypalOption.classList.remove('disabled');
        cashRadio.disabled = false;
        paypalRadio.disabled = false;
      }

      // Update submit button
      const submitBtn = document.getElementById('submit-btn');
      if (isOutOfStock) {
        submitBtn.textContent = 'Out of Stock';
        submitBtn.disabled = true;
        submitBtn.classList.add('out-of-stock-btn');
      } else {
        submitBtn.textContent = 'Submit Order';
        submitBtn.disabled = false;
        submitBtn.classList.remove('out-of-stock-btn');
      }

      document.getElementById('price-display').textContent = '₱' + currentPrice.toFixed(2);
      document.getElementById('selected-price').value = currentPrice.toFixed(2);
      updateBreakdown();
    }

    function setDP(type, event) {
      if (isOutOfStock) return;
      event.preventDefault();
      const buttons = document.querySelectorAll('.btn-group button');
      for (let i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
      }
      event.target.classList.add('active');
      dpType = type;
      document.getElementById('custom-amount-box').style.display = type === 'custom' ? 'block' : 'none';
      if (type === 'custom') document.getElementById('custom-amount').focus();
      updateBreakdown();
    }

    function updateBreakdown() {
      if (isOutOfStock) {
        document.getElementById('subtotal').textContent = '₱0.00';
        document.getElementById('dp-amount').textContent = '₱0.00';
        document.getElementById('balance').textContent = '₱0.00';
        document.getElementById('downpayment-input').value = '0.00';
        return;
      }

      const qty = parseInt(document.getElementById('qty-input').value) || 0;
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
      document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
      document.getElementById('dp-amount').textContent = '₱' + dp.toFixed(2);
      document.getElementById('balance').textContent = '₱' + balance.toFixed(2);
      document.getElementById('downpayment-input').value = dp.toFixed(2);
    }

    function selectPay(method) {
      if (isOutOfStock) return;
      const options = document.querySelectorAll('.payment-option');
      for (let i = 0; i < options.length; i++) {
        options[i].classList.remove('selected');
      }
      const radio = document.getElementById('pay-' + method);
      radio.checked = true;
      radio.closest('.payment-option').classList.add('selected');
    }

    // Prevent form submission when out of stock
    document.getElementById('order-form').addEventListener('submit', function(e) {
      if (isOutOfStock) {
        e.preventDefault();
        alert('This item is currently out of stock. Please select a different size or uniform.');
        return false;
      }

      // Debug log to verify uniform_id is being sent
      console.log('Submitting order with uniform_id:', document.getElementById('uniform-id-input').value);
    });

    // Initialize on page load
    updateSizes();
  </script>
</body>

</html>
