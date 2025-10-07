<?php
include 'database.php'; // your DB connection file

// Get POST data
$action = $_POST['action'] ?? '';
$role = $_POST['role'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($action) || empty($role) || empty($username) || empty($password)) {
    echo "error";
    exit();
}

// Determine table based on role
$table = ($role === 'admin') ? 'admin' : 'staff';

if ($action === 'register') {
    $fullName = $_POST['full_name'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate passwords match
    if ($password !== $confirmPassword) {
        echo "password_mismatch";
        exit();
    }

    // Validate password length
    if (strlen($password) < 6) {
        echo "password_short";
        exit();
    }

    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT username FROM $table WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo "username_exists";
        exit();
    }
    $checkStmt->close();

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user based on role
    if ($role === 'admin') {
        $stmt = $conn->prepare("INSERT INTO admin (full_name, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fullName, $username, $hashedPassword);
    } else {
        // For staff, you might need to adjust field names based on your table structure
        $stmt = $conn->prepare("INSERT INTO staff (full_name, username, password, role) VALUES (?, ?, ?, 'staff')");
        $stmt->bind_param("sss", $fullName, $username, $hashedPassword);
    }

    if ($stmt->execute()) {
        echo "registered";
    } else {
        // Log the error for debugging
        error_log("Registration error: " . $stmt->error);
        echo "error";
    }
    $stmt->close();
    exit();
}

if ($action === 'login') {
    $stmt = $conn->prepare("SELECT * FROM $table WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['role'] = $role;
        $_SESSION['user'] = $user['full_name'];
        $_SESSION['username'] = $user['username'];
        echo "success";
    } else {
        echo "invalid";
    }
    $stmt->close();
    exit();
}

$conn->close();
