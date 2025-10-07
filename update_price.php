<?php
include 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'];
    $price = $_POST['price'];

    $stmt = $conn->prepare("UPDATE uniform_options SET price = ? WHERE id = ?");
    $stmt->bind_param("di", $price, $id);

    if ($stmt->execute()) {
        header("Location: admin_products.php"); // or wherever your product list is
        exit();
    } else {
        echo "❌ Error updating price: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
