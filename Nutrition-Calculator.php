<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login-logout/NutriDeqN-Login.php");
    exit();
}

// Get user data from session
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

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

$user_initials = getInitials($user_name);
require_once 'navigation.php';
$nav_links_array = getNavigationLinks($user_role, 'Nutrition-Calculator.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>NutriDeq - Nutrition Calculator</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/calculator.css">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <script src="scripts/dashboard.js" defer></script>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-container">
                <div class="header">
                <div class="page-title">
                    <h1>Nutrition Calculator</h1>
                    <p>Calculate your energy requirements and body composition metrics</p>
                </div>
            </div>

            <div class="calculator-container">
                <!-- Input Section -->
                <div class="calculator-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-edit"></i> Personal Information</h2>
                    </div>

                    <div class="input-grid">
                        <div class="form-group">
                            <label for="sex">Sex</label>
                            <select class="form-control" id="sex">
                                <option value="">Select Sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" class="form-control" id="dob">
                        </div>

                        <div class="form-group">
                            <label for="weight">Weight</label>
                            <div class="select-group">
                                <input type="number" class="form-control" id="weight" placeholder="0">
                                <select class="form-control">
                                    <option>kg</option>
                                    <option>lbs</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="height">Height</label>
                            <div class="select-group">
                                <input type="text" class="form-control" id="height" placeholder="0">
                                <select class="form-control">
                                    <option>cm</option>
                                    <option>ft</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="activity">Physical Activity Level</label>
                            <select class="form-control" id="activity">
                                <option value="">Select Activity Level</option>
                                <option value="sedentary">Sedentary</option>
                                <option value="light">Lightly Active</option>
                                <option value="moderate">Moderately Active</option>
                                <option value="very">Very Active</option>
                                <option value="extra">Extra Active</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="waist">Waist Circumference</label>
                            <div class="select-group">
                                <input type="number" class="form-control" id="waist" placeholder="0">
                                <select class="form-control">
                                    <option>cm</option>
                                    <option>in</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="hip">Hip Circumference</label>
                            <div class="select-group">
                                <input type="number" class="form-control" id="hip" placeholder="0">
                                <select class="form-control">
                                    <option>cm</option>
                                    <option>in</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button class="calculate-btn" id="calculateBtn">
                            <i class="fas fa-calculator"></i> Calculate All Metrics
                        </button>
                        <button class="reset-btn" id="resetBtn">
                            <i class="fas fa-redo"></i> Reset All Inputs
                        </button>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="calculator-card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-bar"></i> Calculation Results</h2>
                    </div>

                    <div class="results-grid">
                        <div class="result-card">
                            <div class="result-value" id="energyResult">--</div>
                            <div class="result-label">
                                Energy Requirement
                                <button class="info-btn" title="Learn More"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="result-status">kcal/day</div>
                        </div>

                        <div class="result-card">
                            <div class="result-value" id="bmiResult">--</div>
                            <div class="result-label">
                                Body Mass Index
                                <button class="info-btn" title="Learn More"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="result-status" id="bmiStatus">--</div>
                        </div>

                        <div class="result-card">
                            <div class="result-value" id="whrResult">--</div>
                            <div class="result-label">
                                Waist-Hip Ratio
                                <button class="info-btn" title="Learn More"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="result-status" id="whrStatus">--</div>
                        </div>

                        <div class="result-card">
                            <div class="result-value" id="whtrResult">--</div>
                            <div class="result-label">
                                Waist-Height Ratio
                                <button class="info-btn" title="Learn More"><i class="fas fa-info-circle"></i></button>
                            </div>
                            <div class="result-status" id="whtrStatus">--</div>
                        </div>
                    </div>

                    <div class="method-selection">
                        <h3 style="margin-bottom: 15px;">Desirable Body Weight Method</h3>
                        <div class="method-options">
                            <div class="method-option active" data-method="tannhauser">
                                Tannhauser Formula
                            </div>
                            <div class="method-option" data-method="hamwi">
                                Hamwi Formula
                            </div>
                            <div class="method-option" data-method="bmi">
                                Body Mass Index
                            </div>
                            <div class="method-option" data-method="kilogram">
                                Kilogram
                            </div>
                        </div>
                    </div>

                    <div class="result-card" style="margin-top: 20px;">
                        <div class="result-value" id="weightResult">--</div>
                        <div class="result-label">Desirable Body Weight</div>
                        <div class="result-status" id="weightUnit">kg</div>
                    </div>
                </div>
            </div>

            <!-- Disclaimer Section -->
            <div class="disclaimer-box">
                <h4><i class="fas fa-exclamation-triangle"></i> Important Notice</h4>
                <p>For personalized diet and nutrition concerns, it is best to seek advice from a Registered
                    Nutritionist-Dietitian.</p>
                <?php if ($user_role === 'regular'): ?>
                    <button class="consult-btn" id="consultBtn">
                        <i class="fas fa-user-md"></i> CONSULT NOW!
                    </button>
                <?php endif; ?>
                <p style="margin-top: 15px; font-size: 0.8rem; color: var(--gray);">
                    Results are estimates and should not replace professional medical advice.
                </p>
            </div>
            </div> <!-- end page-container -->
        </main> <!-- end main-content -->

</body>

</html>
