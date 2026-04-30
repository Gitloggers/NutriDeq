<?php
// A temporary script to clear message interactions on the live Aiven database.
require_once '../database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Truncate the relevant messaging tables
    $pdo->exec("TRUNCATE TABLE wellness_messages");
    $pdo->exec("TRUNCATE TABLE conversations");
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h2>Success</h2>";
    echo "<p>Live database messages have been cleared successfully.</p>";
    echo "<p>You can now delete this file from the codebase.</p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>Failed to clear messages: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
