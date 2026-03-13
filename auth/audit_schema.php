<?php
require_once('../config/db.php');

try {
    // 1. Marks Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS marks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        internal INT DEFAULT 0,
        external INT DEFAULT 0,
        total INT DEFAULT 0,
        grade VARCHAR(5),
        UNIQUE(student_id, subject_id)
    )");

    // 2. Payments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        amount DECIMAL(10,2),
        status ENUM('pending', 'completed') DEFAULT 'pending',
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 3. Subjects Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        code VARCHAR(20) UNIQUE,
        semester INT
    )");

    echo "Detailed Schema Audit Complete!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
