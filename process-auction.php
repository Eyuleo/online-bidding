<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Use the $auth from bootstrap.php
$auctionController = new AuctionController($pdo);

// Check if user is logged in and is admin
if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit();
}

try {
    // Create uploads directory with proper permissions
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'items';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create upload directory');
        }
        chmod($uploadDir, 0777);
    }

    // Validate form data
    if (empty($_POST['auction_title']) || empty($_POST['auction_description']) || 
        empty($_POST['start_date']) || empty($_POST['end_date']) || empty($_POST['items'])) {
        throw new Exception('All required fields must be filled out');
    }

    // Process the form data
    $auctionData = [
        'auction_title' => trim($_POST['auction_title']),
        'auction_description' => trim($_POST['auction_description']),
        'auction_type' => $_POST['auction_type'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'items' => []
    ];

    // Process each item
    foreach ($_POST['items'] as $index => $item) {
        if (empty($item['name']) || empty($item['description']) || !isset($item['price'])) {
            throw new Exception('All item fields are required');
        }

        $itemData = [
            'name' => trim($item['name']),
            'description' => trim($item['description']),
            'price' => floatval($item['price']),
            'images' => []
        ];

        // Handle image uploads for this item
        if (isset($_FILES['items']['name'][$index]['images'])) {
            $files = $_FILES['items']['name'][$index]['images'];
            $tmpNames = $_FILES['items']['tmp_name'][$index]['images'];
            $errors = $_FILES['items']['error'][$index]['images'];
            
            for ($i = 0; $i < count($files); $i++) {
                if (!empty($files[$i]) && $errors[$i] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($files[$i], PATHINFO_EXTENSION));
                    
                    // Validate file type
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                    }

                    $newFileName = uniqid() . '.' . $extension;
                    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                    if (!move_uploaded_file($tmpNames[$i], $targetPath)) {
                        throw new Exception('Failed to upload file: ' . $files[$i]);
                    }

                    $itemData['images'][] = 'uploads/items/' . $newFileName;
                } elseif ($errors[$i] !== UPLOAD_ERR_NO_FILE) {
                    throw new Exception('File upload error: ' . $errors[$i]);
                }
            }
        }

        $auctionData['items'][] = $itemData;
    }

    // Create the auction
    $result = $auctionController->createAuction($auctionData, $auth->getCurrentUser()['id']);

    if ($result['success']) {
        header('Location: auctions.php?message=' . urlencode('Auction created successfully'));
        exit();
    } else {
        throw new Exception($result['error'] ?? 'Failed to create auction');
    }

} catch (Exception $e) {
    // Log the error
    error_log('Auction creation error: ' . $e->getMessage());
    
    // Redirect with error message
    header('Location: create.php?error=' . urlencode($e->getMessage()));
    exit();
} 