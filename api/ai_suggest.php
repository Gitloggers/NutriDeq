<?php
ini_set('display_errors', 0);
// api/ai_suggest.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'regular';
$context = isset($_GET['context']) ? strtolower(trim($_GET['context'])) : '';

$suggestions = [];

// Helper for simple keyword matching (Mock AI)
function getContextSuggestions($ctx, $role) {
    $s = [];
    
    // Universal greetings
    if (strpos($ctx, 'hello') !== false || strpos($ctx, 'hi') !== false || strpos($ctx, 'hey') !== false) {
        $s[] = "Hi there! How can I help today?";
        $s[] = "Hello! good to see you.";
        $s[] = "Greetings!";
    }

    if ($role === 'staff') {
        // Staff replying to Client
        if (strpos($ctx, 'pain') !== false || strpos($ctx, 'hurt') !== false || strpos($ctx, 'signal') !== false) {
            $s[] = "I'm sorry to hear that. Can you describe the pain?";
            $s[] = "Let's monitor that closely. How long has it been hurting?";
            $s[] = "Remember to listen to your body's signals.";
        }
        if (strpos($ctx, 'diet') !== false || strpos($ctx, 'food') !== false || strpos($ctx, 'eat') !== false || strpos($ctx, 'meal') !== false) {
            $s[] = "Are you finding the current meal plan manageable?";
            $s[] = "We can adjust the portions if needed.";
            $s[] = "Would you like to adjust the meal plan for next week?";
        }
        if (strpos($ctx, 'thanks') !== false || strpos($ctx, 'thank you') !== false) {
            $s[] = "You're very welcome!";
            $s[] = "Happy to help!";
            $s[] = "Great work staying consistent!";
        }
        
        // Defaults if no context match
        if (empty($s)) {
            $s = [
                "That sounds like a great step forward.",
                "How have you been feeling about your energy levels?",
                "Remember to listen to your body's signals.",
                "Would you like to adjust the meal plan for next week?",
                "Great work staying consistent!",
                "I'll be here if you have any questions."
            ];
        }
    } else {
        // Client replying to Staff
        if (strpos($ctx, 'plan') !== false || strpos($ctx, 'meal') !== false || strpos($ctx, 'diet') !== false) {
            $s[] = "I'll try to stick to the plan this week.";
            $s[] = "Can we swap out one of the meals?";
            $s[] = "The current meal plan is working well for me.";
        }
        if (strpos($ctx, 'feeling') !== false || strpos($ctx, 'levels') !== false || strpos($ctx, 'energy') !== false) {
            $s[] = "I'm feeling much better today.";
            $s[] = "Still feeling a bit tired to be honest.";
            $s[] = "My energy levels have been quite steady.";
        }
        
        // Defaults
        if (empty($s)) {
            $s = [
                "I've just uploaded my meal log for today.",
                "I'm feeling a bit low on energy lately.",
                "Can we check my progress on the keto plan?",
                "I have a question about the new recipe.",
                "Thanks for the support!",
                "I'll keep you updated on my progress."
            ];
        }
    }
    
    return $s;
}

$suggestions = getContextSuggestions($context, $role);

// Randomize slightly for "Dynamic" feel
shuffle($suggestions);
$response_suggestions = array_slice($suggestions, 0, 3);

echo json_encode([
    'success' => true,
    'suggestions' => $response_suggestions
]);
?>
