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

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $role = $_POST['role'];
    $inputUsername = trim($_POST['username']);
    $inputPassword = trim($_POST['password']);

    if ($role === 'admin') {
        // Query admin table
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $inputUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($inputPassword, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['admin_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = 'admin';
            $_SESSION['contact_number'] = $user['contact_number'];

            echo 'success';
        } else {
            echo 'error';
        }
    } elseif ($role === 'staff') {
        // Query staff table
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = :username AND status = 'Active' LIMIT 1");
        $stmt->execute(['username' => $inputUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($inputPassword, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['staff_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['contact_number'] = $user['contact_number'];
            $_SESSION['status'] = $user['status'];

            echo 'success';
        } else {
            echo 'error';
        }
    } else {
        echo 'error';
    }
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $role = $_POST['role'];
    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $contactNumber = trim($_POST['contact_number']);

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        if ($role === 'admin') {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE username = :username");
            $stmt->execute(['username' => $username]);

            if ($stmt->rowCount() > 0) {
                echo 'username_exists';
                exit;
            }

            // Insert new admin
            $stmt = $pdo->prepare("INSERT INTO admin (full_name, username, password, contact_number) VALUES (:full_name, :username, :password, :contact_number)");
            $stmt->execute([
                'full_name' => $fullName,
                'username' => $username,
                'password' => $hashedPassword,
                'contact_number' => $contactNumber
            ]);

            echo 'success';
        } elseif ($role === 'staff') {
            $email = trim($_POST['email']);
            $staffRole = $_POST['staff_role'];

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE username = :username");
            $stmt->execute(['username' => $username]);

            if ($stmt->rowCount() > 0) {
                echo 'username_exists';
                exit;
            }

            // Insert new staff
            $stmt = $pdo->prepare("INSERT INTO staff (full_name, username, password, role, email, contact_number, status, created_at) VALUES (:full_name, :username, :password, :role, :email, :contact_number, 'Active', NOW())");
            $stmt->execute([
                'full_name' => $fullName,
                'username' => $username,
                'password' => $hashedPassword,
                'role' => $staffRole,
                'email' => $email,
                'contact_number' => $contactNumber
            ]);

            echo 'success';
        } else {
            echo 'error';
        }
    } catch (PDOException $e) {
        echo 'error';
    }
    exit;
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}
