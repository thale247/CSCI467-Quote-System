<?php
// Start the session (if needed)
session_start();

// Include database connection (if required)
//include 'includes/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote System CSCI 467 test</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <!-- <form id="send_quote_form">
      <label for="user">User:</label>
      <input type="text" id="user" name="user"><br><br>
      <label for="lname">Price:</label>
      <input type="text" id="price" name="price"><br><br>
      <button type="button" onclick="sendData()">Submit</button>
    </form> -->
    <div id="loginDiv">
      <h1>Login:</h1>
      <form action="php/Login.php" method="POST" id="login_form">
        <label for="userID">UserID:</label>
        <input type="text" id="userID" name="userID"><br><br>
        <label for="pass">Password:</label>
        <input type="password" id="pass" name="pass"><br><br>
        <button type="submit">Login</button>
      </form>
    </div>
    <!-- <form id="get_users_form">
      <button type="button" onclick="getData()">Pull names from Thomas's DB</button>
    </form>
    <div id="response"></div>
    <script src="/js/SendQuote.js"></script>
    <script src="/js/GetUsers.js"></script> -->
    <?php include 'includes/footer.php'; ?> <!-- Footer -->

</body>
</html>
