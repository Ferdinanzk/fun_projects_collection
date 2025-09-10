<?php
// Include your database configuration
require_once 'config.php';
// Include the Composer autoloader to use the AWS SDK
require_once __DIR__ . '/vendor/autoload.php';

// Import the necessary classes from the AWS SDK
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// --- S3 CLIENT SETUP ---
$s3Client = null;
if (getenv('S3_BUCKET_NAME')) {
    try {
        $s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => getenv('AWS_REGION'),
            'credentials' => [
                'key'    => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    } catch (Exception $e) {
        // Stop execution if S3 client fails to initialize
        die("Error creating S3 Client: " . $e->getMessage());
    }
}
// --- END S3 CLIENT SETUP ---


// Process delete operation only if ID is present
if (isset($_POST["id"]) && !empty($_POST["id"])) {
    $id = trim($_POST["id"]);
    $image_url_to_delete = "";

    // First, get the image URL from the database
    $sql_select = "SELECT image FROM products WHERE id = ?";
    if ($stmt_select = $conn->prepare($sql_select)) {
        $stmt_select->bind_param("i", $id);
        if ($stmt_select->execute()) {
            $result = $stmt_select->get_result();
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $image_url_to_delete = $row['image'];
            }
        } else {
            die("Error fetching product data.");
        }
        $stmt_select->close();
    }

    // --- DELETE OBJECT FROM S3 ---
    if (!empty($image_url_to_delete) && $s3Client) {
        try {
            $bucket = getenv('S3_BUCKET_NAME');
            // Extract the key (path/filename) from the full URL
            $key = parse_url($image_url_to_delete, PHP_URL_PATH);
            // The key might have a leading slash, which should be removed
            $key = ltrim($key, '/');

            if (!empty($key)) {
                $s3Client->deleteObject([
                    'Bucket' => $bucket,
                    'Key'    => $key,
                ]);
            }
        } catch (AwsException $e) {
            // Log the error but don't stop the script.
            // We still want to delete the database record even if S3 fails.
            error_log("Error deleting S3 object: " . $e->getMessage());
        }
    }
    // --- END S3 DELETE ---

    // Now, delete the record from the database
    $sql_delete = "DELETE FROM products WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $id);
        if ($stmt_delete->execute()) {
            // Records deleted successfully. Redirect to landing page.
            header("location: /index.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt_delete->close();
    }
    $conn->close();

} else {
    // Check if ID parameter is missing
    if (empty(trim($_GET["id"]))) {
        // URL doesn't contain id parameter. Redirect to error page or index.
        header("location: /index.php");
        exit();
    }
}
?>

