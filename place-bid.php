<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Starting place-bid.php");
error_log("POST data: " . print_r($_POST, true));

$auth = new AuthController($pdo);
$auctionController = new AuctionController($pdo);

// Check if user is logged in and can place bids
if (!$auth->isLoggedIn()) {
    error_log("User not logged in");
    header('Location: auctions.php');
    exit();
}

if (!$auth->canPlaceBids()) {
    error_log("User cannot place bids");
    header('Location: auctions.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Not a POST request");
    header('Location: auctions.php');
    exit();
}

$itemIds = $_POST['item_ids'] ?? [];
$bidAmounts = $_POST['bid_amounts'] ?? [];

error_log("Item IDs: " . print_r($itemIds, true));
error_log("Bid amounts: " . print_r($bidAmounts, true));

if (empty($itemIds) || empty($bidAmounts) || count($itemIds) !== count($bidAmounts)) {
    error_log("Invalid bid data - itemIds or bidAmounts empty or mismatched");
    header('Location: auctions.php?error=' . urlencode('Invalid bid data'));
    exit();
}

// Get auction ID for redirect
$stmt = $pdo->prepare('
    SELECT a.id 
    FROM auctions a
    JOIN auction_items i ON a.id = i.auction_id
    WHERE i.id = ?
    LIMIT 1
');
$stmt->execute([$itemIds[0]]);
$auction = $stmt->fetch();

if (!$auction) {
    error_log("Auction not found for item ID: " . $itemIds[0]);
    header('Location: auctions.php?error=' . urlencode('Auction not found'));
    exit();
}

$auctionId = $auction['id'];
error_log("Auction ID found: " . $auctionId);

try {
    // Test database connection
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query('SELECT 1');
        error_log("Database connection test successful");
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        throw $e;
    }

    // Begin transaction
    $pdo->beginTransaction();
    error_log("Transaction started");
    
    $successCount = 0;
    $errors = [];

    // Process each bid
    for ($i = 0; $i < count($itemIds); $i++) {
        $itemId = $itemIds[$i];
        $amount = $bidAmounts[$i];
        error_log("Processing bid for item ID: $itemId with amount: $amount");

        // Get item details to check current price
        try {
            $stmt = $pdo->prepare('
                SELECT i.*, a.end_date, a.start_date,
                       COALESCE(MAX(b.amount), i.starting_price) as highest_bid
                FROM auction_items i
                JOIN auctions a ON i.auction_id = a.id
                LEFT JOIN bids b ON i.id = b.item_id
                WHERE i.id = ?
                GROUP BY i.id
            ');
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            error_log("Item query executed successfully");
        } catch (PDOException $e) {
            error_log("Error fetching item details: " . $e->getMessage());
            throw $e;
        }

        if (!$item) {
            error_log("Item not found: $itemId");
            $errors[] = "Item not found";
            continue;
        }

        error_log("Current highest bid for item $itemId: " . $item['highest_bid']);
        error_log("Start date for auction: " . $item['start_date']);
        error_log("End date for auction: " . $item['end_date']);

        // Check if auction has started
        if (strtotime($item['start_date']) > time()) {
            error_log("Auction has not started yet: " . $item['start_date']);
            $errors[] = "Auction has not started yet. Bidding starts on " . date('M d, Y', strtotime($item['start_date']));
            continue;
        }

        // Check if auction has ended
        if (strtotime($item['end_date']) < time()) {
            error_log("Auction has ended for item $itemId");
            $errors[] = "Auction has ended";
            continue;
        }

        // Check if bid amount is higher than current highest bid
        if ($amount <= $item['highest_bid']) {
            error_log("Bid amount ($amount) not higher than highest bid (" . $item['highest_bid'] . ") for item $itemId");
            $errors[] = "Bid must be higher than current highest bid for " . $item['name'];
            continue;
        }

        try {
            // Insert bid
            $stmt = $pdo->prepare('
                INSERT INTO bids (item_id, user_id, amount, status)
                VALUES (?, ?, ?, ?)
            ');
            $userId = $auth->getCurrentUser()['id'];
            error_log("Attempting to insert bid with values - Item: $itemId, User: $userId, Amount: $amount");
            
            try {
                $stmt->execute([
                    $itemId,
                    $userId,
                    $amount,
                    'pending'  // Status doesn't affect the current price anymore
                ]);
                error_log("Bid insert statement executed successfully");
                error_log("Last Insert ID: " . $pdo->lastInsertId());
            } catch (PDOException $e) {
                error_log("Error executing bid insert: " . $e->getMessage());
                error_log("SQL State: " . $e->errorInfo[0]);
                error_log("Error Code: " . $e->errorInfo[1]);
                error_log("Error Message: " . $e->errorInfo[2]);
                throw $e;
            }

            $successCount++;
            error_log("Success count: $successCount");
        } catch (PDOException $e) {
            error_log("Database error during bid: " . $e->getMessage());
            $errors[] = "Database error: " . $e->getMessage();
            continue;
        }
    }

    if ($successCount > 0) {
        error_log("Committing transaction with $successCount successful bids");
        try {
            $pdo->commit();
            error_log("Transaction committed successfully");
        } catch (PDOException $e) {
            error_log("Error committing transaction: " . $e->getMessage());
            throw $e;
        }
        $message = $successCount === 1 ? 'Bid placed successfully' : 'Bids placed successfully';
        header('Location: auction.php?id=' . $auctionId . '&message=' . urlencode($message));
    } else {
        error_log("Rolling back transaction - no successful bids");
        $pdo->rollBack();
        header('Location: auction.php?id=' . $auctionId . '&error=' . urlencode(implode(', ', $errors)));
    }
} catch (Exception $e) {
    error_log("Error in transaction: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    try {
        $pdo->rollBack();
        error_log("Transaction rolled back successfully");
    } catch (Exception $rollbackError) {
        error_log("Error rolling back transaction: " . $rollbackError->getMessage());
    }
    header('Location: auction.php?id=' . $auctionId . '&error=' . urlencode('Failed to place bid'));
}
exit(); 