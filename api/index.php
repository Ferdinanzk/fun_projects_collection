<?php
session_start();

// Define a base path constant for reliable file includes
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/functions/config.php';
require_once BASE_PATH . '/functions/functions.php';

// Simple Router
$page = $_GET['page'] ?? 'home';

// Fetch all products for the main page
$products = get_all_products($conn);

// Load the header template
include BASE_PATH . '/templates/header.php';

// Load the appropriate page content based on the router
switch ($page) {
    case 'add_product':
        include BASE_PATH . '/templates/add_product_form.php';
        break;
    case 'home':
    default:
        include BASE_PATH . '/templates/product_page.php';
        break;
}

// Load the footer template
include BASE_PATH . '/templates/footer.php';

// Close the database connection
$conn->close();
?>
