<?php
// Get database credentials from environment variables
include('../includes/db_connect.php');

// SQL query to get all data from the Person table
$sql = "SELECT * FROM Person";
$result = $conn->query($sql);

// Check if there are any results
if ($result->num_rows > 0) {
    // Output data for each row
    echo "<table border='1'>";
    echo "<tr><th>Name</th></tr>"; // Display only the name column
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["name"] . "</td>"; // Display the name column
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "0 results found.";
}

// Close the connection
$conn->close();
?>
