<?php
// --- Error Reporting (for development) ---
// Show all errors to help with debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Database Credentials ---
// IMPORTANT: For production, use environment variables instead of hardcoding credentials.
$servername = getenv('DB_SERVERNAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_DBNAME');

// --- Establish Database Connection ---
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Set character set to UTF-8 for proper Traditional Chinese support.
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // If connection fails, show a generic error message and stop the script.
    // In a real application, you might log the detailed error ($e->getMessage()).
    die("<div style='padding:20px; font-family:sans-serif; background-color:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px;'>資料庫連接失敗。請稍後再試。</div>");
}
?>
