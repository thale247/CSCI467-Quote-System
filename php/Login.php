<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    session_start();

    $host = getenv('DB_HOST'); // Access DB host from environment variable
    $username = getenv('DB_USER'); // Access DB username from environment variable
    $password = getenv('DB_PASS'); // Access DB password from environment variable
    $database = 'quoteSystem'; // Access DB name from environment variable

    // Create a connection to the database
    $conn = new mysqli($host, $username, $password, $database);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $userid = $_POST['userid'];
    $password = $_POST['pass'];

    $stmt = $conn->prepare("SELECT * FROM Associate WHERE USERID = ? AND PASSWORD = ?");
    $stmt->bind_param("ss", $userid, $password);  // "ss" = two strings
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Login success
        $_SESSION['userid'] = $userid;
        header("Location: AssociateDash.php");  // Redirect to dashboard
        exit();
    } else {
        echo "Invalid USERID or PASSWORD.";
    }
    
    $stmt->close();
    $conn->close();

?>