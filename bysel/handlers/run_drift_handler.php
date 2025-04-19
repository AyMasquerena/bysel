<?php
// handlers/run_drift_handler.php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';
require_once '../includes/price_engine.php';

// only admins
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// POST + CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_POST['csrf_token'])
    || $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    die("Security violation detected");
}

// run one round of drift
updateLivePrices($conn);

// back to admin with a message
header("Location: ../admin.php?success=Price drift executed");
exit();
