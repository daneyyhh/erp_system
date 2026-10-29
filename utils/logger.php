<?php
function logToExcel($action, $user_id, $user_name, $details) {
    global $pdo;
    
    // 1. Database Logging
    if (!isset($pdo)) {
        require_once(__DIR__ . '/../config/db.php');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs (action, user_id, user_name, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$action, $user_id, $user_name, $details]);
    } catch (Exception $e) {
        error_log("DB Logging failed: " . $e->getMessage());
    }

    // 2. CSV Logging (Uses /tmp fallback on read-only serverless filesystems like Vercel)
    $baseDir = dirname(__DIR__) . '/logs';
    if (!is_writable(dirname(__DIR__)) && !is_writable($baseDir)) {
        $baseDir = rtrim(sys_get_temp_dir(), '/\\') . '/erp_logs';
    }
    $logPath = $baseDir . '/system_logs.csv';
    
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0777, true);
    }

    $fileExists = @file_exists($logPath);
    $f = @fopen($logPath, 'a');
    
    if ($f) {
        if (!$fileExists) {
            @fputcsv($f, ['Timestamp', 'Action', 'User ID', 'User Name', 'Details']);
        }
        @fputcsv($f, [date('Y-m-d H:i:s'), $action, $user_id, $user_name, $details]);
        @fclose($f);
    }
}
?>
