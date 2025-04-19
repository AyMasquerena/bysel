<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'register.php') {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit();
}

$_SESSION['last_activity'] = time();

if (strpos($_SERVER['PHP_SELF'], 'admin') !== false && $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>