<?php
/**
 * Messages API
 * GET  ?user_id=X            — inbox
 * POST action=send            — send message
 * POST action=mark_read&id=X — mark read
 */
header('Content-Type: application/json');
require_once('../config/db.php');

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
    $uid = (int)($_GET['user_id'] ?? 0);
    if (!$uid) { echo json_encode(['ok'=>false]); exit; }

    $rows = $pdo->prepare("
        SELECT m.*, u.name AS from_name, u.role AS from_role
        FROM messages m
        JOIN users u ON m.from_user = u.id
        WHERE m.to_user = ?
        ORDER BY m.created_at DESC LIMIT 20
    ");
    $rows->execute([$uid]);
    $msgs = $rows->fetchAll();

    $unread = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_user=? AND is_read=0");
    $unread->execute([$uid]);

    echo json_encode(['ok'=>true,'messages'=>$msgs,'unread'=>(int)$unread->fetchColumn()]);
    exit;
}

if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $data['action'] ?? 'send';

    if ($action === 'send') {
        $pdo->prepare("INSERT INTO messages (from_user,to_user,subject,body) VALUES (?,?,?,?)")
            ->execute([$data['from_user'],$data['to_user'],$data['subject']??'',$data['body']]);
        echo json_encode(['ok'=>true]);
    } elseif ($action === 'mark_read') {
        $pdo->prepare("UPDATE messages SET is_read=1 WHERE id=?")->execute([$data['id']]);
        echo json_encode(['ok'=>true]);
    }
    exit;
}
