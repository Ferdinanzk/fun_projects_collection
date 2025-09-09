<?php
// Start the session to store status messages.
session_start();

// Define base path and include necessary files.
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/functions/config.php';
require_once BASE_PATH . '/functions/functions.php';

// --- Security Check: Ensure this is a POST request ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // If not a POST request, set an error message and redirect.
    $_SESSION['message'] = [
        'type' => 'error',
        'text' => '無效的請求方法。'
    ];
    header("Location: /index.php?page=add_product");
    exit;
}

// --- Process the form submission ---
// The handle_add_product_form function will do all the work and return a status message.
$result_message = handle_add_product_form($conn, $_POST, $_FILES);

// Store the result message in the session to be displayed on the next page.
$_SESSION['message'] = $result_message;

// --- Redirect ---
// If the product was added successfully, redirect to the homepage.
// Otherwise, redirect back to the add product form.
if ($result_message['type'] === 'success') {
    header("Location: /index.php?page=home");
} else {
    header("Location: /index.php?page=add_product");
}
exit;
?>

