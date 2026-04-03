<?php
ob_start();
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

require_once 'navigation.php';
$nav_links_array = getNavigationLinks($user_role, 'admin-internal-messages.php');

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
$admin_initials = getInitials($admin_name);

require_once 'database.php';
$database = new Database();
$pdo = $database->getConnection();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$error = '';
$success = '';
$threads = [];
$selected_thread = null;
$thread_messages = [];
$staff_members = [];
$filter_status = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Check if internal_threads table is empty and seed with sample data
try {
    $check_threads = $pdo->query("SELECT COUNT(*) as count FROM internal_threads");
    $thread_count = $check_threads->fetch(PDO::FETCH_ASSOC)['count'];

    if ($thread_count == 0) {
        $sample_threads = [
            ['title' => 'Client Meal Plan Query', 'created_by' => 2, 'participants' => [1, 2], 'status' => 'open'],
            ['title' => 'System Performance Issue', 'created_by' => 2, 'participants' => [1, 2], 'status' => 'resolved'],
            ['title' => 'Weekly Report Review', 'created_by' => 2, 'participants' => [1, 2], 'status' => 'open']
        ];

        foreach ($sample_threads as $thread) {
            $uuid = bin2hex(random_bytes(18));
            $stmt = $pdo->prepare("INSERT INTO internal_threads (thread_uuid, title, created_by, participants, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())");
            $stmt->execute([$uuid, $thread['title'], $thread['created_by'], json_encode($thread['participants']), $thread['status']]);
            $thread_id = $pdo->lastInsertId();

            $sample_messages = [
                ['sender_id' => $thread['created_by'], 'message' => 'Hello, I have a question about this topic.', 'sender_role' => 'staff'],
                ['sender_id' => 1, 'message' => 'Sure, how can I help you?', 'sender_role' => 'admin'],
                ['sender_id' => $thread['created_by'], 'message' => 'Thank you for your response!', 'sender_role' => 'staff']
            ];

            foreach ($sample_messages as $msg) {
                $stmt = $pdo->prepare("INSERT INTO internal_thread_messages (thread_id, sender_id, sender_role, message, read_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$thread_id, $msg['sender_id'], $msg['sender_role'], $msg['message'], json_encode([$msg['sender_id']])]);
            }
        }
    }
} catch (Exception $e) {
    error_log("Error seeding threads: " . $e->getMessage());
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, online_status, last_active FROM users WHERE role = 'staff' AND status = 'active' ORDER BY name");
    $stmt->execute();
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error loading staff: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_thread_status'])) {
        $thread_id = $_POST['thread_id'] ?? null;
        $new_status = $_POST['status'] ?? 'open';

        if ($thread_id && in_array($new_status, ['open', 'resolved', 'archived'])) {
            try {
                $admin_json = json_encode([(int) $admin_id]);
                $check = $pdo->prepare("SELECT 1 FROM internal_threads WHERE id = ? AND (JSON_CONTAINS(participants, ?, '$') OR created_by = ?)");
                $check->execute([$thread_id, $admin_json, $admin_id]);

                if ($check->rowCount() > 0) {
                    $update = $pdo->prepare("UPDATE internal_threads SET status = ?, updated_at = NOW() WHERE id = ?");
                    $update->execute([$new_status, $thread_id]);
                    $_SESSION['success'] = "Thread status updated to " . ucfirst($new_status) . ".";
                    $redirect_url = "admin-internal-messages.php?thread_id=" . $thread_id;
                    if ($filter_status !== 'all')
                        $redirect_url .= "&status=" . urlencode($filter_status);
                    if (!empty($search_term))
                        $redirect_url .= "&search=" . urlencode($search_term);
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    $_SESSION['error'] = "You don't have permission to update this thread.";
                    header("Location: admin-internal-messages.php");
                    exit();
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error updating thread status: ' . $e->getMessage();
                header("Location: admin-internal-messages.php");
                exit();
            }
        }
    }

    if (isset($_POST['delete_thread'])) {
        $thread_id = $_POST['thread_id'] ?? null;
        if ($thread_id) {
            try {
                $check = $pdo->prepare("SELECT 1 FROM internal_threads WHERE id = ? AND (JSON_CONTAINS(participants, ?, '$') OR created_by = ?)");
                $admin_json = json_encode($admin_id);
                $check->execute([$thread_id, $admin_json, $admin_id]);

                if ($check->rowCount() > 0) {
                    $deleteMessages = $pdo->prepare("DELETE FROM internal_thread_messages WHERE thread_id = ?");
                    $deleteMessages->execute([$thread_id]);
                    $deleteThread = $pdo->prepare("DELETE FROM internal_threads WHERE id = ?");
                    $deleteThread->execute([$thread_id]);
                    $_SESSION['success'] = "Thread deleted successfully.";
                    header("Location: admin-internal-messages.php");
                    exit();
                } else {
                    $_SESSION['error'] = "You don't have permission to delete this thread.";
                    header("Location: admin-internal-messages.php");
                    exit();
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error deleting thread: ' . $e->getMessage();
                header("Location: admin-internal-messages.php");
                exit();
            }
        }
    }

    if (isset($_POST['send_message'])) {
        $thread_id = $_POST['thread_id'] ?? null;
        $message_text = trim($_POST['message'] ?? '');

        if (empty($message_text)) {
            $_SESSION['error'] = 'Message cannot be empty.';
            if ($thread_id)
                header("Location: admin-internal-messages.php?thread_id=" . $thread_id);
            else
                header("Location: admin-internal-messages.php");
            exit();
        } elseif ($thread_id) {
            try {
                $check = $pdo->prepare("SELECT 1 FROM internal_threads WHERE id = ? AND (JSON_CONTAINS(participants, ?, '$') OR created_by = ?) AND status = 'open'");
                $admin_json = json_encode($admin_id);
                $check->execute([$thread_id, $admin_json, $admin_id]);

                if ($check->rowCount() > 0) {
                    $stmt = $pdo->prepare("INSERT INTO internal_thread_messages (thread_id, sender_id, sender_role, message, read_by, created_at) VALUES (?, ?, 'admin', ?, ?, NOW())");
                    $stmt->execute([$thread_id, $admin_id, $message_text, json_encode([$admin_id])]);
                    $update = $pdo->prepare("UPDATE internal_threads SET last_message_at = NOW(), updated_at = NOW() WHERE id = ?");
                    $update->execute([$thread_id]);
                    $_SESSION['success'] = 'Message sent!';
                    $redirectUrl = "admin-internal-messages.php?thread_id=" . $thread_id . "&status=" . $filter_status . "&search=" . urlencode($search_term);
                    header("Location: " . $redirectUrl);
                    exit();
                } else {
                    $_SESSION['error'] = 'Thread not found, access denied, or thread is not open.';
                    header("Location: admin-internal-messages.php");
                    exit();
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error sending message: ' . $e->getMessage();
                header("Location: admin-internal-messages.php");
                exit();
            }
        }
    }
}

$where_conditions = ["(JSON_CONTAINS(t.participants, ?, '$') OR t.created_by = ?)"];
$params = [json_encode($admin_id), $admin_id];

if ($filter_status !== 'all') {
    $where_conditions[] = "t.status = ?";
    $params[] = $filter_status;
}

if (!empty($search_term)) {
    $where_conditions[] = "(t.title LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.name as created_by_name, u.role as created_by_role,
               (SELECT COUNT(*) FROM internal_thread_messages tm WHERE tm.thread_id = t.id AND tm.sender_id != ? AND (tm.read_by IS NULL OR tm.read_by = '[]' OR (JSON_CONTAINS(COALESCE(tm.read_by, '[]'), ?, '$') = 0 AND JSON_CONTAINS(COALESCE(tm.read_by, '[]'), JSON_QUOTE(?), '$') = 0))) as unread_count
        FROM internal_threads t
        LEFT JOIN users u ON t.created_by = u.id
        WHERE $where_clause
        ORDER BY COALESCE(t.last_message_at, t.created_at) DESC
        LIMIT 50
    ");
    $stmt_params = [$admin_id, json_encode($admin_id), (string) $admin_id];
    $stmt_params = array_merge($stmt_params, $params);
    $stmt->execute($stmt_params);
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Thread query error: " . $e->getMessage());
}

$selected_thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : null;
if ($selected_thread_id) {
    try {
        $stmt = $pdo->prepare("SELECT t.*, u.name as created_by_name, u.email as created_by_email, u.role as created_by_role FROM internal_threads t LEFT JOIN users u ON t.created_by = u.id WHERE t.id = ? AND (JSON_CONTAINS(t.participants, ?, '$') OR t.created_by = ?)");
        $admin_json = json_encode($admin_id);
        $stmt->execute([$selected_thread_id, $admin_json, $admin_id]);
        $selected_thread = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_thread) {
            $stmt = $pdo->prepare("SELECT m.*, u.name as sender_name, u.role as sender_role FROM internal_thread_messages m LEFT JOIN users u ON m.sender_id = u.id WHERE m.thread_id = ? ORDER BY m.created_at ASC");
            $stmt->execute([$selected_thread_id]);
            $thread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            try {
                $stmt = $pdo->prepare("SELECT id, read_by FROM internal_thread_messages WHERE thread_id = ? AND sender_id != ?");
                $stmt->execute([$selected_thread_id, $admin_id]);
                $messages_to_update = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($messages_to_update as $msg) {
                    $read_by = json_decode($msg['read_by'] ?? '[]', true);
                    if (!is_array($read_by))
                        $read_by = [];
                    if (!in_array($admin_id, $read_by)) {
                        $read_by[] = $admin_id;
                        $update = $pdo->prepare("UPDATE internal_thread_messages SET read_by = ? WHERE id = ?");
                        $update->execute([json_encode($read_by), $msg['id']]);
                    }
                }
                foreach ($threads as $idx => $thr) {
                    if ((int) $thr['id'] === (int) $selected_thread_id) {
                        $threads[$idx]['unread_count'] = 0;
                        break;
                    }
                }
            } catch (Exception $e) {
            }
        }
    } catch (Exception $e) {
        $error = 'Error loading thread: ' . $e->getMessage();
    }
}

$total_unread = 0;
foreach ($threads as $thread) {
    $total_unread += (int) $thread['unread_count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Internal Messages | NutriDeq Admin</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Base Styles -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/modern-messages.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <script src="scripts/dashboard.js" defer></script>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content" style="padding: 0 !important; overflow: hidden;">

            <!-- Main Wrapper -->
            <div class="messaging-wrapper <?= $selected_thread_id ? 'view-chat' : 'view-list' ?>" id="messagingWrapper">
                <!-- Sidebar: Threads list -->
                <div class="msg-sidebar">
                    <div class="msg-sidebar-header" style="padding-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center;">
                                <button class="icon-btn" onclick="window.location.href='dashboard.php'"
                                    style="margin-right: 10px; color: var(--primary);">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                                <h2>Internal Chat</h2>
                            </div>
                            <form method="GET" style="margin:0;">
                                <select name="status" onchange="this.form.submit()"
                                    style="padding:4px 8px; border-radius:8px; border:1px solid #E5E7EB; color:#6B7280; font-size:0.85rem;">
                                    <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Chats</option>
                                    <option value="open" <?= $filter_status == 'open' ? 'selected' : '' ?>>Open</option>
                                    <option value="resolved" <?= $filter_status == 'resolved' ? 'selected' : '' ?>>Resolved
                                    </option>
                                </select>
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search_term) ?>">
                            </form>
                        </div>
                        <div class="msg-search" style="margin-top:12px;">
                            <form method="GET">
                                <input type="text" name="search" placeholder="Search threads..."
                                    value="<?= htmlspecialchars($search_term) ?>">
                                <i class="fas fa-search msg-search-icon"></i>
                                <input type="hidden" name="status" value="<?= $filter_status ?>">
                            </form>
                        </div>
                    </div>

                    <div class="contact-list">
                        <?php if (count($threads) > 0): ?>
                            <?php foreach ($threads as $thread):
                                $isActive = $selected_thread_id == $thread['id'];
                                $isUnread = $thread['unread_count'] > 0;
                                ?>
                                <div class="contact-item <?= $isActive ? 'active' : '' ?> <?= $isUnread ? 'has-unread' : '' ?>"
                                    onclick="window.location.href='?thread_id=<?= $thread['id'] ?>&status=<?= $filter_status ?>&search=<?= urlencode($search_term) ?>'">
                                    <div class="contact-avatar" style="background:#EEF2FF; color:#4F46E5;">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                    <div class="contact-info">
                                        <div class="contact-name"><?= htmlspecialchars($thread['title']) ?></div>
                                        <div class="contact-preview">
                                            <span><?= htmlspecialchars($thread['created_by_name']) ?> •
                                                <?= ucfirst($thread['status']) ?></span>
                                            <?php if ($isUnread): ?>
                                                <span
                                                    style="display:inline-block; width:8px; height:8px; background:red; border-radius:50%; margin-left:6px;"></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:40px 20px; text-align:center; color:var(--text-tertiary);">
                                <p>No conversations found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Container -->
                <div class="msg-container">
                    <?php if ($selected_thread): ?>
                        <div class="chat-header">
                            <div style="display: flex; align-items: center;">
                                <button class="icon-btn" id="backToInbox"
                                    style="display: none; margin-right: 10px; color: var(--primary);"
                                    onclick="window.location.href='admin-internal-messages.php'">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                            </div>
                            <div class="chat-header-user">
                                <div class="contact-avatar"
                                    style="background: var(--accent-light); color: var(--primary-green);">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <div class="header-name"><?= htmlspecialchars($selected_thread['title']) ?></div>
                                    <div class="header-status">
                                        Task ID: #<?= $selected_thread['id'] ?> • Status:
                                        <?= ucfirst($selected_thread['status']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="chat-actions">
                                <!-- Admin Actions -->
                                <div class="status-dropdown" style="position:relative; display:inline-block;">
                                    <button id="statusBtn" class="btn btn-outline"
                                        style="padding:6px 12px; font-size:0.9rem;">
                                        Actions <i class="fas fa-chevron-down"
                                            style="margin-left:4px; font-size:0.7rem;"></i>
                                    </button>
                                    <div id="statusMenu"
                                        style="display:none; position:absolute; right:0; top:40px; background:white; border:1px solid #E5E7EB; box-shadow:0 4px 12px rgba(0,0,0,0.1); border-radius:8px; min-width:160px; z-index:100;">
                                        <form method="POST">
                                            <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                            <input type="hidden" name="update_thread_status" value="1">
                                            <button type="submit" name="status" value="open"
                                                style="width:100%; border:none; background:none; padding:10px 16px; text-align:left; cursor:pointer;"
                                                class="hover-bg">Mark Open</button>
                                            <button type="submit" name="status" value="resolved"
                                                style="width:100%; border:none; background:none; padding:10px 16px; text-align:left; cursor:pointer;"
                                                class="hover-bg">Mark resolved</button>
                                            <button type="submit" name="status" value="archived"
                                                style="width:100%; border:none; background:none; padding:10px 16px; text-align:left; cursor:pointer;"
                                                class="hover-bg">Archive</button>
                                        </form>
                                        <div style="border-top:1px solid #E5E7EB;"></div>
                                        <button onclick="document.getElementById('deleteModal').style.display='flex'"
                                            style="width:100%; border:none; background:none; padding:10px 16px; text-align:left; cursor:pointer; color:#DC2626;"
                                            class="hover-bg">Delete Thread</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <?php foreach ($thread_messages as $msg):
                                $isSent = $msg['sender_id'] == $admin_id;
                                $typeClass = $isSent ? 'sent' : 'received';
                                $initials = substr(strtoupper($msg['sender_name']), 0, 2);
                                ?>
                                <div class="message-wrapper <?= $typeClass ?>" id="msg-<?= $msg['id'] ?>">
                                    <?php if (!$isSent): ?>
                                        <div class="contact-avatar"
                                            style="width:36px; height:36px; font-size:0.8rem; margin-right:8px;"
                                            title="<?= htmlspecialchars($msg['sender_name']) ?>"><?= $initials ?></div>
                                    <?php endif; ?>
                                    <div class="message-bubble">
                                        <?php if (!$isSent): ?>
                                            <div
                                                style="font-size:0.7rem; color:var(--primary-green); margin-bottom:2px; font-weight:600;">
                                                <?= htmlspecialchars($msg['sender_name']) ?> (<?= ucfirst($msg['sender_role']) ?>)
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                        <div style="text-align:right; font-size:0.65rem; opacity:0.7; margin-top:4px;">
                                            <?= date('g:i A', strtotime($msg['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="chat-input-area">
                            <?php if ($selected_thread['status'] == 'open'): ?>
                                <form method="POST" id="messageForm">
                                    <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                    <div class="input-pill-container">
                                        <textarea class="chat-input" name="message" placeholder="Type an internal message..."
                                            rows="1"
                                            oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>
                                        <div class="input-actions">
                                            <button type="submit" name="send_message" class="icon-btn send-btn">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div style="text-align:center; color:var(--text-tertiary); font-style:italic;">
                                    This thread is <?= $selected_thread['status'] ?>. Re-open to reply.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Delete Modal -->
                        <div id="deleteModal"
                            style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
                            <div
                                style="background:white; padding:24px; border-radius:12px; width:300px; text-align:center;">
                                <h3>Delete Thread?</h3>
                                <p style="color:#6B7280; margin-bottom:20px;">This cannot be undone.</p>
                                <form method="POST">
                                    <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                    <input type="hidden" name="delete_thread" value="1">
                                    <div style="display:flex; gap:12px; justify-content:center;">
                                        <button type="button"
                                            onclick="document.getElementById('deleteModal').style.display='none'"
                                            style="padding:8px 16px; border-radius:8px; border:1px solid #E5E7EB; background:white; cursor:pointer;">Cancel</button>
                                        <button type="submit"
                                            style="padding:8px 16px; border-radius:8px; border:none; background:#DC2626; color:white; cursor:pointer;">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php else: ?>
                        <div
                            style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--text-tertiary); opacity:0.6;">
                            <img src="https://cdn-icons-png.flaticon.com/512/3220/3220315.png" alt="Internal"
                                style="width:100px; margin-bottom:20px; opacity:0.5; filter:grayscale(100%);">
                            <h3>Internal Communications</h3>
                            <p>Select a thread to view details.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Init Controller -->
            <script src="scripts/internal-chat-controller.js"></script>
            <script>
                // Dropdown Logic
                const btn = document.getElementById('statusBtn');
                const menu = document.getElementById('statusMenu');
                if (btn && menu) {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                    });
                    document.addEventListener('click', () => {
                        menu.style.display = 'none';
                    });
                    menu.addEventListener('click', (e) => e.stopPropagation());
                }

                // Scroll to bottom
                const el = document.getElementById('chatMessages');
                if (el) el.scrollTop = el.scrollHeight;

                // Init JS Controller for Ajax updates
                <?php if ($selected_thread_id): ?>
                    if (typeof ChatController !== 'undefined') {
                        const chat = new ChatController(<?= $admin_id ?>, 'admin', <?= $selected_thread_id ?>);
                    }
                <?php endif; ?>
            </script>
            <style>
                .hover-bg:hover {
                    background-color: #F9FAFB !important;
                }
            </style>
        </div>
    </div>
</body>

</html>
