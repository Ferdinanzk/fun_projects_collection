<?php
// Include the database config and connection file
require_once 'config.php';

// --- Fetch Categories for Dropdown ---
$categories = [];
$category_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
if ($category_result = $conn->query($category_sql)) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
// --- END Fetch Categories ---


// --- Build the Search and Filter Query ---
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';

$sql = "SELECT id, product_name, category, price, image FROM products";
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_term)) {
    $where_clauses[] = "product_name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= "s";
}
if (!empty($filter_category)) {
    $where_clauses[] = "category = ?";
    $params[] = $filter_category;
    $types .= "s";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
// --- END Build Query ---
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
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/index.php" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <img src="/logo.png" class="h-8" alt="USR Logo">
                    <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800">USR-小幫手</span>
                </a>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/index.php" class="bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-medium">首頁</a>
                        <a href="#" class="text-gray-500 hover:bg-gray-100 px-3 py-2 rounded-md text-sm font-medium">關於</a>
                        <a href="#" class="text-gray-500 hover:bg-gray-100 px-3 py-2 rounded-md text-sm font-medium">聯絡我們</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">我們的產品</h1>
             <a href="/add_products.php" class="w-full md:w-auto inline-block text-center bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-indigo-700">
                + 新增產品
            </a>
        </div>
        
        <!-- Search and Filter Form -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-8">
            <form action="/index.php" method="get" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 items-center">
                <div class="md:col-span-2 lg:col-span-2">
                    <label for="search" class="sr-only">搜尋產品</label>
                    <input type="text" name="search" id="search" placeholder="依產品名稱搜尋..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="category" class="sr-only">分類</label>
                    <select name="category" id="category" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">所有分類</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($filter_category == $cat) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                     <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-indigo-700">搜尋</button>
                     <a href="/index.php" class="w-full text-center bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-gray-300">重設</a>
                </div>
            </form>
        </div>


        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                        <img src="<?php echo htmlspecialchars($row['image'] ?: 'https://placehold.co/400x300/e2e8f0/cbd5e0?text=No+Image'); ?>" 
                             alt="<?php echo htmlspecialchars($row['product_name']); ?>" 
                             class="w-full h-48 object-cover">
                        <div class="p-6 flex-grow flex flex-col">
                            <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['product_name']); ?></h2>
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($row['category']); ?></p>
                            <div class="text-2xl font-extrabold text-indigo-600 mt-auto">
                                $<?php echo htmlspecialchars($row['price']); ?>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
                             <a href="/edit_product.php?id=<?php echo $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">編輯</a>
                            <span class="text-gray-300">|</span>
                            <!-- CORRECTED DELETE FORM -->
                            <form action="/delete_product.php" method="post" onsubmit="return confirm('您確定要刪除此產品嗎？');" class="inline">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900 font-medium">刪除</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-lg shadow-md">
                <p class="text-gray-500">找不到產品。</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

