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
    // 1. Check Session (Manual entries from the Setup Form)
    if (isset($_SESSION['DB_CREDS'][$key])) return $_SESSION['DB_CREDS'][$key];

    // 2. Check Environment & Aliases
    $aliases = [
        'DB_HOST' => ['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST'],
        'DB_PORT' => ['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT'],
        'DB_NAME' => ['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE', 'DB_DATABASE'],
        'DB_USER' => ['DB_USER', 'MYSQLUSER', 'MYSQL_USER', 'DB_USERNAME'],
        'DB_PASS' => ['DB_PASS', 'MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DB_PASSWORD']
    ];

    $search_keys = $aliases[$key] ?? [$key];
    foreach ($search_keys as $k) {
        $val = getenv($k) ?: ($_ENV[$k] ?? ($_SERVER[$k] ?? null));
        if ($val && ($key !== 'DB_HOST' || ($val !== 'mysql' && $val !== 'localhost'))) {
             return $val;
        }
    }
    return $default;
}

// Handle Manual Setup Submission (This is what makes the buttons work!)
if (isset($_POST['setup_db'])) {
    $_SESSION['DB_CREDS'] = [
        'DB_HOST' => $_POST['m_host'] ?? 'localhost',
        'DB_PORT' => $_POST['m_port'] ?? '3306',
        'DB_NAME' => $_POST['m_db'] ?? 'smart_college_erp',
        'DB_USER' => $_POST['m_user'] ?? 'root',
        'DB_PASS' => $_POST['m_pass'] ?? ''
    ];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
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
        <title>Connection Required | Enlight ERP</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap' rel='stylesheet'>
        <style>
            :root { --primary: #6366f1; --bg: #f8fafc; }
            body { background: var(--bg); font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
            .setup-card { background: white; border-radius: 32px; box-shadow: 0 30px 60px rgba(0,0,0,0.12); width: 100%; max-width: 550px; padding: 50px; }
            .status-box { background: #f1f5f9; border-radius: 20px; padding: 20px; margin-bottom: 30px; font-size: 14px; }
            .form-control { border-radius: 12px; padding: 12px 18px; border: 2px solid #e2e8f0; }
            .form-control:focus { border-color: var(--primary); box-shadow: none; }
            .btn-primary { background: var(--primary); border: none; padding: 15px; border-radius: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
            .fw-800 { font-weight: 800; }
        </style>
    </head>
    <body>
        <div class='setup-card animate-up'>
            <div class='text-center mb-5'>
                <div style='background: #eef2ff; width: 64px; height: 64px; line-height: 64px; border-radius: 20px; display: inline-block; margin-bottom: 25px;'>
                    <span style='font-size: 28px;'>⚡</span>
                </div>
                <h3 class='fw-800 mb-2'>Cloud Connection Setup</h3>
                <p class='text-muted small'>Fill in your new database credentials below to bring the ERP online.</p>
            </div>

            <form method='POST' action=''>
                <div class='status-box mb-4'>
                    <div class='small fw-800 text-muted uppercase mb-3' style='font-size: 10px; letter-spacing: 1px;'>Status Check</div>
                    $env_report
                </div>

                <div class='row g-3 mb-3'>
                    <div class='col-md-9'>
                        <label class='smallest fw-bold text-muted mb-1'>Hostname</label>
                        <input type='text' name='m_host' class='form-control' placeholder='e.g. tidbcloud.com or planetscale.com' required>
                    </div>
                    <div class='col-md-3'>
                        <label class='smallest fw-bold text-muted mb-1'>Port</label>
                        <input type='text' name='m_port' class='form-control' value='3306'>
                    </div>
                </div>

                <div class='mb-3'>
                    <label class='smallest fw-bold text-muted mb-1'>Database Name</label>
                    <input type='text' name='m_db' class='form-control' placeholder='e.g. erp_db' required>
                </div>

                <div class='mb-3'>
                    <label class='smallest fw-bold text-muted mb-1'>Username</label>
                    <input type='text' name='m_user' class='form-control' placeholder='Database user' required>
                </div>

                <div class='mb-5'>
                    <label class='smallest fw-bold text-muted mb-1'>Secure Password</label>
                    <input type='password' name='m_pass' class='form-control' placeholder='••••••••'>
                </div>

                <button type='submit' name='setup_db' class='btn btn-primary w-100 shadow-sm'>
                    Connect & Go Live <i class='fas fa-arrow-right ms-2'></i>
                </button>
            </form>

            <div class='mt-5 pt-4 border-top text-center'>
                <p style='color:#94a3b8; font-size:11px;'>Diagnostic Error: " . htmlspecialchars($e->getMessage()) . "</p>
            </div>
        </div>
    </body>
    </html>");
}
