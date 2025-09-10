<?php
// Include the database configuration
require_once 'config.php';

// Check if an order ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: /view_orders.php");
    exit();
}
$order_id = trim($_GET['id']);

// --- FORM SUBMISSION: UPDATE ORDER STATUS ---
$success_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['order_status'];
    
    $update_sql = "UPDATE orders SET order_status = ? WHERE id = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $success_message = "訂單狀態已成功更新！";
        } else {
            $error_message = "更新失敗，請再試一次。";
        }
        $stmt->close();
    }
}


// --- FETCH ORDER DETAILS ---
$order = null;
$order_items = [];

// Fetch main order details (join with users)
$order_sql = "SELECT o.id, o.total_amount, o.order_status, DATE_FORMAT(o.order_date, '%Y-%m-%d %H:%i') as formatted_date, u.name as customer_name 
              FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
if ($stmt_order = $conn->prepare($order_sql)) {
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $result = $stmt_order->get_result();
    if ($result->num_rows == 1) {
        $order = $result->fetch_assoc();
    } else {
        // No order found with this ID
        header("location: /view_orders.php");
        exit();
    }
    $stmt_order->close();
}

// Fetch items for this order (join with products)
$items_sql = "SELECT oi.quantity, oi.price_per_item, p.product_name, p.image 
              FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
if ($stmt_items = $conn->prepare($items_sql)) {
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();
}

$conn->close();

$possible_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯訂單 #<?php echo $order['id']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto max-w-4xl px-4 py-8">
        <div class="bg-white p-8 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h1 class="text-3xl font-bold text-gray-800">訂單詳情 <span class="text-indigo-600">#<?php echo htmlspecialchars($order['id']); ?></span></h1>
                <a href="/view_orders.php" class="text-sm font-medium text-gray-600 hover:text-gray-900">返回訂單列表</a>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                  <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">顧客資訊</h2>
                    <p><span class="font-medium text-gray-600">姓名:</span> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><span class="font-medium text-gray-600">訂單日期:</span> <?php echo htmlspecialchars($order['formatted_date']); ?></p>
                </div>
                <div class="md:text-right">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">訂單總覽</h2>
                    <p><span class="font-medium text-gray-600">總金額:</span> <span class="font-bold text-xl text-indigo-600">$<?php echo htmlspecialchars($order['total_amount']); ?></span></p>
                    <p><span class="font-medium text-gray-600">目前狀態:</span> <span class="font-bold text-xl"><?php echo htmlspecialchars($order['order_status']); ?></span></p>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">訂單項目</h2>
                <div class="border rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">產品</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">單價</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">數量</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">小計</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($order_items as $item): ?>
                            <tr class="bg-white">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo htmlspecialchars($item['price_per_item']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 text-right">$<?php echo number_format($item['price_per_item'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-gray-800 mb-2">更新訂單狀態</h2>
                <form action="/edit_order.php?id=<?php echo $order['id']; ?>" method="post">
                    <div class="flex items-center space-x-4">
                        <select name="order_status" class="w-full md:w-1/3 border-gray-300 rounded-md shadow-sm text-base">
                            <?php foreach ($possible_statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php if ($order['order_status'] == $status) echo 'selected'; ?>>
                                    <?php echo $status; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" class="bg-indigo-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md hover:bg-indigo-700">
                            儲存變更
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</body>
</html>
