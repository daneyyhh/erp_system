<?php
require_once('config/db.php');
try {
    $users = $pdo->query("SELECT name, email, role FROM users")->fetchAll();
    echo "<h3>Users in Database:</h3>";
    echo "<table border='1'><tr><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['name']}</td><td>{$u['email']}</td><td>{$u['role']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
