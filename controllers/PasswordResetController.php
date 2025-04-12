<?php

class PasswordResetController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserSecurityQuestion($userId) {
        $stmt = $this->pdo->prepare("
            SELECT sq.id, sq.question
            FROM user_security_answers usa
            JOIN security_questions sq ON usa.question_id = sq.id
            WHERE usa.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verifySecurityAnswer($userId, $answer) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM user_security_answers 
            WHERE user_id = ? AND answer = ?
        ");
        $stmt->execute([$userId, strtolower(trim($answer))]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    public function updateUserPassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }
} 