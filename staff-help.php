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
    <link rel="stylesheet" href="css/base.css?v=111">
    <link rel="stylesheet" href="css/sidebar.css?v=111">
    <link rel="stylesheet" href="css/logout-modal.css?v=111">
    <style>
        /* MOBILE STACKING DOMINION - V111 */
        body { margin: 0; background: #f4f7f6 !important; overflow: hidden; font-family:'Poppins',sans-serif; }
        
        .main-layout { display: grid; grid-template-columns: 260px 1fr; height: 100vh; width: 100%; position:fixed; left:0; top:0; }
        .main-content { grid-column: 2; height: 100vh; overflow: hidden; box-sizing: border-box; }
        
        .messaging-wrapper { display: flex !important; gap: 20px; height: 100vh; width: 100% !important; margin: 0; padding: 20px; box-sizing: border-box; }
        .msg-sidebar, .msg-container { background: white; border-radius: 24px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.05); overflow: hidden; animation: liquidIn 0.6s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .msg-sidebar { width: 360px; flex-shrink:0; }
        .msg-container { flex: 1; }

        .contact-item { padding: 15px 20px; border-radius: 16px; display: flex; align-items: center; gap: 15px; cursor: pointer; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); border-bottom: 1px solid #f9f9f9; margin: 4px 10px; }
        .contact-item:hover { background: #f8fbf9; transform: translateX(5px); }
        .contact-item.active { background: #eff7f2; border-left: 4px solid #2e8b57; }

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

        @media screen and (max-width: 992px) {
            .main-layout { grid-template-columns: 1fr; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { grid-column: 1; padding: 75px 10px 10px 10px !important; }
            .mobile-nav-header { display: flex; }
            .messaging-wrapper { padding: 0; height: calc(100vh - 85px); gap: 0; }
            .msg-sidebar, .msg-container { border-radius: 0; border: none; box-shadow: none; width: 100%; display: none; }
            <?php if (!$selected_thread_id) : ?> .msg-sidebar { display: flex; } <?php else : ?> .msg-container { display: flex; } <?php endif; ?>
        }

        .back-btn { display: none; background: none; border: none; font-size: 1.2rem; color: #2e8b57; cursor: pointer; margin-right: 15px; }

        /* CHAT BUBBLE RESTORATION - V115 */
        .chat-messages { flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background:#fff; scroll-behavior: smooth; }
        .message-wrapper { display: flex; width: 100%; margin-bottom: 10px; animation: liquidIn 0.5s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .message-wrapper.sent { flex-direction: row-reverse; }
        .message-wrapper.received { flex-direction: row; }
        
        .message-bubble { padding: 14px 20px; border-radius: 20px; max-width: 75%; line-height: 1.5; font-size: 0.95rem; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .message-wrapper.sent .message-bubble { background:#2E8B57; color:white !important; border-bottom-right-radius: 4px; }
        .message-wrapper.received .message-bubble { background:#f0f2f5; color:#1a1a1a !important; border-bottom-left-radius: 4px; border: 1px solid rgba(0,0,0,0.02); }
        
        .chat-input-area { padding: 15px 25px; border-top: 1px solid #f0f0f0; background: white; }
        .input-pill { background: #f4f7f6; border: 1px solid transparent; border-radius: 30px; display: flex; align-items: center; padding: 5px 15px; width: 100%; box-sizing: border-box; transition: 0.3; }
        .chat-input { flex: 1; border: none !important; background: transparent !important; padding: 10px 15px !important; outline: none !important; resize: none !important; font-size: 0.95rem; color: #1a1a1a !important; box-shadow: none !important; }

        @media screen and (max-width: 992px) { .back-btn { display: flex; } }
    </style>
</head>
<body>
    <div class="mobile-nav-header">
        <button onclick="toggleSidebar()" style="background:none; border:none; font-size:1.5rem; color:white;"><i class="fas fa-bars"></i></button>
        <div style="font-weight:700; color:white; font-family:'Playfair Display',serif; font-size:1.2rem;">NutriDeq Support</div>
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
                <div style="color:#999;"><?= ucfirst($user_role) ?></div>
            </div>
        </div>
        <div style="padding: 10px 20px; border-top: 1px solid rgba(46, 139, 87, 0.1);">
            <button id="logoutTrigger" style="display:flex; align-items:center; padding:10px 12px; text-decoration:none; color:#ff6b6b; border:none; background:none; width:100%; cursor:pointer; font-weight:500;"><i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout</button>
        </div>
    </div>

    <div class="main-layout">
        <main class="main-content">
            <div class="messaging-wrapper">
                <div class="msg-sidebar">
                    <div style="padding:24px; border-bottom:1px solid #eee; background:#fafafa;">
                        <h3 style="margin:0 0 15px 0; font-family:'Playfair Display',serif; font-size:1.3rem;">Clinical Support</h3>
                        <button onclick="openModal()" style="width:100%; background:#2e8b57; color:white; border:none; padding:15px; border-radius:12px; font-weight:700; cursor:pointer; box-shadow: 0 4px 15px rgba(46,139,87,0.2);">New Inquiry</button>
                    </div>
                    <div class="contact-list" style="overflow-y:auto; flex:1; padding-top:10px;">
                        <?php foreach ($threads as $thread): ?>
                            <div class="contact-item <?= ($selected_thread_id == $thread['id'])?'active':'' ?>" onclick="window.location.href='?thread_id=<?= $thread['id'] ?>'">
                                <div style="width:42px; height:42px; background:#f4f7f6; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#2e8b57; flex-shrink:0; font-weight:700;">#</div>
                                <div><div style="font-weight:700; color:#1a1a1a; font-size:0.95rem;"><?= htmlspecialchars($thread['title']) ?></div><div style="font-size:0.8rem; color:#888;"><?= ucfirst($thread['status']) ?></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="msg-container">
                    <?php if ($selected_thread): ?>
                        <div style="padding:18px 25px; border-bottom:1px solid #eee; background:#fff; display:flex; align-items:center;">
                            <button class="back-btn" onclick="window.location.href='staff-help.php'"><i class="fas fa-arrow-left"></i></button>
                            <h4 style="margin:0; font-weight:700; color:#1a1a1a;"><?= htmlspecialchars($selected_thread['title']) ?></h4>
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
                                <div class="input-pill" style="background: #f4f7f6; border: 1px solid transparent; border-radius: 30px; display: flex; align-items: center; padding: 5px 15px; width: 100%; box-sizing: border-box; transition: 0.3s;">
                                    <button type="button" class="icon-btn" id="attachBtn" style="background:none; border:none; color:#666; cursor:pointer; padding:10px;"><i class="fas fa-paperclip"></i></button>
                                    <input type="file" id="fileInput" style="display:none;" accept=".pdf,.png,.jpg,.jpeg">
                                    <textarea class="chat-input" id="messageInput" style="flex:1; border:none; background:transparent; padding:10px 15px; outline:none; resize:none; font-size:0.95rem; color:#1a1a1a;" placeholder="Type a message..." rows="1"></textarea>
                                    <button type="submit" class="icon-btn" style="background:none; border:none; color:#2e8b57; cursor:pointer; padding:10px;"><i class="fas fa-paper-plane"></i></button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0.5; background:#fff;"><i class="fas fa-comment-medical fa-4x" style="margin-bottom:15px; color:#2e8b57;"></i><h3 style="font-family:'Playfair Display',serif;">NutriDeq Center</h3><p>Select a consultation.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MODALS -->
            <div id="threadModal" class="modal-overlay"><div class="modal-body" style="width:90%; max-width:400px; padding:30px;"><div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;"><h3>New Consult</h3><button onclick="closeModal()" style="border:none; background:none; font-size:24px; cursor:pointer;">&times;</button></div><form method="POST"><div style="margin-bottom:15px;"><label style="font-weight:600;">Subject</label><input type="text" name="thread_title" style="width:100%; padding:15px; border:1px solid #ddd; border-radius:12px; outline:none;" required></div><div style="margin-bottom:15px;"><label style="font-weight:600;">Assign Admins</label><div style="max-height:100px; overflow-y:auto; border:1px solid #f0f0f0; padding:10px; border-radius:12px;">
                <?php foreach ($admins as $admin): ?><label style="display:flex; align-items:center; gap:10px; margin-bottom:5px; font-size:0.9rem;"><input type="checkbox" name="admins[]" value="<?= $admin['id'] ?>"> <?= htmlspecialchars($admin['name']) ?></label><?php endforeach; ?>
            </div></div><div style="margin-bottom:15px;"><label style="font-weight:600;">Initial Message</label><textarea name="initial_message" style="width:100%; padding:15px; border:1px solid #ddd; border-radius:12px; outline:none;" rows="3" required></textarea></div><button type="submit" name="create_thread" style="width:100%; background:#2e8b57; color:white; border:none; padding:15px; border-radius:12px; font-weight:700; cursor:pointer;">Start Thread</button></form></div></div>

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
