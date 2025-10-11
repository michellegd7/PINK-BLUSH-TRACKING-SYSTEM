<?php
session_start();
include 'database.php';

$error = '';
$success = '';
$show_register = isset($_GET['register']) ? true : false;

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $staff = $result->fetch_assoc();

            if (password_verify($password, $staff['password'])) {
                $_SESSION['role'] = 'staff';
                $_SESSION['staff_id'] = $staff['staff_id'];
                $_SESSION['user'] = $staff['full_name'];
                $_SESSION['username'] = $staff['username'];
                $_SESSION['staff_role'] = $staff['role']; // tailor or master_cutter

                if ($staff['role'] === 'tailor') {
                    header('Location: staff_taylor_dashboard.php');
                    exit();
                } elseif ($staff['role'] === 'master cutter') {
                    header('Location: staff_mastercutter_dashboard.php');
                    exit();
                } else {
                    // Show error if role is unexpected
                    die('Error: Unknown staff role "' . htmlspecialchars($staff['role']) . '". Please contact admin.');
                }

                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $contact_number = trim($_POST['contact_number']);

    // Validation
    if (empty($full_name) || empty($username) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Check if email already exists
            if (!empty($email)) {
                $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error = 'Email already exists';
                }
            }

            if (empty($error)) {
                // Hash password and insert new staff
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO staff (full_name, username, email, password, role, contact_number) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $full_name, $username, $email, $hashed_password, $role, $contact_number);

                if ($stmt->execute()) {
                    $success = 'Account created successfully! You can now login.';
                    $show_register = false;
                } else {
                    $error = 'Error creating account. Please try again.';
                }
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Pink Blush Tailoring</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .login-body {
            padding: 30px;
        }

        .tab-container {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #718096;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .form-content {
            display: none;
        }

        .form-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 500;
            font-size: 14px;
        }

        .required {
            color: #e53e3e;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #fc8181;
        }

        .alert-success {
            background: #c6f6d5;
            color: #2f855a;
            border: 1px solid #68d391;
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #718096;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .role-selection {
            display: flex;
            gap: 10px;
        }

        .role-option {
            flex: 1;
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-label {
            display: block;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .role-option input[type="radio"]:checked+.role-label {
            border-color: #667eea;
            background: #f7faff;
        }

        .role-label:hover {
            border-color: #cbd5e0;
        }

        .role-icon {
            font-size: 22px;
            margin-bottom: 4px;
        }

        .role-name {
            font-weight: 500;
            color: #2d3748;
            font-size: 14px;
        }

        .password-hint {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>✂️ Pink Blush Tailoring</h1>
            <p>Staff Portal</p>
        </div>

        <div class="login-body">
            <div class="tab-container">
                <div class="tab <?php echo !$show_register ? 'active' : ''; ?>" onclick="showTab('login')">Login</div>
                <div class="tab <?php echo $show_register ? 'active' : ''; ?>" onclick="showTab('register')">Register</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="form-content <?php echo !$show_register ? 'active' : ''; ?>" id="loginForm">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="login_username">Username <span class="required">*</span></label>
                        <input type="text" id="login_username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="login_password">Password <span class="required">*</span></label>
                        <input type="password" id="login_password" name="password" required>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary">Login</button>

                    <div class="form-footer">
                        Don't have an account? <a href="#" onclick="showTab('register'); return false;">Register here</a>
                    </div>
                </form>
            </div>

            <!-- Register Form -->
            <div class="form-content <?php echo $show_register ? 'active' : ''; ?>" id="registerForm">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_username">Username <span class="required">*</span></label>
                        <input type="text" id="reg_username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" placeholder="09123456789">
                    </div>

                    <div class="form-group">
                        <label>Select Role <span class="required">*</span></label>
                        <div class="role-selection">
                            <div class="role-option">
                                <input type="radio" id="role_tailor" name="role" value="tailor" required checked>
                                <label for="role_tailor" class="role-label">
                                    <div class="role-icon">🧵</div>
                                    <div class="role-name">Tailor</div>
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" id="role_cutter" name="role" value="master cutter" required>
                                <label for="role_cutter" class="role-label">
                                    <div class="role-icon">✂️</div>
                                    <div class="role-name">Master Cutter</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password <span class="required">*</span></label>
                        <input type="password" id="reg_password" name="password" required>
                        <div class="password-hint">Minimum 6 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary">Create Account</button>

                    <div class="form-footer">
                        Already have an account? <a href="#" onclick="showTab('login'); return false;">Login here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const tabs = document.querySelectorAll('.tab');

            if (tab === 'login') {
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                registerForm.classList.add('active');
                loginForm.classList.remove('active');
                tabs[1].classList.add('active');
                tabs[0].classList.remove('active');
            }
        }
    </script>
</body>

</html>
