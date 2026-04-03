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

// Fetch Threads
$where = $is_admin ? "1=1" : "(JSON_CONTAINS(participants, ?, '$') OR created_by = ?)";
$stmt = $pdo->prepare("SELECT * FROM internal_threads WHERE $where ORDER BY last_message_at DESC");
$is_admin ? $stmt->execute() : $stmt->execute([json_encode($user_id), $user_id]);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Support Hub | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css?v=109">
    <link rel="stylesheet" href="css/sidebar.css?v=109">
    <link rel="stylesheet" href="css/logout-modal.css?v=109">
    <style>
        /* INTERACTION FORTRESS - V109 */
        body { margin: 0; background: #f0f2f5 !important; overflow: hidden; font-family:'Poppins',sans-serif; }
        
        .main-layout { display: grid; grid-template-columns: 260px 1fr; height: 100vh; width: 100%; position:fixed; left:0; top:0; }
        .main-content { grid-column: 2; height: 100vh; overflow: hidden; box-sizing: border-box; position:relative; }
        
        .messaging-wrapper { display: flex !important; gap: 20px; height: 100vh; width: 100% !important; margin: 0; padding: 24px; box-sizing: border-box; }
        .msg-sidebar, .msg-container { background: white; border-radius: 20px; display: flex; flex-direction: column; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #ddd; overflow: hidden; }
        .msg-sidebar { width: 340px; flex-shrink:0; }
        .msg-container { flex: 1; position:relative; }

        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background:#fff; }
        
        /* THE ULTIMATE SIDEBAR SYNC */
        .sidebar { background: #ffffff !important; border-right: 1px solid rgba(46, 139, 87, 0.2); padding: 30px 0; display: flex; flex-direction: column; width: 260px; height: 100vh; position: fixed; left: 0; top: 0; z-index: 9999 !important; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); overflow-y: auto !important; }
        .logo { font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: #2e8b57; display: flex; align-items: center; text-decoration: none; padding: 0 20px 20px; border-bottom: 1px solid rgba(46, 139, 87, 0.2); margin-bottom: 15px; }
        .nav-links { list-style: none; padding: 0 15px; flex: 1; margin: 0; }
        .nav-links li { margin-bottom: 8px; }
        .nav-links a { display: flex; align-items: center; padding: 12px 15px; text-decoration: none; color: #4b5563; border-radius: 8px; transition: 0.2s; font-weight: 500; font-size: 14px; }
        .nav-links a:hover, .nav-links a.active { color: #2e8b57; background-color: rgba(46, 139, 87, 0.08); }

        .mobile-nav-header { display: none; background: white; padding: 12px 20px; border-bottom: 1px solid #ddd; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; width: 100%; z-index: 1500; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9500 !important; backdrop-filter: blur(4px); }
        .sidebar-overlay.active { display: block; }

        @media screen and (max-width: 992px) {
            .main-layout { grid-template-columns: 1fr; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { grid-column: 1; padding-top: 60px !important; }
            .mobile-nav-header { display: flex; }
            .messaging-wrapper { padding: 10px; height: calc(100vh - 60px); }
            .msg-sidebar { width: 100%; display: <?= $selected_thread_id ? 'none' : 'flex' ?>; }
            .msg-container { width: 100%; display: <?= $selected_thread_id ? 'flex' : 'none' ?>; }
        }

        .back-btn { display: none; background: none; border: none; font-size: 1.2rem; color: #2e8b57; cursor: pointer; margin-right: 15px; align-items:center; }
        @media screen and (max-width: 992px) { .back-btn { display: flex; } }
        
        .chat-input-area { padding: 15px 20px; border-top: 1px solid #f0f0f0; background: white; }
        .input-pill { background: #f8f9fa; border: 1px solid #ddd; border-radius: 30px; display: flex; align-items: center; padding: 6px 15px; width: 100%; box-sizing: border-box; }
        .chat-input { flex: 1; border: none !important; background: transparent !important; padding: 0 15px !important; outline: none !important; resize: none !important; font-size: 0.9rem; color: #1a1a1a !important; box-shadow: none !important; min-height: 24px; }
        
        .message-bubble { padding: 12px 18px; border-radius: 18px; max-width: 80%; line-height: 1.5; font-size: 0.9rem; }
        .message-wrapper.sent .message-bubble { background:#2E8B57; color:white !important; box-shadow: 0 4px 15px rgba(46,139,87,0.2); }
        .message-wrapper.received .message-bubble { background:#f1f1f1; color:#1a1a1a !important; }
    </style>
</head>
<body>
    <div class="mobile-nav-header">
        <button onclick="toggleSidebar()" style="background:none; border:none; font-size:1.5rem; color:#2e8b57;"><i class="fas fa-bars"></i></button>
        <div style="font-weight:700; color:#2e8b57; font-family:'Playfair Display',serif; font-size:1.2rem;">NutriDeq</div>
        <div style="width:30px;"></div>
    </div>

    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="atomicSidebar">
        <a class="logo" href="dashboard.php">
            <img src="assets/img/logo.png" alt="NutriDeq" style="height:45px; width:auto; border-radius:6px;">
            <span style="margin-left:10px;">NutriDeq</span>
        </a>
        <ul class="nav-links">
            <?php foreach ($nav_links as $link): ?>
                <?php if (isset($link['type']) && $link['type'] === 'header'): ?>
                    <li style="font-size:0.7rem; color:#999; text-transform:uppercase; margin-top:20px; margin-bottom:10px; padding-left:15px; font-weight:700;"><?= $link['text'] ?></li>
                <?php else: ?>
                    <li><a href="<?= $link['href'] ?>" class="<?= !empty($link['active'])?'active':'' ?>"><i class="<?= $link['icon'] ?>"></i> <span><?= $link['text'] ?></span></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div style="padding: 15px 20px; border-top: 1px solid rgba(46, 139, 87, 0.2); display: flex; align-items: center; gap: 12px;">
            <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #2e8b57, #4ca1af); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size:0.8rem;"><?= $user_initials ?></div>
            <div style="font-size:0.8rem;">
                <div style="font-weight:700; color:#1a1a1a;"><?= htmlspecialchars($user_name) ?></div>
                <div style="color:#999;"><?= ucfirst($user_role) ?></div>
            </div>
        </div>
        <div style="padding: 10px 20px; border-top: 1px solid rgba(46, 139, 87, 0.2);">
            <button id="logoutTrigger" style="display:flex; align-items:center; padding:10px 12px; text-decoration:none; color:#ff6b6b; border:none; background:none; width:100%; cursor:pointer; font-weight:500;"><i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout</button>
        </div>
    </div>

    <div class="main-layout">
        <main class="main-content">
            <div class="messaging-wrapper">
                <div class="msg-sidebar">
                    <div style="padding:20px; border-bottom:1px solid #eee; background:#fafafa;">
                        <h3 style="margin:0 0 15px 0; font-family:'Playfair Display',serif; font-size:1.3rem;">Support Inbox</h3>
                        <button onclick="openModal()" style="width:100%; background:#2e8b57; color:white; border:none; padding:12px; border-radius:12px; font-weight:700; cursor:pointer;">+ New Consult</button>
                    </div>
                    <div class="contact-list" style="overflow-y:auto; flex:1;">
                        <?php foreach ($threads as $thread): ?>
                            <div class="contact-item <?= ($selected_thread_id == $thread['id'])?'active':'' ?>" onclick="window.location.href='?thread_id=<?= $thread['id'] ?>'">
                                <div style="width:40px; height:40px; background:#f0f0f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2e8b57; flex-shrink:0;">#</div>
                                <div><div style="font-weight:700; color:#1a1a1a; font-size:0.9rem;"><?= htmlspecialchars($thread['title']) ?></div><div style="font-size:0.75rem; color:#888;"><?= ucfirst($thread['status']) ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="msg-container">
                    <?php if ($selected_thread): ?>
                        <div style="padding:15px 20px; border-bottom:1px solid #eee; background:#fafafa; display:flex; align-items:center;">
                            <button class="back-btn" onclick="window.location.href='staff-help.php'"><i class="fas fa-arrow-left"></i></button>
                            <h4 style="margin:0; font-weight:700; font-size:0.95rem;"><?= htmlspecialchars($selected_thread['title']) ?></h4>
                        </div>
                        <div class="chat-messages" id="chatMessages">
                            <?php foreach ($thread_messages as $msg): 
                                $isMe = ($msg['sender_id'] == $user_id); ?>
                                <div class="message-wrapper <?= $isMe ? 'sent' : 'received' ?>" id="msg-<?= $msg['id'] ?>">
                                    <div class="message-bubble">
                                        <?php if(!$isMe): ?><div style="font-size:0.7rem; color:#2e8b57; font-weight:700; margin-bottom:4px;"><?= htmlspecialchars($msg['sender_name']) ?></div><?php endif; ?>
                                        <div style="color:inherit !important;"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                        <div style="font-size:0.6rem; opacity:0.6; text-align:right; margin-top:5px;"><?= date('g:i A', strtotime($msg['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chat-input-area">
                            <form id="messageForm">
                                <div class="input-pill">
                                    <button type="button" class="icon-btn" id="attachBtn"><i class="fas fa-paperclip"></i></button>
                                    <input type="file" id="fileInput" style="display:none;" accept=".pdf,.png,.jpg,.jpeg">
                                    <textarea class="chat-input" id="messageInput" placeholder="Reply..." rows="1"></textarea>
                                    <button type="submit" class="icon-btn" style="color:#2e8b57;"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.5; text-align:center; padding:20px;"><i class="fas fa-comment-medical fa-4x" style="margin-bottom:15px; color:#2e8b57;"></i><h3 style="font-family:'Playfair Display',serif;">Support Hub</h3><p>Select a consultation thread.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="logoutModal" class="logout-modal"><div class="logout-modal-content"><i class="fas fa-sign-out-alt fa-3x" style="color:#ff6b6b; margin-bottom:20px;"></i><h3>Are you sure?</h3><p style="color:#666;">Logging out will end your session.</p><div style="display:flex; gap:12px; justify-content:center; margin-top:25px;"><button onclick="document.getElementById('logoutModal').classList.remove('active')" style="padding:12px 20px; border-radius:12px; border:1px solid #eee; background:none; cursor:pointer;">Cancel</button><button onclick="window.location.href='login-logout/logout.php'" style="padding:12px 20px; border-radius:12px; border:none; background:#ff6b6b; color:white; font-weight:700; cursor:pointer;">Logout</button></div></div></div>

            <div id="threadModal" class="modal-overlay" style="z-index:10005 !important;"><div class="modal-body" style="width:90%; max-width:400px; padding:25px;"><div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;"><h3>New Consultation</h3><button onclick="closeModal()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button></div><form method="POST"><div style="margin-bottom:15px;"><label style="font-weight:600;">Subject</label><input type="text" name="thread_title" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:12px;" required></div><div style="margin-bottom:15px;"><label style="font-weight:600;">Message</label><textarea name="initial_message" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:12px;" rows="3" required></textarea></div><div style="margin-bottom:15px;"><label style="font-weight:600; font-size:0.85rem;">Assign To</label><div style="max-height:80px; overflow-y:auto; border:1px solid #f0f0f0; padding:5px; border-radius:8px;">
                <?php foreach ($admins as $admin): ?><label style="display:flex; align-items:center; gap:8px; margin-bottom:5px; font-size:0.8rem;"><input type="checkbox" name="admins[]" value="<?= $admin['id'] ?>"> <?= htmlspecialchars($admin['name']) ?></label><?php endforeach; ?>
            </div></div><button type="submit" name="create_thread" style="width:100%; background:#2e8b57; color:white; border:none; padding:14px; border-radius:12px; font-weight:700;">Initiate Consult</button></form></div></div>

            <script>const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';</script>
            <script src="scripts/internal-chat-controller.js?v=109"></script>
            <script>
                function toggleSidebar() { 
                    document.getElementById('atomicSidebar').classList.toggle('active');
                    document.getElementById('sidebarOverlay').classList.toggle('active');
                }
                function openModal() { document.getElementById('threadModal').classList.add('active'); }
                function closeModal() { document.getElementById('threadModal').classList.remove('active'); }
                document.getElementById('logoutTrigger').onclick = () => document.getElementById('logoutModal').classList.add('active');
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
