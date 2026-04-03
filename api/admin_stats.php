<?php
// api/admin_stats.php
session_start();
header('Content-Type: application/json');

require_once '../database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$staff_id = $_REQUEST['staff_id'] ?? 'all';
$database = new Database();
$pdo = $database->getConnection();

try {
    // 1. Ensure hydration_tracking table exists for the feature to work
    $pdo->exec("CREATE TABLE IF NOT EXISTS hydration_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        glasses INT DEFAULT 0,
        tracking_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_date (user_id, tracking_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $response = [
        'success' => true,
        'health_trends' => [],
        'workload' => [
            'total_clients' => 0,
            'max_capacity' => 0
        ],
        'alerts' => [],
        'activity_ratios' => [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'logins' => [0, 0, 0, 0, 0, 0, 0],
            'goals_met' => [0, 0, 0, 0, 0, 0, 0]
        ]
    ];

    // Build Staff Filter Clause
    $where_clause = "";
    $params = [];
    if ($staff_id !== 'all') {
        $where_clause = " WHERE staff_id = ? ";
        $params[] = $staff_id;
    }

    // 2. Health Trends (Last 7 Days)
    $dates = [];
    for ($i = 6; $i >= 0; $i--) {
        $dates[] = date('Y-m-d', strtotime("-$i days"));
    }

    $health_data = [];
    foreach ($dates as $date) {
        $query = "
            SELECT 
                COALESCE(AVG(cal_sum), 0) as avg_cal,
                COALESCE(AVG(water_sum), 0) as avg_water
            FROM (
                SELECT 
                    c.id,
                    (SELECT SUM(calories) FROM food_tracking ft WHERE ft.user_id = c.user_id AND ft.tracking_date = ?) as cal_sum,
                    (SELECT SUM(glasses) FROM hydration_tracking ht WHERE ht.user_id = c.user_id AND ht.tracking_date = ?) as water_sum
                FROM clients c
                " . ($staff_id !== 'all' ? "WHERE c.staff_id = ?" : "") . "
            ) as daily_stats";

        $stmt = $pdo->prepare($query);
        $q_params = [$date, $date];
        if ($staff_id !== 'all')
            $q_params[] = $staff_id;

        $stmt->execute($q_params);
        $row = $stmt->fetch();
        $health_data['labels'][] = date('M d', strtotime($date));
        $health_data['calories'][] = round($row['avg_cal'], 0);
        $health_data['hydration'][] = round($row['avg_water'], 1);
    }
    $response['health_trends'] = $health_data;

    // 3. Workload
    if ($staff_id === 'all') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM clients WHERE status='active'");
        $response['workload']['total_clients'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) * 50 FROM users WHERE role='staff' AND status='active'");
        $response['workload']['max_capacity'] = (int) $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE staff_id = ? AND status='active'");
        $stmt->execute([$staff_id]);
        $response['workload']['total_clients'] = (int) $stmt->fetchColumn();
        $response['workload']['max_capacity'] = 50; // Standard capacity per staff
    }

    // 4. Alerts (No logs in 48 hours)
    $alert_query = "
        SELECT c.name, MAX(ft.tracking_date) as last_log
        FROM clients c
        LEFT JOIN food_tracking ft ON c.user_id = ft.user_id
        " . ($staff_id !== 'all' ? "WHERE c.staff_id = ?" : "") . "
        GROUP BY c.id
        HAVING last_log IS NULL OR last_log < DATE_SUB(CURDATE(), INTERVAL 2 DAY)
        LIMIT 5";

    $stmt = $pdo->prepare($alert_query);
    if ($staff_id !== 'all')
        $stmt->execute([$staff_id]);
    else
        $stmt->execute();
    $response['alerts'] = $stmt->fetchAll();

    // 5. Activity Ratios (Logins vs Goals Met - Mocked logic based on data availability)
    // For a real app, you'd track successful meal plan completions or login counts
    $response['activity_ratios']['logins'] = array_map(function () {
        return rand(10, 50);
    }, range(0, 6));
    $response['activity_ratios']['goals_met'] = array_map(function () {
        return rand(5, 30);
    }, range(0, 6));

    // 6. Staff Engagement Delta Calculation
    $response['engagement_delta'] = null;
    $response['staff_list'] = [];

    // Get all staff with their engagement deltas
    $staff_query = $pdo->query("SELECT id, name, last_login FROM users WHERE role = 'staff' AND status = 'active' ORDER BY name ASC");
    $all_staff = $staff_query->fetchAll();

    foreach ($all_staff as $staff_member) {
        $delta_value = null;
        $sid = $staff_member['id'];

        // Get most recent interaction: MAX of last_login OR last message sent
        $interaction_query = $pdo->prepare("
            SELECT GREATEST(
                COALESCE(u.last_login, '1970-01-01'),
                COALESCE((
                    SELECT MAX(wm.created_at) 
                    FROM wellness_messages wm 
                    JOIN conversations conv ON wm.conversation_id = conv.id 
                    WHERE wm.sender_type = 'staff' AND wm.sender_id = u.id
                ), '1970-01-01')
            ) as last_interaction
            FROM users u WHERE u.id = ?
        ");
        $interaction_query->execute([$sid]);
        $interaction_row = $interaction_query->fetch();
        $last_interaction = $interaction_row['last_interaction'] ?? null;

        if ($last_interaction && $last_interaction !== '1970-01-01') {
            // Calculate Pre-Activity (24h before interaction)
            $pre_query = $pdo->prepare("
                SELECT COUNT(*) as activity_count
                FROM food_tracking ft
                JOIN clients c ON ft.user_id = c.user_id
                WHERE c.staff_id = ?
                AND ft.created_at BETWEEN DATE_SUB(?, INTERVAL 24 HOUR) AND ?
            ");
            $pre_query->execute([$sid, $last_interaction, $last_interaction]);
            $pre_activity = (int) $pre_query->fetchColumn();

            // Calculate Post-Activity (24h after interaction)
            $post_query = $pdo->prepare("
                SELECT COUNT(*) as activity_count
                FROM food_tracking ft
                JOIN clients c ON ft.user_id = c.user_id
                WHERE c.staff_id = ?
                AND ft.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 24 HOUR)
            ");
            $post_query->execute([$sid, $last_interaction, $last_interaction]);
            $post_activity = (int) $post_query->fetchColumn();

            // Calculate delta with division by zero protection
            if ($pre_activity > 0) {
                $delta_value = round((($post_activity - $pre_activity) / $pre_activity) * 100, 1);
            } elseif ($post_activity > 0) {
                $delta_value = 100; // From 0 to something = 100% increase
            } else {
                $delta_value = 0; // No activity before or after
            }
        }

        $response['staff_list'][] = [
            'id' => $sid,
            'name' => $staff_member['name'],
            'engagement_delta' => $delta_value,
            'last_interaction' => $last_interaction
        ];

        // If specific staff selected, set their delta as main response
        if ($staff_id !== 'all' && (int) $staff_id === $sid) {
            $response['engagement_delta'] = $delta_value;
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

