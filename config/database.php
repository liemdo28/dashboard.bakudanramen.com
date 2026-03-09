<?php
/**
 * Database Configuration
 * Update these values with your shared hosting MySQL credentials
 */

define('DB_HOST', 'mysql-taskflow.bakudanramen.com');
define('DB_NAME', 'taskflow_db');        // Tên database trên hosting
define('DB_USER', 'liemdo');               // Username MySQL trên hosting
define('DB_PASS', 'liem@dt2155');                   // Password MySQL trên hosting
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'TaskFlow');
define('APP_URL', 'https://dashboard.bakudanramen.com');    // Đổi thành domain thật
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Session settings
define('SESSION_LIFETIME', 86400); // 24 hours

class Database {
    private static $instance = null;
    private $pdo;
    private $tableCache = [];
    private $columnCache = [];

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function tableExists($table) {
        if (array_key_exists($table, $this->tableCache)) {
            return $this->tableCache[$table];
        }

        $exists = (bool) $this->fetch(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = ?
             AND table_name = ?
             LIMIT 1",
            [DB_NAME, $table]
        );
        $this->tableCache[$table] = $exists;
        return $exists;
    }

    public function columnExists($table, $column) {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnCache)) {
            return $this->columnCache[$cacheKey];
        }

        if (!$this->tableExists($table)) {
            $this->columnCache[$cacheKey] = false;
            return false;
        }

        $exists = (bool) $this->fetch(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = ?
             AND table_name = ?
             AND column_name = ?
             LIMIT 1",
            [DB_NAME, $table, $column]
        );
        $this->columnCache[$cacheKey] = $exists;
        return $exists;
    }

    public function invalidateSchemaCache($table = null, $column = null) {
        if ($table === null) {
            $this->tableCache = [];
            $this->columnCache = [];
            return;
        }

        unset($this->tableCache[$table]);

        if ($column !== null) {
            unset($this->columnCache[$table . '.' . $column]);
            return;
        }

        foreach (array_keys($this->columnCache) as $cacheKey) {
            if (strpos($cacheKey, $table . '.') === 0) {
                unset($this->columnCache[$cacheKey]);
            }
        }
    }
}
