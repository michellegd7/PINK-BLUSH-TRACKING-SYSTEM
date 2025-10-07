<?php
$servername = "localhost";
$db_username = "root";   // your MySQL username
$db_password = "";       // your MySQL password
$dbname = "pink_blush_tailoring";

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
