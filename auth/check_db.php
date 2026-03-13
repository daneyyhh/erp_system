<?php
require_once('../config/db.php');
$stmt = $pdo->query("SELECT email, role FROM users");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
