<?php
session_start();
// Master Anti-Cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

require_once 'navigation.php';
require_once 'database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$is_admin = ($user_role === 'admin');

// Sidebar Initials Logic
function getInitials($name) {
    if (!$name) return '?';
    $p = explode(' ', $name); $i = '';
    foreach ($p as $x) if ($x !== '') $i .= strtoupper($x[0]);
    return substr($i, 0, 2);
}
$user_initials = getInitials($user_name);
$nav_links = getNavigationLinks($user_role, 'staff-help.php');

$database = new Database();
$pdo = $database->getConnection();

// Handle Thread Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_thread'])) {
    $thread_title = trim($_POST['thread_title'] ?? '');
    $initial_message = trim($_POST['initial_message'] ?? '');
    $selected_admins = $_POST['admins'] ?? [];
    if (!empty($thread_title) && !empty($initial_message) && !empty($selected_admins)) {
        try {
            $pdo->beginTransaction();
            $thread_uuid = bin2hex(random_bytes(16));
            $participants = array_merge([$user_id], array_map('intval', $selected_admins));
            $pdo->prepare("INSERT INTO internal_threads (thread_uuid, title, created_by, participants, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, ?, 'open', NOW(), NOW(), NOW())")->execute([$thread_uuid, $thread_title, $user_id, json_encode($participants)]);
            $new_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO internal_thread_messages (thread_id, sender_id, sender_role, message, read_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())")->execute([$new_id, $user_id, $user_role, $initial_message, json_encode([$user_id])]);
            $pdo->commit();
            header("Location: staff-help.php?thread_id=$new_id"); exit();
        } catch (Exception $e) { $pdo->rollBack(); }
    }
}

// Fetch Threads (Master Visibility)
$where = $is_admin ? "1=1" : "(JSON_CONTAINS(participants, ?, '$') OR created_by = ?)";
$stmt = $pdo->prepare("SELECT * FROM internal_threads WHERE $where ORDER BY last_message_at DESC");
$is_admin ? $stmt->execute() : $stmt->execute([json_encode($user_id), $user_id]);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Admins
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'admin' AND status = 'active'");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : null;
$selected_thread = null;
$thread_messages = [];
if ($selected_thread_id) {
    $stmt = $pdo->prepare($is_admin ? "SELECT * FROM internal_threads WHERE id = ?" : "SELECT * FROM internal_threads WHERE id = ? AND (JSON_CONTAINS(participants, ?, '$') OR created_by = ?)");
    $is_admin ? $stmt->execute([$selected_thread_id]) : $stmt->execute([$selected_thread_id, json_encode($user_id), $user_id]);
    $selected_thread = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($selected_thread) {
        $stmt = $pdo->prepare("SELECT m.*, u.name as sender_name FROM internal_thread_messages m LEFT JOIN users u ON m.sender_id = u.id WHERE m.thread_id = ? ORDER BY m.created_at ASC");
        $stmt->execute([$selected_thread_id]);
        $thread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Hub | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css?v=104">
    <link rel="stylesheet" href="css/sidebar.css?v=104">
    <style>
        /* ATOMIC CLINICAL ARMOR - RESISTANCE V104 */
        body { margin: 0; background: #f0f2f5 !important; overflow: hidden; font-family:'Poppins',sans-serif; }
        .main-layout { display: grid; grid-template-columns: 260px 1fr; height: 100vh; width: 100%; position:fixed; left:0; top:0; }
        .main-content { grid-column: 2; height: 100vh; overflow-y: auto; padding: 24px !important; box-sizing: border-box !important; position:relative; }
        
        .messaging-wrapper { display: flex !important; opacity: 1 !important; visibility: visible !important; gap: 20px; height: calc(100vh - 48px); width: 100% !important; margin: 0; }
        .msg-sidebar { width: 340px; background: white; border-radius: 20px; display: flex; flex-direction: column; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #ddd; }
        .msg-container { flex: 1; background: white; border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 4px 25px rgba(0,0,0,0.05); border: 1px solid #ddd; }

        .contact-list { flex: 1; overflow-y: auto; padding: 10px; }
        .contact-item { padding: 15px; border-radius: 15px; display: flex; align-items: center; gap: 15px; cursor: pointer; border-bottom: 1px solid #f9f9f9; transition: 0.2s; }
        .contact-item:hover { background: #f5f7fa; }
        .contact-item.active { background: #e6f4ea; border-left: 5px solid #2e8b57; }

        .chat-messages { flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: #fff; }
        .message-wrapper { display: flex; width: 100%; }
        .message-wrapper.sent { flex-direction: row-reverse; }
        .message-bubble { padding: 12px 18px; border-radius: 18px; max-width: 70%; line-height: 1.5; font-size:0.95rem; }
        .message-wrapper.sent .message-bubble { background: #2E8B57; color: white !important; }
        .message-wrapper.received .message-bubble { background: #f1f1f1; color: #1a1a1a !important; }

        .chat-input-area { padding: 15px 25px; border-top: 1px solid #eee; background: #fff; }
        .input-pill { background: #f8f9fa; border: 1px solid #ddd; border-radius: 30px; display: flex; align-items: center; padding: 5px 15px; }
        .chat-input { flex: 1; border: none; background: transparent; padding: 10px; outline: none; resize: none; font-family: inherit; }

        /* RE-SOLDIER SIDEBAR (Manual) */
        .sidebar { background: white; border-right: 1px solid #ddd; padding: 20px; display: flex; flex-direction: column; }
        .nav-links { list-style: none; padding: 0; margin: 20px 0; flex: 1; }
        .nav-links li { margin-bottom: 8px; }
        .nav-links a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; text-decoration: none; color: #444; font-weight: 500; transition: 0.2s; }
        .nav-links a:hover { background: #f5f7fa; color: #2e8b57; }
        .nav-links a.active { background: #2e8b57; color: white; }

        .modal-overlay { position: fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:9000; }
        .modal-overlay.active { display:flex; }
        .modal-body { background:white; padding:30px; border-radius:20px; width:450px; }
    </style>
</head>
<body>
    <div class="main-layout">
        <!-- ATOMIC SIDEBAR -->
        <div class="sidebar">
            <h2 style="color:#2e8b57; font-family:'Playfair Display',serif; margin-bottom:30px;">NutriDeq</h2>
            <ul class="nav-links">
                <?php foreach ($nav_links as $link): ?>
                    <?php if (isset($link['type']) && $link['type'] === 'header'): ?>
                        <li style="font-size:0.75rem; color:#999; text-transform:uppercase; margin-top:15px; padding-left:15px;"><?= $link['text'] ?></li>
                    <?php else: ?>
                        <li><a href="<?= $link['href'] ?>" class="<?= !empty($link['active'])?'active':'' ?>"><i class="<?= $link['icon'] ?>"></i> <span><?= $link['text'] ?></span></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <div style="border-top:1px solid #eee; padding-top:20px; display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; background:#e6f4ea; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2e8b57; font-weight:700;"><?= $user_initials ?></div>
                <div style="font-size:0.85rem;">
                    <div style="font-weight:700; color:#1a1a1a;"><?= htmlspecialchars($user_name) ?></div>
                    <div style="color:#999;"><?= ucfirst($user_role) ?></div>
                </div>
            </div>
            <a href="login-logout/logout.php" style="margin-top:15px; color:#ff6b6b; text-decoration:none; display:flex; align-items:center; gap:10px; font-weight:600; font-size:0.9rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <main class="main-content">
            <div class="messaging-wrapper">
                <div class="msg-sidebar">
                    <div style="padding:20px; border-bottom:1px solid #eee;">
                        <h3 style="margin:0 0 15px 0;">Help Center</h3>
                        <button onclick="openModal()" style="width:100%; background:#2e8b57; color:white; border:none; padding:10px; border-radius:10px; font-weight:600; cursor:pointer;"><i class="fas fa-plus"></i> New Inquiry</button>
                    </div>
                    <div class="contact-list">
                        <?php foreach ($threads as $thread): ?>
                            <div class="contact-item <?= ($selected_thread_id == $thread['id'])?'active':'' ?>" onclick="window.location.href='?thread_id=<?= $thread['id'] ?>'">
                                <div style="width:40px; height:40px; background:#f0f0f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2e8b57;">#</div>
                                <div>
                                    <div style="font-weight:700; color:#1a1a1a; font-size:0.9rem;"><?= htmlspecialchars($thread['title']) ?></div>
                                    <div style="font-size:0.75rem; color:#888;"><?= ucfirst($thread['status']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="msg-container">
                    <?php if ($selected_thread): ?>
                        <div style="padding:15px 25px; border-bottom:1px solid #eee;">
                            <h4 style="margin:0;"><?= htmlspecialchars($selected_thread['title']) ?></h4>
                        </div>
                        <div class="chat-messages" id="chatMessages">
                            <?php foreach ($thread_messages as $msg): 
                                $isMe = ($msg['sender_id'] == $user_id); ?>
                                <div class="message-wrapper <?= $isMe ? 'sent' : 'received' ?>" id="msg-<?= $msg['id'] ?>">
                                    <div class="message-bubble">
                                        <?php if(!$isMe): ?><div style="font-size:0.75rem; color:#2e8b57; font-weight:700; margin-bottom:4px;"><?= htmlspecialchars($msg['sender_name']) ?></div><?php endif; ?>
                                        <div style="color:inherit !important;"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                        <div style="font-size:0.65rem; opacity:0.6; text-align:right; margin-top:5px;"><?= date('g:i A', strtotime($msg['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chat-input-area">
                            <form id="messageForm">
                                <div class="input-pill">
                                    <textarea class="chat-input" id="messageInput" placeholder="Reply..." rows="1"></textarea>
                                    <button type="submit" style="background:#2e8b57; color:white; border:none; width:40px; height:40px; border-radius:50%; cursor:pointer;"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.5;">
                            <i class="fas fa-comments fa-3x" style="margin-bottom:15px; color:#2e8b57;"></i>
                            <h3>Inquiry Hub</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ATOMIC MODAL -->
            <div id="threadModal" class="modal-overlay">
                <div class="modal-body">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3 style="margin:0;">New Consultation</h3>
                        <button onclick="closeModal()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
                    </div>
                    <form method="POST">
                        <div style="margin-bottom:15px;"><label style="display:block; font-weight:600;">Subject</label><input type="text" name="thread_title" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;" required></div>
                        <div style="margin-bottom:15px;"><label style="display:block; font-weight:600;">Admins</label><div style="max-height:100px; overflow-y:auto; border:1px solid #f0f0f0; padding:5px;">
                            <?php foreach ($admins as $admin): ?><label style="display:block;"><input type="checkbox" name="admins[]" value="<?= $admin['id'] ?>"> <?= htmlspecialchars($admin['name']) ?></label><?php endforeach; ?>
                        </div></div>
                        <div style="margin-bottom:15px;"><label style="display:block; font-weight:600;">Message</label><textarea name="initial_message" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:10px;" rows="3" required></textarea></div>
                        <button type="submit" name="create_thread" style="width:100%; background:#2e8b57; color:white; border:none; padding:12px; border-radius:10px; font-weight:600;">Submit Inquiry</button>
                    </form>
                </div>
            </div>

            <script src="scripts/internal-chat-controller.js?v=104"></script>
            <script>
                const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';
                function openModal() { document.getElementById('threadModal').classList.add('active'); }
                function closeModal() { document.getElementById('threadModal').classList.remove('active'); }
                document.addEventListener('DOMContentLoaded', () => {
                    <?php if ($selected_thread_id): ?>
                    new ChatController(<?= $user_id ?>, '<?= $user_role ?>', <?= $selected_thread_id ?>);
                    const el = document.getElementById('chatMessages'); if(el) el.scrollTop = el.scrollHeight;
                    <?php endif; ?>
                });
            </script>
        </main>
    </div>
</body>
</html>
