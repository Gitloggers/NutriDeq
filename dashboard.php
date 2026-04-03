<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

// Get user data from session
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

// Generate initials for avatar
function getInitials($name)
{
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper($n[0]);
    }
    return substr($initials, 0, 2);
}

$user_initials = getInitials($user_name);

// Role-based navigation links
// Role-based navigation links
require_once 'navigation.php';
$nav_links_array = getNavigationLinks($user_role, 'dashboard.php');
// The loop further down (line 135) iterates via foreach ($nav_links as $link).
// getNavigationLinks returns an associative array where keys are filenames.
// The loop expects an array of arrays.
// The structure returned by getNavigationLinks is: ['filename' => ['href' => '...', ...], ...]
// PHP foreach ($associative_array as $item) iterates over VALUES.
// So $link will be the inner array ['href' => '...', ...].
// This matches the structure expected by the template.
$nav_links = $nav_links_array;

// Role-based dashboard content - UPDATED
function getDashboardTitle($role)
{
    switch ($role) {
        case 'admin':
            return 'Admin Dashboard';
        case 'staff':
            return 'Staff Dashboard';
        default:
            return 'Dashboard';
    }
}

function getDashboardDescription($role)
{
    switch ($role) {
        case 'admin':
            return 'System administration and user management';
        case 'staff':
            return 'Client management and nutrition monitoring';
        default:
            return 'Welcome to your nutrition dashboard';
    }
}
require_once 'database.php';
$database = new Database();
$pdo = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>NutriDeq - <?php echo getDashboardTitle($user_role); ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- External CSS Files -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <?php if ($user_role === 'admin'): ?>
        <link rel="stylesheet" href="css/admin.css">
    <?php elseif ($user_role === 'staff'): ?>
        <link rel="stylesheet" href="css/staff.css">
    <?php else: ?>
        <link rel="stylesheet" href="css/user.css">
    <?php endif; ?>
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <link rel="stylesheet" href="css/user-premium.css">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#2e8b57">
    <style>
        /* Dashboard-specific refined mobile metrics */
        @media screen and (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
            }
            .stat-card {
                padding: 15px !important;
                margin-bottom: 0 !important;
            }
            .charts-section {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    </style>
    <script>
        const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';
    </script>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-container">
                <div class="header">
                    <div class="page-title">
                        <h1><?php echo getDashboardTitle($user_role); ?></h1>
                        <p><?php echo getDashboardDescription($user_role); ?></p>
                    </div>

                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search..." class="global-search"
                                data-target=".dashboard-grid .stat-card">
                        </div>
                    </div>
                </div>

            <!-- Role-specific Dashboard Content -->
            <?php if ($user_role === 'admin'): ?>
                <?php
                $total_users = 0;
                $active_users = 0;
                $admin_count = 0;
                $staff_count = 0;
                $staff_active_count = 0;
                $users_week_diff = 0;
                $staff_month_diff = 0;

                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users, SUM(role='admin') as admin_count, SUM(role='staff') as staff_count, SUM(status='active') as active_users, SUM(role='staff' AND status='active') as staff_active FROM users");
                    $stmt->execute();
                    $row = $stmt->fetch();
                    if ($row) {
                        $total_users = (int) ($row['total_users'] ?? 0);
                        $active_users = (int) ($row['active_users'] ?? 0);
                        $admin_count = (int) ($row['admin_count'] ?? 0);
                        $staff_count = (int) ($row['staff_count'] ?? 0);
                        $staff_active_count = (int) ($row['staff_active'] ?? 0);
                    }
                } catch (Exception $e) {
                }

                try {
                    $week_curr = $pdo->prepare("SELECT COUNT(*) FROM users WHERE YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)");
                    $week_prev = $pdo->prepare("SELECT COUNT(*) FROM users WHERE YEARWEEK(created_at,1)=YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK),1)");
                    $week_curr->execute();
                    $week_prev->execute();
                    $users_week_diff = (int) $week_curr->fetchColumn() - (int) $week_prev->fetchColumn();
                } catch (Exception $e) {
                    $users_week_diff = 0;
                }

                try {
                    $staff_month_curr = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='staff' AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())");
                    $staff_month_prev = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='staff' AND YEAR(created_at)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
                    $staff_month_curr->execute();
                    $staff_month_prev->execute();
                    $staff_month_diff = (int) $staff_month_curr->fetchColumn() - (int) $staff_month_prev->fetchColumn();
                } catch (Exception $e) {
                    $staff_month_diff = 0;
                }

                // Fetch all staff for the dropdown
                try {
                    $staff_stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'staff' AND status = 'active' ORDER BY name ASC");
                    $staff_stmt->execute();
                    $staff_members = $staff_stmt->fetchAll();
                } catch (Exception $e) {
                    $staff_members = [];
                }
                ?>
                <!-- ADMIN DASHBOARD CONTENT - FOCUSED ON SYSTEM MANAGEMENT -->
                <div class="dashboard-grid admin-grid">
                    <div class="stat-card" onclick="location.href='admin-user-management.php'">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="totalUsers"><?php echo $total_users; ?></h3>
                            <p>Total Users</p>
                            <div class="metric-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                <span><?php echo ($users_week_diff >= 0 ? '+' : '') . $users_week_diff; ?> this week</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="location.href='admin-staff-management.php'">
                        <div class="stat-icon staff">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="activeStaff"><?php echo $staff_active_count; ?></h3>
                            <p>Active Staff (<?php echo $staff_count; ?> total)</p>
                            <div class="metric-trend trend-up">
                                <i class="fas fa-arrow-up"></i>
                                <span><?php echo ($staff_month_diff >= 0 ? '+' : '') . $staff_month_diff; ?> this
                                    month</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="location.href='admin-user-management.php'">
                        <div class="stat-icon system">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_users; ?></h3>
                            <p>Active Accounts</p>
                            <div class="metric-trend trend-stable">
                                <i class="fas fa-minus"></i>
                                <span>Real-time</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Command Center - Staff Filter Card -->
                <div class="management-section">
                    <div class="section-header">
                        <h2><i class="fas fa-chart-line"></i> Command Center</h2>
                        <div class="header-actions" style="display: flex; align-items: center; gap: 15px;">
                            <select id="staffFilter" class="form-control touch-ready"
                                style="min-width: 200px; padding: 10px 15px; border-radius: 10px; border: 2px solid #e9ecef; font-family: 'Poppins', sans-serif;">
                                <option value="all">All Staff (Global View)</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary touch-ready"
                                onclick="updateDashboard(document.getElementById('staffFilter').value)"
                                style="height: 48px; width: 48px; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="charts-section">
                    <!-- Staff Engagement Delta Card -->
                    <div class="chart-container" id="staffInfluenceContainer">
                        <div class="chart-header">
                            <h2>Staff Performance Influence <i class="fas fa-info-circle" title="Measures how often staff interact with users relative to their assigned client load."></i></h2>
                            <span id="deltaSelectedBadge"
                                style="display: none; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">--</span>
                        </div>
                        <p style="color: var(--gray); font-size: 0.85rem; margin-bottom: 15px;">Client activity change 24h
                            before vs. after staff interaction</p>
                        <div id="staffInfluenceList" style="display: flex; flex-direction: column; gap: 10px;">
                            <!-- Populated by JS -->
                            <div style="color: #999; font-size: 0.85rem;">Loading staff data...</div>
                        </div>
                    </div>

                    <!-- Workload & Alerts Card -->
                    <div class="chart-container" id="workloadAlertsContainer">
                        <div class="chart-header">
                            <h2>Workload & Alerts</h2>
                        </div>
                        <div id="workloadInfo" style="margin-bottom: 20px;">
                            <div
                                style="height: 12px; background: #e9ecef; border-radius: 6px; overflow: hidden; margin-top: 8px;">
                                <div id="workloadProgressBar"
                                    style="width: 0%; height: 100%; background: var(--primary); transition: width 0.5s ease;">
                                </div>
                            </div>
                            <p id="workloadText" style="font-size: 0.85rem; color: var(--gray); margin-top: 8px;">Loading
                                metrics...</p>
                        </div>
                        <div id="alertsList"
                            style="display: flex; flex-direction: column; gap: 10px; max-height: 200px; overflow-y: auto;">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>

                <!-- System Efficiency Card -->
                <div class="chart-container" style="margin-bottom: 30px;">
                    <div class="chart-header">
                        <h2>Platform Activity Overview <i class="fas fa-info-circle" title="Measures how active users are today relative to the weekly average. Over 100% means the system is performing exceptionally well."></i></h2>
                    </div>
                    <div id="efficiencyMetrics" style="display: flex; align-items: center; gap: 30px; padding: 15px;">
                        <div class="efficiency-gauge" style="position: relative; width: 100px; height: 100px;">
                            <canvas id="efficiencyDoughnutChart"></canvas>
                            <div id="efficiencyPctLabel" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 700; font-size: 1.2rem;">--%</div>
                        </div>
                        <div class="efficiency-details" style="flex: 1;">
                            <p id="efficiencyDescription" style="margin: 0; color: var(--gray); font-size: 0.9rem; line-height: 1.4;">Crunching platform stats...</p>
                            <div id="efficiencyTrendLine" style="margin-top: 5px; font-weight: 600; font-size: 0.8rem; color: var(--primary);">Calculating trends...</div>
                        </div>
                    </div>
                    <canvas id="activityRatiosChart" height="120" style="display: none;"></canvas>
                </div>

                <!-- System Management Quick Actions -->
                <div class="management-section">
                    <div class="section-header">
                        <h2>Admin Actions</h2>

                    </div>

                    <div class="quick-actions">
                        <div class="action-card" onclick="location.href='admin-staff-management.php'">
                            <div class="action-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4>Manage Staff</h4>
                            <p>Add, edit, or remove staff accounts</p>
                        </div>

                        <div class="action-card" onclick="location.href='admin-user-management.php'">
                            <div class="action-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <h4>User Management</h4>
                            <p>Manage all user accounts and permissions</p>
                        </div>

                        <div class="action-card" onclick="location.href='admin-client-management.php'">
                            <div class="action-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4>Client Management</h4>
                            <p>Add, edit, delete, and restore client records</p>
                        </div>

                        <div class="action-card" onclick="location.href='admin-internal-messages.php'">
                            <div class="action-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h4>Internal Messages</h4>
                            <p>Handle's problems regarding system performance and reports</p>
                        </div>
                    </div>
                </div>

                <!-- Recent System Activity -->
                <div class="management-section">
                    <div class="section-header">
                        <h2>Recent System Activity</h2>
                        <button class="btn btn-outline" id="refreshSystemActivity">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>

                    <div class="system-activity-list" id="recentActivityList">
                        <!-- REAL-TIME DATA INJECTED HERE -->
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>Connecting to live system feed...</p>
                        </div>
                    </div>
                    <script src="scripts/admin-realtime.js" defer></script>
                    <div class="system-activity-list-obsolete" style="display:none;">
                        <div class="activity-item">
                            <div class="activity-icon success">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">New Staff Account Created</div>
                                <div class="activity-description">Dr. Sarah Johnson registered as staff member</div>
                                <div class="activity-time">10 minutes ago</div>
                            </div>
                        </div>

                        <div class="activity-item">
                            <div class="activity-icon info">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">System Settings Updated</div>
                                <div class="activity-description">Email notification preferences changed</div>
                                <div class="activity-time">2 hours ago</div>
                            </div>
                        </div>

                        <div class="activity-item">
                            <div class="activity-icon warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">High System Load</div>
                                <div class="activity-description">Server CPU usage peaked at 85%</div>
                                <div class="activity-time">4 hours ago</div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($user_role === 'staff'): ?>
                <?php
                // STAFF DASHBOARD - DATABASE CONNECTION & QUERIES
                $staff_id = $_SESSION['user_id'];

                // SAFE QUERIES - Using client-staff relationship (most reliable)
            
                // Today's Appointments
            

                // Yesterday's Appointments for comparison
            

                // Active Clients
                try {
                    $active_clients = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM clients 
            WHERE staff_id = ? AND status = 'active'
        ");
                    $active_clients->execute([$staff_id]);
                    $clients_count = $active_clients->fetchColumn();
                } catch (Exception $e) {
                    $clients_count = 0;
                }

                // Weekly Progress Entries
                try {
                    $weekly_progress = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM progress_entries 
            WHERE staff_id = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
        ");
                    $weekly_progress->execute([$staff_id]);
                    $progress_count = $weekly_progress->fetchColumn();
                } catch (Exception $e) {
                    $progress_count = 0;
                }

                // Last week progress for comparison
                try {
                    $last_week_progress = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM progress_entries 
            WHERE staff_id = ? AND YEARWEEK(created_at, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)
        ");
                    $last_week_progress->execute([$staff_id]);
                    $last_week_count = $last_week_progress->fetchColumn();
                    $progress_diff = $progress_count - $last_week_count;
                } catch (Exception $e) {
                    $progress_diff = 0;
                }

                // Unread Messages (FIXED: Using wellness_messages)
                try {
                    $unread_messages = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM wellness_messages wm
            JOIN conversations c ON wm.conversation_id = c.id
            WHERE c.dietitian_id = ? AND wm.sender_type = 'client' AND wm.read_at IS NULL
        ");
                    $unread_messages->execute([$staff_id]);
                    $messages_count = $unread_messages->fetchColumn();
                } catch (Exception $e) {
                    $messages_count = 0;
                }

                // Upcoming Appointments (next 7 days)
            
                // Recent Messages (last 5 unread or recent from wellness_messages)
                try {
                    $recent_messages = $pdo->prepare("
            SELECT 
                wm.id,
                c.name as client_name,
                c.id as client_id,
                wm.content as message,
                wm.created_at,
                wm.read_at,
                wm.message_type,
                TIMESTAMPDIFF(MINUTE, wm.created_at, NOW()) as minutes_ago
            FROM wellness_messages wm
            JOIN conversations conv ON wm.conversation_id = conv.id
            JOIN clients c ON conv.client_id = c.id
            WHERE conv.dietitian_id = ? AND wm.sender_type = 'client'
            ORDER BY wm.created_at DESC
            LIMIT 5
        ");
                    $recent_messages->execute([$staff_id]);
                    $messages = $recent_messages->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $messages = [];
                }
                ?>

                <!-- STAFF DASHBOARD CONTENT -->
                <div class="dashboard-grid staff-grid">


                    <div class="stat-card" onclick="location.href='user-management-staff.php'">
                        <div class="stat-icon trackers">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="activeClients"><?php echo $clients_count; ?></h3>
                            <p>Active Clients</p>
                            <div class="metric-trend trend-neutral">
                                <i class="fas fa-user-check"></i>
                                <span>Assigned to you</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="location.href='anthropometric-information.php'">
                        <div class="stat-icon calories">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="weeklyProgress"><?php echo $progress_count; ?></h3>
                            <p>Weekly Progress Entries</p>
                            <div class="metric-trend <?php echo $progress_diff >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <i class="fas fa-arrow-<?php echo $progress_diff >= 0 ? 'up' : 'down'; ?>"></i>
                                <span><?php echo $progress_diff >= 0 ? '+' : '';
                                echo $progress_diff; ?> from last week</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card" onclick="location.href='staff-messages.php'">
                        <div class="stat-icon messages">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="unreadMessages"><?php echo $messages_count; ?></h3>
                            <p>Unread Messages</p>
                            <div class="metric-trend <?php echo $messages_count > 0 ? 'trend-alert' : 'trend-neutral'; ?>">
                                <i
                                    class="fas fa-<?php echo $messages_count > 0 ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                                <span><?php echo $messages_count > 0 ? 'Needs attention' : 'All caught up'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout for Appointments and Messages -->




                <!-- Recent Messages Column -->
                <div class="column">
                    <div class="management-section">
                        <div class="section-header">

                        </div>

                        <!-- Modal Structure -->
                        <div id="modalOverlay" class="modal-overlay">
                            <div class="modal-container">
                                <div class="modal-header">
                                    <h3 id="modalTitle">All Appointments</h3>
                                    <button class="modal-close" onclick="closeModal()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-content">
                                    <div id="modalContent">
                                        <!-- Content will be loaded here via AJAX -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="messages-list">
                            <?php if (count($messages) > 0): ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message-item <?php echo $message['read_at'] === null ? 'unread' : ''; ?>">
                                        <div class="message-avatar">
                                            <?php echo strtoupper(substr($message['client_name'], 0, 2)); ?>
                                        </div>
                                        <div class="message-details">
                                            <div class="message-header">
                                                <div class="message-client"><?php echo htmlspecialchars($message['client_name']); ?>
                                                </div>
                                                <div class="message-time">
                                                    <?php
                                                    echo date('M j, g:i A', strtotime($message['created_at']));
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="message-preview">
                                                <?php
                                                $preview = htmlspecialchars($message['message']);
                                                if (strlen($preview) > 80) {
                                                    $preview = substr($preview, 0, 80) . '...';
                                                }
                                                echo $preview;
                                                ?>
                                            </div>
                                            <?php if ($message['read_at'] === null): ?>
                                                <div class="message-status unread-badge">Unread</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-actions">
                                            <button class="action-btn view-btn" title="View Message"
                                                onclick="location.href='staff-messages.php?client_id=<?php echo $message['client_id']; ?>'">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <i class="fas fa-comments"></i>
                                    <h3>No Messages</h3>
                                    <p>You have no messages from clients at this time.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .two-column-layout {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 25px;
                    margin-bottom: 30px;
                }

                .column {
                    display: flex;
                    flex-direction: column;
                }

                .appointment-item,
                .message-item {
                    display: flex;
                    align-items: center;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    margin-bottom: 10px;
                    transition: var(--transition);
                    border-left: 4px solid var(--primary);
                }

                .message-item.unread {
                    background: rgba(74, 144, 226, 0.05);
                    border-left-color: var(--secondary);
                    font-weight: 500;
                }

                .appointment-item:hover,
                .message-item:hover {
                    background: rgba(46, 139, 87, 0.05);
                    transform: translateX(5px);
                }

                .appointment-avatar,
                .message-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: var(--gradient);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: 600;
                    font-size: 14px;
                    margin-right: 15px;
                }

                .appointment-details,
                .message-details {
                    flex: 1;
                }

                .appointment-client,
                .message-client {
                    font-weight: 600;
                    margin-bottom: 5px;
                    color: var(--dark);
                }

                .appointment-time,
                .message-time {
                    font-size: 0.8rem;
                    color: var(--gray);
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .appointment-type-badge {
                    padding: 3px 8px;
                    background: rgba(46, 139, 87, 0.1);
                    color: var(--primary);
                    border-radius: 12px;
                    font-size: 0.7rem;
                    font-weight: 500;
                }

                .message-preview {
                    font-size: 0.85rem;
                    color: var(--gray);
                    margin-top: 5px;
                    line-height: 1.3;
                }

                .message-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 5px;
                }

                .message-status {
                    margin-top: 5px;
                }

                .unread-badge {
                    padding: 2px 6px;
                    background: var(--accent);
                    color: white;
                    border-radius: 8px;
                    font-size: 0.7rem;
                    font-weight: 500;
                }

                .appointment-actions,
                .message-actions {
                    display: flex;
                    gap: 5px;
                }

                .no-data-message {
                    text-align: center;
                    padding: 40px 20px;
                    color: #666;
                }

                .no-data-message i {
                    font-size: 3rem;
                    margin-bottom: 15px;
                    color: #ccc;
                    display: block;
                }

                .no-data-message h3 {
                    margin-bottom: 10px;
                    color: #333;
                }

                @media (max-width: 768px) {
                    .two-column-layout {
                        grid-template-columns: 1fr;
                    }
                }

                .modal-overlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.6);
                    backdrop-filter: blur(5px);
                    z-index: 1000;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    animation: fadeIn 0.3s ease;
                }

                .modal-container {
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    width: 90%;
                    max-width: 800px;
                    max-height: 80vh;
                    display: flex;
                    flex-direction: column;
                    animation: slideUp 0.3s ease;
                }

                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px 25px;
                    border-bottom: 1px solid #eee;
                    background: var(--light);
                    border-radius: 15px 15px 0 0;
                }

                .modal-header h3 {
                    margin: 0;
                    color: var(--dark);
                    font-size: 1.3rem;
                }

                .modal-close {
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    color: var(--gray);
                    cursor: pointer;
                    padding: 5px;
                    border-radius: 50%;
                    transition: var(--transition);
                }

                .modal-close:hover {
                    background: rgba(0, 0, 0, 0.1);
                    color: var(--dark);
                }

                .modal-content {
                    padding: 0;
                    overflow-y: auto;
                    flex: 1;
                }

                .modal-loading,
                .modal-error {
                    text-align: center;
                    padding: 60px 20px;
                    color: var(--gray);
                }

                .modal-loading i,
                .modal-error i {
                    font-size: 2rem;
                    margin-bottom: 15px;
                    display: block;
                }

                /* Enhanced list styles for modal */
                .modal-appointments-list,
                .modal-messages-list {
                    padding: 20px;
                }

                .modal-appointment-item,
                .modal-message-item {
                    display: flex;
                    align-items: center;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 10px;
                    margin-bottom: 10px;
                    border-left: 4px solid var(--primary);
                    transition: var(--transition);
                }

                .modal-message-item.unread {
                    background: rgba(74, 144, 226, 0.05);
                    border-left-color: var(--secondary);
                }

                .modal-appointment-item:hover,
                .modal-message-item:hover {
                    background: rgba(46, 139, 87, 0.05);
                    transform: translateX(5px);
                }

                /* Animations */
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                    }

                    to {
                        opacity: 1;
                    }
                }

                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .modal-container {
                        width: 95%;
                        margin: 10px;
                    }

                    .modal-header {
                        padding: 15px 20px;
                    }
                }
            </style>

            <script>
                function viewAppointment(appointmentId) {
                    // You can still keep this for individual appointment viewing
                    // or modify it to open a detail modal
                    window.location.href = 'appointments.php?view=' + appointmentId;
                }

                function viewMessage(messageId) {
                    window.location.href = 'messages.php?view=' + messageId;
                }

                // Modal functions
                function openModal(type) {
                    const modal = document.getElementById('modalOverlay');
                    const modalTitle = document.getElementById('modalTitle');
                    const modalContent = document.getElementById('modalContent');



                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
                }

                function closeModal() {
                    const modal = document.getElementById('modalOverlay');
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto'; // Re-enable scrolling
                }

                // Load appointments data into modal
                function loadAppointmentsModal() {
                    const modalContent = document.getElementById('modalContent');

                    // Show loading state
                    modalContent.innerHTML = `
        <div class="modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading appointments...</p>
        </div>
    `;

                    // AJAX call to fetch all appointments
                    fetch(BASE_URL + 'handlers/get_appointments.php')
                        .then(response => response.text())
                        .then(data => {
                            modalContent.innerHTML = data;
                        })
                        .catch(error => {
                            modalContent.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading appointments. Please try again.</p>
                </div>
            `;
                        });
                }

                // Load messages data into modal
                function loadMessagesModal() {
                    const modalContent = document.getElementById('modalContent');

                    // Show loading state
                    modalContent.innerHTML = `
        <div class="modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading messages...</p>
        </div>
    `;

                    // AJAX call to fetch all messages
                    fetch(BASE_URL + 'handlers/get_messages.php')
                        .then(response => response.text())
                        .then(data => {
                            modalContent.innerHTML = data;
                        })
                        .catch(error => {
                            modalContent.innerHTML = `
                <div class="modal-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading messages. Please try again.</p>
                </div>
            `;
                        });
                }

                // Close modal when clicking outside content
                document.getElementById('modalOverlay').addEventListener('click', function (e) {
                    if (e.target.id === 'modalOverlay') {
                        closeModal();
                    }
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                });
            </script>

        <?php else: ?>
            <?php
            // USER DASHBOARD - DATABASE CONNECTION & QUERIES
            require_once 'database.php';
            $database = new Database();
            $pdo = $database->getConnection();

            $user_id = $_SESSION['user_id'];

            // Get client data
            try {
                $client_data = $pdo->prepare("
            SELECT c.*, s.name as staff_name 
            FROM clients c 
            LEFT JOIN staff s ON c.staff_id = s.id 
            WHERE c.user_id = ?
        ");
                $client_data->execute([$user_id]);
                $client = $client_data->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $client = null;
            }

            // Get recommended meal plans
            try {
                $meal_plans = $pdo->prepare("
            SELECT mp.*, s.name as staff_name 
            FROM meal_plans mp 
            LEFT JOIN staff s ON mp.staff_id = s.id 
            WHERE mp.client_id = ? AND mp.status = 'active'
            ORDER BY mp.created_at DESC 
            LIMIT 3
        ");
                $meal_plans->execute([$client ? $client['id'] : 0]);
                $recommended_plans = $meal_plans->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $recommended_plans = [];
            }

            // Get upcoming appointments
            try {
                $upcoming_appointments = $pdo->prepare("
            SELECT a.*, s.name as staff_name 
            FROM appointments a 
            LEFT JOIN staff s ON a.staff_id = s.id 
            WHERE a.client_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'scheduled'
            ORDER BY a.appointment_date ASC 
            LIMIT 3
        ");
                $upcoming_appointments->execute([$client ? $client['id'] : 0]);
                $appointments = $upcoming_appointments->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $appointments = [];
            }

            // Get unread messages (FIXED: Using wellness_messages)
            try {
                $unread_messages = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM wellness_messages wm
            JOIN conversations c ON wm.conversation_id = c.id
            WHERE c.client_id = (SELECT id FROM clients WHERE user_id = ? LIMIT 1) 
            AND wm.sender_type != 'client' 
            AND wm.read_at IS NULL
        ");
                $unread_messages->execute([$user_id]);
                $unread_count = $unread_messages->fetchColumn();
            } catch (Exception $e) {
                $unread_count = 0;
            }

            // Get recent messages
            try {
                $recent_messages = $pdo->prepare("
            SELECT m.id, m.content as message, m.sender_type, m.created_at, m.read_at, s.name as staff_name
            FROM wellness_messages m
            JOIN conversations c ON m.conversation_id = c.id
            LEFT JOIN staff s ON c.dietitian_id = s.id
            WHERE c.client_id = (SELECT id FROM clients WHERE user_id = ? LIMIT 1)
            ORDER BY m.created_at DESC
            LIMIT 3
        ");
                $recent_messages->execute([$user_id]);
                $messages = $recent_messages->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $messages = [];
            }
            ?>

            <!-- ENHANCED USER DASHBOARD CONTENT -->
            <div class="dashboard-grid user-grid">
                <div class="stat-card" onclick="location.href='Nutrition-Calculator.php'">
                    <div class="stat-icon users">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Nutrition Calculator</h3>
                        <p>Calculate Your Needs</p>
                        <div class="metric-trend trend-up">
                            <i class="fas fa-bolt"></i>
                            <span>Personalized Results</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card" onclick="location.href='food-exchange.php'">
                    <div class="stat-icon trackers">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Food Exchange</h3>
                        <p>Smart Food Swaps</p>
                        <div class="metric-trend trend-up">
                            <i class="fas fa-lightbulb"></i>
                            <span>Healthy Alternatives</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card" onclick="location.href='dietary-information.php'">
                    <div class="stat-icon calories" style="background: rgba(46, 139, 87, 0.1); color: var(--primary);">
                        <i class="fas fa-file-medical-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Dietary Info</h3>
                        <p>Nutrition Facts</p>
                        <div class="metric-trend trend-up">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Tracked Metrics</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card" onclick="location.href='user-messages.php'">
                    <div class="stat-icon calories">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="unreadMessages"><?php echo $unread_count; ?></h3>
                        <p>Unread Messages</p>
                        <div class="metric-trend <?php echo $unread_count > 0 ? 'trend-alert' : 'trend-neutral'; ?>">
                            <i class="fas fa-<?php echo $unread_count > 0 ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                            <span><?php echo $unread_count > 0 ? 'New messages' : 'All caught up'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="" onclick="location.href='user-appointments.php'">
                    <div class="stat-icon messages">

                    </div>
                    <div class="stat-info">


                        <div class="metric-trend <?php echo count($appointments) > 0 ? 'trend-up' : 'trend-neutral'; ?>">


                        </div>
                    </div>
                </div>
            <!-- NEW: PERFORMANCE OVERVIEW (WOW FACTOR) -->
            <div class="performance-overview" style="grid-template-columns: 1fr;">
                <!-- Macro Snapshot (SVG Rings) - FIXED FOR MOBILE -->
                <div class="macro-snap-card">
                    <div class="section-header" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin:0;"><i class="fas fa-bullseye"></i> Nutritional Snap <i class="fas fa-info-circle" title="Your progress toward your daily macro goals. Ring fills up as you log food."></i></h2>
                        <button class="btn btn-primary" onclick="generateClinicalReport('.page-container', 'NutriDeq-Weekly-Overview.pdf')" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-file-pdf"></i> Download Report
                        </button>
                    </div>
                    <div class="macro-rings-container">
                        <!-- Protein Ring -->
                        <div class="macro-ring-group">
                            <div class="macro-ring" id="proteinRing">
                                <svg class="macro-svg" viewBox="0 0 100 100" style="overflow: visible;">
                                    <circle class="bg" cx="50" cy="50" r="40" stroke-width="8"></circle>
                                    <circle class="bar" id="p_bar" cx="50" cy="50" r="40" stroke="#4facfe" stroke-width="8" stroke-dasharray="251" stroke-dashoffset="251"></circle>
                                </svg>
                            </div>
                            <div class="macro-label">
                                <div class="macro-name">Protein</div>
                                <div class="macro-val" id="p_val">0 / 0g</div>
                            </div>
                        </div>
                        <!-- Carbs Ring -->
                        <div class="macro-ring-group">
                            <div class="macro-ring" id="carbsRing">
                                <svg class="macro-svg" viewBox="0 0 100 100" style="overflow: visible;">
                                    <circle class="bg" cx="50" cy="50" r="40" stroke-width="8"></circle>
                                    <circle class="bar" id="c_bar" cx="50" cy="50" r="40" stroke="#43e97b" stroke-width="8" stroke-dasharray="251" stroke-dashoffset="251"></circle>
                                </svg>
                            </div>
                            <div class="macro-label">
                                <div class="macro-name">Carbs</div>
                                <div class="macro-val" id="c_val">0 / 0g</div>
                            </div>
                        </div>
                        <!-- Fats Ring -->
                        <div class="macro-ring-group">
                            <div class="macro-ring" id="fatsRing">
                                <svg class="macro-svg" viewBox="0 0 100 100" style="overflow: visible;">
                                    <circle class="bg" cx="50" cy="50" r="40" stroke-width="8"></circle>
                                    <circle class="bar" id="f_bar" cx="50" cy="50" r="40" stroke="#f5576c" stroke-width="8" stroke-dasharray="251" stroke-dashoffset="251"></circle>
                                </svg>
                            </div>
                            <div class="macro-label">
                                <div class="macro-name">Fats</div>
                                <div class="macro-val" id="f_val">0 / 0g</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout for Meal Plans and Communications -->
            <div class="two-column-layout">
                <!-- Recommended Meal Plans Column -->
                <div class="column">
                    <div class="management-section">
                        <div class="section-header">
                            <h2><i class="fas fa-utensils"></i> Recommended Meal by Dietician</h2>
                            <button class="btn btn-primary" onclick="location.href='user-health-tracker.php'">
                                <i class="fas fa-utensils"></i> View
                            </button>
                        </div>

                        <div class="meal-plans-list">
                            <?php if (count($recommended_plans) > 0): ?>
                                <?php foreach ($recommended_plans as $plan): ?>
                                    <div class="meal-plan-card">
                                        <div class="meal-plan-header">
                                            <div class="meal-plan-title"><?php echo htmlspecialchars($plan['plan_name']); ?></div>
                                            <div class="meal-plan-calories"><?php echo $plan['calories']; ?> kcal</div>
                                        </div>
                                        <div class="meal-plan-staff">
                                            <i class="fas fa-user-md"></i>
                                            By: <?php echo htmlspecialchars($plan['staff_name']); ?>
                                        </div>
                                        <div class="meal-plan-description">
                                            <?php
                                            $description = htmlspecialchars($plan['description']);
                                            if (strlen($description) > 100) {
                                                $description = substr($description, 0, 100) . '...';
                                            }
                                            echo $description;
                                            ?>
                                        </div>
                                        <div class="meal-plan-footer">
                                            <div class="meal-plan-date">
                                                Created: <?php echo date('M j, Y', strtotime($plan['created_at'])); ?>
                                            </div>
                                            <button class="btn btn-outline view-plan-btn"
                                                onclick="viewMealPlan(<?php echo $plan['id']; ?>)">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <i class="fas fa-utensils"></i>
                                    <h3>No Meal Plans Yet</h3>
                                    <p>Your dietitian hasn't created any meal plans for you yet.</p>


                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Communications Column -->

                <div class="appointments-list">
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="appointment-avatar">
                                    <?php echo strtoupper(substr($appointment['staff_name'], 0, 2)); ?>
                                </div>
                                <div class="appointment-details">
                                    <div class="appointment-staff"><?php echo htmlspecialchars($appointment['staff_name']); ?></div>
                                    <div class="appointment-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M j, g:i A', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div class="appointment-type">
                                        <span
                                            class="appointment-type-badge"><?php echo ucfirst($appointment['appointment_type']); ?></span>
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <button class="action-btn view-btn" title="View Details"
                                        onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>



                    <?php endif; ?>


                    <!-- Recent Messages -->
                    <div class="management-section">
                        <div class="section-header">
                            <h2><i class="fas fa-comments"></i> Recent Messages</h2>
                            <button class="btn btn-primary" onclick="location.href='user-messages.php'">
                                <i class="fas fa-comment"></i> New Message
                            </button>
                        </div>

                        <div class="messages-list">
                            <?php if (count($messages) > 0): ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message-item <?php echo $message['read_status'] == 0 ? 'unread' : ''; ?>">
                                        <div class="message-avatar">
                                            <?php echo strtoupper(substr($message['staff_name'], 0, 2)); ?>
                                        </div>
                                        <div class="message-details">
                                            <div class="message-header">
                                                <div class="message-staff"><?php echo htmlspecialchars($message['staff_name']); ?>
                                                </div>
                                                <div class="message-time">
                                                    <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div class="message-preview">
                                                <?php
                                                $preview = htmlspecialchars($message['message']);
                                                if (strlen($preview) > 80) {
                                                    $preview = substr($preview, 0, 80) . '...';
                                                }
                                                echo $preview;
                                                ?>
                                            </div>
                                            <?php if ($message['read_at'] === null): ?>
                                                <div class="message-status unread-badge">Unread</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-actions">
                                            <button class="action-btn view-btn" title="View Message"
                                                onclick="viewMessage(<?php echo $message['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <i class="fas fa-comments"></i>
                                    <h3>No Messages</h3>
                                    <p>You have no messages from your dietitian.</p>

                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="management-section">
                <div class="section-header">
                    <h2><i class="fas fa-rocket"></i> Quick Actions</h2>
                </div>

                <div class="quick-actions-grid">
                    <div class="quick-action-card" onclick="location.href='Nutrition-Calculator.php'">
                        <div class="quick-action-icon calculator">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="quick-action-content">
                            <h3>Nutrition Calculator</h3>
                            <p>Calculate your daily calorie needs and macronutrient targets</p>
                        </div>
                    </div>

                    <div class="quick-action-card" onclick="location.href='food-exchange.php'">
                        <div class="quick-action-icon exchange">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="quick-action-content">
                            <h3>Food Exchange List</h3>
                            <p>Discover healthy food alternatives and portion control</p>
                        </div>
                    </div>

                    <div class="quick-action-card" onclick="location.href='user-messages.php'">
                        <div class="quick-action-icon messages">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="quick-action-content">
                            <h3>Message Dietitian</h3>
                            <p>Send questions or updates to your nutrition staff</p>
                        </div>
                    </div>

                    <div class="quick-action-card" onclick="location.href='dietary-information.php'">
                        <div class="quick-action-icon dietary">
                            <i class="fas fa-file-medical-alt"></i>
                        </div>
                        <div class="quick-action-content">
                            <h3>Dietary Information</h3>
                            <p>View and update your nutrition facts and labels</p>
                        </div>
                    </div>



                    <style>
                        /* Enhanced User Dashboard Styles */
                        .user-grid .stat-card {
                            cursor: pointer;
                            transition: transform 0.3s ease, box-shadow 0.3s ease;
                        }

                        .user-grid .stat-card:hover {
                            transform: translateY(-5px);
                            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                        }

                        .meal-plans-list {
                            display: flex;
                            flex-direction: column;
                            gap: 15px;
                        }

                        .meal-plan-card {
                            background: white;
                            border-radius: 15px;
                            padding: 20px;
                            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
                            border-left: 4px solid var(--primary);
                            transition: transform 0.3s ease;
                        }

                        .meal-plan-card:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
                        }

                        .meal-plan-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 10px;
                        }

                        .meal-plan-title {
                            font-weight: 600;
                            color: var(--dark);
                            font-size: 1.1rem;
                        }

                        .meal-plan-calories {
                            background: var(--gradient);
                            color: white;
                            padding: 4px 12px;
                            border-radius: 20px;
                            font-size: 0.8rem;
                            font-weight: 600;
                        }

                        .meal-plan-staff {
                            font-size: 0.9rem;
                            color: var(--gray);
                            margin-bottom: 10px;
                            display: flex;
                            align-items: center;
                            gap: 5px;
                        }

                        .meal-plan-description {
                            font-size: 0.9rem;
                            color: var(--dark);
                            line-height: 1.4;
                            margin-bottom: 15px;
                        }

                        .meal-plan-footer {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        }

                        .meal-plan-date {
                            font-size: 0.8rem;
                            color: var(--gray);
                        }

                        .view-plan-btn {
                            padding: 6px 15px;
                            font-size: 0.8rem;
                        }

                        .quick-actions-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                            gap: 20px;
                            margin-top: 20px;
                        }

                        .quick-action-card {
                            background: white;
                            border-radius: 15px;
                            padding: 25px;
                            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
                            cursor: pointer;
                            transition: all 0.3s ease;
                            border: 2px solid transparent;
                        }

                        .quick-action-card:hover {
                            transform: translateY(-5px);
                            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                            border-color: var(--primary);
                        }

                        .quick-action-icon {
                            width: 60px;
                            height: 60px;
                            border-radius: 15px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin-bottom: 15px;
                            font-size: 1.5rem;
                            color: white;
                        }

                        .quick-action-icon.calculator {
                            background: linear-gradient(135deg, #667eea, #764ba2);
                        }

                        .quick-action-icon.exchange {
                            background: linear-gradient(135deg, #f093fb, #f5576c);
                        }

                        .quick-action-icon.messages {
                            background: linear-gradient(135deg, #4facfe, #00f2fe);
                        }

                        .quick-action-icon.appointments {
                            background: linear-gradient(135deg, #43e97b, #38f9d7);
                        }

                        .quick-action-icon.dietary {
                            background: linear-gradient(135deg, #11998e, #38ef7d);
                        }

                        .quick-action-content h3 {
                            font-size: 1.1rem;
                            margin-bottom: 8px;
                            color: var(--dark);
                        }

                        .quick-action-content p {
                            font-size: 0.85rem;
                            color: var(--gray);
                            line-height: 1.4;
                        }

                        .two-column-layout {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 25px;
                            margin-bottom: 30px;
                        }

                        .column {
                            display: flex;
                            flex-direction: column;
                        }

                        /* Fix button sizes in user dashboard */
                        .user-dashboard .btn,
                        .user-dashboard .btn-primary,
                        .user-dashboard .btn-outline {
                            padding: 8px 16px;
                            font-size: 0.85rem;
                            height: auto;
                            min-height: 36px;
                        }

                        .user-dashboard .btn-primary {
                            padding: 8px 16px;
                            font-size: 0.85rem;
                        }

                        .user-dashboard .btn-outline {
                            padding: 6px 12px;
                            font-size: 0.8rem;
                        }

                        .user-dashboard .action-btn {
                            width: 32px;
                            height: 32px;
                            padding: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        .user-dashboard .action-btn i {
                            font-size: 0.8rem;
                        }

                        /* Fix the "View Details" buttons in meal plans */
                        .user-dashboard .view-plan-btn {
                            padding: 6px 12px;
                            font-size: 0.8rem;
                            min-width: auto;
                        }

                        /* Fix buttons in no-data messages */
                        .user-dashboard .no-data-message .btn {
                            padding: 8px 16px;
                            font-size: 0.85rem;
                            margin-top: 10px;
                        }

                        /* Fix section header buttons */
                        .user-dashboard .section-header .btn {
                            padding: 8px 16px;
                            font-size: 0.85rem;
                            white-space: nowrap;
                        }

                        /* Make sure quick action cards have proper button sizing */
                        .user-dashboard .quick-action-card {
                            padding: 20px;
                        }

                        .user-dashboard .quick-action-icon {
                            width: 50px;
                            height: 50px;
                            font-size: 1.2rem;
                        }

                        .user-dashboard .quick-action-content h3 {
                            font-size: 1rem;
                            margin-bottom: 6px;
                        }

                        .user-dashboard .quick-action-content p {
                            font-size: 0.8rem;
                            line-height: 1.3;
                        }

                        @media (max-width: 768px) {
                            .two-column-layout {
                                grid-template-columns: 1fr;
                            }

                            .quick-actions-grid {
                                grid-template-columns: 1fr;
                            }
                        }
                    </style>
                <?php endif; ?>

            </div>

            <!-- Add Food Dialog Modal (Admin) -->
            <?php if ($user_role === 'admin'): ?>
                <div class="dialog-modal" id="foodDialog">
                    <div class="dialog-content">
                        <div class="dialog-header">
                            <h3>Add Nutritional Food</h3>
                        </div>
                        <div class="dialog-body">
                            <p>Select the food you'd like to add to your nutrition plan:</p>
                            <div class="food-options">
                                <div class="food-option">
                                    <input type="checkbox" id="food1">
                                    <label for="food1">Fresh Fruits Basket</label>
                                </div>
                                <div class="food-option">
                                    <input type="checkbox" id="food2">
                                    <label for="food2">Vegetable Medley</label>
                                </div>
                                <div class="food-option">
                                    <input type="checkbox" id="food3">
                                    <label for="food3">Lean Protein Pack</label>
                                </div>
                            </div>
                        </div>
                        <div class="dialog-footer">
                            <button class="btn btn-outline" id="cancelDialog">Cancel</button>
                            <button class="btn btn-primary" id="confirmDialog">Add Selected</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <script src="scripts/dashboard.js"></script>
            <?php if ($user_role === 'admin'): ?>
                <script src="scripts/admin.js"></script>
            <?php elseif ($user_role === 'staff'): ?>
                <script src="scripts/staff.js"></script>
            <?php endif; ?>

            <!-- Logout Modal Functionality -->
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const logoutBtn = document.getElementById('logoutBtn');
                    const logoutModal = document.getElementById('logoutModal');
                    const cancelLogout = document.getElementById('cancelLogout');
                    const confirmLogout = document.getElementById('confirmLogout');

                    // Open logout modal
                    if (logoutBtn && logoutModal) {
                        logoutBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            logoutModal.classList.add('active');
                        });
                    }

                    // Cancel logout
                    if (cancelLogout && logoutModal) {
                        cancelLogout.addEventListener('click', function () {
                            logoutModal.classList.remove('active');
                        });
                    }

                    // Confirm logout
                    if (confirmLogout) {
                        confirmLogout.addEventListener('click', function () {
                            window.location.href = 'login-logout/logout.php';
                        });
                    }

                    // Close modal when clicking outside
                    if (logoutModal) {
                        logoutModal.addEventListener('click', function (e) {
                            if (e.target === logoutModal) {
                                logoutModal.classList.remove('active');
                            }
                        });
                    }

                    // Close modal with Escape key
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
                            logoutModal.classList.remove('active');
                        }
                    });
                });
            </script>
            <script src="scripts/realtime-dashboard.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="scripts/user-realtime.js" defer></script>
            <!-- Notification Toast Container -->
            <div id="notificationToast" class="notification-toast">
                <div class="toast-icon">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <div class="toast-content">
                    <h4 class="toast-title">New Message</h4>
                    <p class="toast-message" id="toastMessageText">You have a new message from Client.</p>
                </div>
                <button class="toast-close" onclick="hideToast()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Dashboard Polling Script -->
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Only run on staff dashboard
                    if (!document.querySelector('.staff-grid')) return;

                    let knownMessageIds = new Set();

                    // Initialize known IDs from PHP rendered list
                    <?php if (isset($messages) && is_array($messages)): ?>
                        <?php foreach ($messages as $m): ?>
                            knownMessageIds.add(<?php echo $m['id']; ?>);
                        <?php endforeach; ?>
                    <?php endif; ?>

                    const toast = document.getElementById('notificationToast');
                    const audio = new Audio('assets/notification.mp3'); // Ensure this file exists or use a CDN sound if preferred, or silent

                    // Polling Function
                    function checkMessages() {
                        fetch(BASE_URL + 'handlers/get_dashboard_messages.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    updateUnreadBadge(data.unread_count);
                                    updateMessageList(data.messages);
                                    checkForNewMessages(data.messages);
                                }
                            })
                            .catch(err => console.error('Polling error:', err));
                    }

                    function updateUnreadBadge(count) {
                        const badge = document.getElementById('unreadMessages');
                        if (badge) badge.textContent = count;

                        // Update Badge Style
                        const trendIcon = badge.parentElement.querySelector('.metric-trend i');
                        const trendText = badge.parentElement.querySelector('.metric-trend span');
                        const trendContainer = badge.parentElement.querySelector('.metric-trend');

                        if (count > 0) {
                            trendContainer.className = 'metric-trend trend-alert';
                            trendIcon.className = 'fas fa-exclamation-circle';
                            trendText.textContent = 'Needs attention';
                        } else {
                            trendContainer.className = 'metric-trend trend-neutral';
                            trendIcon.className = 'fas fa-check-circle';
                            trendText.textContent = 'All caught up';
                        }
                    }

                    function updateMessageList(messages) {
                        const container = document.querySelector('.messages-list');
                        if (!container) return;

                        if (messages.length === 0) {
                            container.innerHTML = `
                        <div class="no-data-message">
                            <i class="fas fa-comments"></i>
                            <h3>No Messages</h3>
                            <p>You have no messages from clients at this time.</p>
                        </div>`;
                            return;
                        }

                        const html = messages.map(msg => `
                    <div class="message-item ${msg.is_read ? '' : 'unread'}">
                        <div class="message-avatar">
                            ${msg.client_name.substring(0, 2).toUpperCase()}
                        </div>
                        <div class="message-details">
                            <div class="message-header">
                                <div class="message-client">${msg.client_name}</div>
                                <div class="message-time">${msg.time_display}</div>
                            </div>
                            <div class="message-preview">${msg.message}</div>
                            ${!msg.is_read ? '<div class="message-status unread-badge">Unread</div>' : ''}
                        </div>
                        <div class="message-actions">
                            <button class="action-btn view-btn" title="View Message" onclick="location.href='staff-messages.php?client_id=${msg.client_id}'">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                `).join('');

                        container.innerHTML = html;
                    }

                    function checkForNewMessages(messages) {
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
                            showToast(latestSender);
                        }
                    }

                    function showToast(sender) {
                        const text = document.getElementById('toastMessageText');
                        text.textContent = `New message from ${sender}`;
                        toast.classList.add('show');

                        // Play sound (may be blocked by browser policy without interaction)
                        try { audio.play().catch(e => { }); } catch (e) { }

                        setTimeout(() => {
                            hideToast();
                        }, 5000);
                    }

                    window.hideToast = function () {
                        toast.classList.remove('show');
                    };

                    // Poll every 10 seconds
                    setInterval(checkMessages, 10000);
                });
                // --- ADMIN COMMAND CENTER LOGIC ---
                let healthChart, activityChart;

                function initCharts() {
                    const healthCtx = document.getElementById('healthTrendsChart')?.getContext('2d');
                    const activityCtx = document.getElementById('activityRatiosChart')?.getContext('2d');

                    if (healthCtx) {
                        healthChart = new Chart(healthCtx, {
                            type: 'line',
                            data: {
                                labels: [],
                                datasets: [
                                    { label: 'Avg Calories', data: [], borderColor: '#2e8b57', backgroundColor: 'rgba(46, 139, 87, 0.1)', fill: true, tension: 0.4 },
                                    { label: 'Avg Hydration (Glasses)', data: [], borderColor: '#4a90e2', backgroundColor: 'rgba(74, 144, 226, 0.1)', fill: true, tension: 0.4 }
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: { legend: { labels: { color: '#333' } } },
                                scales: {
                                    y: { grid: { color: '#e9ecef' }, ticks: { color: '#666' } },
                                    x: { grid: { color: '#e9ecef' }, ticks: { color: '#666' } }
                                }
                            }
                        });
                    }

                    if (activityCtx) {
                        activityChart = new Chart(activityCtx, {
                            type: 'bar',
                            data: {
                                labels: [],
                                datasets: [
                                    { label: 'User Logins', data: [], backgroundColor: '#f39c12' },
                                    { label: 'Goals Met', data: [], backgroundColor: '#2e8b57' }
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: { legend: { labels: { color: '#333' } } },
                                scales: {
                                    y: { grid: { color: '#e9ecef' }, ticks: { color: '#666' } },
                                    x: { grid: { color: '#e9ecef' }, ticks: { color: '#666' } }
                                }
                            }
                        });
                    }
                }

                function updateDashboard(staffId) {
                    fetch(BASE_URL + `api/admin_stats.php?staff_id=${staffId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) return;

                            // Update Health Chart
                            if (healthChart) {
                                healthChart.data.labels = data.health_trends.labels;
                                healthChart.data.datasets[0].data = data.health_trends.calories;
                                healthChart.data.datasets[1].data = data.health_trends.hydration;
                                healthChart.update('none'); // No flickering
                            }

                            // Update Activity Chart
                            if (activityChart) {
                                activityChart.data.labels = data.activity_ratios.labels;
                                activityChart.data.datasets[0].data = data.activity_ratios.logins;
                                activityChart.data.datasets[1].data = data.activity_ratios.goals_met;
                                activityChart.update('none');
                            }

                            // Update Workload
                            const workloadPercent = (data.workload.total_clients / data.workload.max_capacity) * 100;
                            document.getElementById('workloadProgressBar').style.width = workloadPercent + '%';
                            document.getElementById('workloadText').innerHTML = `Utilizing <strong>${data.workload.total_clients}</strong> / ${data.workload.max_capacity} capacity (${Math.round(workloadPercent)}%)`;

                            if (workloadPercent > 80) document.getElementById('workloadProgressBar').style.background = '#e74c3c';
                            else if (workloadPercent > 50) document.getElementById('workloadProgressBar').style.background = '#f39c12';
                            else document.getElementById('workloadProgressBar').style.background = '#2e8b57';

                            // Update Alerts
                            const alertsList = document.getElementById('alertsList');
                            if (data.alerts.length === 0) {
                                alertsList.innerHTML = '<div style="color: #999; font-size: 0.85rem; padding: 15px; border: 1px dashed #e9ecef; border-radius: 8px; text-align: center; background: #f8f9fa;">No immediate alerts.</div>';
                            } else {
                                alertsList.innerHTML = data.alerts.map(alert => `
                                <div style="background: rgba(231, 76, 60, 0.08); border-left: 3px solid #e74c3c; padding: 12px; border-radius: 6px;">
                                    <div style="font-weight: 500; font-size: 0.9rem; color: #333;">${alert.name}</div>
                                    <div style="font-size: 0.8rem; color: #e74c3c; margin-top: 4px;">No activity for 48h (Last: ${alert.last_log || 'Never'})</div>
                                </div>
                            `).join('');
                            }

                            // Update Staff Engagement Delta Badges
                            const deltaList = document.getElementById('staffDeltaList');
                            const deltaBadge = document.getElementById('deltaSelectedBadge');

                            if (data.staff_list && data.staff_list.length > 0) {
                                deltaList.innerHTML = data.staff_list.map(staff => {
                                    let badgeColor = '#95a5a6'; // Gray for null/0
                                    let badgeText = 'N/A';
                                    let textColor = '#fff';

                                    if (staff.engagement_delta !== null) {
                                        if (staff.engagement_delta > 0) {
                                            badgeColor = '#27ae60';
                                            badgeText = '+' + staff.engagement_delta + '%';
                                        } else if (staff.engagement_delta < 0) {
                                            badgeColor = '#e74c3c';
                                            badgeText = staff.engagement_delta + '%';
                                        } else {
                                            badgeColor = '#95a5a6';
                                            badgeText = '0%';
                                        }
                                    }

                                    return `
                                    <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef;">
                                        <span style="font-weight: 500; color: #333; font-size: 0.9rem;">${staff.name}</span>
                                        <span style="background: ${badgeColor}; color: ${textColor}; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">${badgeText}</span>
                                    </div>
                                `;
                                }).join('');
                            } else {
                                deltaList.innerHTML = '<div style="color: #999; font-size: 0.85rem;">No staff data available.</div>';
                            }

                            // Update selected staff badge in header
                            if (staffId !== 'all' && data.engagement_delta !== null) {
                                let headerColor = data.engagement_delta > 0 ? '#27ae60' : (data.engagement_delta < 0 ? '#e74c3c' : '#95a5a6');
                                let headerText = data.engagement_delta > 0 ? '+' + data.engagement_delta + '%' : data.engagement_delta + '%';
                                deltaBadge.style.display = 'inline-block';
                                deltaBadge.style.background = headerColor;
                                deltaBadge.style.color = '#fff';
                                deltaBadge.textContent = headerText;
                            } else {
                                deltaBadge.style.display = 'none';
                            }
                        });
                }

                document.addEventListener('DOMContentLoaded', () => {
                    const userRole = '<?php echo $user_role; ?>';
                    if (userRole === 'admin') {
                        initCharts();
                        const staffFilter = document.getElementById('staffFilter');
                        if (staffFilter) {
                            staffFilter.addEventListener('change', (e) => updateDashboard(e.target.value));
                            updateDashboard(staffFilter.value);
                        }

                        // Auto-refresh every 60 seconds
                        setInterval(() => {
                            updateDashboard(staffFilter.value);
                        }, 60000);
                    }
                });
            </script>
            <style>
                .notification-toast {
                    position: fixed;
                    bottom: 30px;
                    right: 30px;
                    background: white;
                    border-radius: 12px;
                    padding: 15px 20px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transform: translateY(100px);
                    opacity: 0;
                    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
                    z-index: 9999;
                    border-left: 5px solid var(--accent);
                    max-width: 350px;
                }

                .notification-toast.show {
                    transform: translateY(0);
                    opacity: 1;
                }

                .toast-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: rgba(74, 144, 226, 0.1);
                    color: var(--accent);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                }

                .toast-content h4 {
                    margin: 0 0 5px 0;
                    font-size: 0.95rem;
                    color: var(--dark);
                }

                .toast-content p {
                    margin: 0;
                    font-size: 0.85rem;
                    color: var(--gray);
                }

                .toast-close {
                    background: none;
                    border: none;
                    color: #ccc;
                    cursor: pointer;
                    font-size: 1rem;
                    padding: 5px;
                    transition: color 0.3s;
                }

                .toast-close:hover {
                    color: var(--dark);
                }

                .metric-trend.trend-alert {
                    color: #e74c3c;
                    background: rgba(231, 76, 60, 0.1);
                }

                .metric-trend.trend-alert i {
                    color: #e74c3c;
                }
            </style>
            <script>
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', () => {
                        navigator.serviceWorker.register('service-worker.js')
                            .then(reg => console.log('NutriDeq Native App Enabled', reg.scope))
                            .catch(err => console.error('Native App Connection Failed', err));
                    });
                }
            </script>
</body>

</html>            </div></main></div>

