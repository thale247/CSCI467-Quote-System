<?php
session_start();

// Once user is logged in:
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Connect to legacy customer DB
$legacy_conn = new mysqli('blitz.cs.niu.edu', 'student', 'student', 'csci467', 3306);
if ($legacy_conn->connect_error) 
{
    die("Connection to legacy DB failed: " . $legacy_conn->connect_error);
}

// Query for customer list
$customer_query = "SELECT id, name FROM customers";
$customer_result = $legacy_conn->query($customer_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quote Dashboard</title>
    <script type="text/javascript">
        // JS for pop-up new quote insertion
        function openNewQuote() 
        {
            // get customer ID selected 
            var customer_id = document.getElementById("customer").value;
            if (customer_id) 
            {
                // open new window with newQuote.php
                window.open("newquote.php?customer_id=" + customer_id, "New Quote", "width=600,height=400");
            } else 
            {
                alert("Please select a customer first.");
            }
        }
    </script>
</head>
<body>
    <h2>ASSOCIATE DASHBOARD: Welcome, <?php echo htmlspecialchars($_SESSION['userid']); ?>!</h2>

    <form>
         <!-- Dropdown for selecting a customer -->
        <label for="customer">Select a Customer:</label>
        <select name="customer_id" id="customer" required>
            <option value="">-- Choose Customer --</option>
            <?php
            // check for results 
            if ($customer_result && $customer_result->num_rows > 0) 
            {
                // loop for customers
                while ($row = $customer_result->fetch_assoc()) 
                {
                    echo "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
                }
            } else {
                // no customers found
                echo "<option disabled>No customers found</option>";
            }
            ?>
        </select>
        <br><br>
        <!--calls JS function to open a new quote -->
        <input type="button" value="New Quote" onclick="openNewQuote()">
    </form>
</body>
</html>

<?php
$legacy_conn->close();
?>
