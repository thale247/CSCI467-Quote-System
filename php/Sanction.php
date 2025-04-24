<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

include('../includes/db_connect.php');
include('../includes/db_connect_legacy.php');

$customer_query = "SELECT id, name FROM customers";
$customer_result = $legacy_conn->query($customer_query);


$quote_query =  "SELECT quote_id, created_by, customer_email, items, item_prices, secret_notes, discount_percentage, total_amount, created, customer_id, status FROM Quote";
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
        function openExistingQuote(cust_id, quote_id) {
            if(cust_id && quote_id) {
                window.open("SanctionQuote.php?customer_id=" + cust_id + "&quote_id=" + quote_id, "Sanction Quote", "width=600,height=400");
            }
        }
    </script>
</head>
<body>
    <h2 style="margin-bottom: 30px;">Welcome, <?php echo htmlspecialchars($_SESSION['first']); ?>!</h2>


    <h3>Finalized Quotes:</h3>
    <table border="0">
        <thead>
            <tr>
                <th>Quote ID</th>
                <th>Date Placed</th>
                <th>Created by</th>
                <th>Order From</th>
                <th>Total</th>
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
                    echo "<td>" . htmlspecialchars($quote['created']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['created_by']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['customer_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['total_amount']) . "</td>";
                    $cid = htmlspecialchars($quote['customer_id'], ENT_QUOTES);
                    echo "<td><button onclick=\"openExistingQuote('$cid', '$qid')\">Sanction</button></td>";
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
