<?php
session_start();
require_once 'database.php';
require_once 'navigation.php';

// Check staff/admin login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] !== 'staff' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] === 'admin');
$db = new Database();
$conn = $db->getConnection();

// Fetch Clients
$clients = [];
try {
    $client_check = $is_admin ? "" : "WHERE staff_id = ?";
    $sql = "SELECT id, name, email FROM clients $client_check ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $params = $is_admin ? [] : [$staff_id];
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
$selected_client = null;
if ($selected_client_id) {
    foreach ($clients as $c) {
        if ($c['id'] == $selected_client_id) {
            $selected_client = $c;
            break;
        }
    }
}

// Date selection
$selected_date = $_GET['date'] ?? date('Y-m-d');
$today = date('Y-m-d');

$logs = [];
$totals = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
$grouped_logs = ['Breakfast' => [], 'Lunch' => [], 'Dinner' => [], 'Snack' => []];

if ($selected_client) {
    // Get the actual user_id for the client
    $user_sql = "SELECT user_id FROM clients WHERE id = ?";
    $u_stmt = $conn->prepare($user_sql);
    $u_stmt->execute([$selected_client_id]);
    $u_row = $u_stmt->fetch();
    $client_user_id = $u_row['user_id'] ?? null;

    if ($client_user_id) {
        $log_sql = "SELECT * FROM food_logs WHERE user_id = :user_id AND log_date = :date ORDER BY created_at ASC";
        $l_stmt = $conn->prepare($log_sql);
        $l_stmt->execute([':user_id' => $client_user_id, ':date' => $selected_date]);
        $logs = $l_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($logs as $log) {
            if (isset($grouped_logs[$log['meal_type']])) {
                $grouped_logs[$log['meal_type']][] = $log;
            }
            $totals['calories'] += (float)$log['calories'];
            $totals['protein'] += (float)$log['protein'];
            $totals['carbs'] += (float)$log['carbs'];
            $totals['fat'] += (float)$log['fat'];
        }
    }
}

function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) { if ($n) $initials .= strtoupper($n[0]); }
    return substr($initials, 0, 2);
}

$nav_links = getNavigationLinks($_SESSION['user_role'], 'staff-client-diary.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Diary Monitor | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/modern-messages.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/staff-diary-premium.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="scripts/report-generator.js" defer></script>
    <script src="scripts/dashboard.js" defer></script>
    <style>
        .split-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            height: calc(100vh - 40px);
            gap: 20px;
        }
        .client-list-sidebar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sidebar-search {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .client-items {
            flex: 1;
            overflow-y: auto;
        }
        .client-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #f9f9f9;
        }
        .client-item:hover { background: #f8f9fa; }
        .client-item.active { background: #f0f7f4; border-left: 4px solid var(--primary); }
        .client-avatar {
            width: 35px;
            height: 35px;
            background: #eee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            color: #666;
        }
        .client-item.active .client-avatar { background: var(--primary); color: white; }

        .monitor-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .diary-brief {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
        }

        .summary-mini {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .mini-card {
            padding: 12px;
            border-radius: 10px;
            background: #f8f9fa;
            text-align: center;
        }
        .mini-card .val { display: block; font-weight: 700; color: var(--dark); }
        .mini-card .lbl { font-size: 0.75rem; color: var(--gray); text-transform: uppercase; }

        .meal-log-compact {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .meal-row {
            padding: 10px 15px;
            background: #fbfbfb;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .meal-row h4 { margin: 0; font-size: 0.9rem; color: var(--primary); width: 100px; }
        .meal-row .food-summary { flex: 1; font-size: 0.85rem; color: #444; }
        .meal-row .meal-cals { font-weight: 600; font-size: 0.85rem; }

        .feedback-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 400px;
        }
        .feedback-header {
            padding: 15px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fafafa;
        }
        
        /* Message/Input Styling from modern-messages.css should handle the rest */
        .chat-input-area {
            padding: 15px 25px;
            border-top: 1px solid #eee;
        }

        .empty-selection {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #ccc;
            text-align: center;
        }
        .empty-selection i { font-size: 4rem; margin-bottom: 20px; }

        .date-picker-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }
        .date-picker-nav a { color: var(--primary); }

        /* Mobile Responsive Overrides */
        @media screen and (max-width: 1024px) {
            .split-layout {
                grid-template-columns: 1fr !important;
                height: auto !important;
                overflow: visible !important;
            }
            .client-list-sidebar {
                max-height: 300px !important;
                margin-bottom: 20px !important;
            }
            .monitor-content {
                height: auto !important;
                overflow: visible !important;
                padding-right: 0 !important;
            }
            .summary-mini {
                grid-template-columns: 1fr 1fr !important;
            }
            .meal-row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 5px !important;
            }
            .meal-row h4 {
                width: 100% !important;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
            }
            .meal-row .meal-cals {
                align-self: flex-end !important;
                color: var(--primary) !important;
            }
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="split-layout">
                <!-- Client List -->
                <aside class="client-list-sidebar">
                    <div class="sidebar-search">
                        <input type="text" placeholder="Search clients..." class="form-control" style="font-size: 0.85rem;">
                    </div>
                    <div class="client-items">
                        <?php foreach ($clients as $client): ?>
                            <div class="client-item <?php echo $selected_client_id == $client['id'] ? 'active' : ''; ?>" 
                                 onclick="location.href='?client_id=<?php echo $client['id']; ?>'">
                                <div class="client-avatar"><?php echo getInitials($client['name']); ?></div>
                                <div class="client-info">
                                    <div style="font-weight: 600; font-size: 0.9rem; color: var(--dark);">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--gray);">
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <!-- Monitor & Feedback -->
                <section class="monitor-content">
                    <?php if ($selected_client): ?>
                        <div class="diary-brief">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                                <h2 style="font-size: 1.25rem; margin: 0;"><i class="fas fa-book-medical"></i> Food Diary: <?php echo htmlspecialchars($selected_client['name']); ?></h2>
                                <button class="btn btn-primary" onclick="generateClinicalReport('.monitor-content', 'Patient-Journal-Report.pdf')" style="padding: 8px 16px; font-size: 0.85rem;">
                                    <i class="fas fa-file-pdf"></i> Download Report
                                </button>
                            </div>
                                <div class="date-picker-nav">
                                    <?php 
                                    $prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
                                    $next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
                                    ?>
                                    <a href="?client_id=<?php echo $selected_client_id; ?>&date=<?php echo $prev_date; ?>"><i class="fas fa-chevron-left"></i></a>
                                    <span style="font-weight: 600;"><?php echo date('M j, Y', strtotime($selected_date)); ?></span>
                                    <a href="?client_id=<?php echo $selected_client_id; ?>&date=<?php echo $next_date; ?>"><i class="fas fa-chevron-right"></i></a>
                                </div>
                            </div>

                            <div class="summary-mini">
                                <div class="mini-card">
                                    <span class="val"><?php echo number_format($totals['calories'], 0); ?></span>
                                    <span class="lbl">Calories</span>
                                </div>
                                <div class="mini-card">
                                    <span class="val" style="color: #4a90e2;"><?php echo number_format($totals['protein'], 1); ?>g</span>
                                    <span class="lbl">Protein</span>
                                </div>
                                <div class="mini-card">
                                    <span class="val" style="color: #f5a623;"><?php echo number_format($totals['carbs'], 1); ?>g</span>
                                    <span class="lbl">Carbs</span>
                                </div>
                                <div class="mini-card">
                                    <span class="val" style="color: #d0021b;"><?php echo number_format($totals['fat'], 1); ?>g</span>
                                    <span class="lbl">Fat</span>
                                </div>
                            </div>

                            <div class="meal-log-compact">
                                <?php foreach ($grouped_logs as $meal => $items): ?>
                                    <div class="meal-row">
                                        <h4><?php echo $meal; ?></h4>
                                        <div class="food-summary">
                                            <?php 
                                            if (empty($items)) {
                                                echo '<span style="color: #ddd;">No logs</span>';
                                            } else {
                                                $names = array_column($items, 'food_name');
                                                echo htmlspecialchars(implode(', ', $names));
                                            }
                                            ?>
                                        </div>
                                        <div class="meal-cals">
                                            <?php echo number_format(array_sum(array_column($items, 'calories')), 0); ?> kcal
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="feedback-container">
                            <div class="feedback-header">
                                <h3 style="font-size: 1rem; margin: 0; color: var(--gray);"><i class="fas fa-clipboard-check"></i> Clinical Feedback Log</h3>
                                <div class="context-chips" style="display: flex; gap: 10px;">
                                    <div class="info-chip" style="font-size: 0.75rem; padding: 4px 10px; background: #f0f7f4; color: var(--primary); border-radius: 20px;">
                                        <i class="fas fa-calendar-day"></i> <?php echo date('M j, Y', strtotime($selected_date)); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Clinical Feedback Cards -->
                            <div class="chat-messages" id="feedbackList" style="background: #fff; padding: 25px;">
                                <div style="text-align:center; padding: 40px; color: #999;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading context...
                                </div>
                            </div>

                            <?php if (!$is_admin): ?>
                                <div class="chat-input-area" style="background: #f8f9fa;">
                                    <form id="feedbackForm">
                                        <input type="hidden" name="client_user_id" value="<?php echo $client_user_id; ?>">
                                        <input type="hidden" name="log_date" value="<?php echo $selected_date; ?>">
                                        <div class="input-pill-container" style="display: flex; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 2px solid #f0f0f0; border-radius: 25px; padding: 5px 15px; align-items: center; gap: 10px;">
                                            <textarea class="chat-input" name="content" placeholder="Write clinical feedback for this day's log..." rows="1" 
                                                style="flex: 1; border: none; background: transparent; outline: none; padding: 10px; font-family: inherit; resize: none;"></textarea>
                                            <button type="submit" class="send-btn" style="background: var(--primary); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="chat-input-area" style="text-align: center; color: #999; font-size: 0.85rem; background: #f1f1f1;">
                                    <i class="fas fa-eye"></i> Admin Read-Only
                                </div>
                            <?php endif; ?>
                        </div>

                        <style>
                            .feedback-card {
                                background: #fcfcfc;
                                border: 1px solid #f0f0f0;
                                border-radius: 12px;
                                padding: 15px 20px;
                                margin-bottom: 20px;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.02);
                                border-left: 4px solid var(--primary);
                            }
                            .feedback-card-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-bottom: 8px;
                                font-size: 0.8rem;
                                color: var(--gray);
                            }
                            .feedback-card-header strong { color: var(--dark); font-weight: 600; }
                            .feedback-card-content {
                                font-size: 0.95rem;
                                color: #444;
                                line-height: 1.5;
                                white-space: pre-wrap;
                            }
                        </style>

                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const feedbackList = document.getElementById('feedbackList');
                                const feedbackForm = document.getElementById('feedbackForm');
                                const clientUserId = <?php echo $client_user_id; ?>;
                                const logDate = '<?php echo $selected_date; ?>';

                                function fetchFeedback() {
                                    fetch(`api/diary_feedback_ajax.php?action=fetch&user_id=${clientUserId}&log_date=${logDate}`)
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success) {
                                                renderFeedback(data.feedback);
                                            }
                                        });
                                }

                                function renderFeedback(feedback) {
                                    if (feedback.length === 0) {
                                        feedbackList.innerHTML = `
                                            <div style="text-align:center; padding: 40px; color: #ccc;">
                                                <i class="fas fa-clipboard-list" style="font-size: 3rem; opacity: 0.2; margin-bottom: 10px;"></i>
                                                <p>No clinical feedback logged for this day yet.</p>
                                            </div>`;
                                        return;
                                    }

                                    feedbackList.innerHTML = feedback.map(item => `
                                        <div class="feedback-card">
                                            <div class="feedback-card-header">
                                                <span><strong>Dietitian Note</strong> - ${item.staff_name}</span>
                                                <span>${new Date(item.created_at).toLocaleString()}</span>
                                            </div>
                                            <div class="feedback-card-content">${escapeHtml(item.content)}</div>
                                        </div>
                                    `).join('');
                                    feedbackList.scrollTop = feedbackList.scrollHeight;
                                }

                                function escapeHtml(text) {
                                    const div = document.createElement('div');
                                    div.textContent = text;
                                    return div.innerHTML;
                                }

                                if (feedbackForm) {
                                    feedbackForm.onsubmit = function(e) {
                                        e.preventDefault();
                                        const formData = new FormData(feedbackForm);
                                        formData.append('action', 'save');
                                        formData.append('user_id', clientUserId);

                                        const btn = feedbackForm.querySelector('button[type="submit"]');
                                        const originalBtn = btn.innerHTML;
                                        btn.disabled = true;
                                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                                        fetch('api/diary_feedback_ajax.php', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success) {
                                                feedbackForm.querySelector('textarea').value = '';
                                                fetchFeedback();
                                            } else {
                                                alert(data.message || 'Error saving feedback');
                                            }
                                        })
                                        .finally(() => {
                                            btn.disabled = false;
                                            btn.innerHTML = originalBtn;
                                        });
                                    };

                                    feedbackForm.querySelector('textarea').addEventListener('input', function() {
                                        this.style.height = 'auto';
                                        this.style.height = (this.scrollHeight) + 'px';
                                    });
                                }

                                fetchFeedback();
                            });
                        </script>

                    <?php else: ?>
                        <div class="empty-selection">
                            <i class="fas fa-user-circle"></i>
                            <h2>Select a client to monitor</h2>
                            <p>Choose a client from the sidebar to view their food logs and provide feedback.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

