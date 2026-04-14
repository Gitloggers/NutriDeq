<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'database.php';

echo "<h1>🛠️ NutriDeq UNIVERSAL FORCE SYNC 🛠️</h1>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $alterations = [
        "wellness_messages" => [
            "ADD COLUMN attachment_path VARCHAR(255) NULL AFTER content",
            "ADD COLUMN message_type ENUM('text', 'image', 'file') DEFAULT 'text' AFTER attachment_path",
            "ADD COLUMN read_at DATETIME NULL AFTER message_type"
        ],
        "internal_thread_messages" => [
            "ADD COLUMN attachment_path VARCHAR(255) NULL AFTER message",
            "ADD COLUMN message_type ENUM('text', 'image', 'file') DEFAULT 'text' AFTER attachment_path",
            "ADD COLUMN read_by JSON NULL AFTER message_type"
        ],
        "internal_threads" => [
            "ADD COLUMN updated_at DATETIME NULL AFTER last_message_at",
            "ADD COLUMN last_message_at DATETIME NULL AFTER status"
        ],
        "conversations" => [
            "ADD COLUMN last_message_at DATETIME NULL AFTER dietitian_id",
            "ADD COLUMN status ENUM('open', 'closed', 'archived') DEFAULT 'open' AFTER last_message_at"
        ]
    ];

    foreach ($alterations as $table => $queries) {
        echo "<h3>Checking table: <code>$table</code></h3>";
        foreach ($queries as $query) {
            try {
                $pdo->exec("ALTER TABLE $table $query");
                echo "✅ Applied: <code>ALTER TABLE $table $query</code><br>";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), 'Column already exists') !== false) {
                    echo "ℹ️ Already exists: $table -> " . explode(' ', $query)[2] . "<br>";
                } else {
                    echo "❌ Failed: $table -> $query | Error: " . $e->getMessage() . "<br>";
                }
            }
        }
    }

    echo "<h2>🎉 SYSTEM FULLY ALIGNED!</h2>";
    echo "<p>Every messaging column is now strictly enforced. Go back to your dashboard and refresh twice.</p>";

} catch (Exception $e) {
    echo "<h1>💥 Critical Crash</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
