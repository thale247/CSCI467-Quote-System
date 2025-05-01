<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('../includes/db_connect.php');

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}
$customer_email = '';


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
        $email = $quote['customer_email'];
        $created_by = $quote['created_by'];
        $quote_items = $quote['items'];
        $quote_item_prices = $quote['item_prices'];
        $quote_item_details = $quote['item_details'];
        $secret_notes = $quote['secret_notes'];
        $total_amount = $quote['total_amount'];
        $status = $quote['status'];
        $discount = $quote['discount_percentage'];
        $quote_discount_per = $quote['discount_percentage'];
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
        $descriptions = $conn->real_escape_string($_POST['descriptions']);
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
                $commission_str = $response['commission'];
                $commission_pct = floatval(str_replace('%', '', $commission_str)) / 100.0;
                $commission_amount = $discounted_total * $commission_pct;

                $update_quote = $conn->prepare("UPDATE Quote SET commission = ?, status = 'ordered' WHERE quote_id = ?");
                $update_quote->bind_param("ds", $commission_amount, $quote_id);
                $update_quote->execute();
                $update_quote->close();

                $update_commission = $conn->prepare("UPDATE Associate SET commission = commission + ? WHERE USERID = ?");
                $update_commission->execute([$commission_amount, $_POST['asc_id']]);
                $update_commission->close();

                $message = "Dear $customer_name,\n\nYour order has been successfully placed.\n\nQuote ID: $quote_id\n";
                $item_array = explode(",", $items);
                $desc_array = explode(",", $quote_item_details);
                $price_array = explode(",", $quote_item_prices);

                $message .= "\nOrder Details:\n";
                for ($i = 0; $i < count($item_array); $i++) {
                    $item = $item_array[$i] ?? '';
                    $desc = $desc_array[$i] ?? '';
                    $price = $price_array[$i] ?? '';
                    $message .= "- $item ($desc): \$$price\n";
                }

                $message .= "\nDiscount Applied: $discount%\n";
                $message .= "Total Charged: $" . number_format($discounted_total, 2) . "\n\nThank you for your business!";

                mail($customer_email, "Your Order Confirmation", $message, "From: noreply@quote-system.com");

                echo "<p style='font-weight:bold; color: green;'>Quote successfully ordered and email sent!</p>";
                exit();
            } else {
                echo "<p style='color: red;'>Missing commission in response.</p>";
            }
        }
    } else {
        $cust_id = $conn->real_escape_string($_POST['customer_id']);
        $email = $conn->real_escape_string($_POST['email']);
        $items = $conn->real_escape_string($_POST['items']);
        $prices = $conn->real_escape_string($_POST['prices']);
        $descriptions = $conn->real_escape_string($_POST['descriptions']);
        $notes = $conn->real_escape_string($_POST['notes']);
        $discount = floatval($_POST['discount']);
        $discount_percent = floatval($_POST['discount_percent']);

        $item_prices = explode(",", $_POST['prices']);
        $total = 0;
        foreach ($item_prices as $price) {
            $total += floatval(trim($price));
        }

        $discounted_total = $total * (1 - $discount_percent / 100);
        $total = number_format($discounted_total, 2, '.', '');

        $update = "UPDATE Quote SET customer_email = '$email', items = '$items', item_prices = '$prices', item_details = '$descriptions',
                    secret_notes = '$notes', discount_percentage = '$discount_percent', total_amount = '$total',
                    customer_id = $cust_id
                    WHERE quote_id = '$quote_id'";

        if ($conn->query($update) === TRUE) {
            echo "<p style='font-weight:bold;'>Quote successfully updated!</p>";
        } else {
            echo "<p style='font-weight:bold;'>Error: " . $conn->error . "</p>";
        }
    }
    $customer_email = $_POST['email'];
    $quote_items = $_POST['items'];
    $quote_item_prices = $_POST['prices'];
    $quote_item_details = $_POST['descriptions'];
    $quote_discount_per = $_POST['discount_percent'];
    $conn->close();
}

$legacy_conn->close();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Process Quote</title>
</head>
<script>
    const existingEmail = <?php echo json_encode($email ?? ""); ?>;
    const existingItems = <?php echo json_encode(explode(",", $quote_items ?? "")); ?>;
    const existingPrices = <?php echo json_encode(explode(",", $quote_item_prices ?? "")); ?>;
    const existingDescriptions = <?php echo json_encode(explode(",", $quote_item_details ?? "")); ?>;
    const discount_per = <?php echo json_encode($quote_discount_per ?? "0"); ?>;

    window.onload = function () {
    const container = document.getElementById("items-container");

    for (let i = 0; i < existingItems.length; i++) {
        const row = document.createElement("div");
        row.style.display = "flex";
        row.style.gap = "10px";
        row.style.marginBottom = "10px";
        row.style.alignItems = "center";

        const desc = existingDescriptions[i] || "";

        row.innerHTML = `
            <input type="text" placeholder="Item Name" class="item-name" required value="${existingItems[i]}" style="padding: 5px;">
            <input type="text" placeholder="Description" class="item-desc" value="${desc}" style="padding: 5px;">
            <input type="number" step="0.01" placeholder="Price" class="item-price" required value="${existingPrices[i]}" oninput="calculateTotal()" style="padding: 5px;">
            <button type="button" onclick="removeItem(this)" style="background-color: black; color: white; border: none; padding: 4px 10px; font-weight: bold; font-size: 16px; cursor: pointer; line-height: 1;">X</button>
        `;
        container.appendChild(row);
    }

    // Calculate discount $ from percent
    let total = 0;
    existingPrices.forEach(p => {
        total += parseFloat(p) || 0;
    });
    const calculatedDiscount = (parseFloat(discount_per) / 100) * total;

    document.getElementById("discount_percent").value = parseFloat(discount_per).toFixed(2);
    document.getElementById("discount").value = calculatedDiscount.toFixed(2);

    calculateTotalPercent();
};

</script>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2 style="margin-bottom: 10px;">Order From: <?php echo htmlspecialchars($customer_name); ?></h2>

    <div style="line-height: 1.2; margin-bottom: 20px;">
        <?php echo htmlspecialchars($customer_street); ?><br>
        <?php echo htmlspecialchars($customer_city); ?><br>
        <?php echo htmlspecialchars($customer_contact); ?><br>
        <?php echo "Status: " . htmlspecialchars($quote['status']);?><br>
        <?php echo "Commission: ";?><br>
    </div>

    <form method="post" onsubmit="return prepare();">
        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer_id); ?>">

        <input type="email" name="email" value="<?php echo htmlspecialchars($customer_email); ?>" readonly
         style="margin-bottom: 15px; padding: 5px; background-color: #eee; border: 1px solid #ccc;">

        <h3>Items</h3>

        <div style="display: flex; gap: 10px; font-weight: bold; margin-bottom: 5px;">
            <div style="width: 150px;">Item Name</div>
            <div style="width: 200px;">Description</div>
            <div style="width: 100px;">Price ($)</div>
        </div>

        <div id="items-container"></div>
        <button type="button" disabled style="margin: 10px 0;">+ New Item</button><br>

        <input type="hidden" name="items" id="items-hidden">
        <input type="hidden" name="prices" id="prices-hidden">
        <input type="hidden" name="descriptions" id="descriptions-hidden">
        <input type="hidden" name="asc_id" id="asc_id" value="<?php echo htmlspecialchars($created_by);?>">

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Secret Notes:</div>
        <textarea name="notes" id="notes" rows="3" style="width: 300px; padding: 5px;" readonly><?php echo htmlspecialchars($secret_notes);?></textarea><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Discount (%):</div>
        <input type="number" name="discount_percent" id="discount_percent" step="0.01" value="0" oninput="calculateTotalPercent()" style="padding: 5px;"><br><br>
        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Discount ($):</div>
        <input type="number" name="discount" id="discount" step="0.01" value="0" oninput="calculateTotal()" style="padding: 5px;"><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Total Amount ($):</div>
        <div id="total-amount" style="font-weight: bold; font-size: 18px;">$<?php echo number_format($total_amount,2);?></div><br><br>

        <input type="submit" value="Update Quote" style="padding: 10px 20px; font-weight: bold;"><br><br>
        <p>To convert this quote into an order and process it, click here:
        <button type="button" onclick="submit_quote()" style="padding: 10px 20px; font-weight: bold;">Process PO</button></p>
    </form>

    <script>
        function addItem() {
            const container = document.getElementById("items-container");
            const row = document.createElement("div");
            row.style.display = "flex";
            row.style.gap = "10px";
            row.style.marginBottom = "10px";
            row.style.alignItems = "center";

            row.innerHTML = `
                <input type="text" placeholder="Item Name" class="item-name" readonly style="padding: 5px;">
                <input type="text" placeholder="Description" class="item-desc" readonly style="padding: 5px;">
                <input type="number" step="0.01" placeholder="Price" class="item-price" readonly oninput="calculateTotal()" style="padding: 5px;">
                <button type="button" onclick="removeItem(this)" disabled style="background-color: black; color: white; border: none; padding: 4px 10px; font-weight: bold; font-size: 16px; cursor: pointer; line-height: 1;">X</button>
            `;
            container.appendChild(row);
        }

        function removeItem(button) {
            button.parentElement.remove();
            calculateTotal();
        }

        function calculateTotal() {
            const priceInputs = document.querySelectorAll(".item-price");
            let total = 0;
            priceInputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            const discount = parseFloat(document.getElementById("discount").value) || 0;
            const discountedTotal = total - discount;

            document.getElementById("total-amount").textContent = `$${discountedTotal.toFixed(2)}`;

            if (total !== 0) {
                const per = (discount / total) * 100;
                document.getElementById("discount_percent").value = per.toFixed(2);
            }
        }

        function calculateTotalPercent() {
            const priceInputs = document.querySelectorAll(".item-price");
            let total = 0;
            priceInputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            const percent = parseFloat(document.getElementById("discount_percent").value) || 0;
            const discount = (percent / 100) * total;
            const discountedTotal = total - discount;

            document.getElementById("discount").value = discount.toFixed(2);
            document.getElementById("total-amount").textContent = `$${discountedTotal.toFixed(2)}`;
        }

        function prepare() {
            const itemNames = [];
            const itemPrices = [];
            const itemDescs = [];

            document.querySelectorAll("#items-container > div").forEach(row => {
                const name = row.querySelector(".item-name").value.trim();
                const desc = row.querySelector(".item-desc").value.trim();
                const price = row.querySelector(".item-price").value.trim();
                if (name !== "" && price !== "") {
                    itemNames.push(name);
                    itemPrices.push(price);
                    itemDescs.push(desc);
                }
            });

            document.getElementById("items-hidden").value = itemNames.join(",");
            document.getElementById("prices-hidden").value = itemPrices.join(",");
            document.getElementById("descriptions-hidden").value = itemDescs.join(",");

            calculateTotal();
            return true;
        }

        function submit_quote() {
            prepare();
            const form = document.querySelector('form');
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'sanction';
            hiddenInput.value = '1';
            form.appendChild(hiddenInput);
            form.submit();
        }

        calculateTotal();

    </script>
</body>
</html>
