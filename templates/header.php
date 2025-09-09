<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USR-小幫手</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-3">
            <div class="flex items-center">
                <img src="/public/logo.png" alt="Logo" class="h-12 w-12 mr-3">
                <a href="/index.php?page=home" class="text-2xl font-bold text-indigo-600">USR-小幫手</a>
            </div>
            <div>
                <a href="/index.php?page=home" class="text-gray-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">首頁</a>
            </div>
        </div>
    </div>
</nav>

<main>
    <?php
    // --- Display Session Messages ---
    // Check if a message is set in the session.
    if (isset($_SESSION['message'])):
        $message = $_SESSION['message'];
        $message_type_class = $message['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    ?>
    <div class="container mx-auto px-4 mt-4">
        <div class="p-4 rounded-lg <?php echo $message_type_class; ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
    </div>
    <?php
        // Unset the message so it doesn't show again on the next page load.
        unset($_SESSION['message']);
    endif;
    ?>

