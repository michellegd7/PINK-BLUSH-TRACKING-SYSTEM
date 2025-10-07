<?php
include 'database.php';
$id = intval($_GET['id']);
$query = "DELETE FROM uniform_options WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
header("Location: admin-uniforms.php");
exit();
