<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

$auth = new AuthController($pdo);
$auctionController = new AuctionController($pdo);

// Check if user is logged in and is admin
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getCurrentUser();
$auctionId = $_GET['id'] ?? null;

if (!$auctionId) {
    header('Location: auctions.php');
    exit();
}

// Delete the auction
$result = $auctionController->deleteAuction($auctionId, $user['id']);

if ($result['success']) {
    header('Location: auctions.php?message=Auction deleted successfully');
} else {
    header('Location: auctions.php?error=' . urlencode($result['error']));
}
exit(); 