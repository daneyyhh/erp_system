<?php
// PROJECT ERP - SMART ENGINE
// Optimized for both Vercel & Localhost

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Smart variable lookup for Vercel env vars & Local defaults
 */
function get_db_var($key, $default) {
    $aliases = [
        'DB_HOST' => ['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST'],
        'DB_PORT' => ['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT'],
        'DB_USER' => ['DB_USER', 'MYSQLUSER', 'MYSQL_USER'],
        'DB_PASS' => ['DB_PASS', 'MYSQLPASSWORD', 'MYSQL_PASSWORD'],
        'DB_NAME' => ['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE']
    ];
    $search_keys = $aliases[$key] ?? [$key];
    foreach ($search_keys as $k) {
        $val = getenv($k) ?: ($_ENV[$k] ?? ($_SERVER[$k] ?? null));
        if ($val && ($key !== 'DB_HOST' || ($val !== 'mysql' && $val !== 'localhost'))) return $val;
    }
    return $default;
}

$host = get_db_var('DB_HOST', 'localhost');
$port = get_db_var('DB_PORT', '3306');
$db   = get_db_var('DB_NAME', 'smart_college_erp');
$user = get_db_var('DB_USER', 'root');
$pass = get_db_var('DB_PASS', '');
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
    if ($host === 'localhost') {
        die("<div style='font-family:sans-serif; padding:50px; text-align:center;'>
                <h2 style='color:#b91c1c;'>⛔ Local Database Error</h2>
                <p>Could not connect to the local MySQL server.</p>
                <p style='color:gray; font-size:14px;'>Make sure <b>XAMPP/WAMP Control Panel</b> is running and MySQL is started.</p>
                <p style='color:silver; font-size:10px; margin-top:20px;'>Error: " . $e->getMessage() . "</p>
             </div>");
    } else {
        die("
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Database Offline | Project ERP</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
            <style>
                body { background: #f8fafc; font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
                .setup-card { background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); width: 100%; max-width: 500px; padding: 40px; }
            </style>
        </head>
        <body>
            <div class='setup-card'>
                <div class='text-center mb-4'>
                    <div style='background: #fee2e2; width: 60px; height: 60px; font-size:24px; line-height: 60px; border-radius: 50%; display: inline-block; margin-bottom: 20px;'>🔌</div>
                    <h3 class='fw-bold' style='font-weight: 800;'>Database Connection Required</h3>
                    <p class='text-muted small'>The application is live, but your cloud database is unreachable.</p>
                </div>
                <div class='alert alert-primary border-0 small mb-4' style='border-radius: 12px; background: #eef2ff; color: #4338ca;'>
                    <b>How to Fix:</b> Add your Database credentials into the <b>Environment Variables</b> section of your Vercel Dashboard.
                </div>
            </div>
        </body>
        </html>
        ");
    }
}
?>
