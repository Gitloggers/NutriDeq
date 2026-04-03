<?php
session_start();
require_once 'database.php';
require_once 'navigation.php';
require_once 'api/fct_helper.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login-logout/NutriDeqN-Login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$db = new Database();
$conn = $db->getConnection();
$fct = new FCTHelper();
// Date selection
$selected_date = $_GET['date'] ?? date('Y-m-d');
$today = date('Y-m-d');

// Fetch logs for the selected date
$sql = "SELECT * FROM food_logs WHERE user_id = :user_id AND log_date = :date ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->execute([':user_id' => $user_id, ':date' => $selected_date]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by meal type
$grouped_logs = [
    'Breakfast' => [],
    'Lunch' => [],
    'Dinner' => [],
    'Snack' => []
];

$totals = [
    'calories' => 0,
    'protein' => 0,
    'carbs' => 0,
    'fat' => 0
];

foreach ($logs as $log) {
    if (isset($grouped_logs[$log['meal_type']])) {
        $grouped_logs[$log['meal_type']][] = $log;
    }
    $totals['calories'] += (float)$log['calories'];
    $totals['protein'] += (float)$log['protein'];
    $totals['carbs'] += (float)$log['carbs'];
    $totals['fat'] += (float)$log['fat'];
}

$nav_links = getNavigationLinks($user_role, 'user-diary.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Food Diary | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <script src="scripts/dashboard.js" defer></script>
    <style>
        .diary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 15px;
        }
        .date-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 10px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .date-nav h2 { margin: 0; font-size: 1.1rem; min-width: 150px; text-align: center; }
        .date-nav a { color: var(--primary); font-size: 1.2rem; transition: transform 0.2s; }
        .date-nav a:hover { transform: scale(1.1); }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid var(--primary);
        }
        .summary-card h3 { margin: 0; font-size: 0.9rem; color: var(--gray); font-weight: 500; }
        .summary-card .value { font-size: 1.5rem; font-weight: 700; color: var(--dark); margin: 5px 0; }
        .summary-card .unit { font-size: 0.8rem; color: var(--gray); }

        .meal-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .meal-header {
            background: #f8f9fa;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        .meal-header h3 { margin: 0; font-size: 1.1rem; color: var(--primary); }
        .meal-total { font-size: 0.85rem; color: var(--gray); font-weight: 500; }

        .food-item {
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.2s;
        }
        .food-item:last-child { border-bottom: none; }
        .food-item:hover { background: #fafafa; }
        .food-info h4 { margin: 0; font-size: 1rem; color: var(--dark); }
        .food-info p { margin: 5px 0 0; font-size: 0.8rem; color: var(--gray); }
        .food-macros { text-align: right; }
        .food-macros .cals { font-weight: 600; color: var(--dark); display: block; }
        .food-macros .macro-breakdown { font-size: 0.75rem; color: var(--gray); }

        .no-logs {
            padding: 30px;
            text-align: center;
            color: #ccc;
        }
        .no-logs i { font-size: 2.5rem; margin-bottom: 10px; display: block; }

        .progress-container {
            margin-top: 10px;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: var(--primary);
            width: 0%;
            transition: width 0.5s ease-out;
        }

        /* Feedback Remarks Styling */
        .remarks-section {
            margin-top: 3rem;
            padding: 25px;
            background: #fdfdfd;
            border-radius: 15px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .remarks-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: var(--primary);
            font-weight: 600;
        }
        .remark-card {
            background: #fff;
            border-left: 4px solid var(--primary);
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .remark-meta {
            font-size: 0.75rem;
            color: var(--gray);
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }
        .remark-content {
            font-size: 0.95rem;
            color: #444;
            line-height: 1.5;
        }
        .modal-overlay {
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }
        
        @media screen and (max-width: 768px) {
            .modal-container {
                width: 95% !important;
                margin: 10px auto !important;
                max-height: 95vh;
            }
            .modal-body {
                padding: 15px !important;
            }
            .fct-table thead {
                display: none;
            }
            .fct-table, .fct-table tbody, .fct-table tr, .fct-table td {
                display: block;
                width: 100%;
            }
            .fct-table tr {
                margin-bottom: 15px;
                border: 1px solid #eee !important;
                border-radius: 12px;
                padding: 10px;
                background: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            }
            .fct-table td {
                text-align: left !important;
                padding: 8px 5px !important;
                border: none !important;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .fct-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--gray);
                font-size: 0.85rem;
            }
            .fct-table td:first-child {
                border-bottom: 1px solid #f9f9f9 !important;
                margin-bottom: 5px;
            }
            /* Reset data labels for clear view */
            .fct-table td:nth-child(2) {
                font-size: 1.1rem;
                display: block;
            }
            .fct-table td:nth-child(2)::before {
                display: none;
            }
            
            .modal-footer {
                display: none !important;
            }
            .modal-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            .modal-header-actions {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .modal-header-actions .btn {
                width: 100%;
                padding: 12px !important;
            }
            .modal-body {
                padding: 15px !important;
            }
            /* Adjust sticky search for mobile header growth */
            .modal-controls {
                top: -15px !important; 
            }
        }
    </style>
    <script>const BASE_URL = '<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/';</script>
</head>
<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-container">
                <div class="diary-header">
                    <div class="page-title">
                        <h1>My Food Diary</h1>
                        <p>Track your daily nutritional intake</p>
                    </div>

                    <div class="date-nav">
                        <?php 
                        $prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
                        $next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
                        $date_label = ($selected_date == $today) ? 'Today' : date('M j, Y', strtotime($selected_date));
                        ?>
                        <a href="?date=<?php echo $prev_date; ?>"><i class="fas fa-chevron-left"></i></a>
                        <h2><?php echo $date_label; ?></h2>
                        <a href="?date=<?php echo $next_date; ?>"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>

                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Calories</h3>
                        <div class="value"><?php echo number_format($totals['calories'], 0); ?></div>
                        <div class="unit">kcal</div>
                    </div>
                    <div class="summary-card" style="border-bottom-color: #4a90e2;">
                        <h3>Protein</h3>
                        <div class="value"><?php echo number_format($totals['protein'], 1); ?></div>
                        <div class="unit">g</div>
                    </div>
                    <div class="summary-card" style="border-bottom-color: #f5a623;">
                        <h3>Carbs</h3>
                        <div class="value"><?php echo number_format($totals['carbs'], 1); ?></div>
                        <div class="unit">g</div>
                    </div>
                    <div class="summary-card" style="border-bottom-color: #d0021b;">
                        <h3>Fat</h3>
                        <div class="value"><?php echo number_format($totals['fat'], 1); ?></div>
                        <div class="unit">g</div>
                    </div>
                </div>

                <?php foreach ($grouped_logs as $meal => $items): ?>
                    <section class="meal-section">
                        <div class="meal-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <h3><?php echo $meal; ?></h3>
                                <button type="button" class="add-meal-btn" 
                                    style="color: #2D8A56; font-size: 1.3rem; background: none; border: none; padding: 0; cursor: pointer; transition: transform 0.2s;"
                                    onclick="openFctModal('<?php echo $meal; ?>')" title="Add food to <?php echo $meal; ?>"
                                    onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                            </div>
                            <?php 
                            $meal_cals = array_sum(array_column($items, 'calories'));
                            ?>
                            <div class="meal-total"><?php echo number_format($meal_cals, 0); ?> kcal</div>
                        </div>
                        <div class="meal-content">
                            <?php if (empty($items)): ?>
                                <div class="no-logs">
                                    <i class="fas fa-utensils"></i>
                                    <p>Nothing logged for <?php echo strtolower($meal); ?></p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <div class="food-item">
                                        <div class="food-info">
                                            <h4><?php echo htmlspecialchars($item['food_name']); ?></h4>
                                            <p><?php echo number_format($item['serving_size'], 0); ?>g serving</p>
                                        </div>
                                        <div class="food-macros">
                                            <span class="cals"><?php echo number_format($item['calories'], 0); ?> kcal</span>
                                            <span class="macro-breakdown">
                                                P: <?php echo number_format($item['protein'], 1); ?>g | 
                                                C: <?php echo number_format($item['carbs'], 1); ?>g | 
                                                F: <?php echo number_format($item['fat'], 1); ?>g
                                            </span>
                                        </div>
                                        <?php if ($user_role === 'user'): ?>
                                            <div class="food-actions" style="margin-left: 15px;">
                                                <button onclick="deleteLog(<?php echo $item['id']; ?>)" class="btn-delete" style="color: #ff4d4f; background: none; border: none; cursor: pointer; font-size: 1.1rem;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>


                <!-- Dietitian's Remarks Section -->
                <?php
                $feedback_sql = "SELECT df.*, u.name as staff_name FROM diary_feedback df JOIN users u ON df.staff_id = u.id WHERE df.user_id = ? AND df.log_date = ? ORDER BY df.created_at DESC";
                $f_stmt = $conn->prepare($feedback_sql);
                $f_stmt->execute([$user_id, $selected_date]);
                $feedbacks = $f_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <section class="remarks-section">
                    <div class="remarks-header">
                        <i class="fas fa-user-md"></i>
                        <span>Dietitian's Remarks</span>
                    </div>
                    <?php if (empty($feedbacks)): ?>
                        <p style="color: #ccc; font-style: italic; font-size: 0.9rem;">No remarks for this day yet.</p>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <div class="remark-card">
                                <div class="remark-meta">
                                    <span><strong><?php echo htmlspecialchars($fb['staff_name']); ?></strong></span>
                                    <span><?php echo date('M j, Y g:i A', strtotime($fb['created_at'])); ?></span>
                                </div>
                                <div class="remark-content"><?php echo nl2br(htmlspecialchars($fb['content'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <!-- FCT Library Modal -->
    <div id="fctModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; overflow-y: auto;">
        <div class="modal-container" style="background: white; width: 95%; max-width: 1000px; margin: 30px auto; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2); display: flex; flex-direction: column; max-height: calc(100vh - 60px);">
            <div class="modal-header" style="background: #f8f9fa; padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                <div>
                    <h2 id="fctModalTitle" style="margin: 0; color: var(--primary);">Add Food to Diary</h2>
                    <div id="selectedCount" style="font-size: 0.85rem; color: var(--gray); margin-top: 4px;">0 items selected</div>
                </div>
                <div class="modal-header-actions" style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="btn btn-outline" style="background: white; border: 1px solid #ddd; padding: 8px 15px; border-radius: 8px; cursor: pointer;" onclick="closeFctModal()">Cancel</button>
                    <button type="button" id="btnAddSelected" class="btn btn-primary" style="background: var(--primary); color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; font-weight: 500;" onclick="submitSelectedFoods()">Add Selected</button>
                    <button type="button" onclick="closeFctModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--gray); margin-left: 5px;">&times;</button>
                </div>
            </div>
            <div class="modal-body" style="padding: 25px; overflow-y: auto; flex-grow: 1;">
                <div class="modal-controls" style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; position: sticky; top: -25px; background: white; padding: 10px 0; z-index: 10;">
                    <div style="flex-grow: 1; position: relative;">
                        <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af;"><i class="fas fa-search"></i></span>
                        <input type="text" id="fctSearch" placeholder="Search food items..." style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    </div>
                </div>
                <div id="fctTableContainer">
                    <table class="fct-table" style="width: 100%; border-collapse: collapse;">
                        <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                            <tr style="border-bottom: 2px solid #eee;">
                                <th style="padding: 12px; text-align: left; width: 40px;"><input type="checkbox" id="selectAll"></th>
                                <th style="padding: 12px; text-align: left;">Food Item</th>
                                <th style="padding: 12px; text-align: center;">Calories</th>
                                <th style="padding: 12px; text-align: center;">Protein</th>
                                <th style="padding: 12px; text-align: center;">Carbs</th>
                                <th style="padding: 12px; text-align: center;">Fat</th>
                            </tr>
                        </thead>
                        <tbody id="fctTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const currentUserRole = '<?php echo $user_role; ?>';
        let currentMealType = '';
        const allFctData = <?php echo json_encode($fct->getAllItems()); ?>;
        
        function openFctModal(mealType) {
            currentMealType = mealType;
            document.getElementById('fctModalTitle').innerText = 'Add Food to ' + mealType;
            document.getElementById('btnAddSelected').innerText = 'Add Selected to ' + mealType;
            document.getElementById('fctModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            renderFctTable();
        }

        function closeFctModal() {
            document.getElementById('fctModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function renderFctTable(filter = '') {
            const tbody = document.getElementById('fctTableBody');
            tbody.innerHTML = '';
            
            const filtered = allFctData.filter(item => 
                item.food_name.toLowerCase().includes(filter.toLowerCase()) ||
                (item.food_id && item.food_id.toString().includes(filter))
            ).slice(0, 100); // Limit to 100 for performance

            filtered.forEach(item => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #f1f1f1';
                tr.innerHTML = `
                    <td style="padding: 12px; text-align: left;"><input type="checkbox" class="food-checkbox" value="${item.id}" style="width: 20px; height: 20px;" onchange="updateSelectedCount()"></td>
                    <td data-label="Food Item" style="padding: 12px; text-align: left;">
                        <span style="font-weight: 500; color: var(--dark);">${item.food_name}</span><br>
                        <small style="color: var(--gray);">${item.category}</small>
                    </td>
                    <td data-label="Calories" style="padding: 12px; text-align: center;">${parseFloat(item.calories || 0).toFixed(0)}</td>
                    <td data-label="Protein" style="padding: 12px; text-align: center;">${parseFloat(item.protein || 0).toFixed(1)}</td>
                    <td data-label="Carbs" style="padding: 12px; text-align: center;">${parseFloat(item.carbs || 0).toFixed(1)}</td>
                    <td data-label="Fat" style="padding: 12px; text-align: center;">${parseFloat(item.fat || 0).toFixed(1)}</td>
                `;
                tbody.appendChild(tr);
            });

            if (filtered.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="padding: 40px; text-align: center; color: var(--gray);">No food items found matching your search.</td></tr>';
            }
            updateSelectedCount();
        }

        document.getElementById('fctSearch').addEventListener('input', (e) => {
            renderFctTable(e.target.value);
        });

        document.getElementById('selectAll').addEventListener('change', (e) => {
            document.querySelectorAll('.food-checkbox').forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateSelectedCount();
        });

        function updateSelectedCount() {
            const count = document.querySelectorAll('.food-checkbox:checked').length;
            document.getElementById('selectedCount').innerText = count + ' items selected';
            document.getElementById('btnAddSelected').disabled = count === 0;
            document.getElementById('btnAddSelected').style.opacity = count === 0 ? '0.5' : '1';
        }

        async function submitSelectedFoods() {
            const selectedIds = Array.from(document.querySelectorAll('.food-checkbox:checked')).map(cb => cb.value);
            if (selectedIds.length === 0) return;

            const btn = document.getElementById('btnAddSelected');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const body = new URLSearchParams();
                body.append('meal_type', currentMealType);
                body.append('serving_size', 100);
                selectedIds.forEach(id => body.append('food_item_ids[]', id));

                const response = await fetch(BASE_URL + 'api/save_log.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Error saving logs');
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An unexpected error occurred');
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }

        async function deleteLog(logId) {
            if (!confirm('Are you sure you want to remove this entry?')) return;
            
            try {
                const response = await fetch(BASE_URL + 'api/delete_log.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${logId}`
                });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to delete log');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during deletion');
            }
        }
    </script>
</body>
</html>

