<?php
session_start();
require_once '../database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

// Only for staff currently, but can be expanded
if ($user_role !== 'staff') {
    echo json_encode(['success' => false, 'messages' => [], 'unread_count' => 0]);
    exit();
}

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // 1. Get Unread Count (Total)
    $unread_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM wellness_messages wm
        JOIN conversations c ON wm.conversation_id = c.id
        WHERE c.dietitian_id = ? AND wm.sender_type = 'client' AND wm.read_at IS NULL
    ");
    $unread_stmt->execute([$user_id]);
    $unread_count = (int) $unread_stmt->fetchColumn();

    // 2. Get Recent Messages (for the list)
    // We want the latest message per client or just the latest raw messages?
    // Dashboard usually shows a list of recent distinct conversations or just recent messages.
    // The previous dashboard query showed individual messages. Let's stick to that but make it smarter.
    // Actually, showing the latest 5 messages (even from same client) is what the old code did.

    $msgs_stmt = $pdo->prepare("
        SELECT 
            wm.id,
            c.name as client_name,
            c.id as client_id,
            wm.content as message,
            wm.created_at,
            wm.read_at,
            wm.message_type
        FROM wellness_messages wm
        JOIN conversations conv ON wm.conversation_id = conv.id
        JOIN clients c ON conv.client_id = c.id
        WHERE conv.dietitian_id = ? AND wm.sender_type = 'client'
        ORDER BY wm.created_at DESC
        LIMIT 5
    ");
    $msgs_stmt->execute([$user_id]);
    $raw_messages = $msgs_stmt->fetchAll(PDO::FETCH_ASSOC);

    $messages = [];
    foreach ($raw_messages as $msg) {
        // Calculate time ago
        $created_time = strtotime($msg['created_at']);
        $time_diff = time() - $created_time;
        $minutes_ago = floor($time_diff / 60);

        $time_display = '';
        // Exact date and time as requested
        $time_display = date('M j, g:i A', $created_time);

        $content = htmlspecialchars($msg['message']);
        if ($msg['message_type'] === 'image')
            $content = '📷 [Image]';
        elseif ($msg['message_type'] === 'file')
            $content = '📎 [File]';

        if (strlen($content) > 80) {
            $content = substr($content, 0, 80) . '...';
        }

        $messages[] = [
            'id' => $msg['id'],
            'client_name' => htmlspecialchars($msg['client_name']),
            'client_id' => $msg['client_id'],
            'message' => $content,
            'is_read' => !is_null($msg['read_at']),
            'time_display' => $time_display,
            'timestamp' => $created_time // for JS sorting if needed
        ];
    }

    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>