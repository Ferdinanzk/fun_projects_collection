<div class="container mx-auto px-4 py-12">
    <div class="flex justify-between items-center mb-10">
        <h1 class="text-4xl font-bold text-gray-800">我們的產品</h1>
        <a href="/index.php?page=add_product" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors duration-300">
            新增產品
        </a>
    </div>

    <?php if (!empty($products)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 ease-in-out">
                    <img 
                        src="/public/images/<?php echo htmlspecialchars($product['image'] ?? 'default.png'); ?>" 
                        alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                        class="w-full h-56 object-cover"
                        onerror="this.onerror=null;this.src='https://placehold.co/600x400/F4F4F5/333333?text=<?php echo urlencode(htmlspecialchars($product['product_name'])); ?>';"
                    >
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-1"><?php echo htmlspecialchars($product['category']); ?></p>
                        <h2 class="text-xl font-bold text-gray-800 mb-2 truncate"><?php echo htmlspecialchars($product['product_name']); ?></h2>
                        <div class="flex items-center justify-between">
                            <p class="text-2xl font-bold text-indigo-600">$<?php echo htmlspecialchars($product['price']); ?></p>
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition-colors duration-300">
                                加入購物車
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-20">
            <p class="text-xl text-gray-600">目前沒有找到任何產品。</p>
            <p class="text-gray-500 mt-2">點擊右上角的「新增產品」按鈕來開始吧！</p>
        </div>
    <?php endif; ?>
</div>

