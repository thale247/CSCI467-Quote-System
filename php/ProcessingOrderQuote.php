<?php
session_start();

include('../includes/db_connect.php');

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

$legacy_conn = new mysqli('blitz.cs.niu.edu', 'student', 'student', 'csci467', 3306);
if ($legacy_conn->connect_error) {
    die("Connection to legacy DB failed: " . $legacy_conn->connect_error);
}

if (isset($_GET['customer_id'])) {
    $customer_id = $legacy_conn->real_escape_string($_GET['customer_id']);
    $customer_query = "SELECT name, city, street, contact FROM customers WHERE id = '$customer_id'";
    $customer_result = $legacy_conn->query($customer_query);

    if ($customer_result && $customer_result->num_rows > 0) {
        $customer = $customer_result->fetch_assoc();
        $customer_name = $customer['name'];
        $customer_city = $customer['city'];
        $customer_street = $customer['street'];
        $customer_contact = $customer['contact'];
    } else {
        die("Customer not found.");
    }
} else {
    die("No customer selected.");
}

if (isset($_GET['quote_id'])) {
    $quote_id = $conn->real_escape_string($_GET['quote_id']);
    $quote_query = "SELECT * FROM Quote WHERE quote_id = '$quote_id'";
    $quote_result = $conn->query($quote_query);

    if ($quote_result && $quote_result->num_rows > 0) {
        $quote = $quote_result->fetch_assoc();
        $customer_email = $quote['customer_email'];
        $created_by = $quote['created_by'];
        $items = $quote['items'];
        $item_prices = $quote['item_prices'];
        $secret_notes = $quote['secret_notes'];
        $total_amount = $quote['total_amount'];
        $status = $quote['status'];
        $discount = $quote['discount_percentage'];
    } else {
        die("Quote not found.");
    }
} else {
    die("No quote selected.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userid = $_SESSION['userid'];

    if (isset($_POST['sanction'])) {
        $cust_id = $conn->real_escape_string($_POST['customer_id']);
        $items = $conn->real_escape_string($_POST['items']);
        $prices = $conn->real_escape_string($_POST['prices']);
        $discount = floatval($_POST['discount']);

        $item_prices_array = explode(",", $_POST['prices']);
        $total = 0;
        foreach ($item_prices_array as $price) {
            $total += floatval(trim($price));
        }

        $discounted_total = $total * (1 - $discount / 100);

        $url = 'http://blitz.cs.niu.edu/PurchaseOrder/';
        $data = array(
            'order' => $quote_id,
            'associate' => $_POST['asc_id'],
            'custid' => $cust_id,
            'amount' => round($discounted_total, 2)
        );

        $options = array(
            'http' => array(
                'header'  => array('Content-type: application/json', 'Accept: application/json'),
                'method'  => 'POST',
                'content' => json_encode($data)
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            echo "<p style='color: red;'>Error sending purchase order.</p>";
        } else {
            $response = json_decode($result, true);
            if (isset($response['commission'])) {
                $commission_str = $response['commission']; // e.g., "8%"
                $commission_pct = floatval(str_replace('%', '', $commission_str)) / 100.0;
                $commission_amount = $discounted_total * $commission_pct;

                $update_commission = $conn->prepare("UPDATE Associate SET commission = commission + ? WHERE userid = ?");
                $update_commission->bind_param("ds", $commission_amount, $userid);
                $update_commission->execute();
                $update_commission->close();
            }

            $updateStatus = $conn->prepare("UPDATE Quote SET `status` = 'ordered' WHERE quote_id = ?");
            $updateStatus->bind_param("s", $quote_id);
            if ($updateStatus->execute()) {
                echo "<p style='font-weight:bold; color: green;'>Quote successfully ordered!</p>";
                exit();
            } else {
                echo "<p style='font-weight:bold; color: red;'>Error: " . $updateStatus->error . "</p>";
            }
            $updateStatus->close();
        }
    } else {
        $cust_id = $conn->real_escape_string($_POST['customer_id']);
        $email = $conn->real_escape_string($_POST['email']);
        $items = $conn->real_escape_string($_POST['items']);
        $prices = $conn->real_escape_string($_POST['prices']);
        $notes = $conn->real_escape_string($_POST['notes']);
        $discount = floatval($_POST['discount']);

        $item_prices = explode(",", $_POST['prices']);
        $total = 0;
        foreach ($item_prices as $price) {
            $total += floatval(trim($price));
        }

        $discounted_total = $total * (1 - $discount / 100);
        $total = number_format($discounted_total, 2, '.', '');

        $update = "UPDATE Quote SET customer_email = '$email', items = '$items', item_prices = '$prices',
                    secret_notes = '$notes', discount_percentage = '$discount', total_amount = '$total',
                    customer_id = $cust_id
                    WHERE quote_id = '$quote_id'";

        if ($conn->query($update) === TRUE) {
            echo "<p style='font-weight:bold;'>Quote successfully updated!</p>";
        } else {
            echo "<p style='font-weight:bold;'>Error: " . $conn->error . "</p>";
        }
    }

    $conn->close();
}

$legacy_conn->close();
?>