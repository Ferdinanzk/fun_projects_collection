<?php
// Include the configuration file, located in the same 'api' directory.
require_once 'config.php';

// Check if the ID parameter is set and is not empty
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // Get the product ID from the URL
    $id = trim($_GET["id"]);

    // --- Step 1: Get the image path before deleting the record ---
    $image_path = '';
    $sql_select = "SELECT image FROM products WHERE id = ?";
    if ($stmt_select = $conn->prepare($sql_select)) {
        $stmt_select->bind_param("i", $id);
        if ($stmt_select->execute()) {
            $result = $stmt_select->get_result();
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $image_path = $row['image'];
            }
        }
        $stmt_select->close();
    }

    // --- Step 2: Prepare a delete statement for the database record ---
    $sql_delete = "DELETE FROM products WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        // Bind variables to the prepared statement as parameters
        $stmt_delete->bind_param("i", $id);

        // Attempt to execute the prepared statement
        if ($stmt_delete->execute()) {
            // --- Step 3: If database deletion is successful, delete the image file ---
            // The path in the DB is like '/images/file.jpg', so we need to go to the parent dir `..`
            if (!empty($image_path) && file_exists(".." . $image_path)) {
                unlink(".." . $image_path);
            }
            
            // Product deleted successfully. Redirect to the main page.
            header("location: /index.php");
            exit();
        } else {
            echo "哎呀！刪除時發生錯誤。請稍後再試。";
        }
        $stmt_delete->close();
    }
    
    // Close connection
    $conn->close();

} else {
    // If ID is not present in URL, redirect to the main page
    header("location: /index.php");
    exit();
}
?>

