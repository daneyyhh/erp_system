<?php
require_once('../config/db.php');

try {
    // 1. Ensure Table Exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        type VARCHAR(100),
        status ENUM('pending', 'teacher_approved', 'approved', 'rejected') DEFAULT 'pending',
        FOREIGN KEY(student_id) REFERENCES students(id)
    )");

    // 2. Add Missing Columns if they don't exist
    $columns = [
        'request_date' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'teacher_remarks' => "TEXT",
        'admin_remarks' => "TEXT"
    ];

    foreach ($columns as $col => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM certificates LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE certificates ADD COLUMN $col $definition");
            echo "Added missing column: $col <br>";
        }
    }

    // 3. Update Enum if necessary (ensuring 'teacher_approved' exists)
    $pdo->exec("ALTER TABLE certificates MODIFY COLUMN status ENUM('pending', 'teacher_approved', 'approved', 'rejected') DEFAULT 'pending'");

    echo "<h1>Database Schema Synchronized!</h1>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
