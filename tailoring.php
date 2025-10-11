<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pink Blush - Tailoring Sewing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            padding-bottom: 80px;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            background: #ff6b6b;
            border-radius: 15px;
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .search-box {
            margin: 20px auto;
            max-width: 500px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 15px 25px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 25px;
            top: 50%;
            color: #888;
            font-size: 1.2rem;
        }

        .menu-categories {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 30px;
        }

        .category-btn {
            padding: 14px 28px;
            background-color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .category-btn.active {
            background: #ff6b6b;
            color: white;
        }

        .cart-btn {
            padding: 8px 16px;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            background: #ff6b6b;
        }

        .cart-btn[data-added="true"] {
            background: #ff6b6b;
        }

        .menu-section {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .menu-section h2 {
            color: #ff6b6b;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ffeaa7;
            font-size: 1.8rem;
        }

        .menu-section p {
            color: #666;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 25px;
        }

        .menu-item {
            border: 1px solid #f1f2f6;
            border-radius: 12px;
            padding: 20px;
            background: #fafafa;
        }

        .menu-item h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .price {
            color: #ee5a24;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 12px 0;
        }

        .discount {
            color: #888;
            text-decoration: line-through;
            margin-left: 10px;
            font-size: 1rem;
        }

        .add-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            cursor: pointer;
            margin-top: 15px;
            font-weight: 600;
            width: 100%;
            font-size: 1.1rem;
        }

        .minus-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            cursor: pointer;
            margin-top: 15px;
            font-weight: 600;
            width: 100%;
            font-size: 1.1rem;
        }

        .order-summary {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            padding: 20px 40px;
            box-shadow: 0 -3px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

        .order-summary h3 {
            color: #333;
            font-size: 1.3rem;
        }

        .total {
            font-weight: bold;
            color: #ee5a24;
            font-size: 1.4rem;
        }

        .continue-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 35px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(238, 90, 36, 0.3);
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #eee;
        }

        .divider::after {
            content: "";
            position: absolute;
            right: 0;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #eee;
        }

        @media (max-width: 768px) {
            .menu-items {
                grid-template-columns: 1fr;
            }

            .order-summary {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
            }

            .category-btn {
                padding: 12px 20px;
                font-size: 1rem;
            }

            h1 {
                font-size: 2.2rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Available Uniform</h1>
            <div class="search-box">
                <input type="text" placeholder="Search Uniform...">
                <i class="fas fa-search"></i>
            </div>
        </header>

        <div class="menu-categories">
            <button class="category-btn active"> Student Uniform</button>
            <button class="category-btn active">Teachers Uniform</button>
        </div>

        <div class="menu-section" id="student-uniform">
            <h2>Student Uniform</h2>
            <p>Keeps students looking smart, confident, and united as one school family.</p>
            <div class="menu-items">
                <div class="menu-item">
                    <h3>St. Michael's College - Higher Education | UPPER UNIFORM</h3>
                    <div class="price">600.00</div>
                    <button class="add-btn">ADD +</button>
                    <button class="minus-btn">MINUS –</button>
                </div>
                <div class="menu-item">
                    <h3>St. Michael's College - Higher Education | LOWER UNIFORM</h3>
                    <div class="price">500.00</div>
                    <button class="add-btn">ADD +</button>
                    <button class="minus-btn">MINUS –</button>

                </div>
                <div class="menu-item">
                    <h3>ST.PETERS COLLEGE - UPPER UNIFORM</h3>
                    <div class="price">300.00</div>
                    <button class="add-btn">ADD +</button>
                    <button class="minus-btn">MINUS –</button>
                </div>
                <div class="menu-item">
                    <h3>ST.PETERS COLLEGE - LOWER UNIFORM</h3>
                    <div class="price">500.00</div>
                    <button class="add-btn">ADD +</button>
                    <button class="minus-btn">MINUS –</button>
                </div>
            </div>
        </div>

        <div class="menu-section" id="teachers-uniform">
            <h2>Teachers Uniform</h2>
            <p>Helps students learn and understand lessons.</p>
            <div class="menu-items">
                <div class="menu-item">
                    <h3>St. Michaels College</h3>
                    <div class="price">700.00</div>
                    <button class="add-btn">ADD +</button>
                    <button class="minus-btn">MINUS –</button>
                </div>
            </div>
        </div>

    </div>

    <div class="order-summary">
        <h3>Your Orders (2)</h3>
        <div class="total">Total: </div>
        <button class="continue-btn" id="continue-btn">CONTINUE</button>
    </div>

    <script>
        document.querySelectorAll('.cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const priceText = this.parentElement.querySelector('.price').textContent.trim();
                const priceValue = parseFloat(priceText.replace(/[^\d.]/g, ''));

                const orderCount = document.querySelector('.order-summary h3');
                const totalElement = document.querySelector('.total');

                let currentCount = parseInt(orderCount.textContent.match(/\d+/)[0]);
                let currentTotal = parseFloat(totalElement.textContent.replace(/[^\d.]/g, '')) || 0;

                const isAdded = this.getAttribute('data-added') === "true";

                if (!isAdded) {
                    // Add item
                    currentCount += 1;
                    currentTotal += priceValue;
                    this.innerHTML = "MINUS –";
                    this.style.background = "linear-gradient(135deg, #f44336 0%, #c62828 100%)";
                    this.setAttribute("data-added", "true");
                } else {
                    // Remove item
                    currentCount = Math.max(currentCount - 1, 0);
                    currentTotal = Math.max(currentTotal - priceValue, 0);
                    this.innerHTML = "ADD +";
                    this.style.background = "linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%)";
                    this.setAttribute("data-added", "false");
                }

                orderCount.textContent = `Your Orders (${currentCount})`;
                totalElement.textContent = `Total: ₱${currentTotal.toFixed(2)}`;
            });
        });

        document.querySelectorAll('.add-btn').forEach(button => {
            button.addEventListener('click', function() {

                this.innerHTML = "ADDED ✓";

                setTimeout(() => {
                    this.innerHTML = "ADD +";
                }, 1500);

                const priceText = this.parentElement.querySelector('.price').textContent.trim();
                const priceValue = parseFloat(priceText.replace(/[^\d.]/g, ''));
                // Update order count
                const orderCount = document.querySelector('.order-summary h3');
                const currentCount = parseInt(orderCount.textContent.match(/\d+/)[0]);
                orderCount.textContent = `Your Orders (${currentCount + 1})`;

                // Update total
                const totalElement = document.querySelector('.total');
                const currentTotal = parseFloat(totalElement.textContent.replace(/[^\d.]/g, '')) || 0;
                const newTotal = currentTotal + priceValue;
                totalElement.textContent = `Total: ₱${newTotal.toFixed(2)}`;
            });
        });

        document.querySelectorAll('.minus-btn').forEach(button => {
            button.addEventListener('click', function() {

                const priceText = this.parentElement.querySelector('.price').textContent.trim();
                const priceValue = parseFloat(priceText.replace(/[^\d.]/g, ''));

                // Update order count
                const orderCount = document.querySelector('.order-summary h3');
                const currentCount = parseInt(orderCount.textContent.match(/\d+/)[0]);
                const newCount = Math.max(currentCount - 1, 0);
                orderCount.textContent = `Your Orders (${newCount})`;

                // Update total
                const totalElement = document.querySelector('.total');
                const currentTotal = parseFloat(totalElement.textContent.replace(/[^\d.]/g, '')) || 0;
                const newTotal = Math.max(currentTotal - priceValue, 0);
                totalElement.textContent = `Total: ₱${newTotal.toFixed(2)}`;

                this.innerHTML = "REMOVED ✓";

                setTimeout(() => {
                    this.innerHTML = "MINUS –";
                }, 1500);
            });
        });
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');

                // Scroll to the correct section
                const sectionId = this.textContent.includes("Teacher") ? "teachers-uniform" : "student-uniform";
                const targetSection = document.getElementById(sectionId);
                if (targetSection) {
                    targetSection.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });


        document.getElementById('continue-btn').addEventListener('click', function() {
            // Check PHP session
            const isLoggedIn = <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? 'true' : 'false'; ?>;

            if (!isLoggedIn) {
                // Redirect to login if not logged in
                window.location.href = "login.php";
            } else {
                // Proceed with order
                this.innerHTML = "PROCESSING...";

                setTimeout(() => {
                    alert("Order placed successfully!");
                    this.innerHTML = "CONTINUE";
                }, 1500);
            }
        });
    </script>

</body>

</html>
