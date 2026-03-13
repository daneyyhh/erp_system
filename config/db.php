<?php
// PROJECT ERP - ULTRA-ROBUST CLOUD ENGINE
// Optimized for Vercel Serverless

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Smart variable lookup: Checks getenv, $_ENV, $_SERVER, and common aliases
 */
function get_db_var($key, $default) {
    $aliases = [
        'DB_HOST' => ['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST'],
        'DB_PORT' => ['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT'],
        'DB_NAME' => ['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE'],
        'DB_USER' => ['DB_USER', 'MYSQLUSER', 'MYSQL_USER'],
        'DB_PASS' => ['DB_PASS', 'MYSQLPASSWORD', 'MYSQL_PASSWORD']
    ];

    $search_keys = $aliases[$key] ?? [$key];
    foreach ($search_keys as $k) {
        $val = getenv($k) ?: ($_ENV[$k] ?? ($_SERVER[$k] ?? null));
        
        // Ignore generic local defaults for cloud hosting
        if ($val && ($key !== 'DB_HOST' || ($val !== 'mysql' && $val !== 'localhost'))) {
             return $val;
        }
    }
    return $default;
}

$host = get_db_var('DB_HOST', 'localhost');
$port = get_db_var('DB_PORT', '3306');
$db   = get_db_var('DB_NAME', 'smart_college_erp');
$user = get_db_var('DB_USER', 'root');
$pass = get_db_var('DB_PASS', '');

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $env_report = "";
    $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach($required as $r) {
        $check = get_db_var($r, null);
        $status = ($check && $check !== 'localhost' && $check !== 'mysql' && $check !== 'root') ? "✅ Found" : "❌ Missing";
        $env_report .= "<div style='display:flex; justify-content:space-between; margin-bottom:8px;'>
                            <span style='font-weight:bold;'>$r</span>
                            <span style='color:".($status === "✅ Found" ? "#059669" : "#dc2626")."'>$status</span>
                        </div>";
    }

    die("
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Offline | Enlight ERP</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap' rel='stylesheet'>
        <style>
            :root { --primary: #6366f1; --bg: #f8fafc; }
            body { background: var(--bg); font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
            .setup-card { background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); width: 100%; max-width: 500px; padding: 40px; }
            .status-box { background: #f1f5f9; border-radius: 16px; padding: 20px; margin-bottom: 25px; font-size: 14px; }
            .fw-800 { font-weight: 800; }
        </style>
    </head>
    <body>
        <div class='setup-card animate-fade'>
            <div class='text-center mb-4'>
                <div style='background: #fee2e2; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; display: inline-block; margin-bottom: 20px;'>
                    <span style='font-size: 24px;'>🔌</span>
                </div>
                <h3 class='fw-800'>Database Connection Required</h3>
                <p class='text-muted small'>The application is live, but your cloud database is unreachable.</p>
            </div>

            <div class='status-box'>
                $env_report
            </div>

            <div class='alert alert-primary border-0 small mb-4' style='border-radius: 12px; background: #eef2ff; color: #4338ca;'>
                <b>How to Fix:</b> Paste your database credentials into the <b>Environment Variables</b> section of your Vercel Dashboard.
            </div>
            
            <p style='color:#94a3b8; font-size:11px; text-align:center;'>Technical Error: " . htmlspecialchars($e->getMessage()) . "</p>
        </div>
    </body>
    </html>");
}
