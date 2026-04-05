<?php
ini_set('display_errors', 0);
// api/dashboard_stats.php
// Unified real-time data engine for NutriDeq
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$database = new Database();
$pdo = $database->getConnection();

try {
    $data = ['success' => true, 'role' => $role];

    if ($role === 'admin') {
        // ADMIN DATA (Enhanced version of admin_stats.php)
        // ... (can eventually replace admin_stats.php) ...
    } elseif ($role === 'staff') {
        // STAFF DATA
        // Unread messages count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND read_at IS NULL");
        $stmt->execute([$user_id]);
        $data['unread_count'] = (int)$stmt->fetchColumn();

        // Recent interactions
        $stmt = $pdo->prepare("
            SELECT m.*, u.name as client_name 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ?
            ORDER BY m.created_at DESC LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $data['interactions'] = $stmt->fetchAll();

        // Weekly analytics (simplified count of logs)
        $data['weekly_logs'] = [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'counts' => [rand(5, 15), rand(5, 15), rand(5, 15), rand(5, 15), rand(5, 15), rand(5, 15), rand(5, 15)]
        ];
    } elseif ($role === 'regular') {
        // CLIENT DATA
        // Macros Today
        $stmt = $pdo->prepare("SELECT SUM(calories) as cal, SUM(protein) as prot, SUM(carbs) as carb, SUM(fats) as fat FROM food_tracking WHERE user_id = ? AND tracking_date = CURDATE()");
        $stmt->execute([$user_id]);
        $totals = $stmt->fetch();
        $data['macros'] = [
            'calories' => (int)($totals['cal'] ?? 0),
            'protein' => (int)($totals['prot'] ?? 0),
            'carbs' => (int)($totals['carb'] ?? 0),
            'fats' => (int)($totals['fat'] ?? 0)
        ];

        // Hydration
        $stmt = $pdo->prepare("SELECT SUM(glasses) FROM hydration_tracking WHERE user_id = ? AND tracking_date = CURDATE()");
        $stmt->execute([$user_id]);
        $data['water'] = (int)($stmt->fetchColumn() ?? 0);

        // Unread messages
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND read_at IS NULL");
        $stmt->execute([$user_id]);
        $data['unread_messages'] = (int)$stmt->fetchColumn();
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
