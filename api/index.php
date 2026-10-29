<?php
/**
 * Project ERP - Production Vercel Router & Stateless Session Bridge
 */
ob_start();

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', '0');

if (!class_exists('VercelCookieSessionHandler')) {
    class VercelCookieSessionHandler implements SessionHandlerInterface {
        private string $secret;
        private string $cookieName = 'ERP_SESS_STATE';
        private bool $destroyed = false;

        public function __construct() {
            $rawSecret = getenv('POSTGRES_PASSWORD') ?: (getenv('DATABASE_URL') ?: 'purpleheart-erp-prod-secret-2026');
            $this->secret = hash('sha256', $rawSecret);
        }

        public function open(string $path, string $name): bool {
            return true;
        }

        public function close(): bool {
            return true;
        }

        public function read(string $id): string|false {
            if (!empty($_COOKIE[$this->cookieName])) {
                $parts = explode('.', $_COOKIE[$this->cookieName], 2);
                if (count($parts) === 2) {
                    [$b64, $sig] = $parts;
                    $expected = hash_hmac('sha256', $b64, $this->secret);
                    if (hash_equals($expected, $sig)) {
                        $decoded = base64_decode($b64, true);
                        if ($decoded !== false) {
                            return $decoded;
                        }
                    }
                }
            }
            $tmpFile = rtrim(sys_get_temp_dir(), '/\\') . '/sess_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
            if (is_file($tmpFile)) {
                return (string)@file_get_contents($tmpFile);
            }
            return '';
        }

        public function write(string $id, string $data): bool {
            if ($this->destroyed) {
                return true;
            }
            $tmpFile = rtrim(sys_get_temp_dir(), '/\\') . '/sess_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
            @file_put_contents($tmpFile, $data);

            if (!headers_sent()) {
                $b64 = base64_encode($data);
                $sig = hash_hmac('sha256', $b64, $this->secret);
                $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
                setcookie($this->cookieName, $b64 . '.' . $sig, [
                    'expires'  => time() + 86400 * 7,
                    'path'     => '/',
                    'secure'   => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            return true;
        }

        public function destroy(string $id): bool {
            $this->destroyed = true;
            $tmpFile = rtrim(sys_get_temp_dir(), '/\\') . '/sess_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }
            if (!headers_sent()) {
                setcookie($this->cookieName, '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            unset($_COOKIE[$this->cookieName]);
            return true;
        }

        public function gc(int $max_lifetime): int|false {
            return 0;
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_set_save_handler(new VercelCookieSessionHandler(), false);
}

register_shutdown_function(function () {
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_write_close();
    }
    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
});

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rootPath = dirname(__DIR__);

// Block path traversal or sensitive files
if (strpos($path, '..') !== false || preg_match('#^/\.(env|git|vercel)#i', $path)) {
    http_response_code(403);
    echo "403 Forbidden";
    exit;
}

if ($path === '/' || $path === '') {
    $path = '/index.php';
}

$file = $rootPath . $path;

if (!file_exists($file) && file_exists($file . '.php')) {
    $file .= '.php';
    $path .= '.php';
}

if (is_dir($file)) {
    $trimmed = rtrim($path, '/');
    if (file_exists($file . '/dashboard.php')) {
        $file = rtrim($file, '/') . '/dashboard.php';
        $path = $trimmed . '/dashboard.php';
    } elseif (file_exists($file . '/index.php')) {
        $file = rtrim($file, '/') . '/index.php';
        $path = $trimmed . '/index.php';
    }
}

if (file_exists($file) && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        $_SERVER['SCRIPT_FILENAME'] = $file;
        $_SERVER['SCRIPT_NAME'] = $path;
        $_SERVER['PHP_SELF'] = $path;
        chdir(dirname($file));
        require $file;
        exit;
    } else {
        $mimes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'csv'  => 'text/csv',
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'ico'  => 'image/x-icon',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'text/plain'));
        readfile($file);
        exit;
    }
}

http_response_code(404);
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>404 Not Found</title></head><body style='font-family:sans-serif;text-align:center;padding:60px;'><h2>404 - Page Not Found</h2><p>Requested path: " . htmlspecialchars($path) . "</p><a href='/login.php'>Return to Login</a></body></html>";

