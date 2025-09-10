<?php
// Include the database configuration
require_once 'config.php';

// --- Fetch data for the form ---

// Fetch all users for the customer dropdown
$users = [];
$user_sql = "SELECT id, name, uid FROM users ORDER BY name";
if ($user_result = $conn->query($user_sql)) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch all product categories for the filter dropdown
$categories = [];
$category_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
if ($category_result = $conn->query($category_sql)) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// --- Build the product filter query ---
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';

$product_sql = "SELECT id, product_name, price, category FROM products";
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
    $product_sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$product_sql .= " ORDER BY product_name";

$stmt_products = $conn->prepare($product_sql);
if (!empty($params)) {
    $stmt_products->bind_param($types, ...$params);
}
$stmt_products->execute();
$product_result = $stmt_products->get_result();
$products = $product_result->fetch_all(MYSQLI_ASSOC);
$stmt_products->close();


// --- FORM SUBMISSION LOGIC ---
$error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'] ?? 0;
    $order_items_json = $_POST['order_items_json'] ?? '[]';
    $order_items = json_decode($order_items_json, true);
    $total_amount = 0;

    // Basic validation
    if (empty($user_id) || empty($order_items)) {
        $error_message = "請選擇一位顧客並至少添加一項產品。";
    } else {
        // Server-side recalculation of total
        $product_ids_to_fetch = array_column($order_items, 'id');
        if(!empty($product_ids_to_fetch)) {
            $product_prices = [];
            $in_clause = implode(',', array_fill(0, count($product_ids_to_fetch), '?'));
            $types = str_repeat('i', count($product_ids_to_fetch));

            $price_sql = "SELECT id, price FROM products WHERE id IN ($in_clause)";
            $stmt_prices = $conn->prepare($price_sql);
            $stmt_prices->bind_param($types, ...$product_ids_to_fetch);
            $stmt_prices->execute();
            $price_result = $stmt_prices->get_result();
            while($row = $price_result->fetch_assoc()) {
                $product_prices[$row['id']] = $row['price'];
            }
            $stmt_prices->close();

            foreach ($order_items as $item) {
                $total_amount += $product_prices[$item['id']] * $item['quantity'];
            }
        }
        
        // Use a transaction for data integrity
        $conn->begin_transaction();
        try {
            // Insert into 'orders' table with default 'Pending' status
            $order_sql = "INSERT INTO orders (user_id, total_amount) VALUES (?, ?)";
            $stmt_order = $conn->prepare($order_sql);
            $stmt_order->bind_param("id", $user_id, $total_amount);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            $stmt_order->close();

            // Insert into 'order_items' table
            $items_sql = "INSERT INTO order_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)";
            $stmt_items = $conn->prepare($items_sql);
            foreach ($order_items as $item) {
                $price_per_item = $product_prices[$item['id']];
                $stmt_items->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $price_per_item);
                $stmt_items->execute();
            }
            $stmt_items->close();

            $conn->commit();
            header("location: /view_orders.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "建立訂單時發生錯誤：" . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>建立新訂單</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; }
        .product-card { transition: all 0.2s ease-in-out; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Main Form -->
    <form action="/add_order.php" method="post" id="order-form">
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h1 class="text-3xl font-bold text-gray-800">建立新訂單</h1>
                <a href="/view_orders.php" class="text-sm font-medium text-gray-600 hover:text-gray-900">返回訂單列表</a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                  <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Product Selection -->
                <div class="lg:col-span-2">
                    <!-- Customer Selection -->
                    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                         <label for="user_id" class="block text-lg font-medium text-gray-700 mb-2">1. 選擇顧客</label>
                         <select name="user_id" id="user_id" required class="w-full md:w-2/3 border-gray-300 rounded-md shadow-sm text-base">
                            <option value="">-- 請選擇一位顧客 --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['uid']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Product Filters and List -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <label class="block text-lg font-medium text-gray-700 mb-4">2. 加入產品至訂單</label>
                        <!-- Filter Form -->
                        <form action="/add_order.php" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <input type="text" name="search" placeholder="搜尋產品名稱..." value="<?php echo htmlspecialchars($search_term); ?>" class="md:col-span-2 w-full border-gray-300 rounded-md shadow-sm">
                            <select name="category" onchange="this.form.submit()" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">所有分類</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($filter_category == $cat) echo 'selected'; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <!-- Product Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 max-h-[60vh] overflow-y-auto pr-2">
                             <?php foreach ($products as $product): ?>
                                <div class="product-card border rounded-lg p-3 flex flex-col items-start cursor-pointer" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <h3 class="font-semibold text-sm text-gray-800"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($product['category']); ?></p>
                                    <p class="mt-auto text-base font-bold text-indigo-600">$<?php echo htmlspecialchars($product['price']); ?></p>
                                </div>
                            <?php endforeach; ?>
                            <?php if(empty($products)): ?>
                                <p class="text-gray-500 md:col-span-3">找不到符合條件的產品。</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
                        <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-4">訂單摘要</h2>
                        <div id="cart-items" class="space-y-4 max-h-[50vh] overflow-y-auto pr-2">
                            <!-- Cart items will be injected here by JavaScript -->
                            <p id="empty-cart-message" class="text-gray-500">購物車是空的。</p>
                        </div>
                        <div class="border-t pt-4 mt-4">
                            <div class="flex justify-between items-center text-xl font-bold">
                                <span>總金額:</span>
                                <span id="total-amount" class="text-indigo-600">$0.00</span>
                            </div>
                        </div>
                        <div class="mt-6">
                             <!-- This hidden input will hold the final order data -->
                            <input type="hidden" name="order_items_json" id="order-items-json">
                            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg shadow-md hover:bg-indigo-700 text-lg disabled:bg-gray-400" disabled>
                                提交訂單
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cart = [];
            const cartItemsContainer = document.getElementById('cart-items');
            const totalAmountEl = document.getElementById('total-amount');
            const hiddenJsonInput = document.getElementById('order-items-json');
            const emptyCartMessage = document.getElementById('empty-cart-message');
            const submitButton = document.querySelector('button[type="submit"]');

            window.addToCart = function(product) {
                const existingProduct = cart.find(item => item.id === product.id);
                if (existingProduct) {
                    existingProduct.quantity++;
                } else {
                    cart.push({ ...product, quantity: 1 });
                }
                updateCart();
            };

            function updateCart() {
                cartItemsContainer.innerHTML = '';
                let total = 0;

                if (cart.length === 0) {
                    cartItemsContainer.appendChild(emptyCartMessage);
                } else {
                    cart.forEach((item, index) => {
                        total += item.price * item.quantity;
                        const itemEl = document.createElement('div');
                        itemEl.className = 'flex justify-between items-center';
                        itemEl.innerHTML = `
                            <div>
                                <p class="font-semibold">${item.product_name}</p>
                                <p class="text-sm text-gray-500">$${item.price}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="px-2 rounded bg-gray-200" onclick="changeQuantity(${index}, -1)">-</button>
                                <span>${item.quantity}</span>
                                <button type="button" class="px-2 rounded bg-gray-200" onclick="changeQuantity(${index}, 1)">+</button>
                            </div>
                        `;
                        cartItemsContainer.appendChild(itemEl);
                    });
                }
                
                totalAmountEl.textContent = '$' + total.toFixed(2);
                hiddenJsonInput.value = JSON.stringify(cart);
                submitButton.disabled = cart.length === 0;
            }

            window.changeQuantity = function(index, delta) {
                cart[index].quantity += delta;
                if (cart[index].quantity <= 0) {
                    cart.splice(index, 1);
                }
                updateCart();
            };
        });
    </script>
</body>
</html>

