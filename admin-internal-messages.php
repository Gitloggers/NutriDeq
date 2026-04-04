<?php
session_start();
// Master Anti-Cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

require_once 'navigation.php';
require_once 'database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$is_admin = true;

function getInitials($name) {
    if (!$name) return '?';
    $p = explode(' ', $name); $i = '';
    foreach ($p as $x) if ($x !== '') $i .= strtoupper($x[0]);
    return substr($i, 0, 2);
}
$user_initials = getInitials($user_name);
$nav_links = getNavigationLinks($user_role, 'admin-internal-messages.php');

$database = new Database(); $pdo = $database->getConnection();
$status_filter = $_GET['status'] ?? 'all';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = $_POST['thread_id'] ?? null;
    if (isset($_POST['update_status'])) { $pdo->prepare("UPDATE internal_threads SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$_POST['status'] ?? 'open', $tid]); header("Location: admin-internal-messages.php?thread_id=$tid&status=$status_filter"); exit(); }
    if (isset($_POST['delete'])) { $pdo->prepare("DELETE FROM internal_thread_messages WHERE thread_id = ?")->execute([$tid]); $pdo->prepare("DELETE FROM internal_threads WHERE id = ?")->execute([$tid]); header("Location: admin-internal-messages.php"); exit(); }
}

$where = $status_filter !== 'all' ? "status = ?" : "1=1";
$params = $status_filter !== 'all' ? [$status_filter] : [];
$stmt = $pdo->prepare("SELECT * FROM internal_threads WHERE $where ORDER BY last_message_at DESC");
$stmt->execute($params);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : null;
$selected_thread = null;
$thread_messages = [];
if ($selected_thread_id) {
    $stmt = $pdo->prepare("SELECT * FROM internal_threads WHERE id = ?");
    $stmt->execute([$selected_thread_id]);
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
    <title>Admin Masters | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css?v=119">
    <link rel="stylesheet" href="css/sidebar.css?v=119">
    <link rel="stylesheet" href="css/logout-modal.css?v=119">
    <style>
        /* ADMIN INTERACTION FORTRESS - V118 */
        body { margin: 0; background: #f4f7f6 !important; overflow: hidden; font-family:'Poppins',sans-serif; }
        .main-layout { display: grid; grid-template-columns: 260px 1fr; height: 100vh; width: 100%; position:fixed; left:0; top:0; }
        .main-content { grid-column: 2; height: 100vh; overflow: hidden; box-sizing: border-box; position:relative; }
        .messaging-wrapper { display: flex !important; gap: 20px; height: 100vh; width: 100% !important; margin: 0; padding: 20px; box-sizing: border-box; }
        .msg-sidebar, .msg-container { background: white; border-radius: 24px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05); overflow: hidden; animation: liquidIn 0.6s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .msg-sidebar { width: 360px; flex-shrink:0; }
        .msg-container { flex: 1; }

        @keyframes liquidIn { from { opacity: 0; transform: translateY(20px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }

        /* THE ATOMIC SIDEBAR SYNC (PEAK Z-INDEX) */
        .sidebar { background: #ffffff !important; border-right: 1px solid rgba(46, 139, 87, 0.1); padding: 20px 0; display: flex; flex-direction: column; width: 260px; height: 100vh; position: fixed; left: 0; top: 0; z-index: 9999 !important; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); overflow-y: auto !important; }
        .logo { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: #2e8b57; display: flex; align-items: center; text-decoration: none; padding: 0 20px 20px; border-bottom: 1px solid rgba(46, 139, 87, 0.1); margin-bottom: 15px; }
        .nav-links { list-style: none; padding: 0 15px; flex: 1; margin: 0; }
        .nav-links a { display: flex; align-items: center; padding: 12px 15px; text-decoration: none; color: #555; border-radius: 10px; transition: all 0.3s ease; font-weight: 500; font-size: 14px; position: relative; }
        .nav-links a:hover, .nav-links a.active { color: #2e8b57; background-color: rgba(46, 139, 87, 0.08); }

        .mobile-nav-header { display: none; background: #2e8b57; padding: 15px 20px; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; width: 100%; z-index: 9000; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9500 !important; backdrop-filter: blur(4px); }
        .sidebar-overlay.active { display: block; }

        .back-btn { display: none; background: none; border: none; font-size: 1.2rem; color: #2e8b57; cursor: pointer; margin-right: 15px; }

        /* CHAT BUBBLE RESTORATION - V118 */
        .chat-messages { flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background:#fff; scroll-behavior: smooth; }
        .message-wrapper { display: flex; width: 100%; margin-bottom: 10px; animation: liquidIn 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        .message-wrapper.sent { flex-direction: row-reverse; }
        .message-wrapper.received { flex-direction: row; }
        
        .message-bubble { padding: 14px 20px; border-radius: 20px; max-width: 75%; line-height: 1.5; font-size: 0.95rem; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .message-wrapper.sent .message-bubble { background:#2E8B57; color:white !important; border-bottom-right-radius: 4px; }
        .message-wrapper.received .message-bubble { background:#f0f2f5; color:#1a1a1a !important; border-bottom-left-radius: 4px; border: 1px solid rgba(0,0,0,0.02); }
        
        .chat-input-area { padding: 15px 25px; border-top: 1px solid #f0f0f0; background: white; }
        .input-pill { background: #f4f7f6; border: 1px solid transparent; border-radius: 30px; display: flex; align-items: center; padding: 5px 15px; width: 100%; box-sizing: border-box; }

        @media screen and (max-width: 992px) {
            .main-layout { grid-template-columns: 1fr; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { grid-column: 1; padding: 75px 10px 10px 10px !important; }
            .mobile-nav-header { display: flex; }
            .messaging-wrapper { padding: 0; height: calc(100vh - 85px); gap: 0; }
            .msg-sidebar, .msg-container { border-radius: 0; border: none; box-shadow: none; width: 100%; display: none; }
            <?php if (!$selected_thread_id) : ?> .msg-sidebar { display: flex; } <?php else : ?> .msg-container { display: flex; } <?php endif; ?>
            .back-btn { display: flex; }
        }
    </style>
</head>
<body>
    <div class="mobile-nav-header">
        <button onclick="toggleSidebar()" style="background:none; border:none; font-size:1.5rem; color:white;"><i class="fas fa-bars"></i></button>
        <div style="font-weight:700; color:white; font-family:'Playfair Display',serif; font-size:1.2rem;">NutriDeq Admin</div>
        <div style="width:30px;"></div>
    </div>

    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="atomicSidebar">
        <a class="logo" href="dashboard.php">
            <img src="assets/img/logo.png" alt="NutriDeq" style="height: 40px; width: auto;">
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
        <div style="padding: 15px 20px; border-top: 1px solid rgba(46, 139, 87, 0.1); display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #2e8b57, #4ca1af); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;"><?= $user_initials ?></div>
            <div style="font-size:0.8rem;">
                <div style="font-weight:700; color:#1a1a1a;"><?= htmlspecialchars($user_name) ?></div>
                <div style="color:#999;">Administrator</div>
            </div>
        </div>
        <div style="padding: 10px 20px; border-top: 1px solid rgba(46, 139, 87, 0.1);">
            <button id="logoutTrigger" style="display:flex; align-items:center; padding:10px 12px; text-decoration:none; color:#ff6b6b; border:none; background:none; width:100%; cursor:pointer; font-weight:600;"><i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout</button>
        </div>
    </div>

    <div class="main-layout">
        <main class="main-content">
            <div class="messaging-wrapper">
                <div class="msg-sidebar">
                    <div style="padding:24px; border-bottom:1px solid #eee; background:#fafafa;">
                        <h3 style="margin:0 0 15px 0; font-family:'Playfair Display',serif; font-size:1.3rem;">Clinical Oversight</h3>
                        <form method="GET">
                            <select name="status" onchange="this.form.submit()" style="width:100%; padding:15px; border-radius:12px; border:1px solid #ddd; outline:none; font-family:inherit; color:#1a1a1a; font-size:0.9rem;">
                                <option value="all" <?= $status_filter=='all'?'selected':'' ?>>All Conversations</option>
                                <option value="open" <?= $status_filter=='open'?'selected':'' ?>>Active Cases</option>
                                <option value="resolved" <?= $status_filter=='resolved'?'selected':'' ?>>Resolved</option>
                            </select>
                        </form>
                    </div>
                    <div class="contact-list" style="overflow-y:auto; flex:1; padding-top:10px;">
                        <?php foreach ($threads as $thread): ?>
                            <div class="contact-item <?= ($selected_thread_id == $thread['id'])?'active':'' ?>" onclick="window.location.href='?thread_id=<?= $thread['id'] ?>&status=<?= $status_filter ?>'">
                                <div style="width:42px; height:42px; background:#f4f7f6; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2e8b57; flex-shrink:0; font-weight:700;">#</div>
                                <div><div style="font-weight:700; color:#1a1a1a; font-size:0.95rem;"><?= htmlspecialchars($thread['title']) ?></div><div style="font-size:0.8rem; color:#888;"><?= ucfirst($thread['status']) ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="msg-container">
                    <?php if ($selected_thread): ?>
                        <div style="padding:18px 25px; border-bottom:1px solid #eee; background:#fff; display:flex; align-items:center; justify-content:space-between;">
                            <div style="display:flex; align-items:center;">
                                <button class="back-btn" onclick="window.location.href='admin-internal-messages.php?status=<?= $status_filter ?>'"><i class="fas fa-arrow-left"></i></button>
                                <h4 style="margin:0; font-weight:700; color:#1a1a1a;"><?= htmlspecialchars($selected_thread['title']) ?></h4>
                            </div>
                            <?php if($selected_thread['status'] == 'open'): ?>
                            <form method="POST"><input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>"><input type="hidden" name="update_status" value="1"><button type="submit" name="status" value="resolved" style="background:#2e8b57; color:white; border:none; padding:10px 20px; border-radius:12px; cursor:pointer; font-weight:600; font-size:0.85rem; box-shadow: 0 4px 15px rgba(46,139,87,0.15);">Close Case</button></form>
                            <?php endif; ?>
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
                            <?php if($selected_thread['status'] == 'open'): ?>
                            <form id="messageForm">
                                <div class="input-pill">
                                    <button type="button" class="icon-btn" id="attachBtn" style="background:none; border:none; color:#666; cursor:pointer; padding:10px;"><i class="fas fa-paperclip"></i></button>
                                    <input type="file" id="fileInput" style="display:none;" accept=".pdf,.png,.jpg,.jpeg">
                                    <textarea class="chat-input" id="messageInput" style="flex:1; border:none !important; background:transparent !important; padding:10px 15px !important; outline:none !important; resize:none !important; font-size:0.95rem; color:#1a1a1a;" placeholder="Type a response..." rows="1"></textarea>
                                    <button type="submit" class="icon-btn" style="background:none; border:none; color:#2e8b57; cursor:pointer; padding:10px;"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.5; background:#fff;"><i class="fas fa-shield-alt fa-4x" style="margin-bottom:15px; color:#2e8b57;"></i><h3 style="font-family:'Playfair Display',serif;">Admin Command</h3><p>Select a case.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="logoutModal" class="logout-modal"><div class="logout-modal-content" style="background:white; padding:40px; border-radius:24px; text-align:center; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.15);"><i class="fas fa-sign-out-alt fa-3x" style="color:#ff6b6b; margin-bottom:20px;"></i><h3>End Session?</h3><p style="color:#666;">Are you sure you want to log out?</p><div style="display:flex; gap:12px; justify-content:center; margin-top:25px;"><button onclick="document.getElementById('logoutModal').classList.remove('active')" style="padding:12px 24px; border-radius:12px; border:1px solid #eee; background:none; cursor:pointer; font-weight:600;">Cancel</button><button onclick="window.location.href='login-logout/logout.php'" style="padding:12px 24px; border-radius:12px; border:none; background:#ff6b6b; color:white; font-weight:700; cursor:pointer;">Logout</button></div></div></div>

            <script src="scripts/internal-chat-controller.js?v=119"></script>
            <script>
                function toggleSidebar() { 
                    document.getElementById('atomicSidebar').classList.toggle('active');
                    document.getElementById('sidebarOverlay').classList.toggle('active');
                }
                document.addEventListener('DOMContentLoaded', () => {
                    const logoutBtn = document.getElementById('logoutTrigger');
                    const logoutModal = document.getElementById('logoutModal');
                    if (logoutBtn && logoutModal) {
                        logoutBtn.onclick = (e) => {
                            e.preventDefault();
                            logoutModal.classList.add('active');
                        };
                    }
                    <?php if ($selected_thread_id): ?>
                    new ChatController(<?= $user_id ?>, 'admin', <?= $selected_thread_id ?>);
                    const el = document.getElementById('chatMessages'); if(el) el.scrollTop = el.scrollHeight;
                    <?php endif; ?>
                });
            </script>
        </main>
    </div>
</body>
</html>
