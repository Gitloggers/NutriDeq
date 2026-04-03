<?php
// get_internal_messages.php
session_start();
header('Content-Type: application/json');

require_once '../database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'regular';
$thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : 0;

if ($thread_id <= 0) {
    echo json_encode(['success' => false, 'messages' => [], 'error' => 'Invalid thread ID']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Verify access
    $stmt = $pdo->prepare("\n        SELECT t.* \n        FROM internal_threads t \n        WHERE t.id = ? AND (JSON_CONTAINS(t.participants, ?, '$') OR t.created_by = ?)\n    ");
    $user_json = json_encode($user_id);
    $stmt->execute([$thread_id, $user_json, $user_id]);
    $thread = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thread) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Get messages
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.name as sender_name,
               u.role as sender_role
        FROM internal_thread_messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.thread_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$thread_id]);
    $raw_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read (if not sent by me)
    try {
        $update = $pdo->prepare("
            UPDATE internal_thread_messages 
            SET read_by = JSON_ARRAY_APPEND(COALESCE(read_by, '[]'), '$', ?)
            WHERE thread_id = ? AND sender_id != ? 
            AND (read_by IS NULL OR JSON_CONTAINS(COALESCE(read_by, '[]'), ?, '$') = 0)
        ");
        $update->execute([$user_id, $thread_id, $user_id, $user_id]);
    } catch (Exception $e) {
        // Log error but don't fail request
        error_log("Error updating read status: " . $e->getMessage());
    }

    $messages = [];
    foreach ($raw_messages as $msg) {
        $is_me = ($msg['sender_id'] == $user_id);
        
        $messages[] = [
            'id' => $msg['id'],
            'message' => $msg['message'],
            'sender_id' => $msg['sender_id'],
            'sender_role' => $msg['sender_role'],
            'sender_name' => $msg['sender_name'],
            'type' => $is_me ? 'sent' : 'received',
            'created_at' => $msg['created_at'],
            'pretty_time' => date('g:i A', strtotime($msg['created_at'])),
            'read_status' => 1 // Simplified for now
        ];
    }

    echo json_encode(['success' => true, 'messages' => $messages, 'thread_status' => $thread['status'], 'thread_title' => $thread['title']]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
