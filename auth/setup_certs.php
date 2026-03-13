<?php
require_once('../config/db.php');

// Create/Update Certificates table for multi-level approval
$pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    type VARCHAR(100),
    status ENUM('pending', 'teacher_approved', 'approved', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    teacher_remarks TEXT,
    admin_remarks TEXT,
    FOREIGN KEY(student_id) REFERENCES students(id)
)");

echo "Certificates System Database Synced!";
?>
