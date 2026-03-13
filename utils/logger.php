<?php
function logToExcel($action, $user_id, $user_name, $details) {
    global $pdo;
    
    // Ensure $pdo is available (might need to include config if not global)
    if (!isset($pdo)) {
        require_once(__DIR__ . '/../config/db.php');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO logs (action, user_id, user_name, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$action, $user_id, $user_name, $details]);
    } catch (Exception $e) {
        // Fail silently in production to avoid crashing the user flow
        error_log("Logging failed: " . $e->getMessage());
    }
}
?>
