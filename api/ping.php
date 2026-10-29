<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../config/db.php');
try {
    $users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo json_encode([
        'ok'     => true,
        'status' => 'online',
        'users'  => $users,
        'php'    => PHP_VERSION,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ]);
}

