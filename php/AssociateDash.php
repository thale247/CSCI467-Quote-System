<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['userid'])) {
    header("Location: Login.php");
    exit();
}

include('../includes/db_connect.php');
include('../includes/db_connect_legacy.php');

$customer_query = "SELECT id, name FROM customers";
$customer_result = $legacy_conn->query($customer_query);


$quote_query = "SELECT quote_id, total_amount, customer_id FROM Quote WHERE created_by LIKE '{$_SESSION['userid']}'";
$quote_result = $conn->query($quote_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quote Dashboard</title>
    <style>
        body {
            background-color: #f4f4f9;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 40px;
        }

        h2, h3 {
            color: #333;
        }

        form {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin-bottom: 30px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            color: #444;
        }

        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        input[type="button"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        input[type="button"]:hover {
            background-color: #45a049;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #e8e8e8;
            color: #333;
        }

        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
    <script type="text/javascript">
        function openNewQuote() {
            var customer_id = document.getElementById("customer").value;
            if (customer_id) {
                window.open("newquote.php?customer_id=" + customer_id, "New Quote", "width=600,height=400");
            } else {
                alert("Please select a customer first.");
            }
        }
        function openExistingQuote(cust_id, quote_id) {
            if(cust_id && quote_id) {
                window.open("editquote.php?customer_id=" + cust_id + "&quote_id=" + quote_id, "New Quote", "width=600,height=400");
            }
        }
    </script>
</head>
<body>
    <h2 style="margin-bottom: 30px;">Welcome, <?php echo htmlspecialchars($_SESSION['first']); ?>!</h2>

    <form>
        <label for="customer">Select a Customer:</label>
        <select name="customer_id" id="customer" required>
            <option value="">-- Choose Customer --</option>
            <?php
            if ($customer_result && $customer_result->num_rows > 0) {
                while ($row = $customer_result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
                }
            } else {
                echo "<option disabled>No customers found</option>";
            }
            ?>
        </select>
        <input type="button" value="New Quote" onclick="openNewQuote()">
    </form>

    <h3>Current Quotes:</h3>
    <table border="0">
        <thead>
            <tr>
                <th>Quote ID</th>
                <th>Total Amount ($)</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($quote_result && $quote_result->num_rows > 0) {
                while ($quote = $quote_result->fetch_assoc()) {
                    $qid = htmlspecialchars($quote['quote_id']);
                    echo "<tr>";
                    echo "<td>" . $qid . "</td>";
                    echo "<td>" . htmlspecialchars($quote['total_amount']) . "</td>";
                    $cid = htmlspecialchars($quote['customer_id'], ENT_QUOTES);
                    echo "<td><button onclick=\"openExistingQuote('$cid', '$qid')\">Edit</button></td>";
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
$conn->close();
$legacy_conn->close();
?>
