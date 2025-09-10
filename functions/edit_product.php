<?php
// Include the database config and connection file from the parent directory
require_once '../config.php';

$message = '';
$product = null;
$product_id = null;

// --- Handle GET request to fetch product data for the form ---
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        // Stop execution if no ID is provided in the URL
        die("錯誤：未提供產品ID。");
    }
    $product_id = intval($_GET['id']);

    // Use prepared statements to prevent SQL injection
    $sql = "SELECT id, product_name, product_code, category, price, image FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
    } else {
        die("錯誤：找不到ID為 {$product_id} 的產品。");
    }
    $stmt->close();
}

// --- Handle POST request to update the product ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $product_id = intval($_POST['id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $current_image = mysqli_real_escape_string($conn, $_POST['current_image']);
    $product_code = mysqli_real_escape_string($conn, $_POST['product_code']); // For re-display

    $image_path_for_db = $current_image; // Default to old image path

    // --- Handle Optional New File Upload ---
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "../images/";
        $image_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        // Use product code for a consistent naming scheme
        $unique_filename = $product_code . '_' . time() . '.' . $image_extension;
        $target_file = $target_dir . $unique_filename;

        // Validation checks for the new image
        $allowed_types = array("jpg", "jpeg", "png", "gif");
        if (!in_array($image_extension, $allowed_types)) {
            $message = "錯誤：只允許 JPG, JPEG, PNG 和 GIF 格式的檔案。";
        } elseif ($_FILES["image"]["size"] > 5000000) { // 5MB limit
            $message = "錯誤：您的檔案太大了。";
        } else {
            // Attempt to move the new file to the images directory
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path_for_db = $target_file;
                // If upload is successful, delete the old image if it exists and is not a placeholder
                if (!empty($current_image) && file_exists($current_image)) {
                    unlink($current_image);
                }
            } else {
                $message = "錯誤：上傳新檔案時發生問題。";
            }
        }
    }

    // --- Update Database Record (only if there was no upload error) ---
    if (empty($message)) {
        $sql = "UPDATE products SET product_name = ?, category = ?, price = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsi", $product_name, $category, $price, $image_path_for_db, $product_id);

        if ($stmt->execute()) {
            $message = "產品資訊已成功更新！";
        } else {
            $message = "錯誤：更新失敗。 " . $conn->error;
        }
        $stmt->close();
    }
    
    // Re-populate the $product variable to show the newly updated data in the form fields
    $product = [
        'id' => $product_id,
        'product_name' => $product_name,
        'product_code' => $product_code,
        'category' => $category,
        'price' => $price,
        'image' => $image_path_for_db
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯產品</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-lg bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">編輯產品</h1>
        
        <!-- Display Success or Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 text-sm rounded-lg <?php echo strpos($message, '錯誤') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
        <form action="edit_product.php" method="post" enctype="multipart/form-data" class="space-y-6">
            <!-- Hidden fields to pass the ID and current image path -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image']); ?>">
             <input type="hidden" name="product_code" value="<?php echo htmlspecialchars($product['product_code']); ?>">

            <div>
                <label for="product_name" class="block text-sm font-medium text-gray-700">產品名稱</label>
                <input type="text" id="product_name" name="product_name" required 
                       value="<?php echo htmlspecialchars($product['product_name']); ?>"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label for="product_code" class="block text-sm font-medium text-gray-700">產品代碼 (不可更改)</label>
                <input type="text" id="product_code" name="product_code_disabled" readonly
                       value="<?php echo htmlspecialchars($product['product_code']); ?>"
                       class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">類別</label>
                <input type="text" id="category" name="category"
                       value="<?php echo htmlspecialchars($product['category']); ?>"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label for="price" class="block text-sm font-medium text-gray-700">價格</label>
                <input type="number" id="price" name="price" step="0.01" required
                       value="<?php echo htmlspecialchars($product['price']); ?>"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
            </div>
            
            <div>
                 <label class="block text-sm font-medium text-gray-700">目前圖片</label>
                 <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="目前圖片" class="mt-2 w-32 h-32 object-cover rounded-md border border-gray-200"
                      onerror="this.onerror=null;this.src='https://placehold.co/128x128/e2e8f0/cbd5e0?text=沒有圖片';">
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700">上傳新圖片 (可選)</label>
                <input type="file" id="image" name="image" 
                       class="mt-1 block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100">
            </div>

            <div>
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    更新產品
                </button>
            </div>
        </form>
        <?php endif; ?>

         <div class="mt-4 text-center">
            <a href="../index.php" class="text-sm text-indigo-600 hover:text-indigo-500">返回產品列表</a>
        </div>
    </div>

</body>
</html>
