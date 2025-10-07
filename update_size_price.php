<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $price_xs = $_POST['price_xs'];
    $price_s = $_POST['price_s'];
    $price_m = $_POST['price_m'];
    $price_l = $_POST['price_l'];
    $price_xl = $_POST['price_xl'];

    // Prepare the UPDATE statement
    $stmt = $conn->prepare("UPDATE uniform_options SET 
        price_xs = ?, 
        price_s = ?, 
        price_m = ?, 
        price_l = ?, 
        price_xl = ? 
        WHERE id = ?");

    $stmt->bind_param("dddddi", $price_xs, $price_s, $price_m, $price_l, $price_xl, $id);

    if ($stmt->execute()) {
        // Success - redirect back to products page
        header("Location: admin_products.php?success=1");
        exit();
    } else {
        // Error - redirect back with error message
        header("Location: admin_products.php?error=1");
        exit();
    }

    $stmt->close();
}

$conn->close();
