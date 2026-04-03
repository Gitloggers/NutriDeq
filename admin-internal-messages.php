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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Masters | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css?v=106">
    <link rel="stylesheet" href="css/sidebar.css?v=106">
    <link rel="stylesheet" href="css/logout-modal.css?v=106">
    <style>
        /* ELITE MASTER HUB - V106 */
        body { margin: 0; background: #f0f2f5 !important; overflow: hidden; font-family:'Poppins',sans-serif; }
        .main-layout { display: grid; grid-template-columns: 260px 1fr; height: 100vh; width: 100%; position:fixed; left:0; top:0; }
        .main-content { grid-column: 2; height: 100vh; overflow-y: auto; padding: 24px !important; box-sizing: border-box !important; }
        
        .messaging-wrapper { display: flex !important; gap: 20px; height: calc(100vh - 48px); width: 100% !important; margin: 0; }
        .msg-sidebar, .msg-container { background: white; border-radius: 20px; display: flex; flex-direction: column; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #ddd; }
        .msg-sidebar { width: 340px; flex-shrink:0; }
        .msg-container { flex: 1; overflow: hidden; }

        .contact-item { padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1.25); border-bottom: 1px solid #f9f9f9; }
        .contact-item:hover { background: #f5f7fa; transform: translateX(10px); }
        .contact-item.active { background: #e6f4ea; border-left: 5px solid #2e8b57; }

        .chat-messages { flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background:#fff; }
        .message-wrapper { display: flex; width: 100%; animation: liquidIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .message-wrapper.sent { flex-direction: row-reverse; }
        .message-bubble { padding: 12px 18px; border-radius: 18px; max-width: 75%; font-size: 0.95rem; line-height: 1.5; }
        .message-wrapper.sent .message-bubble { background:#2E8B57; color:white !important; box-shadow: 0 4px 15px rgba(46,139,87,0.2); }
        .message-wrapper.received .message-bubble { background:#f1f1f1; color:#1a1a1a !important; }

        @keyframes liquidIn { from { opacity: 0; transform: scale(0.9) translateY(30px); } to { opacity: 1; transform: scale(1) translateY(0); } }

        /* THE ELITE PILL-ADMIN */
        .chat-input-area { padding: 15px 25px; border-top: 1px solid #f0f0f0; background: white; }
        .input-pill { background: #f8f9fa; border: 1px solid #ddd; border-radius: 30px; display: flex; align-items: center; padding: 8px 18px; width: 100%; box-sizing: border-box; }
        .chat-input { flex: 1; border: none !important; background: transparent !important; padding: 0 15px !important; outline: none !important; resize: none !important; font-family: inherit; font-size: 0.95rem; color: #1a1a1a !important; box-shadow: none !important; }
        
        /* SIDEBAR COMPACT LOGO */
        .sidebar { background: white; border-right: 1px solid #ddd; padding: 15px 20px; display: flex; flex-direction: column; }
        .logo-box { padding: 10px 0 20px 0; display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo-box img { height:40px; width:auto; }
        .logo-text { color:#2e8b57; font-size:1.5rem; font-weight:700; font-family:'Playfair Display',serif; }

        .nav-links { list-style:none; padding:0; margin:10px 0; flex:1; }
        .nav-links li { margin-bottom: 2px; }
        .nav-links a { display:flex; align-items:center; gap:12px; padding:10px 15px; border-radius:10px; text-decoration:none; color:#444; font-weight:500; transition:0.3s cubic-bezier(0.34, 1.56, 0.64, 1.25); font-size:0.9rem; }
        .nav-links a:hover { transform:scale(1.05); color:#2e8b57; }
        .nav-links a.active { background:#2e8b57; color:white; box-shadow:0 4px 12px rgba(46,139,87,0.2); }

        .logout-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
        .logout-modal.active { display: flex; }
        .logout-modal-content { background: white; padding: 40px; border-radius: 20px; text-align: center; max-width: 400px; animation: liquidIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
        
        .icon-btn { background:none; border:none; color:#666; cursor:pointer; padding:6px; font-size:1.2rem; transition:0.2s; display:flex; align-items:center; }
        .icon-btn:hover { color:#2e8b57; transform:scale(1.2); }
    </style>
</head>
<body>
    <div class="main-layout">
        <div class="sidebar">
            <a href="dashboard.php" class="logo-box">
                <img src="assets/img/logo.png" alt="NutriDeq">
                <span class="logo-text">NutriDeq</span>
            </a>
            <ul class="nav-links">
                <?php foreach ($nav_links as $link): ?>
                    <?php if (isset($link['type']) && $link['type'] === 'header'): ?>
                        <li style="font-size:0.7rem; color:#999; text-transform:uppercase; margin-top:12px; padding-left:15px; font-weight:700;"><?= $link['text'] ?></li>
                    <?php else: ?>
                        <li><a href="<?= $link['href'] ?>" class="<?= !empty($link['active'])?'active':'' ?>"><i class="<?= $link['icon'] ?>"></i> <span><?= $link['text'] ?></span></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <div style="border-top:1px solid #eee; padding-top:15px; display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; background:#e6f4ea; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2e8b57; font-weight:700; font-size:0.85rem;"><?= $user_initials ?></div>
                <div style="font-size:0.8rem;">
                    <div style="font-weight:700; color:#1a1a1a;"><?= htmlspecialchars($user_name) ?></div>
                    <div style="color:#999;">Administrator</div>
                </div>
            </div>
            <a href="javascript:void(0)" id="logoutTrigger" style="margin-top:12px; color:#ff6b6b; text-decoration:none; display:flex; align-items:center; gap:10px; font-weight:600; font-size:0.85rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <main class="main-content">
            <div class="messaging-wrapper">
                <div class="msg-sidebar">
                    <div style="padding:24px; border-bottom:1px solid #eee; background:#fafafa;">
                        <h3 style="margin:0 0 15px 0; font-family:'Playfair Display',serif;">Case Oversight</h3>
                        <form method="GET">
                            <select name="status" onchange="this.form.submit()" style="width:100%; padding:10px; border-radius:10px; border:1px solid #ddd; outline:none; font-family:inherit; color:#1a1a1a; font-size:0.9rem;">
                                <option value="all" <?= $status_filter=='all'?'selected':'' ?>>All Conversations</option>
                                <option value="open" <?= $status_filter=='open'?'selected':'' ?>>Active Cases</option>
                                <option value="resolved" <?= $status_filter=='resolved'?'selected':'' ?>>Resolved</option>
                            </select>
                        </form>
                    </div>
                    <div class="contact-list">
                        <?php foreach ($threads as $thread): ?>
                            <div class="contact-item <?= ($selected_thread_id == $thread['id'])?'active':'' ?>" onclick="window.location.href='?thread_id=<?= $thread['id'] ?>&status=<?= $status_filter ?>'">
                                <div style="width:40px; height:40px; background:#f0f0f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2e8b57;">#</div>
                                <div><div style="font-weight:700; color:#1a1a1a; font-size:0.9rem;"><?= htmlspecialchars($thread['title']) ?></div><div style="font-size:0.75rem; color:#888;"><?= ucfirst($thread['status']) ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="msg-container">
                    <?php if ($selected_thread): ?>
                        <div style="padding:18px 25px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#fafafa;">
                            <h4 style="margin:0; font-weight:700;"><?= htmlspecialchars($selected_thread['title']) ?></h4>
                            <?php if($selected_thread['status'] == 'open'): ?>
                            <form method="POST"><input type="hidden" name="thread_id" value="<?= $selected_thread_id ?>"><input type="hidden" name="update_status" value="1"><button type="submit" name="status" value="resolved" style="background:#2e8b57; color:white; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-weight:600; font-size:0.85rem; box-shadow:0 4px 12px rgba(46,139,87,0.15); transition:0.2s;"><i class="fas fa-check"></i> Close Case</button></form>
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
                                    <button type="button" class="icon-btn" id="attachBtn"><i class="fas fa-paperclip"></i></button>
                                    <input type="file" id="fileInput" style="display:none;" accept=".pdf,.png,.jpg,.jpeg">
                                    <textarea class="chat-input" id="messageInput" placeholder="Write an administrative response..." rows="1"></textarea>
                                    <button type="submit" class="icon-btn" style="color:#2e8b57;"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div style="text-align:center; padding:15px; color:#999; font-style:italic;">This clinical thread is marked as resolved.</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.5;"><i class="fas fa-shield-alt fa-4x" style="margin-bottom:15px; color:#2e8b57;"></i><h3 style="font-family:'Playfair Display',serif;">Admin Command Hub</h3><p>Select a consultation case to oversee.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="logoutModal" class="logout-modal"><div class="logout-modal-content"><i class="fas fa-sign-out-alt fa-3x" style="color:#ff6b6b; margin-bottom:20px;"></i><h3>Are you sure?</h3><p style="color:#666;">Logging out will end your administrative session.</p><div style="display:flex; gap:12px; justify-content:center; margin-top:25px;"><button onclick="document.getElementById('logoutModal').classList.remove('active')" style="padding:12px 24px; border-radius:12px; border:1px solid #eee; background:none; cursor:pointer; font-weight:600;">Wait, Cancel</button><button onclick="window.location.href='login-logout/logout.php'" style="padding:12px 24px; border-radius:12px; border:none; background:#ff6b6b; color:white; font-weight:700; cursor:pointer; box-shadow:0 4px 15px rgba(255,107,107,0.2);">Confirm Logout</button></div></div></div>

            <script src="scripts/internal-chat-controller.js?v=106"></script>
            <script>
                const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';
                document.getElementById('logoutTrigger').onclick = () => document.getElementById('logoutModal').classList.add('active');
                document.addEventListener('DOMContentLoaded', () => {
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
