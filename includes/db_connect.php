<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "my_web_app";

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
