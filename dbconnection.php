<?php
$servername = "localhost";
$username = "root";  // Default user for XAMPP
$password = "";  // Default password for XAMPP
$dbname = "login_register";  // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
