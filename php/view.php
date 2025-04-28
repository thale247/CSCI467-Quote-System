<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}

include('../includes/db_connect.php');

if (!isset($_GET['user_id'])) {
    echo "No associate specified.";
    exit();
}

$user_id = $_GET['user_id'];

$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'quote_id';
$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] == 'asc' ? 'asc' : 'desc';
$quote_query = "SELECT quote_id, created_by, customer_email, items, item_prices, secret_notes, discount_percentage, total_amount, created, status 
                FROM Quote WHERE created_by = ? ORDER BY $sort_column $sort_order";

$stmt = $conn->prepare($quote_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$quote_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotes for Associate: <?php echo htmlspecialchars($user_id); ?></title>
    <style>
        body {
            background-color: #f4f4f9;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 40px;
        }

        h2 {
            color: #333;
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
            cursor: pointer;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        th a {
            color: #333;
            text-decoration: none;
            display: block;
        }

        th a:hover {
            background-color: #90EE90;
            color: #fff;
        }

        .back-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            display: inline-block;
        }

        .back-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

    <h2>Quotes Made by Associate: <?php echo htmlspecialchars($user_id); ?></h2>

    <table>
        <thead>
            <tr>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=quote_id&sort_order=<?php echo ($sort_column == 'quote_id' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Quote ID</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=customer_email&sort_order=<?php echo ($sort_column == 'customer_email' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Customer Email</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=items&sort_order=<?php echo ($sort_column == 'items' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Items</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=item_prices&sort_order=<?php echo ($sort_column == 'item_prices' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Item Prices</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=secret_notes&sort_order=<?php echo ($sort_column == 'secret_notes' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Secret Notes</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=discount_percentage&sort_order=<?php echo ($sort_column == 'discount_percentage' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Discount</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=total_amount&sort_order=<?php echo ($sort_column == 'total_amount' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Total Amount</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=created&sort_order=<?php echo ($sort_column == 'created' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Created</a></th>
                <th><a href="?user_id=<?php echo htmlspecialchars($user_id); ?>&sort_column=status&sort_order=<?php echo ($sort_column == 'status' && $sort_order == 'asc') ? 'desc' : 'asc'; ?>">Status</a></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($quote_result && $quote_result->num_rows > 0) {
                while ($quote = $quote_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($quote['quote_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['customer_email']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['items']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['item_prices']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['secret_notes']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['discount_percentage']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['total_amount']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['created']) . "</td>";
                    echo "<td>" . htmlspecialchars($quote['status']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9'>No quotes found for this associate.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <a href="admin.php" class="back-button">Back to Admin</a>

</body>
</html>

<?php
$conn->close();
?>
