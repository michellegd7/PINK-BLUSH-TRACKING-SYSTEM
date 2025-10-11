<?php
include 'database.php';
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pink Blush Tailoring</title>
  <link rel="stylesheet" href="styles.css" />
</head>

<body class="index">
  <header>
    <nav class="navbar">
      <div class="logo">Pink Blush.</div>
      <ul class="nav-links">
        <li><a href="customer-dashboard.php" class="active">Home</a></li>
        <li><a href="#feat">Services</a></li>
        <li><a href="#contact">About</a></li>
      </ul>
      <div>
        <a href="profile.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
        <a href="logout.php">Logout</a>
      </div>
    </nav>
  </header>

  <section class="hero">
    <div class="hero-text">
      <h1>We make clothes that suit you</h1>
      <p>
        To ensure the creation of well-fitting and stylish garments.
      </p>
      <a href="uni_offered.php" class="services">SCHOOL UNIFORM OFFERS "ORDER HERE"</a>
    </div>
  </section>


  <section id="feat" class="features">
    <div class="feature">
      <a href="tailoring-sewing.php">
        <img src="pictures/tailoring_sewing.jpg" alt="Tailor Sewing">
      </a>
      <h3>Tailor Sewing</h3>
    </div>
    <div class="feature">
      <img src="pictures/tailoring_measurement1.jpg" alt="Measurement" />
      <h3>Measurement</h3>
    </div>
    <div class="feature">
      <img src="pictures/ready_made.jpg" alt="Ready-made" />
      <h3>Ready-made</h3>
    </div>
  </section>

  <section id="contact" class="contact-section">
    <div class="contact-container">
      <h2>Let's Stay in Touch</h2>
      <p class="tagline">We’d love to hear from you—whether it’s a fitting inquiry or just a hello!</p>

      <div class="contact-details">
        <p><strong>📞 Phone:</strong> +63 912 345 6789</p>
        <p><strong>📍 Address:</strong> Badelles St, Iligan City, Lanao del Norte, Philippines</p>
        <p><strong>✉️ Email:</strong> pinkblushtailoring@gmail.com</p>
      </div>
    </div>
  </section>
</body>
<script>
  const sections = document.querySelectorAll("section");
  const navLinks = document.querySelectorAll(".nav-links a");

  window.addEventListener("scroll", () => {
    let current = "";
    sections.forEach((section) => {
      const sectionTop = section.offsetTop - 60;
      const sectionHeight = section.clientHeight;
      if (pageYOffset >= sectionTop && pageYOffset < sectionTop + sectionHeight) {
        current = section.getAttribute("id");
      }
    });

    navLinks.forEach((link) => {
      link.classList.remove("active");
      if (link.getAttribute("href").includes(current)) {
        link.classList.add("active");
      }
    });
  });
</script>

</html>
