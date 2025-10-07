<?php
include 'database.php';
$query = "SELECT * FROM uniform_options";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Uniforms</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', sans-serif;
    }

    body {
      display: flex;
      background-color: #f4f6f9;
      min-height: 100vh;
    }

    .sidebar {
      width: 250px;
      background: #2c2f48;
      color: #fff;
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      padding: 20px;
    }

    .sidebar h2 {
      margin-bottom: 30px;
      text-align: center;
      font-size: 24px;
      color: #ff00cc;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      margin: 20px 0;
    }

    .sidebar ul li a {
      color: #fff;
      text-decoration: none;
      display: block;
      padding: 10px;
      border-radius: 6px;
      transition: background 0.3s ease;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #ff00cc;
    }

    .main-content {
      margin-left: 250px;
      padding: 20px;
      flex: 1;
    }

    .header {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .header h1 {
      font-size: 22px;
      color: #333;
    }

    .header .profile {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .uniform-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .uniform-card {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .uniform-card:hover {
      transform: translateY(-5px);
    }

    .uniform-card h3 {
      margin-bottom: 10px;
      color: #ff00cc;
      font-size: 20px;
    }

    .uniform-card p {
      margin: 5px 0;
      font-size: 16px;
      color: #333;
    }

    .uniform-card .price {
      font-weight: bold;
      color: #2a9d8f;
    }

    .uniform-card .customizable {
      font-style: italic;
      color: #f4a261;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 200px;
      }

      .main-content {
        margin-left: 200px;
      }
    }

    @media (max-width: 600px) {
      body {
        flex-direction: column;
      }

      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
      }

      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>My Dashboard</h2>
    <ul>
      <li><a href="admin-dashboard.php">Overview</a></li>
      <li><a href="admin-uniforms.php" class="active">Uniforms</a></li>
      <li><a href="#">Profile</a></li>
      <li><a href="#">Settings</a></li>
      <li><a href="#">Logout</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <div class="header">
      <h1>Uniform Inventory</h1>
      <div class="profile">
        <span>Michelle</span>
      </div>
    </div>

    <!-- Uniform Cards -->
    <div class="uniform-grid">
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="uniform-card">
          <h3><?= htmlspecialchars($row['uniform_name']) ?></h3>
          <p>Type: <?= htmlspecialchars($row['uniform_type']) ?></p>
          <p>Sizes: <?= htmlspecialchars($row['available_sizes']) ?></p>
          <p class="price">Price: ₱<?= number_format($row['price'], 2) ?></p>
          <p class="customizable">
            <?= $row['customizable'] ? 'Customizable: Yes' : 'Customizable: No' ?>
          </p>
          <div style="margin-top: 10px;">
            <a href="edit-uniform.php?id=<?= $row['id'] ?>" style="color: #2a9d8f; text-decoration: none; margin-right: 10px;">✏️ Edit</a>
            <a href="delete-uniform.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this uniform?');" style="color: #e63946; text-decoration: none;">🗑️ Delete</a>
          </div>
        </div>

      <?php endwhile; ?>
    </div>
  </div>
</body>

</html>