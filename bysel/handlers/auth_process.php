<?php
// handlers/auth_process.php

require_once '../includes/db_connect.php'; // This starts the session and connects to the DB

// Ensure we're handling POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

// Determine which action (register or login) the form is requesting
$action = $_POST['action'] ?? 'login';

$username = strtolower(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';

try {
    if ($action === 'register') {
        // Registration flow
        $email = trim($_POST['email'] ?? '');
        
        // 1) Check if the username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        
        if ($stmt->rowCount() > 0) {
            // Username taken
            header("Location: ../register.php?error=username_taken");
            exit();
        }
        
        // 2) Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // 3) Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (username, password_hash, email)
            VALUES (:username, :password_hash, :email)
        ");
        $stmt->execute([
            'username'       => $username,
            'password_hash'  => $hashedPassword,
            'email'          => $email
        ]);
        
        // 4) Redirect on success
        header("Location: ../login.php?success=registered");
        exit();
        
    } else {
        // Login flow
        $stmt = $conn->prepare("
            SELECT id, username, password_hash, role 
            FROM users 
            WHERE username = :username
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify user exists and password is correct
        if (!$user || !password_verify($password, $user['password_hash'])) {
            header("Location: ../login.php?error=invalid_credentials");
            exit();
        }
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: ../admin.php");
        } else {
            header("Location: ../index.php");
        }
        exit();
    }
    
} catch (PDOException $e) {
    // Database error
    header("Location: ../login.php?error=" . urlencode($e->getMessage()));
    exit();
} catch (Exception $e) {
    // General error
    header("Location: ../login.php?error=" . urlencode($e->getMessage()));
    exit();
}