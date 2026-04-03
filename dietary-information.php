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

// Role-based navigation links
// Role-based navigation links
require_once 'navigation.php';
$nav_links_array = getNavigationLinks($user_role, 'dietary-information.php');
// The loop below uses $nav_links, so we assign it.
$nav_links = $nav_links_array;

// Redundant function removed as it is now defined in navigation.php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>NutriDeq - Dietary Information</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="stylesheet" href="css/nutrifacts.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <!-- Base and Mobile styles last for priority -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <!-- dashboard.js included via sidebar.php -->
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-container">
                <div class="header">
                    <div class="page-title">
                        <h1>Dietary Information</h1>
                        <p>Calculate nutrient percentages and analyze food labels</p>
                    </div>
                </div>

                <!-- Nutrition Facts Content -->
                <div class="nutrition-facts-section">
                    <!-- Input Section -->
                    <div class="nutrition-card fade-in">
                        <div class="card-header">
                            <h2><i class="fas fa-edit"></i> Nutrient Input</h2>
                        </div>

                        <p class="instruction-text">Select nutrient groups and input the weight per serving size as stated in the nutrition facts or food label.</p>

                        <!-- Nutrient Group Tabs -->
                        <div class="tabs-header" style="margin-bottom: 25px; top: 0; position: relative; background: #f1f2f6; border-radius: 12px; padding: 6px;">
                            <button class="tab active" onclick="switchNutrientTab('group-essentials', this)" style="flex:1; justify-content: center;">
                                <i class="fas fa-flask"></i> <span>Minerals</span>
                            </button>
                            <button class="tab" onclick="switchNutrientTab('group-vitamins', this)" style="flex:1; justify-content: center;">
                                <i class="fas fa-tablets"></i> <span>Vitamins</span>
                            </button>
                        </div>

                        <div id="group-essentials" class="nutrient-tab-content">
                            <div class="nutrient-grid">
                                <!-- Group 1: Essentials & Minerals -->
                                <div class="nutrient-item" data-nutrient="sodium">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Sodium (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="sodium">
                                </div>
                                <div class="nutrient-item" data-nutrient="potassium">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Potassium (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="potassium">
                                </div>
                                <div class="nutrient-item" data-nutrient="dietary-fiber">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Dietary Fiber (g):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="dietary-fiber">
                                </div>
                                <div class="nutrient-item" data-nutrient="protein">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Protein (g):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="protein">
                                </div>
                                <div class="nutrient-item" data-nutrient="iodine">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Iodine (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="iodine">
                                </div>
                                <div class="nutrient-item" data-nutrient="magnesium">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Magnesium (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="magnesium">
                                </div>
                                <div class="nutrient-item" data-nutrient="zinc">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Zinc (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="zinc">
                                </div>
                                <div class="nutrient-item" data-nutrient="selenium">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Selenium (μg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="selenium">
                                </div>
                                <div class="nutrient-item" data-nutrient="chloride">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Chloride (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="chloride">
                                </div>
                                <div class="nutrient-item" data-nutrient="flouride">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Fluoride (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="flouride">
                                </div>
                                <div class="nutrient-item" data-nutrient="phosphorus">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Phosphorus (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="phosphorus">
                                </div>
                                <div class="nutrient-item" data-nutrient="calcium">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Calcium (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="calcium">
                                </div>
                                <div class="nutrient-item" data-nutrient="iron">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Iron (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="iron">
                                </div>
                            </div>
                        </div>

                        <div id="group-vitamins" class="nutrient-tab-content" style="display:none;">
                            <div class="nutrient-grid">
                                <!-- Group 2: Vitamins -->
                                <div class="nutrient-item" data-nutrient="vitamin-a">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Vitamin A (μg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="vitamin-a">
                                </div>
                                <div class="nutrient-item" data-nutrient="vitamin-c">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Vitamin C (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="vitamin-c">
                                </div>
                                <div class="nutrient-item" data-nutrient="vitamin-d">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Vitamin D (μg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="vitamin-d">
                                </div>
                                <div class="nutrient-item" data-nutrient="vitamin-e">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Vitamin E (mg-a-TE):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="vitamin-e">
                                </div>
                                <div class="nutrient-item" data-nutrient="vitamin-k">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Vitamin K (μg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="vitamin-k">
                                </div>
                                <div class="nutrient-item" data-nutrient="thiamin">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Thiamin (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="thiamin">
                                </div>
                                <div class="nutrient-item" data-nutrient="riboflavin">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Riboflavin (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="riboflavin">
                                </div>
                                <div class="nutrient-item" data-nutrient="niacin">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Niacin (mg NE):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="niacin">
                                </div>
                                <div class="nutrient-item" data-nutrient="vitamin-b6">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Vitamin B6 (mg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="vitamin-b6">
                                </div>
                                <div class="nutrient-item" data-nutrient="folate">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Folate (μg DFE):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="folate">
                                </div>
                                <div class="nutrient-item" data-nutrient="vitamin-b12">
                                    <div class="nutrient-checkbox"></div>
                                    <span class="nutrient-label">Vitamin B12 (μg):</span>
                                    <input type="number" class="nutrient-input" placeholder="0" data-nutrient="vitamin-b12">
                                </div>
                            </div>
                        </div>

                        <div class="button-group">
                            <button class="calculate-btn" id="calculateBtn">
                                <i class="fas fa-calculator"></i> Calculate Nutrition Facts
                            </button>
                            <button class="reset-btn" id="resetBtn">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="nutrition-card fade-in">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-pie"></i> Nutrition Facts</h2>
                        </div>

                        <div class="results-section" id="resultsSection">
                            <!-- Empty state when no calculation done -->
                            <div class="empty-state" id="emptyState">
                                <i class="fas fa-calculator"></i>
                                <p>Enter nutrient values and click "Calculate" to see results</p>
                            </div>

                            <!-- Results will be populated here dynamically -->
                            <div id="calculatedResults" style="display: none;">
                                <h3 class="results-title"><i class="fas fa-percentage"></i> Nutrient percent
                                    contribution
                                </h3>

                                <ul class="result-list" id="resultsList">
                                    <!-- Results will be populated here by JavaScript -->
                                </ul>

                                <div class="result-highlight">
                                    <p><span class="bullet">•</span> Result based on Inputted Values</p>
                                </div>

                                <div class="disclaimer-box">
                                    <h4><i class="fas fa-exclamation-triangle"></i> Important Disclaimer</h4>
                                    <p>The information produced by the calculator is for visual representation only. It
                                        cannot
                                        be used to label food products. <strong>*Limit intake to < 2000 mg in
                                                adults*</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>


        <script>
            // Nutrient selection and calculation functionality
            document.addEventListener('DOMContentLoaded', function () {
                const nutrientItems = document.querySelectorAll('.nutrient-item');
                const calculateBtn = document.getElementById('calculateBtn');
                const resetBtn = document.getElementById('resetBtn');
                const resultsList = document.getElementById('resultsList');
                const emptyState = document.getElementById('emptyState');
                const calculatedResults = document.getElementById('calculatedResults');

                // Reference values from the Philippine nutrition calculator website
                const referenceValues = {
                    'sodium': 500,
                    'potassium': 2000,
                    'dietary-fiber': 25,
                    'protein': 50,
                    'vitamin-a': 800,
                    'vitamin-c': 60,
                    'calcium': 750,
                    'iron': 14,
                    'vitamin-d': 15,
                    'vitamin-e': 15,
                    'vitamin-k': 65,
                    'thiamin': 1.2,
                    'riboflavin': 1.3,
                    'niacin': 16,
                    'vitamin-b6': 1.3,
                    'folate': 400,
                    'vitamin-b12': 2.4,
                    'iodine': 150,
                    'magnesium': 350,
                    'zinc': 11,
                    'selenium': 55,
                    'chloride': 2300,
                    'flouride': 3,
                    'phosphorus': 700
                };

                // Toggle nutrient selection
                nutrientItems.forEach(item => {
                    item.addEventListener('click', function (e) {
                        if (e.target.type !== 'number') {
                            this.classList.toggle('selected');
                            const input = this.querySelector('.nutrient-input');
                            if (this.classList.contains('selected')) {
                                input.focus();
                            }
                        }
                    });
                });

                // Calculate button functionality
                calculateBtn.addEventListener('click', function () {
                    const selectedNutrients = [];

                    // Collect selected nutrients and their values
                    nutrientItems.forEach(item => {
                        if (item.classList.contains('selected')) {
                            const nutrient = item.getAttribute('data-nutrient');
                            const input = item.querySelector('.nutrient-input');
                            const value = parseFloat(input.value) || 0;

                            if (value > 0) {
                                selectedNutrients.push({
                                    nutrient: nutrient,
                                    value: value,
                                    displayName: getDisplayName(nutrient)
                                });
                            }
                        }
                    });

                    if (selectedNutrients.length === 0) {
                        alert('Please select at least one nutrient and enter values greater than 0 to calculate.');
                        return;
                    }

                    // Calculate percentages and display results
                    displayResults(selectedNutrients);

                    // Show success animation
                    this.innerHTML = '<i class="fas fa-check"></i> Calculated Successfully!';
                    this.style.background = 'var(--primary)';

                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-calculator"></i> Calculate Nutrition Facts';
                        this.style.background = '';
                    }, 2000);
                });

                // Reset button functionality
                resetBtn.addEventListener('click', function () {
                    // Reset all inputs and selections
                    nutrientItems.forEach(item => {
                        item.classList.remove('selected');
                        const input = item.querySelector('.nutrient-input');
                        input.value = '';
                    });

                    // Reset results display
                    emptyState.style.display = 'block';
                    calculatedResults.style.display = 'none';
                    resultsList.innerHTML = '';

                    // Show reset confirmation
                    this.innerHTML = '<i class="fas fa-check"></i> Reset Complete!';
                    this.style.background = 'var(--primary)';

                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-redo"></i> Reset All Inputs';
                        this.style.background = '';
                    }, 1500);
                });

                // Function to calculate and display results
                function displayResults(nutrients) {
                    // Clear previous results
                    resultsList.innerHTML = '';

                    // Calculate and add each result
                    nutrients.forEach(nutrient => {
                        const referenceValue = referenceValues[nutrient.nutrient];
                        const percentage = ((nutrient.value / referenceValue) * 100).toFixed(2);

                        const li = document.createElement('li');
                        li.className = 'result-item';

                        const nutrientSpan = document.createElement('span');
                        nutrientSpan.className = 'result-nutrient';
                        nutrientSpan.textContent = nutrient.displayName;

                        // Add asterisk for sodium
                        if (nutrient.nutrient === 'sodium') {
                            nutrientSpan.classList.add('has-note');
                        }

                        const percentageSpan = document.createElement('span');
                        percentageSpan.className = 'result-percentage';
                        percentageSpan.textContent = `${percentage}%`;

                        li.appendChild(nutrientSpan);
                        li.appendChild(percentageSpan);
                        resultsList.appendChild(li);
                    });

                    // Show results and hide empty state
                    emptyState.style.display = 'none';
                    calculatedResults.style.display = 'block';
                }

                // Helper function to get display names
                function getDisplayName(nutrient) {
                    const names = {
                        'sodium': 'Sodium (mg)',
                        'potassium': 'Potassium (mg)',
                        'dietary-fiber': 'Dietary Fiber (g)',
                        'protein': 'Protein (g)',
                        'vitamin-a': 'Vitamin A (μg)',
                        'vitamin-c': 'Vitamin C (mg)',
                        'calcium': 'Calcium (mg)',
                        'iron': 'Iron (mg)',
                        'vitamin-d': 'Vitamin D (μg)',
                        'vitamin-e': 'Vitamin E (mg-a-TE)',
                        'vitamin-k': 'Vitamin K (μg)',
                        'thiamin': 'Thiamin (mg)',
                        'riboflavin': 'Riboflavin (mg)',
                        'niacin': 'Niacin (mg NE)',
                        'vitamin-b6': 'Vitamin B6 (mg)',
                        'folate': 'Folate (μg DFE)',
                        'vitamin-b12': 'Vitamin B12 (μg)',
                        'iodine': 'Iodine (mg)',
                        'magnesium': 'Magnesium (mg)',
                        'zinc': 'Zinc (mg)',
                        'selenium': 'Selenium (μg)',
                        'chloride': 'Chloride (mg)',
                        'flouride': 'Flouride (mg)',
                        'phosphorus': 'Phosphorus (mg)'
                    };
                    return names[nutrient] || nutrient;
                }

                // Function to switch nutrient tabs
                window.switchNutrientTab = function(tabId, btn) {
                    // Update tab buttons
                    btn.parentElement.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    btn.classList.add('active');

                    // Update tab content
                    document.querySelectorAll('.nutrient-tab-content').forEach(c => c.style.display = 'none');
                    document.getElementById(tabId).style.display = 'block';
                }

                // Input focus effect
                const nutrientInputs = document.querySelectorAll('.nutrient-input');
                nutrientInputs.forEach(input => {
                    input.addEventListener('focus', function () {
                        this.parentElement.classList.add('selected');
                    });
                });

            });
        </script>
</body>

</html>
