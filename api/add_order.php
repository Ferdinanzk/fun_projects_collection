<?php
// Include the database configuration
require_once 'config.php';

// Fetch all users to populate the customer dropdown
$users = [];
$user_sql = "SELECT id, name, uid FROM users ORDER BY name";
if ($user_result = $conn->query($user_sql)) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch all products to create the order form
$products = [];
$product_sql = "SELECT id, product_name, price FROM products ORDER BY category, product_name";
if ($product_result = $conn->query($product_sql)) {
    while ($row = $product_result->fetch_assoc()) {
        $products[] = $row;
    }
}


// --- FORM SUBMISSION LOGIC ---
$error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'] ?? 0;
    $order_items = $_POST['products'] ?? [];
    $total_amount = 0;

    // Basic validation
    if (empty($user_id) || empty($order_items)) {
        $error_message = "請選擇一位顧客並至少添加一項產品。";
    } else {
        // Recalculate total amount on the server-side to ensure accuracy
        $product_prices = [];
        $product_ids_to_fetch = array_keys($order_items);
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

        foreach ($order_items as $product_id => $details) {
            $quantity = (int)$details['quantity'];
            if ($quantity > 0 && isset($product_prices[$product_id])) {
                $total_amount += $product_prices[$product_id] * $quantity;
            }
        }
        
        // Use a transaction to ensure data integrity
        $conn->begin_transaction();
        try {
            // 1. Insert into 'orders' table (Status will now be 'Pending' by default)
            $order_sql = "INSERT INTO orders (user_id, total_amount) VALUES (?, ?)";
            $stmt_order = $conn->prepare($order_sql);
            $stmt_order->bind_param("id", $user_id, $total_amount);
            $stmt_order->execute();
            $order_id = $conn->insert_id; // Get the ID of the new order
            $stmt_order->close();

            // 2. Insert into 'order_items' table
            $items_sql = "INSERT INTO order_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)";
            $stmt_items = $conn->prepare($items_sql);

            foreach ($order_items as $product_id => $details) {
                $quantity = (int)$details['quantity'];
                if ($quantity > 0 && isset($product_prices[$product_id])) {
                    $price_per_item = $product_prices[$product_id];
                    $stmt_items->bind_param("iiid", $order_id, $product_id, $quantity, $price_per_item);
                    $stmt_items->execute();
                }
            }
            $stmt_items->close();

            // If everything is successful, commit the transaction
            $conn->commit();
            
            // Redirect to the order list page to see the new pending order
            header("location: /view_orders.php");
            exit();

        } catch (Exception $e) {
            // If any part fails, roll back the entire transaction
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
        .product-row:hover { background-color: #f9fafb; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto max-w-4xl px-4 py-8">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">建立新訂單</h1>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                  <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>

            <form action="/add_order.php" method="post" id="order-form">
                <!-- Customer Selection -->
                <div class="mb-6">
                    <label for="user_id" class="block text-lg font-medium text-gray-700 mb-2">顧客</label>
                    <select name="user_id" id="user_id" required class="w-full md:w-1/2 border-gray-300 rounded-md shadow-sm text-lg">
                        <option value="">-- 選擇一位顧客 --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['uid']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Product List -->
                <div class="mb-6">
                    <h2 class="text-lg font-medium text-gray-700 mb-2">產品列表</h2>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">產品名稱</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">單價</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">數量</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
                                    <tr class="product-row" data-price="<?php echo $product['price']; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo htmlspecialchars($product['price']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <input type="number" name="products[<?php echo $product['id']; ?>][quantity]" class="w-24 border-gray-300 rounded-md shadow-sm quantity-input" min="0" placeholder="0">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Summary and Submission -->
                <div class="flex items-center justify-between mt-8 pt-4 border-t">
                    <div>
                        <span class="text-xl font-medium text-gray-700">總金額:</span>
                        <span id="total-amount" class="text-2xl font-bold text-indigo-600">$0.00</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/index.php" class="text-sm font-medium text-gray-600 hover:text-gray-900">取消</a>
                        <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-lg font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            提交訂單
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('order-form');
            const totalAmountEl = document.getElementById('total-amount');

            form.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity-input')) {
                    updateTotal();
                }
            });

            function updateTotal() {
                let total = 0;
                const quantityInputs = form.querySelectorAll('.quantity-input');
                
                quantityInputs.forEach(input => {
                    const quantity = parseInt(input.value, 10) || 0;
                    if (quantity > 0) {
                        const row = input.closest('.product-row');
                        const price = parseFloat(row.dataset.price);
                        total += quantity * price;
                    }
                });
                
                totalAmountEl.textContent = '$' + total.toFixed(2);
            }
        });
    </script>
</body>
</html>

