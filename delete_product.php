<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'login_register');

// Check for database connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the product ID is provided in the POST request
if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    // Prepare SQL query to delete the product
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);

    // Bind the product ID to the query
    $stmt->bind_param('i', $product_id);

    // Execute the delete query
    if ($stmt->execute()) {
        echo "Product deleted successfully";
    } else {
        echo "Error deleting product: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Close the database connection
$conn->close();

// Redirect back to the admin dashboard
header("Location: admin_dashboard.php");
exit;
?>
