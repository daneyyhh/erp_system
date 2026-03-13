<?php
session_start();
require_once('../utils/logger.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'Unknown Action';
    $details = $_POST['details'] ?? '';
    
    $user_id = $_SESSION['user_id'] ?? 'Guest';
    $user_name = $_SESSION['user_name'] ?? 'Guest';
    
    logToExcel($action, $user_id, $user_name, $details);
    
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
