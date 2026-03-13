<?php
require_once('../config/db.php');
require_once('../utils/logger.php');

try {
    // 1. Ensure generic users exist
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    $users = [
        ['Admin User', 'admin@college.com', 'admin'],
        ['Test Professor', 'teacher@college.com', 'teacher'],
        ['Test Student', 'student@college.com', 'student'],
        ['Test Parent', 'parent@college.com', 'parent']
    ];
    
    foreach ($users as $u) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$u[1]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$u[0], $u[1], $hash, $u[2]]);
            $user_id = $pdo->lastInsertId();
            
            if ($u[2] == 'student') {
                $stmt = $pdo->prepare("INSERT INTO students (user_id, roll_no, class, semester) VALUES (?, 'CAMP-000', 'BCA 1st Year', 1)");
                $stmt->execute([$user_id]);
            } elseif ($u[2] == 'teacher') {
                $stmt = $pdo->prepare("INSERT INTO teachers (user_id, subject, department) VALUES (?, 'Computer Science', 'IT')");
                $stmt->execute([$user_id]);
            }
        }
    }

    // 2. Ensure log file is ready and writable
    $log_path = __DIR__ . '/../logs/system_logs.csv';
    if (!file_exists(dirname($log_path))) {
        mkdir(dirname($log_path), 0777, true);
    }
    if (!file_exists($log_path)) {
        $headers = ["Timestamp", "Action", "User ID", "User Name", "Details"];
        $fp = fopen($log_path, 'w');
        fputcsv($fp, $headers);
        fclose($fp);
    }
    chmod($log_path, 0666);

    // 3. Add some subjects for attendance
    $subjects = [
        ['Database Management', 'BCA101', 'IT', 1],
        ['Web Technologies', 'BCA102', 'IT', 1],
        ['Advanced Java', 'BCA301', 'IT', 5],
        ['Software Engineering', 'BCA201', 'IT', 3]
    ];
    foreach ($subjects as $s) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO subjects (name, code, department, semester) VALUES (?, ?, ?, ?)");
        $stmt->execute($s);
    }

    echo "<b>Setup Complete! All generic logins fixed. Log file initialized.</b>";
    logToExcel('System Setup', 0, 'System', 'Re-initialized logins and logs.');

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
