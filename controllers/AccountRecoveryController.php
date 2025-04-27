<?php

class AccountRecoveryController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function findAccountsByName($name) {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.name, u.email, sq.id as question_id, sq.question
            FROM users u
            JOIN user_security_answers usa ON u.id = usa.user_id
            JOIN security_questions sq ON usa.question_id = sq.id
            WHERE u.name LIKE ?
            LIMIT 5
        ");
        $stmt->execute(['%' . $name . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verifySecurityAnswerAndGetEmail($userId, $questionId, $answer) {
        $stmt = $this->pdo->prepare("
            SELECT u.email 
            FROM users u
            JOIN user_security_answers usa ON u.id = usa.user_id
            WHERE u.id = ? AND usa.question_id = ? AND usa.answer = ?
        ");
        $stmt->execute([$userId, $questionId, strtolower(trim($answer))]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['email'] : null;
    }
} 