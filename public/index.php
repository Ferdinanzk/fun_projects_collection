<?php
// Start the session to handle messages across pages.
session_start();

// Define a base path for consistent file includes.
define('BASE_PATH', dirname(__DIR__));

// Include configuration and function files.
require_once BASE_PATH . '/functions/config.php';
require_once BASE_PATH . '/functions/functions.php';

// --- Simple Router ---
// Get the requested page from the URL, default to 'home'.
$page = $_GET['page'] ?? 'home';

// --- Page Rendering ---
// Include the shared header.
include(BASE_PATH . '/templates/header.php');

// Load the correct page template based on the router.
switch ($page) {
    case 'add_product':
        include(BASE_PATH . '/templates/add_product_form.php');
        break;
    
    case 'home':
    default:
        // For the homepage, fetch all products and load the product page template.
        $products = get_all_products($conn);
        include(BASE_PATH . '/templates/product_page.php');
        break;
}

// Include the shared footer.
include(BASE_PATH . '/templates/footer.php');

// Close the database connection.
$conn->close();
?>
