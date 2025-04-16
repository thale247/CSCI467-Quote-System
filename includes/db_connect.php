<?php

    $host = getenv('DB_HOST'); // Access DB host from environment variable
    $username = getenv('DB_USER'); // Access DB username from environment variable
    $password = getenv('DB_PASS'); // Access DB password from environment variable
    $database = 'quoteSystem'; // Access DB name from environment variable

    // Create a connection to the database
    $conn = new mysqli($host, $username, $password, $database);

// Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>
