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
function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) { if ($n) $initials .= strtoupper($n[0]); }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Macronutrient Diagnostic Engine | NutriDeq</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard-premium.css">
    <!-- Platform Specific Styles -->
    <link rel="stylesheet" href="css/desktop-style.css" media="all and (min-width: 1025px)">
    <link rel="stylesheet" href="css/mobile-style.css" media="all and (max-width: 1024px)">
    
    <style>
        .dash-premium { background: transparent !important; min-height: 100vh; position: relative; overflow-x: hidden; }
        
        .diagnostic-engine {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 28px;
            margin-top: 24px;
            position: relative;
            z-index: 10;
        }

        .terminal-panel {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 32px;
            padding: 32px;
            box-shadow: var(--glass-shadow);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        /* Tactical Bento Inputs */
        .bento-input-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .metric-card {
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 16px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }
        .metric-card:focus-within { border-color: var(--primary); background: #ffffff; transform: translateY(-4px); box-shadow: 0 12px 30px -10px rgba(5, 150, 105, 0.15); }

        .metric-label { font-size: 0.75rem; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .metric-label i { color: var(--primary); font-size: 0.9rem; }

        .tac-input-group { display: flex; align-items: center; gap: 8px; }
        .tac-input { 
            width: 100%; border: none; background: transparent; 
            font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.25rem; 
            color: var(--text-primary); outline: none; 
        }
        .tac-select { 
            background: rgba(0,0,0,0.05); border: none; border-radius: 8px; 
            padding: 4px 8px; font-weight: 700; font-size: 0.75rem; color: var(--text-secondary); 
            cursor: pointer; outline: none;
        }

        /* Floating Pill Nav */
        .pill-nav {
            background: rgba(0,0,0,0.04); padding: 4px; border-radius: 50px;
            display: inline-flex; gap: 6px; margin-bottom: 20px;
        }
        .pill-item {
            padding: 10px 24px; border-radius: 50px; font-weight: 800; font-size: 0.8rem;
            text-transform: uppercase; cursor: pointer; transition: all 0.3s; color: var(--text-secondary);
        }
        .pill-item.active { background: var(--primary); color: white; box-shadow: 0 8px 20px rgba(5, 150, 105, 0.25); transform: scale(1.05); }

        /* Diagnostic HUD */
        .diagnostic-hud { display: flex; flex-direction: column; gap: 20px; }
        .hud-card {
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .hud-val-main { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 2.2rem; color: var(--text-primary); margin-bottom: 4px; }
        .hud-lbl { font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.02em; }

        .progress-hud { height: 10px; background: rgba(0,0,0,0.05); border-radius: 20px; overflow: hidden; margin-top: 16px; border: 1px solid rgba(255,255,255,0.2); }
        .progress-hud-bar { 
            height: 100%; width: 0; background: var(--gradient); 
            border-radius: 20px; transition: width 1.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }
        .progress-hud-bar::after {
            content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%;
            background: #fff; box-shadow: 0 0 10px #fff;
        }

        /* SVG Diagnostic Rings */
        .ring-container { position: relative; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; margin: 0 auto; }
        .ring-svg { transform: rotate(-90deg); }
        .ring-bg { fill: none; stroke: rgba(0,0,0,0.05); stroke-width: 8; }
        .ring-fill { 
            fill: none; stroke: var(--primary); stroke-width: 8; stroke-linecap: round; 
            stroke-dasharray: 283; stroke-dashoffset: 283; transition: stroke-dashoffset 1.5s ease; 
        }
        .ring-val { position: absolute; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.5rem; color: var(--text-primary); }

        /* Bio-Scan Animation */
        .scan-line {
            position: absolute; top: -10px; left: 0; width: 100%; height: 2px;
            background: rgba(5, 150, 105, 0.4); box-shadow: 0 0 15px var(--primary);
            z-index: 100; pointer-events: none; opacity: 0;
        }
        @keyframes scanEffect {
            0% { top: 0; opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        .scan-active .scan-line { animation: scanEffect 1.5s ease-in-out forwards; }

        .btn-bio-scan {
            background: var(--primary); color: white; border: none; border-radius: 20px;
            padding: 18px; font-weight: 800; font-size: 1rem; cursor: pointer;
            width: 100%; margin-top: 24px; transition: all 0.3s;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.2);
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .btn-bio-scan:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(5, 150, 105, 0.4); filter: brightness(1.1); }

        @keyframes pulse-emerald { 0% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.05); opacity: 1; } 100% { transform: scale(1); opacity: 0.8; } }
        .clinical-status { padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; float: right; margin-top: 6px; }
        .status-healthy { background: rgba(5, 150, 105, 0.1); color: #059669; }
        .status-warning { background: rgba(217, 119, 6, 0.1); color: #d97706; }
        .status-danger { background: rgba(225, 29, 72, 0.1); color: #e11d48; }

        @media (max-width: 1100px) { .diagnostic-engine { grid-template-columns: 1fr; } }
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

            <div class="page-container" style="position: relative; z-index: 10; padding: 20px 32px;">
                <div class="header">
                    <div class="page-title">
                        <h1 style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 2.1rem; letter-spacing: -0.02em; color: #1e293b; margin: 0;">Macronutrient Diagnostic Engine</h1>
                        <p style="font-weight: 600; color: #64748b; font-size: 0.95rem; margin-top: 4px;">Premium Bio-Metric Energy Synthesis Framework</p>
                    </div>
                </div>

                <div class="diagnostic-engine">
                    <!-- Left: Input Peripheral -->
                    <div class="terminal-panel stagger d-1" id="inputTerminal">
                        <div class="scan-line"></div>
                        <div style="border:none; padding:0; margin-bottom:20px; display: flex; align-items: center; justify-content: space-between;">
                            <h2 style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.25rem; margin: 0; color: #1e293b;">
                                <i class="fas fa-fingerprint" style="color:var(--primary); margin-right:12px;"></i> Bio-Metric Input
                            </h2>
                        </div>

                        <!-- Sex Segmented Control -->
                        <div class="metric-label"><i class="fas fa-venus-mars"></i> Assigned Sex</div>
                        <div class="pill-nav" id="sexToggle">
                            <div class="pill-item active" data-val="male">Male</div>
                            <div class="pill-item" data-val="female">Female</div>
                        </div>

                        <div class="bento-input-grid">
                            <!-- Date Chip -->
                            <div class="metric-card">
                                <div class="metric-label"><i class="fas fa-calendar-alt"></i> Date of Birth</div>
                                <input type="date" id="dob" class="tac-input" style="font-size: 1rem;">
                            </div>

                            <!-- Weight Chip -->
                            <div class="metric-card">
                                <div class="metric-label"><i class="fas fa-weight"></i> Body Weight</div>
                                <div class="tac-input-group">
                                    <input type="number" id="weight" class="tac-input" placeholder="0">
                                    <select id="weightUnit" class="tac-select"><option>kg</option><option>lbs</option></select>
                                </div>
                            </div>

                            <!-- Height Chip -->
                            <div class="metric-card">
                                <div class="metric-label"><i class="fas fa-ruler-vertical"></i> Vertical Stature</div>
                                <div class="tac-input-group">
                                    <input type="number" id="height" class="tac-input" placeholder="0">
                                    <select id="heightUnit" class="tac-select"><option>cm</option><option>ft</option></select>
                                </div>
                            </div>

                            <!-- Waist Chip -->
                            <div class="metric-card">
                                <div class="metric-label"><i class="fas fa-arrows-alt-h"></i> Waist Girth</div>
                                <div class="tac-input-group">
                                    <input type="number" id="waist" class="tac-input" placeholder="0">
                                    <select id="waistUnit" class="tac-select"><option>cm</option><option>in</option></select>
                                </div>
                            </div>

                            <!-- Hip Chip -->
                            <div class="metric-card">
                                <div class="metric-label"><i class="fas fa-vector-square"></i> Hip Girth</div>
                                <div class="tac-input-group">
                                    <input type="number" id="hip" class="tac-input" placeholder="0">
                                    <select id="hipUnit" class="tac-select"><option>cm</option><option>in</option></select>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Segmented Control -->
                        <div class="metric-label" style="margin-top: 24px;"><i class="fas fa-walking"></i> Activity Coefficient</div>
                        <div class="pill-nav" id="activityToggle" style="margin-bottom: 0;">
                            <div class="pill-item active" data-val="sedentary">Sedentary</div>
                            <div class="pill-item" data-val="light">Light</div>
                            <div class="pill-item" data-val="moderate">Mod</div>
                            <div class="pill-item" data-val="very">Active</div>
                            <div class="pill-item" data-val="extra">Elite</div>
                        </div>

                        <button class="btn-bio-scan" id="calculateBtn">
                            <i class="fas fa-microscope" style="margin-right: 12px;"></i> Initiate Bio-Scan
                        </button>
                    </div>

                    <!-- Right: Diagnostic HUD -->
                    <div class="diagnostic-hud stagger d-2">
                        <!-- Primary Rings -->
                        <div class="terminal-panel" style="padding: 24px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="ring-diagnostic">
                                    <div class="ring-container">
                                        <svg class="ring-svg" width="100" height="100">
                                            <circle class="ring-bg" cx="50" cy="50" r="45"></circle>
                                            <circle class="ring-fill" id="bmiRing" cx="50" cy="50" r="45"></circle>
                                        </svg>
                                        <div class="ring-val" id="bmiResult">--</div>
                                    </div>
                                    <div class="hud-lbl" style="text-align: center; margin-top: 12px;">BMI Diagnostic</div>
                                    <div id="bmiStatus" class="clinical-status" style="float:none; text-align:center; display:block;">Awaiting Scan</div>
                                </div>
                                <div class="ring-diagnostic">
                                    <div class="ring-container">
                                        <svg class="ring-svg" width="100" height="100">
                                            <circle class="ring-bg" cx="50" cy="50" r="45"></circle>
                                            <circle class="ring-fill" id="tdeeRing" cx="50" cy="50" r="45" style="stroke: #3b82f6;"></circle>
                                        </svg>
                                        <div class="ring-val" id="tdeeResult" style="font-size: 1.2rem;">--</div>
                                    </div>
                                    <div class="hud-lbl" style="text-align: center; margin-top: 12px;">TDEE Capacity</div>
                                    <div id="tdeeUnit" class="clinical-status status-healthy" style="float:none; text-align:center; display:block;">kcal/day</div>
                                </div>
                            </div>
                        </div>

                        <!-- Ratio HUDs -->
                        <div class="terminal-panel" style="gap: 16px; padding: 24px;">
                            <div class="hud-row">
                                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                                    <div class="hud-lbl">Waist-Hip Synthesis</div>
                                    <div class="hud-val-main" style="font-size: 1.4rem;" id="whrResult">--</div>
                                </div>
                                <div class="progress-hud"><div class="progress-hud-bar" id="whrBar"></div></div>
                                <div id="whrStatus" class="clinical-status">N/A</div>
                            </div>

                            <div class="hud-row">
                                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                                    <div class="hud-lbl">Waist-Height Index</div>
                                    <div class="hud-val-main" style="font-size: 1.4rem;" id="whtrResult">--</div>
                                </div>
                                <div class="progress-hud"><div class="progress-hud-bar" id="whtrBar" style="background: linear-gradient(90deg, #3b82f6, #60a5fa);"></div></div>
                                <div id="whtrStatus" class="clinical-status">N/A</div>
                            </div>
                        </div>

                        <!-- DBW Terminal -->
                        <div class="terminal-panel" style="padding: 24px; border: 1px solid var(--primary-light) !important;">
                             <div class="hud-lbl" style="margin-bottom: 12px; color: var(--primary);">Desirable Body Weight Method</div>
                             <div class="pill-nav" id="methodToggle">
                                <div class="pill-item active" data-val="tannhauser">Tan</div>
                                <div class="pill-item" data-val="hamwi">Hamwi</div>
                                <div class="pill-item" data-val="bmi">BMI</div>
                                <div class="pill-item" data-val="kg">Kg</div>
                             </div>
                             <div style="display: flex; align-items: baseline; gap: 12px; margin-top: 12px;">
                                <div class="hud-val-main" id="dbwResult" style="color: var(--primary); font-size: 3rem;">--</div>
                                <div class="hud-lbl" style="font-size: 1.2rem;">kg</div>
                             </div>
                             <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 8px 0 0; font-weight: 600;">*Estimated target weight for optimal metabolic performance.</p>
                        </div>
                    </div>
                </div>

                <!-- Clinical Notice Overlay -->
                <div class="terminal-panel stagger d-3" style="margin-top: 24px; background: rgba(239, 68, 68, 0.05) !important; border: 1.2px solid rgba(239, 68, 68, 0.15) !important; padding: 20px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <i class="fas fa-biohazard fa-2x" style="color: #ef4444; opacity: 0.8;"></i>
                        <div>
                            <h4 style="margin: 0; font-family: 'Outfit', sans-serif; font-weight: 800; color: #ef4444; font-size: 1rem;">CLINICAL SURVEILLANCE NOTICE</h4>
                            <p style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">
                                Bio-metric results are synthesized estimates. For clinical diagnosis, consult a Registered Nutritionist-Dietitian.
                            </p>
                        </div>
                        <?php if ($user_role === 'regular'): ?>
                        <button class="btn-dash-action" style="margin-left: auto; background: #ef4444; color: white; padding: 10px 24px; font-size: 0.8rem; border-radius: 50px;">
                            CONSULT SPECIALIST
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tactical Toggle Logic
            function initToggle(containerId, callback) {
                const container = document.getElementById(containerId);
                const items = container.querySelectorAll('.pill-item');
                items.forEach(item => {
                    item.addEventListener('click', () => {
                        items.forEach(i => i.classList.remove('active'));
                        item.classList.add('active');
                        if(callback) callback(item.dataset.val);
                    });
                });
            }

            let currentSex = 'male';
            let currentActivity = 'sedentary';
            let currentMethod = 'tannhauser';

            initToggle('sexToggle', (val) => currentSex = val);
            initToggle('activityToggle', (val) => currentActivity = val);
            initToggle('methodToggle', (val) => {
                currentMethod = val;
                if(document.getElementById('bmiResult').textContent !== '--') calculateDiagnostics();
            });

            // SVG Ring Controller
            function updateRing(id, percentage, max = 100) {
                const ring = document.getElementById(id);
                const radius = 45;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (Math.min(percentage, max) / max) * circumference;
                ring.style.strokeDashoffset = offset;
            }

            // Diagnostic Logic
            const calculateBtn = document.getElementById('calculateBtn');
            calculateBtn.addEventListener('click', () => {
                const terminal = document.getElementById('inputTerminal');
                terminal.classList.remove('scan-active');
                void terminal.offsetWidth; // Trigger reflow
                terminal.classList.add('scan-active');
                
                setTimeout(calculateDiagnostics, 600);
            });

            function calculateDiagnostics() {
                const weightVal = parseFloat(document.getElementById('weight').value) || 0;
                const weightUnit = document.getElementById('weightUnit').value;
                const heightVal = parseFloat(document.getElementById('height').value) || 0;
                const heightUnit = document.getElementById('heightUnit').value;
                const waistVal = parseFloat(document.getElementById('waist').value) || 0;
                const waistUnit = document.getElementById('waistUnit').value;
                const hipVal = parseFloat(document.getElementById('hip').value) || 0;
                const hipUnit = document.getElementById('hipUnit').value;
                const dob = document.getElementById('dob').value;

                if (!weightVal || !heightVal) {
                    alert("PROTOCOL ERROR: Bio-metric metrics incomplete. Please provide Weight and Vertical Stature.");
                    return;
                }

                // Standardize Units
                let weightKg = weightUnit === 'lbs' ? weightVal * 0.453592 : weightVal;
                let heightCm = heightUnit === 'ft' ? heightVal * 30.48 : heightVal;
                let waistCm = waistUnit === 'in' ? waistVal * 2.54 : waistVal;
                let hipCm = hipUnit === 'in' ? hipVal * 2.54 : hipVal;

                // Age Diagnostic
                let age = 25;
                if (dob) {
                    const birthDate = new Date(dob);
                    const today = new Date();
                    age = today.getFullYear() - birthDate.getFullYear();
                    const m = today.getMonth() - birthDate.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
                }

                // BMI Diagnostic
                const heightM = heightCm / 100;
                const bmi = (weightKg / (heightM * heightM)).toFixed(1);
                document.getElementById('bmiResult').textContent = bmi;
                
                const bmiStatus = document.getElementById('bmiStatus');
                bmiStatus.className = 'clinical-status';
                if (bmi < 18.5) { bmiStatus.textContent = 'Underweight'; bmiStatus.classList.add('status-warning'); }
                else if (bmi < 25) { bmiStatus.textContent = 'Healthy Range'; bmiStatus.classList.add('status-healthy'); }
                else if (bmi < 30) { bmiStatus.textContent = 'High Threshold'; bmiStatus.classList.add('status-warning'); }
                else { bmiStatus.textContent = 'Clinical Obese'; bmiStatus.classList.add('status-danger'); }
                
                updateRing('bmiRing', bmi, 40);

                // TDEE Capacity
                const activityMap = { sedentary: 1.2, light: 1.375, moderate: 1.55, very: 1.725, extra: 1.9 };
                const multiplier = activityMap[currentActivity];
                let bmr = currentSex === 'male' ? 
                    (10 * weightKg) + (6.25 * heightCm) - (5 * age) + 5 : 
                    (10 * weightKg) + (6.25 * heightCm) - (5 * age) - 161;
                const tdee = Math.round(bmr * multiplier);
                document.getElementById('tdeeResult').textContent = tdee;
                updateRing('tdeeRing', tdee, 4000);

                // Ratio Synthesis
                if (waistCm && hipCm) {
                    const whr = (waistCm / hipCm).toFixed(2);
                    document.getElementById('whrResult').textContent = whr;
                    const whrStatus = document.getElementById('whrStatus');
                    const isHigh = currentSex === 'male' ? whr > 0.9 : whr > 0.85;
                    whrStatus.textContent = isHigh ? 'High Risk' : 'Optimal';
                    whrStatus.className = 'clinical-status ' + (isHigh ? 'status-danger' : 'status-healthy');
                    document.getElementById('whrBar').style.width = (whr * 100) + '%';
                }

                if (waistCm && heightCm) {
                    const whtr = (waistCm / heightCm).toFixed(2);
                    document.getElementById('whtrResult').textContent = whtr;
                    const whtrStatus = document.getElementById('whtrStatus');
                    const isHigh = whtr > 0.5;
                    whtrStatus.textContent = isHigh ? 'Risk Detected' : 'Metabolic Optimal';
                    whtrStatus.className = 'clinical-status ' + (isHigh ? 'status-danger' : 'status-healthy');
                    document.getElementById('whtrBar').style.width = (whtr * 100) + '%';
                }

                // Desirable Body Weight Method
                let dbw = 0;
                const heightIn = heightCm / 2.54;
                const inchesOver5ft = Math.max(0, heightIn - 60);

                if (currentMethod === 'tannhauser') {
                    dbw = (heightCm - 100) - ((heightCm - 100) * 0.1);
                } else if (currentMethod === 'hamwi') {
                    dbw = currentSex === 'male' ? 48 + (2.7 * inchesOver5ft) : 45 + (2.2 * inchesOver5ft);
                } else if (currentMethod === 'bmi') {
                    dbw = 22 * (heightM * heightM);
                } else {
                    dbw = (heightCm - 100);
                    if (age < 40) dbw *= 0.9;
                }
                document.getElementById('dbwResult').textContent = Math.round(dbw);
            }

            // Spotlight Tracking
            const spotlight = document.getElementById('spotlight');
            document.addEventListener('mousemove', (e) => {
                spotlight.style.left = e.clientX + 'px';
                spotlight.style.top = e.clientY + 'px';
            });
        });
    </script>
</body>
</html>
