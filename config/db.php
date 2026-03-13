<?php
// PROJECT ERP - ULTRA-ROBUST CLOUD ENGINE
// This file is designed to find a database connection regardless of the environment.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Smart variable lookup: Checks getenv, $_ENV, $_SERVER, and common aliases
 */
function get_db_var($key, $default) {
    // 1. Check Session (from the Web Setup Wizard)
    if (isset($_SESSION['DB_CREDS'][$key])) return $_SESSION['DB_CREDS'][$key];

    // 2. Check common aliases (Vercel/others use different naming)
    $aliases = [
        'DB_HOST' => ['DB_HOST', 'MYSQL_HOST', 'MYSQLHOST', 'DATABASE_URL'],
        'DB_PORT' => ['DB_PORT', 'MYSQL_PORT', 'MYSQLPORT'],
        'DB_NAME' => ['DB_NAME', 'MYSQL_DATABASE', 'MYSQLDATABASE', 'DB_DATABASE'],
        'DB_USER' => ['DB_USER', 'MYSQL_USER', 'MYSQLUSER', 'DB_USERNAME'],
        'DB_PASS' => ['DB_PASS', 'MYSQL_PASSWORD', 'MYSQLPASSWORD', 'DB_PASSWORD']
    ];

    $search_keys = $aliases[$key] ?? [$key];
    foreach ($search_keys as $k) {
        $val = getenv($k) ?: ($_ENV[$k] ?? ($_SERVER[$k] ?? null));
        if ($val) return ($key === 'DB_HOST' && strpos($val, 'mysql://') === 0) ? parse_url($val, PHP_URL_HOST) : $val;
    }

    return $default;
}

// Map the variables
$host = get_db_var('DB_HOST', 'localhost');
$port = get_db_var('DB_PORT', '3306');
$db   = get_db_var('DB_NAME', 'smart_college_erp');
$user = get_db_var('DB_USER', 'root');
$pass = get_db_var('DB_PASS', '');
$charset = 'utf8mb4';

// Handle Manual Setup Submission
if (isset($_POST['setup_db'])) {
    $_SESSION['DB_CREDS'] = [
        'DB_HOST' => $_POST['m_host'],
        'DB_PORT' => $_POST['m_port'],
        'DB_NAME' => $_POST['m_db'],
        'DB_USER' => $_POST['m_user'],
        'DB_PASS' => $_POST['m_pass']
    ];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

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
    // DIAGNOSTIC CHECKLIST
    $env_report = "";
    $required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach($required as $r) {
        $check = get_db_var($r, null);
        $status = ($check && $check !== 'localhost' && $check !== 'root') ? "✅ Found" : "❌ Missing";
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
        <title>Connection Required | Enlight ERP</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            :root { --primary: #6366f1; --bg: #f8fafc; }
            body { background: var(--bg); font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
            .setup-card { background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); width: 100%; max-width: 500px; padding: 40px; }
            .status-box { background: #f1f5f9; border-radius: 16px; padding: 20px; margin-bottom: 25px; font-size: 14px; }
            .btn-primary { background: var(--primary); border: none; padding: 12px; border-radius: 12px; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class='setup-card animate-up'>
            <div class='text-center mb-4'>
                <div style='background: #fee2e2; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; display: inline-block; margin-bottom: 20px;'>
                    <span style='font-size: 24px;'>🔌</span>
                </div>
                <h3 class='fw-bold'>Cloud Connection Required</h3>
                <p class='text-muted small'>We couldn't detect your database credentials automatically.</p>
            </div>

            <div class='status-box'>
                $env_report
            </div>

            <form method='POST' action=''>
                <div class='row g-2 mb-2'>
                    <div class='col-8'><input type='text' name='m_host' class='form-control small' placeholder='Database Host (e.g. mysql-xx.aiven.com)' required></div>
                    <div class='col-4'><input type='text' name='m_port' class='form-control small' placeholder='Port' value='24564'></div>
                </div>
                <input type='text' name='m_db' class='form-control mb-2' placeholder='Database Name' value='defaultdb'>
                <input type='text' name='m_user' class='form-control mb-2' placeholder='User (e.g. avnadmin)'>
                <input type='password' name='m_pass' class='form-control mb-4' placeholder='Database Password'>
                
                <button type='submit' name='setup_db' class='btn btn-primary w-100 mb-3'>Connect & Save to Session</button>
            </form>
            
            <p class='text-center smallest text-muted'>Error: " . $e->getMessage() . "</p>
        </div>
    </body>
    </html>");
}
