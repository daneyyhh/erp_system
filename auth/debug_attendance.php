<?php
require_once('../config/db.php');
$stmt = $pdo->query("DESCRIBE attendance");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
