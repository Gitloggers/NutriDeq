<?php
session_start();
require_once 'database.php';

$database = new Database();
$pdo = $database->getConnection();

// Check if user is logged in
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

$user_role = $_SESSION['user_role'] ?? 'regular';
$is_admin = ($user_role === 'admin');
$is_staff = ($user_role === 'staff');

// Check if viewing a specific client's data (for staff/admin)
if (isset($_GET['client_id']) && ($is_staff || $is_admin)) {
    $client_id = $_GET['client_id'];

    // Verify this client belongs to the logged-in staff member OR user is admin
    $check_sql = "SELECT * FROM clients WHERE id = ?";
    $params = [$client_id];

    if (!$is_admin) {
        $check_sql .= " AND staff_id = ?";
        $params[] = $_SESSION['user_id'];
    }

    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute($params);
    $client = $check_stmt->fetch();

    if (!$client) {
        $error = "Client not found or you don't have permission to view this client.";
        unset($_SESSION['viewing_client']);
        unset($_SESSION['client_name']);
        $viewing_client_name = null;
    } else {
        // Set client data for viewing
        $_SESSION['viewing_client'] = $client_id;
        $_SESSION['client_name'] = $client['name'];
        $viewing_client_name = $client['name'];
    }
} else {
    // Regular user viewing their own data
    unset($_SESSION['viewing_client']);
    unset($_SESSION['client_name']);
    $viewing_client_name = null;
}

$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['user_name'] ?? 'Staff User';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $client_id_post = $_POST['client_id'] ?? null;
    if ($client_id_post && ($is_staff || $is_admin)) {
        $verify_sql = "SELECT * FROM clients WHERE id = ?";
        $verify_params = [$client_id_post];
        if (!$is_admin) {
            $verify_sql .= " AND staff_id = ?";
            $verify_params[] = $staff_id;
        }
        $verify = $pdo->prepare($verify_sql);
        $verify->execute($verify_params);
        $clientRow = $verify->fetch();

        if ($clientRow) {
            $user_q = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $user_q->execute([$clientRow['email']]);
            $userRow = $user_q->fetch();
            $uid = $userRow['id'] ?? null;
            if ($_POST['action'] === 'add_food_entry' && $uid) {
                $meal_type = trim($_POST['meal_type'] ?? 'custom');
                $food_name = trim($_POST['food_name'] ?? '');
                $calories = (float) ($_POST['calories'] ?? 0);
                $protein = (float) ($_POST['protein'] ?? 0);
                $carbs = (float) ($_POST['carbs'] ?? 0);
                $fat = (float) ($_POST['fat'] ?? 0);
                if (!empty($food_name)) {
                    $ins = $pdo->prepare("INSERT INTO food_tracking (user_id, food_name, calories, protein, carbs, fat, meal_type, tracking_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())");
                    $ins->execute([$uid, $food_name, $calories, $protein, $carbs, $fat, $meal_type]);
                }
                header("Location: anthropometric-information.php?client_id=" . urlencode($client_id_post) . "&tab=food-tracker");
                exit();
            }
            if ($_POST['action'] === 'delete_food_entry' && $uid) {
                $entry_id = (int) ($_POST['entry_id'] ?? 0);
                if ($entry_id > 0) {
                    $del = $pdo->prepare("DELETE FROM food_tracking WHERE id = ? AND user_id = ?");
                    $del->execute([$entry_id, $uid]);
                }
                header("Location: anthropometric-information.php?client_id=" . urlencode($client_id_post) . "&tab=food-tracker");
                exit();
            }
            if ($_POST['action'] === 'update_body_stats') {
                $weight = $_POST['weight'] ?? null;
                $height = $_POST['height'] ?? null;
                $waist = $_POST['waist_circumference'] ?? null;
                $hip = $_POST['hip_circumference'] ?? null;

                $upd_sql = "UPDATE clients SET weight = ?, height = ?, waist_circumference = ?, hip_circumference = ?, updated_at = NOW() WHERE id = ?";
                $upd_params = [$weight, $height, $waist, $hip, $client_id_post];
                if (!$is_admin) {
                    $upd_sql .= " AND staff_id = ?";
                    $upd_params[] = $staff_id;
                }

                $upd = $pdo->prepare($upd_sql);
                $upd->execute($upd_params);
                header("Location: anthropometric-information.php?client_id=" . urlencode($client_id_post) . "&tab=body-stats");
                exit();
            }
        }
    }
}
// Get staff's clients for the tracker (or all for admin)
$clients_sql = "
    SELECT id, name, email, phone, address, city, state, zip_code, age, date_of_birth, gender, weight, height, 
           waist_circumference, hip_circumference, health_conditions, dietary_restrictions, goals, notes, status, created_at
    FROM clients 
    WHERE status = 'active'
";
$clients_params = [];
if (!$is_admin && $is_staff) {
    $clients_sql .= " AND staff_id = ?";
    $clients_params[] = $staff_id;
}
$clients_sql .= " ORDER BY name";

$clients_query = $pdo->prepare($clients_sql);
$clients_query->execute($clients_params);
$clients = $clients_query->fetchAll(PDO::FETCH_ASSOC);

// Get selected client (if any)
$selected_client_id = $_GET['client_id'] ?? ($clients[0]['id'] ?? null);
$selected_client = null;

if ($selected_client_id) {
    $client_sql = "
        SELECT id, name, email, phone, address, city, state, zip_code, age, date_of_birth, gender, weight, height, 
               waist_circumference, hip_circumference, health_conditions, dietary_restrictions, goals, notes, status, created_at
        FROM clients 
        WHERE id = ?
    ";
    $client_params = [$selected_client_id];

    if (!$is_admin && $is_staff) {
        $client_sql .= " AND staff_id = ?";
        $client_params[] = $staff_id;
    }

    $client_query = $pdo->prepare($client_sql);
    $client_query->execute($client_params);
    $selected_client = $client_query->fetch(PDO::FETCH_ASSOC);
}

// Fetch food items from database using the correct table structure
$food_items = [];
try {
    $food_query = $pdo->prepare("
        SELECT 
            id,
            food_name,
            food_group as group_type,
            serving_size,
            calories as energy,
            protein,
            fat,
            carbs as carbohydrates,
            exchanges
        FROM food_items 
        ORDER BY food_group, food_name
    ");
    $food_query->execute();
    $food_items = $food_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error for debugging
    error_log("Food items query error: " . $e->getMessage());
    $food_items = [];
}

// Organize food items by group for easier access
$food_items_by_group = [];
foreach ($food_items as $item) {
    $group_type = $item['group_type'] ?? 'other';
    if (!isset($food_items_by_group[$group_type])) {
        $food_items_by_group[$group_type] = [];
    }
    $food_items_by_group[$group_type][] = $item;
}

// Initialize variables
$food_entries = [];
$calories_today = 0;
$client_user_id = null;

// Get client data
if ($selected_client) {
    // TEMPORARY FIX: Get user_id from users table using email
    $user_query = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $user_query->execute([$selected_client['email']]);
    $user_data = $user_query->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $client_user_id = $user_data['id'];

        // Get food tracking for today
        $food_today = $pdo->prepare("
            SELECT * FROM food_tracking 
            WHERE user_id = ? AND tracking_date = CURDATE()
            ORDER BY meal_type
        ");
        $food_today->execute([$client_user_id]);
        $food_entries = $food_today->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total calories
        $calories_today = 0;
        foreach ($food_entries as $food) {
            $calories_today += $food['calories'];
        }
    }
}

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

$user_initials = getInitials($staff_name);

// Role-based navigation links
require_once 'navigation.php';
$nav_links_array = getNavigationLinks($_SESSION['user_role'], 'anthropometric-information.php');
// Convert to simple array for loop if necessary, but the loop below (lines 260+) expects an array of associative arrays.
// getNavigationLinks returns an associative array where keys are filenames.
// The loop does: foreach ($nav_links as $link)
// This works perfectly as it will iterate over the values of the associative array returned by getNavigationLinks.
$nav_links = $nav_links_array;

// Check for active tab in session or URL
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
    $_SESSION['active_health_tracker_tab'] = $active_tab;
} else {
    $active_tab = $_SESSION['active_health_tracker_tab'] ?? 'personal-info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriDeq – Anthropometric Information</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Choices.js for searchable dropdowns -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <!-- Core styles -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/premium-management.css">
    <!-- Page-specific premium styles -->
    <link rel="stylesheet" href="css/anthropometric-premium.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <script>const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';</script>
</head>

<body>
<div class="main-layout">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-container ai-page">

            <?php
            // ── Pre-compute display values ──────────────────────────────
            $sc = $selected_client;
            $bmi_val   = 0;
            $bmi_label = 'N/A';
            $bmi_class = 'neutral';
            $whr_val   = 'N/A';
            $whr_risk  = 'N/A';
            if ($sc) {
                if (!empty($sc['weight']) && !empty($sc['height']) && $sc['height'] > 0) {
                    $hm = $sc['height'] / 100;
                    $bmi_val = round($sc['weight'] / ($hm * $hm), 1);
                    if ($bmi_val < 18.5)      { $bmi_label = 'Underweight'; $bmi_class = 'info'; }
                    elseif ($bmi_val < 25)    { $bmi_label = 'Normal';      $bmi_class = 'normal'; }
                    elseif ($bmi_val < 30)    { $bmi_label = 'Overweight';  $bmi_class = 'warn'; }
                    else                      { $bmi_label = 'Obese';       $bmi_class = 'danger'; }
                }
                if (!empty($sc['waist_circumference']) && !empty($sc['hip_circumference']) && $sc['hip_circumference'] > 0) {
                    $whr_val  = number_format($sc['waist_circumference'] / $sc['hip_circumference'], 2);
                    $gender   = $sc['gender'] ?? '';
                    $whr_risk = ($gender === 'male') ? (($sc['waist_circumference'] / $sc['hip_circumference'] <= 0.9) ? 'Low Risk' : 'High Risk')
                                                     : (($sc['waist_circumference'] / $sc['hip_circumference'] <= 0.85) ? 'Low Risk' : 'High Risk');
                }
                $client_initials = getInitials($sc['name']);
                $optimal_low = $optimal_high = 0;
                if (!empty($sc['height'])) {
                    $hm2 = $sc['height'] / 100;
                    $optimal_low  = number_format(18.5 * $hm2 * $hm2, 1);
                    $optimal_high = number_format(24.9 * $hm2 * $hm2, 1);
                }
            }
            ?>

            <!-- ═══ HERO BANNER ═══ -->
            <?php if ($selected_client): ?>
            <div class="ai-hero stagger d-1">
                <div class="ai-hero-avatar"><?= htmlspecialchars($client_initials) ?></div>
                <div class="ai-hero-info">
                    <h1 class="ai-hero-name"><?= htmlspecialchars($sc['name']) ?></h1>
                    <p class="ai-hero-subtitle">
                        <i class="fas fa-id-card"></i> <?= $sc['age'] ? $sc['age'].' years' : 'Age not set' ?>
                        <span class="ai-sep">·</span> 
                        <i class="fas fa-venus-mars"></i> <?= $sc['gender'] ? ucfirst($sc['gender']) : 'Gender unknown' ?>
                        <span class="ai-sep">·</span>
                        <i class="fas fa-location-dot"></i> <?= $sc['city'] ? htmlspecialchars($sc['city']) : 'Location not set' ?>
                    </p>
                    <div class="ai-hero-badges">
                        <div class="ai-badge"><i class="fas fa-weight-scale"></i> <?= $sc['weight'] ? $sc['weight'].' kg' : 'Weight pending' ?></div>
                        <div class="ai-badge"><i class="fas fa-ruler-vertical"></i> <?= $sc['height'] ? $sc['height'].' cm' : 'Height pending' ?></div>
                        <?php if ($bmi_val > 0): ?>
                        <div class="ai-badge ai-badge-bmi bmi-<?= $bmi_class ?>"><i class="fas fa-chart-simple"></i> BMI <?= $bmi_val ?> — <?= $bmi_label ?></div>
                        <?php endif; ?>
                        <div class="ai-badge" style="background:#dcfce7; color:#166534; border-color:#bbf7d0;"><i class="fas fa-circle-check"></i> Active Case</div>
                    </div>
                </div>
                <div class="ai-hero-actions">
                    <?php if ($is_staff || $is_admin): ?>
                    <a href="user-management-staff.php" class="ai-btn-back">
                        <i class="fas fa-chevron-left"></i> Client List
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="ai-hero ai-hero-empty stagger d-1">
                <div class="ai-hero-avatar" style="background:rgba(255,255,255,0.1); border: 2px dashed rgba(255,255,255,0.3);">
                    <i class="fas fa-users-medical" style="font-size:1.6rem; opacity:0.8;"></i>
                </div>
                <div class="ai-hero-info">
                    <h1 class="ai-hero-name">Anthropometric Hub</h1>
                    <p class="ai-hero-subtitle">Comprehensive clinical tracking, food exchange management, and health analytics.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ═══ ALERTS ═══ -->
            <?php if (isset($error)): ?>
            <div class="ai-alert ai-alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="ai-alert ai-alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <!-- ═══ CLIENT SELECTOR ═══ -->
            <div class="ai-selector-bar">
                <div class="ai-selector-icon"><i class="fas fa-user-friends"></i></div>
                <span class="ai-selector-label">Client</span>
                <select onchange="if(this.value) location.href='?client_id='+this.value" id="clientSelect">
                    <option value="">— Choose a Client —</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>" <?= $selected_client_id == $client['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!$selected_client): ?>
            <!-- ═══ EMPTY STATE ═══ -->
            <div class="ai-empty">
                <div class="ai-empty-icon"><i class="fas fa-user-friends"></i></div>
                <h3>No Client Selected</h3>
                <p>Select a client from the dropdown above to view their health tracker.</p>
                <?php if (empty($clients)): ?>
                <p style="margin-top:8px;color:#d97706;font-size:0.85rem;"><i class="fas fa-info-circle"></i> You don't have any active clients assigned yet.</p>
                <?php endif; ?>
            </div>

            <?php else: ?>

            <!-- ═══ STAT RIBBON ═══ -->
            <div class="ai-stat-ribbon">
                <div class="ai-stat-card">
                    <div class="ai-stat-icon"><i class="fas fa-weight"></i></div>
                    <div class="ai-stat-val"><?= $sc['weight'] ? $sc['weight'] : '—' ?></div>
                    <div class="ai-stat-label">Weight (kg)</div>
                    <?php if ($bmi_val > 0): ?>
                    <span class="ai-stat-badge <?= $bmi_class ?>"><?= $bmi_label ?></span>
                    <?php else: ?><span class="ai-stat-badge neutral">Not recorded</span><?php endif; ?>
                </div>
                <div class="ai-stat-card">
                    <div class="ai-stat-icon"><i class="fas fa-ruler-vertical"></i></div>
                    <div class="ai-stat-val"><?= $sc['height'] ? $sc['height'] : '—' ?></div>
                    <div class="ai-stat-label">Height (cm)</div>
                    <span class="ai-stat-badge neutral"><?= ($optimal_low && $optimal_high) ? "Ideal: {$optimal_low}–{$optimal_high} kg" : 'Needs height' ?></span>
                </div>
                <div class="ai-stat-card">
                    <div class="ai-stat-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="ai-stat-val"><?= $bmi_val ?: '—' ?></div>
                    <div class="ai-stat-label">BMI</div>
                    <span class="ai-stat-badge <?= $bmi_class ?>"><?= $bmi_label ?></span>
                </div>
                <div class="ai-stat-card">
                    <div class="ai-stat-icon"><i class="fas fa-circle-dot"></i></div>
                    <div class="ai-stat-val"><?= $whr_val ?></div>
                    <div class="ai-stat-label">Waist-Hip Ratio</div>
                    <span class="ai-stat-badge <?= $whr_risk === 'High Risk' ? 'danger' : ($whr_risk === 'Low Risk' ? '' : 'neutral') ?>"><?= $whr_risk ?></span>
                </div>
            </div>

            <!-- ═══ TABS CONTAINER ═══ -->
            <!-- Keep original .tabs-container wrapper so switchTab() JS still works -->
            <div class="ai-tabs-wrap tabs-container">

                <!-- Tab Rail -->
                <div class="ai-tab-rail stagger d-3">
                    <button class="ai-tab-btn tab <?= $active_tab === 'personal-info' ? 'active' : '' ?>" data-tab="personal-info" onclick="switchTab('personal-info')">
                        <i class="fas fa-address-card"></i> <span>Client Info</span>
                    </button>
                    <button class="ai-tab-btn tab <?= $active_tab === 'food-tracker' ? 'active' : '' ?>" data-tab="food-tracker" onclick="switchTab('food-tracker')">
                        <i class="fas fa-clipboard-list"></i> <span>Food Tracker</span>
                    </button>
                    <button class="ai-tab-btn tab <?= $active_tab === 'body-stats' ? 'active' : '' ?>" data-tab="body-stats" onclick="switchTab('body-stats')">
                        <i class="fas fa-weight-scale"></i> <span>Body Statistics</span>
                    </button>
                    <button class="ai-tab-btn tab <?= $active_tab === 'progress' ? 'active' : '' ?>" data-tab="progress" onclick="switchTab('progress')">
                        <i class="fas fa-utensils-crossed"></i> <span>Meal Planner</span>
                    </button>
                </div>

                <!-- ── TAB 1: Client Info ── -->
                <div class="ai-tab-panel tab-content <?= $active_tab === 'personal-info' ? 'active' : '' ?>" id="personal-info">
                    <div class="ai-section-header">
                        <h2 class="ai-section-title"><i class="fas fa-user-circle"></i> Client Information</h2>
                    </div>

                    <p class="ai-group-label"><i class="fas fa-id-card"></i> Personal Details</p>
                    <div class="ai-form-grid">
                        <div class="ai-field">
                            <label>Full Name</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-user"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['name']) ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>Email Address</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-envelope"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['email']) ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>Phone Number</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-phone"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['phone'] ?? 'Not provided') ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>Age</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-birthday-cake"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['age'] ?? 'Not provided') ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>Gender</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-venus-mars"></i></div>
                            <input type="text" value="<?= htmlspecialchars(ucfirst($sc['gender'] ?? 'Not provided')) ?>" readonly></div>
                        </div>
                    </div>

                    <p class="ai-group-label"><i class="fas fa-map-marker-alt"></i> Address</p>
                    <div class="ai-form-grid">
                        <div class="ai-field ai-field-full">
                            <label>Street Address</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-road"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['address'] ?? 'Not provided') ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>City</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-city"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['city'] ?? 'Not provided') ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>State / Province</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-map"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['state'] ?? 'Not provided') ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>ZIP / Postal Code</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-mailbox"></i></div>
                            <input type="text" value="<?= htmlspecialchars($sc['zip_code'] ?? 'Not provided') ?>" readonly></div>
                        </div>
                    </div>

                    <p class="ai-group-label"><i class="fas fa-heart-pulse"></i> Health Profile</p>
                    <div class="ai-form-grid">
                        <div class="ai-field ai-field-full">
                            <label>Health Conditions</label>
                            <div class="ai-field-inner"><div class="ai-field-icon" style="align-self:flex-start;padding-top:12px;"><i class="fas fa-notes-medical"></i></div>
                            <textarea rows="3" readonly><?= htmlspecialchars($sc['health_conditions'] ?? 'No health conditions reported') ?></textarea></div>
                        </div>
                        <div class="ai-field ai-field-full">
                            <label>Dietary Restrictions</label>
                            <div class="ai-field-inner"><div class="ai-field-icon" style="align-self:flex-start;padding-top:12px;"><i class="fas fa-ban"></i></div>
                            <textarea rows="3" readonly><?= htmlspecialchars($sc['dietary_restrictions'] ?? 'No dietary restrictions') ?></textarea></div>
                        </div>
                        <div class="ai-field ai-field-full">
                            <label>Health / Nutrition Goals</label>
                            <div class="ai-field-inner"><div class="ai-field-icon" style="align-self:flex-start;padding-top:12px;"><i class="fas fa-bullseye"></i></div>
                            <textarea rows="3" readonly><?= htmlspecialchars($sc['goals'] ?? 'No goals set') ?></textarea></div>
                        </div>
                        <div class="ai-field ai-field-full">
                            <label>Dietician Notes</label>
                            <div class="ai-field-inner"><div class="ai-field-icon" style="align-self:flex-start;padding-top:12px;"><i class="fas fa-clipboard"></i></div>
                            <textarea rows="4" readonly><?= htmlspecialchars($sc['notes'] ?? 'No notes yet') ?></textarea></div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB 2: Food Tracker ── -->
                <div class="ai-tab-panel tab-content <?= $active_tab === 'food-tracker' ? 'active' : '' ?>" id="food-tracker">
                    <div class="ai-section-header">
                        <h2 class="ai-section-title"><i class="fas fa-utensils"></i> Daily Food Intake — <?= date('F j, Y') ?></h2>
                    </div>

                    <!-- Food entries list -->
                    <?php if (empty($food_entries)): ?>
                    <div class="ai-empty" style="padding:48px;border-radius:16px;">
                        <div class="ai-empty-icon" style="width:56px;height:56px;font-size:1.4rem;border-radius:16px;"><i class="fas fa-utensils"></i></div>
                        <h3 style="font-size:1rem;">No entries today</h3>
                        <p>Add food items below to start tracking.</p>
                    </div>
                    <?php else: ?>
                    <div class="ai-food-list">
                        <?php foreach ($food_entries as $food):
                            $mealClass = 'meal-'.($food['meal_type'] ?? 'custom');
                            $mealIcon  = match($food['meal_type'] ?? '') { 'breakfast'=>'fa-coffee','lunch'=>'fa-utensils','dinner'=>'fa-moon','snack'=>'fa-apple-alt', default=>'fa-bowl-food' };
                        ?>
                        <div class="ai-food-entry">
                            <div class="ai-food-entry-icon <?= $mealClass ?>"><i class="fas <?= $mealIcon ?>"></i></div>
                            <div class="ai-food-entry-info">
                                <div class="ai-food-entry-name"><?= ucfirst($food['meal_type'] ?? 'Custom') ?>: <?= htmlspecialchars($food['food_name']) ?></div>
                                <div class="ai-food-entry-macros">
                                    <span><b>P</b> <?= $food['protein'] ?? 0 ?>g</span>
                                    <span><b>C</b> <?= $food['carbs'] ?? 0 ?>g</span>
                                    <span><b>F</b> <?= $food['fat'] ?? 0 ?>g</span>
                                </div>
                            </div>
                            <div class="ai-food-entry-kcal"><?= $food['calories'] ?><small> kcal</small></div>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_food_entry">
                                <input type="hidden" name="client_id" value="<?= htmlspecialchars($sc['id']) ?>">
                                <input type="hidden" name="entry_id" value="<?= htmlspecialchars($food['id']) ?>">
                                <button type="submit" class="ai-food-del-btn" title="Remove"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Add food form -->
                    <div class="ai-add-food-form">
                        <h4><i class="fas fa-plus-circle"></i> Add Food Entry</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_food_entry">
                            <input type="hidden" name="client_id" value="<?= htmlspecialchars($sc['id']) ?>">
                            <div class="ai-add-food-inputs">
                                <select name="meal_type">
                                    <option value="breakfast">Breakfast</option>
                                    <option value="lunch">Lunch</option>
                                    <option value="dinner">Dinner</option>
                                    <option value="snack">Snack</option>
                                    <option value="custom" selected>Custom</option>
                                </select>
                                <input type="text" name="food_name" placeholder="Food name" required>
                                <input type="number" step="0.1" name="calories" placeholder="kcal">
                                <input type="number" step="0.1" name="protein" placeholder="Protein g">
                                <input type="number" step="0.1" name="carbs" placeholder="Carbs g">
                                <input type="number" step="0.1" name="fat" placeholder="Fat g">
                                <button type="submit" class="ai-btn-add"><i class="fas fa-plus"></i> Add</button>
                            </div>
                        </form>
                    </div>

                    <!-- Food mini-stats -->
                    <div class="ai-food-stats stagger d-3">
                        <div class="ai-food-stat">
                            <div class="ai-food-stat-icon" style="color:#f97316;"><i class="fas fa-fire-flame-curved"></i></div>
                            <div class="ai-food-stat-val"><?= $calories_today ?></div>
                            <div class="ai-food-stat-label">Calories Today</div>
                        </div>
                        <div class="ai-food-stat">
                            <div class="ai-food-stat-icon" style="color:#10b981;"><i class="fas fa-bowl-food"></i></div>
                            <div class="ai-food-stat-val"><?= count($food_entries) ?></div>
                            <div class="ai-food-stat-label">Meals Logged</div>
                        </div>
                        <div class="ai-food-stat">
                            <div class="ai-food-stat-icon" style="color:#6366f1;"><i class="fas fa-clock-rotate-left"></i></div>
                            <div class="ai-food-stat-val"><?= date('H:i') ?></div>
                            <div class="ai-food-stat-label">Last Update</div>
                        </div>
                        <div class="ai-food-stat">
                            <div class="ai-food-stat-icon" style="color:#d946ef;"><i class="fas fa-signal"></i></div>
                            <div class="ai-food-stat-val" style="color:#10b981;font-size:1rem;">Active</div>
                            <div class="ai-food-stat-label">Tracking Mode</div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB 3: Body Statistics ── -->
                <div class="ai-tab-panel tab-content <?= $active_tab === 'body-stats' ? 'active' : '' ?>" id="body-stats">
                    <div class="ai-section-header">
                        <h2 class="ai-section-title"><i class="fas fa-chart-line"></i> Body Measurements &amp; Statistics</h2>
                    </div>

                    <p class="ai-group-label"><i class="fas fa-eye"></i> Current Measurements (Read-Only)</p>
                    <div class="ai-form-grid">
                        <div class="ai-field">
                            <label>Current Weight</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-weight"></i></div>
                            <input type="text" value="<?= $sc['weight'] ? $sc['weight'].' kg' : 'Not recorded' ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>Height</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-ruler-vertical"></i></div>
                            <input type="text" value="<?= $sc['height'] ? $sc['height'].' cm' : 'Not recorded' ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>BMI</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-chart-bar"></i></div>
                            <input type="text" value="<?= $bmi_val ?: 'Not calculated' ?>" readonly></div>
                            <?php if ($bmi_val): ?><div class="ai-field-hint">Classification: <strong><?= $bmi_label ?></strong></div><?php endif; ?>
                        </div>
                        <div class="ai-field">
                            <label>Optimal Weight Range</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-bullseye"></i></div>
                            <input type="text" value="<?= ($optimal_low && $optimal_high) ? "{$optimal_low} – {$optimal_high} kg" : 'Need height data' ?>" readonly></div>
                            <div class="ai-field-hint">Healthy BMI range: 18.5 – 24.9</div>
                        </div>
                        <div class="ai-field">
                            <label>Waist Circumference</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-circle-dot"></i></div>
                            <input type="text" value="<?= !empty($sc['waist_circumference']) ? $sc['waist_circumference'].' cm' : 'Not recorded' ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>Hip Circumference</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-circle-dot"></i></div>
                            <input type="text" value="<?= !empty($sc['hip_circumference']) ? $sc['hip_circumference'].' cm' : 'Not recorded' ?>" readonly></div>
                        </div>
                        <div class="ai-field">
                            <label>Waist-Hip Ratio</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-arrows-left-right"></i></div>
                            <input type="text" value="<?= $whr_val ?>" readonly></div>
                            <?php if ($whr_risk !== 'N/A'): ?><div class="ai-field-hint">Risk level: <strong><?= $whr_risk ?></strong></div><?php endif; ?>
                        </div>
                        <div class="ai-field">
                            <label>Client Since</label>
                            <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-calendar"></i></div>
                            <input type="text" value="<?= !empty($sc['created_at']) ? date('M j, Y', strtotime($sc['created_at'])) : 'N/A' ?>" readonly></div>
                        </div>
                    </div>

                    <!-- Update form -->
                    <div class="ai-update-section">
                        <h3><i class="fas fa-pen-to-square"></i> Update Body Measurements</h3>
                        <form method="POST" id="updateBodyStatsForm">
                            <input type="hidden" name="action" value="update_body_stats">
                            <input type="hidden" name="client_id" value="<?= htmlspecialchars($sc['id']) ?>">
                            <div class="ai-form-grid">
                                <div class="ai-field">
                                    <label for="upd_weight">Weight (kg)</label>
                                    <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-weight"></i></div>
                                    <input type="number" step="0.1" id="upd_weight" name="weight" value="<?= htmlspecialchars($sc['weight'] ?? '') ?>" placeholder="e.g. 65.0"></div>
                                </div>
                                <div class="ai-field">
                                    <label for="upd_height">Height (cm)</label>
                                    <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-ruler-vertical"></i></div>
                                    <input type="number" step="0.1" id="upd_height" name="height" value="<?= htmlspecialchars($sc['height'] ?? '') ?>" placeholder="e.g. 165"></div>
                                </div>
                                <div class="ai-field">
                                    <label for="upd_waist">Waist (cm)</label>
                                    <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-circle-dot"></i></div>
                                    <input type="number" step="0.1" id="upd_waist" name="waist_circumference" value="<?= htmlspecialchars($sc['waist_circumference'] ?? '') ?>" placeholder="e.g. 82"></div>
                                </div>
                                <div class="ai-field">
                                    <label for="upd_hip">Hip (cm)</label>
                                    <div class="ai-field-inner"><div class="ai-field-icon"><i class="fas fa-circle-dot"></i></div>
                                    <input type="number" step="0.1" id="upd_hip" name="hip_circumference" value="<?= htmlspecialchars($sc['hip_circumference'] ?? '') ?>" placeholder="e.g. 95"></div>
                                </div>
                            </div>
                            <button type="submit" class="ai-btn-save"><i class="fas fa-save"></i> Save Measurements</button>
                        </form>
                    </div>
                </div>

                <!-- ── TAB 4: Meal Planner (Food Exchange List) ── -->
                <div class="ai-tab-panel tab-content <?= $active_tab === 'progress' ? 'active' : '' ?>" id="progress">
                    <div class="ai-meal-header">
                        <h2 class="ai-section-title" style="margin:0;"><i class="fas fa-exchange-alt"></i> Food Exchange List</h2>
                        <button class="ai-btn-save-meal" id="saveMealPlan"><i class="fas fa-save"></i> Save Meal Plan</button>
                    </div>

                    <div class="ai-table-wrap">
                        <table class="ai-exchange-table fel-table">
                            <thead>
                                <tr>
                                    <th width="25%">Food Group &amp; Items</th>
                                    <th width="15%">Exchange</th>
                                    <th width="12%">Weight (g)</th>
                                    <th width="12%">Energy (kcal)</th>
                                    <th width="12%">Protein (g)</th>
                                    <th width="12%">Fat (g)</th>
                                    <th width="12%">CHO (g)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="group-header"><td colspan="7"><i class="fas fa-carrot"></i> <strong>VEGETABLES</strong></td></tr>
                                <tr class="food-item"><td>
                                    <select id="veg-select" class="food-select">
                                        <option value="">Select Vegetable Type</option>
                                        <?php if (!empty($food_items_by_group['vegetable'])): foreach ($food_items_by_group['vegetable'] as $item): ?>
                                        <option value="<?= htmlspecialchars($item['id']) ?>" data-exchange="<?= htmlspecialchars($item['exchanges']??'1') ?>" data-weight="<?= htmlspecialchars($item['serving_size']??'') ?>" data-energy="<?= htmlspecialchars($item['energy']??'0') ?>" data-protein="<?= htmlspecialchars($item['protein']??'0') ?>" data-fat="<?= htmlspecialchars($item['fat']??'0') ?>" data-cho="<?= htmlspecialchars($item['carbohydrates']??'0') ?>"><?= htmlspecialchars($item['food_name']) ?></option>
                                        <?php endforeach; else: ?>
                                        <option value="leafy">Leafy, flower, fruit vegetables</option><option value="root">Root, tuber, bulb vegetables</option><option value="starchy">Starchy vegetables</option>
                                        <?php endif; ?>
                                    </select>
                                </td><td id="vegetable-exchange">-</td><td id="vegetable-weight">-</td><td id="vegetable-energy">-</td><td id="vegetable-protein">-</td><td id="vegetable-fat">-</td><td id="vegetable-cho">-</td></tr>

                                <tr class="group-header"><td colspan="7"><i class="fas fa-apple-alt"></i> <strong>FRUITS</strong></td></tr>
                                <tr class="food-item"><td>
                                    <select id="fruit-select" class="food-select">
                                        <option value="">Select Fruit</option>
                                        <?php if (!empty($food_items_by_group['fruit'])): foreach ($food_items_by_group['fruit'] as $item): ?>
                                        <option value="<?= htmlspecialchars($item['id']) ?>" data-exchange="<?= htmlspecialchars($item['exchanges']??'1') ?>" data-weight="<?= htmlspecialchars($item['serving_size']??'') ?>" data-energy="<?= htmlspecialchars($item['energy']??'0') ?>" data-protein="<?= htmlspecialchars($item['protein']??'0') ?>" data-fat="<?= htmlspecialchars($item['fat']??'0') ?>" data-cho="<?= htmlspecialchars($item['carbohydrates']??'0') ?>"><?= htmlspecialchars($item['food_name']) ?></option>
                                        <?php endforeach; else: ?>
                                        <option value="banana">Banana (saba)</option><option value="mango">Mango</option><option value="apple">Apple</option><option value="orange">Orange</option><option value="papaya">Papaya</option>
                                        <?php endif; ?>
                                    </select>
                                </td><td id="fruit-exchange">-</td><td id="fruit-weight">-</td><td id="fruit-energy">-</td><td id="fruit-protein">-</td><td id="fruit-fat">-</td><td id="fruit-cho">-</td></tr>

                                <tr class="group-header"><td colspan="7"><i class="fas fa-wine-bottle"></i> <strong>MILK</strong></td></tr>
                                <tr class="food-item"><td>
                                    <select id="milk-select" class="food-select">
                                        <option value="">Select Milk Type</option>
                                        <?php if (!empty($food_items_by_group['milk'])): foreach ($food_items_by_group['milk'] as $item): ?>
                                        <option value="<?= htmlspecialchars($item['id']) ?>" data-exchange="<?= htmlspecialchars($item['exchanges']??'1') ?>" data-weight="<?= htmlspecialchars($item['serving_size']??'') ?>" data-energy="<?= htmlspecialchars($item['energy']??'0') ?>" data-protein="<?= htmlspecialchars($item['protein']??'0') ?>" data-fat="<?= htmlspecialchars($item['fat']??'0') ?>" data-cho="<?= htmlspecialchars($item['carbohydrates']??'0') ?>"><?= htmlspecialchars($item['food_name']) ?></option>
                                        <?php endforeach; else: ?>
                                        <option value="whole">Whole, fresh/evap</option><option value="lowfat">Low-fat, fresh</option><option value="nonfat">Non-fat, powdered</option>
                                        <?php endif; ?>
                                    </select>
                                </td><td id="milk-exchange">-</td><td id="milk-weight">-</td><td id="milk-energy">-</td><td id="milk-protein">-</td><td id="milk-fat">-</td><td id="milk-cho">-</td></tr>

                                <tr class="group-header"><td colspan="7"><i class="fas fa-bread-slice"></i> <strong>RICE, RICE SUBSTITUTES &amp; PRODUCTS</strong></td></tr>
                                <tr class="food-item"><td>
                                    <select id="rice-select" class="food-select">
                                        <option value="">Select Rice/Grain</option>
                                        <?php if (!empty($food_items_by_group['rice'])): foreach ($food_items_by_group['rice'] as $item): ?>
                                        <option value="<?= htmlspecialchars($item['id']) ?>" data-exchange="<?= htmlspecialchars($item['exchanges']??'1') ?>" data-weight="<?= htmlspecialchars($item['serving_size']??'') ?>" data-energy="<?= htmlspecialchars($item['energy']??'0') ?>" data-protein="<?= htmlspecialchars($item['protein']??'0') ?>" data-fat="<?= htmlspecialchars($item['fat']??'0') ?>" data-cho="<?= htmlspecialchars($item['carbohydrates']??'0') ?>"><?= htmlspecialchars($item['food_name']) ?></option>
                                        <?php endforeach; else: ?>
                                        <option value="rice_well">Rice, well-milled</option><option value="rice_medium">Rice, medium-milled</option><option value="rice_brown">Rice, brown</option><option value="bread">Bread</option><option value="noodles">Noodles</option>
                                        <?php endif; ?>
                                    </select>
                                </td><td id="rice-exchange">-</td><td id="rice-weight">-</td><td id="rice-energy">-</td><td id="rice-protein">-</td><td id="rice-fat">-</td><td id="rice-cho">-</td></tr>

                                <tr class="group-header"><td colspan="7"><i class="fas fa-drumstick-bite"></i> <strong>MEAT, POULTRY, FISH &amp; PRODUCTS</strong></td></tr>
                                <tr class="food-item"><td>
                                    <select id="meat-select" class="food-select">
                                        <option value="">Select Meat/Fish</option>
                                        <?php if (!empty($food_items_by_group['meat'])): foreach ($food_items_by_group['meat'] as $item): ?>
                                        <option value="<?= htmlspecialchars($item['id']) ?>" data-exchange="<?= htmlspecialchars($item['exchanges']??'1') ?>" data-weight="<?= htmlspecialchars($item['serving_size']??'') ?>" data-energy="<?= htmlspecialchars($item['energy']??'0') ?>" data-protein="<?= htmlspecialchars($item['protein']??'0') ?>" data-fat="<?= htmlspecialchars($item['fat']??'0') ?>" data-cho="<?= htmlspecialchars($item['carbohydrates']??'0') ?>"><?= htmlspecialchars($item['food_name']) ?></option>
                                        <?php endforeach; else: ?>
                                        <option value="lean">Lean meat</option><option value="medium">Medium-fat meat</option><option value="high">High-fat meat</option><option value="fish">Fish, lean</option><option value="fish_medium">Fish, medium-fat</option>
                                        <?php endif; ?>
                                    </select>
                                </td><td id="meat-exchange">-</td><td id="meat-weight">-</td><td id="meat-energy">-</td><td id="meat-protein">-</td><td id="meat-fat">-</td><td id="meat-cho">-</td></tr>

                                <tr class="group-header"><td colspan="7"><i class="fas fa-oil-can"></i> <strong>FATS &amp; OILS</strong></td></tr>
                                <tr class="food-item"><td>
                                    <select id="fat-select" class="food-select">
                                        <option value="">Select Fat/Oil Type</option>
                                        <?php if (!empty($food_items_by_group['fat'])): foreach ($food_items_by_group['fat'] as $item): ?>
                                        <option value="<?= htmlspecialchars($item['id']) ?>" data-exchange="<?= htmlspecialchars($item['exchanges']??'1') ?>" data-weight="<?= htmlspecialchars($item['serving_size']??'') ?>" data-energy="<?= htmlspecialchars($item['energy']??'0') ?>" data-protein="<?= htmlspecialchars($item['protein']??'0') ?>" data-fat="<?= htmlspecialchars($item['fat']??'0') ?>" data-cho="<?= htmlspecialchars($item['carbohydrates']??'0') ?>"><?= htmlspecialchars($item['food_name']) ?></option>
                                        <?php endforeach; else: ?>
                                        <option value="butter">Butter, margarine</option><option value="oil">Cooking oil</option><option value="mayo">Mayonnaise</option>
                                        <?php endif; ?>
                                    </select>
                                </td><td id="fat-exchange">-</td><td id="fat-weight">-</td><td id="fat-energy">-</td><td id="fat-protein">-</td><td id="fat-fat">-</td><td id="fat-cho">-</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Selected Items Summary -->
                    <div class="selected-items-section" style="margin-top:20px;background:#f9fafb;border:1.5px solid #f3f4f6;border-radius:14px;padding:18px 20px;">
                        <h4 style="font-family:'Outfit',sans-serif;font-size:0.9rem;font-weight:800;color:#111827;margin:0 0 12px;display:flex;align-items:center;gap:8px;"><i class="fas fa-list-check" style="color:#059669;"></i> Selected Food Items</h4>
                        <div class="selected-items-list" id="selectedItems">
                            <div class="no-selection" style="color:#9ca3af;font-size:0.85rem;font-style:italic;">No food items selected yet</div>
                        </div>
                    </div>

                    <!-- Nutrition Summary -->
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px;">
                        <div class="ai-food-stat"><div class="ai-food-stat-val" id="total-energy">0</div><div class="ai-food-stat-label">Energy (kcal)</div></div>
                        <div class="ai-food-stat"><div class="ai-food-stat-val" id="total-protein">0</div><div class="ai-food-stat-label">Protein (g)</div></div>
                        <div class="ai-food-stat"><div class="ai-food-stat-val" id="total-fat">0</div><div class="ai-food-stat-label">Fat (g)</div></div>
                        <div class="ai-food-stat"><div class="ai-food-stat-val" id="total-cho">0</div><div class="ai-food-stat-label">CHO (g)</div></div>
                    </div>
                </div>

            </div><!-- /.ai-tabs-wrap -->

            <?php endif; ?>
        </div><!-- /.page-container -->
    </main>
</div><!-- /.main-layout -->
        <script>
            // FNRI FEL Data - MUST BE GLOBAL
            const felData = {
                vegetables: {
                    leafy: { exchange: "1 cup", weight: "100", energy: "25", protein: "2", fat: "0", cho: "5" },
                    root: { exchange: "½ cup", weight: "50", energy: "50", protein: "1", fat: "0", cho: "11" },
                    starchy: { exchange: "½ cup", weight: "80", energy: "70", protein: "2", fat: "0", cho: "15" }
                },
                fruits: {
                    banana: { exchange: "1 piece", weight: "70", energy: "77", protein: "1", fat: "0", cho: "20" },
                    mango: { exchange: "1 piece", weight: "100", energy: "64", protein: "1", fat: "0", cho: "17" },
                    apple: { exchange: "1 piece", weight: "100", energy: "59", protein: "0", fat: "0", cho: "15" },
                    orange: { exchange: "1 piece", weight: "100", energy: "46", protein: "1", fat: "0", cho: "12" },
                    papaya: { exchange: "1 slice", weight: "100", energy: "39", protein: "0", fat: "0", cho: "10" }
                },
                milk: {
                    whole: { exchange: "1 cup", weight: "240", energy: "150", protein: "8", fat: "8", cho: "12" },
                    lowfat: { exchange: "1 cup", weight: "240", energy: "120", protein: "8", fat: "5", cho: "12" },
                    nonfat: { exchange: "3 tbsp", weight: "25", energy: "90", protein: "8", fat: "0", cho: "12" }
                },
                rice: {
                    rice_well: { exchange: "1 cup", weight: "100", energy: "180", protein: "3", fat: "0", cho: "40" },
                    rice_medium: { exchange: "1 cup", weight: "100", energy: "160", protein: "3", fat: "1", cho: "35" },
                    rice_brown: { exchange: "1 cup", weight: "100", energy: "150", protein: "3", fat: "1", cho: "32" },
                    bread: { exchange: "1 slice", weight: "30", energy: "80", protein: "2", fat: "1", cho: "15" },
                    noodles: { exchange: "1 cup", weight: "100", energy: "140", protein: "4", fat: "1", cho: "28" }
                },
                meat: {
                    lean: { exchange: "1 slice", weight: "30", energy: "55", protein: "8", fat: "2", cho: "0" },
                    medium: { exchange: "1 slice", weight: "30", energy: "75", protein: "7", fat: "5", cho: "0" },
                    high: { exchange: "1 slice", weight: "30", energy: "100", protein: "6", fat: "8", cho: "0" },
                    fish: { exchange: "1 slice", weight: "30", energy: "40", protein: "7", fat: "1", cho: "0" },
                    fish_medium: { exchange: "1 slice", weight: "30", energy: "55", protein: "6", fat: "3", cho: "0" }
                },
                fats: {
                    butter: { exchange: "1 tsp", weight: "5", energy: "45", protein: "0", fat: "5", cho: "0" },
                    oil: { exchange: "1 tsp", weight: "5", energy: "45", protein: "0", fat: "5", cho: "0" },
                    mayo: { exchange: "1 tsp", weight: "5", energy: "45", protein: "0", fat: "5", cho: "0" }
                }
            };

            let selectedItems = [];

            // Get active tab function
            function getActiveTab() {
                const activeTab = document.querySelector('.tab.active');
                return activeTab ? activeTab.getAttribute('data-tab') : 'personal-info';
            }

            // Update detail functions (all your update functions remain the same)
            function updateVegetableDetails(select) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value) {
                    const data = {
                        exchange: selectedOption.getAttribute('data-exchange') || '1',
                        weight: selectedOption.getAttribute('data-weight') || '-',
                        energy: selectedOption.getAttribute('data-energy') || '0',
                        protein: selectedOption.getAttribute('data-protein') || '0',
                        fat: selectedOption.getAttribute('data-fat') || '0',
                        cho: selectedOption.getAttribute('data-cho') || '0'
                    };

                    document.getElementById('vegetable-exchange').textContent = data.exchange;
                    document.getElementById('vegetable-weight').textContent = data.weight;
                    document.getElementById('vegetable-energy').textContent = data.energy;
                    document.getElementById('vegetable-protein').textContent = data.protein;
                    document.getElementById('vegetable-fat').textContent = data.fat;
                    document.getElementById('vegetable-cho').textContent = data.cho;

                    addSelectedItem('Vegetables', selectedOption.text, data);
                }
            }

            function updateFruitDetails(select) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value) {
                    const data = {
                        exchange: selectedOption.getAttribute('data-exchange') || '1',
                        weight: selectedOption.getAttribute('data-weight') || '-',
                        energy: selectedOption.getAttribute('data-energy') || '0',
                        protein: selectedOption.getAttribute('data-protein') || '0',
                        fat: selectedOption.getAttribute('data-fat') || '0',
                        cho: selectedOption.getAttribute('data-cho') || '0'
                    };

                    document.getElementById('fruit-exchange').textContent = data.exchange;
                    document.getElementById('fruit-weight').textContent = data.weight;
                    document.getElementById('fruit-energy').textContent = data.energy;
                    document.getElementById('fruit-protein').textContent = data.protein;
                    document.getElementById('fruit-fat').textContent = data.fat;
                    document.getElementById('fruit-cho').textContent = data.cho;

                    addSelectedItem('Fruits', selectedOption.text, data);
                }
            }

            function updateMilkDetails(select) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value) {
                    const data = {
                        exchange: selectedOption.getAttribute('data-exchange') || '1',
                        weight: selectedOption.getAttribute('data-weight') || '-',
                        energy: selectedOption.getAttribute('data-energy') || '0',
                        protein: selectedOption.getAttribute('data-protein') || '0',
                        fat: selectedOption.getAttribute('data-fat') || '0',
                        cho: selectedOption.getAttribute('data-cho') || '0'
                    };

                    document.getElementById('milk-exchange').textContent = data.exchange;
                    document.getElementById('milk-weight').textContent = data.weight;
                    document.getElementById('milk-energy').textContent = data.energy;
                    document.getElementById('milk-protein').textContent = data.protein;
                    document.getElementById('milk-fat').textContent = data.fat;
                    document.getElementById('milk-cho').textContent = data.cho;

                    addSelectedItem('Milk', selectedOption.text, data);
                }
            }

            function updateRiceDetails(select) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value) {
                    const data = {
                        exchange: selectedOption.getAttribute('data-exchange') || '1',
                        weight: selectedOption.getAttribute('data-weight') || '-',
                        energy: selectedOption.getAttribute('data-energy') || '0',
                        protein: selectedOption.getAttribute('data-protein') || '0',
                        fat: selectedOption.getAttribute('data-fat') || '0',
                        cho: selectedOption.getAttribute('data-cho') || '0'
                    };

                    document.getElementById('rice-exchange').textContent = data.exchange;
                    document.getElementById('rice-weight').textContent = data.weight;
                    document.getElementById('rice-energy').textContent = data.energy;
                    document.getElementById('rice-protein').textContent = data.protein;
                    document.getElementById('rice-fat').textContent = data.fat;
                    document.getElementById('rice-cho').textContent = data.cho;

                    addSelectedItem('Rice & Grains', selectedOption.text, data);
                }
            }

            function updateMeatDetails(select) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value) {
                    const data = {
                        exchange: selectedOption.getAttribute('data-exchange') || '1',
                        weight: selectedOption.getAttribute('data-weight') || '-',
                        energy: selectedOption.getAttribute('data-energy') || '0',
                        protein: selectedOption.getAttribute('data-protein') || '0',
                        fat: selectedOption.getAttribute('data-fat') || '0',
                        cho: selectedOption.getAttribute('data-cho') || '0'
                    };

                    document.getElementById('meat-exchange').textContent = data.exchange;
                    document.getElementById('meat-weight').textContent = data.weight;
                    document.getElementById('meat-energy').textContent = data.energy;
                    document.getElementById('meat-protein').textContent = data.protein;
                    document.getElementById('meat-fat').textContent = data.fat;
                    document.getElementById('meat-cho').textContent = data.cho;

                    addSelectedItem('Meat & Fish', selectedOption.text, data);
                }
            }

            function updateFatDetails(select) {
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value) {
                    const data = {
                        exchange: selectedOption.getAttribute('data-exchange') || '1',
                        weight: selectedOption.getAttribute('data-weight') || '-',
                        energy: selectedOption.getAttribute('data-energy') || '0',
                        protein: selectedOption.getAttribute('data-protein') || '0',
                        fat: selectedOption.getAttribute('data-fat') || '0',
                        cho: selectedOption.getAttribute('data-cho') || '0'
                    };

                    document.getElementById('fat-exchange').textContent = data.exchange;
                    document.getElementById('fat-weight').textContent = data.weight;
                    document.getElementById('fat-energy').textContent = data.energy;
                    document.getElementById('fat-protein').textContent = data.protein;
                    document.getElementById('fat-fat').textContent = data.fat;
                    document.getElementById('fat-cho').textContent = data.cho;

                    addSelectedItem('Fats & Oils', selectedOption.text, data);
                }
            }

            // Helper Functions (Global Scope)
            function addSelectedItem(group, name, data) {
                const item = {
                    group,
                    name,
                    data,
                    id: Date.now() + Math.random()
                };
                selectedItems.push(item);
                updateSelectedItemsList();
                updateNutritionSummary();
            }

            function removeSelectedItem(id) {
                selectedItems = selectedItems.filter(item => item.id !== id);
                updateSelectedItemsList();
                updateNutritionSummary();
            }

            function updateSelectedItemsList() {
                const container = document.getElementById('selectedItems');
                if (selectedItems.length === 0) {
                    container.innerHTML = '<div class="no-selection">No food items selected yet</div>';
                    return;
                }
                container.innerHTML = selectedItems.map(item => `
                <div class="selected-item">
                    <div class="selected-item-info">
                        <div class="selected-item-name">${item.name}</div>
                        <div class="selected-item-details">
                            ${item.data.exchange} • ${item.data.energy} kcal • P:${item.data.protein}g F:${item.data.fat}g C:${item.data.cho}g
                        </div>
                    </div>
                    <div class="selected-item-actions">
                        <button class="remove-item" onclick="removeSelectedItem(${item.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            }

            // Global Tab Switching Function — uses both class AND inline display for reliability
            function switchTab(tabId) {
                const allPanels  = document.querySelectorAll('.tabs-container .tab-content');
                const allButtons = document.querySelectorAll('.tabs-container .tab');
                const target     = document.getElementById(tabId);
                const activeBtn  = document.querySelector(`.tabs-container .tab[data-tab="${tabId}"]`);

                if (!target || !activeBtn) {
                    console.warn('switchTab: panel or button not found for', tabId);
                    return;
                }

                // Hide all panels
                allPanels.forEach(p => {
                    p.classList.remove('active');
                    p.style.display = 'none';
                });

                // Deactivate all buttons
                allButtons.forEach(b => b.classList.remove('active'));

                // Show target panel
                target.classList.add('active');
                target.style.display = 'block';

                // Activate target button
                activeBtn.classList.add('active');

                // Persist
                sessionStorage.setItem('activeTab', tabId);
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.replaceState({}, '', url);
            }

            function updateNutritionSummary() {
                const totalEnergy = selectedItems.reduce((sum, item) => sum + parseInt(item.data.energy), 0);
                const totalProtein = selectedItems.reduce((sum, item) => sum + parseInt(item.data.protein), 0);
                const totalFat = selectedItems.reduce((sum, item) => sum + parseInt(item.data.fat), 0);
                const totalCHO = selectedItems.reduce((sum, item) => sum + parseInt(item.data.cho), 0);

                document.getElementById('total-energy').textContent = totalEnergy;
                document.getElementById('total-protein').textContent = totalProtein;
                document.getElementById('total-fat').textContent = totalFat;
                document.getElementById('total-cho').textContent = totalCHO;
            }

            // SINGLE DOMContentLoaded - All functionality here
            document.addEventListener('DOMContentLoaded', function () {

                // Ensure all tab panels start hidden, then activate the right one
                const allPanels = document.querySelectorAll('.tabs-container .tab-content');
                allPanels.forEach(p => { p.style.display = 'none'; p.classList.remove('active'); });

                const urlParams  = new URLSearchParams(window.location.search);
                const urlTab     = urlParams.get('tab');
                const savedTab   = sessionStorage.getItem('activeTab');
                const phpTab     = '<?php echo htmlspecialchars($active_tab); ?>';
                const validTabs  = ['personal-info', 'food-tracker', 'body-stats', 'progress'];

                // Priority: URL param > sessionStorage > PHP default > first tab
                let targetTab = urlTab || savedTab || phpTab || 'personal-info';
                if (!validTabs.includes(targetTab)) targetTab = 'personal-info';

                switchTab(targetTab);

                // Save meal plan functionality
                const saveMealPlanBtn = document.getElementById('saveMealPlan');
                if (saveMealPlanBtn) {
                    saveMealPlanBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (selectedItems.length === 0) {
                            alert('Please select some food items before saving.');
                            return;
                        }

                        const clientId = <?php echo $selected_client ? (int) $selected_client['id'] : 'null'; ?>;
                        const staffId = <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>;

                        if (!clientId) {
                            alert('Error: No client selected.');
                            return;
                        }

                        const mealPlanData = {
                            client_id: clientId,
                            staff_id: staffId,
                            items: selectedItems,
                            total_energy: document.getElementById('total-energy').textContent,
                            total_protein: document.getElementById('total-protein').textContent,
                            total_fat: document.getElementById('total-fat').textContent,
                            total_cho: document.getElementById('total-cho').textContent
                        };

                        saveMealPlanBtn.disabled = true;
                        saveMealPlanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                        fetch(BASE_URL + 'handlers/save_meal_plan.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(mealPlanData)
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert(data.message);
                                    window.location.reload();
                                } else {
                                    alert('Error saving meal plan: ' + data.message);
                                    saveMealPlanBtn.disabled = false;
                                    saveMealPlanBtn.innerHTML = '<i class="fas fa-save"></i> Save Meal Plan';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Error saving meal plan.');
                                saveMealPlanBtn.disabled = false;
                                saveMealPlanBtn.innerHTML = '<i class="fas fa-save"></i> Save Meal Plan';
                            });
                    });
                }

                // Initialize FCT Library Dropdowns
                const fctDropdowns = {
                    vegetable: document.getElementById('veg-select'),
                    fruit: document.getElementById('fruit-select'),
                    milk: document.getElementById('milk-select'),
                    rice: document.getElementById('rice-select'),
                    meat: document.getElementById('meat-select'),
                    fat: document.getElementById('fat-select')
                };

                const choicesInstances = {};
                
                for (const [key, select] of Object.entries(fctDropdowns)) {
                    if (select) {
                        choicesInstances[key] = new Choices(select, {
                            searchEnabled: true,
                            itemSelectText: '',
                            shouldSort: false,
                            placeholder: true
                        });
                    }
                }

                // Fetch data and populate
                fetch(BASE_URL + 'handlers/get_fct_foods.php')
                    .then(r => r.json())
                    .then(res => {
                        if (res.success && res.data) {
                            for (const [key, items] of Object.entries(res.data)) {
                                if (choicesInstances[key] && items.length > 0) {
                                    const choicesOptions = items.map(item => ({
                                        value: item.id.toString(),
                                        label: item.food_name,
                                        customProperties: {
                                            exchange: item.exchange,
                                            weight: item.serving_size,
                                            energy: item.energy,
                                            protein: item.protein,
                                            fat: item.fat,
                                            cho: item.carbohydrates
                                        }
                                    }));
                                    
                                    choicesInstances[key].clearChoices();
                                    choicesInstances[key].setChoices([{value: '', label: `Search ${key}...`, disabled: true, selected: true}, ...choicesOptions], 'value', 'label', false);
                                }
                            }
                        }
                    })
                    .catch(err => console.error("Error loading FCT foods:", err));

                // Listen to choice events to update details Native
                for (const [key, select] of Object.entries(fctDropdowns)) {
                    if (select) {
                        select.addEventListener('change', function(e) {
                            const choice = choicesInstances[key].getValue();
                            if (choice && choice.value !== '') {
                                const props = choice.customProperties;
                                if (!props) return;
                                
                                const pfx = key; // matches exactly! (vegetable, fruit, milk, rice, meat, fat)
                                
                                document.getElementById(`${pfx}-exchange`).textContent = props.exchange;
                                document.getElementById(`${pfx}-weight`).textContent = props.weight;
                                document.getElementById(`${pfx}-energy`).textContent = props.energy;
                                document.getElementById(`${pfx}-protein`).textContent = props.protein;
                                document.getElementById(`${pfx}-fat`).textContent = props.fat;
                                document.getElementById(`${pfx}-cho`).textContent = props.cho;

                                const groupNames = { vegetable: 'Vegetables', fruit: 'Fruits', milk: 'Milk', rice: 'Rice & Grains', meat: 'Meat & Fish', fat: 'Fats & Oils' };
                                
                                addSelectedItem(groupNames[key], choice.label, {
                                    exchange: props.exchange || '1',
                                    weight: props.weight || '-',
                                    energy: props.energy || '0',
                                    protein: props.protein || '0',
                                    fat: props.fat || '0',
                                    cho: props.cho || '0'
                                });
                                
                                // Do not reset select after adding automatically
                                // setTimeout(() => choicesInstances[key].setChoiceByValue(''), 100);
                            }
                        });
                    }
                }

                // Mobile sidebar toggle fallback
                if (window.innerWidth <= 576) {
                    const header = document.querySelector('.header');
                    if (header) {
                        const toggleBtn = document.createElement('button');
                        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                        toggleBtn.className = 'mobile-nav-toggle';
                        toggleBtn.style.cssText = 'width: 40px; height: 40px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow); cursor: pointer; border: none; margin-right: 15px;';
                        toggleBtn.addEventListener('click', function () {
                            const sidebar = document.querySelector('.sidebar');
                            if (sidebar) {
                                sidebar.classList.toggle('active');
                                if (sidebar.classList.contains('active')) {
                                    sidebar.style.transform = 'translateX(0)';
                                } else {
                                    sidebar.style.transform = 'translateX(-100%)';
                                }
                            }
                        });
                        header.insertBefore(toggleBtn, header.firstChild);
                    }
                }
            });
        </script>

</body>

</html>
