<?php
session_start();

include('../includes/db_connect.php');

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}


$userid = $_SESSION['userid'];
$assoc_query = "SELECT `FIRST`, `LAST`, commission FROM Associate WHERE USERID = '$userid'";
$assoc_result = $conn->query($assoc_query);

if ($assoc_result && $assoc_result->num_rows > 0) {
    $associate = $assoc_result->fetch_assoc();
    $associate_name = $associate['FIRST'];
    $associate_name_last = $associate['LAST'];
    $current_commission = $associate['commission'];
} else {
    die("Associate not found.");
}

$legacy_conn = new mysqli('blitz.cs.niu.edu', 'student', 'student', 'csci467', 3306);
if ($legacy_conn->connect_error) {
    die("Connection to legacy DB failed: " . $legacy_conn->connect_error);
}

if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cust_id = $conn->real_escape_string($_POST['customer_id']);
    $email = $conn->real_escape_string($_POST['email']);
    $items = $conn->real_escape_string($_POST['items']);
    $prices = $conn->real_escape_string($_POST['prices']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $discount = floatval($_POST['discount']);

    // Calculate total and apply discount
    $item_prices = explode(",", $_POST['prices']);
    $total = 0;
    foreach ($item_prices as $price) {
        $total += floatval(trim($price));
    }
    $discounted_total = $total * (1 - $discount / 100);
    $total = number_format($discounted_total, 2, '.', '');

    // Generate quote_id (format: 3b-###-###)
    $quote_id = "3b-" . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT) . "-" . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

    // INSERT quote with generated quote_id
    $insert = "INSERT INTO Quote (quote_id, created_by, customer_email, items, item_prices, secret_notes, discount_percentage, total_amount, customer_id, customer_name, asc_name, asc_name_last, `status`)
               VALUES ('$quote_id', '{$_SESSION['userid']}', '$email', '$items', '$prices', '$notes', '$discount', '$total', $customer_id, '$customer_name', '$associate_name','$associate_name_last', 'unresolved')";

    if (isset($_POST['submit_quote'])) {
        $updateStatus = $conn->prepare("UPDATE Quote SET `status` = 'finalized' WHERE quote_id = ?");
        $updateStatus->bind_param("s", $quote_id);
        if ($updateStatus->execute()) {
            echo "<p style='font-weight:bold; color: green;'>Quote successfully submitted!</p>";
            exit();
        } else {
            echo "<p style='font-weight:bold; color: red;'>Error: " . $updateStatus->error . "</p>";
        }
        $updateStatus->close();
    }
    else if ($conn->query($insert) === TRUE) {
        echo "<p style='font-weight:bold;'>Quote successfully saved! Quote ID: $quote_id</p>";
    } else {
        echo "<p style='font-weight:bold;'>Error: " . $conn->error . "</p>";
    }

    // Preserve form values for redisplay
    $quote_items = $_POST['items'];
    $quote_item_prices = $_POST['prices'];
    $quote_discount = $_POST['discount'];

    $conn->close();
}

$legacy_conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Quote</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2 style="margin-bottom: 10px;">CREATING NEW QUOTE FOR: <?php echo htmlspecialchars($customer_name); ?></h2>

    <div style="line-height: 1.2; margin-bottom: 20px;">
        <?php echo htmlspecialchars($customer_street); ?><br>
        <?php echo htmlspecialchars($customer_city); ?><br>
        <?php echo htmlspecialchars($customer_contact); ?>
    </div>

    <form method="post" onsubmit="return prepare();">
        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer_id); ?>">

        <label for="email">Customer Email:</label><br>
        <input type="email" name="email" id="email" required style="margin-bottom: 15px; padding: 5px;"><br>

        <h3>Items</h3>

        <div style="display: flex; gap: 10px; font-weight: bold; margin-bottom: 5px;">
            <div style="width: 150px;">Item Name</div>
            <div style="width: 150px;">Price ($)</div>
        </div>

        <div id="items-container"></div>
        <button type="button" onclick="addItem()" style="margin: 10px 0;">+ New Item</button><br>

        <input type="hidden" name="items" id="items-hidden">
        <input type="hidden" name="prices" id="prices-hidden">

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Secret Notes:</div>
        <textarea name="notes" id="notes" rows="3" style="width: 300px; padding: 5px;"></textarea><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Discount (%):</div>
        <input type="number" name="discount" id="discount" step="0.01" value="0" oninput="calculateTotal()" style="padding: 5px;"><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Total Amount ($):</div>
        <div id="total-amount" style="font-weight: bold; font-size: 18px;">$0.00</div><br><br>

        <input type="submit" value="Save Quote" style="padding: 10px 20px; font-weight: bold;">
        <button type="button" onclick="submit_quote()" style="padding: 10px 20px; font-weight: bold;">Submit Quote</button>

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
                <input type="number" step="0.01" placeholder="Price" oninput="calculateTotal()" class="item-price" required style="padding: 5px;">
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
            const discountedTotal = total * (1 - discount / 100);
            document.getElementById("total-amount").textContent = `$${total.toFixed(2)}`;
        }

        function prepare() {
            const itemNames = [];
            const itemPrices = [];

            document.querySelectorAll("#items-container > div").forEach(row => {
                const name = row.querySelector(".item-name").value.trim();
                const price = row.querySelector(".item-price").value.trim();
                if (name !== "" && price !== "") {
                    itemNames.push(name);
                    itemPrices.push(price);
                }
            });

            document.getElementById("items-hidden").value = itemNames.join(",");
            document.getElementById("prices-hidden").value = itemPrices.join(",");

            calculateTotal();
            return true;
        }

        function submit_quote() {
            const form = document.querySelector('form');
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'submit_quote';
            form.appendChild(hiddenInput);
            form.submit();
        }

        calculateTotal();
    </script>
</body>
</html>
