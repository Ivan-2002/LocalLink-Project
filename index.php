<?php
$servername = "localhost"; // Usually localhost
$username = "root";        // Your phpMyAdmin username
$password = "";            // Your phpMyAdmin password (often empty for local)
$dbname = "c2c_platform";  // The name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>