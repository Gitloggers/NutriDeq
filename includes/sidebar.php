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
?>

<!-- Mobile Header (Fixed) -->
<div class="mobile-header">
    <button class="mobile-nav-toggle" id="mobileNavToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="header-logo">
        <img src="assets/img/logo.png" alt="NutriDeq" height="45">
        <span>NutriDeq</span>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="mainSidebar">
    <a class="logo" href="dashboard.php">
        <img src="assets/img/logo.png" alt="NutriDeq" class="logo-img">
        <span>NutriDeq</span>
    </a>

    <ul class="nav-links">
        <?php foreach ($sidebar_nav_links as $link): ?>
            <?php if (isset($link['type']) && $link['type'] === 'header'): ?>
                <li class="nav-header">
                    <?php echo htmlspecialchars($link['text']); ?>
                </li>
            <?php else: ?>
                <li>
                    <a href="<?php echo $link['href']; ?>" class="<?php echo !empty($link['active']) ? 'active' : ''; ?>">
                        <i class="<?php echo $link['icon']; ?>"></i>
                        <span><?php echo $link['text']; ?></span>
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
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Global Logout Modal -->
<div class="logout-modal" id="logoutModal" style="z-index: 9999;">
    <div class="logout-modal-content">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h3>Are you sure?</h3>
        <p>You will be logged out and redirected to the login page.</p>
        <div class="logout-modal-actions">
            <button class="btn btn-secondary" id="cancelLogout">Cancel</button>
            <button class="btn btn-primary" id="confirmLogout">Yes, Logout</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="css/sidebar.css?v=119">
<link rel="stylesheet" href="css/logout-modal.css?v=119">
<link rel="stylesheet" href="css/interactive-animations.css?v=119">

        background: rgba(239, 68, 68, 0.1);
        transform: rotate(90deg);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function checkMessages() {
            fetch('handlers/get_dashboard_messages.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const unreadCount = data.unread_count || 0;
                        const badge = document.getElementById('unreadMessages');
                        if (badge) badge.textContent = unreadCount;
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }

        const userRole = '<?php echo $sidebar_user_role; ?>';
        if (userRole === 'admin' || userRole === 'staff') {
            setInterval(checkMessages, 5000);
            checkMessages();
        }
    });
</script>

<script src="scripts/dashboard.js?v=119" defer></script>
<script src="scripts/interactive-effects.js?v=119" defer></script>