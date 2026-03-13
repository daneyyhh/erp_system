<?php
require_once('../config/db.php');
try {
    $common_pass = password_hash('password123', PASSWORD_DEFAULT);
    
    // Check if John Doe exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['student@scholarly.com']);
    $user = $stmt->fetch();
    
    if (!$user) {
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('John Doe', 'student@scholarly.com', '$common_pass', 'student')");
        $student_user_id = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO students (user_id, roll_no, class) VALUES ($student_user_id, 'SCH-2026-001', 'BCA 1st Year')");
        echo "Created fresh student account: student@scholarly.com / password123<br>";
    } else {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$common_pass, $user['id']]);
        echo "Updated password for existing student account: student@scholarly.com / password123<br>";
    }

    echo "<h1>Student Login Fixed!</h1>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
