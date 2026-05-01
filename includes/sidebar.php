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
require_once __DIR__ . '/../navigation.php';
$sidebar_nav_links = getNavigationLinks($sidebar_user_role, $current_page);

// Update last activity for real-time monitoring
if (isset($_SESSION['user_id'])) {
    try {
        // Find PDO if not already available in parent scope
        if (!isset($pdo)) {
            require_once __DIR__ . '/../database.php';
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
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/global-elite.css">

<!-- Global Scroll Bar -->
<div id="nd-scroll-bar"></div>

<!-- Global Ambient Mesh Orbs -->
<div class="global-mesh-wrap">
    <div class="global-orb global-orb-1"></div>
    <div class="global-orb global-orb-2"></div>
    <div class="global-orb global-orb-3"></div>
</div>


<!-- Mobile Top Header -->
<div class="mobile-header">
    <div class="header-logo">
        <img src="assets/img/logo.png" alt="NutriDeq" style="height: 32px; width: auto;">
        <span>NutriDeq</span>
    </div>
    <div class="mobile-header-actions">
        <button class="mobile-header-logout" id="mobileLogoutTrigger" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </button>
        <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
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
                <i class="fas fa-sidebar" id="collapseIcon"></i>
            </button>
            <button class="mobile-sidebar-close" id="mobileSidebarClose" title="Close Menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <ul class="nav-links" id="navLinksList">
        <?php foreach ($sidebar_nav_links as $nav_item): 
            $item_text = $nav_item['text'] ?? 'Untitled';
            $item_icon = $nav_item['icon'] ?? 'fas fa-circle';
            $item_href = $nav_item['href'] ?? '#';
            $is_header = isset($nav_item['type']) && $nav_item['type'] === 'header';
        ?>
            <?php if ($is_header): ?>
                <li class="nav-header">
                    <?php echo htmlspecialchars($item_text); ?>
                </li>
            <?php else: ?>
                <li>
                    <a href="<?php echo htmlspecialchars($item_href); ?>" 
                       class="<?php echo !empty($nav_item['active']) ? 'active' : ''; ?>" 
                       title="<?php echo htmlspecialchars($item_text); ?>">
                        <i class="<?php echo htmlspecialchars($item_icon); ?>"></i>
                        <span class="nav-text"><?php echo htmlspecialchars($item_text); ?></span>
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
        <a href="login-logout/logout.php" class="logout-btn" id="logoutTrigger">
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

<link rel="stylesheet" href="css/sidebar.css?v=200">
<link rel="stylesheet" href="css/logout-modal.css?v=119">
<link rel="stylesheet" href="css/interactive-animations.css?v=119">

<!-- Scripts are now at the bottom for reliability -->


<style>
    /* ─── Force Nav Visibility (nuclear override) ─── */
    .nav-links li {
        display: block !important;
        visibility: visible !important;
        height: auto !important;
        min-height: 44px !important;
    }
    .nav-links a {
        color: #4b5563 !important;
        width: 100% !important;
        text-decoration: none !important;
    }
    .nav-links a:hover,
    .nav-links a.active {
        color: #059669 !important;
    }
    .nav-links .nav-text {
        color: inherit !important;
    }

    /* ─── Notification Toast ─── */
    .notification-toast {
        position: fixed; 
        bottom: 30px; right: 30px;
        background: white; 
        border-radius: 16px; 
        padding: 16px 24px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.12); 
        display: flex; 
        align-items: center; gap: 16px;
        transform: translateY(150px) scale(0.9); 
        opacity: 0; 
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        z-index: 10001; 
        border-left: 6px solid #059669; 
        cursor: pointer;
        max-width: 380px;
    }
    .notification-toast.show { transform: translateY(0) scale(1); opacity: 1; }
    .notification-toast:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 20px 60px rgba(45,138,86,0.15); }
    .toast-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(5,150,105,0.1); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .toast-content { flex: 1; }
    .toast-title { margin: 0; font-size: 1rem; font-weight: 700; color: #1a1a1a; font-family: 'Poppins', sans-serif; }
    .toast-message { margin: 2px 0 0; font-size: 0.85rem; color: #666; }
    .toast-close { background: none; border: none; color: #aaa; cursor: pointer; font-size: 1.1rem; padding: 4px; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
    .toast-close:hover { color: #ef4444; background: rgba(239,68,68,0.1); transform: rotate(90deg); }
</style>



<!-- GSAP Core & Plugins -->
<!-- GSAP Core & Plugins -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<script>
/**
 * NUTRIDEQ SIDEBAR ENGINE
 * Unified mobile/desktop controller
 */
document.addEventListener('DOMContentLoaded', () => {
    const mainSidebar        = document.getElementById('mainSidebar');
    const sidebarOverlay     = document.getElementById('sidebarOverlay');
    const collapseBtn        = document.getElementById('sidebarCollapseBtn');
    const mobileNavToggle    = document.getElementById('mobileNavToggle');
    const mobileSidebarClose = document.getElementById('mobileSidebarClose');
    const mobileMenuTrigger  = document.getElementById('mobileMenuTrigger'); // From bottom app bar
    const logoutTrigger      = document.getElementById('logoutTrigger');
    const mobileLogoutTrigger= document.getElementById('mobileLogoutTrigger');
    const logoutModal        = document.getElementById('logoutModal');
    const cancelLogout       = document.getElementById('cancelLogout');
    const mainLayout         = document.querySelector('.main-layout');
    const navItems           = document.querySelectorAll('.nav-links a');
    const navHeaders         = document.querySelectorAll('.nav-header');

    if (!mainSidebar || !gsap) return;

    // 1. Initial State & Entrance
    const sidebarTL = gsap.timeline({ delay: 0.1 });
    sidebarTL.from(mainSidebar, { x: -20, opacity: 0, duration: 0.6, ease: "power3.out" })
             .to(navHeaders, { opacity: 1, duration: 0.4, stagger: 0.05 }, "-=0.3")
             .to(navItems, { opacity: 1, y: 0, duration: 0.5, stagger: 0.04, ease: "power3.out" }, "-=0.25");

    gsap.set(navItems, { y: 10 });

    // 2. Mobile Logic
    let isMobileSidebarOpen = false;

    const openMobileSidebar = (e) => {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (isMobileSidebarOpen || !mainSidebar) return;
        isMobileSidebarOpen = true;

        // Reset any desktop-collapse widths for mobile mode
        gsap.set(mainSidebar, { 
            x: '-100%', 
            display: 'flex', 
            width: '280px',
            opacity: 1,
            visibility: 'visible',
            zIndex: 10005 // Higher than overlay (10002) and header (10001)
        });
        
        mainSidebar.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (sidebarOverlay) {
            sidebarOverlay.style.display = 'block';
            sidebarOverlay.classList.add('active');
        }

        gsap.to(mainSidebar, { 
            x: '0%', 
            duration: 0.5, 
            ease: "expo.out",
            force3D: true
        });

        
        gsap.fromTo(navItems, 
            { x: -15, opacity: 0 }, 
            { x: 0, opacity: 1, stagger: 0.04, duration: 0.45, ease: "power3.out", delay: 0.1 }
        );
    };

    const closeMobileSidebar = (e) => {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (!isMobileSidebarOpen || !mainSidebar) return;
        isMobileSidebarOpen = false;

        gsap.to(mainSidebar, {
            x: '-100%', duration: 0.4, ease: "power3.in",
            onComplete: () => {
                document.body.style.overflow = '';
                mainSidebar.classList.remove('active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                    setTimeout(() => { if (!isMobileSidebarOpen) sidebarOverlay.style.display = 'none'; }, 300);
                }
                mainSidebar.style.transform = '';
            }
        });
    };

    // Resilient Listeners
    if (mobileNavToggle) mobileNavToggle.onclick = openMobileSidebar;
    if (mobileMenuTrigger) mobileMenuTrigger.onclick = openMobileSidebar;
    if (mobileSidebarClose) mobileSidebarClose.onclick = closeMobileSidebar;
    if (sidebarOverlay) sidebarOverlay.onclick = closeMobileSidebar;


    // 3. Desktop Collapse
    let isCollapsed = false;
    collapseBtn?.addEventListener('click', () => {
        isCollapsed = !isCollapsed;
        const width = isCollapsed ? '76px' : '260px';
        gsap.to(mainSidebar, { width, duration: 0.4, ease: isCollapsed ? "power3.inOut" : "expo.out" });
        gsap.to('.nav-text, .user-info, .logo-text, .nav-header', { opacity: isCollapsed ? 0 : 1, duration: 0.2, stagger: 0.02 });
        mainSidebar.classList.toggle('collapsed', isCollapsed);
        mainLayout?.classList.toggle('sidebar-collapsed', isCollapsed);
    });

    // 4. Logout Modal
    const toggleLogout = (show) => {
        if (!logoutModal) return;
        if (show) {
            // If mobile sidebar is open, close it first
            if (isMobileSidebarOpen) closeMobileSidebar();
            logoutModal.classList.add('active');
        }
        gsap.to(logoutModal, { opacity: show ? 1 : 0, duration: 0.3, onComplete: () => !show && logoutModal.classList.remove('active') });
        gsap.fromTo(logoutModal.querySelector('.logout-modal-content') || logoutModal, 
            { scale: show ? 0.9 : 1, y: show ? 20 : 0 },
            { scale: show ? 1 : 0.9, y: show ? 0 : 20, duration: 0.4, ease: show ? "back.out(1.5)" : "power2.in" });
    };


    [logoutTrigger, mobileLogoutTrigger].forEach(btn => btn?.addEventListener('click', (e) => { e.preventDefault(); toggleLogout(true); }));
    cancelLogout?.addEventListener('click', () => toggleLogout(false));
    logoutModal?.addEventListener('click', (e) => e.target === logoutModal && toggleLogout(false));

    // 5. Message Polling
    const toast = document.getElementById('notificationToast');
    const pollMessages = () => {
        if ('<?php echo $sidebar_user_role; ?>' !== 'admin' && '<?php echo $sidebar_user_role; ?>' !== 'staff') return;
        fetch('handlers/get_dashboard_messages.php').then(r => r.json()).then(data => {
            if (data.success) {
                const badge = document.getElementById('unreadMessages');
                if (badge) badge.textContent = data.unread_count || 0;
                if (data.unread_count > 0 && toast && !toast.classList.contains('show')) {
                    const latest = data.messages[0]?.client_name || 'System';
                    document.getElementById('toastMessageText').textContent = `New message from ${latest}`;
                    toast.classList.add('show');
                }
            }
        });
    };
    if (toast) {
        setInterval(pollMessages, 60000);
        pollMessages();
        document.getElementById('closeToastBtn')?.addEventListener('click', (e) => { e.stopPropagation(); toast.classList.remove('show'); });
        toast.addEventListener('click', () => { 
            const role = '<?php echo $sidebar_user_role; ?>';
            window.location.href = (role === 'admin') ? 'admin-internal-messages.php' : 'staff-messages.php';
        });
    }
});
</script>

<script src="scripts/dashboard.js?v=119" defer></script>
<script src="scripts/interactive-effects.js?v=119" defer></script>
<script src="scripts/global-gsap.js" defer></script>