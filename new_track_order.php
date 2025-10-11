<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f9f9f9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .form-container {
            max-width: 500px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-cancel {
            background: #ccc;
        }

        .btn-save {
            background: #27ae60;
            color: white;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Add New Order</h2>
        <form action="save_order.php" method="POST">
            <div class="form-group">
                <label>Customer Name</label>
                <input type="text" name="customerName" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phoneNumber" required>
            </div>
            <div class="form-group">
                <label>Product Type</label>
                <select name="productType" required>
                    <option value="Suit">Suit</option>
                    <option value="Shirt">Shirt</option>
                    <option value="Pants">Pants</option>
                    <option value="Dress">Dress</option>
                    <option value="Blazer">Blazer</option>
                    <option value="Uniform">Uniform</option>
                </select>
            </div>
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="dueDate" required>
            </div>
            <div class="form-group">
                <label>Rush Order</label>
                <select name="rushOrder">
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes / Measurements</label>
                <textarea name="orderNotes" placeholder="Special instructions, measurements, fabric details..."></textarea>
            </div>
            <div class="form-actions">
                <a href="track_order.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">Save Order</button>
            </div>
        </form>
    </div>
</body>

</html>
