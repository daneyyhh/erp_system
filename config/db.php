<?php
// PROJECT ERP - CLOUD DATABASE ENGINE
// Optimized for Vercel deployment

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Robust variable lookup: Searches for database credentials in environment variables and common aliases.
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
        if ($val) {
            // Ignore generic defaults like 'mysql' or 'localhost' if we are looking for a cloud host
            if ($key === 'DB_HOST' && ($val === 'mysql' || $val === 'localhost')) continue;
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
    // DIAGNOSTIC CHECKLIST
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
        <meta charset='UTF-8'><title>Connection Required | Enlight ERP</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            body { background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: sans-serif; }
            .setup-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 450px; padding: 40px; }
        </style>
    </head>
    <body>
        <div class='setup-card'>
            <div class='text-center mb-4'>
                <h3 class='fw-bold'>🔌 Database Offline</h3>
                <p class='text-muted small'>The app is running, but cannot reach your database.</p>
            </div>
            <div style='background: #f1f5f9; border-radius: 12px; padding: 15px; margin-bottom: 20px;'>
                $env_report
            </div>
            <div class='alert alert-warning small'>
                <b>Action Required:</b> Go to Vercel Settings -> Environment Variables and ensure your database details are added correctly.
            </div>
            <p style='font-size: 10px; color: gray; text-align:center;'>Error: ".htmlspecialchars($e->getMessage())."</p>
        </div>
    </body>
    </html>");
}
