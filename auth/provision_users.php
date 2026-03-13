<?php
require_once('../config/db.php');

$password = password_hash('password123', PASSWORD_DEFAULT);

// Clean up everything to avoid duplicate key errors
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE users");
$pdo->exec("TRUNCATE TABLE students");
$pdo->exec("TRUNCATE TABLE teachers");
$pdo->exec("TRUNCATE TABLE fees");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Create Admin
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['Enlight Admin', 'admin@scholarly.com', $password, 'admin']);

// Create Teacher
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['Prof. Aryan', 'teacher@scholarly.com', $password, 'teacher']);
$teacher_user_id = $pdo->lastInsertId();
$pdo->prepare("INSERT INTO teachers (user_id, subject, department) VALUES (?, 'Computer Science', 'IT')")
    ->execute([$teacher_user_id]);

// Create Student
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['John Doe', 'student@scholarly.com', $password, 'student']);

// Get student user_id for profiles
$student_user_id = $pdo->lastInsertId();
$pdo->prepare("INSERT INTO students (user_id, roll_no, class, semester) VALUES (?, 'BCA-2024-001', 'BCA 2nd Year', 2)")
    ->execute([$student_user_id]);
$student_id = $pdo->lastInsertId();

// Add some sample fees for the student
$pdo->prepare("INSERT INTO fees (student_id, amount, due_date, status) VALUES (?, 45000, '2024-10-15', 'pending')")
    ->execute([$student_id]);
$pdo->prepare("INSERT INTO fees (student_id, amount, due_date, status) VALUES (?, 1500, '2024-10-20', 'pending')")
    ->execute([$student_id]);

// Create Parent
$pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
    ->execute(['Mr. Robert', 'parent@scholarly.com', $password, 'parent']);

echo "CLEAN RESET: All 4 Roles Provisioned Successfully. Passwords: password123";
?>
