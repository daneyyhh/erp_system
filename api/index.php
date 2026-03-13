<?php
/**
 * Project ERP - Smart Router for Vercel
 * This router ensures clean URLs and maps requests to the correct PHP files.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rootPath = dirname(__DIR__);

// 1. Handle root request
if ($path === '/' || $path === '') {
    $path = '/index.php';
}

// 2. Map path to local file system
$file = $rootPath . $path;

// 3. Auto-append .php if missing
if (!file_exists($file) && file_exists($file . '.php')) {
    $file .= '.php';
    $path .= '.php';
}

// 4. Update Server variables to trick PHP into thinking it's a direct file access
// This fixes active nav states and relative path calculations
$_SERVER['SCRIPT_FILENAME'] = $file;
$_SERVER['SCRIPT_NAME'] = $path;
$_SERVER['PHP_SELF'] = $path;

// 5. If file exists, serve it
if (file_exists($file) && is_file($file)) {
    // Handle CSV/Text files differently from PHP
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        chdir(dirname($file));
        require $file;
    } else {
        // Simple static file serving for non-asset binaries/text
        $mime = ($ext === 'csv') ? 'text/csv' : 'text/plain';
        header("Content-Type: $mime");
        readfile($file);
    }
    exit;
}

// 6. Handle directory (e.g. /admin)
if (is_dir($file)) {
    $index = $file . '/dashboard.php';
    if (file_exists($index)) {
        $_SERVER['PHP_SELF'] = $path . '/dashboard.php';
        chdir($file);
        require $index;
        exit;
    }
}

// 404 Fallback
http_response_code(404);
echo "<h3>404 - File Not Found</h3>";
echo "Requested: " . htmlspecialchars($path);
