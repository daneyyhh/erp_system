<?php
/**
 * Project ERP - Localhost Setup Wizard
 * Use this to automatically create your database and admin user.
 */

// 1. Initial Connection to MySQL (without selecting a DB)
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Create the Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS smart_college_erp");
    $pdo->exec("USE smart_college_erp");
    
    // 3. Import the Schema
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    // Simplified split for local XAMPP
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        $pdo->exec($stmt);
    }

    echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>
            <h2 style='color:#059669;'>✅ Local Setup Complete!</h2>
            <p>The database <b>smart_college_erp</b> has been created and populated.</p>
            <div style='background:#f0fdf4; padding:20px; border-radius:15px; margin:20px auto; max-width:400px; text-align:left;'>
                <b>Login Details:</b><br>
                Email: <code>admin@scholarly.com</code><br>
                Password: <code>admin123</code>
            </div>
            <a href='login.php' style='display:inline-block; margin-top:30px; padding:15px 30px; background:#6366f1; color:white; text-decoration:none; border-radius:12px; font-weight:bold;'>Go to Login Page</a>
          </div>";

} catch (PDOException $e) {
    echo "<div style='font-family:sans-serif; padding:50px; text-align:center;'>
            <h2 style='color:#b91c1c;'>❌ Setup Failed</h2>
            <p>Error: " . $e->getMessage() . "</p>
            <p>Ensure XAMPP MySQL is running.</p>
          </div>";
}
?>
