<?php
// Include the database config and connection file from the parent directory
require_once '../config.php';

// Initialize a variable to hold messages
$message = '';

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Retrieve and Sanitize Form Data ---
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_code = mysqli_real_escape_string($conn, $_POST['product_code']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    
    $image_path = null;

    // --- Handle File Upload ---
    // Check if a file was uploaded without errors
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        // The target directory is relative to the root, so ../images/ is correct
        $target_dir = "../images/"; // IMPORTANT: This directory must exist and be writable!
        
        // Create a unique filename to prevent overwriting existing files
        $image_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $unique_filename = $product_code . '_' . time() . '.' . $image_extension;
        $target_file = $target_dir . $unique_filename;

        // --- Validation Checks ---
        $allowed_types = array("jpg", "jpeg", "png", "gif");
        if (!in_array($image_extension, $allowed_types)) {
            $message = "錯誤：只允許 JPG, JPEG, PNG 和 GIF 格式的檔案。";
        } elseif ($_FILES["image"]["size"] > 5000000) { // Check file size (e.g., 5MB)
            $message = "錯誤：您的檔案太大了。";
        } else {
            // Try to move the uploaded file to the target directory
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file; // Set the path to be saved in the database
            } else {
                $message = "錯誤：上傳檔案時發生問題。";
            }
        }
    } else {
        $message = "注意：未上傳圖片或上傳過程中發生錯誤。";
    }

    // --- Insert into Database ---
    // Only proceed if there were no major errors with the file upload
    if (empty($message) || $image_path !== null) {
        // SQL query to insert data. Using NULL for image if no path was set.
        $sql = "INSERT INTO products (product_name, product_code, category, price, image) 
                VALUES ('$product_name', '$product_code', '$category', '$price', '$image_path')";

        if ($conn->query($sql) === TRUE) {
            $message = "新產品已成功新增！";
        } else {
            // Check for duplicate product_code error
            if ($conn->errno == 1062) { // 1062 is the MySQL error code for duplicate entry
                 $message = "錯誤：具有此產品代碼的產品已存在。";
            } else {
                 $message = "錯誤： " . $sql . "<br>" . $conn->error;
            }
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增產品</title>
    <!-- Using Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-lg bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">新增產品</h1>
        
        <!-- Display Success or Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 text-sm rounded-lg <?php echo strpos($message, '錯誤') !== false || strpos($message, 'Error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- The 'enctype' is crucial for file uploads. The action points to this file itself. -->
        <form action="add_products.php" method="post" enctype="multipart/form-data" class="space-y-6">
            
            <div>
                <label for="product_name" class="block text-sm font-medium text-gray-700">產品名稱</label>
                <input type="text" id="product_name" name="product_name" required 
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="product_code" class="block text-sm font-medium text-gray-700">產品代碼</label>
                <input type="text" id="product_code" name="product_code" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">類別</label>
                <input type="text" id="category" name="category"
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="price" class="block text-sm font-medium text-gray-700">價格</label>
                <input type="number" id="price" name="price" step="0.01" required
                       class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="image" class="block text-sm font-medium text-gray-700">產品圖片</label>
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
                    新增產品
                </button>
            </div>
        </form>
         <div class="mt-4 text-center">
            <!-- Link back to the index file in the parent directory -->
            <a href="../index.php" class="text-sm text-indigo-600 hover:text-indigo-500">查看所有產品</a>
        </div>
    </div>

</body>
</html>

