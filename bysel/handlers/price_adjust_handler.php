<?php
// handlers/price_adjust_handler.php
//--------------------------------------------------------------
// Admin tool: randomly nudges a stock price within ±percent
// or 0…‑percent if "negative_only" is set.
//--------------------------------------------------------------

require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

/* ---- sanitize inputs --------------------------------------- */
$ticker = strtoupper(trim($_POST['ticker'] ?? ''));
$percent = (float)($_POST['percent'] ?? 0);
$negOnly = isset($_POST['negative_only']);

if (!preg_match('/^[A-Z]{1,5}$/', $ticker) || $percent <= 0) {
    header("Location: ../admin.php?error=" . urlencode("Invalid input"));
    exit();
}

/* ---- fetch current price ----------------------------------- */
$stmt = $conn->prepare("SELECT price FROM stocks WHERE ticker = ?");
$stmt->execute([$ticker]);
$current = $stmt->fetchColumn();

if ($current === false) {
    header("Location: ../admin.php?error=" . urlencode("Ticker not found"));
    exit();
}

/* ---- compute random delta ---------------------------------- */
$maxDelta = $percent / 100.0;               // convert to decimal
if ($negOnly) {
    $delta = -mt_rand(0, (int)($maxDelta * 10000)) / 10000;   // 0 … -max
} else {
    $delta = mt_rand(- (int)($maxDelta * 10000), (int)($maxDelta * 10000)) / 10000;
}

$newPrice = round(max(0.01, $current * (1 + $delta)), 2);

/* ---- update ------------------------------------------------- */
$stmt = $conn->prepare("
    UPDATE stocks
    SET price = ?, last_update = NOW()
    WHERE ticker = ?
");
$stmt->execute([$newPrice, $ticker]);

header("Location: ../admin.php?success=" . urlencode(
    "Price for $ticker adjusted from $" . number_format($current,2) .
    " to $" . number_format($newPrice,2)
));
exit();
