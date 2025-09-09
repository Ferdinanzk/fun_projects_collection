<?php
session_start();

// Define the base path relative to this file's new location in /api
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/functions/config.php';
require_once BASE_PATH . '/functions/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['product_name']) || empty($_POST['product_code']) || !isset($_POST['price'])) {
        set_message('請填寫所有必填欄位。', 'error');
        header('Location: /?page=add_product');
        exit();
    }

    $product_name = $_POST['product_name'];
    $product_code = $_POST['product_code'];
    $category = $_POST['category'] ?? null;
    $price = $_POST['price'];
    $image_name = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // The upload directory is now referenced from the project root
        $upload_dir = BASE_PATH . '/public/images/';
        
        if (!is_dir($upload_dir)) {
            // Create the directory if it doesn't exist
            mkdir($upload_dir, 0777, true);
        }

        $image_name = handle_image_upload($_FILES['image'], $upload_dir);
        
        if ($image_name === false) {
            // Error message is set inside the function, redirect back to the form
            header('Location: /?page=add_product');
            exit();
        }
    }

    if (add_product($conn, $product_name, $product_code, $category, $price, $image_name)) {
        set_message('產品已成功新增！', 'success');
    } else {
        set_message('新增產品時發生錯誤。', 'error');
    }
}

// Redirect to the homepage after processing
header('Location: /');
exit();
?>
