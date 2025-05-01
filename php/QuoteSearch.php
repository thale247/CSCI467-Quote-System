<?php
session_start();
include('../includes/db_connect.php');

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$asc = $_GET['asc'] ?? '';
$customer = $_GET['customer'] ?? '';

$query = "SELECT * FROM Quote WHERE 1=1";

if ($status !== '') {
    $query .= " AND status = '" . $conn->real_escape_string($status) . "'";
}
if ($start_date && $end_date) {
    $query .= " AND created BETWEEN '$start_date' AND '$end_date'";
}
if ($asc !== '') {
    $query .= " AND asc_name LIKE '%" . $conn->real_escape_string($asc) . "%'";
}
if ($customer !== '') {
    $query .= " AND customer_name LIKE '%" . $conn->real_escape_string($customer) . "%'";
}

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quote Search</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        th { background: #eee; }
        input, select { padding: 5px; margin: 5px; }
        .search-form { margin-bottom: 20px; background: #fff; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
<h2>Search Quotes</h2>
<form class="search-form" method="get">
    <label>Status:</label>
    <select name="status">
        <option value="">-- All --</option>
        <option value="finalized" <?= $status == 'finalized' ? 'selected' : '' ?>>Finalized</option>
        <option value="sanctioned" <?= $status == 'sanctioned' ? 'selected' : '' ?>>Sanctioned</option>
        <option value="ordered" <?= $status == 'ordered' ? 'selected' : '' ?>>Ordered</option>
    </select>
    <label>Date Range:</label>
    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
    <label>Associate:</label>
    <input type="text" name="asc" value="<?= htmlspecialchars($asc) ?>">
    <label>Customer:</label>
    <input type="text" name="customer" value="<?= htmlspecialchars($customer) ?>">
    <input type="submit" value="Search">
</form>

<table>
    <thead>
        <tr>
            <th>Quote ID</th>
            <th>Status</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Associate</th>
            <th>Total ($)</th>
            <th>Discount (%)</th>
            <th>Commission</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['quote_id']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['created']) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><?= htmlspecialchars($row['asc_name']) . ' ' . htmlspecialchars($row['asc_name_last']) ?></td>
                    <td>$<?= number_format($row['total_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['discount_percentage']) ?>%</td>
                    <td>$<?= number_format($row['commission'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No quotes found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
<?php $conn->close(); ?>
