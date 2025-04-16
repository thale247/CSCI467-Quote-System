<?php
// Include the database connection file
include('database.php'); 

session_start();
// IF YOU HAVENT LOGGED IN BEFORE TRY USING ID ID00002 PASS 123
// form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the userid and password are set
    if (isset($_POST['userid']) && isset($_POST['pass'])) {
        $userid = $_POST['userid'];
        $password = $_POST['pass'];

        //SQL query on Associates
        $stmt = $conn->prepare("SELECT * FROM Associate WHERE USERID = ? AND PASSWORD = ?");
        $stmt->bind_param("ss", $userid, $password);  // "ss" = two strings
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Login success
            $_SESSION['userid'] = $userid;
            header("Location: AssociateDash.php");  // Redirect to dashboard
            exit();  // Always call exit after header redirection
        } else {
            echo "Invalid USERID or PASSWORD.";
        }

        $stmt->close();
    } else {
        echo "Please enter both USERID and PASSWORD.";
    }
} else {
    echo "Please submit the login form.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login to Your Account</h2>

    <!-- Login Form -->
    <form method="POST" action="login.php">
        <label for="userid">USERID:</label>
        <input type="text" id="userid" name="userid" required><br><br>

        <label for="pass">PASSWORD:</label>
        <input type="password" id="pass" name="pass" required><br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>
