<?php
// Include the configuration file, located in the same 'api' directory.
require_once 'config.php';

// Define variables and initialize with empty values
$product_name = $product_code = $category = $price = $current_image = "";
$product_name_err = $product_code_err = $price_err = $image_err = "";
$id = 0;

// Check if ID is present in the URL
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $id = trim($_GET["id"]);

    // Prepare a select statement
    $sql = "SELECT * FROM products WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $product_name = $row["product_name"];
                $product_code = $row["product_code"];
                $category = $row["category"];
                $price = $row["price"];
                $current_image = $row["image"];
            } else {
                // URL doesn't contain valid id. Redirect to error page or index.
                header("location: /index.php");
                exit();
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    $stmt->close();
} else {
    // URL doesn't contain id parameter. Redirect to error page or index.
    header("location: /index.php");
    exit();
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get hidden id value
    $id = $_POST["id"];

    // Validate product name
    if (empty(trim($_POST["product_name"]))) {
        $product_name_err = "請輸入產品名稱。";
    } else {
        $product_name = trim($_POST["product_name"]);
    }
    
    // Validate product code (check for uniqueness only if it has changed)
    if (empty(trim($_POST["product_code"]))) {
        $product_code_err = "請輸入產品代碼。";
    } elseif (trim($_POST["product_code"]) != $product_code) {
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
    } else {
        $product_code = trim($_POST["product_code"]);
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
    
    // Process image upload if a new file is provided
    $new_image_path = $current_image; // Default to old image
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowed_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
        $file_name = $_FILES["image"]["name"];
        $file_type = $_FILES["image"]["type"];
        $file_size = $_FILES["image"]["size"];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!array_key_exists($ext, $allowed_types) || !in_array($file_type, $allowed_types)) {
            $image_err = "錯誤：請上傳有效的圖片格式 (JPG, PNG, GIF)。";
        } elseif ($file_size > 2 * 1024 * 1024) { // 2MB Max
            $image_err = "錯誤：檔案大小超過 2MB 的限制。";
        } else {
            $new_filename = uniqid() . "." . $ext;
            $target_dir = "../images/";
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // If upload is successful, delete the old image if it exists
                if ($current_image && file_exists(".." . $current_image)) {
                    unlink(".." . $current_image);
                }
                $new_image_path = "/images/" . $new_filename;
            } else {
                $image_err = "上傳新圖片時發生錯誤。";
            }
        }
    }

    // Check input errors before updating the database
    if (empty($product_name_err) && empty($product_code_err) && empty($price_err) && empty($image_err)) {
        $sql = "UPDATE products SET product_name=?, product_code=?, category=?, price=?, image=? WHERE id=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssssi", $product_name, $product_code, $category, $price, $new_image_path, $id);
            if ($stmt->execute()) {
                // Records updated successfully. Redirect to landing page.
                header("location: /index.php");
                exit();
            } else {
                echo "更新時發生錯誤，請再試一次。";
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
    <title>編輯產品</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto max-w-2xl px-4 py-8">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">編輯產品</h1>
            
            <form action="/edit_product.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" class="space-y-6">
                <!-- Hidden input to store the product ID -->
                <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                
                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700">產品名稱</label>
                    <input type="text" name="product_name" id="product_name" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($product_name_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm" value="<?php echo htmlspecialchars($product_name); ?>">
                    <span class="text-xs text-red-500"><?php echo $product_name_err; ?></span>
                </div>
                <div>
                    <label for="product_code" class="block text-sm font-medium text-gray-700">產品代碼</label>
                    <input type="text" name="product_code" id="product_code" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($product_code_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm" value="<?php echo htmlspecialchars($product_code); ?>">
                    <span class="text-xs text-red-500"><?php echo $product_code_err; ?></span>
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">分類</label>
                    <input type="text" name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" value="<?php echo htmlspecialchars($category); ?>">
                </div>
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">價格</label>
                    <input type="text" name="price" id="price" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($price_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm" value="<?php echo htmlspecialchars($price); ?>">
                    <span class="text-xs text-red-500"><?php echo $price_err; ?></span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">目前圖片</label>
                    <?php if (!empty($current_image)): ?>
                        <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Current Image" class="mt-2 rounded-md border border-gray-200 h-32 w-auto">
                    <?php else: ?>
                        <p class="mt-2 text-sm text-gray-500">沒有圖片</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">上傳新圖片 (選填)</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">留空以保留目前圖片。</p>
                    <span class="text-xs text-red-500"><?php echo $image_err; ?></span>
                </div>

                <div class="flex items-center justify-end space-x-4 pt-4">
                    <a href="/index.php" class="text-sm font-medium text-gray-600 hover:text-gray-900">取消</a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">儲存變更</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>


