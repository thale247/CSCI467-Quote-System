<?php
session_start();

// Check for user login
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Connect to legacy customer DB (for fetching customer details)
$legacy_conn = new mysqli('blitz.cs.niu.edu', 'student', 'student', 'csci467', 3306);
if ($legacy_conn->connect_error) {
    die("Connection to legacy DB failed: " . $legacy_conn->connect_error);
}

// Get customer info
// check for id from GET
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];

    //query to feth customer info from legacy db
    $customer_query = "SELECT name, city, street, contact FROM customers WHERE id = '$customer_id'";
    $customer_result = $legacy_conn->query($customer_query);

    // store customer info 
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

// Process form submission
// MANUAL LOCAL DATABASE CONNECTION I CANT FIGURE OUT WHAT TO DO SORRY
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to local db for quoteSystem
    $quote_db = new mysqli('71.228.20.16', 'connor', 'Hlb20!hello', 'quoteSystem');
    if ($quote_db->connect_error) {
        die("Quote DB connection failed: " . $quote_db->connect_error);
    }

    // Prepare for data injection
    $cust_id = $quote_db->real_escape_string($_POST['customer_id']);
    $email = $quote_db->real_escape_string($_POST['email']);
    $items = $quote_db->real_escape_string($_POST['items']);
    $prices = $quote_db->real_escape_string($_POST['prices']);
    $notes = $quote_db->real_escape_string($_POST['notes']);
    $discount = floatval($_POST['discount']);

    // calculate total amount
    $item_prices = explode(",", $_POST['prices']); 
    $total = 0;
    foreach ($item_prices as $price) {
        $total += floatval(trim($price));
    }

    // discounts
    $discounted_total = $total * (1 - $discount / 100);
    $total = number_format($discounted_total, 2, '.', '');

    // Insert into Quote table
    $insert = "INSERT INTO Quote (customer_email, items, item_prices, secret_notes, discount_percentage, total_amount)
               VALUES ('$email', '$items', '$prices', '$notes', '$discount', '$total')";

    // run query
    if ($quote_db->query($insert) === TRUE) {
        echo "<p><strong>Quote successfully submitted!</strong></p>";
    } else {
        echo "<p><strong>Error:</strong> " . $quote_db->error . "</p>";
    }

    // close database connection
    $quote_db->close();
}

// close legacy database
$legacy_conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Quote</title>
    <style>
        .address-info { line-height: 1.2; margin-bottom: 20px; }
        .item-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .item-row input { padding: 5px; }
        .item-labels { display: flex; gap: 10px; font-weight: bold; margin-bottom: 5px; }
        .item-labels div { width: 150px; }
        .remove-btn { background-color: black; color: white; border: none; padding: 4px 10px; font-weight: bold; font-size: 16px; cursor: pointer; line-height: 1; }
        .section-title { font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 5px; }
        .total-amount { font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <h2>CREATING NEW QUOTE FOR: <?php echo htmlspecialchars($customer_name); ?></h2>

    <!-- address and contact info from legacy DB -->
    <div class="address-info">
        <?php echo htmlspecialchars($customer_street); ?><br>
        <?php echo htmlspecialchars($customer_city); ?><br>
        <?php echo htmlspecialchars($customer_contact); ?>
    </div>

    <form method="post" onsubmit="return prepare();">
        <!-- Store customer ID -->
        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer_id); ?>">

         <!-- email input -->
        <label for="email">Customer Email:</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <h3>Items</h3>

        <div class="item-labels">
            <div>Item Name</div>
            <div>Price ($)</div>
        </div>
        <!-- dynamic item rows -->
        <div id="items-container"></div>
        <!-- Add item button -->
        <button type="button" onclick="addItem()">+ New Item</button><br><br>

        <!-- store item names and prices -->
        <input type="hidden" name="items" id="items-hidden">
        <input type="hidden" name="prices" id="prices-hidden">

        <!-- secret notes input -->
        <div class="section-title">Secret Notes:</div>
        <textarea name="notes" id="notes" rows="3"></textarea><br><br>

        <!-- discount input -->
        <div class="section-title">Discount (%):</div>
        <input type="number" name="discount" id="discount" step="0.01" value="0" oninput="calculateTotal()"><br><br>

        <!-- running amount total -->
        <div class="section-title">Total Amount ($):</div>
        <div id="total-amount" class="total-amount">$0.00</div><br><br>

        <!-- submit button -->
        <input type="submit" value="Submit Quote">
    </form>

    <script>
        // logic for dynamic item rows
        function addItem() 
        {
            const container = document.getElementById("items-container");
            const row = document.createElement("div");
            row.className = "item-row";
            row.innerHTML = `
                <input type="text" placeholder="Item Name" class="item-name" required>
                <input type="number" step="0.01" placeholder="Price" class="item-price" required>
                <button type="button" class="remove-btn" onclick="removeItem(this)">X</button>
            `;
            container.appendChild(row);
        }

        // x button to remove item and update total
        function removeItem(button) 
        {
            button.parentElement.remove();
            calculateTotal();
        }

        // function to calculate total price
        function calculateTotal() 
        {
            // add prices
            const priceInputs = document.querySelectorAll(".item-price");
            let total = 0;
            priceInputs.forEach(input => 
            {
                total += parseFloat(input.value) || 0;
            });

            // discounts
            const discount = parseFloat(document.getElementById("discount").value) || 0;
            const discountedTotal = total * (1 - discount / 100);
            document.getElementById("total-amount").textContent = `$${discountedTotal.toFixed(2)}`;
        }

        // prepare lists for submission
        function prepare() {
            const itemNames = [];
            const itemPrices = [];

            document.querySelectorAll(".item-row").forEach(row => {
                const name = row.querySelector(".item-name").value.trim();
                const price = row.querySelector(".item-price").value.trim();
                if (name !== "" && price !== "") {
                    itemNames.push(name);
                    itemPrices.push(price);
                }
            });

            // store for server-side processing
            document.getElementById("items-hidden").value = itemNames.join(",");
            document.getElementById("prices-hidden").value = itemPrices.join(",");

            // final calculation check
            calculateTotal();
            return true;
        }

        // Initial call
        calculateTotal();
    </script>
</body>
</html>
