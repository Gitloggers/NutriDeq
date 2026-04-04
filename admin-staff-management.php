<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$deleted_by = 'admin';
$deleted_by_id = $admin_id;

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

// Include navigation helper
require_once 'navigation.php';

// Get navigation links for current page
$nav_links_array = getNavigationLinks($user_role, 'admin-staff-management.php');

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

// Database connection
require_once 'database.php';
$database = new Database();
$conn = $database->getConnection();

$users = [];
$deleted_users = [];
$stats = [
    'total_users' => 0,
    'staff_count' => 0,
    'admin_count' => 0,
    'regular_count' => 0,
    'deleted_users' => 0
];
$error = '';

try {
    $purge_stmt = $conn->prepare("DELETE FROM deleted_users WHERE deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $purge_stmt->execute();
} catch (PDOException $e) {
}

// Handle form actions for inline operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_user':
                $user_id = $_POST['user_id'];

                try {
                    $conn->beginTransaction();

                    // Get user data before deletion
                    $select_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $select_stmt->execute([$user_id]);
                    $user = $select_stmt->fetch();

                    if ($user) {
                        // Get admin's name for tracking
                        $admin_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                        $admin_stmt->execute([$admin_id]);
                        $admin = $admin_stmt->fetch();
                        $admin_name = $admin['name'] ?? 'Admin';

                        // Insert into deleted_users table with proper tracking
                        $insert_stmt = $conn->prepare("
                            INSERT INTO deleted_users (
                                original_id, deleted_by, deleted_by_user_id, deleted_by_name,
                                name, email, password, role, status,
                                created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");

                        $insert_stmt->execute([
                            $user['id'],
                            $deleted_by, // 'admin'
                            $deleted_by_id, // $admin_id
                            $admin_name, // Admin's actual name
                            $user['name'],
                            $user['email'],
                            $user['password'],
                            $user['role'],
                            $user['status'],
                            $user['created_at'],
                            $user['updated_at']
                        ]);

                        // Delete from users table
                        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                        $delete_stmt->execute([$user_id]);

                        $conn->commit();
                        $_SESSION['success'] = "User moved to delete history successfully";
                    } else {
                        $error = "User not found";
                    }

                    header("Location: admin-staff-management.php");
                    exit();
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $error = "Database error: " . $e->getMessage();
                }
                break;

            case 'restore_user':
                $deleted_user_id = $_POST['deleted_user_id'];

                try {
                    $conn->beginTransaction();

                    // Get deleted user data
                    $select_stmt = $conn->prepare("SELECT * FROM deleted_users WHERE id = ?");
                    $select_stmt->execute([$deleted_user_id]);
                    $user = $select_stmt->fetch();

                    if ($user) {
                        // Check if user with same email already exists
                        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                        $check_stmt->execute([$user['email']]);

                        if ($check_stmt->fetch()) {
                            $_SESSION['error'] = "A user with this email already exists. Cannot restore.";
                        } else {
                            // Insert back into users table
                            $insert_stmt = $conn->prepare("
                                INSERT INTO users (
                                    id, name, email, password, role, status,
                                    created_at, updated_at, restored_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                            ");

                            $insert_stmt->execute([
                                $user['original_id'],
                                $user['name'],
                                $user['email'],
                                $user['password'],
                                $user['role'],
                                $user['status'],
                                $user['created_at'],
                                $user['updated_at']
                            ]);

                            // Remove from deleted_users table
                            $delete_stmt = $conn->prepare("DELETE FROM deleted_users WHERE id = ?");
                            $delete_stmt->execute([$deleted_user_id]);

                            $conn->commit();
                            $_SESSION['success'] = "User restored successfully";
                        }
                    } else {
                        $_SESSION['error'] = "User not found in delete history";
                    }

                    header("Location: admin-staff-management.php?tab=delete_history");
                    exit();
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $_SESSION['error'] = "Database error: " . $e->getMessage();
                    header("Location: admin-staff-management.php?tab=delete_history");
                    exit();
                }
                break;

            case 'update_role':
                $user_id = $_POST['user_id'];
                $new_role = $_POST['role'];
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                $_SESSION['success'] = "User role updated successfully";
                header("Location: admin-staff-management.php");
                exit();
                break;

            case 'toggle_status':
                $user_id = $_POST['user_id'];
                $stmt = $conn->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "User status updated successfully";
                header("Location: admin-staff-management.php");
                exit();
                break;

            case 'edit_user':
                $user_id = $_POST['user_id'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $role = 'staff';
                $status = $_POST['status'];
                $new_password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                try {
                    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
                    $check_stmt->execute([$email, $user_id]);
                    if ($check_stmt->fetch()) {
                        $_SESSION['error'] = "Email already exists";
                        header("Location: admin-staff-management.php");
                        exit();
                    }

                    $role = 'staff';
                    if (!in_array($status, ['active', 'inactive'])) {
                        $status = 'active';
                    }

                    if (!empty($new_password)) {
                        if ($new_password !== $confirm_password) {
                            $_SESSION['error'] = "New password and confirm password do not match";
                            header("Location: admin-staff-management.php");
                            exit();
                        }
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ?, password = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $email, $role, $status, $hashed_password, $user_id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $email, $role, $status, $user_id]);
                    }

                    $_SESSION['success'] = "User updated successfully";
                    header("Location: admin-staff-management.php");
                    exit();
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database error: " . $e->getMessage();
                    header("Location: admin-staff-management.php");
                    exit();
                }
                break;
            case 'permanent_delete':
                $deleted_user_id = $_POST['deleted_user_id'];
                try {
                    $stmt = $conn->prepare("SELECT deleted_at FROM deleted_users WHERE id = ?");
                    $stmt->execute([$deleted_user_id]);
                    $row = $stmt->fetch();
                    if (!$row) {
                        $_SESSION['error'] = "Staff not found in delete history";
                        header("Location: admin-staff-management.php?tab=delete_history");
                        exit();
                    }
                    $deleted_at = strtotime($row['deleted_at']);
                    $diff_days = floor((time() - $deleted_at) / 86400);
                    if ($diff_days < 10) {
                        $_SESSION['error'] = "Permanent delete allowed only after 10 days";
                        header("Location: admin-staff-management.php?tab=delete_history");
                        exit();
                    }
                    $del = $conn->prepare("DELETE FROM deleted_users WHERE id = ?");
                    $del->execute([$deleted_user_id]);
                    $_SESSION['success'] = "Staff permanently deleted";
                    header("Location: admin-staff-management.php?tab=delete_history");
                    exit();
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Database error: " . $e->getMessage();
                    header("Location: admin-staff-management.php?tab=delete_history");
                    exit();
                }
                break;
        }
    }
}

// Handle GET parameters for tabs
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active_users';

try {
    // Get user statistics
    $stats_sql = "SELECT 
        COUNT(*) as total_users,
        SUM(role = 'admin') as admin_count,
        SUM(role = 'staff') as staff_count,
        SUM(role = 'regular') as regular_count,
        SUM(status = 'active') as active_users
        FROM users";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute();
    $stats_data = $stats_stmt->fetch();
    $stats['total_users'] = $stats_data['total_users'] ?? 0;
    $stats['staff_count'] = $stats_data['staff_count'] ?? 0;
    $stats['admin_count'] = $stats_data['admin_count'] ?? 0;
    $stats['regular_count'] = $stats_data['regular_count'] ?? 0;

    // Get deleted users count
    $deleted_stats_stmt = $conn->prepare("SELECT COUNT(*) as deleted_count FROM deleted_users");
    $deleted_stats_stmt->execute();
    $deleted_stats = $deleted_stats_stmt->fetch();
    $stats['deleted_users'] = $deleted_stats['deleted_count'] ?? 0;

    // Get all active users
    $users_sql = "SELECT * FROM users WHERE role = 'staff' ORDER BY created_at DESC";
    $users_stmt = $conn->prepare($users_sql);
    $users_stmt->execute();
    $users = $users_stmt->fetchAll();

    // Get all deleted users
    $deleted_users_sql = "SELECT * FROM deleted_users WHERE role = 'staff' ORDER BY deleted_at DESC";
    $deleted_users_stmt = $conn->prepare($deleted_users_sql);
    $deleted_users_stmt->execute();
    $deleted_users = $deleted_users_stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>NutriDeq - User Management</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- External CSS Files -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/staff-management.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <script src="scripts/dashboard.js" defer></script>
    <style>
        .tabs {
            display: flex;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            color: #3498db;
        }

        .tab-btn.active {
            color: #3498db;
            border-bottom-color: #3498db;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .delete-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .delete-history-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        .delete-history-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .delete-history-table tr:hover {
            background: #f9f9f9;
        }

        .deleted-by {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .deleted-by-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }

        .restore-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }

        .restore-btn:hover {
            background: #219653;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .stat-card.stat-deleted {
            background: #f5f7fa;
            color: #333;
        }

        .stat-deleted .stat-icon {
            background: #95a5a6;
            color: white;
        }
    </style>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                    <div class="page-title">
                        <h1>Staff Management</h1>
                        <p>Manage staff accounts and permissions</p>
                    </div>

                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search users by name, email..."
                                class="global-search" data-target=".user-table tbody tr">
                        </div>
                    </div>
                </div>

                <!-- Display success messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Display error messages -->
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['add_user_errors']) && is_array($_SESSION['add_user_errors']) && count($_SESSION['add_user_errors']) > 0): ?>
                    <div class="error-message">
                        <?php foreach ($_SESSION['add_user_errors'] as $e) {
                            echo htmlspecialchars($e) . '<br>';
                        } ?>
                    </div>
                    <?php unset($_SESSION['add_user_errors']); ?>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="dashboard-grid admin-grid">
                    <div class="stat-card">
                        <div class="stat-icon staff">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['staff_count'] ?? 0; ?></h3>
                            <p>Staff Members</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn <?php echo $current_tab === 'active_users' ? 'active' : ''; ?>"
                        onclick="switchTab('active_users')">
                        Active Users
                    </button>
                    <button class="tab-btn <?php echo $current_tab === 'delete_history' ? 'active' : ''; ?>"
                        onclick="switchTab('delete_history')">
                        Delete History
                    </button>
                </div>

                <!-- User Management Section -->
                <div class="management-section">
                    <!-- Active Users Tab -->
                    <div id="active-users-tab"
                        class="tab-content <?php echo $current_tab === 'active_users' ? 'active' : ''; ?>">
                        <div class="section-header">
                            <h2><i class="fas fa-users-cog"></i> User Accounts</h2>
                            <button class="btn btn-primary" id="addUserBtn">
                                <i class="fas fa-user-plus"></i> Add New User
                            </button>
                        </div>

                        <div class="table-container table-responsive">
                            <table class="user-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td data-label="User">
                                                <div class="user-info-cell">
                                                    <div class="user-avatar-small">
                                                        <?php echo getInitials($user['name']); ?>
                                                    </div>
                                                    <div class="user-details">
                                                        <div class="user-name">
                                                            <?php echo htmlspecialchars($user['name']); ?></div>
                                                        <div class="user-id">
                                                            #USR-<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Email" class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td data-label="Role">
                                                <span class="role-badge role-staff">Staff</span>
                                            </td>
                                            <td data-label="Join Date" class="join-date">
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td data-label="Status">
                                                <form method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit"
                                                        class="status-toggle status-<?php echo $user['status']; ?>">
                                                        <?php echo ucfirst($user['status']); ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td data-label="Actions">
                                                <div class="user-actions">
                                                    <button type="button" class="action-btn edit-btn"
                                                        data-user-id="<?php echo $user['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                        data-role="<?php echo $user['role']; ?>"
                                                        data-status="<?php echo $user['status']; ?>" title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id"
                                                            value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="action-btn delete-btn"
                                                            title="Delete User"
                                                            onclick="return confirm('Are you sure you want to delete this user? The user will be moved to delete history.')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Delete History Tab -->
                    <div id="delete-history-tab"
                        class="tab-content <?php echo $current_tab === 'delete_history' ? 'active' : ''; ?>">
                        <div class="section-header">
                            <h2><i class="fas fa-history"></i> Delete History</h2>
                            <p>All deleted users by administrators. You can restore them here.</p>
                        </div>

                        <?php if (empty($deleted_users)): ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <h3>No deleted users found</h3>
                                <p>Deleted users will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container table-responsive">
                                <table class="delete-history-table">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Deleted By</th>
                                            <th>Deleted Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deleted_users as $deleted_user):
                                            $deleter_name = $deleted_user['deleted_by_name'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info-cell">
                                                        <div class="user-avatar-small">
                                                            <?php echo getInitials($deleted_user['name']); ?>
                                                        </div>
                                                        <div class="user-details">
                                                            <div class="user-name">
                                                                <?php echo htmlspecialchars($deleted_user['name']); ?>
                                                            </div>
                                                            <div class="user-id">
                                                                #USR-<?php echo str_pad($deleted_user['original_id'], 3, '0', STR_PAD_LEFT); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($deleted_user['email']); ?></td>
                                                <td>
                                                    <span class="role-badge role-<?php echo $deleted_user['role']; ?>">
                                                        <?php echo ucfirst($deleted_user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-toggle status-<?php echo $deleted_user['status']; ?>">
                                                        <?php echo ucfirst($deleted_user['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="deleted-by">
                                                        <div class="deleted-by-avatar"><?php echo getInitials($deleter_name); ?>
                                                        </div>
                                                        <span><?php echo htmlspecialchars($deleter_name); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo date('M j, Y H:i', strtotime($deleted_user['deleted_at'])); ?>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="restore_user">
                                                        <input type="hidden" name="deleted_user_id"
                                                            value="<?php echo $deleted_user['id']; ?>">
                                                        <button type="submit" class="restore-btn"
                                                            onclick="return confirmRestore()">
                                                            <i class="fas fa-undo"></i> Restore
                                                        </button>
                                                    </form>
                                                    <?php
                                                    $age_days = floor((time() - strtotime($deleted_user['deleted_at'])) / 86400);
                                                    $can_perm_delete = $age_days >= 10;
                                                    ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="permanent_delete">
                                                        <input type="hidden" name="deleted_user_id"
                                                            value="<?php echo $deleted_user['id']; ?>">
                                                        <button type="submit" class="restore-btn perm-delete-btn"
                                                            data-deleted-at="<?php echo $deleted_user['deleted_at']; ?>"
                                                            data-min-days="10" style="background:#e74c3c" <?php echo $can_perm_delete ? '' : 'disabled'; ?>
                                                            onclick="return confirm('Permanently delete this staff? This cannot be undone.')">
                                                            <i class="fas fa-trash"></i> Delete Permanently
                                                        </button>
                                                        <span class="perm-countdown"
                                                            data-deleted-at="<?php echo $deleted_user['deleted_at']; ?>"
                                                            data-min-days="10" style="margin-left:8px;color:#888"></span>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Role Permissions Section -->
                <div class="management-section">
                    <div class="section-header">
                        <h2><i class="fas fa-user-lock"></i> Role Permissions</h2>
                    </div>

                    <div class="permissions-grid">
                        <div class="permission-card permission-staff">
                            <div class="permission-header">
                                <div class="permission-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="permission-title">Staff</div>
                            </div>
                            <ul class="permission-list">
                                <li><i class="fas fa-check"></i> All Regular User Features</li>
                                <li><i class="fas fa-check"></i> Client Management</li>
                                <li><i class="fas fa-check"></i> Health Progress Tracking</li>
                                <li><i class="fas fa-times"></i> System Settings</li>
                                <li><i class="fas fa-times"></i> User Role Management</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add User Modal -->
            <div class="modal" id="addUserModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Add New User</h2>
                        <button class="close-btn" onclick="closeModal()">&times;</button>
                    </div>
                    <form method="POST" action="process_admin_add_user.php" id="addUserForm">
                        <input type="hidden" name="origin" value="admin-staff-management.php">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" required
                                placeholder="Enter full name"
                                value="<?php echo isset($_SESSION['add_user_data']['name']) ? htmlspecialchars($_SESSION['add_user_data']['name']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required
                                placeholder="Enter email address"
                                value="<?php echo isset($_SESSION['add_user_data']['email']) ? htmlspecialchars($_SESSION['add_user_data']['email']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="password" id="password" name="password" class="form-control" required
                                    placeholder="Enter password" style="flex:1;">
                                <button type="button" class="btn btn-outline" id="toggle_password"
                                    title="Show/Hide Password"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="password" id="confirm_password" name="confirm_password"
                                    class="form-control" required placeholder="Confirm password" style="flex:1;">
                                <button type="button" class="btn btn-outline" id="toggle_confirm_password"
                                    title="Show/Hide Password"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="staff" selected>Staff Member</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal" id="editUserModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Edit User</h2>
                        <button class="close-btn" onclick="closeEditModal()">&times;</button>
                    </div>
                    <form method="POST" id="editUserForm">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="form-group">
                            <label for="edit_name">Full Name</label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_role">Role</label>
                            <select id="edit_role" name="role" class="form-control" required>
                                <option value="staff" selected>Staff Member</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select id="edit_status" name="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">New Password (optional)</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="password" id="edit_password" name="password" class="form-control"
                                    placeholder="Leave blank to keep current password" style="flex:1;">
                                <button type="button" class="btn btn-outline" id="toggle_edit_password"
                                    title="Show/Hide Password"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_confirm_password">Confirm New Password</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="password" id="edit_confirm_password" name="confirm_password"
                                    class="form-control" placeholder="Re-enter new password" style="flex:1;">
                                <button type="button" class="btn btn-outline" id="toggle_edit_confirm_password"
                                    title="Show/Hide Password"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const togglePassword = document.getElementById('toggle_password');
                    if (togglePassword) {
                        togglePassword.addEventListener('click', function () {
                            const input = document.getElementById('password');
                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                            input.setAttribute('type', type);
                            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                        });
                    }
                    const toggleConfirmPassword = document.getElementById('toggle_confirm_password');
                    if (toggleConfirmPassword) {
                        toggleConfirmPassword.addEventListener('click', function () {
                            const input = document.getElementById('confirm_password');
                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                            input.setAttribute('type', type);
                            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                        });
                    }
                    const toggleEditPassword = document.getElementById('toggle_edit_password');
                    if (toggleEditPassword) {
                        toggleEditPassword.addEventListener('click', function () {
                            const input = document.getElementById('edit_password');
                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                            input.setAttribute('type', type);
                            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                        });
                    }
                    const toggleEditConfirmPassword = document.getElementById('toggle_edit_confirm_password');
                    if (toggleEditConfirmPassword) {
                        toggleEditConfirmPassword.addEventListener('click', function () {
                            const input = document.getElementById('edit_confirm_password');
                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                            input.setAttribute('type', type);
                            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                        });
                    }

                    if (<?php echo isset($_SESSION['add_user_data']) ? 'true' : 'false'; ?>) {
                        document.getElementById('addUserModal').style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                        <?php unset($_SESSION['add_user_data']); ?>
                    }
                    // Add User Button
                    const addUserBtn = document.getElementById('addUserBtn');
                    if (addUserBtn) {
                        addUserBtn.addEventListener('click', function () {
                            document.getElementById('addUserModal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        });
                    }

                    // Tab switching functionality
                    const tabButtons = document.querySelectorAll('.tab-btn');
                    tabButtons.forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            // Remove active class from all tabs
                            tabButtons.forEach(b => b.classList.remove('active'));
                            // Add active class to clicked tab
                            this.classList.add('active');
                        });
                    });

                    // Handle search functionality
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.addEventListener('input', function (e) {
                            const searchTerm = e.target.value.toLowerCase();

                            // Get the currently active tab
                            const currentTab = document.querySelector('.tab-content.active');
                            if (currentTab) {
                                // Search in the active tab only
                                const rows = currentTab.querySelectorAll('tbody tr');

                                rows.forEach(row => {
                                    const text = row.textContent.toLowerCase();
                                    if (text.includes(searchTerm)) {
                                        row.style.display = '';
                                    } else {
                                        row.style.display = 'none';
                                    }
                                });
                            }
                        });
                    }

                    // Logout Modal Functionality
                    const logoutBtn = document.getElementById('logoutBtn');
                    const logoutModal = document.getElementById('logoutModal');
                    const cancelLogout = document.getElementById('cancelLogout');
                    const confirmLogout = document.getElementById('confirmLogout');

                    // Open logout modal
                    if (logoutBtn) {
                        logoutBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            logoutModal.classList.add('active');
                        });
                    }

                    // Cancel logout
                    if (cancelLogout) {
                        cancelLogout.addEventListener('click', function () {
                            logoutModal.classList.remove('active');
                        });
                    }

                    // Confirm logout
                    if (confirmLogout) {
                        confirmLogout.addEventListener('click', function () {
                            // Redirect to logout script
                            window.location.href = 'logout.php';
                        });
                    }

                    const editButtons = document.querySelectorAll('.edit-btn');
                    const editModal = document.getElementById('editUserModal');
                    const editForm = document.getElementById('editUserForm');
                    if (editButtons && editModal && editForm) {
                        editButtons.forEach(btn => {
                            btn.addEventListener('click', function (e) {
                                e.preventDefault();
                                const id = this.getAttribute('data-user-id');
                                const name = this.getAttribute('data-name');
                                const email = this.getAttribute('data-email');
                                const role = this.getAttribute('data-role');
                                const status = this.getAttribute('data-status');
                                
                                document.getElementById('edit_user_id').value = id;
                                document.getElementById('edit_name').value = name;
                                document.getElementById('edit_email').value = email;
                                document.getElementById('edit_role').value = role || 'staff';
                                document.getElementById('edit_status').value = status || 'active';
                                
                                document.getElementById('edit_password').value = '';
                                document.getElementById('edit_confirm_password').value = '';
                                
                                editModal.style.display = 'flex';
                                document.body.style.overflow = 'hidden';
                            });
                        });
                    }



                    function formatTimeLeft(seconds) {
                        const d = Math.floor(seconds / 86400);
                        seconds -= d * 86400;
                        const h = Math.floor(seconds / 3600);
                        seconds -= h * 3600;
                        const m = Math.floor(seconds / 60);
                        const s = Math.floor(seconds - m * 60);
                        return `${d}d ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                    }

                    function setupPermDeleteCountdown() {
                        const buttons = document.querySelectorAll('.perm-delete-btn');
                        buttons.forEach(btn => {
                            const deletedAtStr = btn.getAttribute('data-deleted-at');
                            const minDays = parseInt(btn.getAttribute('data-min-days') || '10', 10);
                            const countdownSpan = btn.parentElement.querySelector('.perm-countdown');
                            if (!deletedAtStr || !countdownSpan) return;
                            const deletedMs = Date.parse(deletedAtStr.replace(' ', 'T'));
                            function update() {
                                const elapsed = Date.now() - deletedMs;
                                const minMs = minDays * 86400 * 1000;
                                const leftMs = Math.max(0, minMs - elapsed);
                                const leftSec = Math.floor(leftMs / 1000);
                                if (leftMs <= 0) {
                                    btn.removeAttribute('disabled');
                                    countdownSpan.textContent = '';
                                    return true;
                                } else {
                                    btn.setAttribute('disabled', 'disabled');
                                    countdownSpan.textContent = `Available in ${formatTimeLeft(leftSec)}`;
                                    return false;
                                }
                            }
                            update();
                            const iv = setInterval(() => { if (update()) clearInterval(iv); }, 1000);
                        });
                    }

                    setupPermDeleteCountdown();
                });

                // Tab switching function
                function switchTab(tabName) {
                    window.location.href = `admin-staff-management.php?tab=${tabName}`;
                }

                function closeModal() {
                    const addUserModal = document.getElementById('addUserModal');
                    if (addUserModal) {
                        addUserModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        document.getElementById('addUserForm').reset();
                    }
                }

                function closeEditModal() {
                    const editUserModal = document.getElementById('editUserModal');
                    if (editUserModal) {
                        editUserModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        document.getElementById('editUserForm').reset();
                    }
                }

                function confirmRestore() {
                    return confirm('Are you sure you want to restore this user? All user data and privileges will be restored.');
                }

                // Close modal when clicking outside
                window.addEventListener('click', function (event) {
                    const addUserModal = document.getElementById('addUserModal');
                    if (event.target === addUserModal) {
                        closeModal();
                    }
                    const editUserModal = document.getElementById('editUserModal');
                    if (event.target === editUserModal) {
                        closeEditModal();
                    }
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeModal();
                        closeEditModal();
                    }
                });

                // Close logout modal when clicking outside
                window.addEventListener('click', function (event) {
                    const logoutModal = document.getElementById('logoutModal');
                    if (event.target === logoutModal) {
                        logoutModal.classList.remove('active');
                    }
                });

        </div> <!-- end main-content -->
    </div> <!-- end main-layout -->
</body>

</html>
