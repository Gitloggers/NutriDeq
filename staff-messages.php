<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}
require_once 'navigation.php';
require_once 'database.php';
$staff_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] === 'admin');
$nav_links_array = getNavigationLinks($_SESSION['user_role'], 'staff-messages.php');
$database = new Database();
$pdo = $database->getConnection();

function getInitials($name)
{
    $p = explode(' ', $name);
    $i = '';
    foreach ($p as $x)
        if ($x !== '')
            $i .= strtoupper($x[0]);
    return substr($i, 0, 2);
}

// Fetch Clients with Last Message
$clients = [];
try {
    // Join with proper subquery to get the VERY LAST message per conversation
    $dt_check = $is_admin ? "" : "AND dietitian_id = ?";
    $client_check = $is_admin ? "" : "WHERE c.staff_id = ?";

    $sql = "
        SELECT 
            c.id, c.name, c.email, s.name as staff_name,
            (SELECT content FROM wellness_messages wm 
             WHERE wm.conversation_id = (SELECT id FROM conversations WHERE client_id = c.id $dt_check LIMIT 1) 
             ORDER BY wm.created_at DESC LIMIT 1) as last_message,
             (SELECT message_type FROM wellness_messages wm2 
             WHERE wm2.conversation_id = (SELECT id FROM conversations WHERE client_id = c.id $dt_check LIMIT 1) 
             ORDER BY wm2.created_at DESC LIMIT 1) as last_msg_type
        FROM clients c 
        LEFT JOIN users s ON c.staff_id = s.id
        $client_check
        ORDER BY c.name
    ";
    $stmt = $pdo->prepare($sql);

    $params = [];
    if (!$is_admin) {
        $params = [$staff_id, $staff_id, $staff_id];
    }

    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
$selected_client = null;
if ($selected_client_id) {
    foreach ($clients as $c)
        if ($c['id'] == $selected_client_id) {
            $selected_client = $c;
            break;
        }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Wellness Messages</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <!-- Premium Wellness CSS (v2) -->
    <link rel="stylesheet" href="css/modern-messages.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <script src="scripts/dashboard.js" defer></script>
    <script>
        const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';
        console.log('BASE_URL:', BASE_URL);
    </script>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <!-- Layout Wrapper -->
            <div class="messaging-wrapper <?= $selected_client_id ? 'view-chat' : 'view-list' ?>" id="messagingWrapper">

                <!-- B. Inbox Column (Floating Card) -->
                <div class="msg-sidebar">
                    <div class="msg-sidebar-header">
                        <h2>Wellness Inbox</h2>
                    </div>
                    <div class="contact-list">
                        <?php foreach ($clients as $client): ?>
                            <?php $isActive = ($client['id'] == $selected_client_id); ?>
                            <div class="contact-item <?= $isActive ? 'active' : '' ?>"
                                onclick="window.location.href='?client_id=<?= $client['id'] ?>'">
                                <div class="contact-avatar">
                                    <?= getInitials($client['name']) ?>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-name">
                                        <?= htmlspecialchars($client['name']) ?>
                                        <?php if ($is_admin && $client['staff_name']): ?>
                                            <span style="font-size: 0.7rem; color: #9ca3af; font-weight: 400; display: block;">
                                                Assigned: <?= htmlspecialchars($client['staff_name']) ?> -
                                                <?= getUserRoleText('staff') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="contact-preview">
                                        <?php
                                        if ($client['last_message']) {
                                            $txt = $client['last_message'];
                                            if ($client['last_msg_type'] === 'image')
                                                $txt = '📷 [Image]';
                                            elseif ($client['last_msg_type'] === 'file')
                                                $txt = '📎 [File]';

                                            echo htmlspecialchars(mb_strimwidth($txt, 0, 30, "..."));
                                        } else {
                                            echo "Start a conversation...";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- C. Chat Container (Floating Card) -->
                <div class="msg-container">
                    <?php if ($selected_client): ?>
                        <!-- D. Chat Header -->
                        <div class="chat-header">
                            <div style="display: flex; align-items: center;">
                                <button class="icon-btn" id="backToInbox"
                                    style="display: none; margin-right: 10px; color: var(--primary);"
                                    onclick="window.location.href='staff-messages.php'">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                            </div>
                            <div class="chat-header-user">
                                <div class="contact-avatar" style="width:40px; height:40px; font-size:0.9rem;">
                                    <?= getInitials($selected_client['name']) ?>
                                </div>
                                <div>
                                    <div class="header-name">
                                        <?= htmlspecialchars($selected_client['name']) ?>
                                        <?php if ($selected_client['staff_name']): ?>
                                            <span
                                                style="font-size: 0.85rem; color: #6b7280; font-weight: 400; margin-left: 8px;">
                                                - <?= htmlspecialchars($selected_client['staff_name']) ?>
                                                (<?= getUserRoleText('staff') ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="header-status">
                                        <span class="status-dot"></span> Active Now
                                    </div>
                                </div>
                            </div>
                            <div class="context-chips">
                                <div class="info-chip"><i class="fas fa-bullseye"></i> <span>Goal: Wellness</span></div>
                                <div class="info-chip"><i class="fas fa-file-alt"></i> <span>Plan: Keto</span></div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="chat-messages" id="chatMessages">
                            <div style="text-align:center; margin-top:40px; color:var(--text-tertiary);">
                                <i class="fas fa-spinner fa-spin"></i> Loading context...
                            </div>
                        </div>

                        <!-- I. AI Suggestions Overlay -->
                        <!-- Use correct Free Icon class: fa-wand-magic-sparkles -->
                        <!-- Wrapper Moved Inside Input Area -->

                        <?php if (!$is_admin): ?>
                            <!-- G. Input Area (Pill) -->
                            <div class="chat-input-area">
                                <!-- AI Suggestions Overlay (Moved Here for Positioning) -->
                                <div class="ai-suggestions-wrapper" id="aiSuggestions" style="display:none;"></div>

                                <div class="typing-indicator" id="typingIndicator"
                                    style="position:absolute; top:4px; left:40px; font-size:0.75rem; color:var(--text-secondary); opacity:0; transition:opacity 0.2s;">
                                    Client is typing...
                                </div>

                                <form id="messageForm">
                                    <input type="file" id="fileInput" name="attachment" style="display: none;">
                                    <div class="input-pill-container">
                                        <button type="button" class="icon-btn" id="attachBtn" title="Add File"><i
                                                class="fas fa-plus"></i></button>
                                        <button type="button" class="icon-btn" id="aiToggleBtn" title="AI Suggest">
                                            <i class="fas fa-wand-magic-sparkles" style="color:#818CF8;"></i>
                                        </button>

                                        <textarea class="chat-input" placeholder="Type a message..." rows="1"
                                            oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>

                                        <div class="input-actions">
                                            <button type="submit" class="icon-btn send-btn"><i
                                                    class="fas fa-paper-plane"></i></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Admin Read-Only View -->
                            <div class="chat-input-area"
                                style="justify-content: center; background-color: #f3f4f6; color: #6b7280; font-weight: 500;">
                                <i class="fas fa-eye" style="margin-right: 8px;"></i> Read-Only View (Admin Access)
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div
                            style="flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-tertiary);">
                            <div style="text-align:center;">
                                <i class="fas fa-comment-dots" style="font-size:3rem; margin-bottom:16px; opacity:0.1;"></i>
                                <p style="font-size:0.95rem;">Select a conversation to start</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($selected_client_id): ?>
                <script src="scripts/chat-controller.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const chat = new ChatController(<?= $staff_id ?>, 'staff', <?= $selected_client_id ?>);
                        document.getElementById('aiToggleBtn').addEventListener('click', () => {
                            chat.toggleAISuggestions();
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
