<?php
include 'database.php';
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'];
  $description = $_POST['description'];
  $image = $_FILES['image']['name'];
  $target = "pictures/" . basename($image);

  if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    $sql = "INSERT INTO uniform_schools (name, description, image_path) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $name, $description, $target);
    mysqli_stmt_execute($stmt);
    header("Location: uni_offered.php");
    exit();
  } else {
    $error = "Image upload failed.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add School</title>
  <style>
    body {
      background: linear-gradient(to right, #ffafbd, #ffc3a0);
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }

    .form-container {
      background-color: #fff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      width: 500px;
      max-width: 90%;
    }

    h2 {
      text-align: center;
      color: #e63946;
      margin-bottom: 30px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
      color: #333;
    }

    input[type="text"],
    textarea,
    input[type="file"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }

    textarea {
      resize: vertical;
      height: 100px;
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #e63946;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #d62828;
    }

    .error {
      color: #d62828;
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>

<body>
  <div class="form-container">
    <h2>Add New School</h2>
    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    <form action="add_school.php" method="POST" enctype="multipart/form-data">
      <label for="name">School Name</label>
      <input type="text" name="name" id="name" required />

      <label for="description">Description</label>
      <textarea name="description" id="description" required></textarea>

      <label for="image">Upload Image</label>
      <input type="file" name="image" id="image" accept="image/*" required />

      <button type="submit">Add School</button>
    </form>
  </div>
</body>

</html>
