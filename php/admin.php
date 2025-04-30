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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {

        if (strpos($key, 'save_') === 0) {

            $user_id = substr($key, 5);
            $first_name = mysqli_real_escape_string($conn, $_POST['first_' . $user_id]);
            $last_name = mysqli_real_escape_string($conn, $_POST['last_' . $user_id]);
            $password = mysqli_real_escape_string($conn, $_POST['password_' . $user_id]);
            $commission = mysqli_real_escape_string($conn, $_POST['commission_' . $user_id]);
            $address = mysqli_real_escape_string($conn, $_POST['address_' . $user_id]);

            $update_query = "UPDATE Associate SET FIRST = ?, LAST = ?, PASSWORD = ?, commission = ?, address = ? WHERE USERID = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ssssss', $first_name, $last_name, $password, $commission, $address, $user_id);
            $stmt->execute();
        }
    }
}

$associate_query = "SELECT USERID, FIRST, LAST, PASSWORD, commission, address FROM Associate";
$associate_result = $conn->query($associate_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Associates</title>
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
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .view-quotes-btn {
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .view-quotes-btn:hover {
            background-color: #2980b9;
        }

        .back-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-top: 20px;
        }

        .back-btn:hover {
            background-color: #e67e22;
        }
    </style>
</head>
<body>
<div style="position: absolute; top:20px; right: 40px;">
    <form action="logout.php" method="post" style="text-align: right; margin-bottomL 40px;">
            <button type="submit" style="background-color: #4CAF50;color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;">
            Logout
    </button>
    </form>
    </div>

    <h2>Manage Associates</h2>

    <form method="post" action="admin.php">
        <table>
            <thead>
                <tr>
                    <th>USERID</th>
                    <th>FIRST</th>
                    <th>LAST</th>
                    <th>PASSWORD</th>
                    <th>Commission</th>
                    <th>Address</th>
                    <th>Save</th>
                    <th>View Quotes</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($associate_result && $associate_result->num_rows > 0) {
                    while ($associate = $associate_result->fetch_assoc()) {
                        $user_id = htmlspecialchars($associate['USERID']);
                        $first_name = htmlspecialchars($associate['FIRST']);
                        $last_name = htmlspecialchars($associate['LAST']);
                        $password = htmlspecialchars($associate['PASSWORD']);
                        $commission = htmlspecialchars($associate['commission']);
                        $address = htmlspecialchars($associate['address']);
                        
                        echo "<tr>";
                        echo "<td>" . $user_id . "</td>";
                        echo "<td><input type='text' name='first_".$user_id."' value='".$first_name."' /></td>";
                        echo "<td><input type='text' name='last_".$user_id."' value='".$last_name."' /></td>";
                        echo "<td><input type='text' name='password_".$user_id."' value='".$password."' /></td>";
                        echo "<td><input type='text' name='commission_".$user_id."' value='$".$commission."' step='0.01' /></td>";
                        echo "<td><input type='text' name='address_".$user_id."' value='".$address."' /></td>";
                        echo "<td><input type='submit' value='Save' name='save_".$user_id."'></td>";
                        echo "<td><form method='get' action='view.php' style='display:inline;'>
                                <input type='hidden' name='user_id' value='".$user_id."' />
                                <input type='submit' class='view-quotes-btn' value='View Quotes'>
                            </form></td>";
                        echo "<td><form method='post' action='delete_associate.php' style='display:inline;'><input type='hidden' name='user_id' value='".$user_id."' /><input type='submit' class='delete-btn' value='Delete'></form></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No associates found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </form>

    <a href="ProcessingOrder.php" class="back-btn">Back to Processing Orders</a>

</body>
</html>

<?php
$conn->close();
?>
