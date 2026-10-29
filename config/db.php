<?php
/**
 * Project ERP - Production-Ready Smart Database Engine
 * Supports:
 * 1. Neon / Cloud PostgreSQL (via PGHOST / DATABASE_URL — native pdo_pgsql or Neon HTTP SQL API fallback)
 * 2. Cloud MySQL (via DB_HOST / MYSQLHOST)
 * 3. Localhost XAMPP/WAMP MySQL (127.0.0.1 / localhost:3306)
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!class_exists('SmartPDOStatement')) {
    class SmartPDOStatement {
        private $stmt;
        private $httpBridge;
        private string $sql;
        private array $rows = [];
        private int $cursor = 0;
        private int $rowCountVal = 0;
        private $lastId = null;

        public function __construct($stmtOrBridge, string $sql = '') {
            if ($stmtOrBridge instanceof NeonHttpBridge) {
                $this->httpBridge = $stmtOrBridge;
                $this->sql = $sql;
            } else {
                $this->stmt = $stmtOrBridge;
            }
        }

        public function execute(?array $params = null): bool {
            if ($this->stmt) {
                return $this->stmt->execute($params);
            }
            $res = $this->httpBridge->runQuery($this->sql, $params ?? []);
            $this->rows = $res['rows'] ?? [];
            $this->rowCountVal = $res['rowCount'] ?? count($this->rows);
            $this->cursor = 0;
            return true;
        }

        public function fetch(int $mode = PDO::FETCH_ASSOC) {
            if ($this->stmt) {
                return $this->stmt->fetch($mode);
            }
            if ($this->cursor >= count($this->rows)) {
                return false;
            }
            $row = $this->rows[$this->cursor++];
            if ($mode === PDO::FETCH_COLUMN) {
                $vals = array_values($row);
                return $vals[0] ?? false;
            }
            if ($mode === PDO::FETCH_NUM) {
                return array_values($row);
            }
            return $row;
        }

        public function fetchAll(int $mode = PDO::FETCH_ASSOC, ...$args): array {
            if ($this->stmt) {
                return $this->stmt->fetchAll($mode, ...$args);
            }
            if ($mode === PDO::FETCH_COLUMN) {
                $colIdx = $args[0] ?? 0;
                $out = [];
                foreach ($this->rows as $r) {
                    $vals = array_values($r);
                    $out[] = $vals[$colIdx] ?? null;
                }
                return $out;
            }
            if ($mode === PDO::FETCH_KEY_PAIR) {
                $out = [];
                foreach ($this->rows as $r) {
                    $vals = array_values($r);
                    if (count($vals) >= 2) {
                        $out[$vals[0]] = $vals[1];
                    }
                }
                return $out;
            }
            if ($mode === PDO::FETCH_NUM) {
                return array_map('array_values', $this->rows);
            }
            return $this->rows;
        }

        public function fetchColumn(int $column = 0) {
            if ($this->stmt) {
                return $this->stmt->fetchColumn($column);
            }
            if ($this->cursor >= count($this->rows)) {
                return false;
            }
            $row = $this->rows[$this->cursor++];
            $vals = array_values($row);
            return $vals[$column] ?? false;
        }

        public function rowCount(): int {
            if ($this->stmt) {
                return $this->stmt->rowCount();
            }
            return $this->rowCountVal;
        }

        public function bindValue($param, $value, $type = PDO::PARAM_STR): bool {
            if ($this->stmt) {
                return $this->stmt->bindValue($param, $value, $type);
            }
            return true;
        }
    }

    class NeonHttpBridge {
        private string $endpoint;
        private string $connStr;
        public string $lastInsertIdVal = '0';

        public function __construct(string $host, string $connStr) {
            $this->endpoint = "https://{$host}/sql";
            $this->connStr  = $connStr;
        }

        public function runQuery(string $sql, array $params = []): array {
            // Convert ? placeholders to $1, $2, ... outside single-quoted strings
            $pgSql = $this->convertPlaceholders($sql);
            $isInsert = preg_match('/^\s*INSERT\s+INTO\s+/i', $pgSql) && !preg_match('/\bRETURNING\b/i', $pgSql);
            if ($isInsert) {
                $pgSql = rtrim($pgSql, "; \t\n\r") . " RETURNING *";
            }

            $payload = json_encode([
                'query'  => $pgSql,
                'params' => array_values($params),
            ]);

            $ch = curl_init($this->endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Neon-Connection-String: ' . $this->connStr,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT        => 15,
            ]);
            $raw  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($raw === false) {
                throw new PDOException("Neon HTTP connection error: " . $err);
            }
            $data = json_decode($raw, true);
            if ($code >= 400) {
                $msg = $data['message'] ?? $raw;
                throw new PDOException("Database error ($code): " . $msg);
            }

            $rows = $data['rows'] ?? [];
            if ($isInsert && !empty($rows[0]['id'])) {
                $this->lastInsertIdVal = (string)$rows[0]['id'];
            }
            return [
                'rows'     => $rows,
                'rowCount' => (int)($data['rowCount'] ?? count($rows)),
            ];
        }

        private function convertPlaceholders(string $sql): string {
            $out = '';
            $inSingle = false;
            $idx = 1;
            $len = strlen($sql);
            for ($i = 0; $i < $len; $i++) {
                $c = $sql[$i];
                if ($c === "'" && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inSingle = !$inSingle;
                    $out .= $c;
                } elseif ($c === '?' && !$inSingle) {
                    $out .= '$' . ($idx++);
                } else {
                    $out .= $c;
                }
            }
            return $out;
        }
    }

    class SmartPDO {
        private ?PDO $pdo = null;
        private ?NeonHttpBridge $http = null;
        private string $driver;

        public function __construct($pdoOrBridge, string $driver = 'pgsql') {
            $this->driver = $driver;
            if ($pdoOrBridge instanceof PDO) {
                $this->pdo = $pdoOrBridge;
            } elseif ($pdoOrBridge instanceof NeonHttpBridge) {
                $this->http = $pdoOrBridge;
            }
        }

        public static function translateSql(string $sql, string $driver = 'pgsql'): string {
            if ($driver !== 'pgsql') {
                return $sql;
            }
            $s = trim($sql);

            // Ignore MySQL-only session pragmas
            if (preg_match('/^\s*SET\s+FOREIGN_KEY_CHECKS\s*=/i', $s)) {
                return 'SELECT 1';
            }

            // Strip backticks
            $s = str_replace('`', '', $s);

            // DDL conversions
            if (preg_match('/^\s*(CREATE|ALTER)\s+TABLE\b/i', $s)) {
                $s = preg_replace('/\bINT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i', 'SERIAL PRIMARY KEY', $s);
                $s = preg_replace('/\bTINYINT\b/i', 'SMALLINT', $s);
                $s = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $s);
                $s = preg_replace('/\bENUM\s*\([^)]+\)/i', 'VARCHAR(50)', $s);
                $s = preg_replace('/\bON\s+UPDATE\s+CURRENT_TIMESTAMP\b/i', '', $s);
                $s = preg_replace('/\bUNIQUE\s+KEY\s+[a-zA-Z0-9_]+\s*\(([^)]+)\)/i', 'UNIQUE ($1)', $s);
                $s = preg_replace('/\bADD\s+COLUMN\s+(?!IF\s+NOT\s+EXISTS\b)([a-zA-Z0-9_]+)/i', 'ADD COLUMN IF NOT EXISTS $1', $s);
                $s = preg_replace('/\s+AFTER\s+[a-zA-Z0-9_]+\s*$/i', '', $s);
            }

            // Function & expression conversions
            $s = preg_replace('/\bCURDATE\s*\(\s*\)/i', 'CURRENT_DATE', $s);
            $s = preg_replace('/\bMONTH\s*\(\s*([a-zA-Z0-9_.]+)\s*\)/i', 'EXTRACT(MONTH FROM $1)::int', $s);
            $s = preg_replace('/\bYEAR\s*\(\s*([a-zA-Z0-9_.]+)\s*\)/i', 'EXTRACT(YEAR FROM $1)::int', $s);
            $s = preg_replace('/\bDATE_ADD\s*\(\s*NOW\s*\(\s*\)\s*,\s*INTERVAL\s+(\d+\s+[A-Z]+)\s*\)/i', "(NOW() + INTERVAL '$1')", $s);
            $s = preg_replace('/\bTIMESTAMPDIFF\s*\(\s*SECOND\s*,\s*([a-zA-Z0-9_.]+)\s*,\s*NOW\s*\(\s*\)\s*\)/i', 'EXTRACT(EPOCH FROM (NOW() - $1))::int', $s);
            $s = preg_replace('/\bCAST\s*\(\s*SUBSTRING_INDEX\s*\(\s*([a-zA-Z0-9_.]+)\s*,\s*\'-\'\s*,\s*-1\s*\)\s+AS\s+UNSIGNED\s*\)/i', "CAST(SPLIT_PART($1, '-', 2) AS INTEGER)", $s);
            $s = preg_replace('/\bSUM\s*\(\s*([a-zA-Z0-9_.]+)\s*=\s*(\'[^\']*\')\s*\)/i', 'SUM(CASE WHEN $1 = $2 THEN 1 ELSE 0 END)', $s);

            // FIELD(col, 'a', 'b', ...) -> CASE col WHEN 'a' THEN 1 ... END
            if (stripos($s, 'FIELD(') !== false) {
                $s = preg_replace_callback('/\bFIELD\s*\(\s*([a-zA-Z0-9_.]+)\s*,([^)]+)\)/i', function ($m) {
                    $col = $m[1];
                    $items = array_map('trim', explode(',', $m[2]));
                    $case = "CASE $col ";
                    foreach ($items as $idx => $val) {
                        $rank = $idx + 1;
                        $case .= "WHEN $val THEN $rank ";
                    }
                    $case .= "ELSE 99 END";
                    return $case;
                }, $s);
            }

            // INSERT IGNORE INTO -> INSERT INTO ... ON CONFLICT DO NOTHING
            if (preg_match('/^\s*INSERT\s+IGNORE\s+INTO\b/i', $s)) {
                $s = preg_replace('/^\s*INSERT\s+IGNORE\s+INTO\b/i', 'INSERT INTO', $s);
                if (stripos($s, 'ON CONFLICT') === false) {
                    $s = rtrim($s, "; \t\n\r") . ' ON CONFLICT DO NOTHING';
                }
            }

            // ON DUPLICATE KEY UPDATE -> ON CONFLICT (...) DO UPDATE SET ...
            if (preg_match('/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/i', $s)) {
                $conflictTarget = '(id)';
                if (preg_match('/\bINTO\s+([a-zA-Z0-9_]+)/i', $s, $tm)) {
                    $tbl = strtolower($tm[1]);
                    $map = [
                        'marks'            => '(student_id, subject_id)',
                        'hostel_mess_menu' => '(day, meal_type)',
                        'bus_locations'    => '(bus_id)',
                        'users'            => '(email)',
                        'settings'         => '(setting_key)',
                        'subjects'         => '(code)',
                        'students'         => '(roll_no)',
                    ];
                    $conflictTarget = $map[$tbl] ?? '(id)';
                }
                $s = preg_replace('/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/i', "ON CONFLICT $conflictTarget DO UPDATE SET", $s);
                $s = preg_replace('/\bVALUES\s*\(\s*([a-zA-Z0-9_]+)\s*\)/i', 'EXCLUDED.$1', $s);
            }

            return $s;
        }

        public function prepare(string $sql, array $options = []) {
            $translated = self::translateSql($sql, $this->driver);
            if ($this->pdo) {
                $stmt = $this->pdo->prepare($translated, $options);
                return new SmartPDOStatement($stmt);
            }
            return new SmartPDOStatement($this->http, $translated);
        }

        public function query(string $sql, ?int $fetchMode = null, ...$fetchModeArgs) {
            $translated = self::translateSql($sql, $this->driver);
            if ($this->pdo) {
                $stmt = $fetchMode !== null
                    ? $this->pdo->query($translated, $fetchMode, ...$fetchModeArgs)
                    : $this->pdo->query($translated);
                return new SmartPDOStatement($stmt);
            }
            $stmt = new SmartPDOStatement($this->http, $translated);
            $stmt->execute([]);
            return $stmt;
        }

        public function exec(string $sql) {
            $translated = self::translateSql($sql, $this->driver);
            if ($this->pdo) {
                return $this->pdo->exec($translated);
            }
            $res = $this->http->runQuery($translated, []);
            return $res['rowCount'];
        }

        public function lastInsertId(?string $name = null) {
            if ($this->pdo) {
                try {
                    return $this->pdo->lastInsertId($name);
                } catch (\Throwable $e) {
                    return '0';
                }
            }
            return $this->http->lastInsertIdVal;
        }

        public function beginTransaction(): bool {
            if ($this->pdo) {
                return $this->pdo->inTransaction() ? true : $this->pdo->beginTransaction();
            }
            return true;
        }

        public function commit(): bool {
            if ($this->pdo) {
                return $this->pdo->inTransaction() ? $this->pdo->commit() : true;
            }
            return true;
        }

        public function rollBack(): bool {
            if ($this->pdo) {
                return $this->pdo->inTransaction() ? $this->pdo->rollBack() : true;
            }
            return true;
        }

        public function inTransaction(): bool {
            return $this->pdo ? $this->pdo->inTransaction() : false;
        }

        public function quote(string $string, int $type = PDO::PARAM_STR): string {
            if ($this->pdo) {
                return $this->pdo->quote($string, $type);
            }
            return "'" . str_replace("'", "''", $string) . "'";
        }

        public function setAttribute(int $attribute, $value): bool {
            if ($this->pdo) {
                return $this->pdo->setAttribute($attribute, $value);
            }
            return true;
        }
    }
}

if (!function_exists('get_env_val')) {
    function get_env_val(array $keys, $default = null) {
        foreach ($keys as $k) {
            $v = getenv($k);
            if ($v === false || $v === '') {
                $v = $_ENV[$k] ?? ($_SERVER[$k] ?? null);
            }
            if ($v !== null && $v !== '') {
                return trim((string)$v, "\"'");
            }
        }
        return $default;
    }
}

// 1. Check for Neon / PostgreSQL Environment Variables (Production on Vercel)
$pg_host = get_env_val(['PGHOST', 'POSTGRES_HOST']);
$pg_db   = get_env_val(['PGDATABASE', 'POSTGRES_DATABASE'], 'neondb');
$pg_user = get_env_val(['PGUSER', 'POSTGRES_USER']);
$pg_pass = get_env_val(['PGPASSWORD', 'POSTGRES_PASSWORD']);
$db_url  = get_env_val(['DATABASE_URL', 'POSTGRES_URL']);

if ((!$pg_host || !$pg_user) && $db_url && stripos($db_url, 'postgres') === 0) {
    $parts = parse_url($db_url);
    $pg_host = $pg_host ?: ($parts['host'] ?? null);
    $pg_user = $pg_user ?: ($parts['user'] ?? null);
    $pg_pass = $pg_pass ?: ($parts['pass'] ?? null);
    $pg_db   = !empty($parts['path']) ? ltrim($parts['path'], '/') : $pg_db;
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,
    PDO::ATTR_TIMEOUT            => 8,
];

if ($pg_host && $pg_user) {
    try {
        if (extension_loaded('pdo_pgsql')) {
            $dsn = "pgsql:host={$pg_host};port=5432;dbname={$pg_db};sslmode=require";
            $rawPdo = new PDO($dsn, $pg_user, $pg_pass, $options);
            $pdo = new SmartPDO($rawPdo, 'pgsql');
        } else {
            $connStr = $db_url ?: "postgresql://{$pg_user}:{$pg_pass}@{$pg_host}/{$pg_db}?sslmode=require";
            $bridge = new NeonHttpBridge($pg_host, $connStr);
            $pdo = new SmartPDO($bridge, 'pgsql');
        }
    } catch (\Throwable $e) {
        // Fallback to Neon HTTP SQL API if direct TCP/PDO fails
        try {
            $connStr = $db_url ?: "postgresql://{$pg_user}:{$pg_pass}@{$pg_host}/{$pg_db}?sslmode=require";
            $bridge = new NeonHttpBridge($pg_host, $connStr);
            $pdo = new SmartPDO($bridge, 'pgsql');
        } catch (\Throwable $e2) {
            http_response_code(500);
            die("Database connection error.");
        }
    }
} else {
    // 2. Cloud or Local MySQL Fallback
    $host = get_env_val(['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST'], 'localhost');
    $port = get_env_val(['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT'], '3306');
    $db   = get_env_val(['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE'], 'smart_college_erp');
    $user = get_env_val(['DB_USER', 'MYSQLUSER', 'MYSQL_USER'], 'root');
    $pass = get_env_val(['DB_PASS', 'MYSQLPASSWORD', 'MYSQL_PASSWORD'], '');

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    try {
        $rawPdo = new PDO($dsn, $user, $pass, $options);
        $pdo = new SmartPDO($rawPdo, 'mysql');
    } catch (\PDOException $e) {
        http_response_code(500);
        die("<div style='font-family:sans-serif; padding:50px; text-align:center;'>
                <h2 style='color:#b91c1c;'>⛔ Database Connection Error</h2>
                <p>Could not connect to the database server.</p>
             </div>");
    }
}
?>
