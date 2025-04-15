<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class AuctionController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createAuction($data, $userId) {
        try {
            // Validate dates
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            $today = new DateTime();
            $today->setTime(0, 0);

            if ($startDate < $today) {
                return [
                    'success' => false,
                    'error' => 'Start date cannot be in the past'
                ];
            }

            if ($endDate <= $startDate) {
                return [
                    'success' => false,
                    'error' => 'End date must be after start date'
                ];
            }

            if (empty($data['items'])) {
                return [
                    'success' => false,
                    'error' => 'At least one item is required'
                ];
            }

            $this->pdo->beginTransaction();

            // Insert auction
            $stmt = $this->pdo->prepare('
                INSERT INTO auctions (title, description, created_by, status, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ');

            if (!$stmt->execute([
                $data['auction_title'],
                $data['auction_description'],
                $userId,
                'active',
                $data['start_date'],
                $data['end_date']
            ])) {
                throw new PDOException('Failed to create auction');
            }

            $auctionId = $this->pdo->lastInsertId();

            // Insert items
            foreach ($data['items'] as $item) {
                if (empty($item['name']) || empty($item['description']) || !isset($item['price'])) {
                    throw new PDOException('Invalid item data');
                }

                $stmt = $this->pdo->prepare('
                    INSERT INTO auction_items (auction_id, name, description, starting_price, current_price)
                    VALUES (?, ?, ?, ?, ?)
                ');

                if (!$stmt->execute([
                    $auctionId,
                    $item['name'],
                    $item['description'],
                    $item['price'],
                    $item['price'] // Initial current_price is the starting_price
                ])) {
                    throw new PDOException('Failed to create auction item');
                }

                $itemId = $this->pdo->lastInsertId();

                // Handle images if they exist
                if (isset($item['images']) && !empty($item['images'])) {
                    foreach ($item['images'] as $index => $image) {
                        $stmt = $this->pdo->prepare('
                            INSERT INTO item_images (item_id, image_path, is_primary)
                            VALUES (?, ?, ?)
                        ');

                        if (!$stmt->execute([
                            $itemId,
                            $image,
                            $index === 0 ? 1 : 0 // Convert boolean to integer (1 for true, 0 for false)
                        ])) {
                            throw new PDOException('Failed to save item image');
                        }
                    }
                }
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'auction_id' => $auctionId
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Database error in createAuction: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Error in createAuction: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getAuctions($status = null) {
        $sql = 'SELECT a.*, u.name as creator_name, 
                (SELECT COUNT(*) FROM auction_items WHERE auction_id = a.id) as item_count,
                (SELECT MIN(starting_price) FROM auction_items WHERE auction_id = a.id) as min_price,
                (SELECT MAX(starting_price) FROM auction_items WHERE auction_id = a.id) as max_price,
                CASE 
                    WHEN start_date > NOW() THEN "upcoming"
                    WHEN end_date < NOW() THEN "ended"
                    ELSE "open"
                END as auction_status
                FROM auctions a
                JOIN users u ON a.created_by = u.id';
        
        $params = [];
        if ($status) {
            if ($status === 'upcoming') {
                $sql .= ' WHERE a.start_date > NOW()';
            } elseif ($status === 'ended') {
                $sql .= ' WHERE a.end_date < NOW()';
            } elseif ($status === 'open') {
                $sql .= ' WHERE a.start_date <= NOW() AND a.end_date >= NOW()';
            } elseif ($status !== 'all') {
                $sql .= ' WHERE a.status = ?';
                $params[] = $status;
            }
        }
        
        $sql .= ' ORDER BY a.created_at DESC';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteAuction($auctionId, $userId) {
        try {
            // Check if user is admin or auction creator
            $stmt = $this->pdo->prepare('
                SELECT created_by FROM auctions WHERE id = ?
            ');
            $stmt->execute([$auctionId]);
            $auction = $stmt->fetch();

            if (!$auction) {
                return [
                    'success' => false,
                    'error' => 'Auction not found'
                ];
            }

            // Begin transaction
            $this->pdo->beginTransaction();

            // Delete auction and all related data (cascade will handle related records)
            $stmt = $this->pdo->prepare('DELETE FROM auctions WHERE id = ?');
            $stmt->execute([$auctionId]);

            $this->pdo->commit();
            return ['success' => true];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }

    public function getAuctionDetails($auctionId) {
        try {
            // Get auction details
            $stmt = $this->pdo->prepare('
                SELECT a.*, u.name as creator_name
                FROM auctions a
                JOIN users u ON a.created_by = u.id
                WHERE a.id = ?
            ');
            $stmt->execute([$auctionId]);
            $auction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$auction) {
                return null;
            }

            // Get auction items with highest bids
            $stmt = $this->pdo->prepare('
                SELECT i.*,
                       COALESCE(MAX(b.amount), i.starting_price) as highest_bid
                FROM auction_items i
                LEFT JOIN bids b ON i.id = b.item_id
                WHERE i.auction_id = ?
                GROUP BY i.id
            ');
            $stmt->execute([$auctionId]);
            $auction['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $auction;
        } catch (PDOException $e) {
            error_log('Error in getAuctionDetails: ' . $e->getMessage());
            return null;
        }
    }

    public function getAuctionBids($auctionId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT b.*, 
                       i.name as item_name,
                       u.name as bidder_name,
                       u.email as bidder_email
                FROM bids b
                JOIN auction_items i ON b.item_id = i.id
                JOIN users u ON b.user_id = u.id
                WHERE i.auction_id = ?
                ORDER BY b.created_at DESC
            ');
            $stmt->execute([$auctionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getAuctionBids: ' . $e->getMessage());
            return [];
        }
    }

    public function updateAuction($auctionId, $data, $userId) {
        try {
            // Validate dates
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            $today = new DateTime();
            $today->setTime(0, 0);

            if ($endDate <= $startDate) {
                return [
                    'success' => false,
                    'error' => 'End date must be after start date'
                ];
            }

            if (empty($data['items'])) {
                return [
                    'success' => false,
                    'error' => 'At least one item is required'
                ];
            }

            $this->pdo->beginTransaction();

            // Update auction
            $stmt = $this->pdo->prepare('
                UPDATE auctions 
                SET title = ?, description = ?, start_date = ?, end_date = ?
                WHERE id = ?
            ');

            if (!$stmt->execute([
                $data['auction_title'],
                $data['auction_description'],
                $data['start_date'],
                $data['end_date'],
                $auctionId
            ])) {
                throw new PDOException('Failed to update auction');
            }

            // Get existing items
            $stmt = $this->pdo->prepare('SELECT id FROM auction_items WHERE auction_id = ?');
            $stmt->execute([$auctionId]);
            $existingItems = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $updatedItems = [];

            // Update or insert items
            foreach ($data['items'] as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingItems)) {
                    // Update existing item
                    $stmt = $this->pdo->prepare('
                        UPDATE auction_items 
                        SET name = ?, description = ?, starting_price = ?
                        WHERE id = ? AND auction_id = ?
                    ');

                    if (!$stmt->execute([
                        $item['name'],
                        $item['description'],
                        $item['price'],
                        $item['id'],
                        $auctionId
                    ])) {
                        throw new PDOException('Failed to update auction item');
                    }

                    $updatedItems[] = $item['id'];
                } else {
                    // Insert new item
                    $stmt = $this->pdo->prepare('
                        INSERT INTO auction_items (auction_id, name, description, starting_price, current_price)
                        VALUES (?, ?, ?, ?, ?)
                    ');

                    if (!$stmt->execute([
                        $auctionId,
                        $item['name'],
                        $item['description'],
                        $item['price'],
                        $item['price']
                    ])) {
                        throw new PDOException('Failed to create auction item');
                    }

                    $itemId = $this->pdo->lastInsertId();
                    $updatedItems[] = $itemId;

                    // Handle images if they exist
                    if (isset($item['images']) && !empty($item['images'])) {
                        foreach ($item['images'] as $index => $image) {
                            $stmt = $this->pdo->prepare('
                                INSERT INTO item_images (item_id, image_path, is_primary)
                                VALUES (?, ?, ?)
                            ');

                            if (!$stmt->execute([
                                $itemId,
                                $image,
                                $index === 0 ? 1 : 0
                            ])) {
                                throw new PDOException('Failed to save item image');
                            }
                        }
                    }
                }
            }

            // Delete items that weren't updated
            $itemsToDelete = array_diff($existingItems, $updatedItems);
            if (!empty($itemsToDelete)) {
                $placeholders = str_repeat('?,', count($itemsToDelete) - 1) . '?';
                $stmt = $this->pdo->prepare("
                    DELETE FROM auction_items 
                    WHERE id IN ($placeholders) AND auction_id = ?
                ");
                
                $params = array_merge($itemsToDelete, [$auctionId]);
                if (!$stmt->execute($params)) {
                    throw new PDOException('Failed to delete removed items');
                }
            }

            $this->pdo->commit();
            return [
                'success' => true,
                'auction_id' => $auctionId
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Database error in updateAuction: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Error in updateAuction: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 