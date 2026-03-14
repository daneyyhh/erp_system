<?php
require 'config/db.php';
try {
    $pdo->exec("ALTER TABLE exam_applications ADD UNIQUE(user_id)");
    echo "Done Exam.\n";
} catch (Exception $e) { }

try {
    $insert = $pdo->prepare("INSERT INTO attendance (student_id, subject_id, date, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
    $insert->execute([1, 1, '2026-03-14', 'present']);
    echo "Done Attendance.\n";
} catch (Exception $e) { echo $e->getMessage(); }
?>
