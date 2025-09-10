<?php
// Include the database config and connection file, adjusting the path
require_once '../config.php';

// Check if the product ID is set in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = intval($_GET['id']); // Sanitize the ID

    // --- Step 1: Get the image path before deleting the record ---
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image'];

        // --- Step 2: Delete the product record from the database ---
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete_stmt->bind_param("i", $product_id);
        
        if ($delete_stmt->execute()) {
            // --- Step 3: If the database record was deleted, delete the image file ---
            if (!empty($image_path)) {
                // The path stored is relative to the 'functions' folder (e.g., ../images/file.jpg)
                // This path works correctly from this script's location.
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            // Redirect back to the main page with a success message (optional)
            header("Location: ../index.php?status=deleted");
            exit();
        } else {
            // Handle error
            header("Location: ../index.php?status=error");
            exit();
        }
        $delete_stmt->close();
    } else {
        // Product not found
        header("Location: ../index.php?status=notfound");
        exit();
    }
    $stmt->close();
} else {
    // Redirect if no ID is provided
    header("Location: ../index.php");
    exit();
}

$conn->close();
?>
