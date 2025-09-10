<?php
// Include the database config and connection file, which is in the same 'api' directory.
require_once 'config.php';

// --- Fetch unique categories for the filter dropdown ---
$categories = [];
$category_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$category_result = $conn->query($category_sql);
if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// --- Build the main product query based on search and filter ---
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

$sql = "SELECT id, product_name, category, price, image FROM products";
$conditions = [];
$params = [];
$types = '';

// Add search condition if a search term is provided
if (!empty($search_term)) {
    $conditions[] = "product_name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= 's';
}

// Add category filter condition if a category is selected
if (!empty($selected_category)) {
    $conditions[] = "category = ?";
    $params[] = $selected_category;
    $types .= 's';
}

// Append conditions to the SQL query
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY id DESC";

// Prepare and execute the statement to prevent SQL injection
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>產品列表</title>
    <!-- Using Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <!-- Vercel serves files from the root, so the path is /logo.png -->
                    <img src="/logo.png" class="h-8 w-auto" alt="USR-小幫手 Logo">
                    <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800">USR-小幫手</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">我們的產品</h1>
            <!-- Link is root-relative for Vercel routing -->
            <a href="/add_products.php" class="inline-block bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                + 新增產品
            </a>
        </div>

        <!-- Search and Filter Form -->
        <form action="/" method="GET" class="mb-8 bg-white p-4 rounded-lg shadow">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <label for="search" class="sr-only">搜尋產品</label>
                    <input type="text" name="search" id="search" placeholder="依產品名稱搜尋..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="w-full md:w-1/3">
                    <label for="category" class="sr-only">篩選分類</label>
                    <select name="category" id="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">所有分類</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($selected_category === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md hover:bg-indigo-700">搜尋</button>
                </div>
            </div>
        </form>

        <!-- Product Grid -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                        <img src="<?php echo htmlspecialchars($row['image']); ?>" 
                             alt="<?php echo htmlspecialchars($row['product_name']); ?>" 
                             class="w-full h-40 object-cover"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x300/e2e8f0/cbd5e0?text=沒有圖片';">
                        <div class="p-6 flex-grow">
                            <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['product_name']); ?></h2>
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($row['category']); ?></p>
                            <div class="text-2xl font-extrabold text-indigo-600">
                                $<?php echo htmlspecialchars($row['price']); ?>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-between items-center">
                             <a href="/edit_product.php?id=<?php echo $row['id']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">編輯</a>
                             <a href="/delete_product.php?id=<?php echo $row['id']; ?>" 
                                class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors"
                                onclick="return confirm('您確定要刪除「<?php echo htmlspecialchars(addslashes($row['product_name'])); ?>」嗎？此操作無法復原。');">刪除</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-lg shadow">
                <h2 class="text-xl font-semibold text-gray-700">找不到產品</h2>
                <p class="text-gray-500 mt-2">請嘗試調整您的搜尋或篩選條件。</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>


