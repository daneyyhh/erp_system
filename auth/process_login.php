<?php
session_start();
require_once('../config/db.php');
require_once('../utils/logger.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    try {
        // AUTO-DETECT ROLE: No need to pass 'role' from the form anymore
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM `users` WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                logToExcel('User Login', $user['id'], $user['name'], "Logged in as " . $user['role']);
                
                header("Location: ../" . $user['role'] . "/dashboard.php");
                exit();
            }
        }
            header("Location: ../login.php?error=invalid_credentials");
            exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>
