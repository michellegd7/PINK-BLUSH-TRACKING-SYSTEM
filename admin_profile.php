<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

include 'database.php';

$admin_name = $_SESSION['user'] ?? 'Admin';
$admin_username = $_SESSION['username'] ?? '';
$admin_initial = strtoupper(substr($admin_name, 0, 1));

// Get current admin data
$stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $new_username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate current password
    if (!password_verify($current_password, $admin_data['password'])) {
        $message = 'Current password is incorrect!';
        $message_type = 'error';
    } else {
        // Check if username is being changed and if it's already taken
        if ($new_username !== $admin_username) {
            $check_stmt = $conn->prepare("SELECT admin_id FROM admin WHERE username = ? AND admin_id != ?");
            $check_stmt->bind_param("si", $new_username, $admin_data['admin_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = 'Username already exists!';
                $message_type = 'error';
                $check_stmt->close();
            } else {
                $check_stmt->close();
            }
        }

        if (empty($message)) {
            // Update profile
            if (!empty($new_password)) {
                // Validate new password
                if ($new_password !== $confirm_password) {
                    $message = 'New passwords do not match!';
                    $message_type = 'error';
                } elseif (strlen($new_password) < 6) {
                    $message = 'New password must be at least 6 characters!';
                    $message_type = 'error';
                } else {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE admin SET full_name = ?, username = ?, password = ? WHERE admin_id = ?");
                    $update_stmt->bind_param("sssi", $full_name, $new_username, $hashed_password, $admin_data['admin_id']);
                }
            } else {
                // Update without changing password
                $update_stmt = $conn->prepare("UPDATE admin SET full_name = ?, username = ? WHERE admin_id = ?");
                $update_stmt->bind_param("ssi", $full_name, $new_username, $admin_data['admin_id']);
            }

            if (empty($message) && isset($update_stmt)) {
                if ($update_stmt->execute()) {
                    // Update session
                    $_SESSION['user'] = $full_name;
                    $_SESSION['username'] = $new_username;

                    $message = 'Profile updated successfully!';
                    $message_type = 'success';

                    // Refresh admin data
                    $admin_name = $full_name;
                    $admin_username = $new_username;
                    $admin_initial = strtoupper(substr($full_name, 0, 1));
                    $admin_data['full_name'] = $full_name;
                    $admin_data['username'] = $new_username;
                } else {
                    $message = 'Failed to update profile!';
                    $message_type = 'error';
                }
                $update_stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
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
            text-decoration: none;
            color: white;
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar-large {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            border: 4px solid white;
        }

        .profile-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            opacity: 0.9;
        }

        .profile-content {
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #fc8181;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: #2d3748;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-note {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 0.25rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 0 1rem;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="logo">
                <span>✂️</span>
                <span>Master Tailor</span>
            </a>

            <div class="user-info">
                <span><?php echo htmlspecialchars($admin_name); ?></span>
                <div class="avatar"><?php echo $admin_initial; ?></div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar-large"><?php echo $admin_initial; ?></div>
                <h2><?php echo htmlspecialchars($admin_name); ?></h2>
                <p>Administrator Account</p>
            </div>

            <div class="profile-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-section">
                        <h3>Account Information</h3>

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin_data['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Change Password</h3>

                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                            <p class="form-note">Required to make any changes</p>
                        </div>

                        <div class="form-group">
                            <label>New Password (Optional)</label>
                            <input type="password" name="new_password" minlength="6">
                            <p class="form-note">Leave blank to keep current password</p>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" minlength="6">
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>