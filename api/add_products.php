<?php
// Include your database configuration
require_once 'config.php';
// Include the Composer autoloader to use the AWS SDK.
// The path goes up one level from 'api' to the root where the 'vendor' folder is.
require_once __DIR__ . '/../vendor/autoload.php';

// Import the necessary classes from the AWS SDK
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// --- S3 CLIENT SETUP ---
// This part will only work if you have set the environment variables in Vercel
$s3Client = null;
if (getenv('S3_BUCKET_NAME')) {
    try {
        $s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => getenv('AWS_REGION'),
            'credentials' => [
                'key'    => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    } catch (Exception $e) {
        // This will help debug if the SDK itself has an issue
        die("Error creating S3 Client: " . $e->getMessage());
    }
}
// --- END S3 CLIENT SETUP ---

// Initialize variables
$product_name = $product_code = $category = $price = "";
$product_name_err = $product_code_err = $price_err = $image_err = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- (Validation for product name, code, price remains the same) ---
    if (empty(trim($_POST["product_name"]))) {
        $product_name_err = "請輸入產品名稱。";
    } else {
        $product_name = trim($_POST["product_name"]);
    }
    
    if (empty(trim($_POST["product_code"]))) {
        $product_code_err = "請輸入產品代碼。";
    } else {
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
    
    $category = trim($_POST["category"]);
    if (empty(trim($_POST["price"]))) {
        $price_err = "請輸入價格。";
    } elseif (!is_numeric($_POST["price"]) || $_POST["price"] < 0) {
        $price_err = "請輸入有效的價格。";
    } else {
        $price = trim($_POST["price"]);
    }
    
    // --- AWS S3 IMAGE UPLOAD LOGIC ---
    $image_path_to_save = ""; // This will store the final S3 URL
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        if ($s3Client) {
            $bucket = getenv('S3_BUCKET_NAME');
            $file_tmp_path = $_FILES['image']['tmp_name'];
            $file_name = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            // Create a unique filename to avoid overwrites
            $key = 'product-images/' . uniqid('', true) . '.' . $ext;

            try {
                // Upload the file to S3
                $result = $s3Client->putObject([
                    'Bucket'     => $bucket,
                    'Key'        => $key,
                    'SourceFile' => $file_tmp_path,
                    'ACL'        => 'public-read', // Make the file publicly readable
                ]);
                // Get the public URL of the uploaded file
                $image_path_to_save = $result['ObjectURL'];
            } catch (AwsException $e) {
                $image_err = "圖片上傳至S3時發生錯誤: " . $e->getMessage();
            }
        } else {
            $image_err = "S3 未正確設定，無法上傳圖片。";
        }
    } elseif (isset($_FILES["image"]) && $_FILES["image"]["error"] != UPLOAD_ERR_NO_FILE) {
         $image_err = "圖片上傳時發生錯誤。";
    }
    // --- END AWS S3 UPLOAD LOGIC ---

    // If there are no errors, insert into the database
    if (empty($product_name_err) && empty($product_code_err) && empty($price_err) && empty($image_err)) {
        $sql = "INSERT INTO products (product_name, product_code, category, price, image) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $product_name, $product_code, $category, $price, $image_path_to_save);
            if ($stmt->execute()) {
                // Redirect to the product list on success
                header("location: /index.php");
                exit();
            } else {
                echo "哎呀！出了些問題。請稍後再試。";
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
            
            <?php // Display a warning if S3 is not configured
            if(empty(getenv('S3_BUCKET_NAME'))): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                  <p class="font-bold">設定錯誤</p>
                  <p>AWS S3 環境變數未在 Vercel 中設定。圖片上傳將無法運作。</p>
                </div>
            <?php endif; ?>

            <form action="/add_products.php" method="post" enctype="multipart/form-data" class="space-y-6">
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
                    <label for="image" class="block text-sm font-medium text-gray-700">產品圖片</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <span class="text-xs text-red-500"><?php echo $image_err; ?></span>
                </div>
                <div class="flex items-center justify-end space-x-4 pt-4">
                    <a href="/index.php" class="text-sm font-medium text-gray-600 hover:text-gray-900">取消</a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">儲存產品</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

