<?php
// send_internal_message.php
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

// Only staff and admin can send internal messages
if (!in_array($user_role, ['staff', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$thread_id = isset($_POST['thread_id']) ? intval($_POST['thread_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($thread_id <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
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

    if ($thread['status'] !== 'open') {
        echo json_encode(['success' => false, 'error' => 'Thread is closed']);
        exit;
    }

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO internal_thread_messages (thread_id, sender_id, sender_role, message, read_by, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $thread_id, 
        $user_id, 
        $user_role, 
        $message, 
        json_encode([$user_id])
    ]);
    
    $message_id = $pdo->lastInsertId();

    // Update thread last message time
    $update = $pdo->prepare("UPDATE internal_threads SET last_message_at = NOW(), updated_at = NOW() WHERE id = ?");
    $update->execute([$thread_id]);

    // Fetch the inserted message to return
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.name as sender_name,
               u.role as sender_role
        FROM internal_thread_messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$message_id]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);

    $formatted_message = [
        'id' => $msg['id'],
        'message' => $msg['message'],
        'sender_id' => $msg['sender_id'],
        'sender_role' => $msg['sender_role'],
        'sender_name' => $msg['sender_name'],
        'type' => 'sent',
        'created_at' => $msg['created_at'],
        'pretty_time' => date('g:i A', strtotime($msg['created_at'])),
        'read_status' => 1
    ];

    echo json_encode(['success' => true, 'message' => $formatted_message]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
