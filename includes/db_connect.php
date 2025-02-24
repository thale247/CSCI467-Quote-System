<?php
$connection_string = getenv('AZURE_MYSQL_CONNECTIONSTRING');

// Create connection
$conn = new mysqli($connection_string);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
