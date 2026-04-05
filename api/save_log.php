<?php
ini_set('display_errors', 0);
session_start();
require_once 'fct_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
if ($user_role !== 'user') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Only users can log food to the diary. Detected role: ' . $user_role]);
    exit();
}

$user_id = $_SESSION['user_id'];
$food_item_ids = $_POST['food_item_ids'] ?? [];
if (!is_array($food_item_ids) && !empty($_POST['food_item_id'])) {
    $food_item_ids = [$_POST['food_item_id']];
}
$meal_type = $_POST['meal_type'] ?? '';
$serving_size = $_POST['serving_size'] ?? 100;

if (empty($food_item_ids) || !$meal_type) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $fct = new FCTHelper();
    
    $conn->beginTransaction();

    $sql = "INSERT INTO food_logs (user_id, food_id, food_name, calories, protein, carbs, fat, serving_size, meal_type, log_date) 
            VALUES (:user_id, :food_id, :food_name, :calories, :protein, :carbs, :fat, :serving_size, :meal_type, :log_date)";
    $stmt = $conn->prepare($sql);

    foreach ($food_item_ids as $id) {
        $details = $fct->getDetails($id);
        if (!$details) continue;

        $food = $details['item'];
        $nutrients = $details['nutrients'];

        // Helper to find nutrient value
        $getNutrientVal = function($nutrients, $name) {
            foreach ($nutrients as $n) {
                if (stripos($n['nutrient_name'], $name) !== false) {
                    return (float)$n['value'];
                }
            }
            return 0;
        };

        $base_calories = $getNutrientVal($nutrients, 'Energy');
        $base_protein = $getNutrientVal($nutrients, 'Protein');
        $base_carbs = $getNutrientVal($nutrients, 'Carbohydrate');
        $base_fat = $getNutrientVal($nutrients, 'Fat');

        $ratio = (float)$serving_size / 100;

        $stmt->execute([
            ':user_id' => $user_id,
            ':food_id' => $food['food_id'],
            ':food_name' => $food['food_name'],
            ':calories' => $base_calories * $ratio,
            ':protein' => $base_protein * $ratio,
            ':carbs' => $base_carbs * $ratio,
            ':fat' => $base_fat * $ratio,
            ':serving_size' => $serving_size,
            ':meal_type' => $meal_type,
            ':log_date' => date('Y-m-d')
        ]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Food(s) logged successfully']);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
