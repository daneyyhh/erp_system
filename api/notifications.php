<?php
/**
 * Notifications API
 * GET  ?user_id=X            — fetch unread notifications
 * POST action=mark_read&id=X — mark one as read
 * POST action=mark_all&user_id=X — mark all as read
 */
header('Content-Type: application/json');
require_once('../config/db.php');

// Auto-create tables
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    type       VARCHAR(60) DEFAULT 'General',
    is_read    TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    from_user   INT NOT NULL,
    to_user     INT NOT NULL,
    subject     VARCHAR(200) DEFAULT '',
    body        TEXT NOT NULL,
    is_read     TINYINT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $uid   = (int)($_GET['user_id'] ?? 0);
    $limit = max(1, (int)($_GET['limit'] ?? 10));
    if (!$uid) { echo json_encode(['ok'=>false,'error'=>'No user_id']); exit; }

    $rows = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT " . $limit);
    $rows->execute([$uid]);
    $notifs = $rows->fetchAll();

    $count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $count->execute([$uid]);
    $unread = $count->fetchColumn();

    echo json_encode(['ok'=>true,'notifications'=>$notifs,'unread'=>(int)$unread]);
    exit;
}

if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $data['action'] ?? '';

    if ($action === 'mark_read') {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$data['id']]);
        echo json_encode(['ok'=>true]);
    } elseif ($action === 'mark_all') {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$data['user_id']]);
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
    }
    exit;
}
