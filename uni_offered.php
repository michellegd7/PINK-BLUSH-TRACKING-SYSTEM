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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Uniform Offered</title>
  <link rel="stylesheet" href="styles.css" />
</head>

<body>

  <header>
    <div class="navbar">
      <div class="logo">Pink Blush.</div>
      <ul>
        <li><a href="customer-dashboard.php">Home</a></li>
        <li><a href="#">Categories</a></li>
      </ul>
    </div>
  </header>

  <div class="search-section">
  </div>

  <div class="menu-section">
    <h2>School Uniform</h2>
    <div class="school-uniform">
      <?php
      $query = "SELECT * FROM uniform_schools";
      $result = mysqli_query($conn, $query);

      if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
          echo '<div class="school-uni">';
          echo '<img src="' . htmlspecialchars($row['image_path']) . '" alt="' . htmlspecialchars($row['name']) . '">';
          echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
          echo '<p>' . htmlspecialchars($row['description']) . '</p>';

          echo '<form method="GET" action="uniform-details.php">';
          echo '<input type="hidden" name="school_id" value="' . $row['id'] . '">';
          echo '<button type="submit">View Uniform Options</button>';
          echo '</form>';

          echo '</div>';
        }
      } else {
        echo '<p>No schools found.</p>';
      }
      ?>
    </div>
  </div>

</body>
<script>
  document.querySelector(".search-box button").addEventListener("click", function() {
    const query = document.querySelector(".search-box input").value.toLowerCase();
    const items = document.querySelectorAll(".school-uni");

    items.forEach(item => {
      const text = item.textContent.toLowerCase();
      item.style.display = text.includes(query) ? "block" : "none";
    });
  });
</script>

</html>