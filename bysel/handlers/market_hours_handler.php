<?php
// handlers/market_hours_handler.php
//--------------------------------------------------------------
// Receives the Market‑Hours form from admin.php
// • Validates CSRF token and admin role
// • Updates the single row in `market_hours`
// • Redirects back with success / error message
//--------------------------------------------------------------

require_once '../includes/db_connect.php';   // starts session + PDO
require_once '../includes/auth_check.php';  // confirms logged‑in

/* ---- admin guard ------------------------------------------------ */
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

/* ---- POST + CSRF ------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

/* ---- sanitize & validate times --------------------------------- */
$open  = $_POST['open_time']  ?? '';
$close = $_POST['close_time'] ?? '';

if (!preg_match('/^\d{2}:\d{2}$/', $open) || !preg_match('/^\d{2}:\d{2}$/', $close)) {
    header("Location: ../admin.php?error=" . urlencode("Invalid time format"));
    exit();
}

try {
    $stmt = $conn->prepare("
        INSERT INTO market_hours (id, open_time, close_time)
        VALUES (1, ?, ?)
        ON DUPLICATE KEY UPDATE open_time = VALUES(open_time),
                                close_time = VALUES(close_time)
    ");
    $stmt->execute([$open . ':00', $close . ':00']);  // append seconds

    header("Location: ../admin.php?success=Market hours updated");
} catch (PDOException $e) {
    header("Location: ../admin.php?error=" . urlencode($e->getMessage()));
}
exit();