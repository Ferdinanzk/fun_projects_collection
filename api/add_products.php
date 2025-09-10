<?php
// Include the configuration file, located in the same 'api' directory.
require_once 'config.php';

// Define variables and initialize with empty values
$product_name = $product_code = $category = $price = "";
$product_name_err = $product_code_err = $category_err = $price_err = $image_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate product name
    if (empty(trim($_POST["product_name"]))) {
        $product_name_err = "請輸入產品名稱。";
    } else {
        $product_name = trim($_POST["product_name"]);
    }

    // Validate product code
    if (empty(trim($_POST["product_code"]))) {
        $product_code_err = "請輸入產品代碼。";
    } else {
        // Check if product code is unique
        $sql = "SELECT id FROM products WHERE product_code = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_product_code);
            $param_product_code = trim($_POST["product_code"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $product_code_err = "此產品代碼已被使用。";
                } else {
                    $product_code = trim($_POST["product_code"]);
                }
            } else {
                echo "哎呀！出了些問題。請稍後再試。";
            }
            $stmt->close();
        }
    }

    // Validate category and price
    $category = trim($_POST["category"]);
    if (empty(trim($_POST["price"]))) {
        $price_err = "請輸入價格。";
    } elseif (!is_numeric($_POST["price"]) || $_POST["price"] < 0) {
        $price_err = "請輸入有效的價格。";
    } else {
        $price = trim($_POST["price"]);
    }

    // Validate and process image upload
    $image_path_for_db = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowed_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
        $file_name = $_FILES["image"]["name"];
        $file_type = $_FILES["image"]["type"];
        $file_size = $_FILES["image"]["size"];
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);

        if (!array_key_exists($ext, $allowed_types) || !in_array($file_type, $allowed_types)) {
            $image_err = "錯誤：請上傳有效的圖片格式 (JPG, PNG, GIF)。";
        } elseif ($file_size > 2 * 1024 * 1024) { // 2MB Max
            $image_err = "錯誤：檔案大小超過 2MB 的限制。";
        } else {
            // Create a unique filename to prevent overwriting
            $new_filename = uniqid() . "." . $ext;

            // IMPORTANT NOTE FOR VERCEL DEPLOYMENT:
            // Vercel's filesystem is ephemeral. This means files uploaded here will be DELETED
            // on the next deployment or when the serverless function recycles.
            // For a production app, you MUST use a cloud storage service like Cloudinary or AWS S3.
            // This code provides a temporary solution for testing.

            // The target directory is relative to this file's location in `api/`.
            // `../images/` points to the `images` folder in the project root.
            $target_dir = "../images/";
            $target_file = $target_dir . $new_filename;

            // Ensure the target directory exists.
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // The path stored in the database must be root-relative for web access.
                $image_path_for_db = "/images/" . $new_filename;
            } else {
                $image_err = "上傳圖片時發生錯誤。";
            }
        }
    }

    // Check input errors before inserting into database
    if (empty($product_name_err) && empty($product_code_err) && empty($price_err) && empty($image_err)) {
        $sql = "INSERT INTO products (product_name, product_code, category, price, image) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $product_name, $product_code, $category, $price, $image_path_for_db);
            if ($stmt->execute()) {
                // Redirect to the main page on success. Use root-relative path.
                header("location: /index.php");
                exit();
            } else {
                echo "儲存時發生錯誤，請再試一次。";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新增產品</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto max-w-2xl px-4 py-8">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">新增產品</h1>
            
            <!-- The form action points to the root-relative path -->
            <form action="/add_products.php" method="post" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700">產品名稱</label>
                    <input type="text" name="product_name" id="product_name" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($product_name_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" value="<?php echo htmlspecialchars($product_name); ?>">
                    <span class="text-xs text-red-500"><?php echo $product_name_err; ?></span>
                </div>
                <div>
                    <label for="product_code" class="block text-sm font-medium text-gray-700">產品代碼</label>
                    <input type="text" name="product_code" id="product_code" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($product_code_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" value="<?php echo htmlspecialchars($product_code); ?>">
                    <span class="text-xs text-red-500"><?php echo $product_code_err; ?></span>
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">分類</label>
                    <input type="text" name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" value="<?php echo htmlspecialchars($category); ?>">
                </div>
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">價格</label>
                    <input type="text" name="price" id="price" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($price_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" value="<?php echo htmlspecialchars($price); ?>">
                    <span class="text-xs text-red-500"><?php echo $price_err; ?></span>
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">產品圖片</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <span class="text-xs text-red-500"><?php echo $image_err; ?></span>
                </div>
                <div class="flex items-center justify-end space-x-4 pt-4">
                    <a href="/index.php" class="text-sm font-medium text-gray-600 hover:text-gray-900">取消</a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">儲存產品</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


