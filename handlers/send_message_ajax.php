<?php
// send_message_ajax.php
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

if ($user_role === 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admins are read-only']);
    exit;
}
$recipient_id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : 0;
$message_text = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($recipient_id <= 0 || empty($message_text)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // 1. Participant Resolution
    $client_id = 0;
    $dietitian_id = 0;
    $sender_type = '';

    if ($user_role === 'staff') {
        $sender_type = 'staff';
        $dietitian_id = $user_id;
        $client_id = $recipient_id;
    } else {
        $sender_type = 'client';
        $dietitian_id = $recipient_id;

        // Get Client ID
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_email'] ?? '']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$row)
            throw new Exception("Client profile not found");
        $client_id = $row['id'];
    }

    // 2. Find or Create Conversation
    $conv_id = 0;
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE client_id = ? AND dietitian_id = ? LIMIT 1");
    $stmt->execute([$client_id, $dietitian_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $conv_id = $row['id'];
        // Update timestamp
        $upd = $pdo->prepare("UPDATE conversations SET last_message_at = NOW(), status = 'open' WHERE id = ?");
        $upd->execute([$conv_id]);
    } else {
        // Create new
        $ins = $pdo->prepare("INSERT INTO conversations (client_id, dietitian_id, status, last_message_at) VALUES (?, ?, 'open', NOW())");
        $ins->execute([$client_id, $dietitian_id]);
        $conv_id = $pdo->lastInsertId();
    }

    // 3. Insert Message
    $ins_msg = $pdo->prepare("INSERT INTO wellness_messages (conversation_id, sender_type, sender_id, content, created_at) VALUES (?, ?, ?, ?, NOW())");
    $ins_msg->execute([$conv_id, $sender_type, $user_id, $message_text]);
    $new_msg_id = $pdo->lastInsertId();

    // 4. Return
    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $new_msg_id,
            'message' => $message_text,
            'sender_type' => $sender_type,
            'pretty_time' => date('g:i A'),
            'type' => 'sent',
            'read' => false,
            'date_separator' => null // Usually handled by frontend for new messages unless it's a new day
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>