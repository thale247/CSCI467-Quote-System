<?php
    session_start();

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);


    include('../includes/db_connect.php');

    $userid = $_POST['userid'];
    $password = $_POST['pass'];

    $stmt = $conn->prepare("SELECT * FROM Associate WHERE USERID = ? AND PASSWORD = ?");
    $stmt->bind_param("ss", $userid, $password);  // "ss" = two strings
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Login success
        $_SESSION['userid'] = $userid;
        $_SESSION['first'] = $result[1];
        $_SESSION['last'] = $result[2];
        header("Location: AssociateDash.php");  // Redirect to dashboard
        exit();
    } else {
        echo "Invalid USERID or PASSWORD.";
    }
    
    $stmt->close();
    $conn->close();

?>