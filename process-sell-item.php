<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'SellItemController.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Use the $auth from bootstrap.php
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
    $action = $_POST['action'] ?? 'create';

    switch ($action) {
        case 'create':
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

            // Create uploads directory with proper permissions
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'items';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception('Failed to create upload directory');
                }
                chmod($uploadDir, 0777);
            }

            // Handle image uploads
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
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

                        $itemData['images'][] = 'uploads/items/' . $newFileName;
                    } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception('File upload error: ' . $files['error'][$i]);
                    }
                }
            }

            $result = $sellItemController->createSellItem($itemData, $auth->getCurrentUser()['id']);
            break;

        case 'update':
            if (empty($_POST['item_id'])) {
                throw new Exception('Item ID is required for update');
            }

            $itemData = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description']),
                'price' => floatval($_POST['price']),
                'images' => []
            ];

            // Create uploads directory with proper permissions
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'items';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception('Failed to create upload directory');
                }
                chmod($uploadDir, 0777);
            }

            // Handle image uploads for update
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                $fileCount = count($files['name']);

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                        
                        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                        }

                        $newFileName = uniqid() . '.' . $extension;
                        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                        if (!move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                            throw new Exception('Failed to upload file: ' . $files['name'][$i]);
                        }

                        $itemData['images'][] = 'uploads/items/' . $newFileName;
                    }
                }
            }

            $result = $sellItemController->updateSellItem($_POST['item_id'], $itemData, $auth->getCurrentUser()['id']);
            break;

        case 'delete':
            if (empty($_POST['item_id'])) {
                throw new Exception('Item ID is required for deletion');
            }

            $result = $sellItemController->deleteSellItem($_POST['item_id'], $auth->getCurrentUser()['id']);
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Redirect back to sell-items page with success message
            header('Location: sell-items.php?success=Item deleted successfully');
            exit();
            break;

        default:
            throw new Exception('Invalid action specified');
    }

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    // Redirect back to sell-items page with success message
    header('Location: sell-items.php?success=Item ' . ($action === 'create' ? 'created' : 'updated') . ' successfully');
    exit();

} catch (Exception $e) {
    // Redirect back with error message
    header('Location: sell-items.php?error=' . urlencode($e->getMessage()));
    exit();
} 