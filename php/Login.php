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
        $row = $result->fetch_row();
        $_SESSION['userid'] = $userid;
        $_SESSION['first'] = $row[1];
        $_SESSION['last'] = $row[2];
        header("Location: AssociateDash.php");  // Redirect to dashboard
        exit();
    } else {
        echo "Invalid USERID or PASSWORD.";
    }
    $stmt = $conn->prepare("SELECT * FROM Admin WHERE USERID = ? AND PASSWORD = ?");
    $stmt->bind_param("ss", $userid, $password);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
    // Admin login success
    $row = $result->fetch_row();
    $_SESSION['userid'] = $userid;
    $_SESSION['first'] = $row[1];
    $_SESSION['last'] = $row[2];
    $_SESSION['role'] = 'admin';
    header("Location: Sanction.php");  // Redirect to admin page
    exit();
    }
    
    $stmt->close();
    $conn->close();

?>
