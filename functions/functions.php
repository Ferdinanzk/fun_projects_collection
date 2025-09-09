<?php

/**
 * Fetches all products from the database.
 * @param mysqli $conn The database connection object.
 * @return array An array of products or an empty array on failure.
 */
function get_all_products(mysqli $conn): array {
    $sql = "SELECT id, product_name, product_code, category, price, image FROM products";
    $result = $conn->query($sql);
    
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

/**
 * Handles the logic for adding a new product, including file upload.
 * @param mysqli $conn The database connection object.
 * @param array $post The $_POST superglobal.
 * @param array $files The $_FILES superglobal.
 * @return array A status message with 'type' (success/error) and 'text'.
 */
function handle_add_product_form(mysqli $conn, array $post, array $files): array {
    // --- 1. Get and Sanitize Text Data ---
    $product_name = trim($post['product_name'] ?? '');
    $product_code = trim($post['product_code'] ?? '');
    $category = trim($post['category'] ?? '');
    $price = trim($post['price'] ?? '');
    $image_filename = null;

    // --- 2. Validate Required Fields ---
    if (empty($product_name) || empty($product_code) || empty($price)) {
        return ['type' => 'error', 'text' => '請填寫所有必填欄位。'];
    }

    // --- 3. Handle Image Upload ---
    if (isset($files["image"]) && $files["image"]["error"] == 0) {
        $target_dir = BASE_PATH . "/public/images/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $unique_prefix = bin2hex(random_bytes(8));
        $original_filename = basename($files["image"]["name"]);
        $target_file = $target_dir . $unique_prefix . "_" . $original_filename;
        $image_filename_to_save = $unique_prefix . "_" . $original_filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Basic security checks
        if (getimagesize($files["image"]["tmp_name"]) === false) {
            return ['type' => 'error', 'text' => '檔案不是有效的圖片。'];
        }
        if ($files["image"]["size"] > 5000000) { // 5MB limit
            return ['type' => 'error', 'text' => '抱歉，您的檔案太大了。上限為 5MB。'];
        }
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            return ['type' => 'error', 'text' => '抱歉，僅允許 JPG, JPEG, PNG & GIF 格式的檔案。'];
        }

        // Move the uploaded file
        if (move_uploaded_file($files["image"]["tmp_name"], $target_file)) {
            $image_filename = $image_filename_to_save;
        } else {
            return ['type' => 'error', 'text' => '抱歉，上傳您的檔案時發生錯誤。'];
        }
    }

    // --- 4. Insert into Database ---
    $stmt = $conn->prepare("INSERT INTO products (product_name, product_code, category, price, image) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        return ['type' => 'error', 'text' => '資料庫準備錯誤。'];
    }
    
    $stmt->bind_param("sssds", $product_name, $product_code, $category, $price, $image_filename);

    if ($stmt->execute()) {
        $stmt->close();
        return ['type' => 'success', 'text' => '產品已成功新增！'];
    } else {
        $error_message = htmlspecialchars($stmt->error);
        $stmt->close();
        return ['type' => 'error', 'text' => '資料庫錯誤：' . $error_message];
    }
}
?>
