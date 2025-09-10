<?php
// --- Database Configuration & Connection ---

// --- Credentials ---
$servername = "13.230.122.24"; // Or "localhost" if running locally
$username = "root"; // Your database username
$password = "new_secure_password"; // Your database password
$dbname = "fun_project"; // The database name from your .sql file

// --- Create Connection ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Check Connection ---
if ($conn->connect_error) {
    // If connection fails, stop the script and display an error.
    die("Connection failed: " . $conn->connect_error);
}
?>
