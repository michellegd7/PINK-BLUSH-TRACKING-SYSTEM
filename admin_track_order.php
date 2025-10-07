<?php
include 'database.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all orders with customer details
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.contact_number,
          GROUP_CONCAT(DISTINCT oi.item_type SEPARATOR ', ') as products
          FROM orders o
          LEFT JOIN customer c ON o.customer_id = c.customer_id
          LEFT JOIN order_item oi ON o.order_id = oi.order_id
          GROUP BY o.order_id
          ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $query);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

// Handle AJAX update requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);

        $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit();
    }

    if ($_POST['action'] === 'delete_order') {
        $order_id = intval($_POST['order_id']);

        // Delete order items first
        $delete_items = "DELETE FROM order_item WHERE order_id = ?";
        $stmt1 = mysqli_prepare($conn, $delete_items);
        mysqli_stmt_bind_param($stmt1, "i", $order_id);
        mysqli_stmt_execute($stmt1);

        // Delete order
        $delete_order = "DELETE FROM orders WHERE order_id = ?";
        $stmt2 = mysqli_prepare($conn, $delete_order);
        mysqli_stmt_bind_param($stmt2, "i", $order_id);

        if (mysqli_stmt_execute($stmt2)) {
            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Tailor - Order Tracking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 22px;
            font-weight: 600;
        }

        .navbar-menu {
            display: flex;
            gap: 5px;
            list-style: none;
        }

        .nav-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .nav-item {
            color: rgba(255, 255, 255, 0.9);
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 6px;
            transition: background 0.3s;
            font-size: 15px;
            font-weight: 500;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .main-content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .page-subtitle {
            color: #7f8c8d;
            font-size: 15px;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 20px;
            color: #2c3e50;
            font-weight: 600;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #f8f9fa;
        }

        .data-table th {
            text-align: left;
            padding: 15px;
            color: #5a6c7d;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid #e9ecef;
        }

        .data-table td {
            padding: 20px 15px;
            color: #2c3e50;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-order_confirmation {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-payment_confirmation {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-measurement {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-material_prep {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-cutting {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-sewing {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-finishing {
            background: #fff9c4;
            color: #f57f17;
        }

        .status-quality_checking {
            background: #ffe0b2;
            color: #e65100;
        }

        .status-final_pressing {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .status-ready_for_pickup {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-ready {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-final_payment {
            background: #b2dfdb;
            color: #00695c;
        }

        .status-delivered {
            background: #a5d6a7;
            color: #1b5e20;
        }

        .status-completed {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .btn-edit {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: background 0.3s;
            margin-right: 5px;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: #5a6c7d;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .form-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-save {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .customer-info {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 3px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <span>✂️</span>
            <span>Master Tailor</span>
        </div>
        <ul class="navbar-menu">
            <li class="nav-item"><a href="admin_orders.php">Dashboard</a></li>
            <li class="nav-item"><a href="admin_orders.php">Orders</a></li>
            <li class="nav-item"><a href="admin_customer.php">Customers</a></li>
            <li class="nav-item"><a href="admin_products.php">Products</a></li>
            <li class="nav-item active">Tracking</li>
        </ul>
        <div class="user-profile">
            <span>Admin</span>
            <div class="user-avatar">A</div>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Order Tracking</h1>
            <p class="page-subtitle">Monitor and manage order progress from measurement to completion</p>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">All Orders</h2>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product(s)</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Due Date</th>
                        <th>Total Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <div><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
                                    <div class="customer-info"><?= htmlspecialchars($order['contact_number']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($order['products'] ?: 'N/A') ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '_', $order['status'])) ?>">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['status']))) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                <td>
                                    <?php if ($order['due_date'] && $order['due_date'] != '0000-00-00'): ?>
                                        <?= date('M d, Y', strtotime($order['due_date'])) ?>
                                    <?php else: ?>
                                        <span style="color: #95a5a6;">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                                <td>
                                    <button class="btn-edit" onclick="openStatusModal(<?= $order['order_id'] ?>, '<?= htmlspecialchars($order['status']) ?>')">
                                        Update Status
                                    </button>
                                    <button class="btn-delete" onclick="deleteOrder(<?= $order['order_id'] ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Update Order Status</h2>
            <form id="statusForm">
                <input type="hidden" id="modal_order_id" name="order_id">
                <div class="form-group">
                    <label class="form-label">Order Status</label>
                    <select class="form-select" id="modal_status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="order_confirmation">Order Confirmation</option>
                        <option value="payment_confirmation">Payment Confirmation</option>
                        <option value="measurement">Measurement</option>
                        <option value="material_prep">Material Preparation</option>
                        <option value="cutting">Cutting</option>
                        <option value="sewing">Sewing</option>
                        <option value="finishing">Finishing</option>
                        <option value="quality_checking">Quality Checking</option>
                        <option value="final_pressing">Final Pressing / Packaging</option>
                        <option value="ready_for_pickup">Ready for Pickup</option>
                        <option value="final_payment">Final Payment</option>
                        <option value="delivered">Delivered to Customer</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeStatusModal()">Cancel</button>
                    <button type="submit" class="btn-save">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(orderId, currentStatus) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
        }

        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('order_id', document.getElementById('modal_order_id').value);
            formData.append('status', document.getElementById('modal_status').value);

            fetch('admin_tracking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred. Please try again.');
                    console.error('Error:', error);
                });
        });

        function deleteOrder(orderId) {
            if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_order');
            formData.append('order_id', orderId);

            fetch('admin_tracking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred. Please try again.');
                    console.error('Error:', error);
                });
        }

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>

</html>