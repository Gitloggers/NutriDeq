<?php
/**
 * Admin Internal Messaging Hub
 * Elite Standard for Administrative Control
 */
ob_start();
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

require_once 'navigation.php';
$nav_links_array = getNavigationLinks($user_role, 'admin-internal-messages.php');

function getInitials($name) {
    $p = explode(' ', $name);
    $i = '';
    foreach ($p as $x) if ($x !== '') $i .= strtoupper($x[0]);
    return substr($i, 0, 2);
}

require_once 'database.php';
$database = new Database();
$pdo = $database->getConnection();

$filter_status = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Handle Actions (Resolve, Re-open, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thread_id = $_POST['thread_id'] ?? null;
    
    if (isset($_POST['update_thread_status'])) {
        $new_status = $_POST['status'] ?? 'open';
        $stmt = $pdo->prepare("UPDATE internal_threads SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $thread_id]);
        header("Location: admin-internal-messages.php?thread_id=$thread_id&status=$filter_status");
        exit();
    }
    
    if (isset($_POST['delete_thread'])) {
        $pdo->prepare("DELETE FROM internal_thread_messages WHERE thread_id = ?")->execute([$thread_id]);
        $pdo->prepare("DELETE FROM internal_threads WHERE id = ?")->execute([$thread_id]);
        header("Location: admin-internal-messages.php");
        exit();
    }
}

// Fetch Threads (Master Visibility for Admins)
$where_conditions = ["1=1"];
$params = [];
if ($filter_status !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
}
if (!empty($search_term)) {
    $where_conditions[] = "title LIKE ?";
    $params[] = "%$search_term%";
}
$where_sql = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("SELECT * FROM internal_threads WHERE $where_sql ORDER BY last_message_at DESC");
$stmt->execute($params);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Internal Messages | NutriDeq Admin</title>
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
        <div class="main-content" style="padding: 0 !important;">
            <div class="messaging-wrapper <?= $selected_thread_id ? 'view-chat' : 'view-list' ?>">
                <div class="msg-sidebar">
                    <div class="msg-sidebar-header">
                        <h2>Internal Chat</h2>
                        <form method="GET">
                            <select name="status" onchange="this.form.submit()" class="status-select">
                                <option value="all" <?= $filter_status=='all'?'selected':'' ?>>All Chats</option>
                                <option value="open" <?= $filter_status=='open'?'selected':'' ?>>Open</option>
                                <option value="resolved" <?= $filter_status=='resolved'?'selected':'' ?>>Resolved</option>
                            </select>
                        </form>
                    </div>
                    <div class="contact-list">
                        <?php foreach ($threads as $thread): ?>
                            <div class="contact-item <?= ($selected_thread_id == $thread['id']) ? 'active' : '' ?>" 
                                 onclick="window.location.href='?thread_id=<?= $thread['id'] ?>&status=<?= $filter_status ?>'">
                                <div class="contact-avatar"><i class="fas fa-hashtag"></i></div>
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
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="header-name"><?= htmlspecialchars($selected_thread['title']) ?></div>
                            </div>
                            <div class="chat-actions">
                                <?php if ($selected_thread['status'] == 'open'): ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                    <input type="hidden" name="update_thread_status" value="1">
                                    <button type="submit" name="status" value="resolved" class="btn btn-primary" style="padding:6px 14px; font-size:0.85rem;">
                                        <i class="fas fa-check-circle"></i> Resolve
                                    </button>
                                </form>
                                <?php endif; ?>
                                <button class="btn btn-outline" id="manageBtn" style="padding:6px 12px; font-size:0.9rem;">Manage</button>
                                <div id="manageMenu" style="display:none; position:absolute; right:20px; top:70px; background:white; border:1px solid #ddd; box-shadow:0 10px 30px rgba(0,0,0,0.1); border-radius:12px; z-index:100; min-width:160px;">
                                    <form method="POST">
                                        <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                        <input type="hidden" name="update_thread_status" value="1">
                                        <button type="submit" name="status" value="open" style="width:100%; padding:10px; border:none; background:none; text-align:left; cursor:pointer;" class="hover-bg">Re-open Thread</button>
                                        <button type="submit" name="status" value="archived" style="width:100%; padding:10px; border:none; background:none; text-align:left; cursor:pointer;" class="hover-bg">Archive Case</button>
                                    </form>
                                    <button onclick="document.getElementById('deleteModal').style.display='flex'" style="width:100%; padding:10px; border:none; background:none; text-align:left; cursor:pointer; color:red;" class="hover-bg">Delete Thread</button>
                                </div>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <!-- Messages handled by controller -->
                        </div>

                        <div class="chat-input-area">
                            <?php if ($selected_thread['status'] == 'open'): ?>
                                <form id="messageForm">
                                    <div class="input-pill-container">
                                        <button type="button" class="icon-btn" id="attachBtn"><i class="fas fa-plus"></i></button>
                                        <textarea class="chat-input" id="messageInput" placeholder="Type an internal message..." rows="1"></textarea>
                                        <div class="input-actions">
                                            <button type="submit" class="icon-btn send-btn"><i class="fas fa-paper-plane"></i></button>
                                        </div>
                                    </div>
                                    <input type="file" id="fileInput" style="display:none;">
                                </form>
                            <?php else: ?>
                                <div style="text-align:center; padding:20px; color:var(--text-tertiary);">Thread is <?= $selected_thread['status'] ?>. Re-open to reply.</div>
                            <?php endif; ?>
                        </div>

                        <div id="deleteModal" class="modal-overlay" style="display:none;">
                            <div class="modal-container" style="width:300px; text-align:center;">
                                <h3>Delete Thread?</h3>
                                <p>This cannot be undone.</p>
                                <form method="POST">
                                    <input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>">
                                    <input type="hidden" name="delete_thread" value="1">
                                    <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                                        <button type="button" onclick="document.getElementById('deleteModal').style.display='none'" class="btn btn-outline">Cancel</button>
                                        <button type="submit" class="btn" style="background:red; color:white;">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php else: ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.6;">
                            <i class="fas fa-headset fa-4x" style="margin-bottom:20px;"></i>
                            <h3>Master Message Center</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';</script>
            <script src="scripts/internal-chat-controller.js?v=101"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    <?php if ($selected_thread_id): ?>
                    window._chat = new ChatController(<?= $admin_id ?>, 'admin', <?= $selected_thread_id ?>);
                    <?php endif; ?>

                    const manageBtn = document.getElementById('manageBtn');
                    const manageMenu = document.getElementById('manageMenu');
                    if (manageBtn && manageMenu) {
                        manageBtn.onclick = (e) => {
                            e.stopPropagation();
                            manageMenu.style.display = manageMenu.style.display === 'block' ? 'none' : 'block';
                        };
                        document.onclick = () => manageMenu.style.display = 'none';
                    }
                });
            </script>
            <style>
                .status-select { padding:4px 8px; border-radius:8px; border:1px solid #ddd; font-size:0.85rem; }
                .hover-bg:hover { background: #f5f7fa; }
                .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; }
                .modal-container { background:white; padding:30px; border-radius:15px; box-shadow:0 10px 40px rgba(0,0,0,0.1); }
            </style>
        </div>
    </div>
</body>
</html>
