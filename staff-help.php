<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

require_once 'navigation.php';
require_once 'database.php';

$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$is_admin = ($user_role === 'admin');
$nav_links_array = getNavigationLinks($user_role, 'staff-help.php');

$database = new Database();
$pdo = $database->getConnection();

function getInitials($name)
{
    $p = explode(' ', $name);
    $i = '';
    foreach ($p as $x) {
        if ($x !== '')
            $i .= strtoupper($x[0]);
    }
    return substr($i, 0, 2);
}

// Handle Thread Creation (POST)
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_thread'])) {
    $thread_title = trim($_POST['thread_title'] ?? '');
    $initial_message = trim($_POST['initial_message'] ?? '');
    $selected_admins = $_POST['admins'] ?? [];

    // If Admin creates thread, sender role is admin
    $sender_role = $is_admin ? 'admin' : 'staff';

    if (empty($thread_title) || empty($initial_message) || empty($selected_admins)) {
        $error = 'Please fill all required fields.';
    } else {
        try {
            $pdo->beginTransaction();
            $thread_uuid = bin2hex(random_bytes(16));
            $participants = array_merge([$staff_id], array_map('intval', $selected_admins));

            $stmt = $pdo->prepare(
                "INSERT INTO internal_threads (thread_uuid, title, created_by, participants, status, last_message_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'open', NOW(), NOW(), NOW())"
            );
            $stmt->execute([$thread_uuid, $thread_title, $staff_id, json_encode($participants)]);
            $new_thread_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO internal_thread_messages (thread_id, sender_id, sender_role, message, read_by, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$new_thread_id, $staff_id, $sender_role, $initial_message, json_encode([$staff_id])]);

            $pdo->commit();
            header("Location: staff-help.php?thread_id=" . $new_thread_id);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error creating thread: ' . $e->getMessage();
        }
    }
}

// Fetch Threads
$threads = [];
try {
    // Admins see all threads, Staff see only theirs
    $where_clause = $is_admin ? "" : "WHERE JSON_CONTAINS(t.participants, ?) OR t.created_by = ?";

    $sql = "
        SELECT t.*, 
               u.name as created_by_name,
               (
                 SELECT COUNT(*) 
                 FROM internal_thread_messages tm 
                 WHERE tm.thread_id = t.id 
                   AND (
                        tm.read_by IS NULL 
                        OR (
                            JSON_CONTAINS(tm.read_by, ?, '$') = 0 
                            AND JSON_CONTAINS(tm.read_by, JSON_QUOTE(?), '$') = 0
                        )
                   )
               ) as unread_count
        FROM internal_threads t
        LEFT JOIN users u ON t.created_by = u.id
        $where_clause
        ORDER BY t.last_message_at DESC, t.updated_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $staff_json = json_encode($staff_id);

    $params = [$staff_json, (string) $staff_id];
    if (!$is_admin) {
        $params[] = $staff_json;
        $params[] = $staff_id;
    }

    $stmt->execute($params);
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* handle error */
    error_log("Error fetching threads: " . $e->getMessage());
}

// Fetch Admins for Modal
$admins = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, online_status FROM users WHERE role = 'admin' AND status = 'active' ORDER BY name");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* handle error */
}

// Selected Thread
$selected_thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : null;
$selected_thread = null;

if ($selected_thread_id) {
    try {
        // Admins can view any thread
        $where_clause = $is_admin ? "WHERE t.id = ?" : "WHERE t.id = ? AND (JSON_CONTAINS(t.participants, ?) OR t.created_by = ?)";

        $sql = "
            SELECT t.*, 
                   u.name as created_by_name,
                   u.email as created_by_email
            FROM internal_threads t
            LEFT JOIN users u ON t.created_by = u.id
            $where_clause
        ";

        $stmt = $pdo->prepare($sql);
        $staff_json = json_encode($staff_id);

        $params = [$selected_thread_id];
        if (!$is_admin) {
            $params[] = $staff_json;
            $params[] = $staff_id;
        }

        $stmt->execute($params);
        $selected_thread = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_thread) {
            $stmt = $pdo->prepare("\n                SELECT m.*, \n                       u.name as sender_name,\n                       u.role as sender_role\n                FROM internal_thread_messages m\n                LEFT JOIN users u ON m.sender_id = u.id\n                WHERE m.thread_id = ?\n                ORDER BY m.created_at ASC\n            ");
            $stmt->execute([$selected_thread_id]);
            $thread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mark messages as read
            try {
                $update = $pdo->prepare("\n                    UPDATE internal_thread_messages \n                    SET read_by = JSON_ARRAY_APPEND(COALESCE(read_by, '[]'), '$', ?)\n                    WHERE thread_id = ? AND sender_id != ? \n                    AND (read_by IS NULL OR JSON_CONTAINS(COALESCE(read_by, '[]'), ?, '$') = 0)\n                ");
                $update->execute([$staff_id, $selected_thread_id, $staff_id, $staff_id]);
            } catch (Exception $e) {
                error_log("Error updating read status: " . $e->getMessage());
            }
        } else {
            $error = 'Thread not found or access denied.';
        }
    } catch (Exception $e) {
        $error = 'Error loading thread: ' . $e->getMessage();
    }
}

// Handle sending message in existing thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $selected_thread) {
    $message_text = trim($_POST['message'] ?? '');
    if (empty($message_text)) {
        $error = 'Message cannot be empty.';
    } else {
        try {
            $stmt = $pdo->prepare("\n                INSERT INTO internal_thread_messages (thread_id, sender_id, sender_role, message, read_by, created_at)\n                VALUES (?, ?, 'staff', ?, ?, NOW())\n            ");
            $stmt->execute([$selected_thread_id, $staff_id, $message_text, json_encode([$staff_id])]);
            $update = $pdo->prepare("UPDATE internal_threads SET last_message_at = NOW(), updated_at = NOW() WHERE id = ?");
            $update->execute([$selected_thread_id]);
            $_SESSION['success'] = 'Message sent!';
            header("Location: staff-help.php?thread_id=" . $selected_thread_id);
            exit();
        } catch (Exception $e) {
            $error = 'Error sending message: ' . $e->getMessage();
        }
    }
}

// Handle closing thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_thread']) && $selected_thread) {
    try {
        $update = $pdo->prepare("UPDATE internal_threads SET status = 'resolved', updated_at = NOW() WHERE id = ?");
        $update->execute([$selected_thread_id]);
        $_SESSION['success'] = 'Thread marked as resolved.';
        header("Location: staff-help.php?thread_id=" . $selected_thread_id);
        exit();
    } catch (Exception $e) {
        $error = 'Error closing thread: ' . $e->getMessage();
    }
}

// Session flash
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriDeq - Staff Help Center</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/modern-messages.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <link rel="stylesheet" href="css/logout-modal.css">
</head>
<style>
    /* Specific overrides for Help Center */
    .new-thread-btn {
        background: rgba(150, 150, 150, 0.2);
        color: black;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 8px 15px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        width: 100%;
        justify-content: center;
    }

    .new-thread-btn:hover {
        background: white;
        color: var(--primary-green);
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1001;
        backdrop-filter: blur(5px);
    }

    .modal-container {
        background: white;
        border-radius: 15px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideInUp 0.3s ease;
    }

    .modal-header {
        padding: 20px;
        background: var(--primary-green);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .modal-content {
        padding: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: inherit;
    }

    .admin-selection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
    }

    .admin-select-card {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }

    .admin-select-card:hover {
        border-color: var(--primary-green);
        background: var(--light-green);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-primary {
        background: var(--primary-green);
        color: white;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid #ddd;
    }
</style>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="messaging-wrapper">
                <!-- Sidebar: Threads List -->
                <div class="msg-sidebar">
                    <div class="msg-sidebar-header">
                        <h2>Help Center</h2>
                        <button class="new-thread-btn" onclick="openNewThreadModal()"
                            style="margin-top:12px; width:100%; justify-content:center;">
                            <i class="fas fa-plus-circle"></i> New Conversation
                        </button>
                    </div>

                    <div class="contact-list">
                        <?php if (empty($threads)): ?>
                            <div style="padding:20px; text-align:center; color:#6B7280;">No conversations yet.</div>
                        <?php else: ?>
                            <?php foreach ($threads as $thread): ?>
                                <?php
                                $isActive = ($selected_thread_id == $thread['id']);
                                $statusColor = $thread['status'] == 'open' ? 'var(--primary-green)' : '#9CA3AF';
                                ?>
                                <div class="contact-item <?= $isActive ? 'active' : '' ?>" data-thread-id="<?= $thread['id'] ?>"
                                    data-thread-title="<?= htmlspecialchars($thread['title'], ENT_QUOTES) ?>"
                                    onclick="window.location.href='?thread_id=<?= $thread['id'] ?>'">
                                    <div class="contact-avatar" style="background: <?= $statusColor ?>; color: white;">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div class="contact-info">
                                        <div class="contact-name"><?= htmlspecialchars($thread['title']) ?></div>
                                        <div class="contact-preview">
                                            <span><?= ucfirst($thread['status']) ?></span>
                                            <?php if ($thread['unread_count'] > 0): ?>
                                                <span class="badge"
                                                    style="background:red; color:white; padding:2px 6px; border-radius:10px; font-size:0.7rem; margin-left:5px;"><?= $thread['unread_count'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="msg-container" style="<?= $selected_thread ? 'display:flex' : 'display:none' ?>">
                    <?php if ($selected_thread): ?>
                        <div class="chat-header">
                            <div class="chat-header-user">
                                <div class="contact-avatar"
                                    style="background: var(--accent-light); color: var(--primary-green);">
                                    <i class="fas fa-hashtag"></i>
                                </div>
                                <div>
                                    <div class="header-name"><?= htmlspecialchars($selected_thread['title']) ?></div>
                                    <div class="header-status">
                                        started by <?= htmlspecialchars($selected_thread['created_by_name']) ?> •
                                        <?= date('M j', strtotime($selected_thread['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="chat-actions" style="display:flex; gap:10px; align-items:center;">
                                <?php if ($selected_thread['status'] == 'open'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                        <button type="submit" name="close_thread" class="btn btn-outline"
                                            style="padding:6px 12px; font-size:0.85rem;"
                                            onclick="return confirm('Mark this conversation as resolved?')">
                                            <i class="fas fa-check-circle"></i> Resolve
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button class="icon-btn"
                                    onclick="if(window._internalChat){window._internalChat.fetchMessages();}"
                                    title="Refresh">
                                    <i class="fas fa-sync-alt" style="font-size:1rem;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <?php if (!empty($thread_messages)): ?>
                                <?php foreach ($thread_messages as $index => $msg):
                                    $isSent = $msg['sender_id'] == $staff_id;
                                    $initials = getInitials($msg['sender_name']);
                                    ?>
                                    <div class="message-wrapper <?= $isSent ? 'sent' : 'received' ?>" id="msg-<?= $msg['id'] ?>"
                                        style="animation-delay: <?= $index * 0.05 ?>s">
                                        <?php if (!$isSent): ?>
                                            <div class="contact-avatar"
                                                style="width:36px; height:36px; font-size:0.8rem; margin-right:8px;"
                                                title="<?= htmlspecialchars($msg['sender_name']) ?>"><?= $initials ?></div>
                                        <?php endif; ?>
                                        <div class="message-bubble">
                                            <?php if (!$isSent): ?>
                                                <div
                                                    style="font-size:0.7rem; color:var(--primary-green); margin-bottom:2px; font-weight:600;">
                                                    <?= htmlspecialchars($msg['sender_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="message-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                            <div style="text-align:right; font-size:0.65rem; opacity:0.7; margin-top:4px;">
                                                <?= date('g:i A', strtotime($msg['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-thread-selected">
                                    <i class="fas fa-comments"
                                        style="font-size: 3rem; color: rgba(0,0,0,0.1); margin-bottom: 15px;"></i>
                                    <h3>No messages yet</h3>
                                    <p style="color: var(--text-light);">Start the conversation by sending a message</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($selected_thread['status'] == 'open'): ?>
                            <div class="chat-input-area">
                                <form method="POST" id="messageForm">
                                    <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                    <div class="input-pill-container">
                                        <textarea class="chat-input" name="message" id="messageInput"
                                            placeholder="Type your message here..." rows="1" oninput="autoResize(this)"
                                            required></textarea>

                                        <div class="input-actions">
                                            <button type="submit" name="send_message" class="icon-btn send-btn"
                                                id="sendMessageBtn"><i class="fas fa-paper-plane"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="chat-input-area" style="text-align: center; padding: 20px;">
                                <p style="color: var(--text-light);"><i class="fas fa-lock"></i> This conversation has been
                                    resolved.
                                    You can start a new one if needed.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div
                            style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#9CA3AF;">
                            <i class="fas fa-headset fa-4x" style="margin-bottom:20px; opacity:0.3;"></i>
                            <h3>Welcome to Help Center</h3>
                            <p>Select a conversation or start a new one.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- New Thread Modal -->
            <div id="newThreadModal" class="modal-overlay">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Start New Conversation</h3>
                        <button class="modal-close" onclick="closeNewThreadModal()"><i
                                class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-content">
                        <form method="POST">
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" name="thread_title" class="form-control" required
                                    placeholder="Brief title of your issue">
                            </div>
                            <div class="form-group">
                                <label>Select Admins</label>
                                <div class="admin-selection-grid">
                                    <?php foreach ($admins as $admin): ?>
                                        <label class="admin-select-card">
                                            <input type="checkbox" name="admins[]" value="<?= $admin['id'] ?>">
                                            <span><?= htmlspecialchars($admin['name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="initial_message" class="form-control" rows="4" required
                                    placeholder="Describe your issue..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-outline"
                                    onclick="closeNewThreadModal()">Cancel</button>
                                <button type="submit" name="create_thread" class="btn btn-primary">Create
                                    Thread</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script src="scripts/dashboard.js"></script>
            <script src="scripts/internal-chat-controller.js"></script>
            <script>
                function openNewThreadModal() {
                    document.getElementById('newThreadModal').style.display = 'flex';
                }
                function closeNewThreadModal() {
                    document.getElementById('newThreadModal').style.display = 'none';
                }
                function autoResize(textarea) {
                    textarea.style.height = 'auto';
                    const minHeight = 50;
                    const maxHeight = 150;
                    const newHeight = Math.max(minHeight, Math.min(textarea.scrollHeight, maxHeight));
                    textarea.style.height = newHeight + 'px';
                    textarea.style.overflowY = newHeight >= maxHeight ? 'auto' : 'hidden';
                }
                document.addEventListener('DOMContentLoaded', function () {
                    const initialThreadId = <?= $selected_thread_id ? $selected_thread_id : 'null' ?>;
                    const controller = new ChatController(<?= $staff_id ?>, 'staff', initialThreadId);
                    window._internalChat = controller;

                    const contacts = document.querySelectorAll('.contact-item');
                    contacts.forEach(contact => {
                        contact.addEventListener('click', () => {
                            const tid = contact.getAttribute('data-thread-id');
                            const ttitle = contact.getAttribute('data-thread-title') || (contact.querySelector('.contact-name') ? contact.querySelector('.contact-name').textContent : 'Conversation');

                            contacts.forEach(c => c.classList.remove('active'));
                            contact.classList.add('active');

                            const container = document.querySelector('.msg-container');
                            const formExists = document.getElementById('messageForm');
                            if (!formExists && container) {
                                container.style.display = 'flex';
                                container.innerHTML = `
                            <div class="chat-header">
                                <div style="flex: 1;">
                                    <h3>${ttitle}</h3>
                                </div>
                                <div class="chat-actions">
                                    <button class="btn btn-outline" onclick="if(window._internalChat){window._internalChat.fetchMessages();}"><i class="fas fa-sync-alt"></i></button>
                                </div>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <div style="text-align:center; margin-top:50px; color:#9CA3AF;">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Loading conversation...
                                </div>
                            </div>
                            <div class="chat-input-area">
                                <form method="POST" id="messageForm">
                                    <div class="input-wrapper">
                                        <textarea class="chat-input" id="messageInput" placeholder="Type your message here..." rows="1" oninput="autoResize(this)" required></textarea>
                                        <button type="submit" class="send-btn" id="sendMessageBtn"><i class="fas fa-paper-plane"></i></button>
                                    </div>
                                </form>
                            </div>
                        `;
                            }

                            if (tid) controller.changeThread(tid, ttitle);
                        });
                    });

                    const messageInput = document.getElementById('messageInput');
                    if (messageInput && <?= $selected_thread && $selected_thread['status'] == 'open' ? 'true' : 'false' ?>) {
                        setTimeout(() => { messageInput.focus(); }, 100);
                    }
                });
                    </div >
        </div >
    </div >
</body >

</html >
