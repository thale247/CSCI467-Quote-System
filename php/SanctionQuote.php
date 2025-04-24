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
//Set up quote fetch
if (isset($_GET['quote_id'])) {
    $quote_id = $_GET['quote_id'];
    $quote_query = "SELECT customer_email, items, item_prices, secret_notes, total_amount, status FROM Quote WHERE quote_id = '$quote_id'";
    $quote_result = $conn->query($quote_query);

    if ($quote_result && $quote_result->num_rows > 0) {
        $quote = $quote_result->fetch_assoc();
        $customer_email = $quote['customer_email'];
        $items = $quote['items'];
        $item_prices = $quote['item_prices'];
        $secret_notes = $quote['secret_notes'];
        $total_amount = $quote['total_amount'];
        $status = $quote['status'];
    } else {
        die("Quote not found.");
    }
} else {
    die("No quote selected.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {


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

    $insert = "INSERT INTO Quote (created_by, customer_email, items, item_prices, secret_notes, discount_percentage, total_amount, customer_id)
               VALUES ('{$_SESSION['userid']}', '$email', '$items', '$prices', '$notes', '$discount', '$total', $customer_id)";

    if ($conn->query($insert) === TRUE) {
        echo "<p style='font-weight:bold;'>Quote successfully submitted!</p>";
    } else {
        echo "<p style='font-weight:bold;'>Error: " . $conn->error . "</p>";
    }

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
    <h2 style="margin-bottom: 10px;">Order From: <?php echo htmlspecialchars($customer_name); ?></h2>

    <div style="line-height: 1.2; margin-bottom: 20px;">
        <?php echo htmlspecialchars($customer_street); ?><br>
        <?php echo htmlspecialchars($customer_city); ?><br>
        <?php echo htmlspecialchars($customer_contact); ?><br>
        <?php echo "Status: " . htmlspecialchars($quote['status']);?><br>
        <!-- we need to update this as soon as the whole payroll associate thing works-->
        <?php echo "Commission: ";?><br>
    </div>

    <form method="post" onsubmit="return prepare();">
        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer_id); ?>">

        <input type="email" value="<?php echo htmlspecialchars($customer_email); ?>" readonly 
         style="margin-bottom: 15px; padding: 5px; background-color: #eee; border: 1px solid #ccc;">


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
        <textarea name="notes" id="notes" rows="3" style="width: 300px; padding: 5px;"><?php echo htmlspecialchars($secret_notes);?></textarea><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Discount (%):</div>
        <input type="number" name="discount" id="discount" step="0.01" value="0" oninput="calculateTotal()" style="padding: 5px;"><br><br>

        <div style="font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px;">Total Amount ($):</div>
        <div id="total-amount" style="font-weight: bold; font-size: 18px;"><?php echo number_format($total_amount,2);?></div><br><br>

        <input type="submit" value="Finalize Quote" style="padding: 10px 20px; font-weight: bold;">
    </form>

    <script>
        function addItem() 
        {
            const container = document.getElementById("items-container");
            const row = document.createElement("div");
            row.style.display = "flex";
            row.style.gap = "10px";
            row.style.marginBottom = "10px";
            row.style.alignItems = "center";

            row.innerHTML = `
                <input type="text" placeholder="Item Name" class="item-name" required style="padding: 5px;">
                <input type="number" step="0.01" placeholder="Price" class="item-price" oninput="calculateTotal()" required style="padding: 5px;">
                <button type="button" onclick="removeItem(this)" style="background-color: black; color: white; border: none; padding: 4px 10px; font-weight: bold; font-size: 16px; cursor: pointer; line-height: 1;">X</button>
            `;
            container.appendChild(row);
        }

        function removeItem(button) 
        {
            button.parentElement.remove();
            calculateTotal();
        }

        function calculateTotal() 
        {
            const priceInputs = document.querySelectorAll(".item-price");
            let total = 0;
            priceInputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            const discount = parseFloat(document.getElementById("discount").value) || 0;
            const discountedTotal = total * (1 - discount / 100);
            document.getElementById("total-amount").textContent = `$${discountedTotal.toFixed(2)}`;
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

        calculateTotal();
    </script>
</body>
</html>
