<?php
include 'database.php';
$id = intval($_GET['id']);
$query = "SELECT * FROM uniform_options WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$uniform = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['uniform_name'];
  $type = $_POST['uniform_type'];
  $sizes = $_POST['available_sizes'];
  $customizable = isset($_POST['customizable']) ? 1 : 0;
  $price = floatval($_POST['price']);

  $update = "UPDATE uniform_options SET uniform_name=?, uniform_type=?, available_sizes=?, customizable=?, price=? WHERE id=?";
  $stmt = mysqli_prepare($conn, $update);
  mysqli_stmt_bind_param($stmt, "sssidi", $name, $type, $sizes, $customizable, $price, $id);
  mysqli_stmt_execute($stmt);

  header("Location: admin-uniforms.php");
  exit();
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>Edit Uniform</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      padding: 40px;
    }

    .form-box {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    h2 {
      color: #ff00cc;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }

    input[type="text"],
    input[type="number"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    input[type="checkbox"] {
      margin-top: 10px;
    }

    button {
      margin-top: 20px;
      padding: 12px;
      width: 100%;
      background-color: #ff00cc;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }

    button:hover {
      background-color: #e63946;
    }
  </style>
</head>

<body>
  <div class="form-box">
    <h2>Edit Uniform</h2>
    <form method="POST">
      <label>Uniform Name</label>
      <input type="text" name="uniform_name" value="<?= htmlspecialchars($uniform['uniform_name']) ?>" required />

      <label>Uniform Type</label>
      <input type="text" name="uniform_type" value="<?= htmlspecialchars($uniform['uniform_type']) ?>" />

      <label>Available Sizes</label>
      <input type="text" name="available_sizes" value="<?= htmlspecialchars($uniform['available_sizes']) ?>" required />

      <label>Price</label>
      <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($uniform['price']) ?>" required />

      <label>
        <input type="checkbox" name="customizable" <?= $uniform['customizable'] ? 'checked' : '' ?> />
        Customizable
      </label>

      <button type="submit">Update Uniform</button>
    </form>
  </div>
</body>

</html>
