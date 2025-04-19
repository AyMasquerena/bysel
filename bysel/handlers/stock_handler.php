<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth_check.php';

// Validate admin access
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

// Process form data
$action = $_POST['action'];
$company = htmlspecialchars($_POST['company']);
$ticker = strtoupper(htmlspecialchars($_POST['ticker']));
$price = (float)$_POST['price'];
$volume = (int)$_POST['volume'];

try {
    $conn->beginTransaction();
    
    switch($action) {
        case 'create':
            $stmt = $conn->prepare("
			INSERT INTO stocks
			(company_name, ticker, price, volume,
			 day_open_price, day_high_price, day_low_price, last_update)
			VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
			");
			$stmt->execute([
			$company,
			$ticker,
			$price,
			$volume,
			$price,   // day_open_price
			$price,   // day_high_price
			$price    // day_low_price
			]);
            $message = "Stock created successfully";
            break;
            
        case 'update':
            $stmt = $conn->prepare("UPDATE stocks SET 
                                  company_name = ?,
                                  price = ?,
                                  volume = ?
                                  WHERE ticker = ?");
            $stmt->execute([$company, $price, $volume, $ticker]);
            $message = "Stock updated successfully";
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM stocks WHERE ticker = ?");
            $stmt->execute([$ticker]);
            $message = "Stock deleted successfully";
            break;
            
        default:
            throw new Exception("Invalid action type");
    }
    
    $conn->commit();
    header("Location: ../admin.php?success=" . urlencode($message));
} catch(PDOException $e) {
    $conn->rollBack();
    $error = "Database error: " . $e->getMessage();
    header("Location: ../admin.php?error=" . urlencode($error));
} catch(Exception $e) {
    $conn->rollBack();
    header("Location: ../admin.php?error=" . urlencode($e->getMessage()));
}
exit();