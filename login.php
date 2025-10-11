<?php
include 'database.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Information</title>
  <link rel="stylesheet" href="one.css" />
  <style>
    .password-wrapper {
      position: relative;
      width: 100%;
    }

    .password-wrapper input {
      width: 100%;
      padding-right: 45px;
    }

    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      user-select: none;
      font-size: 14px;
      color: #666;
    }

    .toggle-password:hover {
      color: #000;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="back-button">
      <a href="index.php">Back to Home</a>
    </div>
    <div class="login-panel">
      <?php if (isset($_GET['error'])): ?>
        <div class="error-message">Invalid username or password.</div>
      <?php endif; ?>

      <form action="login-process.php" method="POST" class="login-form">
        <h2>Hello!</h2>
        <p>Sign Up to Get Started</p>

        <input type="text" name="username" placeholder="Username" required autocomplete="username" />

        <div class="password-wrapper">
          <input type="password" id="password" name="password" placeholder="Password" required autocomplete="current-password" />
          <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>

        <button type="submit" class="login-btn">Login</button>

        <a href="#" class="forgot">Forgot Password</a>
        <p class="no-account">Don't have an account yet?</p>
        <button type="button" class="register-btn" onclick="window.location.href='register.php'">Register</button>
      </form>

    </div>
    <div class="image-panel">
      <img src="pictures/pb.jpg" alt="Tailoring tools" />
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.querySelector('.toggle-password');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
      } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
      }
    }
  </script>
</body>

</html>
