<?php
// handlers/order_cancel_handler.php
//--------------------------------------------------------------
// Cancels a still‑pending order that belongs to the logged‑in user
//--------------------------------------------------------------

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

/* ---- POST + CSRF ------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../orders.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

/* ---- validate --------------------------------------------------- */
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    header("Location: ../orders.php?error=" . urlencode("Invalid order ID"));
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    /* only cancel if it’s still pending and belongs to this user */
    $stmt = $conn->prepare("
        UPDATE pending_orders
        SET status = 'cancelled', executed_at = NOW()
        WHERE order_id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$order_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        header("Location: ../orders.php?error=" . urlencode("Unable to cancel (already executed or not yours)"));
    } else {
        header("Location: ../orders.php?success=cancelled");
    }
} catch (PDOException $e) {
    header("Location: ../orders.php?error=" . urlencode($e->getMessage()));
}
exit();
