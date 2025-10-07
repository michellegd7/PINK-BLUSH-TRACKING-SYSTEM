<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'pink_blush_tailoring';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get logged in staff info (you'll need to implement proper authentication)
// For now, we'll show all orders since staff_id in your orders table refers to customers
$staff_id = 11; // This would be from session after login
$staff_name = "John Doe";
$staff_role = "Tailor";

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start_order':
                $order_id = $_POST['order_id'];
                $stmt = $pdo->prepare("UPDATE orders SET status = 'In Progress' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                echo json_encode(['success' => true, 'message' => 'Order started successfully']);
                exit;

            case 'complete_order':
                $order_id = $_POST['order_id'];
                $stmt = $pdo->prepare("UPDATE orders SET status = 'Completed' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                echo json_encode(['success' => true, 'message' => 'Order completed successfully']);
                exit;

            case 'update_tracking':
                $order_id = $_POST['order_id'];
                $status = $_POST['status'];
                $notes = $_POST['notes'];

                // Update order status
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $stmt->execute([$status, $order_id]);

                // Insert tracking record if tracking table exists
                try {
                    $stmt = $pdo->prepare("INSERT INTO tracking (order_id, status, notes, updated_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$order_id, $status, $notes]);
                } catch (PDOException $e) {
                    // Tracking table might not exist yet
                }

                echo json_encode(['success' => true, 'message' => 'Tracking updated successfully']);
                exit;
        }
    }
}

// Fetch statistics for pending orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$stmt->execute();
$assigned_count = $stmt->fetchColumn();

// Fetch in-progress orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status IN ('In Progress', 'Cutting', 'Sewing', 'Fitting', 'Finishing')");
$stmt->execute();
$in_progress_count = $stmt->fetchColumn();

// Fetch completed orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'Completed'");
$stmt->execute();
$completed_count = $stmt->fetchColumn();

// Fetch assigned (pending) orders - without JOIN first to avoid errors
$stmt = $pdo->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.status = 'Pending'
    ORDER BY o.due_date ASC
");
$stmt->execute();
$assigned_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer names separately
foreach ($assigned_orders as &$order) {
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->execute([$order['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    // Try different possible column names
    $order['customer_name'] = $customer['name'] ?? $customer['customer_name'] ?? $customer['full_name'] ?? 'Unknown';
}
unset($order);

// Fetch in-progress orders
$stmt = $pdo->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.status IN ('In Progress', 'Cutting', 'Sewing', 'Fitting', 'Finishing')
    ORDER BY o.due_date ASC
");
$stmt->execute();
$in_progress_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer names separately
foreach ($in_progress_orders as &$order) {
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->execute([$order['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    // Try different possible column names
    $order['customer_name'] = $customer['name'] ?? $customer['customer_name'] ?? $customer['full_name'] ?? 'Unknown';
}
unset($order);

// Function to format due date
function formatDueDate($due_date)
{
    if (empty($due_date) || $due_date == '0000-00-00') return 'No due date';

    $today = new DateTime();
    $due = new DateTime($due_date);
    $diff = $today->diff($due);

    if ($diff->days == 0 && !$diff->invert) {
        return 'Today';
    } elseif ($diff->days == 1 && !$diff->invert) {
        return 'Tomorrow';
    } elseif ($diff->invert) {
        return 'Overdue - ' . date('M j, Y', strtotime($due_date));
    } else {
        return date('M j, Y', strtotime($due_date));
    }
}

// Function to get status class
function getStatusClass($status)
{
    $status_lower = strtolower(str_replace(' ', '-', $status));
    return 'status-' . $status_lower;
}

// Function to get item description (placeholder since we don't have order_item table yet)
function getItemDescription($order)
{
    // You can customize this based on your needs
    return "Order Item";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .header {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .stat-num {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 5px;
        }

        .content {
            background: white;
            padding: 20px;
            border-radius: 5px;
        }

        h2 {
            margin-bottom: 15px;
            color: #333;
        }

        .order {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }

        .order-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .order-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 14px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-start {
            background: #667eea;
            color: white;
        }

        .btn-edit {
            background: #f59e0b;
            color: white;
        }

        .btn-complete {
            background: #10b981;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 25px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .close {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group select,
        .form-group textarea,
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group input[readonly] {
            background: #f0f0f0;
        }

        .btn-save {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .btn-save:hover {
            background: #5568d3;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-cutting {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-sewing {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-fitting {
            background: #fce7f3;
            color: #831843;
        }

        .status-finishing {
            background: #d1fae5;
            color: #065f46;
        }

        .status-in-progress {
            background: #bfdbfe;
            color: #1e3a8a;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-quality-check {
            background: #fce7f3;
            color: #831843;
        }

        .status-ready-for-pickup {
            background: #d1fae5;
            color: #065f46;
        }

        .no-orders {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.error {
            background: #ef4444;
        }

        .overdue {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="notification" id="notification"></div>

    <div class="header">
        <h1>Staff Work Dashboard</h1>
        <p><?php echo htmlspecialchars($staff_name . ' - ' . $staff_role); ?></p>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="stat-num"><?php echo $assigned_count; ?></div>
            <div class="stat-label">Assigned</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?php echo $in_progress_count; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat">
            <div class="stat-num"><?php echo $completed_count; ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <div class="content">
        <h2>My Assigned Orders</h2>

        <?php if (empty($assigned_orders)): ?>
            <div class="no-orders">No assigned orders at the moment</div>
        <?php else: ?>
            <?php foreach ($assigned_orders as $order): ?>
                <div class="order">
                    <div class="order-title">
                        Order #<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?> -
                        <?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown Customer'); ?>
                        <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </div>
                    <div class="order-info">
                        <?php echo htmlspecialchars(getItemDescription($order)); ?> |
                        Due: <span class="<?php echo (strtotime($order['due_date']) < time() ? 'overdue' : ''); ?>">
                            <?php echo formatDueDate($order['due_date']); ?>
                        </span>
                    </div>
                    <button class="btn btn-start" onclick="startOrder(<?php echo $order['order_id']; ?>)">Start Work</button>
                    <button class="btn btn-edit" onclick="openTrackingModal(
                        <?php echo $order['order_id']; ?>, 
                        '<?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown', ENT_QUOTES); ?>', 
                        '<?php echo htmlspecialchars(getItemDescription($order), ENT_QUOTES); ?>', 
                        '<?php echo htmlspecialchars($order['status'], ENT_QUOTES); ?>'
                    )">Update Tracking</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2 style="margin-top: 30px;">Currently Working On</h2>

        <?php if (empty($in_progress_orders)): ?>
            <div class="no-orders">No orders in progress</div>
        <?php else: ?>
            <?php foreach ($in_progress_orders as $order): ?>
                <div class="order">
                    <div class="order-title">
                        Order #<?php echo str_pad($order['order_id'], 4, '0', STR_PAD_LEFT); ?> -
                        <?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown Customer'); ?>
                        <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </div>
                    <div class="order-info">
                        <?php echo htmlspecialchars(getItemDescription($order)); ?> |
                        Due: <span class="<?php echo (strtotime($order['due_date']) < time() ? 'overdue' : ''); ?>">
                            <?php echo formatDueDate($order['due_date']); ?>
                        </span>
                    </div>
                    <button class="btn btn-complete" onclick="completeOrder(<?php echo $order['order_id']; ?>)">Mark Complete</button>
                    <button class="btn btn-edit" onclick="openTrackingModal(
                        <?php echo $order['order_id']; ?>, 
                        '<?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown', ENT_QUOTES); ?>', 
                        '<?php echo htmlspecialchars(getItemDescription($order), ENT_QUOTES); ?>', 
                        '<?php echo htmlspecialchars($order['status'], ENT_QUOTES); ?>'
                    )">Update Tracking</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Tracking Modal -->
    <div id="trackingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Order Tracking</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>

            <form id="trackingForm">
                <input type="hidden" id="modalOrderId" name="order_id">

                <div class="form-group">
                    <label>Order Number</label>
                    <input type="text" id="modalOrderNum" readonly>
                </div>

                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" id="modalCustomer" readonly>
                </div>

                <div class="form-group">
                    <label>Item Type</label>
                    <input type="text" id="modalItem" readonly>
                </div>

                <div class="form-group">
                    <label>Current Status</label>
                    <select id="modalStatus" name="status">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Cutting">Cutting</option>
                        <option value="Sewing">Sewing</option>
                        <option value="Fitting">Fitting</option>
                        <option value="Finishing">Finishing</option>
                        <option value="Quality Check">Quality Check</option>
                        <option value="Ready for Pickup">Ready for Pickup</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Progress Notes</label>
                    <textarea id="modalNotes" name="notes" placeholder="Add notes about the progress..."></textarea>
                </div>

                <button type="button" class="btn-save" onclick="saveTracking()">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification' + (isError ? ' error' : '');
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        function startOrder(orderId) {
            if (!confirm('Start working on Order #' + String(orderId).padStart(4, '0') + '?')) return;

            const formData = new FormData();
            formData.append('action', 'start_order');
            formData.append('order_id', orderId);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message || 'Error starting order', true);
                    }
                })
                .catch(error => {
                    showNotification('Error: ' + error.message, true);
                });
        }

        function completeOrder(orderId) {
            if (!confirm('Mark Order #' + String(orderId).padStart(4, '0') + ' as complete?')) return;

            const formData = new FormData();
            formData.append('action', 'complete_order');
            formData.append('order_id', orderId);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message || 'Error completing order', true);
                    }
                })
                .catch(error => {
                    showNotification('Error: ' + error.message, true);
                });
        }

        function openTrackingModal(orderNum, customer, item, status) {
            document.getElementById('modalOrderId').value = orderNum;
            document.getElementById('modalOrderNum').value = '#' + String(orderNum).padStart(4, '0');
            document.getElementById('modalCustomer').value = customer;
            document.getElementById('modalItem').value = item;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalNotes').value = '';
            document.getElementById('trackingModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('trackingModal').style.display = 'none';
        }

        function saveTracking() {
            const orderId = document.getElementById('modalOrderId').value;
            const status = document.getElementById('modalStatus').value;
            const notes = document.getElementById('modalNotes').value;

            const formData = new FormData();
            formData.append('action', 'update_tracking');
            formData.append('order_id', orderId);
            formData.append('status', status);
            formData.append('notes', notes);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message);
                        closeModal();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message || 'Error updating tracking', true);
                    }
                })
                .catch(error => {
                    showNotification('Error: ' + error.message, true);
                });
        }

        window.onclick = function(event) {
            const modal = document.getElementById('trackingModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>