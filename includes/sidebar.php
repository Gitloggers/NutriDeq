<?php
// includes/sidebar.php

// Helper function for initials if not already defined (using a check to avoid redefinition errors)
if (!function_exists('getSidebarInitials')) {
    function getSidebarInitials($name)
    {
        $names = explode(' ', $name);
        $initials = '';
        foreach ($names as $n) {
            if (!empty($n)) {
                $initials .= strtoupper($n[0]);
            }
        }
        return substr($initials, 0, 2);
    }
}

// Ensure necessary variables are available
$current_page = basename($_SERVER['PHP_SELF']);
$sidebar_user_role = $_SESSION['user_role'] ?? 'regular';
$sidebar_user_name = $_SESSION['user_name'] ?? 'User';
$sidebar_user_initials = getSidebarInitials($sidebar_user_name);

// Get navigation links from standardized navigation file
require_once 'navigation.php';
$sidebar_nav_links = getNavigationLinks($sidebar_user_role, $current_page);

// Update last activity for real-time monitoring
if (isset($_SESSION['user_id'])) {
    try {
        // Find PDO if not already available in parent scope
        if (!isset($pdo)) {
            require_once 'database.php';
            $sidebar_db = new Database();
            $pdo = $sidebar_db->getConnection();
        }
        $update_activity = $pdo->prepare("UPDATE users SET last_active = NOW(), online_status = 1 WHERE id = ?");
        $update_activity->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log("Sidebar status update failed: " . $e->getMessage());
    }
}
?>

<!-- Mobile Top Header -->
<div class="mobile-header">
    <div class="header-logo">
        <img src="assets/img/logo.png" alt="NutriDeq" style="height: 32px; width: auto;">
        <span>NutriDeq</span>
    </div>
    <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle Menu">
        <i class="fas fa-bars"></i>
    </button>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <a class="logo" href="dashboard.php">
            <img src="assets/img/logo.png" alt="NutriDeq" class="logo-img">
            <span class="logo-text">NutriDeq</span>
        </a>
        <div class="sidebar-controls">
            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <button class="mobile-sidebar-close" id="mobileSidebarClose" title="Close Menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <ul class="nav-links">
        <?php foreach ($sidebar_nav_links as $link): ?>
            <?php if (isset($link['type']) && $link['type'] === 'header'): ?>
                <li class="nav-header">
                    <?php echo htmlspecialchars($link['text']); ?>
                </li>
            <?php else: ?>
                <li>
                    <a href="<?php echo $link['href']; ?>" class="<?php echo !empty($link['active']) ? 'active' : ''; ?>" title="<?php echo $link['text']; ?>">
                        <i class="<?php echo $link['icon']; ?>"></i>
                        <span class="nav-text"><?php echo $link['text']; ?></span>
                        <?php 
                        // Show badge for pending reset requests if this is User Management
                        if (strpos($link['href'], 'admin-user-management.php') !== false && ($sidebar_user_role === 'admin' || $sidebar_user_role === 'staff')):
                            try {
                                if (!isset($pdo)) {
                                    require_once 'database.php';
                                    $sidebar_db = new Database();
                                    $pdo = $sidebar_db->getConnection();
                                }
                                $reset_stmt = $pdo->prepare("SELECT COUNT(*) FROM password_reset_requests WHERE status = 'pending' " . ($sidebar_user_role === 'staff' ? "AND staff_id = ?" : ""));
                                if ($sidebar_user_role === 'staff') {
                                    $reset_stmt->execute([$_SESSION['user_id']]);
                                } else {
                                    $reset_stmt->execute();
                                }
                                $reset_count = $reset_stmt->fetchColumn();
                                if ($reset_count > 0):
                                    echo '<span class="badge" style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 5px;">' . $reset_count . '</span>';
                                endif;
                            } catch (Exception $e) {}
                        endif; 
                        ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <div class="user-profile">
        <div class="user-avatar"><?php echo $sidebar_user_initials; ?></div>
        <div class="user-info">
            <h4><?php echo htmlspecialchars($sidebar_user_name); ?></h4>
            <p><?php echo getUserRoleText($sidebar_user_role); ?></p>
        </div>
    </div>

    <div class="logout-section">
        <a href="login-logout/logout.php" class="logout-btn" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i>
            <span class="nav-text">Logout</span>
        </a>
    </div>
</div>

<!-- Mobile Bottom App Bar -->
<nav class="bottom-app-bar" id="mobileBottomBar">
    <div class="bottom-nav-links">
        <?php 
        // Filter out headers for mobile view
        $filtered_links = array_filter($sidebar_nav_links, function($l) {
            return !isset($l['type']) || $l['type'] !== 'header';
        });
        $filtered_links = array_values($filtered_links); // Re-index
        $total_links = count($filtered_links);
        
        $limit = 5;
        $show_menu_btn = ($total_links > $limit);
        $display_count = $show_menu_btn ? 4 : $total_links;

        for ($i = 0; $i < $display_count; $i++) {
            $link = $filtered_links[$i];
            $is_active = !empty($link['active']) ? 'active' : '';
            echo '<a href="' . $link['href'] . '" class="mobile-nav-item ' . $is_active . '">';
            echo '<i class="' . $link['icon'] . '"></i>';
            echo '<span>' . $link['text'] . '</span>';
            echo '</a>';
        }

        if ($show_menu_btn) {
            echo '<button class="mobile-nav-item" id="mobileMenuTrigger">';
            echo '<i class="fas fa-th-large"></i>';
            echo '<span>Menu</span>';
            echo '</button>';
        }
        ?>
    </div>
</nav>

<!-- Global Logout Modal -->
<div class="logout-modal" id="logoutModal" style="z-index: 10001;">
    <div class="logout-modal-content">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h3>Are you sure?</h3>
        <p>You will be logged out and redirected to the login page.</p>
        <div class="logout-modal-actions">
            <button class="btn btn-secondary" id="cancelLogout">Cancel</button>
            <button class="btn btn-primary" id="confirmLogout" onclick="window.location.href='login-logout/logout.php'">Yes, Logout</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoutTrigger = document.getElementById('logoutTrigger');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogout = document.getElementById('cancelLogout');
        
        // Mobile Navigation Elements
        const mobileNavToggle = document.getElementById('mobileNavToggle');
        const mobileSidebarClose = document.getElementById('mobileSidebarClose');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainSidebar = document.getElementById('mainSidebar');
        const mobileMenuTrigger = document.getElementById('mobileMenuTrigger');

        const toggleSidebar = () => {
            mainSidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = mainSidebar.classList.contains('active') ? 'hidden' : '';
        };

        const closeSidebar = () => {
            mainSidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        };

        if (mobileNavToggle) mobileNavToggle.addEventListener('click', toggleSidebar);
        if (mobileSidebarClose) mobileSidebarClose.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
        if (mobileMenuTrigger) mobileMenuTrigger.addEventListener('click', toggleSidebar);

        if (logoutTrigger && logoutModal) {
            logoutTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                logoutModal.classList.add('active');
            });
        }
        
        if (cancelLogout && logoutModal) {
            cancelLogout.addEventListener('click', () => {
                logoutModal.classList.remove('active');
            });
        }
        
        // Close modal when clicking on the backdrop
        if (logoutModal) {
            logoutModal.addEventListener('click', (e) => {
                if (e.target === logoutModal) {
                    logoutModal.classList.remove('active');
                }
            });
        }
    });
</script>

<link rel="stylesheet" href="css/sidebar.css?v=119">
<link rel="stylesheet" href="css/logout-modal.css?v=119">
<link rel="stylesheet" href="css/interactive-animations.css?v=119">

<!-- Global Notification Toast -->
<div id="notificationToast" class="notification-toast">
    <div class="toast-icon">
        <i class="fas fa-comment-dots"></i>
    </div>
    <div class="toast-content">
        <h4 class="toast-title">New Message</h4>
        <p class="toast-message" id="toastMessageText">Checking messages...</p>
    </div>
    <button class="toast-close" id="closeToastBtn">
        <i class="fas fa-times"></i>
    </button>
</div>

<style>
    .notification-toast {
        position: fixed; 
        bottom: 30px; 
        right: 30px;
        background: white; 
        border-radius: 16px; 
        padding: 16px 24px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.12); 
        display: flex; 
        align-items: center; 
        gap: 16px;
        transform: translateY(150px) scale(0.9); 
        opacity: 0; 
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        z-index: 10001; 
        border-left: 6px solid #2D8A56; 
        cursor: pointer;
        max-width: 380px;
    }

    .notification-toast.show { 
        transform: translateY(0) scale(1); 
        opacity: 1; 
    }

    .notification-toast:hover { 
        transform: translateY(-5px) scale(1.02); 
        background: #fbfdfc; 
        box-shadow: 0 20px 60px rgba(45,138,86,0.15);
    }

    .toast-icon { 
        width: 44px; 
        height: 44px; 
        border-radius: 12px; 
        background: rgba(45,138,86,0.1); 
        color: #2D8A56; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        font-size: 1.2rem;
    }

    .toast-content {
        flex: 1;
    }

    .toast-title { 
        margin: 0; 
        font-size: 1rem; 
        font-weight: 700; 
        color: #1a1a1a; 
        font-family: 'Poppins', sans-serif;
    }

    .toast-message { 
        margin: 2px 0 0; 
        font-size: 0.85rem; 
        color: #666; 
    }

    .toast-close { 
        background: none; 
        border: none; 
        color: #aaa; 
        cursor: pointer; 
        font-size: 1.1rem;
        padding: 4px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .toast-close:hover {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
        transform: rotate(90deg);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('notificationToast');
        const closeBtn = document.getElementById('closeToastBtn');
        const audio = new Audio('assets/notification.mp3');
        let knownMessageIds = new Set();
        let isManuallyClosed = false;

        function checkMessages() {
            // Using absolute path or relative to root
            fetch('handlers/get_dashboard_messages.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const unreadCount = data.unread_count || 0;
                        
                        if (unreadCount === 0) {
                            hideToast();
                            isManuallyClosed = false;
                        } else {
                            checkForNewMessages(data.messages);
                        }

                        const badge = document.getElementById('unreadMessages');
                        if (badge) badge.textContent = unreadCount;
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }

        function checkForNewMessages(messages) {
            if (!messages) return;
            let hasNew = false;
            let latestSender = '';
            
            messages.forEach(msg => {
                const id = parseInt(msg.id);
                if (!knownMessageIds.has(id)) {
                    knownMessageIds.add(id);
                    if (!msg.is_read) {
                        hasNew = true;
                        latestSender = msg.client_name;
                    }
                }
            });

            if (hasNew) {
                isManuallyClosed = false;
                showToast(latestSender);
            } else if (messages.some(m => !m.is_read)) {
                // If there are unread messages but none are "new" to this session,
                // we still show the toast if it hasn't been closed.
                const firstUnread = messages.find(m => !m.is_read);
                if (firstUnread) showToast(firstUnread.client_name);
            }
        }

        function showToast(sender) {
            const text = document.getElementById('toastMessageText');
            if (text) text.textContent = `New message from ${sender}`;
            if (toast && !isManuallyClosed) {
                toast.classList.add('show');
            }
            // Optional: only play audio for ACTUAL new messages
        }

        function hideToast() {
            if (toast) toast.classList.remove('show');
        }

        if (toast) {
            toast.onclick = function(e) {
                if (e.target.closest('#closeToastBtn')) return;
                const userRole = '<?php echo $sidebar_user_role; ?>';
                window.location.href = (userRole === 'admin') ? 'admin-internal-messages.php' : 'staff-messages.php';
            };
        }

        if (closeBtn) {
            closeBtn.onclick = function(e) {
                e.stopPropagation();
                hideToast();
                isManuallyClosed = true;
            };
        }

        // Global polling for messages
        if ('<?php echo $sidebar_user_role; ?>' === 'admin' || '<?php echo $sidebar_user_role; ?>' === 'staff') {
            setInterval(checkMessages, 5000);
            checkMessages();
        }
    });
</script>

<script src="scripts/dashboard.js?v=119" defer></script>
<script src="scripts/interactive-effects.js?v=119" defer></script>