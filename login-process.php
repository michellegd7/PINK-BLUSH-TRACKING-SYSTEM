<?php
session_start();
include("connect.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM customer WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($row['password'] === $pass) {
            $_SESSION['username'] = $row['username'];
            header("Location: customer-dashboard.php");
            exit();
        }
    }

    header("Location: login.php?error=1");
    exit();
}
