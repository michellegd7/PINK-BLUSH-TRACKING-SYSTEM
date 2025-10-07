<?php
include 'database.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $first_name = $_POST['first_name'];
  $last_name = $_POST['last_name'];
  $username = $_POST['username'];
  $email = $_POST['email'];
  $contact_number = $_POST['contact_number'];
  $address = $_POST['address'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match!');</script>";
  } else {
    $sql = "INSERT INTO customer (first_name, last_name, username, password, contact_number, address) 
                VALUES ('$first_name', '$last_name', '$username', '$password', '$contact_number', '$address')";

    if ($conn->query($sql) === TRUE) {
      echo "<script>alert('Registration successful!'); window.location='login.php';</script>";
    } else {
      echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link rel="stylesheet" href="one.css" />
</head>

<body>
  <div class="container">
    <div class="back-button">
      <a href="index.php">Back to Home</a>
    </div>

    <div class="login-panel">
      <form action="register.php" method="POST" class="login-form">
        <h2>Register</h2>
        <p>Fill in your details below</p>

        <!-- Layer 1 -->
        <input type="text" name="first_name" placeholder="First Name" required />
        <input type="text" name="last_name" placeholder="Last Name" required />
        <input type="text" name="username" placeholder="Username" required />
        <input type="email" name="email" placeholder="Email" required />

        <!-- Layer 2 -->
        <input type="tel" name="contact_number" placeholder="Cellphone Number" required />
        <input type="text" name="address" placeholder="Address" required />

        <div class="password-field">
          <input type="password" id="password" name="password" placeholder="Password" required />
          <span class="toggle-eye" onclick="togglePassword('password', this)">👁</span>
        </div>

        <div class="password-field">
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required />
          <span class="toggle-eye" onclick="togglePassword('confirm_password', this)">👁</span>
        </div>

        <button type="submit" class="login-btn">Register</button>
      </form>
    </div>

    <div class="image-panel">
      <img src="pictures/pb.jpg" alt="Tailoring tools" />
    </div>
  </div>

  <script>
    function togglePassword(fieldId, eyeIcon) {
      const input = document.getElementById(fieldId);
      if (input.type === "password") {
        input.type = "text";
        eyeIcon.style.color = "#000";
      } else {
        input.type = "password";
        eyeIcon.style.color = "#666";
      }
    }
  </script>
</body>

</html>