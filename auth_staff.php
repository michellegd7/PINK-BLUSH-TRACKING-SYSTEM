<?php
include 'database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // LOGIN
    if ($action === 'login') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];

        // Query staff table
        $query = "SELECT * FROM staff WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Verify password
            if (password_verify($password, $row['password'])) {
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_id'] = $row['staff_id'];
                $_SESSION['staff_username'] = $row['username'];
                $_SESSION['staff_name'] = $row['full_name'];
                echo 'success';
            } else {
                echo 'invalid';
            }
        } else {
            echo 'invalid';
        }
        mysqli_stmt_close($stmt);
    }

    // REGISTER
    if ($action === 'register') {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check if username exists
        $checkQuery = "SELECT username FROM staff WHERE username = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "s", $username);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            echo 'username_exists';
            mysqli_stmt_close($checkStmt);
            exit();
        }
        mysqli_stmt_close($checkStmt);

        // Insert new staff
        $insertQuery = "INSERT INTO staff (full_name, username, password) VALUES (?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, "sss", $full_name, $username, $password);

        if (mysqli_stmt_execute($insertStmt)) {
            echo 'registered';
        } else {
            echo 'error';
        }
        mysqli_stmt_close($insertStmt);
    }
}

mysqli_close($conn);
