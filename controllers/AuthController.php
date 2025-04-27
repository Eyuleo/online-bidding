<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Check if user exists and is active
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Invalid email or password'
            ];
        }

        // Check if account is active
        if (!$user['is_active']) {
            return [
                'success' => false,
                'error' => 'Account is deactivated. Please contact support.'
            ];
        }

        // Check if account is restricted
        $isRestricted = false;
        $restrictionEnd = null;
        if ($user['account_restricted_until'] !== null) {
            $restrictionEnd = new DateTime($user['account_restricted_until']);
            $now = new DateTime();
            if ($restrictionEnd > $now) {
                $isRestricted = true;
            }
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Store user information in session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'is_restricted' => $isRestricted,
                'restriction_end' => $isRestricted ? $restrictionEnd->format('Y-m-d H:i:s') : null
            ];
            $_SESSION['user_id'] = $user['id']; // Keep this for backward compatibility
            $_SESSION['logged_in'] = true;

            return [
                'success' => true,
                'user' => $user,
                'is_restricted' => $isRestricted,
                'restriction_end' => $isRestricted ? $restrictionEnd->format('Y-m-d H:i:s') : null
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid email or password'
        ];
    }

    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    public function updateProfile($userId, $data) {
        $allowedFields = ['name', 'email'];
        $updates = [];
        $values = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $values[] = $value;
            }
        }

        if (!empty($updates)) {
            $values[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            try {
                $success = $stmt->execute($values);
                if ($success) {
                    // Update session if name or email was changed
                    if (isset($data['name'])) {
                        $_SESSION['user']['name'] = $data['name'];
                    }
                    if (isset($data['email'])) {
                        $_SESSION['user']['email'] = $data['email'];
                    }
                }
                return $success;
            } catch (PDOException $e) {
                // Check for duplicate email
                if ($e->getCode() == 23000) {
                    return [
                        'success' => false,
                        'error' => 'Email already exists'
                    ];
                }
                throw $e;
            }
        }

        return false;
    }

    public function updateSecurityAnswers($userId, $questionId, $answer) {
        try {
            // Begin transaction
            $this->pdo->beginTransaction();

            // Delete existing answers
            $stmt = $this->pdo->prepare('DELETE FROM user_security_answers WHERE user_id = ?');
            $stmt->execute([$userId]);

            // Insert new answer
            $stmt = $this->pdo->prepare('
                INSERT INTO user_security_answers (user_id, question_id, answer)
                VALUES (?, ?, ?)
            ');

            $stmt->execute([$userId, $questionId, strtolower(trim($answer))]);

            // Commit transaction
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getSecurityQuestions() {
        $stmt = $this->pdo->query('SELECT id, question FROM security_questions WHERE is_active = true');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserSecurityQuestion($userId) {
        $stmt = $this->pdo->prepare('
            SELECT sq.id, sq.question, usa.answer 
            FROM user_security_answers usa
            JOIN security_questions sq ON usa.question_id = sq.id
            WHERE usa.user_id = ?
            LIMIT 1
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifySecurityAnswer($userId, $answer) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count 
            FROM user_security_answers 
            WHERE user_id = ? AND answer = ?
        ');

        $stmt->execute([$userId, strtolower(trim($answer))]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    public function updatePassword($userId, $currentPassword, $newPassword) {
        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && password_verify($currentPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            return $stmt->execute([$hashedPassword, $userId]);
        }

        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            $stmt = $this->pdo->prepare('SELECT id, name, email, role, is_active, account_restricted_until, recovery_email FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                // Check if account is restricted
                $isRestricted = false;
                $restrictionEnd = null;
                if ($user['account_restricted_until'] !== null) {
                    $restrictionEnd = new DateTime($user['account_restricted_until']);
                    $now = new DateTime();
                    if ($restrictionEnd > $now) {
                        $isRestricted = true;
                    }
                }

                // Update session with latest restriction status
                $_SESSION['user']['is_restricted'] = $isRestricted;
                $_SESSION['user']['restriction_end'] = $isRestricted ? $restrictionEnd->format('Y-m-d H:i:s') : null;

                $user['is_restricted'] = $isRestricted;
                $user['restriction_end'] = $isRestricted ? $restrictionEnd->format('Y-m-d H:i:s') : null;

                return $user;
            }
        }
        return null;
    }

    public function signup($data) {
        try {
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'Invalid email format'
                ];
            }

            // Check if email already exists
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'error' => 'Email already registered'
                ];
            }

            // Begin transaction
            $this->pdo->beginTransaction();

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $this->pdo->prepare('
                INSERT INTO users (name, email, password, role, is_active)
                VALUES (?, ?, ?, ?, true)
            ');

            $stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role'] ?? 'user' // Default role
            ]);

            $userId = $this->pdo->lastInsertId();

            // Insert security question answer if provided
            if (isset($data['security_question'])) {
                $stmt = $this->pdo->prepare('
                    INSERT INTO user_security_answers (user_id, question_id, answer)
                    VALUES (?, ?, ?)
                ');

                $stmt->execute([
                    $userId,
                    $data['security_question']['question_id'],
                    strtolower(trim($data['security_question']['answer']))
                ]);
            }

            // Commit transaction
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Account created successfully',
                'user_id' => $userId
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }

    public function isAdmin() {
        return $this->isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    public function requireAdmin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }

        if (!$this->isAdmin()) {
            header('Location: /');
            exit();
        }
    }

    public function canPlaceBids() {
        error_log("Checking if user can place bids...");
        error_log("Session state: " . print_r($_SESSION, true));
        
        if (!$this->isLoggedIn()) {
            error_log("User is not logged in");
            return false;
        }

        $user = $this->getCurrentUser();
        error_log("Current user data: " . print_r($user, true));
        
        if (!$user) {
            error_log("Could not get current user data");
            return false;
        }

        // Check if user is active and not restricted
        if (!$user['is_active']) {
            error_log("User is not active");
            return false;
        }

        // Check if user is currently restricted
        if ($user['account_restricted_until'] && strtotime($user['account_restricted_until']) > time()) {
            error_log("User is restricted until: " . $user['account_restricted_until']);
            return false;
        }

        error_log("User can place bids");
        return true;
    }

    public function deleteAccount($userId, $password) {
        try {
            // Verify password first
            $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid password'
                ];
            }

            // Begin transaction
            $this->pdo->beginTransaction();

            // Get all items where the user has the highest/lowest bid
            $stmt = $this->pdo->prepare('
                SELECT i.id, i.starting_price, a.auction_type,
                       (SELECT MIN(b2.amount) FROM bids b2 WHERE b2.item_id = i.id AND b2.user_id != ?) as next_lowest,
                       (SELECT MAX(b2.amount) FROM bids b2 WHERE b2.item_id = i.id AND b2.user_id != ?) as next_highest
                FROM auction_items i
                JOIN auctions a ON i.auction_id = a.id
                JOIN bids b ON i.id = b.item_id
                WHERE b.user_id = ?
                GROUP BY i.id
            ');
            $stmt->execute([$userId, $userId, $userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Update current prices for items where user had highest/lowest bid
            foreach ($items as $item) {
                $newPrice = null;
                if ($item['auction_type'] === 'buy') {
                    // For reverse auction (buy), use next lowest bid or starting price
                    $newPrice = $item['next_lowest'] ?? $item['starting_price'];
                } else {
                    // For regular auction (sell), use next highest bid or starting price
                    $newPrice = $item['next_highest'] ?? $item['starting_price'];
                }

                $stmt = $this->pdo->prepare('UPDATE auction_items SET current_price = ? WHERE id = ?');
                $stmt->execute([$newPrice, $item['id']]);
            }

            // Delete security answers
            $stmt = $this->pdo->prepare('DELETE FROM user_security_answers WHERE user_id = ?');
            $stmt->execute([$userId]);

            // Delete user's bids
            $stmt = $this->pdo->prepare('DELETE FROM bids WHERE user_id = ?');
            $stmt->execute([$userId]);

            // Delete the user
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);

            // Commit transaction
            $this->pdo->commit();

            // Clear session
            session_destroy();

            return [
                'success' => true
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }

    public function updateRecoveryContacts($userId, $contact, $type) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE users 
                SET recovery_contact = ?, recovery_contact_type = ?
                WHERE id = ?
            ');
            return $stmt->execute([$contact ?: null, $type ?: null, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function findAccountByRecoveryContact($contact) {
        if (!$contact) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, email FROM users WHERE recovery_contact = ?');
        $stmt->execute([$contact]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRecoveryEmail($userId, $recoveryEmail) {
        if (!filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE users SET recovery_email = ? WHERE id = ?");
        return $stmt->execute([$recoveryEmail, $userId]);
    }

    public function findAccountByRecoveryEmail($recoveryEmail) {
        $stmt = $this->pdo->prepare("SELECT id, email FROM users WHERE recovery_email = ?");
        $stmt->execute([$recoveryEmail]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 