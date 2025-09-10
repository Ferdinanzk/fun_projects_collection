<?php
// Include the database config and connection file
require_once 'config.php';

// --- SEARCH & FILTER LOGIC ---

// Fetch all unique categories for the filter dropdown
$category_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$category_result = $conn->query($category_sql);

// Get search term and category from the URL
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Base SQL query
$sql = "SELECT id, product_name, category, price, image FROM products";

// Prepare conditions and parameters for the query to prevent SQL injection
$conditions = [];
$params = [];
$types = '';

if (!empty($search_term)) {
    $conditions[] = "product_name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= 's';
}

if (!empty($selected_category)) {
    $conditions[] = "category = ?";
    $params[] = $selected_category;
    $types .= 's';
}

// Append conditions to the base query if any exist
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY id DESC";

// Prepare and execute the statement
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
        /* Fallback for broken images */
        img {
            display: block;
            background-color: #f0f0f0;
            color: #aaa;
            text-align: center;
            line-height: 10rem; /* Center text vertically */
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-3 rtl:space-x-reverse">
                        <!-- Logo -->
                        <img src="logo.png" class="h-8 w-auto" alt="USR-小幫手 Logo">
                        <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800">USR-小幫手</span>
                    </a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-medium">首頁</a>
                        <a href="#" class="text-gray-500 hover:bg-gray-100 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">關於我們</a>
                        <a href="#" class="text-gray-500 hover:bg-gray-100 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">聯絡方式</a>
                    </div>
                </div>
                <!-- Mobile Menu Button -->
                <div class="-mr-2 flex md:hidden">
                    <button id="mobile-menu-button" type="button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu, show/hide based on menu state. -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php" class="bg-indigo-600 text-white block px-3 py-2 rounded-md text-base font-medium">首頁</a>
                <a href="#" class="text-gray-500 hover:bg-gray-100 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium">關於我們</a>
                <a href="#" class="text-gray-500 hover:bg-gray-100 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium">聯絡方式</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">我們的產品</h1>
            <a href="functions/add_products.php" class="w-full md:w-auto inline-block text-center bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                + 新增產品
            </a>
        </div>

        <!-- Search and Filter Form -->
        <form action="index.php" method="GET" class="mb-8 bg-white p-4 rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="search" class="sr-only">搜尋產品</label>
                    <input type="text" name="search" id="search" placeholder="依產品名稱搜尋..." value="<?php echo htmlspecialchars($search_term); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="category" class="sr-only">產品分類</label>
                    <select name="category" id="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">所有分類</option>
                        <?php if ($category_result->num_rows > 0): ?>
                            <?php while($cat_row = $category_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat_row['category']); ?>" <?php if ($selected_category == $cat_row['category']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat_row['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 text-right">
                <a href="index.php" class="text-gray-600 hover:text-gray-800 text-sm mr-4">清除</a>
                <button type="submit" class="bg-indigo-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">搜尋</button>
            </div>
        </form>

        <!-- Product Grid -->
        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                        <img src="<?php echo htmlspecialchars(str_replace('../', '', $row['image'])); ?>" 
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
                             <a href="functions/edit_product.php?id=<?php echo $row['id']; ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">編輯</a>
                             <a href="functions/delete_product.php?id=<?php echo $row['id']; ?>" 
                                class="text-sm font-medium text-red-600 hover:text-red-800 transition-colors"
                                onclick="return confirm('您確定要刪除「<?php echo htmlspecialchars(addslashes($row['product_name'])); ?>」嗎？此操作無法復原。');">刪除</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-lg shadow">
                <h2 class="text-xl font-semibold text-gray-700">找不到符合條件的產品。</h2>
                <p class="text-gray-500 mt-2">請試著調整您的搜尋關鍵字或分類。</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript for Mobile Menu -->
    <script>
        const btn = document.getElementById('mobile-menu-button');
        const menu = document.getElementById('mobile-menu');
        const icons = btn.querySelectorAll('svg');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
            icons[0].classList.toggle('hidden');
            icons[0].classList.toggle('block');
            icons[1].classList.toggle('hidden');
            icons[1].classList.toggle('block');
        });
    </script>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>


