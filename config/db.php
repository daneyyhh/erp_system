<?php
// PROJECT ERP - VERCEL CLOUD ENGINE
// This configuration is optimized for Vercel Serverless Functions

$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost'); 
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306'); 
$db   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'smart_college_erp');
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("<div style='font-family:sans-serif; padding:40px; text-align:center;'>
            <h2 style='color:#b91c1c;'>⛔ Global ERP Offline</h2>
            <p>Database connection failed. Please check your <strong>Vercel Environment Variables</strong>.</p>
            <div style='background:#fef2f2; padding:20px; border-radius:15px; margin:20px auto; max-width:500px;'>
                <ul style='text-align:left;'>
                    <li>DB_HOST</li>
                    <li>DB_PORT</li>
                    <li>DB_NAME</li>
                    <li>DB_USER</li>
                    <li>DB_PASS</li>
                </ul>
            </div>
            <p style='color:gray; font-size:12px;'>Error: " . $e->getMessage() . "</p>
            <p style='color:silver; font-size:10px;'>Connected to: " . htmlspecialchars($host) . " on port " . htmlspecialchars($port) . "</p>
          </div>");
}
?>
