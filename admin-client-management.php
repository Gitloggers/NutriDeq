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

require_once 'navigation.php';
$nav_links_array = getNavigationLinks($user_role, 'admin-client-management.php');

function getInitials($name)
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
$user_initials = getInitials($user_name);

require_once 'database.php';
$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

try {
    $purge_clients_stmt = $conn->prepare("DELETE FROM deleted_clients WHERE deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $purge_clients_stmt->execute();
} catch (PDOException $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_client':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone'] ?? '');
                $age = $_POST['age'] ?? null;
                $gender = $_POST['gender'] ?? '';
                $address = trim($_POST['address'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $state = trim($_POST['state'] ?? '');
                $zip_code = trim($_POST['zip_code'] ?? '');
                $waist_circumference = $_POST['waist_circumference'] ?? null;
                $hip_circumference = $_POST['hip_circumference'] ?? null;
                $staff_id = !empty($_POST['staff_id']) ? (int) $_POST['staff_id'] : null;
                try {
                    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['error'] = 'Please provide valid name and email';
                        header('Location: admin-client-management.php?tab=active_clients');
                        exit();
                    }
                    $account_password = $_POST['account_password'] ?? '';
                    $account_confirm_password = $_POST['account_confirm_password'] ?? '';
                    $desired_password = '';
                    if (!empty($account_password)) {
                        if ($account_password !== $account_confirm_password) {
                            $_SESSION['error'] = 'Account password and confirm password do not match';
                            header('Location: admin-client-management.php?tab=active_clients');
                            exit();
                        }
                        $desired_password = $account_password;
                    }
                    $check = $conn->prepare('SELECT id FROM clients WHERE email = ?');
                    $check->execute([$email]);
                    if ($check->fetch()) {
                        $_SESSION['error'] = 'Client with this email already exists';
                        header('Location: admin-client-management.php?tab=active_clients');
                        exit();
                    }
                    if (!is_null($staff_id)) {
                        $staff_chk = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'staff' AND status = 'active'");
                        $staff_chk->execute([$staff_id]);
                        if (!$staff_chk->fetch()) {
                            $staff_id = null;
                        }
                    }
                    $stmt = $conn->prepare("INSERT INTO clients (user_id, staff_id, name, email, phone, address, city, state, zip_code, age, gender, waist_circumference, hip_circumference, status, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())");
                    $stmt->execute([$staff_id, $name, $email, $phone, $address, $city, $state, $zip_code, $age, $gender, $waist_circumference, $hip_circumference]);
                    $client_id_new = $conn->lastInsertId();
                    $user_check = $conn->prepare('SELECT id FROM users WHERE email = ?');
                    $user_check->execute([$email]);
                    $existing_user = $user_check->fetch();
                    $linked_user_id = null;
                    if ($existing_user && isset($existing_user['id'])) {
                        $linked_user_id = (int) $existing_user['id'];
                        if (!empty($desired_password)) {
                            $hashed = password_hash($desired_password, PASSWORD_DEFAULT);
                            $upd_user = $conn->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
                            $upd_user->execute([$hashed, $linked_user_id]);
                        }
                    } else {
                        $to_hash = !empty($desired_password) ? $desired_password : bin2hex(random_bytes(8));
                        $hashed = password_hash($to_hash, PASSWORD_DEFAULT);
                        $ins_user = $conn->prepare('INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
                        $ins_user->execute([$name, $email, $hashed, 'regular', 'active']);
                        $linked_user_id = (int) $conn->lastInsertId();
                    }
                    if (!empty($linked_user_id)) {
                        $upd = $conn->prepare('UPDATE clients SET user_id = ? WHERE id = ?');
                        $upd->execute([$linked_user_id, $client_id_new]);
                    }
                    $_SESSION['success'] = 'Client added successfully';
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                }
                break;

            case 'update_client':
                $client_id = $_POST['client_id'];
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $age = $_POST['age'] ?? null;
                $gender = $_POST['gender'] ?? '';
                $address = trim($_POST['address'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $state = trim($_POST['state'] ?? '');
                $zip_code = trim($_POST['zip_code'] ?? '');
                $waist_circumference = $_POST['waist_circumference'] ?? null;
                $hip_circumference = $_POST['hip_circumference'] ?? null;
                $new_password = $_POST['new_password'] ?? '';
                $confirm_new_password = $_POST['confirm_new_password'] ?? '';
                $staff_id = !empty($_POST['staff_id']) ? (int) $_POST['staff_id'] : null;
                try {
                    $conn->beginTransaction();
                    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $conn->commit();
                        $_SESSION['error'] = 'Please provide valid name and email';
                        header('Location: admin-client-management.php?tab=active_clients');
                        exit();
                    }
                    $dupe = $conn->prepare('SELECT id FROM clients WHERE email = ? AND id != ?');
                    $dupe->execute([$email, $client_id]);
                    if ($dupe->fetch()) {
                        $conn->commit();
                        $_SESSION['error'] = 'Client with this email already exists';
                        header('Location: admin-client-management.php?tab=active_clients');
                        exit();
                    }
                    if (!is_null($staff_id)) {
                        $staff_chk = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'staff'");
                        $staff_chk->execute([$staff_id]);
                        if (!$staff_chk->fetch()) {
                            $staff_id = null;
                        }
                    }
                    $stmt = $conn->prepare('UPDATE clients SET name = ?, email = ?, phone = ?, age = ?, gender = ?, address = ?, city = ?, state = ?, zip_code = ?, waist_circumference = ?, hip_circumference = ?, staff_id = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$name, $email, $phone, $age, $gender, $address, $city, $state, $zip_code, $waist_circumference, $hip_circumference, $staff_id, $client_id]);

                    $selClient = $conn->prepare('SELECT user_id FROM clients WHERE id = ?');
                    $selClient->execute([$client_id]);
                    $clientRow = $selClient->fetch();
                    $linkedUserId = $clientRow['user_id'] ?? null;
                    if ($linkedUserId) {
                        $dupUser = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
                        $dupUser->execute([$email, $linkedUserId]);
                        if (!$dupUser->fetch()) {
                            $updUser = $conn->prepare('UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?');
                            $updUser->execute([$name, $email, $linkedUserId]);
                        }
                    } else {
                        $userByEmail = $conn->prepare('SELECT id FROM users WHERE email = ?');
                        $userByEmail->execute([$email]);
                        $u = $userByEmail->fetch();
                        if ($u && isset($u['id'])) {
                            $linkStmt = $conn->prepare('UPDATE clients SET user_id = ? WHERE id = ?');
                            $linkStmt->execute([(int) $u['id'], $client_id]);
                            $updUser = $conn->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?');
                            $updUser->execute([$name, (int) $u['id']]);
                        } else {
                            $tmpPassword = bin2hex(random_bytes(8));
                            $hashedTmp = password_hash($tmpPassword, PASSWORD_DEFAULT);
                            $insUser = $conn->prepare('INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
                            $insUser->execute([$name, $email, $hashedTmp, 'regular', 'active']);
                            $newUid = (int) $conn->lastInsertId();
                            $linkStmt = $conn->prepare('UPDATE clients SET user_id = ? WHERE id = ?');
                            $linkStmt->execute([$newUid, $client_id]);
                        }
                    }

                    if (!empty($new_password)) {
                        if ($new_password !== $confirm_new_password) {
                            $conn->commit();
                            $_SESSION['error'] = 'New password and confirm password do not match';
                            header('Location: admin-client-management.php?tab=active_clients');
                            exit();
                        }
                        $sel = $conn->prepare('SELECT user_id, email FROM clients WHERE id = ?');
                        $sel->execute([$client_id]);
                        $cl = $sel->fetch();
                        $target_user_id = $cl['user_id'] ?? null;
                        if ($target_user_id) {
                            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                            $up = $conn->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
                            $up->execute([$hashed, $target_user_id]);
                        } else if (!empty($cl['email'])) {
                            $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
                            $check->execute([$cl['email']]);
                            $u = $check->fetch();
                            if ($u && isset($u['id'])) {
                                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                                $up = $conn->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
                                $up->execute([$hashed, $u['id']]);
                            } else {
                                $_SESSION['error'] = 'Client has no linked user account to update password';
                                $conn->commit();
                                header('Location: admin-client-management.php?tab=active_clients');
                                exit();
                            }
                        } else {
                            $_SESSION['error'] = 'Client has no linked user account to update password';
                            $conn->commit();
                            header('Location: admin-client-management.php?tab=active_clients');
                            exit();
                        }
                    }

                    $conn->commit();
                    $_SESSION['success'] = 'Client updated successfully';
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                }
                break;

            case 'toggle_client_status':
                $client_id = $_POST['client_id'];
                try {
                    $stmt = $conn->prepare("UPDATE clients SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
                    $stmt->execute([$client_id]);
                    $sel = $conn->prepare("SELECT status, user_id FROM clients WHERE id = ?");
                    $sel->execute([$client_id]);
                    $c = $sel->fetch();
                    if ($c && !empty($c['user_id'])) {
                        $updUserStatus = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                        $updUserStatus->execute([$c['status'], $c['user_id']]);
                    }
                    $_SESSION['success'] = 'Client status updated successfully';
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                }
                break;

            case 'delete_client':
                $client_id = $_POST['client_id'];
                try {
                    $conn->beginTransaction();
                    $sel = $conn->prepare('SELECT * FROM clients WHERE id = ?');
                    $sel->execute([$client_id]);
                    $client = $sel->fetch();
                    if ($client) {
                        if (!empty($client['user_id'])) {
                            try {
                                $updUser = $conn->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                                $updUser->execute([$client['user_id']]);
                            } catch (PDOException $e) {
                            }
                        }
                        $ins = $conn->prepare("INSERT INTO deleted_clients (original_id, user_id, staff_id, deleted_by, deleted_by_user_id, deleted_by_name, name, email, phone, address, city, state, zip_code, age, gender, weight, height, waist_circumference, hip_circumference, health_conditions, dietary_restrictions, goals, notes, status, created_at, updated_at, deleted_at) VALUES (?, ?, ?, 'admin', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $ins->execute([
                            $client['id'],
                            $client['user_id'],
                            $client['staff_id'],
                            $admin_id,
                            $user_name,
                            $client['name'],
                            $client['email'],
                            $client['phone'],
                            $client['address'],
                            $client['city'],
                            $client['state'],
                            $client['zip_code'],
                            $client['age'],
                            $client['gender'],
                            $client['weight'] ?? null,
                            $client['height'] ?? null,
                            $client['waist_circumference'] ?? null,
                            $client['hip_circumference'] ?? null,
                            $client['health_conditions'] ?? null,
                            $client['dietary_restrictions'] ?? null,
                            $client['goals'] ?? null,
                            $client['notes'] ?? null,
                            $client['status'],
                            $client['created_at'] ?? null,
                            $client['updated_at'] ?? null
                        ]);
                        try {
                            $del = $conn->prepare('DELETE FROM clients WHERE id = ?');
                            $del->execute([$client_id]);
                        } catch (PDOException $delErr) {
                            // Fallback: mark inactive if direct delete is blocked by constraints
                            $fallback = $conn->prepare("UPDATE clients SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                            $fallback->execute([$client_id]);
                        }
                        $conn->commit();
                        $_SESSION['success'] = 'Client moved to delete history successfully';
                    } else {
                        $_SESSION['error'] = 'Client not found';
                    }
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                    header('Location: admin-client-management.php?tab=active_clients');
                    exit();
                }
                break;

            case 'restore_client':
                $deleted_client_id = $_POST['deleted_client_id'];
                try {
                    $conn->beginTransaction();
                    $sel = $conn->prepare('SELECT * FROM deleted_clients WHERE id = ?');
                    $sel->execute([$deleted_client_id]);
                    $c = $sel->fetch();
                    if ($c) {
                        $exists_stmt = $conn->prepare('SELECT id FROM clients WHERE id = ?');
                        $exists_stmt->execute([$c['original_id']]);
                        $exists = $exists_stmt->fetch();
                        if ($exists) {
                            $upd = $conn->prepare("UPDATE clients SET user_id = ?, staff_id = ?, name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, age = ?, gender = ?, weight = ?, height = ?, waist_circumference = ?, hip_circumference = ?, health_conditions = ?, dietary_restrictions = ?, goals = ?, notes = ?, status = ?, updated_at = NOW() WHERE id = ?");
                            $upd->execute([$c['user_id'], $c['staff_id'], $c['name'], $c['email'], $c['phone'], $c['address'], $c['city'], $c['state'], $c['zip_code'], $c['age'], $c['gender'], $c['weight'], $c['height'], $c['waist_circumference'], $c['hip_circumference'], $c['health_conditions'], $c['dietary_restrictions'], $c['goals'], $c['notes'], $c['status'], $c['original_id']]);
                        } else {
                            $ins = $conn->prepare("INSERT INTO clients (id, user_id, staff_id, name, email, phone, address, city, state, zip_code, age, gender, weight, height, waist_circumference, hip_circumference, health_conditions, dietary_restrictions, goals, notes, status, created_at, updated_at, restored_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                            $ins->execute([$c['original_id'], $c['user_id'], $c['staff_id'], $c['name'], $c['email'], $c['phone'], $c['address'], $c['city'], $c['state'], $c['zip_code'], $c['age'], $c['gender'], $c['weight'], $c['height'], $c['waist_circumference'], $c['hip_circumference'], $c['health_conditions'], $c['dietary_restrictions'], $c['goals'], $c['notes'], $c['status'], $c['created_at'], $c['updated_at']]);
                        }
                        if (!empty($c['user_id'])) {
                            try {
                                $updUser = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
                                $updUser->execute([$c['status'], $c['user_id']]);
                            } catch (PDOException $e) {
                            }
                        }
                        $del = $conn->prepare('DELETE FROM deleted_clients WHERE id = ?');
                        $del->execute([$deleted_client_id]);
                        $conn->commit();
                        $_SESSION['success'] = 'Client restored successfully';
                    } else {
                        $_SESSION['error'] = 'Client not found in delete history';
                    }
                    header('Location: admin-client-management.php?tab=delete_history');
                    exit();
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                    header('Location: admin-client-management.php?tab=delete_history');
                    exit();
                }
                break;

            case 'permanent_delete_client':
                $deleted_client_id = $_POST['deleted_client_id'];
                try {
                    $stmt = $conn->prepare('SELECT deleted_at, original_id FROM deleted_clients WHERE id = ?');
                    $stmt->execute([$deleted_client_id]);
                    $row = $stmt->fetch();
                    if (!$row || empty($row['deleted_at'])) {
                        $_SESSION['error'] = 'Permanent delete unavailable: missing deletion date';
                        header('Location: admin-client-management.php?tab=delete_history');
                        exit();
                    }
                    $deleted_at = strtotime($row['deleted_at']);
                    $diff_days = floor((time() - $deleted_at) / 86400);
                    if ($diff_days < 10) {
                        $_SESSION['error'] = 'Permanent delete allowed only after 10 days';
                        header('Location: admin-client-management.php?tab=delete_history');
                        exit();
                    }
                    if (!empty($row['original_id'])) {
                        $tryDelClient = $conn->prepare("DELETE FROM clients WHERE id = ?");
                        $tryDelClient->execute([$row['original_id']]);
                    }
                    $del = $conn->prepare('DELETE FROM deleted_clients WHERE id = ?');
                    $del->execute([$deleted_client_id]);
                    $_SESSION['success'] = 'Client permanently deleted';
                    header('Location: admin-client-management.php?tab=delete_history');
                    exit();
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
                    header('Location: admin-client-management.php?tab=delete_history');
                    exit();
                }
                break;
        }
    }
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active_clients';

$stats = ['total_clients' => 0, 'active_clients' => 0, 'deleted_clients' => 0];
$clients_admin = [];
$deleted_clients_admin = [];
$staff_members = [];

try {
    $client_stats_stmt = $conn->prepare("SELECT COUNT(*) total_clients, SUM(status='active') active_clients FROM clients");
    $client_stats_stmt->execute();
    $client_stats_data = $client_stats_stmt->fetch();
    $stats['total_clients'] = $client_stats_data['total_clients'] ?? 0;
    $stats['active_clients'] = $client_stats_data['active_clients'] ?? 0;
    $deleted_client_count_stmt = $conn->prepare("SELECT COUNT(*) deleted_clients FROM deleted_clients");
    $deleted_client_count_stmt->execute();
    $client_deleted_count = $deleted_client_count_stmt->fetch();
    $stats['deleted_clients'] = $client_deleted_count['deleted_clients'] ?? 0;

    $clients_admin_stmt = $conn->prepare("SELECT * FROM clients WHERE id NOT IN (SELECT original_id FROM deleted_clients) ORDER BY created_at DESC");
    $clients_admin_stmt->execute();
    $clients_admin = $clients_admin_stmt->fetchAll();

    $deleted_clients_admin_stmt = $conn->prepare("SELECT * FROM deleted_clients ORDER BY deleted_at DESC");
    $deleted_clients_admin_stmt->execute();
    $deleted_clients_admin = $deleted_clients_admin_stmt->fetchAll();

    $staff_stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = 'staff' AND status = 'active' ORDER BY name");
    $staff_stmt->execute();
    $staff_members = $staff_stmt->fetchAll();
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
    <title>NutriDeq - Client Management</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/staff-management.css">
    <link rel="stylesheet" href="css/user-management-staff.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <script src="scripts/dashboard.js" defer></script>
    <style>
        .tabs-header {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            border-bottom: 2px solid #edeff2;
            padding-bottom: 2px;
        }

        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: -2px;
        }

        .tab:hover {
            color: var(--primary);
            background: rgba(46, 139, 87, 0.05);
            border-radius: 8px 8px 0 0;
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 600;
        }

        .tab-icon {
            font-size: 1.1rem;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        /* Mobile Responsive Tables -> Cards */
        @media (max-width: 1024px) {
            .dashboard-grid.admin-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 20px !important;
            }
            .stat-card {
                width: 100% !important;
            }
            
            .user-table thead, .delete-history-table thead {
                display: none !important;
            }
            
            .user-table, .user-table tbody, .user-table tr, .user-table td,
            .delete-history-table, .delete-history-table tbody, .delete-history-table tr, .delete-history-table td {
                display: block !important;
                width: 100% !important;
            }
            
            .user-table tr, .delete-history-table tr {
                margin-bottom: 20px !important;
                background: white !important;
                border-radius: 12px !important;
                box-shadow: 0 2px 10px rgba(0,0,0,0.02) !important;
                padding: 10px 20px 20px 20px !important;
                border: 1px solid #eaeaea !important;
            }
            
            .user-table td, .delete-history-table td {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 16px 0 !important;
                border-bottom: 1px solid #f4f4f4 !important;
                text-align: right !important;
            }
            
            .user-table td:last-child, .delete-history-table td:last-child {
                border-bottom: none !important;
                border-top: 1px solid #f4f4f4 !important;
                flex-direction: row !important;
                justify-content: center !important;
                gap: 12px !important;
                padding-top: 20px !important;
                margin-top: 8px !important;
            }
            
            .user-table td::before, .delete-history-table td::before {
                content: attr(data-label);
                font-weight: 700 !important;
                color: #8fa1b3 !important;
                text-align: left !important;
                padding-right: 15px !important;
                flex-shrink: 0 !important;
                min-width: 90px;
                text-transform: uppercase !important;
                font-size: 0.75rem !important;
                letter-spacing: 0.5px !important;
            }

            .user-info-cell {
                text-align: right !important;
                justify-content: flex-end !important;
                width: 100% !important;
            }

            .user-avatar-small {
                background: #6a5acd !important; /* Matches purple avatar in screenshot */
            }

            .user-id {
                color: #A0AAB2 !important;
            }

            .cell-content {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-end !important;
                justify-content: center !important;
                text-align: right !important;
                gap: 5px;
                color: #555 !important;
            }
            
            .user-details { 
                text-align: right !important; 
            }

            .user-name {
                font-size: 0.95rem !important;
                font-weight: 700 !important;
                color: #333 !important;
                margin-bottom: 2px !important;
            }

            .status-active, .status-inactive {
                padding: 4px 16px !important;
                border-radius: 99px !important;
                font-size: 0.8rem !important;
                font-weight: 600 !important;
            }
            
            .user-actions {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
                gap: 15px !important;
            }

            .action-btn {
                padding: 0 !important;
                width: 36px !important;
                height: 36px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 6px !important;
                background: white !important;
                border: 1px solid #e2e8f0 !important;
                color: #64748b !important;
            }

            .action-btn i {
                font-size: 0.9rem !important;
            }

            .action-btn:hover {
                background: #f8fafc !important;
                color: #334155 !important;
                transform: none !important;
            }
            
            /* Responsive Modal Fixes */
            .client-info-grid {
                display: flex !important;
                flex-direction: column !important;
            }
            .form-group {
                width: 100% !important;
            }
        }
    </style>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <div class="page-container">
                <div class="header sticky-search-wrapper">
                    <div class="page-title">
                        <h1>Client Management</h1>
                        <p>Manage all clients</p>
                    </div>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search clients..." id="searchInput" class="global-search"
                                data-target=".user-table tbody tr, .client-card">
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="dashboard-grid admin-grid">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_clients'] ?? 0; ?></h3>
                            <p>Total Clients</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon staff">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['active_clients'] ?? 0; ?></h3>
                            <p>Active Clients</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon admins">
                            <i class="fas fa-trash"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['deleted_clients'] ?? 0; ?></h3>
                            <p>Deleted Clients</p>
                        </div>
                    </div>
                </div>

                <div class="tabs-header">
                    <a href="?tab=active_clients"
                        class="tab <?php echo $current_tab === 'active_clients' ? 'active' : ''; ?>">
                        <i class="fas fa-users tab-icon"></i>
                        <span>Active Clients</span>
                    </a>
                    <a href="?tab=delete_history"
                        class="tab <?php echo $current_tab === 'delete_history' ? 'active' : ''; ?>">
                        <i class="fas fa-history tab-icon"></i>
                        <span>Delete History</span>
                    </a>
                </div>

                <div class="management-section">
                    <div id="active-clients-tab"
                        class="tab-content <?php echo $current_tab === 'active_clients' ? 'active' : ''; ?>">
                        <div class="section-header">
                            <h2><i class="fas fa-user-injured"></i> Clients</h2>
                            <button class="btn btn-primary" id="addClientBtn"><i class="fas fa-user-plus"></i> Add New
                                Client</button>
                        </div>
                        <div class="table-container table-responsive desktop-view">
                            <table class="user-table">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients_admin as $client): ?>
                                        <tr>
                                            <td data-label="Client Info">
                                                <div class="user-info-cell">
                                                    <div class="user-avatar-small">
                                                        <?php echo getInitials($client['name']); ?></div>
                                                    <div class="user-details">
                                                        <div class="user-name">
                                                            <?php echo htmlspecialchars($client['name']); ?>
                                                        </div>
                                                        <div class="user-id"><?php echo $client['age'] ?? 'N/A'; ?>y •
                                                            <?php echo ucfirst($client['gender'] ?? 'Not set'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Contact">
                                                <div class="cell-content">
                                                    <div class="user-email"><?php echo htmlspecialchars($client['email']); ?></div>
                                                    <div class="user-id" style="text-align: right;"><?php echo $client['phone'] ?: 'No phone'; ?></div>
                                                </div>
                                            </td>
                                            <td data-label="Status">
                                                <div class="cell-content">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_client_status">
                                                        <input type="hidden" name="client_id"
                                                            value="<?php echo $client['id']; ?>">
                                                        <button type="submit"
                                                            class="status-toggle status-<?php echo $client['status']; ?>"><?php echo ucfirst($client['status']); ?></button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td data-label="Actions">
                                                <div class="user-actions">
                                                    <button class="action-btn edit-client-btn" title="Edit Client"
                                                        data-id="<?php echo $client['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($client['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($client['email']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>"
                                                        data-age="<?php echo $client['age'] ?? ''; ?>"
                                                        data-gender="<?php echo $client['gender'] ?? ''; ?>"
                                                        data-address="<?php echo htmlspecialchars($client['address'] ?? ''); ?>"
                                                        data-city="<?php echo htmlspecialchars($client['city'] ?? ''); ?>"
                                                        data-state="<?php echo htmlspecialchars($client['state'] ?? ''); ?>"
                                                        data-zip_code="<?php echo htmlspecialchars($client['zip_code'] ?? ''); ?>"
                                                        data-waist="<?php echo $client['waist_circumference'] ?? ''; ?>"
                                                        data-hip="<?php echo $client['hip_circumference'] ?? ''; ?>"
                                                        data-staff_id="<?php echo $client['staff_id'] ?? ''; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('Delete this client? They will be moved to delete history.')">
                                                        <input type="hidden" name="action" value="delete_client">
                                                        <input type="hidden" name="client_id"
                                                            value="<?php echo $client['id']; ?>">
                                                        <button type="submit" class="action-btn delete-btn"
                                                            title="Delete Client"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="client-card-container mobile-view">
                            <?php foreach ($clients_admin as $client): ?>
                                <div class="client-card">
                                    <div class="card-top-row">
                                        <div class="user-avatar-purple"><?php echo getInitials($client['name']); ?></div>
                                        <div class="card-header-info">
                                            <div class="card-client-name"><?php echo htmlspecialchars($client['name']); ?></div>
                                            <div class="card-status-badge">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="toggle_client_status">
                                                    <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                                    <button type="submit" class="card-badge-btn badge-<?php echo $client['status']; ?>">
                                                        <?php echo ucfirst($client['status']); ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="card-actions-top">
                                            <button class="action-icon-btn edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($client)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Delete this client?')">
                                                <input type="hidden" name="action" value="delete_client">
                                                <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                                <button type="submit" class="action-icon-btn delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card-middle-row">
                                        <div class="card-data-col">
                                            <span class="card-label">Email</span>
                                            <span class="card-value"><?php echo htmlspecialchars($client['email']); ?></span>
                                        </div>
                                        <div class="card-data-col">
                                            <span class="card-label">Phone</span>
                                            <span class="card-value"><?php echo $client['phone'] ?: 'N/A'; ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="delete-history-tab"
                        class="tab-content <?php echo $current_tab === 'delete_history' ? 'active' : ''; ?>">
                        <div class="section-header">
                            <h2><i class="fas fa-history"></i> Delete History</h2>
                            <p>Clients deleted by administrators.</p>
                        </div>
                        <?php if (empty($deleted_clients_admin)): ?>
                            <div class="empty-state"><i class="fas fa-history"></i>
                                <h3>No deleted clients found</h3>
                                <p>Deleted clients will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container table-responsive">
                                <table class="delete-history-table">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Age/Gender</th>
                                            <th>Deleted By</th>
                                            <th>Deleted Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="desktop-view">
                                        <?php foreach ($deleted_clients_admin as $deleted_client): ?>
                                            <tr>
                                                <td data-label="Client">
                                                    <div class="user-info-cell">
                                                        <div class="user-avatar-small">
                                                            <?php echo getInitials($deleted_client['name']); ?>
                                                        </div>
                                                        <div class="user-details">
                                                            <div class="user-name">
                                                                <?php echo htmlspecialchars($deleted_client['name']); ?>
                                                            </div>
                                                            <div class="user-id">ID:
                                                                <?php echo $deleted_client['original_id']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-label="Email"><div class="cell-content"><?php echo htmlspecialchars($deleted_client['email']); ?></div></td>
                                                <td data-label="Phone"><div class="cell-content"><?php echo $deleted_client['phone'] ?: 'N/A'; ?></div></td>
                                                <td data-label="Age/Gender"><div class="cell-content"><?php echo $deleted_client['age'] ?? 'N/A'; ?>y •
                                                    <?php echo ucfirst($deleted_client['gender'] ?? 'N/A'); ?></div>
                                                </td>
                                                <td data-label="Deleted By">
                                                    <div class="deleted-by cell-content">
                                                        <span><?php echo ucfirst($deleted_client['deleted_by'] ?? 'admin'); ?></span>
                                                        <div class="deleted-by-avatar">
                                                            <?php echo getInitials($deleted_client['deleted_by_name'] ?? 'Admin'); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-label="Deleted Date"><div class="cell-content"><?php echo date('M j, Y H:i', strtotime($deleted_client['deleted_at'])); ?></div>
                                                </td>
                                                <td data-label="Actions">
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="restore_client">
                                                        <input type="hidden" name="deleted_client_id"
                                                            value="<?php echo $deleted_client['id']; ?>">
                                                        <button type="submit" class="restore-btn"
                                                            onclick="return confirm('Restore this client?')"><i
                                                                class="fas fa-undo"></i>
                                                            Restore</button>
                                                    </form>
                                                    <?php $deleted_ts = !empty($deleted_client['deleted_at']) ? strtotime($deleted_client['deleted_at']) : null;
                                                    $age_days = $deleted_ts ? floor((time() - $deleted_ts) / 86400) : 0;
                                                    $can_perm_delete = $deleted_ts && $age_days >= 10; ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="permanent_delete_client">
                                                        <input type="hidden" name="deleted_client_id"
                                                            value="<?php echo $deleted_client['id']; ?>">
                                                        <button type="submit" class="restore-btn perm-delete-btn"
                                                            data-deleted-at="<?php echo $deleted_client['deleted_at']; ?>"
                                                            data-deleted-at-ts="<?php echo $deleted_ts ? $deleted_ts : ''; ?>"
                                                            data-min-days="10" style="background:#e74c3c" <?php echo $can_perm_delete ? '' : 'disabled'; ?>
                                                            onclick="return confirm('Permanently delete this record? This cannot be undone.')"><i
                                                                class="fas fa-trash"></i> Delete Permanently</button>
                                                        <span class="perm-countdown"
                                                            data-deleted-at="<?php echo $deleted_client['deleted_at']; ?>"
                                                            data-deleted-at-ts="<?php echo $deleted_ts ? $deleted_ts : ''; ?>"
                                                            data-min-days="10" style="margin-left:8px;color:#888"></span>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card View for History -->
                            <div class="client-card-container mobile-view">
                                <?php foreach ($deleted_clients_admin as $deleted_client): ?>
                                    <div class="client-card">
                                        <div class="card-top-row">
                                            <div class="user-avatar-purple"><?php echo getInitials($deleted_client['name']); ?></div>
                                            <div class="card-header-info">
                                                <div class="card-client-name"><?php echo htmlspecialchars($deleted_client['name']); ?></div>
                                                <div class="card-status-badge">
                                                    <span class="badge-deleted">Deleted</span>
                                                </div>
                                            </div>
                                            <div class="card-actions-top">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="restore_client">
                                                    <input type="hidden" name="deleted_client_id" value="<?php echo $deleted_client['id']; ?>">
                                                    <button type="submit" class="action-icon-btn restore" onclick="return confirm('Restore this client?')">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="card-middle-row">
                                            <div class="card-data-col">
                                                <span class="card-label">Email</span>
                                                <span class="card-value"><?php echo htmlspecialchars($deleted_client['email']); ?></span>
                                            </div>
                                            <div class="card-data-col">
                                                <span class="card-label">Phone</span>
                                                <span class="card-value"><?php echo $deleted_client['phone'] ?: 'N/A'; ?></span>
                                            </div>
                                        </div>
                                        <div class="card-bottom-info">
                                            <span class="deleted-info">Deleted on <?php echo date('M j, Y', strtotime($deleted_client['deleted_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                </div> <!-- end management-section -->

            <!-- Modals moved to bottom for viewport clearance -->

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const toggleAccountPassword = document.getElementById('toggle_account_password');
                    if (toggleAccountPassword) {
                        toggleAccountPassword.addEventListener('click', function () {
                            const input = document.getElementById('account_password');
                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                            input.setAttribute('type', type);
                            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                        });
                    }
                    const toggleAccountConfirmPassword = document.getElementById('toggle_account_confirm_password');
                    if (toggleAccountConfirmPassword) {
                        toggleAccountConfirmPassword.addEventListener('click', function () {
                            const input = document.getElementById('account_confirm_password');
                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                            input.setAttribute('type', type);
                            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                        });
                    }
                    const addClientBtn = document.getElementById('addClientBtn');
                    if (addClientBtn) {
                        addClientBtn.addEventListener('click', function () {
                            document.getElementById('addClientModal').style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        });
                    }
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.addEventListener('input', function (e) {
                            const searchTerm = e.target.value.toLowerCase();
                            
                            // Desktop table rows
                            const rows = document.querySelectorAll('.user-table tbody tr');
                            rows.forEach(row => {
                                const clientName = row.cells[0].textContent.toLowerCase();
                                const clientEmail = row.cells[1].textContent.toLowerCase();
                                if (clientName.includes(searchTerm) || clientEmail.includes(searchTerm)) { row.style.display = ''; } else { row.style.display = 'none'; }
                            });

                            // Mobile cards
                            const cards = document.querySelectorAll('.client-card');
                            cards.forEach(card => {
                                const clientName = card.querySelector('.card-client-name').textContent.toLowerCase();
                                const clientEmail = card.querySelector('.card-value').textContent.toLowerCase();
                                if (clientName.includes(searchTerm) || clientEmail.includes(searchTerm)) { card.style.display = ''; } else { card.style.display = 'none'; }
                            });
                        });
                    }
                    (function setupPasswordToggleClientModal() {
                        const toggleConfirm = document.getElementById('toggle_edit_confirm_new_password');
                        const inputConfirm = document.getElementById('edit_confirm_new_password');
                        if (toggleConfirm && inputConfirm) {
                            toggleConfirm.addEventListener('click', function () {
                                inputConfirm.type = inputConfirm.type === 'password' ? 'text' : 'password';
                                this.innerHTML = inputConfirm.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                            });
                        }
                        const toggleNew = document.getElementById('toggle_edit_new_password');
                        const inputNew = document.getElementById('edit_new_password');
                        if (toggleNew && inputNew) {
                            toggleNew.addEventListener('click', function () {
                                inputNew.type = inputNew.type === 'password' ? 'text' : 'password';
                                this.innerHTML = inputNew.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                            });
                        }
                    })();


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
                            const tsAttr = btn.getAttribute('data-deleted-at-ts');
                            const deletedAtStr = btn.getAttribute('data-deleted-at');
                            const minDays = parseInt(btn.getAttribute('data-min-days') || '10', 10);
                            const countdownSpan = btn.parentElement.querySelector('.perm-countdown');
                            if (!countdownSpan) return;
                            let deletedMs = null;
                            if (tsAttr && /^\d+$/.test(tsAttr)) { deletedMs = parseInt(tsAttr, 10) * 1000; }
                            else if (deletedAtStr) { deletedMs = Date.parse(deletedAtStr.replace(' ', 'T')); }
                            if (!deletedMs || Number.isNaN(deletedMs)) return;
                            function update() {
                                const elapsed = Date.now() - deletedMs;
                                const minMs = minDays * 86400 * 1000;
                                const leftMs = Math.max(0, minMs - elapsed);
                                const leftSec = Math.floor(leftMs / 1000);
                                if (leftMs <= 0) { btn.removeAttribute('disabled'); countdownSpan.textContent = ''; return true; }
                                else { btn.setAttribute('disabled', 'disabled'); countdownSpan.textContent = `Available in ${formatTimeLeft(leftSec)}`; return false; }
                            }
                            update();
                            const iv = setInterval(() => { if (update()) clearInterval(iv); }, 1000);
                        });
                    }
                    setupPermDeleteCountdown();

                    // Logout Modal Functionality parity with other pages
                    const logoutBtn = document.getElementById('logoutBtn');
                    const logoutModal = document.getElementById('logoutModal');
                    const cancelLogout = document.getElementById('cancelLogout');
                    const confirmLogout = document.getElementById('confirmLogout');
                    if (logoutBtn) {
                        logoutBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            logoutModal.classList.add('active');
                        });
                    }
                    if (cancelLogout) {
                        cancelLogout.addEventListener('click', function () {
                            logoutModal.classList.remove('active');
                        });
                    }
                    if (confirmLogout) {
                        confirmLogout.addEventListener('click', function () {
                            window.location.href = 'logout.php';
                        });
                    }

                    // Close modals on overlay click
                    const addClientModal = document.getElementById('addClientModal');
                    const editClientModal = document.getElementById('editClientModal');
                    window.addEventListener('click', function (event) {
                        if (event.target === addClientModal) { closeModal(); }
                        if (event.target === editClientModal) { closeEditModal(); }
                        if (event.target === logoutModal) { logoutModal.classList.remove('active'); }
                    });
                    // Close on Escape
                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            closeModal();
                            closeEditModal();
                            if (logoutModal && logoutModal.classList.contains('active')) {
                                logoutModal.classList.remove('active');
                            }
                        }
                    });
                    const editClientButtons = document.querySelectorAll('.edit-client-btn');
                    if (editClientButtons && editClientModal) {
                        editClientButtons.forEach(btn => {
                            btn.addEventListener('click', function (e) {
                                e.preventDefault();
                                document.getElementById('edit_client_id').value = this.getAttribute('data-id');
                                document.getElementById('edit_name').value = this.getAttribute('data-name');
                                document.getElementById('edit_email').value = this.getAttribute('data-email');
                                document.getElementById('edit_phone').value = this.getAttribute('data-phone');
                                document.getElementById('edit_age').value = this.getAttribute('data-age');
                                document.getElementById('edit_address').value = this.getAttribute('data-address');
                                document.getElementById('edit_city').value = this.getAttribute('data-city');
                                document.getElementById('edit_state').value = this.getAttribute('data-state');
                                document.getElementById('edit_zip_code').value = this.getAttribute('data-zip_code');
                                document.getElementById('edit_waist_circumference').value = this.getAttribute('data-waist');
                                document.getElementById('edit_hip_circumference').value = this.getAttribute('data-hip');
                                document.getElementById('edit_new_password').value = '';
                                
                                const staffId = this.getAttribute('data-staff_id');
                                const staffSel = document.getElementById('edit_staff_id');
                                if (staffSel) { staffSel.value = staffId || ''; }
                                
                                editClientModal.style.display = 'flex';
                                document.body.style.overflow = 'hidden';
                            });
                        });
                    }
                });

                function closeModal() { document.getElementById('addClientModal').style.display = 'none'; document.body.style.overflow = 'auto'; document.getElementById('addClientForm').reset(); }
                function closeEditModal() { document.getElementById('editClientModal').style.display = 'none'; document.body.style.overflow = 'auto'; document.getElementById('editClientForm').reset(); }
            </script>
            </div> <!-- end page-container -->
        </main> <!-- end main-content -->
    </div> <!-- end main-layout -->

    <div class="modal" id="addClientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Client</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="addClientForm">
                <input type="hidden" name="action" value="add_client">
                <div class="form-group"><label for="name">Full Name *</label><input type="text" id="name" name="name"
                        class="form-control" placeholder="First and last name" required></div>
                <div class="form-group"><label for="email">Email *</label><input type="email" id="email" name="email"
                        class="form-control" placeholder="name@example.com" required></div>
                <div class="form-group"><label for="account_password">Account Password (optional)</label>
                    <div style="display:flex;align-items:center;gap:8px;"><input type="password" id="account_password"
                            name="account_password" class="form-control" placeholder="Set initial account password"
                            style="flex:1;"><button type="button" class="btn btn-outline" id="toggle_account_password"
                            title="Show/Hide Password"><i class="fas fa-eye"></i></button></div>
                </div>
                <div class="form-group"><label for="account_confirm_password">Confirm Password</label>
                    <div style="display:flex;align-items:center;gap:8px;"><input type="password"
                            id="account_confirm_password" name="account_confirm_password" class="form-control"
                            placeholder="Re-enter password" style="flex:1;"><button type="button"
                            class="btn btn-outline" id="toggle_account_confirm_password" title="Show/Hide Password"><i
                                class="fas fa-eye"></i></button></div>
                </div>
                <div class="form-group"><label for="add_staff_id">Assign Staff</label><select id="add_staff_id"
                        name="staff_id" class="form-control">
                        <option value="">Unassigned</option>
                        <?php if (!empty($staff_members)) {
                            foreach ($staff_members as $s) {
                                echo '<option value="' . htmlspecialchars($s['id']) . '">' . htmlspecialchars($s['name']) . ' (' . htmlspecialchars($s['email']) . ')</option>';
                            }
                        } ?>
                    </select></div>
                <div class="form-group"><label for="phone">Phone</label><input type="tel" id="phone" name="phone"
                        class="form-control" placeholder="+63 1-456-7890"></div>
                <div class="form-group"><label for="address">Street Address</label><input type="text" id="address"
                        name="address" class="form-control" placeholder="123 Main Street"></div>
                <div class="client-info-grid">
                    <div class="form-group"><label for="city">City</label><input type="text" id="city" name="city"
                            class="form-control" placeholder="New York"></div>
                    <div class="form-group"><label for="state">State/Province</label><input type="text" id="state"
                            name="state" class="form-control" placeholder="NY"></div>
                </div>
                <div class="form-group"><label for="zip_code">ZIP/Postal Code</label><input type="text" id="zip_code"
                        name="zip_code" class="form-control" placeholder="10001"></div>
                <div class="client-info-grid">
                    <div class="form-group"><label for="age">Age</label><input type="number" id="age" name="age"
                            class="form-control" min="1" max="120"></div>
                    <div class="form-group"><label for="gender">Gender</label><select id="gender" name="gender"
                            class="form-control">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select></div>
                </div>
                <div class="form-group"><label for="waist_circumference">Waist Circumference (cm)</label><input
                        type="number" class="form-control" id="waist_circumference" name="waist_circumference"
                        step="0.1" min="0" placeholder="Enter waist measurement"></div>
                <div class="form-group"><label for="hip_circumference">Hip Circumference (cm)</label><input
                        type="number" class="form-control" id="hip_circumference" name="hip_circumference" step="0.1"
                        min="0" placeholder="Enter hip measurement"></div>
                <div class="form-actions"><button type="button" class="btn btn-outline"
                        onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-primary">Add
                        Client</button></div>
            </form>
        </div>
    </div>

    <div class="modal" id="editClientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Client Information</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editClientForm">
                <input type="hidden" name="action" value="update_client">
                <input type="hidden" id="edit_client_id" name="client_id">
                <div class="form-section">
                    <div class="form-group"><label for="edit_name">Full Name</label><input type="text" id="edit_name"
                            name="name" class="form-control" required></div>
                    <div class="form-group"><label for="edit_email">Email</label><input type="email" id="edit_email"
                            name="email" class="form-control" required></div>
                    <div class="form-group"><label for="edit_staff_id">Assign Staff</label><select id="edit_staff_id"
                            name="staff_id" class="form-control">
                            <option value="">Unassigned</option>
                            <?php if (!empty($staff_members)) {
                                foreach ($staff_members as $s) {
                                    echo '<option value="' . htmlspecialchars($s['id']) . '">' . htmlspecialchars($s['name']) . ' (' . htmlspecialchars($s['email']) . ')</option>';
                                }
                            } ?>
                        </select></div>
                    <div class="client-info-grid">
                        <div class="form-group"><label for="edit_phone">Phone</label><input type="tel" id="edit_phone"
                                name="phone" class="form-control" placeholder="+63 1-456-7890">
                        </div>
                        <div class="form-group"><label for="edit_age">Age</label><input type="number" id="edit_age"
                                name="age" class="form-control" min="1" max="120"></div>
                    </div>
                    <div class="client-info-grid">
                        <div class="form-group"><label for="edit_address">Street Address</label><input type="text"
                                id="edit_address" name="address" class="form-control" placeholder="123 Main Street">
                        </div>
                        <div class="form-group"><label for="edit_city">City</label><input type="text" id="edit_city"
                                name="city" class="form-control" placeholder="New York"></div>
                    </div>
                    <div class="client-info-grid">
                        <div class="form-group"><label for="edit_state">State/Province</label><input type="text"
                                id="edit_state" name="state" class="form-control" placeholder="NY"></div>
                        <div class="form-group"><label for="edit_zip_code">ZIP/Postal Code</label><input type="text"
                                id="edit_zip_code" name="zip_code" class="form-control" placeholder="10001"></div>
                    </div>
                    <div class="client-info-grid">
                        <div class="form-group"><label for="edit_waist_circumference">Waist Circumference
                                (cm)</label><input type="number" id="edit_waist_circumference"
                                name="waist_circumference" class="form-control" step="0.1" min="0"
                                placeholder="Enter waist measurement"></div>
                        <div class="form-group"><label for="edit_hip_circumference">Hip Circumference
                                (cm)</label><input type="number" id="edit_hip_circumference" name="hip_circumference"
                                class="form-control" step="0.1" min="0" placeholder="Enter hip measurement"></div>
                    </div>
                    <div class="form-group"><label for="edit_new_password">New Password (linked user)</label>
                        <div style="display:flex;align-items:center;gap:8px;"><input type="password"
                                id="edit_new_password" name="new_password" class="form-control" placeholder="Optional"
                                style="flex:1;"><button type="button" class="btn btn-outline"
                                id="toggle_edit_new_password" title="Show/Hide Password"><i
                                    class="fas fa-eye"></i></button></div>
                    </div>
                    <div class="form-group"><label for="edit_confirm_new_password">Confirm New Password</label>
                        <div style="display:flex;align-items:center;gap:8px;"><input type="password"
                                id="edit_confirm_new_password" name="confirm_new_password" class="form-control"
                                placeholder="Re-enter new password" style="flex:1;"><button type="button"
                                class="btn btn-outline" id="toggle_edit_confirm_new_password"
                                title="Show/Hide Password"><i class="fas fa-eye"></i></button></div>
                    </div>
                </div>
                <div class="form-actions"><button type="button" class="btn btn-outline"
                        onclick="closeEditModal()">Cancel</button><button type="submit" class="btn btn-primary">Update
                        Client</button></div>
            </form>
        </div>
    </div>
</body>
</html>
