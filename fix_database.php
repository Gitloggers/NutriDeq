<?php
require_once 'database.php';
echo "<h1>NutriDeq Cloud Database Repair 🛠️</h1>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // 1. Create internal_threads if missing
    $pdo->exec("CREATE TABLE IF NOT EXISTS internal_threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        created_by INT NOT NULL,
        participants JSON NOT NULL,
        status ENUM('open', 'closed', 'archived') DEFAULT 'open',
        last_message_at DATETIME NULL,
        updated_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    
    // Add missing updated_at column if table already existed without it
    $check = $pdo->query("SHOW COLUMNS FROM internal_threads LIKE 'updated_at'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE internal_threads ADD COLUMN updated_at DATETIME NULL AFTER last_message_at");
        echo "🔧 Added missing column: updated_at to internal_threads<br>";
    }
    echo "✅ Verified table: internal_threads<br>";

    // 2. Create internal_thread_messages if missing
    $pdo->exec("CREATE TABLE IF NOT EXISTS internal_thread_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        sender_id INT NOT NULL,
        sender_role VARCHAR(50) NOT NULL,
        message TEXT,
        attachment_path VARCHAR(255) NULL,
        message_type ENUM('text', 'image', 'file') DEFAULT 'text',
        read_by JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_thread (thread_id)
    ) ENGINE=InnoDB;");
    echo "✅ Verified table: internal_thread_messages<br>";

    // 3. Create conversations if missing
    $pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        dietitian_id INT NOT NULL,
        status ENUM('open', 'closed', 'archived') DEFAULT 'open',
        last_message_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_dietitian (dietitian_id)
    ) ENGINE=InnoDB;");
    echo "✅ Verified table: conversations<br>";

    // 4. Create wellness_messages if missing
    $pdo->exec("CREATE TABLE IF NOT EXISTS wellness_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_type ENUM('client', 'staff', 'ai') NOT NULL,
        sender_id INT NOT NULL,
        content TEXT,
        attachment_path VARCHAR(255) NULL,
        message_type ENUM('text', 'image', 'file') DEFAULT 'text',
        read_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conv (conversation_id)
    ) ENGINE=InnoDB;");
    echo "✅ Verified table: wellness_messages<br>";

    echo "<h2>🎉 Database is fully repaired!</h2>";
    echo "<p>You can now close this page and test your messages.</p>";

} catch (Exception $e) {
    echo "<h1>❌ Error during repair</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
