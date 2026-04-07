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

            // Ensure columns and tables for recovery system exist
            $this->ensureRecoverySystemExists();
        } catch (PDOException $exception) {
            // Re-throw as a standard exception so API callers can return clean JSON.
            // Connection debug info is available in XAMPP error logs.
            throw new Exception('Database connection failed: ' . $exception->getMessage());
        }

        return $this->conn;
    }

    private function ensureRecoverySystemExists()
    {
        try {
            // Check for recovery_key column in users table
            $stmt = $this->conn->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('recovery_key', $columns)) {
                $this->conn->exec("ALTER TABLE users ADD COLUMN recovery_key VARCHAR(12) DEFAULT NULL");
                $this->conn->exec("CREATE INDEX idx_recovery_key ON users(recovery_key)");
            }

            // Create password_reset_requests table
            $this->conn->exec("CREATE TABLE IF NOT EXISTS password_reset_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                staff_id INT DEFAULT NULL,
                status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (PDOException $e) {
            // Silently fail or log in development
        }
    }
}
?>