<?php
// Get database credentials from environment variables
$host = getenv('DB_HOST'); // Access DB host from environment variable
$username = getenv('DB_USER'); // Access DB username from environment variable
$password = getenv('DB_PASS'); // Access DB password from environment variable
$database = 'Associates'; // Access DB name from environment variable

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
