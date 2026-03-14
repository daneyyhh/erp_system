<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once('../config/db.php');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=system_logs_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Timestamp', 'Action', 'User ID', 'User Name', 'Details'));

$stmt = $pdo->query("SELECT * FROM logs ORDER BY timestamp DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}
fclose($output);
exit();
?>
