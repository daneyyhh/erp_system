<?php
session_start();
require_once('../config/db.php');
require_once('../utils/logger.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (!in_array($role, ['student', 'teacher', 'parent'])) {
        header("Location: ../register.php?error=invalid_role");
        exit();
    }
    
    // validation
    if (empty($name) || empty($email) || empty($password)) {
        header("Location: ../register.php?error=missing_fields");
        exit();
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            header("Location: ../register.php?error=email_exists");
            exit();
        }
        
        // check specific fields based on role
        if ($role === 'student') {
            $roll_no = trim($_POST['roll_no']);
            $class = trim($_POST['class']);
            
            if (empty($roll_no)) {
                $pdo->rollBack();
                header("Location: ../register.php?error=missing_fields");
                exit();
            }
            
            // Check if roll number exists
            $stmt = $pdo->prepare("SELECT id FROM students WHERE roll_no = ?");
            $stmt->execute([$roll_no]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                header("Location: ../register.php?error=roll_exists");
                exit();
            }
        } else if ($role === 'teacher') {
            $subject = trim($_POST['subject']);
            $department = trim($_POST['department']);
            
            if (empty($subject)) {
                $pdo->rollBack();
                header("Location: ../register.php?error=missing_fields");
                exit();
            }
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $hashed_password, $role]);
        $user_id = $pdo->lastInsertId();
        
        // Handle specific tables
        if ($role === 'student') {
            $stmt = $pdo->prepare("INSERT INTO students (user_id, roll_no, class) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $roll_no, $class]);
            logToExcel('User Registration', $user_id, $name, "New student registered: Roll No. $roll_no");
            
        } else if ($role === 'teacher') {
            $stmt = $pdo->prepare("INSERT INTO teachers (user_id, subject, department) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $subject, $department]);
            logToExcel('User Registration', $user_id, $name, "New teacher registered: Subject $subject");
            
        } else if ($role === 'parent') {
            // Parents only exist in the users table currently
            logToExcel('User Registration', $user_id, $name, "New parent registered");
        }
        
        // Commit transaction
        $pdo->commit();
        
        header("Location: ../register.php?success=1");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Registration Error: " . $e->getMessage());
        header("Location: ../register.php?error=system");
        exit();
    }
} else {
    header("Location: ../register.php");
    exit();
}
?>
