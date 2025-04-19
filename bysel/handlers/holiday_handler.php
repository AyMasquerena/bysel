<?php
// handlers/holiday_handler.php
//--------------------------------------------------------------
// Add or delete a market holiday (admin only)
//--------------------------------------------------------------

require_once '../includes/db_connect.php';   // starts session + PDO
require_once '../includes/auth_check.php';  // ensures logged‑in

/* ---- admin guard ------------------------------------------- */
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

/* ---- POST + CSRF ------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

/* ---- determine action -------------------------------------- */
$action = $_POST['action'] ?? '';
$today  = (new DateTime())->format('Y-m-d');

try {
    switch ($action) {
        /* =====================================================
           ADD a holiday
        ======================================================*/
        case 'add':
            $dateStr     = $_POST['holiday_date'] ?? '';
            $description = trim($_POST['description'] ?? '');

            // Basic validation
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                throw new Exception("Invalid date format (YYYY-MM-DD expected)");
            }
            if ($description === '') {
                throw new Exception("Description cannot be empty");
            }

            // Insert or replace description
            $stmt = $conn->prepare("
                INSERT INTO market_holidays (holiday_date, description)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE description = VALUES(description)
            ");
            $stmt->execute([$dateStr, $description]);

            header("Location: ../admin.php?success=Holiday added");
            break;

        /* =====================================================
           DELETE a holiday
        ======================================================*/
        case 'delete':
            $dateStr = $_POST['holiday_date'] ?? '';

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                throw new Exception("Invalid date");
            }

            $stmt = $conn->prepare("DELETE FROM market_holidays WHERE holiday_date = ?");
            $stmt->execute([$dateStr]);

            header("Location: ../admin.php?success=Holiday removed");
            break;

        default:
            throw new Exception("Unknown action");
    }

} catch (Exception $e) {
    header("Location: ../admin.php?error=" . urlencode($e->getMessage()));
}
exit();
