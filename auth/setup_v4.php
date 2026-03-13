<?php
require_once('../config/db.php');

// Ensure tables exist and roles are correct
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'teacher', 'student', 'parent'),
    phone VARCHAR(20)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    roll_no VARCHAR(50) UNIQUE,
    class VARCHAR(50),
    semester INT,
    remarks TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

$pass = password_hash('admin123', PASSWORD_BCRYPT);

// Generic Users - UPSERT logic
$users = [
    ['Administrator', 'admin@college.com', 'admin'],
    ['Prof. Xavier', 'teacher@college.com', 'teacher'],
    ['John Doe', 'student@college.com', 'student'],
    ['Parent of John', 'parent@college.com', 'parent']
];

foreach ($users as $u) {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)");
    $stmt->execute([$u[0], $u[1], $pass, $u[2]]);
}

// Ensure Student Data for login
$student_user_id = $pdo->query("SELECT id FROM users WHERE email='student@college.com'")->fetchColumn();
$pdo->exec("INSERT IGNORE INTO students (user_id, roll_no, class, semester) VALUES ($student_user_id, 'BSBAD0555', 'BCA 1st Year', 1)");

// Mock Subject
$pdo->exec("INSERT IGNORE INTO subjects (name, code, semester) VALUES ('Basics of User Research', 'BB5012', 1)");

echo "<h1>Credentials Reset & Database Synced!</h1>";
echo "Use <b>admin123</b> for all accounts:<br>";
echo "- admin@college.com<br>- teacher@college.com<br>- student@college.com<br>- parent@college.com";
