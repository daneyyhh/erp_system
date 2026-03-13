<?php
/**
 * Project ERP - Local Database Configuration
 * Optimized for XAMPP / WAMP / Localhost
 */

$host = 'localhost';
$port = '3306';
$db   = 'smart_college_erp';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("<div style='font-family:sans-serif; padding:50px; text-align:center;'>
            <h2 style='color:#b91c1c;'>⛔ Local Database Error</h2>
            <p>Could not connect to the local MySQL server.</p>
            <p style='color:gray; font-size:14px;'>Make sure <b>XAMPP/WAMP Control Panel</b> is running and MySQL is started.</p>
            <p style='color:silver; font-size:10px; margin-top:20px;'>Error: " . $e->getMessage() . "</p>
         </div>");
}
?>
