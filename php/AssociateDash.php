<?php
session_start();

// Once user is logged in:
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

include('db_connect_legacy.php');

// Query for customer list
$customer_query = "SELECT id, name FROM customers";
$customer_result = $legacy_conn->query($customer_query);

// Query for current quotes from the quote database
$quote_db = new mysqli('71.228.20.16', 'user', 'pass', 'quoteSystem');
if ($quote_db->connect_error) {
    die("Quote DB connection failed: " . $quote_db->connect_error);
}

$quote_query = "SELECT quote_id, total_amount FROM Quote";
$quote_result = $quote_db->query($quote_query);
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

    <h3>Current Quotes:</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Quote ID</th>
                <th>Total Amount ($)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Check if quotes exist
            if ($quote_result && $quote_result->num_rows > 0) {
                while ($quote = $quote_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($quote['quote_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['total_amount']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='2'>No quotes found.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>

<?php
// Close the quote database connection
$quote_db->close();

// Close the legacy database connection
$legacy_conn->close();
?>
