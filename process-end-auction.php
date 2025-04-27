<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

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

    // End the auction
    $result = $auctionController->endAuction($auctionId, $auth->getCurrentUser()['id']);

    if ($result['success']) {
        header('Location: auction.php?id=' . $auctionId . '&message=' . urlencode('Auction ended successfully'));
        exit();
    } else {
        throw new Exception($result['error'] ?? 'Failed to end auction');
    }

} catch (Exception $e) {
    // Log the error
    error_log('Auction end error: ' . $e->getMessage());
    
    // Redirect with error message
    header('Location: edit-auction.php?id=' . $auctionId . '&error=' . urlencode($e->getMessage()));
    exit();
} 