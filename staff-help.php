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
    $where_clause = $is_admin ? "" : "WHERE JSON_CONTAINS(t.participants, ?) OR t.created_by = ?";
    $sql = "
        SELECT t.*, 
               u.name as created_by_name,
               (SELECT COUNT(*) FROM internal_thread_messages tm WHERE tm.thread_id = t.id AND (tm.read_by IS NULL OR JSON_CONTAINS(tm.read_by, ?, '$') = 0)) as unread_count
        FROM internal_threads t
        LEFT JOIN users u ON t.created_by = u.id
        $where_clause
        ORDER BY t.last_message_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $staff_json = json_encode($staff_id);
    $params = $is_admin ? [$staff_json] : [$staff_json, $staff_id, $staff_json];
    $stmt->execute($params);
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch Admins for Modal
$admins = [];
try {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'admin' AND status = 'active' ORDER BY name");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$selected_thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : null;
$selected_thread = null;
if ($selected_thread_id) {
    $stmt = $pdo->prepare("SELECT * FROM internal_threads WHERE id = ?");
    $stmt->execute([$selected_thread_id]);
    $selected_thread = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Hub | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css?v=101">
    <link rel="stylesheet" href="css/sidebar.css?v=101">
    <link rel="stylesheet" href="css/modern-messages.css?v=101">
    <link rel="stylesheet" href="css/responsive.css?v=101">
    <link rel="stylesheet" href="css/mobile-style.css?v=101">
    <link rel="stylesheet" href="css/logout-modal.css?v=101">
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="messaging-wrapper <?= $selected_thread_id ? 'view-chat' : 'view-list' ?>">
                <div class="msg-sidebar">
                    <div class="msg-sidebar-header">
                        <h2>Help Center</h2>
                        <button class="new-thread-btn" onclick="document.getElementById('newThreadModal').style.display='flex';">
                            <i class="fas fa-plus"></i> New Conversation
                        </button>
                    </div>
                    <div class="contact-list">
                        <?php foreach ($threads as $thread): ?>
                            <div class="contact-item <?= ($selected_thread_id == $thread['id']) ? 'active' : '' ?>" 
                                 onclick="window.location.href='?thread_id=<?= $thread['id'] ?>'">
                                <div class="contact-avatar"><i class="fas fa-comments"></i></div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($thread['title']) ?></div>
                                    <div class="contact-preview"><?= ucfirst($thread['status']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="msg-container">
                    <?php if ($selected_thread): ?>
                        <div class="chat-header">
                            <div class="chat-header-user">
                                <div class="contact-avatar" style="background:var(--accent-light); color:var(--primary-green);">
                                    <i class="fas fa-hashtag"></i>
                                </div>
                                <div class="header-name"><?= htmlspecialchars($selected_thread['title']) ?></div>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <div style="text-align:center; margin-top:50px; color:var(--text-tertiary);">
                                <i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...
                            </div>
                        </div>

                        <div class="chat-input-area">
                            <form id="messageForm">
                                <input type="file" id="fileInput" name="attachment" style="display: none;">
                                <div class="input-pill-container">
                                    <button type="button" class="icon-btn" id="attachBtn"><i class="fas fa-plus"></i></button>
                                    <textarea class="chat-input" id="messageInput" placeholder="Type your message..." rows="1"></textarea>
                                    <div class="input-actions">
                                        <button type="submit" class="icon-btn send-btn"><i class="fas fa-paper-plane"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.6;">
                            <i class="fas fa-headset fa-4x" style="margin-bottom:20px;"></i>
                            <h3>Select a conversation</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal -->
            <div id="newThreadModal" class="modal-overlay" style="display:none;">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>New Conversation</h3>
                        <button class="modal-close" onclick="closeNewThreadModal()">&times;</button>
                    </div>
                    <div class="modal-content">
                        <form method="POST">
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" name="thread_title" class="form-control" required>
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
                                <textarea name="initial_message" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="create_thread" class="btn btn-primary">Start</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';</script>
            <script src="scripts/internal-chat-controller.js?v=101"></script>
            <script>
                function openNewThreadModal() { 
                    const m = document.getElementById('newThreadModal');
                    if (m) { m.style.display = 'flex'; m.style.opacity = '1'; }
                }
                function closeNewThreadModal() { 
                    const m = document.getElementById('newThreadModal');
                    if (m) m.style.display = 'none'; 
                }
                document.addEventListener('DOMContentLoaded', () => {
                    <?php if ($selected_thread_id): ?>
                    window._chat = new ChatController(<?= $staff_id ?>, 'staff', <?= $selected_thread_id ?>);
                    <?php endif; ?>
                });
            </script>
        </div>
    </div>
</body>
</html>
