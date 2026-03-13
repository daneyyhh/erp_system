<?php
require_once('../config/db.php');
$stmt = $pdo->query("SELECT u.email, u.role, s.roll_no FROM users u LEFT JOIN students s ON u.id = s.user_id");
echo "<h2>Registered Users:</h2><table border='1'><tr><th>Email</th><th>Role</th><th>Roll No (Student Only)</th></tr>";
while($row = $stmt->fetch()) {
    echo "<tr><td>{$row['email']}</td><td>{$row['role']}</td><td>" . ($row['roll_no'] ?? 'N/A') . "</td></tr>";
}
echo "</table>";

// Reset student password for testing
$new_pass = password_hash('student1', PASSWORD_DEFAULT);
$pdo->exec("UPDATE users SET password = '$new_pass' WHERE role = 'student'");
echo "<br><b>All student passwords have been reset to: student1</b>";
?>
