<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'SellItemController.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$auth = new AuthController($pdo);
$sellItemController = new SellItemController($pdo);

// Check if user is logged in
if (!$auth->isAdmin()) {
    header('Location: /');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sell-items.php');
    exit();
}

try {
    // Create uploads directory with proper permissions
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sell-items';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
        chmod($uploadDir, 0777);
    }

    // Validate form data
    if (empty($_POST['name']) || empty($_POST['description']) || !isset($_POST['price'])) {
        throw new Exception('All required fields must be filled out');
    }

    // Process the form data
    $itemData = [
        'name' => trim($_POST['name']),
        'description' => trim($_POST['description']),
        'price' => floatval($_POST['price']),
        'images' => []
    ];

    // Handle image uploads
    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                
                // Validate file type
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                }

                $newFileName = uniqid() . '.' . $extension;
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                if (!move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                    throw new Exception('Failed to upload file: ' . $files['name'][$i]);
                }

                $itemData['images'][] = 'uploads/sell-items/' . $newFileName;
            } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                throw new Exception('File upload error: ' . $files['error'][$i]);
            }
        }
    }

    // Create the sell item
    $result = $sellItemController->createSellItem($itemData, $auth->getCurrentUser()['id']);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    // Redirect back to sell items page with success message
    header('Location: sell-items.php?success=1');
    exit();

} catch (Exception $e) {
    // Redirect back with error message
    header('Location: sell-items.php?error=' . urlencode($e->getMessage()));
    exit();
} 