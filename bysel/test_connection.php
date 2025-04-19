<?php
try {
    $conn = new PDO("mysql:host=localhost;dbname=bysel_db", "root", "");
    echo "Connected successfully!";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}