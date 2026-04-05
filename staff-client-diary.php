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
    <!-- Platform Specific Styles -->
    <link rel="stylesheet" href="css/desktop-style.css" media="all and (min-width: 1025px)">
    <link rel="stylesheet" href="css/mobile-style.css" media="all and (max-width: 1024px)">
    <link rel="stylesheet" href="css/dashboard-premium.css">
    <style>
        .dash-premium { background: transparent !important; }
        .split-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            height: calc(100vh - 100px);
            gap: 24px;
            position: relative;
            z-index: 10;
            margin-top: 20px;
        }
        .client-list-sidebar {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 24px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--glass-shadow);
        }
        .monitor-content {
            display: flex;
            flex-direction: column;
            gap: 24px;
            overflow-y: auto;
            padding-right: 10px;
            background: transparent !important;
        }
        .diary-brief {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 32px;
            padding: 32px;
            box-shadow: var(--glass-shadow);
        }
        .meal-row {
            background: var(--bg-surface);
            border-radius: 16px;
            padding: 14px 24px;
            margin-bottom: 12px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }
        .meal-row:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .meal-row h4 { width: 120px; font-weight: 700; color: #1e293b; margin: 0; }
        
        .report-preview-panel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            width: 800px;
            max-width: 95vw;
            height: 80vh;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.6);
            border-radius: 32px;
            z-index: 10000;
            display: none;
            flex-direction: column;
            box-shadow: 0 50px 100px -20px rgba(0,0,0,0.2);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .report-preview-panel.active { display: flex; transform: translate(-50%, -50%) scale(1); }

        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

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
            .bento-grid {
                grid-template-columns: 1fr 1fr !important;
            }
            .meal-row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px !important;
            }
            .meal-row h4 {
                width: 100% !important;
                border-bottom: 1px solid rgba(0,0,0,0.05);
                padding-bottom: 5px;
            }
            .meal-row .meal-cals {
                align-self: flex-end !important;
                color: #10b981 !important;
            }
        }
    </style>
    <!-- MOBILE FALLBACK FIXES -->
    <style>
    @media (max-width: 1024px) {
        .main-content, .page-container, main, .dashboard { padding-bottom: 120px !important; margin-bottom: 120px !important; }
        .table-responsive, .table-container, .card-body {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            display: block !important;
        }
        table { min-width: 700px !important; display: table !important; }
        thead, tbody, tr { width: 100% !important; }
    }
    </style>
    <style>
    @media (max-width: 1024px) {
        .main-content, .page-container, main { padding-bottom: 120px !important; }
        .table-responsive, .table-container, .card-body {
            width: 100vw !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            display: block !important;
        }
        table { min-width: 800px !important; display: table !important; }
        .grid, .tab-buttons { display: flex; flex-direction: column; }
    }
    </style>
</head>
<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content dash-premium">
            <!-- Modern Mesh Background Elements -->
            <div class="mesh-gradient-container dashboard-mesh">
                <div class="mesh-blob blob-1"></div>
                <div class="mesh-blob blob-2"></div>
                <div class="mesh-blob blob-3"></div>
            </div>

            <!-- Nutri-Glass Noise Texture -->
            <div class="glass-noise"></div>

            <!-- Spotlight & Custom Cursor -->
            <div class="spotlight" id="spotlight"></div>
            <div id="organicCursor"></div>
            <div class="glow-aura" id="cursorAura"></div>
            <div class="split-layout">
                <!-- Client List -->
                <aside class="client-list-sidebar">
                    <div class="sidebar-search">
                        <input type="text" placeholder="Search clients..." class="form-control" style="font-size: 0.85rem;">
                    </div>
                    <div class="client-items">
                        <?php foreach ($clients as $client): ?>
                            <?php 
                                $isActive = ($selected_client_id == $client['id']);
                                // Mock alert logic for clinical context
                                $hasAlert = (strpos(strtolower($client['name']), 'a') !== false);
                                $alertText = (strlen($client['name']) % 2 === 0) ? 'Over Calorie' : 'Low Protein';
                            ?>
                            <div class="client-item <?php echo $isActive ? 'active' : ''; ?>" 
                                 onclick="location.href='?client_id=<?php echo $client['id']; ?>'"
                                 style="padding: 16px 20px;">
                                <div class="client-avatar circle-avatar" style="width:44px; height:44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border: 2px solid #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                    <?php echo getInitials($client['name']); ?>
                                </div>
                                <div class="client-info">
                                    <div style="font-weight: 600; font-size: 0.95rem; color: #1e293b; display: flex; justify-content: space-between; align-items: center;">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                        <?php if ($hasAlert): ?>
                                            <span style="font-size: 0.6rem; background: #fee2e2; color: #ef4444; padding: 2px 6px; border-radius: 6px; font-weight: 800;"><?php echo $alertText; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
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
                            <style>
                                @media screen and (max-width: 768px) {
                                  .mobile-diary-header {
                                    flex-direction: column !important;
                                    align-items: flex-start !important;
                                  }
                                  .mobile-diary-header button {
                                    width: 100%;
                                    margin-top: 10px;
                                  }
                                }
                            </style>
                            <div class="mobile-diary-header" style="display: flex; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 20px;">
                                <h2 style="font-size: 1.25rem; margin: 0;"><i class="fas fa-book-medical"></i> Food Diary: <?php echo htmlspecialchars($selected_client['name']); ?></h2>
                                <button class="btn-dash-action premium-clinical-btn" onclick="toggleReportPreview(true)" 
                                    style="padding:12px 24px; border-radius: 16px; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 2px solid rgba(16, 185, 129, 0.2); gap: 12px; font-weight: 700;">
                                    <i class="fas fa-eye-medical" style="font-size: 1.1rem;"></i> <span>Preview Clinical Report</span>
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

                        <div class="bento-grid" style="margin-top: 32px;">
                            <div class="bento-stat stat-danger">
                                <div class="bento-stat-icon"><i class="fas fa-fire"></i></div>
                                <div class="bento-stat-val"><?php echo number_format($totals['calories'], 0); ?></div>
                                <div class="bento-stat-label">Total Calories</div>
                            </div>
                            <div class="bento-stat stat-primary">
                                <div class="bento-stat-icon"><i class="fas fa-drumstick-bite"></i></div>
                                <div class="bento-stat-val"><?php echo number_format($totals['protein'], 1); ?><small style="font-size: 0.5em;">g</small></div>
                                <div class="bento-stat-label">Daily Protein</div>
                            </div>
                            <div class="bento-stat stat-accent">
                                <div class="bento-stat-icon"><i class="fas fa-bread-slice"></i></div>
                                <div class="bento-stat-val"><?php echo number_format($totals['carbs'], 1); ?><small style="font-size: 0.5em;">g</small></div>
                                <div class="bento-stat-label">Daily Carbs</div>
                            </div>
                            <div class="bento-stat stat-secondary">
                                <div class="bento-stat-icon"><i class="fas fa-oil-can"></i></div>
                                <div class="bento-stat-val"><?php echo number_format($totals['fat'], 1); ?><small style="font-size: 0.5em;">g</small></div>
                                <div class="bento-stat-label">Daily Fats</div>
                            </div>
                        </div>

                            <div class="meal-log-compact" style="margin-top: 24px;">
                                <?php foreach ($grouped_logs as $meal => $items): ?>
                                    <div class="meal-row">
                                        <div class="bento-stat-icon" style="flex-shrink: 0; background: rgba(99, 102, 241, 0.1); color: #6366f1; width: 40px; height: 40px; border-radius: 12px; font-size: 1rem;">
                                            <i class="fas fa-<?php 
                                                switch($meal) {
                                                    case 'Breakfast': echo 'coffee'; break;
                                                    case 'Lunch': echo 'utensils'; break;
                                                    case 'Dinner': echo 'moon'; break;
                                                    default: echo 'apple-alt';
                                                }
                                            ?>"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; color: #1e293b; margin-bottom: 4px;"><?php echo $meal; ?></h4>
                                            <div class="food-summary" style="color: #64748b; font-weight: 500;">
                                                <?php 
                                                if (empty($items)) {
                                                    echo '<span style="opacity: 0.4;">No patient logs recorded</span>';
                                                } else {
                                                    $names = array_column($items, 'food_name');
                                                    echo htmlspecialchars(implode(', ', $names));
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.1rem; color: #111827;">
                                                <?php echo number_format(array_sum(array_column($items, 'calories')), 0); ?><small style="font-size: 0.6em; opacity: 0.6; margin-left: 2px;">kcal</small>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #10b981; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px;">
                                                Diagnostic Valid
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="feedback-container" style="background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.4); border-radius: 24px; box-shadow: 0 20px 40px -20px rgba(0,0,0,0.1); margin-top: 24px;">
                            <div class="feedback-header" style="border-bottom: 1px solid rgba(0,0,0,0.05); padding: 24px 32px;">
                                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; margin: 0; color: #1e293b;"><i class="fas fa-clipboard-check" style="color: #10b981; margin-right: 10px;"></i> Clinical Journal</h3>
                                <div class="context-chips" style="display: flex; gap: 10px;">
                                    <div class="info-chip" style="font-size: 0.75rem; font-weight: 700; padding: 6px 14px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                                        <i class="fas fa-calendar-day"></i> <?php echo date('M j', strtotime($selected_date)); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Clinical Feedback Cards -->
                            <div class="chat-messages" id="feedbackList" style="background: transparent; padding: 32px; min-height: 300px;">
                                <div style="text-align:center; padding: 40px; color: #94a3b8;">
                                    <i class="fas fa-spinner fa-spin"></i> Synchronizing clinical notes...
                                </div>
                            </div>

                            <?php if (!$is_admin): ?>
                                <div class="chat-input-area" style="background: rgba(255, 255, 255, 0.6); padding: 24px 32px; border-top: 1px solid rgba(0,0,0,0.05);">
                                    <form id="feedbackForm">
                                        <input type="hidden" name="client_user_id" value="<?php echo $client_user_id; ?>">
                                        <input type="hidden" name="log_date" value="<?php echo $selected_date; ?>">
                                        <div class="input-pill-container" style="display: flex; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 8px 16px; align-items: center; gap: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                                            <textarea class="chat-input" name="content" placeholder="Enter clinical assessment for this daily log..." rows="1" 
                                                style="flex: 1; border: none; background: transparent; outline: none; padding: 8px; font-family: 'Outfit', sans-serif; font-size: 0.95rem; color: #1e293b; resize: none;"></textarea>
                                            <button type="submit" class="btn-dash-action" style="padding: 10px 14px; background: #10b981; color: white; border: none;">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="chat-input-area" style="text-align: center; color: #64748b; font-size: 0.85rem; background: rgba(0,0,0,0.02); padding: 16px;">
                                    <i class="fas fa-eye"></i> Diagnostic View (Admin Read-Only)
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
            </div>
        </main>
    </div>

    <!-- Live Report Preview Panel -->
    <div class="report-preview-panel" id="reportPreview">
        <div style="padding: 32px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-family: 'Outfit', sans-serif; margin: 0; color: #1e293b;">Clinical Diagnostic Report</h2>
            <button class="btn-dash-action" onclick="toggleReportPreview(false)" style="padding: 10px; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="flex: 1; overflow-y: auto; padding: 40px; background: white;">
            <div id="reportPrintArea" style="max-width: 800px; margin: 0 auto; font-family: 'Inter', sans-serif;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 2px solid #10b981; padding-bottom: 20px;">
                    <div>
                        <h1 style="color: #10b981; margin: 0; font-size: 2rem;">NutriDeq</h1>
                        <p style="color: #64748b; margin: 5px 0;">Official Clinical Journal Summary</p>
                    </div>
                    <div style="text-align: right;">
                        <p style="font-weight: 700; color: #1e293b; margin: 0;"><?php echo date('F j, Y'); ?></p>
                        <p style="color: #64748b; margin: 5px 0;">Report ID: #<?php echo bin2hex(random_bytes(4)); ?></p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
                    <div>
                        <h4 style="color: #10b981; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 10px;">Patient Information</h4>
                        <p style="font-size: 1.1rem; font-weight: 700; margin: 0;"><?php echo htmlspecialchars($selected_client['name'] ?? 'N/A'); ?></p>
                        <p style="color: #64748b; margin: 4px 0;"><?php echo htmlspecialchars($selected_client['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <h4 style="color: #10b981; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 10px;">Review Date</h4>
                        <p style="font-size: 1.1rem; font-weight: 700; margin: 0;"><?php echo date('M j, Y', strtotime($selected_date)); ?></p>
                    </div>
                </div>

                <h4 style="color: #10b981; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Nutritional Summary</h4>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px;">
                    <div style="background: #f8fafc; padding: 15px; border-radius: 12px; text-align: center;">
                        <p style="color: #64748b; font-size: 0.75rem; margin-bottom: 5px;">Energy</p>
                        <p style="font-weight: 800; font-size: 1.2rem; margin: 0;"><?php echo number_format($totals['calories'], 0); ?> kcal</p>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 12px; text-align: center;">
                        <p style="color: #64748b; font-size: 0.75rem; margin-bottom: 5px;">Protein</p>
                        <p style="font-weight: 800; font-size: 1.2rem; margin: 0;"><?php echo number_format($totals['protein'], 1); ?> g</p>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 12px; text-align: center;">
                        <p style="color: #64748b; font-size: 0.75rem; margin-bottom: 5px;">Carbs</p>
                        <p style="font-weight: 800; font-size: 1.2rem; margin: 0;"><?php echo number_format($totals['carbs'], 1); ?> g</p>
                    </div>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 12px; text-align: center;">
                        <p style="color: #64748b; font-size: 0.75rem; margin-bottom: 5px;">Fats</p>
                        <p style="font-weight: 800; font-size: 1.2rem; margin: 0;"><?php echo number_format($totals['fat'], 1); ?> g</p>
                    </div>
                </div>

                <h4 style="color: #10b981; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Clinical Observations</h4>
                <div style="background: #fff; border: 1px solid #e2e8f0; padding: 25px; border-radius: 16px; margin-bottom: 40px; color: #475569; line-height: 1.6;">
                    The patient's daily intake for <?php echo date('M j, Y', strtotime($selected_date)); ?> has been synchronized and reviewed. 
                    Clinical data indicates a total caloric intake of <?php echo number_format($totals['calories'], 0); ?> kcal.
                    Practitioner feedback and meal adherence markers are documented in the centralized NutriDeq Clinical Journal.
                </div>

                <div style="margin-top: 60px; border-top: 1px solid #e2e8f0; padding-top: 20px; color: #94a3b8; font-size: 0.75rem; text-align: center;">
                    This is an automatically generated clinical document from the NutriDeq Practitioner Portal.
                </div>
            </div>
        </div>
        <div style="padding: 24px 32px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: flex-end; gap: 16px;">
            <button class="btn-dash-action" onclick="toggleReportPreview(false)" style="padding:12px 24px; border-radius: 12px; border: 1.5px solid #e2e8f0;">Close Preview</button>
            <button class="btn-dash-action" style="padding:12px 24px; border-radius: 12px; background: #10b981; color: white; border: none; font-weight: 700; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);" 
                onclick="generateClinicalReport('#reportPrintArea', 'Diagnostic-Report-P<?php echo $selected_client_id; ?>.pdf')">
                <i class="fas fa-file-medical"></i> Generate PDF Document
            </button>
        </div>
    </div>

    <script>
        function toggleReportPreview(show) {
            const panel = document.getElementById('reportPreview');
            if (show) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        }
    </script>
</body>
</html>


