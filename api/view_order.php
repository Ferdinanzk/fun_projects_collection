<?php
// Include the database configuration
require_once 'config.php';

// Fetch all orders, joining with the users table to get customer names
$orders = [];
$sql = "SELECT 
            o.id, 
            o.total_amount, 
            o.order_status, 
            DATE_FORMAT(o.order_date, '%Y-%m-%d %H:%i') as formatted_date, 
            u.name as customer_name, 
            u.uid 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單管理</title>
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
                <a href="/index.php" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <img src="/logo.png" class="h-8" alt="USR Logo">
                    <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800">USR-小幫手</span>
                </a>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="/index.php" class="text-gray-500 hover:bg-indigo-100 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">產品管理</a>
                        <a href="/view_orders.php" class="bg-indigo-100 text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">訂單管理</a>
                        <a href="/add_order.php" class="bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">新增訂單</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">訂單列表</h1>
            <a href="/add_order.php" class="inline-block bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-indigo-700">
                + 新增訂單
            </a>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單 ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">顧客</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">總金額</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">訂單日期</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo htmlspecialchars($order['total_amount']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $order['formatted_date']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">查看詳情</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">目前沒有任何訂單。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
