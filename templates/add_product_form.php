<div class="container mx-auto px-4 py-12 max-w-2xl">
    <div class="flex justify-between items-center mb-10">
        <h1 class="text-4xl font-bold text-gray-800">新增產品</h1>
        <a href="/index.php?page=home" class="text-indigo-600 hover:text-indigo-800 font-semibold">
            &larr; 返回產品列表
        </a>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <!-- The form now submits to the dedicated API endpoint -->
        <form action="/api/add_product.php" method="POST" enctype="multipart/form-data">
            <div class="space-y-6">
                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700">產品名稱 <span class="text-red-500">*</span></label>
                    <input type="text" name="product_name" id="product_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="product_code" class="block text-sm font-medium text-gray-700">產品代碼 <span class="text-red-500">*</span></label>
                    <input type="text" name="product_code" id="product_code" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">類別</label>
                    <input type="text" name="category" id="category" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">價格 <span class="text-red-500">*</span></label>
                    <input type="number" name="price" id="price" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">產品圖片</label>
                    <input type="file" name="image" id="image" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                    <p class="mt-1 text-xs text-gray-500">僅允許 JPG, JPEG, PNG & GIF 格式。最大 5MB。</p>
                </div>
            </div>
            <div class="mt-8">
                <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors duration-300">
                    提交產品
                </button>
            </div>
        </form>
    </div>
</div>

