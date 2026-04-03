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
        // Use Railway MySQL environment variables, fallback to local settings
        $this->host = getenv('MYSQLHOST') ?: 'localhost';
        $this->dbname = getenv('MYSQLDATABASE') ?: 'nutrideq';
        $this->username = getenv('MYSQLUSER') ?: 'root';
        $this->password = getenv('MYSQLPASSWORD') ?: '';
        $this->port = getenv('MYSQLPORT') ?: '3306';
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->dbname . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            // Detailed debug output for Railway deployment
            echo "<div style='color:#721c24; background-color:#f8d7da; border-color:#f5c6cb; padding:15px; margin:20px; border-radius:5px; font-family:sans-serif;'>";
            echo "<h3 style='margin-top:0;'>Database Connection Error</h3>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<h4>Environment Variables (Debug):</h4>";
            echo "<ul>";
            echo "<li><strong>MYSQLHOST:</strong> " . htmlspecialchars(getenv('MYSQLHOST') ?: 'Not Set') . " (Current: " . htmlspecialchars($this->host) . ")</li>";
            echo "<li><strong>MYSQL_HOST:</strong> " . htmlspecialchars(getenv('MYSQL_HOST') ?: 'Not Set') . "</li>";
            echo "<li><strong>MYSQLPORT:</strong> " . htmlspecialchars(getenv('MYSQLPORT') ?: 'Not Set') . " (Current: " . htmlspecialchars($this->port) . ")</li>";
            echo "<li><strong>MYSQLDATABASE:</strong> " . htmlspecialchars(getenv('MYSQLDATABASE') ?: 'Not Set') . " (Current: " . htmlspecialchars($this->dbname) . ")</li>";
            echo "<li><strong>MYSQLUSER:</strong> " . htmlspecialchars(getenv('MYSQLUSER') ?: 'Not Set') . " (Current: " . htmlspecialchars($this->username) . ")</li>";
            echo "<li><strong>MYSQLPASSWORD length:</strong> " . strlen(getenv('MYSQLPASSWORD') ?: '') . " chars</li>";
            echo "<li><strong>Fallback checking \$_SERVER['MYSQLHOST']:</strong> " . htmlspecialchars($_SERVER['MYSQLHOST'] ?? 'Not Set') . "</li>";
            echo "</ul>";
            echo "<p><em>Note: If connecting internally on Railway, the host is typically something like mysql.railway.internal or mariadb.railway.internal</em></p>";
            echo "</div>";
            
            // Halt execution so we can see the debug message clearly
            die();
        }

        return $this->conn;
    }
}
?>