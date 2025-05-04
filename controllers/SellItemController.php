<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class SellItemController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createSellItem($data, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Insert sell item
            $stmt = $this->pdo->prepare('
                INSERT INTO sell_items (name, description, price, created_by)
                VALUES (?, ?, ?, ?)
            ');

            if (!$stmt->execute([
                $data['name'],
                $data['description'],
                $data['price'],
                $userId
            ])) {
                throw new PDOException('Failed to create sell item');
            }

            $itemId = $this->pdo->lastInsertId();

            // Handle images if they exist
            if (isset($data['images']) && !empty($data['images'])) {
                foreach ($data['images'] as $index => $image) {
                    $stmt = $this->pdo->prepare('
                        INSERT INTO sell_item_images (item_id, image_path, is_primary)
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

            $this->pdo->commit();
            return [
                'success' => true,
                'item_id' => $itemId
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Database error in createSellItem: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred: ' . $e->getMessage()
            ];
        }
    }

    public function getAvailableSellItems() {
        try {
            $stmt = $this->pdo->prepare('
                SELECT si.*, u.name as creator_name,
                       GROUP_CONCAT(sii.image_path) as images
                FROM sell_items si
                JOIN users u ON si.created_by = u.id
                LEFT JOIN sell_item_images sii ON si.id = sii.item_id
                WHERE si.status = "available"
                GROUP BY si.id
                ORDER BY si.created_at DESC
            ');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getAvailableSellItems: ' . $e->getMessage());
            return [];
        }
    }

    public function getSellItemDetails($itemId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT si.*, u.name as creator_name
                FROM sell_items si
                JOIN users u ON si.created_by = u.id
                WHERE si.id = ?
            ');
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                return null;
            }

            // Get images
            $stmt = $this->pdo->prepare('
                SELECT image_path, is_primary
                FROM sell_item_images
                WHERE item_id = ?
                ORDER BY is_primary DESC, created_at ASC
            ');
            $stmt->execute([$itemId]);
            $item['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $item;
        } catch (PDOException $e) {
            error_log('Error in getSellItemDetails: ' . $e->getMessage());
            return null;
        }
    }

    public function updateSellItem($itemId, $data, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Update sell item
            $stmt = $this->pdo->prepare('
                UPDATE sell_items 
                SET name = ?, description = ?, price = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND created_by = ?
            ');

            if (!$stmt->execute([
                $data['name'],
                $data['description'],
                $data['price'],
                $itemId,
                $userId
            ])) {
                throw new PDOException('Failed to update sell item');
            }

            // Delete existing images
            $stmt = $this->pdo->prepare('DELETE FROM sell_item_images WHERE item_id = ?');
            $stmt->execute([$itemId]);

            // Insert new images
            if (isset($data['images']) && !empty($data['images'])) {
                foreach ($data['images'] as $index => $image) {
                    $stmt = $this->pdo->prepare('
                        INSERT INTO sell_item_images (item_id, image_path, is_primary)
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

            $this->pdo->commit();
            return ['success' => true];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Database error in updateSellItem: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred: ' . $e->getMessage()
            ];
        }
    }

    public function deleteSellItem($itemId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Delete images first
            $stmt = $this->pdo->prepare('DELETE FROM sell_item_images WHERE item_id = ?');
            $stmt->execute([$itemId]);

            // Delete the item
            $stmt = $this->pdo->prepare('DELETE FROM sell_items WHERE id = ? AND created_by = ?');
            $stmt->execute([$itemId, $userId]);

            $this->pdo->commit();
            return ['success' => true];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Database error in deleteSellItem: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred: ' . $e->getMessage()
            ];
        }
    }
} 