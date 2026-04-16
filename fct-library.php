<?php
session_start();
echo '<script>console.log("Current Role: ' . ($_SESSION['user_role'] ?? 'none') . '");</script>';
require_once 'api/fct_helper.php';

// Check login (Assuming standard auth)
if (!isset($_SESSION['user_id'])) {
    header('Location: login-logout/NutriDeqN-Login.php');
    exit();
}

$user_role = strtolower($_SESSION['user_role'] ?? 'guest');
$fct = new FCTHelper();
?>
<script> 
    const currentUserRole = '<?php echo $user_role; ?>'; 
    console.log('Detected Role:', currentUserRole);
</script>
<?php

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['cat']) ? $_GET['cat'] : '';

$data = $fct->getFoodItems($page, 20, $search, $category);
$items = $data['items'];
$total_pages = $data['pages'];

require_once 'navigation.php';
$nav_links = getNavigationLinks($user_role, 'fct-library.php');

$fct_categories = [
    'Cereals and Cereal Products',
    'Starchy Roots and Tubers',
    'Nuts, Pulses and Seeds',
    'Vegetables and Vegetable Products',
    'Fruits and Fruit Products',
    'Fish and Shellfish',
    'Eggs and Egg Products',
    'Milk and Milk Products',
    'Fats and Oils',
    'Meat and Poultry',
    'Sweets and Condiments',
    'Spices and Condiments',
    'Beverages (Alcoholic and Non-Alcoholic)',
    'Mixed Dishes / Prepared Foods',
    'Baby Foods (Strained)',
    'Seaweeds',
    'Miscellaneous'
];

// Map food categories to an icon + accent color
function getFoodIcon(string $category): array
{
    $cat = strtolower($category);
    if (str_contains($cat, 'cereal'))
        return ['fa-wheat-awn', '#E67E22', 'rgba(230,126,34,0.1)'];
    if (str_contains($cat, 'starchy') || str_contains($cat, 'root') || str_contains($cat, 'tuber'))
        return ['fa-carrot', '#E74C3C', 'rgba(231,76,60,0.1)'];
    if (str_contains($cat, 'nut') || str_contains($cat, 'pulse') || str_contains($cat, 'seed'))
        return ['fa-seedling', '#27AE60', 'rgba(39,174,96,0.1)'];
    if (str_contains($cat, 'vegetable'))
        return ['fa-leaf', '#2ECC71', 'rgba(46,204,113,0.1)'];
    if (str_contains($cat, 'fruit'))
        return ['fa-apple-whole', '#E91E8C', 'rgba(233,30,140,0.1)'];
    if (str_contains($cat, 'fish') || str_contains($cat, 'shellfish'))
        return ['fa-fish', '#3498DB', 'rgba(52,152,219,0.1)'];
    if (str_contains($cat, 'egg'))
        return ['fa-egg', '#FDD835', 'rgba(253,216,53,0.12)'];
    if (str_contains($cat, 'milk'))
        return ['fa-cow', '#00BCD4', 'rgba(0,188,212,0.1)'];
    if (str_contains($cat, 'fat') || str_contains($cat, 'oil'))
        return ['fa-oil-well', '#F5A623', 'rgba(245,166,35,0.1)'];
    if (str_contains($cat, 'meat') || str_contains($cat, 'poultry'))
        return ['fa-drumstick-bite', '#C0392B', 'rgba(192,57,43,0.1)'];
<?php
session_start();
echo '<script>console.log("Current Role: ' . ($_SESSION['user_role'] ?? 'none') . '");</script>';
require_once 'api/fct_helper.php';

// Check login (Assuming standard auth)
if (!isset($_SESSION['user_id'])) {
    header('Location: login-logout/NutriDeqN-Login.php');
    exit();
}

$user_role = strtolower($_SESSION['user_role'] ?? 'guest');
$fct = new FCTHelper();
?>
<script> 
    const currentUserRole = '<?php echo $user_role; ?>'; 
    console.log('Detected Role:', currentUserRole);
</script>
<?php

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['cat']) ? $_GET['cat'] : '';

$data = $fct->getFoodItems($page, 20, $search, $category);
$items = $data['items'];
$total_pages = $data['pages'];

require_once 'navigation.php';
$nav_links = getNavigationLinks($user_role, 'fct-library.php');

$fct_categories = [
    'Cereals and Cereal Products',
    'Starchy Roots and Tubers',
    'Nuts, Pulses and Seeds',
    'Vegetables and Vegetable Products',
    'Fruits and Fruit Products',
    'Fish and Shellfish',
    'Eggs and Egg Products',
    'Milk and Milk Products',
    'Fats and Oils',
    'Meat and Poultry',
    'Sweets and Condiments',
    'Spices and Condiments',
    'Beverages (Alcoholic and Non-Alcoholic)',
    'Mixed Dishes / Prepared Foods',
    'Baby Foods (Strained)',
    'Seaweeds',
    'Miscellaneous'
];

// Map food categories to an icon + accent color
function getFoodIcon(string $category): array
{
    $cat = strtolower($category);
    if (str_contains($cat, 'cereal'))
        return ['fa-wheat-awn', '#E67E22', 'rgba(230,126,34,0.1)'];
    if (str_contains($cat, 'starchy') || str_contains($cat, 'root') || str_contains($cat, 'tuber'))
        return ['fa-carrot', '#E74C3C', 'rgba(231,76,60,0.1)'];
    if (str_contains($cat, 'nut') || str_contains($cat, 'pulse') || str_contains($cat, 'seed'))
        return ['fa-seedling', '#27AE60', 'rgba(39,174,96,0.1)'];
    if (str_contains($cat, 'vegetable'))
        return ['fa-leaf', '#2ECC71', 'rgba(46,204,113,0.1)'];
    if (str_contains($cat, 'fruit'))
        return ['fa-apple-whole', '#E91E8C', 'rgba(233,30,140,0.1)'];
    if (str_contains($cat, 'fish') || str_contains($cat, 'shellfish'))
        return ['fa-fish', '#3498DB', 'rgba(52,152,219,0.1)'];
    if (str_contains($cat, 'egg'))
        return ['fa-egg', '#FDD835', 'rgba(253,216,53,0.12)'];
    if (str_contains($cat, 'milk'))
        return ['fa-cow', '#00BCD4', 'rgba(0,188,212,0.1)'];
    if (str_contains($cat, 'fat') || str_contains($cat, 'oil'))
        return ['fa-oil-well', '#F5A623', 'rgba(245,166,35,0.1)'];
    if (str_contains($cat, 'meat') || str_contains($cat, 'poultry'))
        return ['fa-drumstick-bite', '#C0392B', 'rgba(192,57,43,0.1)'];
    if (str_contains($cat, 'sweet') || str_contains($cat, 'candy'))
        return ['fa-candy-cane', '#E91E63', 'rgba(233,30,99,0.1)'];
    if (str_contains($cat, 'spice') || str_contains($cat, 'condiment'))
        return ['fa-pepper-hot', '#FF5722', 'rgba(255,87,34,0.1)'];
    if (str_contains($cat, 'beverage') || str_contains($cat, 'drink'))
        return ['fa-mug-hot', '#8E44AD', 'rgba(142,68,173,0.1)'];
    if (str_contains($cat, 'mixed') || str_contains($cat, 'prepared'))
        return ['fa-bowl-food', '#16A085', 'rgba(22,160,133,0.1)'];
    if (str_contains($cat, 'baby'))
        return ['fa-baby', '#F06292', 'rgba(240,98,146,0.1)'];
    if (str_contains($cat, 'seaweed'))
        return ['fa-water', '#1ABC9C', 'rgba(26,188,156,0.1)'];
    return ['fa-utensils', '#7F8C8D', 'rgba(127,140,141,0.1)'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCT Library | NutriDeq</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/fct-style.css">
    
    <?php if ($user_role === 'admin'): ?>
        <link rel="stylesheet" href="css/admin.css">
    <?php elseif ($user_role === 'staff'): ?>
        <link rel="stylesheet" href="css/staff.css">
    <?php else: ?>
        <link rel="stylesheet" href="css/user-premium.css">
    <?php endif; ?>

    <link rel="stylesheet" href="css/dashboard-premium.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    
    <!-- Choices.js (Base Styles First) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <style>
        /* Choices.js Dropdown Polish */
        .choices {
            position: relative !important;
            overflow: visible !important;
            margin-bottom: 0 !important;
        }
        .choices__list--dropdown {
            z-index: 100000 !important;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        .choices__inner, .choices__list, .ant-space-compact, .ant-input-wrapper, .ant-controls-group, .card, .dash-panel {
            overflow: visible !important;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .page-container, .fct-container, .table-container, .main-content {
                padding-left: 15px !important;
                padding-right: 15px !important;
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Organic Background Engine -->
            <div class="organic-bg" id="organicBg">
                <div class="organic-blob blob-1"></div>
                <div class="organic-blob blob-2"></div>
                <div class="organic-blob blob-3"></div>
            </div>
            <!-- Custom Ring Cursor -->
            <div class="cursor-ring" id="cursorRing"></div>
            <div class="page-container fct-container">
                <div class="dash-hero-ribbon dashFadeIn" style="padding: 30px 40px; margin-bottom: 24px;">
                    <div class="dash-hero-content">
                        <p class="dash-hero-welcome">Clinical Database</p>
                        <h1 style="font-size: 2.2rem;">FCT Library</h1>
                        <p>Philippine Nutrient Encyclopedia & Nutritional Intelligence</p>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="dash-panel stagger d-2" style="padding: 24px; margin-bottom: 28px; min-height: auto; overflow: visible !important; z-index: 1000; position: relative !important;">
                    <form action="" method="GET">
                        <div style="display: grid; grid-template-columns: 1fr 280px auto; gap: 16px; align-items: center;">
                            <div class="search-box" style="position: relative; margin-bottom: 0; background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 16px; padding: 0 20px; display: flex; align-items: center; gap: 12px; transition: all 0.2s ease;">
                                <i class="fas fa-search" style="color: #9ca3af;"></i>
                                <input type="text" name="search" placeholder="Search by name or ID..."
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    style="border: none; background: transparent; height: 54px; width: 100%; outline: none; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 0.95rem; color: #111827;">
                            </div>
                            <select name="cat" class="searchable-select">
                                <option value="">Global Category Overview</option>
                                <?php foreach ($fct_categories as $catOption): ?>
                                    <option value="<?php echo htmlspecialchars($catOption); ?>" <?php echo $category == $catOption ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($catOption); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (FCTHelper::canManage($user_role)): ?>
                                <button type="button" class="btn-premium-add" onclick="showAddModal()" style="height: 54px; width: 54px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 16px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Data Table -->
                <!-- Encyclopedia View -->
                <div class="dash-panel stagger d-3">
                    <div class="activity-feed">
                        <?php if (empty($items)): ?>
                            <div style="text-align: center; padding: 80px 20px; color: #9ca3af;">
                                <i class="fas fa-layer-group" style="font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.5;"></i>
                                <p style="font-weight: 500; font-size: 1.1rem;">No nutritional records found matching your criteria.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php [$icon, $color, $bg] = getFoodIcon($item['category'] ?? ''); ?>
                                <div class="activity-item" style="padding: 20px; transition: all 0.3s ease; cursor: pointer; border-bottom: 1px solid #f3f4f6;" onclick="viewDetails(<?php echo $item['id']; ?>)">
                                    <div class="activity-icon" style="width: 54px; height: 54px; border-radius: 16px; background: <?php echo $bg; ?>; color: <?php echo $color; ?>; font-size: 1.3rem;">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                            <div>
                                                <p class="activity-text" style="font-size: 1.1rem; color: #111827;"><b><?php echo htmlspecialchars($item['food_name']); ?></b></p>
                                                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                                    <span style="font-family: 'SFMono-Regular', monospace; font-size: 0.75rem; background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 6px; font-weight: 600;">
                                                        ID: <?php echo htmlspecialchars($item['food_id']); ?>
                                                    </span>
                                                    <span class="profile-badge" style="font-family: 'Inter', sans-serif; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; padding: 2px 12px; border-radius: 50px; background: <?php echo $bg; ?>; color: <?php echo $color; ?>; border: none; display: inline-flex; align-items: center; justify-content: center; height: 22px;">
                                                        <?php echo htmlspecialchars($item['category']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ant-btn-group" onclick="event.stopPropagation();">
                                                <?php if ($user_role === 'user'): ?>
                                                    <button type="button" class="ant-btn ant-btn-icon-only" title="Log to Diary"
                                                        style="color: #10b981; background: #ecfdf5; border: none; width: 42px; height: 42px; border-radius: 12px;"
                                                        onclick="openLogModal(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="ant-btn ant-btn-icon-only" title="View Details"
                                                    style="background: #f9fafb; border: none; width: 42px; height: 42px; border-radius: 12px; color: #4b5563;"
                                                    onclick="viewDetails(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (FCTHelper::canManage($user_role)): ?>
                                                    <button type="button" class="ant-btn ant-btn-icon-only"
                                                        style="background: #eff6ff; color: #3b82f6; border: none; width: 42px; height: 42px; border-radius: 12px;"
                                                        title="Edit Item" onclick="editItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-pen-to-square"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (FCTHelper::canDelete($user_role)): ?>
                                                    <button type="button" class="ant-btn ant-btn-icon-only"
                                                        style="background: #fef2f2; color: #ef4444; border: none; width: 42px; height: 42px; border-radius: 12px;"
                                                        title="Delete Item" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-trash-can"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:none;">
                    <table class="fct-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Food ID</th>
                                <th style="width: 35%;">Name</th>
                                <th style="width: 25%;">Category</th>
                                <th style="width: 25%; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 4rem;">
                                        <div style="color: #bfbfbf;">
                                            <i class="fas fa-inbox"
                                                style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                            <p style="margin: 0; font-size: 1rem;">No Data</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php
else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td data-label="Food ID">
                                            <span
                                                style="color: #8c8c8c; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 0.9em;">
                                                <?php echo htmlspecialchars($item['food_id']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Name">
                                            <?php
        [$icon, $color, $bg] = getFoodIcon($item['category'] ?? '');
?>
                                            <div style="display:flex;align-items:center;gap:12px;">
                                                <div style="
                                                    width:38px;height:38px;border-radius:10px;
                                                    background:<?php echo $bg; ?>;
                                                    color:<?php echo $color; ?>;
                                                    display:flex;align-items:center;justify-content:center;
                                                    font-size:1rem;flex-shrink:0;
                                                    box-shadow:0 2px 6px <?php echo $bg; ?>;
                                                ">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                </div>
                                                <span style="color:#262626;font-weight:500;font-size:0.95rem;">
                                                    <?php echo htmlspecialchars($item['food_name']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label="Category">
                                            <span class="ant-tag ant-tag-green">
                                                <?php echo htmlspecialchars($item['category']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions" style="text-align: center;">
                                            <div class="ant-btn-group">
                                                <?php if ($user_role === 'user'): ?>
                                                    <button type="button" class="ant-btn ant-btn-icon-only log-btn" title="Log to Diary"
                                                        style="color: var(--primary);"
                                                        onclick="openLogModal(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-plus-circle"></i>
                                                    </button>
                                                <?php
        endif; ?>
                                                <button type="button" class="ant-btn ant-btn-icon-only" title="View Details"
                                                    onclick="viewDetails(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (FCTHelper::canManage($user_role)): ?>
                                                    <button type="button" class="ant-btn ant-btn-icon-only ant-btn-edit"
                                                        title="Edit Item" onclick="editItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php
        endif; ?>
                                                <?php if (FCTHelper::canDelete($user_role)): ?>
                                                    <button type="button" class="ant-btn ant-btn-icon-only ant-btn-danger"
                                                        title="Delete Item" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php
        endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
    endforeach; ?>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="fct-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&cat=<?php echo urlencode($category); ?>"
                                class="page-btn"><i class="fas fa-chevron-left"></i></a>
                        <?php
    endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&cat=<?php echo urlencode($category); ?>"
                                class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php
    endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&cat=<?php echo urlencode($category); ?>"
                                class="page-btn"><i class="fas fa-chevron-right"></i></a>
                        <?php
    endif; ?>
                    </div>
                <?php
endif; ?>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="fctModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Food Item</h3>
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="fctForm" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
                <input type="hidden" name="action" value="save">
                <div class="modal-content" style="padding: 25px;">
                    <div class="fct-form-group">
                        <label>Food ID (Code)</label>
                        <input type="text" name="food_id" class="fct-input" placeholder="e.g. A001" required>
                    </div>
                    <div class="fct-form-group">
                        <label>Food Name</label>
                        <input type="text" name="food_name" class="fct-input" placeholder="e.g. Rice, polished"
                            required>
                    </div>
                    <div class="fct-form-group">
                        <label>Category</label>
                        <select name="category" class="fct-input searchable-select-modal" required>
                            <option value="">Select a category</option>
                            <?php foreach ($fct_categories as $catOption): ?>
                                <option value="<?php echo htmlspecialchars($catOption); ?>">
                                    <?php echo htmlspecialchars($catOption); ?>
                                </option>
                            <?php
endforeach; ?>
                        </select>
                    </div>

                    <h3 style="margin: 20px 0 10px; font-size: 1.1rem; color: var(--dark);">Nutrients (per 100g)</h3>
                    <div id="nutrientContainer" class="nutrient-inputs">
                        <!-- Nutrient rows will be added here -->
                    </div>
                    <button type="button" class="btn btn-outline" style="width: 100%; margin-top: 1rem;"
                        onclick="addNutrientRow()">
                        <i class="fas fa-plus"></i> Add Nutrient
                    </button>
                </div>
                <div class="modal-footer"
                    style="padding: 15px 25px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log to Diary Modal -->
    <div id="logModal" class="modal-overlay" style="z-index: 9999;">
        <div class="modal-container" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Log to Food Diary</h3>
                <button class="modal-close" onclick="closeLogModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="logForm">
                <input type="hidden" name="food_item_id" id="logFoodItemId">
                <div class="modal-content" style="padding: 25px;">
                    <div id="logFoodSummary" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid var(--primary);">
                        <h4 id="logFoodName" style="margin: 0; color: var(--dark);">Food Name</h4>
                        <p id="logFoodMacros" style="margin: 5px 0 0; font-size: 0.85rem; color: var(--gray);">Loading nutrition data...</p>
                    </div>
                    
                    <div class="fct-form-group">
                        <label>Meal Type</label>
                        <select name="meal_type" class="fct-input" required>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Dinner">Dinner</option>
                            <option value="Snack">Snack</option>
                        </select>
                    </div>
                    
                    <div class="fct-form-group">
                        <label>Serving Size (grams)</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="number" name="serving_size" class="fct-input" value="100" min="1" step="any" required>
                            <span style="color: var(--gray);">g</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 15px 25px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeLogModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="logSubmitBtn">Log Food</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-container" style="max-width: 900px;">
            <div class="modal-header">
                <h3 id="viewTitle">Food Details</h3>
                <button class="modal-close" onclick="closeViewModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-content" style="padding: 0; background: var(--light);">
                <div id="viewContent">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
            <div class="modal-footer"
                style="padding: 15px 25px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; background: white;">
                <button class="btn btn-outline" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const fctModal = document.getElementById('fctModal');
        const viewModal = document.getElementById('viewModal');
        const fctForm = document.getElementById('fctForm');
        const nutrientContainer = document.getElementById('nutrientContainer');

        document.addEventListener('DOMContentLoaded', function () {
            window.choicesInstances = window.choicesInstances || {};

            // --- Filter Category Select ---
            let filterSelectEl = document.querySelector('.searchable-select');
            if (filterSelectEl) {
                window.choicesInstances['filter'] = new Choices(filterSelectEl, {
                    searchEnabled: false,
                    itemSelectText: '',
                    shouldSort: false,
                    position: 'auto',
                    allowHTML: false
                });

                // Category change: submit form so PHP applies the exact DB filter
                filterSelectEl.addEventListener('change', function () {
                    this.closest('form').submit();
                });
            }

            // --- Search input: Server-side search with debounce ---
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                let searchTimeout = null;
                searchInput.addEventListener('input', function () {
                    if (searchTimeout) clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.closest('form').submit();
                    }, 500); // 500ms debounce
                });
                
                // Allow Enter to submit form immediately
                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (searchTimeout) clearTimeout(searchTimeout);
                        this.closest('form').submit();
                    }
                });
                
                // Move cursor to the end of input if keeping search value
                let val = searchInput.value;
                searchInput.value = '';
                searchInput.value = val;
            }
        });

        // Modal Select
        document.addEventListener('DOMContentLoaded', function () {
            const modalSelect = document.querySelector('.searchable-select-modal');
            if (modalSelect) {
                window.choicesInstances['modalCategory'] = new Choices(modalSelect, {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    position: 'auto',
                    allowHTML: true
                });
            }
        });

        // Ant Design style message toast
        function antMessage(text, type = 'success') {
            const msg = document.createElement('div');
            msg.className = `ant-message ant-message-${type}`;
            msg.innerHTML = `
                <div class="ant-message-content" style="padding: 10px 16px; border-radius: 8px; box-shadow: 0 6px 16px 0 rgba(0,0,0,0.08), 0 3px 6px -4px rgba(0,0,0,0.12), 0 9px 28px 8px rgba(0,0,0,0.05); background: #fff; display: inline-flex; align-items: center; font-family: 'Poppins', sans-serif; font-size: 14px; color: #262626;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color: ${type === 'success' ? '#52c41a' : '#ff4d4f'}; margin-right: 8px; font-size: 16px;"></i>
                    <span>${text}</span>
                </div>
            `;
            // Add basic placement styling inline for simplicity
            Object.assign(msg.style, {
                position: 'fixed',
                top: '-50px',
                left: '50%',
                transform: 'translateX(-50%)',
                transition: 'all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1)',
                zIndex: '9999',
                opacity: '0'
            });

            document.body.appendChild(msg);

            // Animate In
            requestAnimationFrame(() => {
                msg.style.top = '24px';
                msg.style.opacity = '1';
            });

            // Animate Out
            setTimeout(() => {
                msg.style.top = '-50px';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            }, 3000);
        }

        function showAddModal() {
            document.getElementById('modalTitle').innerText = 'Add New Food Item';
            fctForm.reset();
            if (window.choicesInstances['modalCategory']) {
                window.choicesInstances['modalCategory'].setChoiceByValue('');
            }
            nutrientContainer.innerHTML = '';

            // Comprehensive PhilFCT Nutrient List
            const PhilFCTDefaults = [
                // Proximates
                { n: 'Water', u: 'g' }, { n: 'Energy', u: 'kcal' }, { n: 'Protein', u: 'g' },
                { n: 'Fat', u: 'g' }, { n: 'Carbohydrate', u: 'g' }, { n: 'Ash', u: 'g' },
                // Other Carbohydrates
                { n: 'Fiber, total dietary', u: 'g' }, { n: 'Sugars, total', u: 'g' },
                // Minerals
                { n: 'Calcium', u: 'mg' }, { n: 'Phosphorus', u: 'mg' }, { n: 'Iron', u: 'mg' },
                { n: 'Sodium', u: 'mg' }, { n: 'Potassium', u: 'mg' },
                // Vitamins
                { n: 'Vitamin A', u: 'ug' }, { n: 'Thiamin (B1)', u: 'mg' },
                { n: 'Riboflavin (B2)', u: 'mg' }, { n: 'Niacin', u: 'mg' }, { n: 'Vitamin C', u: 'mg' },
                // Lipids
                { n: 'SFA', u: 'g' }, { n: 'MUFA', u: 'g' }, { n: 'PUFA', u: 'g' }, { n: 'Cholesterol', u: 'mg' }
            ];

            PhilFCTDefaults.forEach(item => addNutrientRow(item.n, '', item.u));
            fctModal.style.display = 'flex';
        }

        function closeModal() {
            fctModal.style.display = 'none';
            fctForm.reset();
            if (window.choicesInstances['modalCategory']) {
                window.choicesInstances['modalCategory'].setChoiceByValue('');
            }
        }

        function closeViewModal() {
            viewModal.style.display = 'none';
        }

        function addNutrientRow(name = '', value = '', unit = '') {
            const row = document.createElement('div');
            row.className = 'nutrient-row';
            row.innerHTML = `
                <div>
                    <label style="font-size: 0.8rem;">Name</label>
                    <input type="text" class="fct-input n-name" value="${name}" required>
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Value</label>
                    <input type="number" step="0.0001" class="fct-input n-val" value="${value}" required>
                </div>
                <div>
                    <label style="font-size: 0.8rem;">Unit</label>
                    <input type="text" class="fct-input n-unit" value="${unit}" placeholder="g, mg, etc.">
                </div>
                <button type="button" class="remove-nutrient" onclick="this.parentElement.remove()">&times;</button>
            `;
            nutrientContainer.appendChild(row);
        }

        function openTab(tabId, btn) {
            const tabsContainer = btn.closest('.tabs-container');
            // Hide all tab panels
            tabsContainer.querySelectorAll('.tab-content').forEach(el => {
                el.style.display = 'none';
                el.classList.remove('active');
            });
            // Reset all tab button styles
            tabsContainer.querySelectorAll('button.tab').forEach(b => {
                b.classList.remove('active');
                b.style.borderColor = '#e5e7eb';
                b.style.background  = 'white';
                b.style.color       = '#6b7280';
            });
            // Show the target panel
            const target = tabsContainer.querySelector('#' + tabId);
            if (target) {
                target.style.display = 'block';
                target.classList.add('active');
            }
            // Highlight the clicked tab
            btn.classList.add('active');
            btn.style.borderColor = '#3c9d6a';
            btn.style.background  = '#f0faf5';
            btn.style.color       = '#3c9d6a';
        }

        function viewDetails(id) {
            fetch('api/fct_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_details&id=${id}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('viewTitle').innerText = data.item.food_name;

                        // Categorize nutrients
                        const proximates = ['Water', 'Energy', 'Protein', 'Fat', 'Carbohydrate', 'Ash'];
                        const otherCarbs = ['Fiber, total dietary', 'Sugars, total'];
                        const minerals = ['Calcium', 'Phosphorus', 'Iron', 'Sodium', 'Potassium'];
                        const vitamins = ['Vitamin A', 'Thiamin (B1)', 'Riboflavin (B2)', 'Niacin', 'Vitamin C'];
                        const lipids = ['SFA', 'MUFA', 'PUFA', 'Cholesterol'];

                        let grouped = {
                            proximates: [],
                            otherCarbs: [],
                            minerals: [],
                            vitamins: [],
                            lipids: [],
                            others: []
                        };

                        data.nutrients.forEach(n => {
                            let name = n.nutrient_name;
                            if (proximates.some(p => name.includes(p))) grouped.proximates.push(n);
                            else if (otherCarbs.some(p => name.includes(p))) grouped.otherCarbs.push(n);
                            else if (minerals.some(p => name.includes(p))) grouped.minerals.push(n);
                            else if (vitamins.some(p => name.includes(p))) grouped.vitamins.push(n);
                            else if (lipids.some(p => name.includes(p))) grouped.lipids.push(n);
                            else grouped.others.push(n);
                        });

                        // Icon map: nutrient name keyword -> { icon, color, bg }
                        const NUTRIENT_ICONS = {
                            'water':        { icon: 'fa-droplet',     color: '#4A90E2', bg: 'rgba(74,144,226,0.1)'  },
                            'energy':       { icon: 'fa-fire-flame-curved', color: '#FF6B35', bg: 'rgba(255,107,53,0.1)'  },
                            'protein':      { icon: 'fa-dumbbell',    color: '#7B61FF', bg: 'rgba(123,97,255,0.1)' },
                            'fat':          { icon: 'fa-oil-well',    color: '#F5A623', bg: 'rgba(245,166,35,0.1)' },
                            'carbohydrate': { icon: 'fa-wheat-awn',   color: '#E67E22', bg: 'rgba(230,126,34,0.1)' },
                            'ash':          { icon: 'fa-smog',        color: '#95A5A6', bg: 'rgba(149,165,166,0.1)'},
                            'fiber':        { icon: 'fa-seedling',    color: '#27AE60', bg: 'rgba(39,174,96,0.1)'  },
                            'sugar':        { icon: 'fa-candy-cane',  color: '#E91E8C', bg: 'rgba(233,30,140,0.1)' },
                            'calcium':      { icon: 'fa-bone',        color: '#F0E68C', bg: 'rgba(240,230,140,0.15)'},
                            'phosphorus':   { icon: 'fa-atom',        color: '#00BCD4', bg: 'rgba(0,188,212,0.1)'  },
                            'iron':         { icon: 'fa-magnet',      color: '#E53935', bg: 'rgba(229,57,53,0.1)'  },
                            'sodium':       { icon: 'fa-flask',       color: '#1565C0', bg: 'rgba(21,101,192,0.1)' },
                            'potassium':    { icon: 'fa-bolt',        color: '#FDD835', bg: 'rgba(253,216,53,0.1)' },
                            'vitamin a':    { icon: 'fa-eye',         color: '#FF8F00', bg: 'rgba(255,143,0,0.1)'  },
                            'vitamin c':    { icon: 'fa-lemon',       color: '#FFD600', bg: 'rgba(255,214,0,0.12)' },
                            'vitamin':      { icon: 'fa-capsules',    color: '#8BC34A', bg: 'rgba(139,195,74,0.1)' },
                            'thiamin':      { icon: 'fa-pills',       color: '#26C6DA', bg: 'rgba(38,198,218,0.1)' },
                            'riboflavin':   { icon: 'fa-pills',       color: '#AB47BC', bg: 'rgba(171,71,188,0.1)' },
                            'niacin':       { icon: 'fa-pills',       color: '#66BB6A', bg: 'rgba(102,187,106,0.1)'},
                            'beta-carotene':{ icon: 'fa-carrot',      color: '#FF7043', bg: 'rgba(255,112,67,0.1)' },
                            'retinol':      { icon: 'fa-eye',         color: '#FFA726', bg: 'rgba(255,167,38,0.1)' },
                            'cholesterol':  { icon: 'fa-heart-pulse', color: '#EF5350', bg: 'rgba(239,83,80,0.1)'  },
                            'saturated':    { icon: 'fa-droplet',     color: '#FF7043', bg: 'rgba(255,112,67,0.1)' },
                            'mufa':         { icon: 'fa-droplet',     color: '#42A5F5', bg: 'rgba(66,165,245,0.1)' },
                            'pufa':         { icon: 'fa-droplet',     color: '#26A69A', bg: 'rgba(38,166,154,0.1)' },
                        };

                        function getNutrientStyle(name) {
                            const lower = name.toLowerCase();
                            for (const [key, val] of Object.entries(NUTRIENT_ICONS)) {
                                if (lower.includes(key)) return val;
                            }
                            return { icon: 'fa-circle-info', color: '#9B9B9B', bg: 'rgba(155,155,155,0.1)' };
                        }

                        const renderList = (arr) => {
                            if (arr.length === 0) return `
                                <div style="padding:40px;text-align:center;color:#94a3b8;font-family:'Poppins',sans-serif;">
                                    <i class="fas fa-circle-info" style="font-size:2rem;margin-bottom:12px;display:block;"></i>
                                    No data available for this category.
                                </div>`;

                            return '<div style="display:flex;flex-direction:column;gap:10px;">' +
                                arr.map(n => {
                                    const s = getNutrientStyle(n.nutrient_name);
                                    const val = parseFloat(n.value);
                                    const displayVal = Number.isInteger(val) ? val.toString() : val.toFixed(4);
                                    return `
                                    <div class="nutrient-card-modern" style="
                                        display:flex;align-items:center;gap:16px;
                                        padding:14px 20px;background:white;
                                        border-radius:14px;border:1px solid #f0f0f0;
                                        box-shadow:0 2px 6px rgba(0,0,0,0.03);
                                        transition:all 0.25s ease;
                                    ">
                                        <div style="
                                            width:42px;height:42px;border-radius:10px;
                                            background:${s.bg};color:${s.color};
                                            display:flex;align-items:center;justify-content:center;
                                            font-size:1.05rem;flex-shrink:0;
                                        ">
                                            <i class="fas ${s.icon}"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-weight:600;color:#374151;font-size:0.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                ${n.nutrient_name}
                                            </div>
                                            <div style="font-size:0.75rem;color:#9ca3af;margin-top:1px;">per 100 g E.P.</div>
                                        </div>
                                        <div style="text-align:right;flex-shrink:0;">
                                            <div style="font-size:1.2rem;font-weight:700;color:${s.color};">${displayVal}</div>
                                            <div style="font-size:0.72rem;color:#9ca3af;font-weight:500;">${n.unit || ''}</div>
                                        </div>
                                    </div>`;
                                }).join('')
                            + '</div>';
                        };

                        // Tab icons
                        const tabDefs = [
                            { id: 'tab-proximates', label: 'Proximates',  icon: 'fa-list-check',  data: grouped.proximates  },
                            { id: 'tab-othercarbs', label: 'Other Carbs', icon: 'fa-wheat-awn',   data: grouped.otherCarbs  },
                            { id: 'tab-minerals',   label: 'Minerals',    icon: 'fa-gem',         data: grouped.minerals    },
                            { id: 'tab-vitamins',   label: 'Vitamins',    icon: 'fa-capsules',    data: grouped.vitamins    },
                            { id: 'tab-lipids',     label: 'Lipids',      icon: 'fa-oil-well',    data: grouped.lipids      },
                        ];
                        if (grouped.others.length > 0)
                            tabDefs.push({ id: 'tab-others', label: 'Other', icon: 'fa-circle-info', data: grouped.others });

                        const tabButtons = tabDefs.map((t, i) =>
                            `<button class="tab${i===0?' active':''}" onclick="openTab('${t.id}',this)"
                                style="display:inline-flex;align-items:center;gap:7px;
                                    border:1.5px solid ${i===0?'#3c9d6a':'#e5e7eb'};
                                    background:${i===0?'#f0faf5':'white'};
                                    color:${i===0?'#3c9d6a':'#6b7280'};
                                    padding:8px 16px;border-radius:8px;
                                    font-weight:600;font-size:0.85rem;
                                    cursor:pointer;white-space:nowrap;
                                    font-family:'Poppins',sans-serif;">
                                <i class="fas ${t.icon}" style="font-size:0.8rem;"></i>
                                ${t.label} <span style="font-size:0.75rem;opacity:0.7;">(${t.data.length})</span>
                            </button>`
                        ).join('');

                        const tabPanels = tabDefs.map((t, i) =>
                            `<div class="tab-content" id="${t.id}" style="display:${i===0?'block':'none'};">
                                ${renderList(t.data)}
                            </div>`
                        ).join('');

                        let html = `
                        <div style="padding:25px 30px 30px; background:#fafbfc;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid #f1f5f9;">
                                <div style="font-family:'Poppins',sans-serif;">
                                    <div style="font-size:0.75rem;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;">Category</div>
                                    <div style="font-size:1rem;color:#374151;font-weight:600;margin-top:2px;">${data.item.category}</div>
                                </div>
                                <div style="background:linear-gradient(135deg,#3fa5a1,#4a91cf);color:white;padding:7px 18px;border-radius:50px;font-weight:700;font-size:0.95rem;box-shadow:0 4px 14px rgba(63,165,161,0.35);letter-spacing:0.5px;">
                                    ${data.item.food_id}
                                </div>
                            </div>

                            <div class="tabs-container" style="background:transparent;border:none;box-shadow:none;">
                                <div style="display:flex;gap:8px;margin-bottom:20px;overflow-x:auto;padding-bottom:4px;scrollbar-width:none;">
                                    ${tabButtons}
                                </div>
                                ${tabPanels}
                            </div>
                        </div>`;
                        document.getElementById('viewContent').innerHTML = html;
                        viewModal.style.display = 'flex';
                    } else {
                        alert(data.message);
                    }
                });
        }

        function editItem(id) {
            document.getElementById('modalTitle').innerText = 'Edit Food Item';
            fctForm.reset();
            nutrientContainer.innerHTML = '';

            fetch('api/fct_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_details&id=${id}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Auto-fill using antd-like pattern form.setFieldsValue
                        const form = fctForm;
                        form.elements['food_id'].value = data.item.food_id;
                        form.elements['food_name'].value = data.item.food_name;

                        if (window.choicesInstances['modalCategory']) {
                            window.choicesInstances['modalCategory'].setChoiceByValue(data.item.category);
                        } else {
                            form.elements['category'].value = data.item.category;
                        }

                        // Render existing nutrients
                        data.nutrients.forEach(n => {
                            addNutrientRow(n.nutrient_name, n.value, n.unit);
                        });

                        fctModal.style.display = 'flex';
                    } else {
                        antMessage(data.message, 'error');
                    }
                })
                .catch(() => antMessage('Error fetching item details', 'error'));
        }

        fctForm.onsubmit = function (e) {
            e.preventDefault();
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const nutrients = [];
            document.querySelectorAll('.nutrient-row').forEach(row => {
                nutrients.push({
                    name: row.querySelector('.n-name').value,
                    value: row.querySelector('.n-val').value,
                    unit: row.querySelector('.n-unit').value
                });
            });

            const formData = new FormData(fctForm);
            formData.append('nutrients', JSON.stringify(nutrients));

            fetch('api/fct_ajax.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        antMessage('Item successfully saved!', 'success');
                        closeModal();
                        setTimeout(() => location.reload(), 800);
                    } else {
                        antMessage(data.message || 'Error saving item', 'error');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = 'Save Item';
                    }
                });
        };

        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                fetch('api/fct_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&id=${id}`
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        }
        // Diary Logging Logic
        const logModal = document.getElementById('logModal');
        const logForm = document.getElementById('logForm');
        let currentNutrients = null;

        function openLogModal(id) {
            document.getElementById('logFoodItemId').value = id;
            document.getElementById('logFoodName').innerText = 'Loading...';
            document.getElementById('logFoodMacros').innerText = 'Fetching nutritional data...';
            logModal.style.display = 'flex';

            fetch('api/fct_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_details&id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('logFoodName').innerText = data.item.food_name;
                    currentNutrients = data.nutrients;
                    
                    const calories = data.nutrients.find(n => n.nutrient_name === 'Energy') || { value: 0, unit: 'kcal' };
                    const protein = data.nutrients.find(n => n.nutrient_name === 'Protein') || { value: 0, unit: 'g' };
                    const carbs = data.nutrients.find(n => n.nutrient_name === 'Carbohydrate') || { value: 0, unit: 'g' };
                    const fat = data.nutrients.find(n => n.nutrient_name === 'Fat') || { value: 0, unit: 'g' };
                    
                    document.getElementById('logFoodMacros').innerText = 
                        `${calories.value}${calories.unit} | P: ${protein.value}${protein.unit} | C: ${carbs.value}${carbs.unit} | F: ${fat.value}${fat.unit} (per 100g)`;
                }
            });
        }

        function closeLogModal() {
            logModal.style.display = 'none';
            logForm.reset();
        }

        logForm.onsubmit = function(e) {
            e.preventDefault();
            const btn = document.getElementById('logSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging...';

            const formData = new FormData(logForm);
            
            fetch('api/save_log.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    antMessage('Food logged to your diary!', 'success');
                    closeLogModal();
                } else {
                    antMessage(data.message || 'Error logging food', 'error');
                }
            })
            .catch(err => antMessage('Network error', 'error'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'Log Food';
            });
        };
    </script>

    <!-- Debug: Session Role = <?php echo htmlspecialchars($user_role); ?> -->
</body>

</html>

