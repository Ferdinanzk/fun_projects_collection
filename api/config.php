<?php
/*
|--------------------------------------------------------------------------
| Vercel Database Configuration
|--------------------------------------------------------------------------
|
| This file is configured to work with Vercel's Environment Variables.
| You must set these variables in your Vercel project settings for the
| database connection to work.
|
| Required Environment Variables:
| - DB_SERVER:   Your database host (e.g., from PlanetScale, AWS RDS).
| - DB_USERNAME: Your database username.
| - DB_PASSWORD: Your database password.
| - DB_NAME:     Your database name.
|
*/

// Fetch credentials from Vercel's Environment Variables
$db_server   = getenv('DB_SERVER');
$db_username = getenv('DB_USERNAME');
$db_password = getenv('DB_PASSWORD');
$db_name     = getenv('DB_NAME');

// Create a new MySQLi connection object
$conn = new mysqli($db_server, $db_username, $db_password, $db_name);

// Check for a connection error
if ($conn->connect_error) {
    // In a production environment, it's better to log errors than display them.
    // For now, we'll stop the script and show the error.
    die("資料庫連線失敗 (Connection failed): " . $conn->connect_error);
}

// Set the character set to utf8mb4 to ensure proper handling of all characters
if (!$conn->set_charset("utf8mb4")) {
    // Log an error if the character set cannot be set.
    error_log("Error loading character set utf8mb4: " . $conn->error);
}

// The $conn object is now configured and ready to be used by any script that includes this file.

?>

