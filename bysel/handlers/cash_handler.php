<?php
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cash.php");
    exit();
}

// CSRF Validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

$user_id = $_SESSION['user_id'];
$type = $_POST['transaction_type'];
$amount = (float)$_POST['amount'];

try {
    $conn->beginTransaction();
    
    // Get current balance
    $stmt = $conn->prepare("SELECT cash_balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn();
    
    // Update balance
    if ($type === 'deposit') {
        $new_balance = $current_balance + $amount;
    } else {
        if ($amount > $current_balance) {
            throw new Exception("Insufficient funds");
        }
        $new_balance = $current_balance - $amount;
    }
    
    // Update user balance
    $stmt = $conn->prepare("UPDATE users SET cash_balance = ? WHERE id = ?");
    $stmt->execute([$new_balance, $user_id]);
    
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $type, $amount]);
    
    $conn->commit();
    header("Location: ../cash.php?success=1");
} catch(Exception $e) {
    $conn->rollBack();
    header("Location: ../cash.php?error=" . urlencode($e->getMessage()));
}
exit();