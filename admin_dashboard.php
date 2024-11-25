<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'login_register');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch products from the database
$productSql = "SELECT * FROM products";
$productResult = $conn->query($productSql);

// Fetch orders from the database
$orderSql = "SELECT * FROM orders"; // Make sure the orders table exists in your database
$orderResult = $conn->query($orderSql);

// Handle form submission for adding a product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['product_image'])) {
    $productName = $_POST['product_name'];
    $productPrice = $_POST['product_price'];
    $productImage = $_FILES['product_image'];

    // Handle image upload
    $imageName = time() . '_' . basename($productImage['name']);
    $targetDir = __DIR__ . '/uploads/';
    $targetFile = $targetDir . $imageName;

    if (move_uploaded_file($productImage['tmp_name'], $targetFile)) {
        // Insert new product into the database
        $stmt = $conn->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $productName, $productPrice, $imageName);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_dashboard.php"); // Redirect to avoid resubmission
        exit();
    } else {
        echo "Error uploading the image.";
    }
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $productId = $_POST['product_id'];
    // First, get the product image name to delete the image file
    $getImageSql = "SELECT image FROM products WHERE id = ?";
    $stmt = $conn->prepare($getImageSql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->bind_result($imageName);
    $stmt->fetch();
    $stmt->close();
    
    // Delete the product from the database
    $deleteSql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->close();

    // Delete the product image file from the server
    if (file_exists(__DIR__ . '/uploads/' . $imageName)) {
        unlink(__DIR__ . '/uploads/' . $imageName);
    }
    
    header("Location: admin_dashboard.php"); // Redirect to refresh the page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            transition: margin-left 0.3s ease;
        }

        /* Topbar Styling */
        .topbar {
            position: fixed;
            top: 0;
            width: 100%;
            height: 60px;
            background-color: #333;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
        }

        .topbar h1 {
            font-size: 20px;
            margin-left: 10px;
        }

        .toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            width: 50px;
            height: 40px;
        }

        .bar {
            display: block;
            width: 100%;
            height: 4px;
            background-color: white;
            border-radius: 2px;
            transition: 0.3s;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 200px;
            height: 100vh;
            background-color: #2196f3cc;
            padding-top: 80px;
            position: fixed;
            left: -200px;
            transition: left 0.3s ease;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 15px;
            text-decoration: none;
            font-size: 16px;
        }

        .sidebar a:hover {
            background-color: #45a049;
        }

        /* Main Content Styling */
        .main {
            margin-left: 0;
            padding: 80px 20px 20px 20px;
            width: 100%;
            transition: margin-left 0.3s ease;
        }

        .product-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: flex-start;
        }

        .product-item {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 200px;
        }

        .product-item img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }

        .product-item h6 {
            margin: 10px 0 5px;
            font-size: 18px;
        }

        .product-item p {
            color: #555;
        }

        .order-item {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            margin-bottom: 15px;
        }

        .order-item h6 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .order-item p {
            margin-bottom: 5px;
        }

        form label {
            margin-top: 10px;
        }

        form input,
        form button {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #orders-section {
            display: none;
        }
    </style>
</head>
<body>

    <!-- Topbar -->
    <div class="topbar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <h1>Admin Dashboard</h1>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#">Dashboard</a>
        <a href="admin_dashboard.php">Manage Products</a>
        <a href="javascript:void(0);" onclick="showOrders()">Orders</a> <!-- Added onclick event here -->
        <a href="#">Settings</a>
        <a href="index.php">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h2>Add New Product</h2>
        <form action="admin_dashboard.php" method="post" enctype="multipart/form-data">
            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" id="product_name" required>

            <label for="product_price">Product Price:</label>
            <input type="number" step="0.01" name="product_price" id="product_price" required>

            <label for="product_image">Product Image:</label>
            <input type="file" name="product_image" id="product_image" required>

            <button type="submit">Add Product</button>
        </form>

        <h2>Product List</h2>
        <div class="product-grid">
            <?php while ($row = $productResult->fetch_assoc()) { ?>
                <div class="product-item">
                    <img src="uploads/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                    <h6><?php echo $row['name']; ?></h6>
                    <p>$<?php echo $row['price']; ?></p>
                    <form action="admin_dashboard.php" method="post">
                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete_product">Delete</button>
                    </form>
                </div>
            <?php } ?>
        </div>

        <!-- Orders Section (Initially Hidden) -->
        <div id="orders-section">
            <h2>Orders List</h2>
            <div class="product-grid">
                <?php while ($order = $orderResult->fetch_assoc()) { ?>
                    <div class="order-item">
                        <h6>Order ID: <?php echo $order['id']; ?></h6>
                        <p><strong>Customer Name:</strong> <?php echo $order['customer_name']; ?></p>
                        <p><strong>Total Amount:</strong> $<?php echo $order['total_amount']; ?></p>
                        <p><strong>Status:</strong> <?php echo $order['status']; ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- JavaScript to Toggle Sidebar and Show Orders -->
    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("open");

            var main = document.querySelector(".main");
            if (sidebar.classList.contains("open")) {
                main.style.marginLeft = "200px";
            } else {
                main.style.marginLeft = "0";
            }
        }

        function showOrders() {
            var ordersSection = document.getElementById("orders-section");
            // Toggle the visibility of the orders section
            ordersSection.style.display = ordersSection.style.display === "none" || ordersSection.style.display === "" ? "block" : "none";
        }
    </script>
</body>
</html>
