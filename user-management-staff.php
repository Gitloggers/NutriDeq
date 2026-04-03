<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'user')) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}
$staff_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] === 'admin');
$is_user = ($_SESSION['user_role'] === 'user');
$deleted_by = $is_admin ? 'admin' : ($is_user ? 'user' : 'staff');
$deleted_by_id = $staff_id;

$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

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

$clients = [];
$deleted_clients = [];
$stats = [
    'total_clients' => 0,
    'active_clients' => 0,
    'new_this_month' => 0,
    'deleted_clients' => 0
];
$error = '';
$success = '';

// Handle form actions for client management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_client':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone'] ?? '');
                $age = $_POST['age'] ?? null;
                $gender = $_POST['gender'] ?? '';
                $weight = $_POST['weight'] ?? null;
                $height = $_POST['height'] ?? null;
                $health_conditions = trim($_POST['health_conditions'] ?? '');
                $dietary_restrictions = trim($_POST['dietary_restrictions'] ?? '');
                $goals = trim($_POST['goals'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $state = trim($_POST['state'] ?? '');
                $zip_code = trim($_POST['zip_code'] ?? '');
                $waist_circumference = $_POST['waist_circumference'] ?? null;
                $hip_circumference = $_POST['hip_circumference'] ?? null;
                $account_password = $_POST['account_password'] ?? '';
                $account_confirm_password = $_POST['account_confirm_password'] ?? '';
                $desired_password = '';
                if (!empty($account_password)) {
                    if ($account_password !== $account_confirm_password) {
                        $error = "Account password and confirm password do not match";
                        break;
                    }
                    $desired_password = $account_password;
                }

                if (empty($name) || empty($email)) {
                    $error = "Name and email are required";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format";
                } else {
                    $check_stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
                    $check_stmt->execute([$email]);
                    if ($check_stmt->fetch()) {
                        $error = "Client with this email already exists";
                    } else {
                        try {
                            $conn->beginTransaction();

                            $insert_stmt = $conn->prepare("
                                INSERT INTO clients (user_id, name, email, phone, address, city, state, zip_code, age, gender, weight, height, waist_circumference, hip_circumference, health_conditions, dietary_restrictions, goals, status, staff_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
                            ");

                            if (
                                $insert_stmt->execute([
                                    null,
                                    $name,
                                    $email,
                                    $phone,
                                    $address,
                                    $city,
                                    $state,
                                    $zip_code,
                                    $age,
                                    $gender,
                                    $weight,
                                    $height,
                                    $waist_circumference,
                                    $hip_circumference,
                                    $health_conditions,
                                    $dietary_restrictions,
                                    $goals,
                                    $staff_id
                                ])
                            ) {
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
                                $conn->commit();
                                $_SESSION['success'] = "Client added successfully";
                                header("Location: user-management-staff.php");
                                exit();
                            } else {
                                $conn->rollBack();
                                $error = "Failed to add client";
                            }
                        } catch (PDOException $e) {
                            $conn->rollBack();
                            $error = "Database error: " . $e->getMessage();
                        }
                    }
                }
                break;

            case 'update_client':
                $client_id = $_POST['client_id'];
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $age = $_POST['age'] ?? null;
                $weight = $_POST['weight'] ?? null;
                $height = $_POST['height'] ?? null;
                $waist_circumference = $_POST['waist_circumference'] ?? null;
                $hip_circumference = $_POST['hip_circumference'] ?? null;
                $address = trim($_POST['address'] ?? '');
                $city = trim($_POST['city'] ?? '');
                $state = trim($_POST['state'] ?? '');
                $zip_code = trim($_POST['zip_code'] ?? '');
                $health_conditions = trim($_POST['health_conditions'] ?? '');
                $dietary_restrictions = trim($_POST['dietary_restrictions'] ?? '');
                $goals = trim($_POST['goals'] ?? '');
                $notes = trim($_POST['notes'] ?? '');

                try {
                    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = "Please provide valid name and email";
                        break;
                    }
                    $dupe = $conn->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
                    $dupe->execute([$email, $client_id]);
                    if ($dupe->fetch()) {
                        $error = "Client with this email already exists";
                        break;
                    }
                    $update_sql = "UPDATE clients SET name = ?, email = ?, phone = ?, age = ?, weight = ?, height = ?, waist_circumference = ?, hip_circumference = ?, address = ?, city = ?, state = ?, zip_code = ?, health_conditions = ?, dietary_restrictions = ?, goals = ?, notes = ?, updated_at = NOW() WHERE id = ?";
                    $update_params = [
                        $name,
                        $email,
                        $phone,
                        $age,
                        $weight,
                        $height,
                        $waist_circumference,
                        $hip_circumference,
                        $address,
                        $city,
                        $state,
                        $zip_code,
                        $health_conditions,
                        $dietary_restrictions,
                        $goals,
                        $notes,
                        $client_id
                    ];

                    if (!$is_admin) {
                        $update_sql .= " AND staff_id = ?";
                        $update_params[] = $staff_id;
                    }

                    $update_stmt = $conn->prepare($update_sql);

                    if ($update_stmt->execute($update_params)) {
                        $link_check = $conn->prepare('SELECT user_id FROM clients WHERE id = ?');
                        $link_check->execute([$client_id]);
                        $row = $link_check->fetch();
                        $current_user_id = $row['user_id'] ?? null;
                        $user_check = $conn->prepare('SELECT id FROM users WHERE email = ?');
                        $user_check->execute([$email]);
                        $existing_user = $user_check->fetch();
                        $target_user_id = null;
                        if ($existing_user && isset($existing_user['id'])) {
                            $target_user_id = (int) $existing_user['id'];
                            $dup = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
                            $dup->execute([$email, $target_user_id]);
                            if (!$dup->fetch()) {
                                $upd_user = $conn->prepare('UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?');
                                $upd_user->execute([$name, $email, $target_user_id]);
                            }
                        } else {
                            $tmpPassword = bin2hex(random_bytes(8));
                            $hashed = password_hash($tmpPassword, PASSWORD_DEFAULT);
                            $ins_user = $conn->prepare('INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
                            $ins_user->execute([$name, $email, $hashed, 'regular', 'active']);
                            $target_user_id = (int) $conn->lastInsertId();
                        }
                        if (empty($current_user_id) || $current_user_id != $target_user_id) {
                            $upd = $conn->prepare('UPDATE clients SET user_id = ? WHERE id = ?');
                            $upd->execute([$target_user_id, $client_id]);
                        }
                        $_SESSION['success'] = "Client updated successfully";
                        header("Location: user-management-staff.php");
                        exit();
                    } else {
                        $error = "Failed to update client";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
                break;

            case 'toggle_client_status':
                $client_id = $_POST['client_id'];
                try {
                    $toggle_sql = "UPDATE clients SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?";
                    $toggle_params = [$client_id];
                    if (!$is_admin) {
                        $toggle_sql .= " AND staff_id = ?";
                        $toggle_params[] = $staff_id;
                    }
                    $stmt = $conn->prepare($toggle_sql);
                    $stmt->execute($toggle_params);

                    $sel_sql = "SELECT status, user_id FROM clients WHERE id = ?";
                    $sel_params = [$client_id];
                    if (!$is_admin) {
                        $sel_sql .= " AND staff_id = ?";
                        $sel_params[] = $staff_id;
                    }
                    $sel = $conn->prepare($sel_sql);
                    $sel->execute($sel_params);
                    $c = $sel->fetch();
                    if ($c && !empty($c['user_id'])) {
                        $updUserStatus = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                        $updUserStatus->execute([$c['status'], $c['user_id']]);
                    }
                    $_SESSION['success'] = "Client status updated successfully";
                    header("Location: user-management-staff.php");
                    exit();
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
                break;

            case 'delete_client':
                $client_id = $_POST['client_id'];

                try {
                    $conn->beginTransaction();

                    // Get client data before deletion
                    $sel_sql = "SELECT * FROM clients WHERE id = ?";
                    $sel_params = [$client_id];
                    if (!$is_admin) {
                        $sel_sql .= " AND staff_id = ?";
                        $sel_params[] = $staff_id;
                    }
                    $select_stmt = $conn->prepare($sel_sql);
                    $select_stmt->execute($sel_params);
                    $client = $select_stmt->fetch();

                    if ($client) {
                        // Insert into deleted_clients table with correct column names
                        $insert_stmt = $conn->prepare("\n    INSERT INTO deleted_clients (\n        original_id, user_id, staff_id, deleted_by, \n        deleted_by_user_id, deleted_by_name,\n        name, email, phone, address, city, state, zip_code,\n        age, gender, weight, height, waist_circumference,\n        hip_circumference, health_conditions, dietary_restrictions,\n        goals, notes, status, created_at, updated_at, deleted_at\n    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())\n            ");

                        $insert_stmt->execute([
                            $client['id'],
                            $client['user_id'],
                            $client['staff_id'],
                            $deleted_by,
                            $deleted_by_id,
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
                            $client['weight'],
                            $client['height'],
                            $client['waist_circumference'],
                            $client['hip_circumference'],
                            $client['health_conditions'],
                            $client['dietary_restrictions'],
                            $client['goals'],
                            $client['notes'],
                            $client['status'],
                            $client['created_at'],
                            $client['updated_at']
                        ]);

                        if (!empty($client['user_id'])) {
                            try {
                                $updUser = $conn->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                                $updUser->execute([$client['user_id']]);
                            } catch (PDOException $e) {
                            }
                        }
                        $del_sql = "UPDATE clients SET status = 'deleted', updated_at = NOW() WHERE id = ?";
                        $del_params = [$client_id];
                        if (!$is_admin) {
                            $del_sql .= " AND staff_id = ?";
                            $del_params[] = $staff_id;
                        }
                        $delete_stmt = $conn->prepare($del_sql);
                        $delete_stmt->execute($del_params);

                        $conn->commit();
                        $_SESSION['success'] = "Client moved to delete history successfully";
                    } else {
                        $error = "Client not found or you don't have permission to delete this client";
                    }

                    header("Location: user-management-staff.php");
                    exit();
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $error = "Database error: " . $e->getMessage();
                }
                break;

            case 'restore_client':
                $deleted_client_id = $_POST['deleted_client_id'];

                try {
                    $conn->beginTransaction();

                    // Get deleted client data for clients belonging to this staff
                    $sel_sql = "SELECT * FROM deleted_clients WHERE id = ?";
                    $sel_params = [$deleted_client_id];
                    if (!$is_admin) {
                        $sel_sql .= " AND staff_id = ?";
                        $sel_params[] = $staff_id;
                    }
                    $select_stmt = $conn->prepare($sel_sql);
                    $select_stmt->execute($sel_params);
                    $client = $select_stmt->fetch();

                    if ($client) {
                        // If a client row with same original_id exists, update it; else insert
                        $exists_stmt = $conn->prepare("SELECT id FROM clients WHERE id = ?");
                        $exists_stmt->execute([$client['original_id']]);
                        $exists = $exists_stmt->fetch();

                        if ($exists) {
                            // Update existing row back to original data
                            $update_stmt = $conn->prepare("
                                UPDATE clients SET
                                    user_id = ?, staff_id = ?, name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?,
                                    age = ?, gender = ?, weight = ?, height = ?, waist_circumference = ?, hip_circumference = ?,
                                    health_conditions = ?, dietary_restrictions = ?, goals = ?, notes = ?, status = ?,
                                    updated_at = NOW(), restored_at = NOW()
                                WHERE id = ?
                            ");
                            $update_stmt->execute([
                                $client['user_id'],
                                $client['staff_id'],
                                $client['name'],
                                $client['email'],
                                $client['phone'],
                                $client['address'],
                                $client['city'],
                                $client['state'],
                                $client['zip_code'],
                                $client['age'],
                                $client['gender'],
                                $client['weight'],
                                $client['height'],
                                $client['waist_circumference'],
                                $client['hip_circumference'],
                                $client['health_conditions'],
                                $client['dietary_restrictions'],
                                $client['goals'],
                                $client['notes'],
                                $client['status'],
                                $client['original_id']
                            ]);
                        } else {
                            // Check duplicate email in other records (exclude this original_id)
                            $check_stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
                            $check_stmt->execute([$client['email'], $client['original_id']]);
                            if ($check_stmt->fetch()) {
                                $conn->rollBack();
                                $error = "A client with this email already exists. Cannot restore.";
                                break;
                            }
                            // Insert back into clients table
                            $insert_stmt = $conn->prepare("
                                INSERT INTO clients (
                                    id, user_id, staff_id, name, email, phone, address, city, state, zip_code,
                                    age, gender, weight, height, waist_circumference, hip_circumference,
                                    health_conditions, dietary_restrictions, goals, notes, status,
                                    created_at, updated_at, restored_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $insert_stmt->execute([
                                $client['original_id'],
                                $client['user_id'],
                                $client['staff_id'],
                                $client['name'],
                                $client['email'],
                                $client['phone'],
                                $client['address'],
                                $client['city'],
                                $client['state'],
                                $client['zip_code'],
                                $client['age'],
                                $client['gender'],
                                $client['weight'],
                                $client['height'],
                                $client['waist_circumference'],
                                $client['hip_circumference'],
                                $client['health_conditions'],
                                $client['dietary_restrictions'],
                                $client['goals'],
                                $client['notes'],
                                $client['status'],
                                $client['created_at'],
                                $client['updated_at']
                            ]);
                        }

                        // Remove from deleted_clients table
                        $delete_stmt = $conn->prepare("DELETE FROM deleted_clients WHERE id = ?");
                        $delete_stmt->execute([$deleted_client_id]);

                        $conn->commit();
                        $_SESSION['success'] = "Client restored successfully";
                        if (!empty($client['user_id'])) {
                            try {
                                $updUser = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
                                $updUser->execute([$client['status'], $client['user_id']]);
                            } catch (PDOException $e) {
                            }
                        }
                    } else {
                        $error = "Client not found in delete history";
                    }

                    header("Location: user-management-staff.php?tab=delete_history");
                    exit();
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $error = "Database error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Handle GET parameters for tabs
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active_clients';

try {
    // Get client statistics for the logged-in staff member
    $stats_sql = "SELECT 
        COUNT(*) as total_clients,
        SUM(status = 'active') as active_clients,
        SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_this_month
        FROM clients";
    $stats_params = [];
    if (!$is_admin) {
        $stats_sql .= " WHERE staff_id = ?";
        $stats_params[] = $staff_id;
    }
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute($stats_params);
    $stats_data = $stats_stmt->fetch();
    $stats['total_clients'] = $stats_data['total_clients'] ?? 0;
    $stats['active_clients'] = $stats_data['active_clients'] ?? 0;
    $stats['new_this_month'] = $stats_data['new_this_month'] ?? 0;

    // Get deleted clients count for this staff
    $del_stats_sql = "SELECT COUNT(*) as deleted_count FROM deleted_clients";
    $del_stats_params = [];
    if (!$is_admin) {
        $del_stats_sql .= " WHERE staff_id = ?";
        $del_stats_params[] = $staff_id;
    }
    $deleted_stats_stmt = $conn->prepare($del_stats_sql);
    $deleted_stats_stmt->execute($del_stats_params);
    $deleted_stats = $deleted_stats_stmt->fetch();
    $stats['deleted_clients'] = $deleted_stats['deleted_count'] ?? 0;

    // Get active clients assigned to this staff
    $clients_sql = "SELECT * FROM clients WHERE ";
    $clients_params = [];
    if ($is_admin) {
        $clients_sql .= "id NOT IN (SELECT original_id FROM deleted_clients)";
    } else {
        $clients_sql .= "staff_id = ? AND id NOT IN (SELECT original_id FROM deleted_clients)";
        $clients_params[] = $staff_id;
    }
    $clients_sql .= " ORDER BY created_at DESC";
    $clients_stmt = $conn->prepare($clients_sql);
    $clients_stmt->execute($clients_params);
    $clients = $clients_stmt->fetchAll();

    // Get deleted clients deleted by this staff
    $deleted_clients_sql = "SELECT * FROM deleted_clients";
    $deleted_clients_params = [];
    if (!$is_admin) {
        $deleted_clients_sql .= " WHERE staff_id = ?";
        $deleted_clients_params[] = $staff_id;
    }
    $deleted_clients_sql .= " ORDER BY deleted_at DESC";
    $deleted_clients_stmt = $conn->prepare($deleted_clients_sql);
    $deleted_clients_stmt->execute($deleted_clients_params);
    $deleted_clients = $deleted_clients_stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    // For debugging
    error_log("Database error in user-management-staff.php: " . $e->getMessage());
}
require_once 'navigation.php';
$nav_links_array = getNavigationLinks($user_role, 'user-management-staff.php');
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
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/user-management-staff.css">
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
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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
    </style>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <div class="header">
                <div class="page-title">
                    <h1>Client Management</h1>
                    <p>Manage your clients and their nutritional profiles</p>
                </div>

                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search clients..." id="searchInput" class="global-search"
                            data-target=".user-table tbody tr, .client-card">
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

            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_clients']; ?></h3>
                        <p>Total Clients</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon staff">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['active_clients']; ?></h3>
                        <p>Active Clients</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon admins">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['new_this_month']; ?></h3>
                        <p>New This Month</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #e74c3c;">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['deleted_clients']; ?></h3>
                        <p>Deleted Clients</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <a href="?tab=active_clients"
                    class="tab-btn <?php echo $current_tab === 'active_clients' ? 'active' : ''; ?>">
                    Active Clients
                </a>
                <a href="?tab=delete_history"
                    class="tab-btn <?php echo $current_tab === 'delete_history' ? 'active' : ''; ?>">
                    Delete History
                </a>
            </div>
            <!-- Client Management Section -->
            <div class="management-section">
                <!-- Active Clients Tab -->
                <div id="active-clients-tab"
                    class="tab-content <?php echo $current_tab === 'active_clients' ? 'active' : ''; ?>">
                    <div class="section-header">
                        <h2><i class="fas fa-user-injured"></i> My Clients</h2>
                        <button class="btn btn-primary" id="addClientBtn" onclick="openModal()">
                            <i class="fas fa-user-plus"></i> Add New Client
                        </button>
                    </div>

                    </div>

                    <!-- Desktop View Table -->
                    <div class="table-container table-responsive desktop-view">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Contact</th>
                                    <th>Health Metrics</th>
                                    <th>Health Info</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client):
                                    // Calculate BMI
                                    $bmi = $client['height'] > 0 ? $client['weight'] / (($client['height'] / 100) * ($client['height'] / 100)) : 0;
                                    $bmi_category = '';
                                    if ($bmi < 18.5)
                                        $bmi_category = 'underweight';
                                    elseif ($bmi < 25)
                                        $bmi_category = 'normal';
                                    elseif ($bmi < 30)
                                        $bmi_category = 'overweight';
                                    else
                                        $bmi_category = 'obese';
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div class="user-avatar"
                                                    style="width: 32px; height: 32px; font-size: 12px;">
                                                    <?php echo getInitials($client['name']); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($client['name']); ?>
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--gray);">
                                                        <?php echo $client['age'] ?? 'N/A'; ?>y •
                                                        <?php echo ucfirst($client['gender'] ?? 'Not set'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.85rem;">
                                                <?php echo htmlspecialchars($client['email']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--gray);">
                                                <?php echo $client['phone'] ?: 'No phone'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="health-metrics">
                                                <div class="metric-item">
                                                    <div class="metric-value"><?php echo $client['weight'] ?? 'N/A'; ?>kg
                                                    </div>
                                                    <div class="metric-label">Weight</div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-value"><?php echo $client['height'] ?? 'N/A'; ?>cm
                                                    </div>
                                                    <div class="metric-label">Height</div>
                                                </div>
                                                <div class="metric-item">
                                                    <div class="metric-value"><?php echo number_format($bmi, 1); ?></div>
                                                    <div class="metric-label">
                                                        BMI <span class="bmi-indicator bmi-<?php echo $bmi_category; ?>">
                                                            <?php echo substr(ucfirst($bmi_category), 0, 1); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="client-info-compact">
                                                <div><strong>C:</strong>
                                                    <?php echo $client['health_conditions'] ? substr($client['health_conditions'], 0, 20) . '...' : 'None'; ?>
                                                </div>
                                                <div><strong>R:</strong>
                                                    <?php echo $client['dietary_restrictions'] ? substr($client['dietary_restrictions'], 0, 20) . '...' : 'None'; ?>
                                                </div>
                                                <div><strong>G:</strong>
                                                    <?php echo $client['goals'] ? substr($client['goals'], 0, 20) . '...' : 'Not set'; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_client_status">
                                                <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                                <button type="submit"
                                                    class="status-toggle status-<?php echo $client['status']; ?>">
                                                    <?php echo ucfirst($client['status']); ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="user-actions">
                                                <button class="action-btn edit-btn" title="Edit Client"
                                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($client)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn view-btn" title="View Health Plan"
                                                    onclick="viewHealthPlan(<?php echo $client['id']; ?>)">
                                                    <i class="fas fa-file-medical"></i>
                                                </button>

                                                <form method="POST" style="display: inline;"
                                                    onsubmit="return confirmDelete()">
                                                    <input type="hidden" name="action" value="delete_client">
                                                    <input type="hidden" name="client_id"
                                                        value="<?php echo $client['id']; ?>">
                                                    <button type="submit" class="action-btn delete-btn"
                                                        title="Delete Client">
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

                    <!-- Mobile View Card List -->
                    <div class="mobile-client-cards mobile-view">
                        <button class="btn btn-primary mobile-add-btn" onclick="document.getElementById('addClientBtn').click();">
                            <i class="fas fa-plus"></i> Add New Client
                        </button>
                        <?php if (empty($clients)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-friends"></i>
                                <h3>No clients found</h3>
                                <p>Start by adding your first client</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($clients as $client):
                                $bmi = $client['height'] > 0 ? $client['weight'] / (($client['height'] / 100) * ($client['height'] / 100)) : 0;
                                $bmi_category = '';
                                if ($bmi < 18.5)
                                    $bmi_category = 'underweight';
                                elseif ($bmi < 25)
                                    $bmi_category = 'normal';
                                elseif ($bmi < 30)
                                    $bmi_category = 'overweight';
                                else
                                    $bmi_category = 'obese';
                                ?>
                                <div class="client-card">
                                    <div class="card-header">
                                        <div class="user-avatar">
                                            <?php echo getInitials($client['name']); ?>
                                        </div>
                                        <div class="user-info">
                                            <h3><?php echo htmlspecialchars($client['name']); ?></h3>
                                            <span class="client-meta"><?php echo $client['age'] ?? 'N/A'; ?>y •
                                                <?php echo ucfirst($client['gender'] ?? 'Not set'); ?></span>
                                        </div>
                                        <div class="card-status">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_client_status">
                                                <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                                <button type="submit" class="mobile-status status-<?php echo $client['status']; ?>">
                                                    <?php echo ucfirst($client['status']); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="card-metrics">
                                        <div class="metric-box">
                                            <span class="value"><?php echo $client['weight'] ?? 'N/A'; ?> <small>kg</small></span>
                                            <span class="label">Weight</span>
                                        </div>
                                        <div class="metric-box">
                                            <span class="value"><?php echo $client['height'] ?? 'N/A'; ?> <small>cm</small></span>
                                            <span class="label">Height</span>
                                        </div>
                                        <div class="metric-box">
                                            <span class="value"><?php echo number_format($bmi, 1); ?></span>
                                            <span class="label">BMI <span
                                                    class="bmi-tag bmi-<?php echo $bmi_category; ?>"><?php echo substr(ucfirst($bmi_category), 0, 1); ?></span></span>
                                        </div>
                                    </div>

                                    <details class="card-details">
                                        <summary>
                                            <span><i class="fas fa-info-circle"></i> View Health & Contact Details</span>
                                            <i class="fas fa-chevron-down toggle-icon"></i>
                                        </summary>
                                        <div class="details-content">
                                            <div class="info-section">
                                                <h4>Health Profile</h4>
                                                <div class="detail-row">
                                                    <strong>Conditions:</strong>
                                                    <span><?php echo $client['health_conditions'] ?: 'None'; ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <strong>Restrictions:</strong>
                                                    <span><?php echo $client['dietary_restrictions'] ?: 'None'; ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <strong>Goals:</strong>
                                                    <span><?php echo $client['goals'] ?: 'Not set'; ?></span>
                                                </div>
                                            </div>
                                            <div class="info-section">
                                                <h4>Contact & Address</h4>
                                                <div class="detail-row">
                                                    <strong>Email:</strong>
                                                    <span><?php echo htmlspecialchars($client['email']); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <strong>Phone:</strong>
                                                    <span><?php echo $client['phone'] ?: 'No phone'; ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <strong>Address:</strong>
                                                    <span><?php echo $client['address'] ? "{$client['address']}, {$client['city']}" : 'Not provided'; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </details>

                                    <div class="card-footer">
                                        <button class="footer-btn edit"
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($client)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="footer-btn plan" onclick="viewHealthPlan(<?php echo $client['id']; ?>)">
                                            <i class="fas fa-file-medical"></i> Plan
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="action" value="delete_client">
                                            <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                            <button type="submit" class="footer-btn delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Delete History Tab -->
                <div id="delete-history-tab"
                    class="tab-content <?php echo $current_tab === 'delete_history' ? 'active' : ''; ?>">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Delete History</h2>
                        <p>Clients deleted by you. You can restore them here.</p>
                    </div>

                    <?php if (empty($deleted_clients)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
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
                                <tbody>
                                    <?php foreach ($deleted_clients as $deleted_client): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div class="user-avatar"
                                                        style="width: 32px; height: 32px; font-size: 12px;">
                                                        <?php echo getInitials($deleted_client['name']); ?>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 600; font-size: 0.9rem;">
                                                            <?php echo htmlspecialchars($deleted_client['name']); ?>
                                                        </div>
                                                        <div style="font-size: 0.75rem; color: var(--gray);">
                                                            ID: <?php echo $deleted_client['original_id']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($deleted_client['email']); ?></td>
                                            <td><?php echo $deleted_client['phone'] ?: 'N/A'; ?></td>
                                            <td>
                                                <?php echo $deleted_client['age'] ?? 'N/A'; ?>y •
                                                <?php echo ucfirst($deleted_client['gender'] ?? 'N/A'); ?>
                                            </td>
                                            <td>
                                                <div class="deleted-by">
                                                    <div class="deleted-by-avatar"><?php echo getInitials($user_name); ?></div>
                                                    <span><?php echo ucfirst($deleted_client['deleted_by']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($deleted_client['deleted_at'])); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="restore_client">
                                                    <input type="hidden" name="deleted_client_id"
                                                        value="<?php echo $deleted_client['id']; ?>">
                                                    <button type="submit" class="restore-btn" onclick="return confirmRestore()">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add Client Modal -->
                <div class="modal" id="addClientModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Add New Client</h2>
                            <button class="close-btn" onclick="closeModal()">&times;</button>
                        </div>
                        <form method="POST" id="addClientForm">
                            <input type="hidden" name="action" value="add_client">

                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="account_password">Account Password (optional)</label>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="password" id="account_password" name="account_password"
                                        class="form-control" placeholder="Set initial account password" style="flex:1;">
                                    <button type="button" class="btn btn-outline" id="toggle_account_password"
                                        title="Show/Hide Password"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="account_confirm_password">Confirm Password</label>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="password" id="account_confirm_password" name="account_confirm_password"
                                        class="form-control" placeholder="Re-enter password" style="flex:1;">
                                    <button type="button" class="btn btn-outline" id="toggle_account_confirm_password"
                                        title="Show/Hide Password"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                    placeholder="+63 987-654-2100">
                            </div>

                            <!-- Address Fields -->
                            <div class="form-group">
                                <label for="address">Street Address</label>
                                <input type="text" id="address" name="address" class="form-control"
                                    placeholder="123 Main Street">
                            </div>

                            <div class="client-info-grid">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city" class="form-control"
                                        placeholder="Los Banos">
                                </div>

                                <div class="form-group">
                                    <label for="state">State/Province</label>
                                    <input type="text" id="state" name="state" class="form-control"
                                        placeholder="Laguna">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="zip_code">ZIP/Postal Code</label>
                                <input type="text" id="zip_code" name="zip_code" class="form-control"
                                    placeholder="0000">
                            </div>

                            <div class="client-info-grid">
                                <div class="form-group">
                                    <label for="age">Age</label>
                                    <input type="number" id="age" name="age" class="form-control" min="1" max="100"
                                        placeholder="+99">
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" class="form-control">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="client-info-grid">
                                <div class="form-group">
                                    <label for="weight">Weight (kg)</label>
                                    <input type="number" id="weight" name="weight" class="form-control" step="0.1"
                                        min="1">
                                </div>

                                <div class="form-group">
                                    <label for="height">Height (cm)</label>
                                    <input type="number" id="height" name="height" class="form-control" step="0.1"
                                        min="1">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="waist_circumference">Waist Circumference (cm)</label>
                                <input type="number" class="form-control" id="waist_circumference"
                                    name="waist_circumference" step="0.1" min="0" placeholder="Enter waist measurement">
                            </div>

                            <div class="form-group">
                                <label for="hip_circumference">Hip Circumference (cm)</label>
                                <input type="number" class="form-control" id="hip_circumference"
                                    name="hip_circumference" step="0.1" min="0" placeholder="Enter hip measurement">
                            </div>

                            <div class="form-group">
                                <label for="health_conditions">Health Conditions</label>
                                <textarea id="health_conditions" name="health_conditions" class="form-control" rows="2"
                                    placeholder="e.g., Diabetes, Hypertension"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="dietary_restrictions">Dietary Restrictions</label>
                                <textarea id="dietary_restrictions" name="dietary_restrictions" class="form-control"
                                    rows="2" placeholder="e.g., Vegetarian, Gluten-free"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="goals">Health/Nutrition Goals</label>
                                <textarea id="goals" name="goals" class="form-control" rows="2"
                                    placeholder="e.g., Weight loss, Muscle gain, Better digestion"></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Client</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Client Modal -->
                <div class="modal" id="editClientModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Edit Client Information</h2>
                            <button class="close-btn" onclick="closeEditModal()">&times;</button>
                        </div>
                        <form method="POST" id="editClientForm">
                            <input type="hidden" name="action" value="update_client">
                            <input type="hidden" id="edit_client_id" name="client_id">

                            <!-- Basic Info Section -->
                            <div class="form-section">
                                <h4>Basic Information</h4>
                                <div class="client-info-grid">
                                    <div class="form-group">
                                        <label for="edit_name">Full Name</label>
                                        <input type="text" id="edit_name" name="name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_email">Email</label>
                                        <input type="email" id="edit_email" name="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="client-info-grid">
                                    <div class="form-group">
                                        <label for="edit_phone">Phone</label>
                                        <input type="tel" id="edit_phone" name="phone" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_age">Age</label>
                                        <input type="number" id="edit_age" name="age" class="form-control" min="1"
                                            max="120">
                                    </div>
                                </div>

                                <div class="client-info-grid">
                                    <div class="form-group">
                                        <label for="edit_weight">Weight (kg)</label>
                                        <input type="number" id="edit_weight" name="weight" class="form-control"
                                            step="0.1" min="1">
                                    </div>

                                    <div class="form-group">
                                        <label for="edit_height">Height (cm)</label>
                                        <input type="number" id="edit_height" name="height" class="form-control"
                                            step="0.1" min="1">
                                    </div>
                                </div>
                            </div>

                            <!-- Address Section -->
                            <div class="form-section">
                                <h4>Address Information</h4>
                                <div class="form-group">
                                    <label for="edit_address">Street Address</label>
                                    <input type="text" id="edit_address" name="address" class="form-control">
                                </div>

                                <div class="client-info-grid">
                                    <div class="form-group">
                                        <label for="edit_city">City</label>
                                        <input type="text" id="edit_city" name="city" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="edit_state">State/Province</label>
                                        <input type="text" id="edit_state" name="state" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="edit_zip_code">ZIP/Postal Code</label>
                                    <input type="text" id="edit_zip_code" name="zip_code" class="form-control">
                                </div>
                            </div>

                            <!-- Health fields -->
                            <div class="form-section">
                                <h4>Health Information</h4>
                                <div class="form-group">
                                    <label for="edit_waist_circumference">Waist Circumference (cm)</label>
                                    <input type="number" id="edit_waist_circumference" name="waist_circumference"
                                        class="form-control" step="0.1" min="0">
                                </div>

                                <div class="form-group">
                                    <label for="edit_hip_circumference">Hip Circumference (cm)</label>
                                    <input type="number" id="edit_hip_circumference" name="hip_circumference"
                                        class="form-control" step="0.1" min="0">
                                </div>

                                <div class="form-group">
                                    <label for="edit_health_conditions">Health Conditions</label>
                                    <textarea id="edit_health_conditions" name="health_conditions" class="form-control"
                                        rows="3"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="edit_dietary_restrictions">Dietary Restrictions</label>
                                    <textarea id="edit_dietary_restrictions" name="dietary_restrictions"
                                        class="form-control" rows="3"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="edit_goals">Health/Nutrition Goals</label>
                                    <textarea id="edit_goals" name="goals" class="form-control" rows="3"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="edit_notes">Dietician Notes</label>
                                    <textarea id="edit_notes" name="notes" class="form-control" rows="4"
                                        placeholder="Track progress, recommendations, etc."></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Client</button>
                            </div>
                        </form>
                    </div>
                </div>
        </div>
    </div>

    <!-- Essential Scripts for Client Management -->
    <script>
        const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';

        // Tab Switching Logic
        function showTab(tabId) {
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);

            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabId + '-tab').classList.add('active');
            
            // Set active button
            event.currentTarget.classList.add('active');
        }

        // Modal Logic
        function openModal() {
            document.getElementById('addClientModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('addClientModal').style.display = 'none';
        }

        function openEditModal(client) {
            document.getElementById('edit_client_id').value = client.id;
            document.getElementById('edit_name').value = client.name || '';
            document.getElementById('edit_email').value = client.email || '';
            document.getElementById('edit_phone').value = client.phone || '';
            document.getElementById('edit_age').value = client.age || '';
            document.getElementById('edit_weight').value = client.weight || '';
            document.getElementById('edit_height').value = client.height || '';
            document.getElementById('edit_waist_circumference').value = client.waist_circumference || '';
            document.getElementById('edit_hip_circumference').value = client.hip_circumference || '';
            document.getElementById('edit_address').value = client.address || '';
            document.getElementById('edit_city').value = client.city || '';
            document.getElementById('edit_state').value = client.state || '';
            document.getElementById('edit_zip_code').value = client.zip_code || '';
            document.getElementById('edit_health_conditions').value = client.health_conditions || '';
            document.getElementById('edit_dietary_restrictions').value = client.dietary_restrictions || '';
            document.getElementById('edit_goals').value = client.goals || '';
            document.getElementById('edit_notes').value = client.notes || '';
            
            document.getElementById('editClientModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editClientModal').style.display = 'none';
        }

        function viewHealthPlan(clientId) {
            window.location.href = `anthropometric-information.php?client_id=${clientId}&tab=food-tracker`;
        }

        function confirmRestore() {
            return confirm("Are you sure you want to restore this client?");
        }

        // Close modals on outside click
        window.onclick = function(event) {
            const addModal = document.getElementById('addClientModal');
            const editModal = document.getElementById('editClientModal');
            if (event.target == addModal) closeModal();
            if (event.target == editModal) closeEditModal();
        }

        // Password Toggles
        document.getElementById('toggle_account_password')?.addEventListener('click', function() {
            const pwd = document.getElementById('account_password');
            const icon = this.querySelector('i');
            if(pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });

        document.getElementById('toggle_account_confirm_password')?.addEventListener('click', function() {
            const pwd = document.getElementById('account_confirm_password');
            const icon = this.querySelector('i');
            if(pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    </script>
</body>

</html>
