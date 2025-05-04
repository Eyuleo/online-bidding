<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'SellItemController.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Use the $auth from bootstrap.php
$auctionController = new AuctionController($pdo);
$sellItemController = new SellItemController($pdo);

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
        empty($_POST['start_date']) || empty($_POST['end_date'])) {
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
    if (isset($_POST['items']) && is_array($_POST['items'])) {
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

            // Check if this is a sell item
            if (isset($item['sell_item_id'])) {
                $itemData['sell_item_id'] = $item['sell_item_id'];
                
                // Get sell item details
                $sellItem = $sellItemController->getSellItemDetails($item['sell_item_id']);
                if (!$sellItem) {
                    throw new Exception('Invalid sell item selected');
                }

                // Copy images from sell item
                foreach ($sellItem['images'] as $image) {
                    $itemData['images'][] = $image['image_path'];
                }
            } else {
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
            }

            $auctionData['items'][] = $itemData;
        }
    }

    if (empty($auctionData['items'])) {
        throw new Exception('At least one item is required');
    }

    // Create the auction
    $result = $auctionController->createAuction($auctionData, $auth->getCurrentUser()['id']);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    // Update sell items status if any were used
    foreach ($auctionData['items'] as $item) {
        if (isset($item['sell_item_id'])) {
            $sellItemController->updateSellItem($item['sell_item_id'], [
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => $item['price'],
                'status' => 'sold'
            ], $auth->getCurrentUser()['id']);
        }
    }

    // Redirect to auction details page
    header('Location: auction.php?id=' . $result['auction_id']);
    exit();

} catch (Exception $e) {
    // Redirect back with error message
    header('Location: create.php?error=' . urlencode($e->getMessage()));
    exit();
} 