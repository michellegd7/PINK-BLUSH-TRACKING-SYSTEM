<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .role-select {
            margin-bottom: 20px;
        }

        .role-select label,
        label {
            display: block;
            margin-bottom: 8px;
            color: #5a6c7d;
            font-weight: 600;
            font-size: 14px;
        }

        .role-select select,
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .role-select select:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .staff-fields {
            display: none;
        }

        .staff-fields.active {
            display: block;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .form-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .form-link p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .form-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .form-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Create Account</h2>
        <p class="subtitle">Register your account</p>

        <form onsubmit="handleSubmit(event)">
            <div class="role-select">
                <label>Select Role</label>
                <select id="registerRole" onchange="toggleStaffFields()">
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </div>

            <label>Full Name</label>
            <input type="text" id="fullName" placeholder="Enter full name" required>

            <label>Username</label>
            <input type="text" id="username" placeholder="Enter username" required>

            <label>Password</label>
            <input type="password" id="password" placeholder="Enter password" required minlength="6">

            <label>Confirm Password</label>
            <input type="password" id="confirmPassword" placeholder="Confirm password" required minlength="6">

            <div id="staffFields" class="staff-fields">
                <label>Email</label>
                <input type="email" id="email" placeholder="Enter email address">

                <label>Staff Role</label>
                <select id="staffRole">
                    <option value="Tailor">Tailor</option>
                    <option value="masterCutter">Master Cutter</option>
                </select>
            </div>

            <label>Contact Number</label>
            <input type="tel" id="contactNumber" placeholder="Enter contact number" required>

            <button type="submit" class="submit-btn">Register</button>

            <div class="form-link">
                <p>Already have an account? <a href="admin_login.php">Login here</a></p>
            </div>
        </form>
    </div>

    <script>
        function toggleStaffFields() {
            const role = document.getElementById('registerRole').value;
            const staffFields = document.getElementById('staffFields');
            const emailField = document.getElementById('email');

            if (role === 'staff') {
                staffFields.classList.add('active');
                emailField.required = true;
            } else {
                staffFields.classList.remove('active');
                emailField.required = false;
            }
        }

        function handleSubmit(e) {
            e.preventDefault();

            const role = document.getElementById('registerRole').value;
            const fullName = document.getElementById('fullName').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const contactNumber = document.getElementById('contactNumber').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }

            const data = new FormData();
            data.append('action', 'register');
            data.append('role', role);
            data.append('full_name', fullName);
            data.append('username', username);
            data.append('password', password);
            data.append('contact_number', contactNumber);

            if (role === 'staff') {
                const email = document.getElementById('email').value;
                const staffRole = document.getElementById('staffRole').value;
                data.append('email', email);
                data.append('staff_role', staffRole);
            }

            fetch('auth.php', {
                    method: 'POST',
                    body: data
                })
                .then(res => res.text())
                .then(response => {
                    if (response === 'success') {
                        alert('Registration successful! Please login.');
                        window.location.href = role === 'admin' ? 'admin_login.php' : 'staff_login.php';
                    } else if (response === 'username_exists') {
                        alert('Username already exists. Please choose another.');
                    } else {
                        alert('Registration failed. Please try again.');
                    }
                })
                .catch(error => {
                    alert('An error occurred. Please try again.');
                    console.error('Error:', error);
                });
        }
    </script>
</body>

</html>
