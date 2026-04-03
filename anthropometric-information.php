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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>NutriDeq - Anthropometric Information</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/help-tracker.css">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="stylesheet" href="css/nutrifacts.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <!-- Base and Mobile styles last for priority -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <!-- Choices JS for Searchable API Dropdowns -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <!-- dashboard.js included via sidebar.php -->
    <script>const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';</script>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-container">
                <div class="header">
                    <div class="page-title">
                        <h1>Anthropometric Information
                            <?php if (isset($viewing_client_name)): ?>
                                <span style="font-size: 1.2rem; color: var(--gray);"> - Viewing:
                                    <?php echo htmlspecialchars($viewing_client_name); ?></span>
                            <?php endif; ?>
                        </h1>
                        <p>
                            <?php if (isset($viewing_client_name)): ?>
                                Monitoring health data for <?php echo htmlspecialchars($viewing_client_name); ?>
                            <?php else: ?>
                                Track your health metrics and progress
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="header-actions">
                        <?php if (isset($viewing_client_name)): ?>
                            <a href="user-management-staff.php" class="btn-back">
                                <i class="fas fa-arrow-left"></i> <span>Back to Clients</span>
                            </a>
                        <?php endif; ?>

                        <div class="">
                            <i class=""></i>
                            <div class=""></div>
                        </div>
                    </div>
                </div>

                <!-- Display error messages -->
                <?php if (isset($error)): ?>
                    <div class="error-message"
                        style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message"
                        style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Client Selector -->
                <div class="client-selector">
                    <label for="clientSelect"><i class="fas fa-user-friends"></i> Select Client:</label>
                    <select class="client-select" id="clientSelect"
                        onchange="if(this.value) location.href='?client_id=' + this.value">
                        <option value="">-- Choose a Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                                (<?php echo htmlspecialchars($client['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!$selected_client): ?>
                    <div class="no-client-message">
                        <i class="fas fa-user-friends"></i>
                        <h3>No Client Selected</h3>
                        <p>Please select a client from the dropdown above to view their health tracker.</p>
                        <?php if (empty($clients)): ?>
                            <p style="margin-top: 10px; color: var(--accent);">
                                <i class="fas fa-info-circle"></i> You don't have any active clients assigned yet.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Premium Tab Container -->
                    <div class="tabs-container">
                        <div class="tabs-header">
                            <button class="tab <?php echo $active_tab === 'personal-info' ? 'active' : ''; ?>"
                                data-tab="personal-info" onclick="switchTab('personal-info')">
                                <i class="fas fa-user-circle tab-icon"></i>
                                <span>Client Info</span>
                            </button>
                            <button class="tab <?php echo $active_tab === 'food-tracker' ? 'active' : ''; ?>"
                                data-tab="food-tracker" onclick="switchTab('food-tracker')">
                                <i class="fas fa-utensils tab-icon"></i>
                                <span>Food Tracker</span>
                            </button>
                            <button class="tab <?php echo $active_tab === 'body-stats' ? 'active' : ''; ?>"
                                data-tab="body-stats" onclick="switchTab('body-stats')">
                                <i class="fas fa-chart-line tab-icon"></i>
                                <span>Body Statistics</span>
                            </button>
                            <button class="tab <?php echo $active_tab === 'progress' ? 'active' : ''; ?>"
                                data-tab="progress" onclick="switchTab('progress')">
                                <i class="fas fa-calendar-alt tab-icon"></i>
                                <span>Meal Planner</span>
                            </button>
                        </div>
                        <!-- Tab content starts below -->

                        <!-- Personal Info Tab -->
                        <div class="tab-content <?php echo $active_tab === 'personal-info' ? 'active' : ''; ?>"
                            id="personal-info">
                            <div class="section-header">
                                <h2>Client Information</h2>
                                <div class="section-actions">

                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="fullName">Full Name</label>
                                    <input type="text" class="form-control" id="fullName"
                                        value="<?php echo htmlspecialchars($selected_client['name']); ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email"
                                        value="<?php echo htmlspecialchars($selected_client['email']); ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone"
                                        value="<?php echo htmlspecialchars($selected_client['phone'] ?? 'Not provided'); ?>"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="age">Age</label>
                                    <input type="text" class="form-control" id="age"
                                        value="<?php echo htmlspecialchars($selected_client['age'] ?? 'Not provided'); ?>"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <input type="text" class="form-control" id="gender"
                                        value="<?php echo htmlspecialchars(ucfirst($selected_client['gender'] ?? 'Not provided')); ?>"
                                        readonly>
                                </div>

                                <!-- Address Fields -->
                                <div class="form-group">
                                    <label for="address">Street Address</label>
                                    <input type="text" class="form-control" id="address"
                                        value="<?php echo htmlspecialchars($selected_client['address'] ?? 'Not provided'); ?>"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" class="form-control" id="city"
                                        value="<?php echo htmlspecialchars($selected_client['city'] ?? 'Not provided'); ?>"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="state">State/Province</label>
                                    <input type="text" class="form-control" id="state"
                                        value="<?php echo htmlspecialchars($selected_client['state'] ?? 'Not provided'); ?>"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="zip_code">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" id="zip_code"
                                        value="<?php echo htmlspecialchars($selected_client['zip_code'] ?? 'Not provided'); ?>"
                                        readonly>
                                </div>

                                <div class="form-group full-width">
                                    <label for="health_conditions">Health Conditions</label>
                                    <textarea class="form-control" id="health_conditions" rows="3"
                                        readonly><?php echo htmlspecialchars($selected_client['health_conditions'] ?? 'No health conditions reported'); ?></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="dietary_restrictions">Dietary Restrictions</label>
                                    <textarea class="form-control" id="dietary_restrictions" rows="3"
                                        readonly><?php echo htmlspecialchars($selected_client['dietary_restrictions'] ?? 'No dietary restrictions'); ?></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="goals">Health/Nutrition Goals</label>
                                    <textarea class="form-control" id="goals" rows="3"
                                        readonly><?php echo htmlspecialchars($selected_client['goals'] ?? 'No goals set'); ?></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="notes">Dietician Notes</label>
                                    <textarea class="form-control" id="notes" rows="4"
                                        readonly><?php echo htmlspecialchars($selected_client['notes'] ?? 'No notes yet'); ?></textarea>
                                </div>
                            </div>

                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $selected_client['age'] ?? 'N/A'; ?></div>
                                    <div class="stat-label">Age</div>
                                    <div class="stat-trend">Active Client</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $selected_client['weight'] ?? 'N/A'; ?>kg</div>
                                    <div class="stat-label">Current Weight</div>
                                    <div class="stat-trend">Latest</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $selected_client['height'] ?? 'N/A'; ?>cm</div>
                                    <div class="stat-label">Height</div>
                                    <div class="stat-trend">Recorded</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">
                                        <?php if ($selected_client['city']): ?>
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($selected_client['city']); ?>
                                        <?php else: ?>
                                            Location N/A
                                        <?php endif; ?>
                                    </div>
                                    <div class="stat-label">Location</div>
                                    <div class="stat-trend trend-up">Client Area</div>
                                </div>
                            </div>
                        </div>

                        <!-- Food Tracker Tab -->
                        <div class="tab-content <?php echo $active_tab === 'food-tracker' ? 'active' : ''; ?>"
                            id="food-tracker">
                            <div class="section-header">
                                <h2>Daily Food Intake Tracker</h2>
                                <div class="section-actions">

                                </div>
                            </div>

                            <div class="form-group">
                                <label>Today's Food Intake - <?php echo date('F j, Y'); ?></label>
                                <div class="food-entries">
                                    <?php if (empty($food_entries)): ?>
                                        <div class="no-client-message" style="padding: 30px;">
                                            <i class="fas fa-utensils"></i>
                                            <p>No food entries recorded for today.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($food_entries as $food): ?>
                                            <div class="food-entry">
                                                <div class="food-icon">
                                                    <i class="fas fa-<?php
                                                    switch ($food['meal_type']) {
                                                        case 'breakfast':
                                                            echo 'coffee';
                                                            break;
                                                        case 'lunch':
                                                            echo 'utensils';
                                                            break;
                                                        case 'dinner':
                                                            echo 'moon';
                                                            break;
                                                        default:
                                                            echo 'apple-alt';
                                                    }
                                                    ?>"></i>
                                                </div>
                                                <div class="food-details">
                                                    <div class="food-name"><?php echo ucfirst($food['meal_type']); ?>:
                                                        <?php echo htmlspecialchars($food['food_name']); ?>
                                                    </div>
                                                    <div class="food-macros">
                                                        Protein: <?php echo $food['protein'] ?? '0'; ?>g |
                                                        Carbs: <?php echo $food['carbs'] ?? '0'; ?>g |
                                                        Fat: <?php echo $food['fat'] ?? '0'; ?>g
                                                    </div>
                                                </div>
                                                <div class="food-calories"><?php echo $food['calories']; ?> kcal</div>
                                                <?php if ($selected_client): ?>
                                                    <div class="food-actions">
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_food_entry">
                                                            <input type="hidden" name="client_id"
                                                                value="<?php echo htmlspecialchars($selected_client['id']); ?>">
                                                            <input type="hidden" name="entry_id"
                                                                value="<?php echo htmlspecialchars($food['id']); ?>">
                                                            <button type="submit" class="btn btn-outline" title="Delete"><i
                                                                    class="fas fa-trash"></i></button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($selected_client): ?>
                                <div class="form-group">
                                    <h3 style="margin-bottom:10px;">Add Food Entry</h3>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="add_food_entry">
                                        <input type="hidden" name="client_id"
                                            value="<?php echo htmlspecialchars($selected_client['id']); ?>">
                                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                            <select name="meal_type" class="form-control" style="max-width:180px;">
                                                <option value="breakfast">Breakfast</option>
                                                <option value="lunch">Lunch</option>
                                                <option value="dinner">Dinner</option>
                                                <option value="snack">Snack</option>
                                                <option value="custom" selected>Custom</option>
                                            </select>
                                            <input type="text" name="food_name" class="form-control" placeholder="Food name"
                                                required style="flex:1;min-width:220px;">
                                            <input type="number" step="0.1" name="calories" class="form-control"
                                                placeholder="kcal" style="max-width:120px;">
                                            <input type="number" step="0.1" name="protein" class="form-control"
                                                placeholder="Protein g" style="max-width:140px;">
                                            <input type="number" step="0.1" name="carbs" class="form-control"
                                                placeholder="Carbs g" style="max-width:140px;">
                                            <input type="number" step="0.1" name="fat" class="form-control" placeholder="Fat g"
                                                style="max-width:120px;">
                                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i>
                                                Add</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $calories_today; ?></div>
                                    <div class="stat-label">Calories Today</div>
                                    <div class="stat-trend">Total intake</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo count($food_entries); ?></div>
                                    <div class="stat-label">Meals Logged</div>
                                    <div class="stat-trend">Today</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo date('H:i'); ?></div>
                                    <div class="stat-label">Last Update</div>
                                    <div class="stat-trend">Current time</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">Active</div>
                                    <div class="stat-label">Tracking</div>
                                    <div class="stat-trend trend-up">Ongoing</div>
                                </div>
                            </div>
                        </div>

                        <!-- Body Statistics Tab -->
                        <div class="tab-content <?php echo $active_tab === 'body-stats' ? 'active' : ''; ?>"
                            id="body-stats">
                            <div class="section-header">
                                <h2>Body Measurements & Statistics</h2>
                                <div class="section-actions">

                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="currentWeight">Current Weight (kg)</label>
                                    <input type="text" class="form-control" id="currentWeight"
                                        value="<?php echo $selected_client['weight'] ? $selected_client['weight'] . ' kg' : 'Not recorded'; ?>"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="targetWeight">Optimal Weight Range (kg)</label>
                                    <?php
                                    $optimal_weight = 'Need height data';
                                    if (isset($selected_client['height']) && $selected_client['height'] > 0) {
                                        $height = $selected_client['height'];
                                        $height_in_meters = $height / 100;

                                        // Calculate healthy BMI range (18.5 - 24.9)
                                        $bmi_low = 18.5 * ($height_in_meters * $height_in_meters);
                                        $bmi_high = 24.9 * ($height_in_meters * $height_in_meters);

                                        $optimal_weight = number_format($bmi_low, 1) . " - " . number_format($bmi_high, 1) . " kg";
                                    }
                                    ?>
                                    <input type="text" class="form-control" id="targetWeight"
                                        value="<?php echo $optimal_weight; ?>" readonly>
                                    <small class="form-text text-muted">Healthy BMI Range (18.5 - 24.9)</small>
                                </div>

                                <div class="form-group">
                                    <label for="height">Height (cm)</label>
                                    <input type="text" class="form-control" id="height"
                                        value="<?php echo $selected_client['height'] ? $selected_client['height'] . ' cm' : 'Not recorded'; ?>"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="bmi">BMI</label>
                                    <input type="text" class="form-control" id="bmi" value="<?php
                                    if (isset($selected_client['weight']) && isset($selected_client['height'])) {
                                        $height_in_meters = $selected_client['height'] / 100;
                                        $bmi = $selected_client['weight'] / ($height_in_meters * $height_in_meters);
                                        echo number_format($bmi, 1);
                                    } else {
                                        echo 'Not calculated';
                                    }
                                    ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="waist">Waist Circumference (cm)</label>
                                    <input type="text" class="form-control" id="waist" value="<?php
                                    if (isset($selected_client['waist_circumference'])) {
                                        echo $selected_client['waist_circumference'] . ' cm';
                                    } else {
                                        echo 'Not recorded';
                                    }
                                    ?>" readonly>
                                </div>

                                <div class="form-group">
                                    <label for="hip">Hip Circumference (cm)</label>
                                    <input type="text" class="form-control" id="hip" value="<?php
                                    if (isset($selected_client['hip_circumference'])) {
                                        echo $selected_client['hip_circumference'] . ' cm';
                                    } else {
                                        echo 'Not recorded';
                                    }
                                    ?>" readonly>
                                </div>
                            </div>

                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-value"><?php
                                    if (isset($selected_client['weight']) && isset($selected_client['height'])) {
                                        $height_in_meters = $selected_client['height'] / 100;
                                        $bmi_value = $selected_client['weight'] / ($height_in_meters * $height_in_meters);
                                        echo number_format($bmi_value, 1);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?></div>
                                    <div class="stat-label">Current BMI</div>
                                    <div class="stat-trend">
                                        <?php
                                        if (isset($selected_client['weight']) && isset($selected_client['height'])) {
                                            $height_in_meters = $selected_client['height'] / 100;
                                            $bmi_value = $selected_client['weight'] / ($height_in_meters * $height_in_meters);
                                            if ($bmi_value < 18.5)
                                                echo 'Underweight';
                                            elseif ($bmi_value < 25)
                                                echo 'Normal';
                                            elseif ($bmi_value < 30)
                                                echo 'Overweight';
                                            else
                                                echo 'Obese';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-value">
                                        <?php
                                        if (isset($selected_client['weight']) && isset($selected_client['height'])) {
                                            // Calculate optimal weight (midpoint of healthy BMI range)
                                            $height_in_meters = $selected_client['height'] / 100;
                                            $optimal_weight_mid = 21.7 * ($height_in_meters * $height_in_meters);
                                            $weight_diff = $selected_client['weight'] - $optimal_weight_mid;
                                            echo number_format(abs($weight_diff), 1) . 'kg';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">To Optimal Weight</div>
                                    <div class="stat-trend <?php
                                    if (isset($weight_diff)) {
                                        echo $weight_diff > 0 ? 'trend-down' : ($weight_diff < 0 ? 'trend-up' : 'trend-neutral');
                                    } else {
                                        echo 'trend-neutral';
                                    }
                                    ?>">
                                        <?php
                                        if (isset($weight_diff)) {
                                            echo $weight_diff > 0 ? 'To lose' : ($weight_diff < 0 ? 'To gain' : 'Ideal weight');
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-value">
                                        <?php
                                        if (isset($selected_client['waist_circumference']) && isset($selected_client['hip_circumference'])) {
                                            echo number_format($selected_client['waist_circumference'] / $selected_client['hip_circumference'], 2);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Waist-Hip Ratio</div>
                                    <div class="stat-trend">
                                        <?php
                                        if (isset($selected_client['waist_circumference']) && isset($selected_client['hip_circumference'])) {
                                            $wh_ratio = $selected_client['waist_circumference'] / $selected_client['hip_circumference'];
                                            $gender = $selected_client['gender'] ?? '';
                                            if ($gender === 'male') {
                                                echo ($wh_ratio <= 0.9) ? 'Low risk' : 'High risk';
                                            } else {
                                                echo ($wh_ratio <= 0.85) ? 'Low risk' : 'High risk';
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="stat-value">
                                        <?php
                                        if (isset($selected_client['created_at'])) {
                                            echo date('M j', strtotime($selected_client['created_at']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Last Update</div>
                                    <div class="stat-trend">
                                        <?php
                                        if (isset($selected_client['created_at'])) {
                                            $days_ago = floor((time() - strtotime($selected_client['created_at'])) / (60 * 60 * 24));
                                            if ($days_ago == 0)
                                                echo 'Today';
                                            elseif ($days_ago == 1)
                                                echo 'Yesterday';
                                            elseif ($days_ago < 7)
                                                echo $days_ago . ' days ago';
                                            else
                                                echo 'Updated';
                                        } else {
                                            echo 'Initial';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($selected_client): ?>
                                <div class="section-header"
                                    style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                                    <h2>Update Body Measurements</h2>
                                </div>
                                <form method="POST" id="updateBodyStatsForm">
                                    <input type="hidden" name="action" value="update_body_stats">
                                    <input type="hidden" name="client_id"
                                        value="<?php echo htmlspecialchars($selected_client['id']); ?>">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="upd_weight">Weight (kg)</label>
                                            <input type="number" step="0.1" id="upd_weight" name="weight" class="form-control"
                                                value="<?php echo htmlspecialchars($selected_client['weight'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="upd_height">Height (cm)</label>
                                            <input type="number" step="0.1" id="upd_height" name="height" class="form-control"
                                                value="<?php echo htmlspecialchars($selected_client['height'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="upd_waist">Waist (cm)</label>
                                            <input type="number" step="0.1" id="upd_waist" name="waist_circumference"
                                                class="form-control"
                                                value="<?php echo htmlspecialchars($selected_client['waist_circumference'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="upd_hip">Hip (cm)</label>
                                            <input type="number" step="0.1" id="upd_hip" name="hip_circumference"
                                                class="form-control"
                                                value="<?php echo htmlspecialchars($selected_client['hip_circumference'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save
                                            Measurements</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- Meal Planner Tab -->
                        <div class="tab-content <?php echo $active_tab === 'progress' ? 'active' : ''; ?>" id="progress">
                            <div class="section-header">
                                <h2>Food Exchange List</h2>
                                <div class="section-actions">
                                    <button class="btn btn-primary" id="saveMealPlan">
                                        <i class="fas fa-save"></i> Save Meal Plan
                                    </button>
                                </div>
                            </div>

                            <!-- FNRI Style Table Layout -->
                            <div class="fel-container">
                                <div class="fel-header">
                                    <h3><i class="fas fa-exchange-alt"></i> Meal Planning List</h3>
                                    <p>Select food items and specify quantities for each exchange group</p>
                                </div>

                                <div class="fel-table-container">
                                    <table class="fel-table">
                                        <thead>
                                            <tr>
                                                <th width="25%">Food Group & Items</th>
                                                <th width="15%">Exchange</th>
                                                <th width="12%">Weight (g)</th>
                                                <th width="12%">Energy (kcal)</th>
                                                <th width="12%">Protein (g)</th>
                                                <th width="12%">Fat (g)</th>
                                                <th width="12%">CHO (g)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Vegetables Group -->
                                            <tr class="group-header">
                                                <td colspan="7">
                                                    <i class="fas fa-carrot"></i>
                                                    <strong>VEGETABLES</strong>
                                                </td>
                                            </tr>
                                            <tr class="food-item">
                                                <td>
                                                    <select id="veg-select" class="food-select">
                                                        <option value="">Select Vegetable Type</option>
                                                        <?php if (!empty($food_items_by_group['vegetable'])): ?>
                                                            <?php foreach ($food_items_by_group['vegetable'] as $item): ?>
                                                                <option value="<?php echo htmlspecialchars($item['id']); ?>"
                                                                    data-exchange="<?php echo htmlspecialchars($item['exchanges'] ?? '1'); ?>"
                                                                    data-weight="<?php echo htmlspecialchars($item['serving_size'] ?? ''); ?>"
                                                                    data-energy="<?php echo htmlspecialchars($item['energy'] ?? '0'); ?>"
                                                                    data-protein="<?php echo htmlspecialchars($item['protein'] ?? '0'); ?>"
                                                                    data-fat="<?php echo htmlspecialchars($item['fat'] ?? '0'); ?>"
                                                                    data-cho="<?php echo htmlspecialchars($item['carbohydrates'] ?? '0'); ?>">
                                                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <!-- Fallback options if no database data -->
                                                            <option value="leafy">Leafy, flower, fruit vegetables</option>
                                                            <option value="root">Root, tuber, bulb vegetables</option>
                                                            <option value="starchy">Starchy vegetables</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td id="vegetable-exchange">-</td>
                                                <td id="vegetable-weight">-</td>
                                                <td id="vegetable-energy">-</td>
                                                <td id="vegetable-protein">-</td>
                                                <td id="vegetable-fat">-</td>
                                                <td id="vegetable-cho">-</td>
                                            </tr>

                                            <!-- Fruits Group -->
                                            <tr class="group-header">
                                                <td colspan="7">
                                                    <i class="fas fa-apple-alt"></i>
                                                    <strong>FRUITS</strong>
                                                </td>
                                            </tr>
                                            <tr class="food-item">
                                                <td data-label="Food Item">
                                                    <select id="fruit-select" class="food-select">
                                                        <option value="">Select Fruit</option>
                                                        <?php if (!empty($food_items_by_group['fruit'])): ?>
                                                            <?php foreach ($food_items_by_group['fruit'] as $item): ?>
                                                                <option value="<?php echo htmlspecialchars($item['id']); ?>"
                                                                    data-exchange="<?php echo htmlspecialchars($item['exchanges'] ?? '1'); ?>"
                                                                    data-weight="<?php echo htmlspecialchars($item['serving_size'] ?? ''); ?>"
                                                                    data-energy="<?php echo htmlspecialchars($item['energy'] ?? '0'); ?>"
                                                                    data-protein="<?php echo htmlspecialchars($item['protein'] ?? '0'); ?>"
                                                                    data-fat="<?php echo htmlspecialchars($item['fat'] ?? '0'); ?>"
                                                                    data-cho="<?php echo htmlspecialchars($item['carbohydrates'] ?? '0'); ?>">
                                                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <!-- Fallback options -->
                                                            <option value="banana">Banana (saba)</option>
                                                            <option value="mango">Mango</option>
                                                            <option value="apple">Apple</option>
                                                            <option value="orange">Orange</option>
                                                            <option value="papaya">Papaya</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td data-label="Exchange" id="fruit-exchange">-</td>
                                                <td data-label="Weight" id="fruit-weight">-</td>
                                                <td data-label="Energy" id="fruit-energy">-</td>
                                                <td data-label="Protein" id="fruit-protein">-</td>
                                                <td data-label="Fat" id="fruit-fat">-</td>
                                                <td data-label="CHO" id="fruit-cho">-</td>
                                            </tr>

                                            <!-- Milk Group -->
                                            <tr class="group-header">
                                                <td colspan="7">
                                                    <i class="fas fa-wine-bottle"></i>
                                                    <strong>MILK</strong>
                                                </td>
                                            </tr>
                                            <tr class="food-item">
                                                <td data-label="Food Item">
                                                    <select id="milk-select" class="food-select">
                                                        <option value="">Select Milk Type</option>
                                                        <?php if (!empty($food_items_by_group['milk'])): ?>
                                                            <?php foreach ($food_items_by_group['milk'] as $item): ?>
                                                                <option value="<?php echo htmlspecialchars($item['id']); ?>"
                                                                    data-exchange="<?php echo htmlspecialchars($item['exchanges'] ?? '1'); ?>"
                                                                    data-weight="<?php echo htmlspecialchars($item['serving_size'] ?? ''); ?>"
                                                                    data-energy="<?php echo htmlspecialchars($item['energy'] ?? '0'); ?>"
                                                                    data-protein="<?php echo htmlspecialchars($item['protein'] ?? '0'); ?>"
                                                                    data-fat="<?php echo htmlspecialchars($item['fat'] ?? '0'); ?>"
                                                                    data-cho="<?php echo htmlspecialchars($item['carbohydrates'] ?? '0'); ?>">
                                                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <!-- Fallback options -->
                                                            <option value="whole">Whole, fresh/evap</option>
                                                            <option value="lowfat">Low-fat, fresh</option>
                                                            <option value="nonfat">Non-fat, powdered</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td data-label="Exchange" id="milk-exchange">-</td>
                                                <td data-label="Weight" id="milk-weight">-</td>
                                                <td data-label="Energy" id="milk-energy">-</td>
                                                <td data-label="Protein" id="milk-protein">-</td>
                                                <td data-label="Fat" id="milk-fat">-</td>
                                                <td data-label="CHO" id="milk-cho">-</td>
                                            </tr>

                                            <!-- Rice Group -->
                                            <tr class="group-header">
                                                <td colspan="7">
                                                    <i class="fas fa-bread-slice"></i>
                                                    <strong>RICE, RICE SUBSTITUTES & PRODUCTS</strong>
                                                </td>
                                            </tr>
                                            <tr class="food-item">
                                                <td data-label="Food Item">
                                                    <select id="rice-select" class="food-select">
                                                        <option value="">Select Rice/Grain</option>
                                                        <?php if (!empty($food_items_by_group['rice'])): ?>
                                                            <?php foreach ($food_items_by_group['rice'] as $item): ?>
                                                                <option value="<?php echo htmlspecialchars($item['id']); ?>"
                                                                    data-exchange="<?php echo htmlspecialchars($item['exchanges'] ?? '1'); ?>"
                                                                    data-weight="<?php echo htmlspecialchars($item['serving_size'] ?? ''); ?>"
                                                                    data-energy="<?php echo htmlspecialchars($item['energy'] ?? '0'); ?>"
                                                                    data-protein="<?php echo htmlspecialchars($item['protein'] ?? '0'); ?>"
                                                                    data-fat="<?php echo htmlspecialchars($item['fat'] ?? '0'); ?>"
                                                                    data-cho="<?php echo htmlspecialchars($item['carbohydrates'] ?? '0'); ?>">
                                                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <!-- Fallback options -->
                                                            <option value="rice_well">Rice, well-milled</option>
                                                            <option value="rice_medium">Rice, medium-milled</option>
                                                            <option value="rice_brown">Rice, brown</option>
                                                            <option value="bread">Bread</option>
                                                            <option value="noodles">Noodles</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td data-label="Exchange" id="rice-exchange">-</td>
                                                <td data-label="Weight" id="rice-weight">-</td>
                                                <td data-label="Energy" id="rice-energy">-</td>
                                                <td data-label="Protein" id="rice-protein">-</td>
                                                <td data-label="Fat" id="rice-fat">-</td>
                                                <td data-label="CHO" id="rice-cho">-</td>
                                            </tr>

                                            <!-- Meat Group -->
                                            <tr class="group-header">
                                                <td colspan="7">
                                                    <i class="fas fa-drumstick-bite"></i>
                                                    <strong>MEAT, POULTRY, FISH & PRODUCTS</strong>
                                                </td>
                                            </tr>
                                            <tr class="food-item">
                                                <td data-label="Food Item">
                                                    <select id="meat-select" class="food-select">
                                                        <option value="">Select Meat/Fish</option>
                                                        <?php if (!empty($food_items_by_group['meat'])): ?>
                                                            <?php foreach ($food_items_by_group['meat'] as $item): ?>
                                                                <option value="<?php echo htmlspecialchars($item['id']); ?>"
                                                                    data-exchange="<?php echo htmlspecialchars($item['exchanges'] ?? '1'); ?>"
                                                                    data-weight="<?php echo htmlspecialchars($item['serving_size'] ?? ''); ?>"
                                                                    data-energy="<?php echo htmlspecialchars($item['energy'] ?? '0'); ?>"
                                                                    data-protein="<?php echo htmlspecialchars($item['protein'] ?? '0'); ?>"
                                                                    data-fat="<?php echo htmlspecialchars($item['fat'] ?? '0'); ?>"
                                                                    data-cho="<?php echo htmlspecialchars($item['carbohydrates'] ?? '0'); ?>">
                                                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <!-- Fallback options -->
                                                            <option value="lean">Lean meat</option>
                                                            <option value="medium">Medium-fat meat</option>
                                                            <option value="high">High-fat meat</option>
                                                            <option value="fish">Fish, lean</option>
                                                            <option value="fish_medium">Fish, medium-fat</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td data-label="Exchange" id="meat-exchange">-</td>
                                                <td data-label="Weight" id="meat-weight">-</td>
                                                <td data-label="Energy" id="meat-energy">-</td>
                                                <td data-label="Protein" id="meat-protein">-</td>
                                                <td data-label="Fat" id="meat-fat">-</td>
                                                <td data-label="CHO" id="meat-cho">-</td>
                                            </tr>

                                            <!-- Fats Group -->
                                            <tr class="group-header">
                                                <td colspan="7">
                                                    <i class="fas fa-oil-can"></i>
                                                    <strong>FATS & OILS</strong>
                                                </td>
                                            </tr>
                                            <tr class="food-item">
                                                <td data-label="Food Item">
                                                    <select id="fat-select" class="food-select">
                                                        <option value="">Select Fat/Oil Type</option>
                                                        <?php if (!empty($food_items_by_group['fat'])): ?>
                                                            <?php foreach ($food_items_by_group['fat'] as $item): ?>
                                                                <option value="<?php echo htmlspecialchars($item['id']); ?>"
                                                                    data-exchange="<?php echo htmlspecialchars($item['exchanges'] ?? '1'); ?>"
                                                                    data-weight="<?php echo htmlspecialchars($item['serving_size'] ?? ''); ?>"
                                                                    data-energy="<?php echo htmlspecialchars($item['energy'] ?? '0'); ?>"
                                                                    data-protein="<?php echo htmlspecialchars($item['protein'] ?? '0'); ?>"
                                                                    data-fat="<?php echo htmlspecialchars($item['fat'] ?? '0'); ?>"
                                                                    data-cho="<?php echo htmlspecialchars($item['carbohydrates'] ?? '0'); ?>">
                                                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <!-- Fallback options -->
                                                            <option value="butter">Butter, margarine</option>
                                                            <option value="oil">Cooking oil</option>
                                                            <option value="mayo">Mayonnaise</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                                <td data-label="Exchange" id="fat-exchange">-</td>
                                                <td data-label="Weight" id="fat-weight">-</td>
                                                <td data-label="Energy" id="fat-energy">-</td>
                                                <td data-label="Protein" id="fat-protein">-</td>
                                                <td data-label="Fat" id="fat-fat">-</td>
                                                <td data-label="CHO" id="fat-cho">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Selected Items Summary -->
                                <div class="selected-items-section">
                                    <h4><i class="fas fa-list-check"></i> Selected Food Items</h4>
                                    <div class="selected-items-list" id="selectedItems">
                                        <div class="no-selection">No food items selected yet</div>
                                    </div>
                                </div>

                                <!-- Nutrition Summary -->
                                <div class="nutrition-summary-fel">
                                    <div class="summary-header">
                                        <h4><i class="fas fa-calculator"></i> Total Nutritional Values</h4>
                                    </div>
                                    <div class="summary-grid">
                                        <div class="summary-item">
                                            <div class="summary-value" id="total-energy">0</div>
                                            <div class="summary-label">Energy (kcal)</div>
                                        </div>
                                        <div class="summary-item">
                                            <div class="summary-value" id="total-protein">0</div>
                                            <div class="summary-label">Protein (g)</div>
                                        </div>
                                        <div class="summary-item">
                                            <div class="summary-value" id="total-fat">0</div>
                                            <div class="summary-label">Fat (g)</div>
                                        </div>
                                        <div class="summary-item">
                                            <div class="summary-value" id="total-cho">0</div>
                                            <div class="summary-label">CHO (g)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

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
