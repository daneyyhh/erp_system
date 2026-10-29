<?php
require_once('config/db.php');

try {
    // Set default phone number
    $pdo->exec("UPDATE users SET phone = '+91 6235985969'");
    
    // Create password resets table
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL
    )");
    
    echo "Database updated successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
