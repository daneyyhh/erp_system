<?php
/**
 * Project ERP - Smart Router for Vercel
 * This file captures all requests and routes them to the correct PHP file.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 1. Handle root request
if ($path === '/' || $path === '') {
    require __DIR__ . '/../index.php';
    exit;
}

// 2. Map path to local file system
$rootPath = dirname(__DIR__);
$file = $rootPath . $path;

// 3. Auto-append .php if missing (e.g. /login -> /login.php)
if (!file_exists($file) && file_exists($file . '.php')) {
    $file .= '.php';
}

// 4. If file exists, serve it
if (file_exists($file) && is_file($file)) {
    // CRITICAL: Change working directory so includes like '../config/db.php' work correctly
    chdir(dirname($file));
    require $file;
} else {
    // 5. Handle sub-directory index files (e.g. /admin/ -> /admin/dashboard.php)
    if (is_dir($file)) {
        $indexFiles = ['index.php', 'dashboard.php', 'login.php'];
        foreach ($indexFiles as $idx) {
            if (file_exists($file . DIRECTORY_SEPARATOR . $idx)) {
                chdir($file);
                require $file . DIRECTORY_SEPARATOR . $idx;
                exit;
            }
        }
    }
    
    // 404 Fallback
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The requested path <b>" . htmlspecialchars($path) . "</b> was not found on this ERP server.</p>";
}
