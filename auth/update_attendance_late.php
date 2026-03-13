<?php
require_once('../config/db.php');
try {
    $pdo->exec("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'late') DEFAULT 'present'");
    echo "Attendance status updated to include 'late'.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
