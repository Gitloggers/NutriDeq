<?php
// Set default timezone for the application
date_default_timezone_set('Asia/Manila');

class Database
{
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct()
    {
        // Try to get the connection string first (Railway standard)
        $urlString = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: getenv('MYSQLURL');
        
        if ($urlString) {
            $url = parse_url($urlString);
            $this->host = $url['host'] ?? 'localhost';
            $this->dbname = ltrim($url['path'] ?? 'nutrideq', '/');
            $this->username = $url['user'] ?? 'root';
            $this->password = $url['pass'] ?? '';
            $this->port = $url['port'] ?? '3306';
        } else {
            // Fallback to individual Railway MySQL environment variables, then local settings
            $this->host = getenv('MYSQLHOST') ?: 'localhost';
            $this->dbname = getenv('MYSQLDATABASE') ?: 'nutrideq';
            $this->username = getenv('MYSQLUSER') ?: 'root';
            $this->password = getenv('MYSQLPASSWORD') ?: '';
            $this->port = getenv('MYSQLPORT') ?: '3306';
        }
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->dbname . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Ensure columns exist (for migration)
            $this->ensureColumnsExist();
        } catch (PDOException $exception) {
            // Re-throw as a standard exception so API callers can return clean JSON.
            // Connection debug info is available in XAMPP error logs.
            throw new Exception('Database connection failed: ' . $exception->getMessage());
        }

        return $this->conn;
    }

    private function ensureColumnsExist()
    {
        try {
            // Get all columns in the users table
            $stmt = $this->conn->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check and add columns if missing (compatibile with older MySQL versions)
            if (!in_array('is_verified', $columns)) {
                $this->conn->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
            }
            if (!in_array('verification_token', $columns)) {
                $this->conn->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL");
            }
            if (!in_array('has_password', $columns)) {
                $this->conn->exec("ALTER TABLE users ADD COLUMN has_password TINYINT(1) DEFAULT 1");
            }
        } catch (PDOException $e) {
            // Log error or ignore
        }
    }
}
?>