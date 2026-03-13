<?php
/**
 * Project ERP - Smart Router for Vercel
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rootPath = dirname(__DIR__);

// 1. Handle root request
if ($path === '/' || $path === '') {
    $entry = $rootPath . '/index.php';
    if (file_exists($entry)) {
        require $entry;
    } else {
        echo "Root index.php not found at: " . htmlspecialchars($entry);
    }
    exit;
}

// 2. Map path to local file system
$file = $rootPath . $path;

// 3. Auto-append .php if missing
if (!file_exists($file) && file_exists($file . '.php')) {
    $file .= '.php';
}

// 4. If file exists, serve it
if (file_exists($file) && is_file($file)) {
    chdir(dirname($file));
    require $file;
    exit;
}

// 5. Handle directory (e.g. /admin)
if (is_dir($file)) {
    $index = $file . '/dashboard.php';
    if (file_exists($index)) {
        chdir($file);
        require $index;
        exit;
    }
}

// 404 Fallback with Debug info
http_response_code(404);
echo "<h3>404 - File Not Found</h3>";
echo "Requested: " . htmlspecialchars($path) . "<br>";
echo "Resolved to: " . htmlspecialchars($file);
