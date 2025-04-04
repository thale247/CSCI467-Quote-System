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
    <title>Quote System CSCI 467</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>

    <?php include 'includes/header.php'; ?> <!-- Navigation Bar -->

    <form id="send_quote_form"> <!-- action="SendPurchaseOrder.php" method="POST" -->
      <label for="user">User:</label>
      <input type="text" id="user" name="user"><br><br>
      <label for="lname">Price:</label>
      <input type="text" id="price" name="price"><br><br>
      <button type="button" onclick="sendData()">Submit</button>
    </form>
    <form id="get_users_form"> <!-- action="SendPurchaseOrder.php" method="POST" -->
      <button type="button" onclick="getData()">Pull names from Thomas's DB</button>
    </form>
    <div id="response"></div>
    <script src="/js/SendQuote.js"></script>
    <script src="/js/GetUsers.js"></script>
    <?php include 'includes/footer.php'; ?> <!-- Footer -->

</body>
</html>
