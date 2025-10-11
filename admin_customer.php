<?php
session_start();


include 'database.php';
// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

// Get logged-in user's full name
$admin_name = $_SESSION['user'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));


// Fetch all orders with customer info
$query = "SELECT o.*, c.first_name, c.last_name, c.email 
          FROM orders o 
          LEFT JOIN customer c ON o.customer_id = c.customer_id 
          ORDER BY o.order_date DESC";


$result = mysqli_query($conn, $query);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle edit/update operations
if ($_POST['action'] ?? '' === 'update_customer') {
    $stmt = $conn->prepare("UPDATE customer SET first_name = ?, last_name = ?, username = ?, email = ?, contact_number = ?, address = ? WHERE customer_id = ?");
    $stmt->bind_param(
        "sssssssi",
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['username'],
        $_POST['email'],
        $_POST['contact_number'],
        $_POST['address'],
        $_POST['customer_id']
    );

    if ($stmt->execute()) {
        $success_message = "Customer updated successfully!";
    } else {
        $error_message = "Error updating customer: " . $stmt->error;
    }
    $stmt->close();
}

// Get all customers
$customers = [];
$result = $conn->query("SELECT * FROM customer ORDER BY customer_id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Get statistics
$total_customers = 0;
$active_customers = 0;
$new_this_week = 0;

$result = $conn->query("SELECT COUNT(*) as total FROM customer");
if ($result) {
    $total_customers = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) as active FROM customer WHERE username IS NOT NULL AND username != ''");
if ($result) {
    $active_customers = $result->fetch_assoc()['active'];
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Tailor Shop</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }

        /* Top Navigation */
        .top-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu {
            display: flex;
            gap: 1.5rem;
            list-style: none;
        }

        .nav-item {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-item a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatar {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #718096;
            font-size: 1.1rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #c6f6d5;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Customer Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            color: #4a5568;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #f7fafc;
        }

        .btn-primary {
            background: #4299e1;
            border-color: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background: #3182ce;
        }



        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }

        tr:hover {
            background: #f7fafc;
        }

        .customer-info {
            font-weight: 500;
        }

        .customer-info small {
            display: block;
            color: #718096;
            font-weight: 400;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            top: 1rem;
            right: 1.5rem;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4299e1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .main-content {
                padding: 0 1rem;
            }

            th,
            td {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .table-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="logo">
                <span>✂️</span>
                <span>Master Tailor</span>
            </div>

            <ul class="nav-menu">
                <li class="nav-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a href="admin_orders.php">Orders</a></li>
                <li class="nav-item active"><a href="admin_customer.php">Customers</a></li>
                <li class="nav-item"><a href="admin_products.php">Products</a></li>
                <li class="nav-item"><a href="admin_stocks.php">Stocks</a></li>
            </ul>

            <div class="user-info">
                <span>Maria Santos</span>
                <div class="avatar">MS</div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Customer Management</h1>
            <p class="page-subtitle">Manage customer accounts and information</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $total_customers; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
        </div>

        <!-- Customer Table -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="card-title">All Customers</h3>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>#<?php echo str_pad($customer['customer_id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td class="customer-info">
                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                <small><?php echo htmlspecialchars($customer['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($customer['username'] ?? 'Not set'); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($customer['address']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin_customer_edit.php?id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Customer</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_customer">
                <input type="hidden" name="customer_id" id="edit_customer_id">

                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" id="edit_first_name" required>
                </div>

                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" id="edit_last_name" required>
                </div>

                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" id="edit_username">
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>

                <div class="form-group">
                    <label>Contact Number:</label>
                    <input type="text" name="contact_number" id="edit_contact_number" required>
                </div>

                <div class="form-group">
                    <label>Address:</label>
                    <input type="text" name="address" id="edit_address" required>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
        </div>
    </div>



    <script>
        // Edit Customer Modal
        function editCustomer(customer) {
            document.getElementById('edit_customer_id').value = customer.customer_id;
            document.getElementById('edit_first_name').value = customer.first_name;
            document.getElementById('edit_last_name').value = customer.last_name;
            document.getElementById('edit_username').value = customer.username || '';
            document.getElementById('edit_email').value = customer.email;
            document.getElementById('edit_contact_number').value = customer.contact_number;
            document.getElementById('edit_address').value = customer.address;

            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');

            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }

        // Navigation functionality
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>

</html>
