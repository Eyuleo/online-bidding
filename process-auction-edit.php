<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$auth = new AuthController($pdo);
$auctionController = new AuctionController($pdo);

// Check if user is logged in and is admin
if (!$auth->isAdmin()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auctions.php');
    exit();
}

try {
    // Get auction ID
    $auctionId = $_POST['auction_id'] ?? null;
    if (!$auctionId) {
        throw new Exception('Auction ID is required');
    }

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
            'id' => $item['id'] ?? null, // This will be null for new items
            'name' => trim($item['name']),
            'description' => trim($item['description']),
            'price' => floatval($item['price']),
            'images' => []
        ];

        // Preserve existing images if they were included in the form
        if (isset($item['existing_images']) && is_array($item['existing_images'])) {
            foreach ($item['existing_images'] as $existingImage) {
                // Validate that the image path exists and is within the uploads directory
                $imagePath = __DIR__ . DIRECTORY_SEPARATOR . $existingImage;
                if (file_exists($imagePath) && strpos(realpath($imagePath), realpath($uploadDir)) === 0) {
                    $itemData['images'][] = $existingImage;
                }
            }
        }

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

    // Update the auction
    $stmt = $pdo->prepare('
        UPDATE auctions 
        SET title = ?, 
            description = ?, 
            start_date = ?, 
            end_date = ?, 
            auction_type = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ');

    if (!$stmt->execute([
        $auctionData['auction_title'],
        $auctionData['auction_description'],
        $auctionData['start_date'],
        $auctionData['end_date'],
        $auctionData['auction_type'] ?? 'buy',
        $auctionId
    ])) {
        throw new PDOException('Failed to update auction');
    }

    $result = $auctionController->updateAuction($auctionId, $auctionData, $auth->getCurrentUser()['id']);

    if ($result['success']) {
        header('Location: auction.php?id=' . $auctionId . '&message=' . urlencode('Auction updated successfully'));
        exit();
    } else {
        throw new Exception($result['error'] ?? 'Failed to update auction');
    }

} catch (Exception $e) {
    // Log the error
    error_log('Auction update error: ' . $e->getMessage());
    
    // Redirect with error message
    header('Location: edit-auction.php?id=' . $auctionId . '&error=' . urlencode($e->getMessage()));
    exit();
} 