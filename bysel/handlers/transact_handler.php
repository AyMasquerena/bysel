<?php
require_once '../includes/db_connect.php';
require_once '../includes/market_utils.php';   // NEW

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../transact.php");
    exit();
}

/* CSRF */
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

$user_id = $_SESSION['user_id'];
$type    = $_POST['transaction_type'];                 // buy | sell
$ticker  = strtoupper($_POST['ticker']);
$shares  = (int)$_POST['shares'];

/* ------------------------------------------------------------
   First, run any due pending orders if the market is open now
   ------------------------------------------------------------ */
processPendingOrders($conn);

try {
    /* ----------------------------------------------------------------
       If market is CLOSED → queue the order in pending_orders table
       ---------------------------------------------------------------- */
    if (!isMarketOpen($conn)) {

        // capture the current price so user sees what they queued at
        $stmt = $conn->prepare("SELECT price FROM stocks WHERE ticker = ?");
        $stmt->execute([$ticker]);
        $price = $stmt->fetchColumn();
        if (!$price) {
            throw new Exception("Stock not found");
        }

        $stmt = $conn->prepare("
            INSERT INTO pending_orders
                (user_id, ticker, shares, type, price_at_order)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $ticker, $shares, $type, $price]);

        header("Location: ../transact.php?success=queued");
        exit();
    }

    /* ----------------------------------------------------------------
       Market is OPEN → perform immediate trade (original logic)
       ---------------------------------------------------------------- */
    $conn->beginTransaction();

    // Get stock price
    $stmt = $conn->prepare("SELECT price FROM stocks WHERE ticker = ?");
    $stmt->execute([$ticker]);
    $stock_price = $stmt->fetchColumn();
    if (!$stock_price) {
        throw new Exception("Stock not found");
    }
    $total_cost = $shares * $stock_price;

    // Get user balance
    $stmt = $conn->prepare("SELECT cash_balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $current_balance = $stmt->fetchColumn();

    if ($type === 'buy') {
        if ($current_balance < $total_cost) {
            throw new Exception("Insufficient funds");
        }
        $new_balance = $current_balance - $total_cost;
    } else { // sell
        // Check available shares
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(
                CASE WHEN type='buy' THEN shares ELSE -shares END),0)
            FROM transactions
            WHERE user_id = ? AND ticker = ?
        ");
        $stmt->execute([$user_id, $ticker]);
        $available_shares = $stmt->fetchColumn();
        if ($available_shares < $shares) {
            throw new Exception("Insufficient shares to sell");
        }
        $new_balance = $current_balance + $total_cost;
    }

    // Update balance
    $stmt = $conn->prepare("UPDATE users SET cash_balance = ? WHERE id = ?");
    $stmt->execute([$new_balance, $user_id]);

    // Record transaction
    $stmt = $conn->prepare("
        INSERT INTO transactions (user_id, type, ticker, shares, amount)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $type, $ticker, $shares, $total_cost]);

    $conn->commit();
    header("Location: ../portfolio.php?success=1");
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header("Location: ../transact.php?error=" . urlencode($e->getMessage()));
}
exit();
