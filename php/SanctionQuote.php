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

if (!isset($_GET['customer_id']) || !isset($_GET['quote_id'])) {
    die("Customer or quote not selected.");
}

$customer_id = $_GET['customer_id'];
$quote_id = $_GET['quote_id'];

$customer_stmt = $legacy_conn->prepare("SELECT name, city, street, contact FROM customers WHERE id = ?");
$customer_stmt->bind_param("s", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
if ($customer_result->num_rows === 0) {
    die("Customer not found.");
}
$customer = $customer_result->fetch_assoc();
$customer_name = $customer['name'];
$customer_city = $customer['city'];
$customer_street = $customer['street'];
$customer_contact = $customer['contact'];
$customer_stmt->close();

$quote_stmt = $conn->prepare("SELECT * FROM Quote WHERE quote_id = ?");
$quote_stmt->bind_param("s", $quote_id);
$quote_stmt->execute();
$quote_result = $quote_stmt->get_result();
if ($quote_result->num_rows === 0) {
    die("Quote not found.");
}
$quote = $quote_result->fetch_assoc();
$quote_stmt->close();

$customer_email = $quote['customer_email'];
$quote_items = explode(",", $quote['items']);
$quote_item_prices = explode(",", $quote['item_prices']);
$quote_item_details = explode(",", $quote['item_details']);
$total_amount = $quote['total_amount'];
$discount = $quote['discount_percentage'];
$quote_discount_per = $quote['discount_percentage'];
$status = $quote['status'];
$asc_id = $quote['created_by'];
$secret_notes = $quote['secret_notes'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cust_id = $conn->real_escape_string($_POST['customer_id']);
    $email = $conn->real_escape_string($_POST['email']);
    $items = $conn->real_escape_string($_POST['items']);
    $prices = $conn->real_escape_string($_POST['prices']);
    $descriptions = $conn->real_escape_string($_POST['descriptions']);
    $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
    $discount = floatval($_POST['discount']);
    $discount_percent = floatval($_POST['discount_percent']);


    $item_prices_array = explode(",", $_POST['prices']);
    $items = explode(",", $items);
    $total = 0;
    foreach ($item_prices_array as $price) {
        $total += floatval(trim($price));
    }

    $discounted_total = $total * (1 - $discount_percent / 100);
    $formatted_total = number_format($discounted_total, 2, '.', '');

    if (isset($_POST['sanction'])) {
        $update_stmt = $conn->prepare("UPDATE Quote SET status = 'sanctioned' WHERE quote_id = ?");
        $update_stmt->bind_param("s", $quote_id);
        if ($update_stmt->execute()) {
            $message = "Dear $customer_name,\n\nHere is the finalized quote for your request:\n\n";
            $message .= "Itemized List:\n";
            for ($i = 0; $i < count($items); $i++) {
                $name = $items[$i] ?? '';
                $desc = $quote_item_details[$i] ?? '';
                $price = $quote_item_prices[$i] ?? '';
                $message .= "- $name ($desc): \$$price\n";
            }
            $message .= "\nDiscount Applied: $discount%\n";
            $message .= "Total Due: \$$total_amount\n\nThank you for your business!";

            mail($customer_email, "Your Sanctioned Quote", $message, "From: noreply@quote-system.com");
            echo "<p style='font-weight:bold; color: green;'>Quote sanctioned and email sent!</p>";
            $update_stmt->close();
            $conn->close();
            $legacy_conn->close();
            exit();
        } else {
            echo "<p style='font-weight:bold; color: red;'>Error: " . $update_stmt->error . "</p>";
            $update_stmt->close();
        }
    } else {
        $update = $conn->prepare("UPDATE Quote SET customer_email = ?, items = ?, item_prices = ?, item_details = ?, secret_notes = ?, discount_percentage = ?, total_amount = ?, customer_id = ? WHERE quote_id = ?");
        $update->bind_param("ssssdsiss", $email, $quote_items, $prices, $descriptions, $notes, $discount_percent, $formatted_total, $cust_id, $quote_id);

        if ($update->execute()) {
            echo "<p style='font-weight:bold;'>Quote successfully updated!</p>";
        } else {
            echo "<p style='font-weight:bold;'>Error: " . $update->error . "</p>";
        }

        $update->close();

    }


    $customer_email = $_POST['email'];
    $quote_items = $_POST['items'];
    $quote_item_prices = $_POST['prices'];
    $quote_item_details = $_POST['descriptions'];
    $quote_discount_per = $_POST['discount_percent'];

    $conn->close();
    $legacy_conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sanction Quote</title>
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
    <h2 style="margin-bottom: 10px;">Order From: <?= htmlspecialchars($customer_name) ?></h2>
    <div style="line-height: 1.2; margin-bottom: 20px;">
        <?= htmlspecialchars($customer_street) ?><br>
        <?= htmlspecialchars($customer_city) ?><br>
        <?= htmlspecialchars($customer_contact) ?><br>
        <?= "Status: " . htmlspecialchars($status) ?><br>
        <?= "Commission: " ?><br>
    </div>

    <form method="post" onsubmit="return prepare();">
        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
        <input type="email" name="email" value="<?= htmlspecialchars($customer_email) ?>" style="margin-bottom: 15px; padding: 5px; background-color: #eee; border: 1px solid #ccc;">

        <h3>Items</h3>
        <div style="display: flex; gap: 10px; font-weight: bold; margin-bottom: 5px;">
            <div style="width: 150px;">Item Name</div>
            <div style="width: 200px;">Description</div>
            <div style="width: 100px;">Price ($)</div>
        </div>
        <div id="items-container"></div>
        <button type="button" onclick="addItem()" style="margin: 10px 0;">+ New Item</button><br>

        <input type="hidden" name="items" id="items-hidden">
        <input type="hidden" name="prices" id="prices-hidden">
        <input type="hidden" name="descriptions" id="descriptions-hidden">
        <input type="hidden" name="asc_id" id="asc_id" value="<?= htmlspecialchars($asc_id) ?>">

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Secret Notes:</div>
        <textarea name="notes" id="notes" rows="3" style="width: 300px; padding: 5px;"><?= htmlspecialchars($secret_notes) ?></textarea><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Discount (%):</div>
        <input type="number" name="discount_percent" id="discount_percent" step="0.01" value="0" oninput="calculateTotalPercent()" style="padding: 5px;"><br><br>
        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Discount ($):</div>
        <input type="number" name="discount" id="discount" step="0.01" value="0" oninput="calculateTotal()" style="padding: 5px;"><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Total Amount ($):</div>
        <div id="total-amount" style="font-weight: bold; font-size: 18px;">$<?= number_format($total_amount, 2) ?></div><br><br>

        <input type="submit" value="Update Quote" style="padding: 10px 20px; font-weight: bold;"><br><br>

        <p>To sanction this quote and email it to the customer, click here:
        <button type="button" onclick="submit_quote()" style="padding: 10px 20px; font-weight: bold;">Sanction Quote</button></p>
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
                <input type="text" placeholder="Item Name" class="item-name" required style="padding: 5px;">
                <input type="text" placeholder="Description" class="item-desc" style="padding: 5px;">
                <input type="number" step="0.01" placeholder="Price" class="item-price" oninput="calculateTotal()" required style="padding: 5px;">
                <button type="button" onclick="removeItem(this)" style="background-color: black; color: white; border: none; padding: 4px 10px; font-weight: bold; font-size: 16px; cursor: pointer; line-height: 1;">X</button>
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
                if (name && price) {
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

        const existingItems = "<?= addslashes($quote['items']) ?>".split(",");
        const existingPrices = "<?= addslashes($quote['item_prices']) ?>".split(",");
        const existingDescriptions = "<?= addslashes($quote['item_details']) ?>".split(",");

        window.addEventListener("DOMContentLoaded", () => {
            for (let i = 0; i < existingItems.length; i++) {
                if (existingItems[i].trim() !== "") {
                    addItem();
                    const row = document.querySelectorAll("#items-container > div")[i];
                    row.querySelector(".item-name").value = existingItems[i];
                    row.querySelector(".item-desc").value = existingDescriptions[i] || "";
                    row.querySelector(".item-price").value = existingPrices[i] || "";
                }
            }
            calculateTotal();
        });
    </script>
</body>
</html>
