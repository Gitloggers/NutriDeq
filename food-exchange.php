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
$nav_links_array = getNavigationLinks($user_role, 'food-exchange.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <script src="scripts/theme-toggle.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Food Exchange Lists | NutriDeq</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/food-exchange.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard-premium.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/logout-modal.css">
    <!-- Platform Specific Styles -->
    <link rel="stylesheet" href="css/desktop-style.css" media="all and (min-width: 1025px)">
    <link rel="stylesheet" href="css/mobile-style.css" media="all and (max-width: 1024px)">
    <!-- dashboard.js included via sidebar.php -->
    <style>
        /* ═══════════ FOOD EXCHANGE LISTS · PREMIUM SHELL ═══════════ */
        .main-content {
            position: relative;
            overflow-x: hidden;
        }

        /* ── Hero Terminal Header ── */
        .fet-hero {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 60%, #047857 100%);
            border-radius: 28px;
            padding: 28px 36px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 50px -15px rgba(6, 78, 59, 0.4);
        }

        .fet-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.12) 0%, transparent 60%);
            pointer-events: none;
        }

        .fet-hero-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: clamp(1.4rem, 3vw, 2rem);
            color: #fff;
            margin: 0;
            letter-spacing: -0.03em;
        }

        .fet-hero-sub {
            font-size: 0.9rem;
            color: rgba(167, 243, 208, 0.85);
            margin: 4px 0 0;
            font-weight: 500;
        }

        .fet-hero-badge {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 8px 20px;
            color: #a7f3d0;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        /* ── Pill Tab Navigation ── */
        .tabs-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            overflow: visible;
            margin-bottom: 0;
        }

        .tabs-header {
            display: flex !important;
            flex-direction: row !important;
            overflow-x: auto !important;
            white-space: nowrap !important;
            -webkit-overflow-scrolling: touch !important;
            gap: 8px !important;
            padding: 16px !important;
            width: 100% !important;
            box-sizing: border-box !important;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 24px 24px 0 0;
            border-bottom: 1px solid var(--border-color);
            position: sticky !important;
            top: 0 !important;
            z-index: 50 !important;
        }

        .tabs-header::-webkit-scrollbar {
            display: none !important;
        }

        .tab {
            padding: 10px 20px !important;
            background: rgba(255, 255, 255, 0.5) !important;
            border: 1px solid var(--border-color) !important;
            cursor: pointer;
            border-radius: 50px !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-secondary) !important;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            white-space: nowrap !important;
            flex: 0 0 auto !important;
            display: flex !important;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .tab:hover {
            color: var(--primary) !important;
            background: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.1);
        }

        .tab.active {
            color: white !important;
            background: var(--primary) !important;
            font-weight: 700;
            border-color: var(--primary) !important;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.25) !important;
            transform: translateY(-2px);
        }

        .tab-icon {
            font-size: 0.95rem;
        }

        .tab-content {
            display: none;
            padding: 28px;
            animation: fetFadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fetFadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Section Headers ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: clamp(1rem, 2.5vw, 1.3rem);
            color: var(--text-primary);
            white-space: normal;
            line-height: 1.3;
        }

        .info-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
        }

        .info-btn:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35);
        }

        /* ── Glass Tables ── */
        .table-container {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }

        .mobile-scroll-wrapper {
            min-width: 100%;
        }

        .food-exchange-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
        }

        .food-exchange-table thead tr {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
        }

        .food-exchange-table th {
            color: #a7f3d0;
            font-weight: 700;
            padding: 12px 16px;
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .food-exchange-table th.active {
            background: rgba(52, 211, 153, 0.25);
            color: #fff;
        }

        .food-exchange-table td {
            padding: 10px 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            transition: all 0.2s;
        }

        .food-exchange-table tbody tr:hover td {
            background: rgba(5, 150, 105, 0.04);
        }

        .food-exchange-table tbody tr:last-child td {
            border-bottom: none;
        }

        .food-group-header {
            background: linear-gradient(90deg, rgba(5, 150, 105, 0.08), transparent);
            font-weight: 800;
            color: var(--primary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .food-subgroup {
            color: var(--text-primary);
            font-weight: 600;
        }

        .exchange-value {
            display: inline-block;
            font-weight: 700;
            color: var(--text-primary);
        }

        .exchange-value.highlight {
            background: rgba(5, 150, 105, 0.12);
            color: var(--primary);
            border-radius: 20px;
            padding: 2px 10px;
            font-weight: 800;
        }

        .active-cell {
            background: rgba(5, 150, 105, 0.05);
        }

        /* ── Calorie Selector ── */
        .food-exchange-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .food-exchange-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(0.95rem, 2vw, 1.2rem);
            font-weight: 800;
            color: var(--text-primary);
        }

        .food-exchange-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .calorie-selector {
            background: var(--bg-surface);
            border: 1.5px solid var(--border-color);
            border-radius: 50px;
            padding: 8px 18px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .calorie-selector:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        /* ── Mobile Calorie Pills ── */
        .mobile-calorie-pills-container {
            margin-bottom: 16px;
        }

        .pill-slider-label {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
        }

        .mobile-pill-slider {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: none;
        }

        .mobile-pill-slider::-webkit-scrollbar {
            display: none;
        }

        .calorie-pill {
            flex-shrink: 0;
            padding: 6px 14px;
            border-radius: 50px;
            border: 1.5px solid var(--border-color);
            background: var(--bg-surface);
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.25s;
        }

        .calorie-pill.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
        }

        .mobile-calorie-cards {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 12px;
        }

        .mcv-category-header {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
            padding: 12px 0 4px;
            border-bottom: 2px solid rgba(5, 150, 105, 0.15);
            margin-bottom: 4px;
        }

        .mcv-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .mcv-row--sub {
            padding-left: 12px;
        }

        .mcv-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .mcv-value {
            font-weight: 800;
            color: var(--primary);
            font-size: 1rem;
        }

        /* ── Mobile visibility toggles ── */
        .mobile-view {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-view {
                display: block;
            }

            .desktop-view {
                display: none;
            }

            .tab-content {
                padding: 16px 14px !important;
            }

            .fet-hero {
                padding: 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .fet-hero-badge {
                font-size: 0.75rem;
            }
        }

        @media (min-width: 769px) {
            .desktop-view {
                display: block;
            }
        }

        /* ── Utility ── */
        .d-none {
            display: none !important;
        }

        @media (min-width: 769px) {
            .d-md-flex {
                display: flex !important;
            }

            .d-md-none {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .d-md-flex {
                display: none !important;
            }

            .d-md-none {
                display: flex !important;
            }
        }

        /* ── Macronutrient Bento Grid ── */
        .macronutrient-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .macronutrient-card {
            background: var(--bg-surface);
            border-radius: 20px;
            padding: 22px;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .macronutrient-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient);
            transform: scaleX(0);
            transform-origin: left;
            transition: all 0.3s;
        }

        .macronutrient-card:hover::before {
            transform: scaleX(1);
        }

        .macronutrient-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.08);
            border-color: rgba(5, 150, 105, 0.2);
        }

        .macronutrient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .macronutrient-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: var(--primary);
            font-size: 1rem;
            padding-left: 14px;
            position: relative;
        }

        .macronutrient-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 14px;
            background: var(--primary);
            border-radius: 4px;
        }

        .macronutrient-energy {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .macronutrient-values {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.06);
            font-size: 0.88rem;
            transition: all 0.2s;
        }

        .macronutrient-values:hover {
            transform: translateX(4px);
        }

        .macronutrient-values:last-child {
            border-bottom: none;
        }

        .macronutrient-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .macronutrient-amount {
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ── Calculation Steps ── */
        .calculation-visual {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 20px;
        }

        .calculation-step {
            background: var(--bg-surface);
            border-radius: 20px;
            padding: 22px;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .calculation-step::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #7c3aed, #4f46e5);
            transform: scaleY(0);
            transform-origin: top;
            transition: all 0.3s;
        }

        .calculation-step:hover::before,
        .calculation-step.expanded::before {
            transform: scaleY(1);
        }

        .calculation-step:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
        }

        .step-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: var(--text-primary);
            font-size: 1rem;
            padding-left: 14px;
            position: relative;
        }

        .step-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 14px;
            background: #7c3aed;
            border-radius: 4px;
        }

        .step-number {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .step-content {
            margin-top: 16px;
            font-size: 0.9rem;
            display: none;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .step-content strong {
            color: var(--text-primary);
        }

        .calculation-step.expanded .step-content {
            display: block;
            animation: fetFadeIn 0.35s ease;
        }

        .step-values {
            display: none;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .calculation-step.expanded .step-values {
            display: flex;
            animation: fetFadeIn 0.4s ease 0.1s both;
        }

        .value-card {
            flex: 1;
            min-width: 120px;
            background: rgba(124, 58, 237, 0.06);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            border: 1px solid rgba(124, 58, 237, 0.1);
        }

        .value-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .value-amount {
            font-weight: 800;
            font-size: 1rem;
            color: #7c3aed;
            font-family: 'Outfit', sans-serif;
        }

        .step-total {
            display: none;
            background: rgba(124, 58, 237, 0.08);
            border-radius: 12px;
            padding: 12px;
            margin-top: 14px;
            text-align: center;
            font-weight: 700;
            color: #7c3aed;
            border: 1px solid rgba(124, 58, 237, 0.15);
            font-size: 0.9rem;
        }

        .calculation-step.expanded .step-total {
            display: block;
            animation: fetFadeIn 0.4s ease 0.2s both;
        }

        .food-exchange-footer {
            margin-top: 20px;
            padding: 16px;
            background: rgba(5, 150, 105, 0.05);
            border-radius: 14px;
            border: 1px solid rgba(5, 150, 105, 0.1);
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* ── Distribution Grid ── */
        .distribution-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .distribution-card {
            background: var(--bg-surface);
            border-radius: 20px;
            padding: 22px;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }

        .distribution-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient);
            transform: scaleX(0);
            transform-origin: left;
            transition: all 0.3s;
        }

        .distribution-card:hover::before {
            transform: scaleX(1);
        }

        .distribution-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.08);
        }

        .distribution-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .distribution-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: var(--primary);
            font-size: 1rem;
        }

        .distribution-exchange {
            font-size: 0.8rem;
            font-weight: 700;
            background: rgba(5, 150, 105, 0.1);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
        }

        .distribution-time {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 14px;
            font-weight: 600;
        }

        .distribution-time i {
            color: var(--primary);
        }

        .distribution-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.06);
        }

        .distribution-item:last-child {
            border-bottom: none;
        }

        .distribution-item-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(5, 150, 105, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: var(--primary);
            flex-shrink: 0;
        }

        .distribution-item-name {
            flex: 1;
            font-weight: 600;
            font-size: 0.88rem;
        }

        .distribution-item-amount {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--primary);
        }

        .distribution-summary {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
        }

        .distribution-summary-item {
            flex: 1;
            text-align: center;
        }

        .distribution-summary-value {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }

        .distribution-summary-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* ── Meal Cards Premium Overhaul (Compressed) ── */
        .menu-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .meal-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 18px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 4px 15px -5px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .meal-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient);
            opacity: 0.5;
        }

        .meal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(5, 150, 105, 0.12);
            border-color: rgba(5, 150, 105, 0.1);
        }

        .meal-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-bottom: 8px;
            border-bottom: 1.5px solid rgba(5, 150, 105, 0.08);
            margin-bottom: 2px;
            gap: 2px;
        }

        .meal-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: var(--primary);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meal-subtitle {
            font-size: 0.7rem;
            color: var(--text-secondary);
            font-weight: 600;
            opacity: 0.8;
            background: rgba(5, 150, 105, 0.05);
            padding: 1px 6px;
            border-radius: 4px;
        }

        .meal-title i {
            font-size: 0.85rem;
            color: var(--primary);
        }

        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.02);
        }

        .menu-item:hover {
            padding-left: 4px;
            background: rgba(5, 150, 105, 0.02);
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-food {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .menu-measure {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.78rem;
            background: rgba(5, 150, 105, 0.08);
            padding: 4px 12px;
            border-radius: 50px;
            text-transform: lowercase;
            border: 1px solid rgba(5, 150, 105, 0.1);
        }

        /* ── FEL Vertical Tabs ── */
        .fel-vtabs {
            display: flex;
            gap: 20px;
        }

        .fel-vtab-sidebar {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 200px;
            flex-shrink: 0;
        }

        .fel-vtab {
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.82rem;
            color: var(--text-secondary);
            cursor: pointer;
            text-align: left;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fel-vtab:hover {
            background: #fff;
            color: var(--primary);
        }

        .fel-vtab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.2);
        }

        .fel-vtab-content {
            flex: 1;
            min-width: 0;
        }

        .fel-pane {
            display: none;
        }

        .fel-pane.active {
            display: block;
            animation: fetFadeIn 0.3s ease;
        }

        /* ── Mobile Accordion (FEL) ── */
        .fel-mobile-accordion {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .fel-accordion-item {
            background: var(--bg-surface);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .fel-accordion-header {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--primary);
            cursor: pointer;
            background: rgba(5, 150, 105, 0.04);
            transition: all 0.25s;
        }

        .fel-accordion-header:hover {
            background: rgba(5, 150, 105, 0.08);
        }

        .fel-accordion-content {
            padding: 16px 18px;
            display: none;
            border-top: 1px solid var(--border-color);
        }

        .fel-accordion-content.active {
            display: block;
            animation: fetFadeIn 0.3s ease;
        }

        .fel-mobile-item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.06);
            font-size: 0.88rem;
        }

        .fel-mobile-item-row:last-child {
            border-bottom: none;
        }

        .fel-mobile-label {
            font-weight: 700;
            color: var(--text-primary);
            min-width: 38%;
        }

        .fel-mobile-value {
            color: var(--text-secondary);
            text-align: right;
            font-weight: 500;
        }

        /* ── Food Exchange Legend ── */
        .food-exchange-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }

        .legend-vegetable {
            background: #16a34a;
        }

        .legend-fruit {
            background: #d97706;
        }

        .legend-milk {
            background: #2563eb;
        }

        .legend-rice {
            background: #ca8a04;
        }

        .legend-meat {
            background: #dc2626;
        }

        .legend-fat {
            background: #7c3aed;
        }

        .legend-sugar {
            background: #db2777;
        }
    </style>
    <!-- MOBILE FALLBACK FIXES -->
    <style>
        @media (max-width: 1024px) {

            .main-content,
            .page-container,
            main,
            .dashboard {
                padding-bottom: 120px !important;
                margin-bottom: 120px !important;
            }

            .table-responsive,
            .table-container,
            .card-body {
                width: 100% !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
                display: block !important;
            }

            table {
                min-width: 700px !important;
                display: table !important;
            }

            thead,
            tbody,
            tr {
                width: 100% !important;
            }
        }
    </style>
    <style>
        @media (max-width: 1024px) {

            .main-content,
            .page-container,
            main {
                padding-bottom: 120px !important;
            }

            .table-responsive,
            .table-container,
            .card-body {
                width: 100vw !important;
                max-width: 100% !important;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch !important;
                display: block !important;
            }

            table {
                min-width: 800px !important;
                display: table !important;
            }

            .grid,
            .tab-buttons {
                display: flex;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="main-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <!-- Ambient Mesh Background -->
            <div class="mesh-gradient-container dashboard-mesh">
                <div class="mesh-blob blob-1"></div>
                <div class="mesh-blob blob-2"></div>
                <div class="mesh-blob blob-3"></div>
            </div>
            <div class="glass-noise"></div>
            <div class="spotlight" id="spotlight"></div>

            <div class="page-container" style="position:relative;z-index:10;padding:20px 32px 32px;">

                <!-- Hero Terminal Header -->
                <div class="fet-hero">
                    <div>
                        <h1 class="fet-hero-title"><i class="fas fa-exchange-alt"
                                style="margin-right:14px;opacity:0.8;"></i>Healthy Exchanges</h1>
                        <p class="fet-hero-sub">Philippine Food Exchange Lists & Nutritional Guide</p>
                    </div>
                    <div class="fet-hero-badge"><i class="fas fa-leaf"></i> NutriDeq FEL v2.0</div>
                </div>

                <!-- Food Exchange Terminal Tabs -->
                <div class="tabs-container">
                    <div class="tabs-header">
                        <button class="tab active" data-tab="quick-reference"><i
                                class="fas fa-table tab-icon"></i><span>Quick Reference</span></button>
                        <button class="tab" data-tab="macronutrient"><i
                                class="fas fa-apple-alt tab-icon"></i><span>Macronutrients</span></button>
                        <button class="tab" data-tab="calculation"><i
                                class="fas fa-calculator tab-icon"></i><span>Computation</span></button>
                        <button class="tab" data-tab="distribution"><i class="fas fa-clock tab-icon"></i><span>Meal
                                Distribution</span></button>
                        <button class="tab" data-tab="sample-menu"><i class="fas fa-utensils tab-icon"></i><span>Sample
                                Menu</span></button>
                        <button class="tab" data-tab="food-exchange-list"><i
                                class="fas fa-list-alt tab-icon"></i><span>Exchange List</span></button>
                    </div>

                    <div class="tab-content active" id="quick-reference">
                        <div class="food-exchange-section">
                            <div class="food-exchange-header">
                                <h2>CALCULATED DIETS FOR QUICK REFERENCE</h2>
                                <div class="food-exchange-controls">
                                    <div class="calorie-selector-container">
                                        <select class="calorie-selector" id="calorieSelector">
                                            <option value="1200">1200 Calories</option>
                                            <option value="1300">1300 Calories</option>
                                            <option value="1400">1400 Calories</option>
                                            <option value="1500">1500 Calories</option>
                                            <option value="1600" selected>1600 Calories</option>
                                            <option value="1700">1700 Calories</option>
                                            <option value="1800">1800 Calories</option>
                                            <option value="1900">1900 Calories</option>
                                            <option value="2000">2000 Calories</option>
                                            <option value="2100">2100 Calories</option>
                                            <option value="2200">2200 Calories</option>
                                            <option value="2300">2300 Calories</option>
                                            <option value="2400">2400 Calories</option>
                                        </select>
                                    </div>
                                    <div class="info-btn" id="foodExchangeInfo">
                                        <i class="fas fa-info"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Desktop table view -->
                            <div class="desktop-view">
                                <div class="table-container">
                                    <div class="mobile-scroll-wrapper">

                                        <table class="food-exchange-table calorie-ref-table">
                                            <thead>
                                                <tr>
                                                    <th>Food Group</th>
                                                    <th>1200</th>
                                                    <th>1300</th>
                                                    <th>1400</th>
                                                    <th>1500</th>
                                                    <th class="active">1600</th>
                                                    <th>1700</th>
                                                    <th>1800</th>
                                                    <th>1900</th>
                                                    <th>2000</th>
                                                    <th>2100</th>
                                                    <th>2200</th>
                                                    <th>2300</th>
                                                    <th>2400</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Vegetable++ -->
                                                <tr>
                                                    <td class="food-group-header">Vegetable++</td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value highlight">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                </tr>

                                                <!-- Fruit -->
                                                <tr>
                                                    <td class="food-group-header">Fruit</td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value highlight">6</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">6.5</span></td>
                                                    <td><span class="exchange-value">7</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                </tr>

                                                <!-- Milk -->
                                                <tr>
                                                    <td class="food-group-header" colspan="14">Milk</td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">Whole Milk</td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value highlight">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">Low Fat</td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value highlight">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">Non-Fat Milk</td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value highlight">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                </tr>

                                                <!-- Rice -->
                                                <tr>
                                                    <td class="food-group-header" colspan="14">Rice</td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">Low Protein</td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value highlight">2</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2.5</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">Medium Protein</td>
                                                    <td><span class="exchange-value">3.5</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">4.5</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value highlight">3</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">8</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">High Protein</td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value highlight">2</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                </tr>

                                                <!-- Meat -->
                                                <tr>
                                                    <td class="food-group-header" colspan="14">Meat</td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">Low Fat</td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value highlight">4</span></td>
                                                    <td><span class="exchange-value">3.5</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">3.5</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">Medium Fat</td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value highlight">1</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">1</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="food-subgroup">High Fat</td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value highlight">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                    <td><span class="exchange-value">-</span></td>
                                                </tr>

                                                <!-- Fat -->
                                                <tr>
                                                    <td class="food-group-header">Fat</td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value">2</span></td>
                                                    <td><span class="exchange-value highlight">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                </tr>

                                                <!-- Sugar -->
                                                <tr>
                                                    <td class="food-group-header">Sugar</td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value highlight">4</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">3</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                    <td><span class="exchange-value">5</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">6</span></td>
                                                    <td><span class="exchange-value">4</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div> <!-- end desktop-view -->

                            <!-- MOBILE ONLY: Dynamic Calorie Pill Selector -->
                            <div class="mobile-calorie-pills-container mobile-view">
                                <div class="pill-slider-label">Select Calorie Level</div>
                                <div class="mobile-pill-slider scroll-hide" id="mobilePillSlider">
                                    <div class="calorie-pill active" data-value="1200">1200</div>
                                    <div class="calorie-pill" data-value="1300">1300</div>
                                    <div class="calorie-pill" data-value="1400">1400</div>
                                    <div class="calorie-pill" data-value="1500">1500</div>
                                    <div class="calorie-pill" data-value="1600">1600</div>
                                    <div class="calorie-pill" data-value="1700">1700</div>
                                    <div class="calorie-pill" data-value="1800">1800</div>
                                    <div class="calorie-pill" data-value="1900">1900</div>
                                    <div class="calorie-pill" data-value="2000">2000</div>
                                    <div class="calorie-pill" data-value="2100">2100</div>
                                    <div class="calorie-pill" data-value="2200">2200</div>
                                    <div class="calorie-pill" data-value="2300">2300</div>
                                    <div class="calorie-pill" data-value="2400">2400</div>
                                </div>
                            </div>

                            <!-- MOBILE ONLY: Dynamic Calorie Card View -->
                            <div class="mobile-calorie-cards mobile-view" id="mobileCalorieCards">
                                <!-- Rendered by JS -->
                            </div>

                            <div class="food-exchange-footer">
                                <p>Food The diet prescription considered in the rating +/- 5 for macronutrients and +/-
                                    50
                                    for
                                    the calories. Refers to grams for carbohydrate, protein and fat which follows the
                                    percent
                                    (%) distribution of 65-15-20, respectively. ++Some
                                    vegetables can be included in meals as much as desired.</p>

                                <div class="food-exchange-legend">
                                    <div class="legend-item">
                                        <div class="legend-color legend-vegetable"></div>
                                        <span>Vegetable</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color legend-fruit"></div>
                                        <span>Fruit</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color legend-milk"></div>
                                        <span>Milk</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color legend-rice"></div>
                                        <span>Rice</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color legend-meat"></div>
                                        <span>Meat</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color legend-fat"></div>
                                        <span>Fat</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color legend-sugar"></div>
                                        <span>Sugar</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="macronutrient">
                        <div class="section-header">
                            <h2>MACRONUTRIENT COMPOSITION OF FOOD EXCHANGE LISTS</h2>
                            <div class="info-btn" id="macronutrientInfo">
                                <i class="fas fa-info"></i>
                            </div>
                        </div>
                        <div class="macronutrient-grid">
                            <!-- Vegetable Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Vegetable</div>
                                    <div class="macronutrient-energy">10 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">5g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">1g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                            </div>

                            <!-- Fruit Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Fruit</div>
                                    <div class="macronutrient-energy">40 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">10g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                            </div>

                            <!-- Whole Milk Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Milk - Whole</div>
                                    <div class="macronutrient-energy">170 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">12g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">8g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">10g</div>
                                </div>
                            </div>

                            <!-- Low Fat Milk Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Milk - Low Fat</div>
                                    <div class="macronutrient-energy">125 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">12g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">8g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">5g</div>
                                </div>
                            </div>

                            <!-- Non-Fat Milk Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Milk - Non-Fat</div>
                                    <div class="macronutrient-energy">80 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">12g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">8g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                            </div>

                            <!-- Low Protein Rice Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Rice - Low Protein</div>
                                    <div class="macronutrient-energy">92 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">23g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                            </div>

                            <!-- Medium Protein Rice Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Rice - Medium Protein</div>
                                    <div class="macronutrient-energy">100 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">23g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">2g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                            </div>

                            <!-- High Protein Rice Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Rice - High Protein</div>
                                    <div class="macronutrient-energy">108 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">23g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">4g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                            </div>

                            <!-- Low Fat Meat Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Meat - Low Fat</div>
                                    <div class="macronutrient-energy">41 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">8g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">1g</div>
                                </div>
                            </div>

                            <!-- Medium Fat Meat Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Meat - Medium Fat</div>
                                    <div class="macronutrient-energy">86 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">8g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">6g</div>
                                </div>
                            </div>

                            <!-- High Fat Meat Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Meat - High Fat</div>
                                    <div class="macronutrient-energy">122 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">8g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">10g</div>
                                </div>
                            </div>

                            <!-- Fat Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Fat</div>
                                    <div class="macronutrient-energy">43 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">5g</div>
                                </div>
                            </div>

                            <!-- Sugar Card -->
                            <div class="macronutrient-card">
                                <div class="macronutrient-header">
                                    <div class="macronutrient-title">Sugar</div>
                                    <div class="macronutrient-energy">20 kcal</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Carbohydrates</div>
                                    <div class="macronutrient-amount">5g</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Protein</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                                <div class="macronutrient-values">
                                    <div class="macronutrient-label">Fat</div>
                                    <div class="macronutrient-amount">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="calculation">
                        <div class="section-header">
                            <h2>SAMPLE COMPUTATION AND DISTRIBUTION (1500 KCAL)</h2>
                            <div class="info-btn" id="calculationInfo">
                                <i class="fas fa-info"></i>
                            </div>
                        </div>
                        <div class="calculation-visual">
                            <!-- Step 1 -->
                            <div class="calculation-step">
                                <div class="step-header">
                                    <div class="step-title">Fixed Food Groups</div>
                                    <div class="step-number">1</div>
                                </div>
                                <div class="step-content">
                                    <p>Start with fixed food groups that provide baseline nutrition.</p>
                                </div>
                                <div class="step-values">
                                    <div class="value-card">
                                        <div class="value-label">Vegetable</div>
                                        <div class="value-amount">3 exchanges</div>
                                    </div>
                                    <div class="value-card">
                                        <div class="value-label">Fruit</div>
                                        <div class="value-amount">5 exchanges</div>
                                    </div>
                                    <div class="value-card">
                                        <div class="value-label">Milk</div>
                                        <div class="value-amount">1 exchange</div>
                                    </div>
                                    <div class="value-card">
                                        <div class="value-label">Sugar</div>
                                        <div class="value-amount">3 exchanges</div>
                                    </div>
                                </div>
                                <div class="step-total">
                                    Carbohydrate Partial Sum: 86g
                                </div>
                            </div>

                            <!-- Step 2 -->
                            <div class="calculation-step">
                                <div class="step-header">
                                    <div class="step-title">Rice Exchanges Calculation</div>
                                    <div class="step-number">2</div>
                                </div>
                                <div class="step-content">
                                    <p>Calculate remaining carbohydrate needs and determine rice exchanges.</p>
                                    <p><strong>245g (prescribed) - 86g (partial) = 159g remaining</strong></p>
                                    <p>159g ÷ 23g (per rice exchange) = 6.91 ≈ 7 rice exchanges</p>
                                </div>
                                <div class="step-values">
                                    <div class="value-card">
                                        <div class="value-label">Rice A</div>
                                        <div class="value-amount">1 exchange</div>
                                    </div>
                                    <div class="value-card">
                                        <div class="value-label">Rice B</div>
                                        <div class="value-amount">5 exchanges</div>
                                    </div>
                                    <div class="value-card">
                                        <div class="value-label">Rice C</div>
                                        <div class="value-amount">1 exchange</div>
                                    </div>
                                </div>
                                <div class="step-total">
                                    Protein Partial Sum: 25g
                                </div>
                            </div>

                            <!-- Step 3 -->
                            <div class="calculation-step">
                                <div class="step-header">
                                    <div class="step-title">Meat Exchanges Calculation</div>
                                    <div class="step-number">3</div>
                                </div>
                                <div class="step-content">
                                    <p>Calculate remaining protein needs and determine meat exchanges.</p>
                                    <p><strong>55g (prescribed) - 25g (partial) = 30g remaining</strong></p>
                                    <p>30g ÷ 8g (per meat exchange) = 3.75 ≈ 4 meat exchanges</p>
                                </div>
                                <div class="step-values">
                                    <div class="value-card">
                                        <div class="value-label">Low Fat Meat</div>
                                        <div class="value-amount">2 exchanges</div>
                                    </div>
                                    <div class="value-card">
                                        <div class="value-label">Medium Fat Meat</div>
                                        <div class="value-amount">2 exchanges</div>
                                    </div>
                                </div>
                                <div class="step-total">
                                    Fat Partial Sum: 24g
                                </div>
                            </div>

                            <!-- Step 4 -->
                            <div class="calculation-step">
                                <div class="step-header">
                                    <div class="step-title">Fat Exchanges Calculation</div>
                                    <div class="step-number">4</div>
                                </div>
                                <div class="step-content">
                                    <p>Calculate remaining fat needs and determine fat exchanges.</p>
                                    <p><strong>35g (prescribed) - 24g (partial) = 11g remaining</strong></p>
                                    <p>11g ÷ 5g (per fat exchange) = 2.2 ≈ 2 fat exchanges</p>
                                </div>
                                <div class="step-values">
                                    <div class="value-card">
                                        <div class="value-label">Fat</div>
                                        <div class="value-amount">2 exchanges</div>
                                    </div>
                                </div>
                                <div class="step-total">
                                    Final Total: 247g Carbs, 57g Protein, 34g Fat, 1502 kcal
                                </div>
                            </div>
                        </div>
                        <div class="food-exchange-footer">
                            <p><strong>Diet Prescription:</strong> 1500 kcal, Carbohydrate 245 g, Protein 55 g, Fat 35 g
                            </p>
                        </div>
                    </div>

                    <div class="tab-content" id="distribution">
                        <div class="section-header">
                            <h2>DISTRIBUTION OF HOUSEHOLD MEASUREMENTS PER MEAL</h2>
                            <div class="info-btn" id="distributionInfo">
                                <i class="fas fa-info"></i>
                            </div>
                        </div>
                        <div class="distribution-grid">
                            <!-- Breakfast -->
                            <div class="distribution-card">
                                <div class="distribution-header">
                                    <div class="distribution-title">Breakfast</div>
                                    <div class="distribution-exchange">8 Household Measurements</div>
                                </div>
                                <div class="distribution-time">
                                    <i class="fas fa-clock"></i>
                                    <span>7:00 AM</span>
                                </div>
                                <div class="distribution-items">
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-carrot"></i>
                                        </div>
                                        <div class="distribution-item-name">Vegetables</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-apple-alt"></i>
                                        </div>
                                        <div class="distribution-item-name">Fruit</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-bread-slice"></i>
                                        </div>
                                        <div class="distribution-item-name">Rice C</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-wine-bottle"></i>
                                        </div>
                                        <div class="distribution-item-name">Whole Milk</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-drumstick-bite"></i>
                                        </div>
                                        <div class="distribution-item-name">Low Fat Meat</div>
                                        <div class="distribution-item-amount">2 Household Measurements</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-oil-can"></i>
                                        </div>
                                        <div class="distribution-item-name">Fat</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <div class="distribution-item-name">Sugar</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                </div>
                                <div class="distribution-summary">
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">8</div>
                                        <div class="distribution-summary-label">Total Household Measurements</div>
                                    </div>
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">473</div>
                                        <div class="distribution-summary-label">Calories</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Morning Snack -->
                            <div class="distribution-card">
                                <div class="distribution-header">
                                    <div class="distribution-title">Morning Snack</div>
                                    <div class="distribution-exchange">3 Household Measurements</div>
                                </div>
                                <div class="distribution-time">
                                    <i class="fas fa-clock"></i>
                                    <span>10:00 AM</span>
                                </div>
                                <div class="distribution-items">
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-apple-alt"></i>
                                        </div>
                                        <div class="distribution-item-name">Fruit</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-bread-slice"></i>
                                        </div>
                                        <div class="distribution-item-name">Rice B</div>
                                        <div class="distribution-item-amount">2 Household Measurements</div>
                                    </div>
                                </div>
                                <div class="distribution-summary">
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">3</div>
                                        <div class="distribution-summary-label">Total Household Measurements</div>
                                    </div>
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">240</div>
                                        <div class="distribution-summary-label">Calories</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lunch -->
                            <div class="distribution-card">
                                <div class="distribution-header">
                                    <div class="distribution-title">Lunch</div>
                                    <div class="distribution-exchange">7.5 Household Measurements</div>
                                </div>
                                <div class="distribution-time">
                                    <i class="fas fa-clock"></i>
                                    <span>1:00 PM</span>
                                </div>
                                <div class="distribution-items">
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-carrot"></i>
                                        </div>
                                        <div class="distribution-item-name">Vegetables</div>
                                        <div class="distribution-item-amount">½ Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-apple-alt"></i>
                                        </div>
                                        <div class="distribution-item-name">Fruit</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-bread-slice"></i>
                                        </div>
                                        <div class="distribution-item-name">Rice B</div>
                                        <div class="distribution-item-amount">4 Household Measurements</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-drumstick-bite"></i>
                                        </div>
                                        <div class="distribution-item-name">Medium Fat Meat</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-oil-can"></i>
                                        </div>
                                        <div class="distribution-item-name">Fat</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                </div>
                                <div class="distribution-summary">
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">7.5</div>
                                        <div class="distribution-summary-label">Total Household Measurements</div>
                                    </div>
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">579</div>
                                        <div class="distribution-summary-label">Calories</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Afternoon Snack -->
                            <div class="distribution-card">
                                <div class="distribution-header">
                                    <div class="distribution-title">Afternoon Snack</div>
                                    <div class="distribution-exchange">3 Household Measurements</div>
                                </div>
                                <div class="distribution-time">
                                    <i class="fas fa-clock"></i>
                                    <span>4:00 PM</span>
                                </div>
                                <div class="distribution-items">
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-bread-slice"></i>
                                        </div>
                                        <div class="distribution-item-name">Rice A</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <div class="distribution-item-name">Sugar</div>
                                        <div class="distribution-item-amount">2 Household Measurements</div>
                                    </div>
                                </div>
                                <div class="distribution-summary">
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">3</div>
                                        <div class="distribution-summary-label">Total Household Measurements</div>
                                    </div>
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">132</div>
                                        <div class="distribution-summary-label">Calories</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Supper -->
                            <div class="distribution-card">
                                <div class="distribution-header">
                                    <div class="distribution-title">Supper</div>
                                    <div class="distribution-exchange">7.5 Household Measurements</div>
                                </div>
                                <div class="distribution-time">
                                    <i class="fas fa-clock"></i>
                                    <span>7:00 PM</span>
                                </div>
                                <div class="distribution-items">
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-carrot"></i>
                                        </div>
                                        <div class="distribution-item-name">Vegetables</div>
                                        <div class="distribution-item-amount">1½ Household Measurements</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-apple-alt"></i>
                                        </div>
                                        <div class="distribution-item-name">Fruit</div>
                                        <div class="distribution-item-amount">2 Household Measurements</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-bread-slice"></i>
                                        </div>
                                        <div class="distribution-item-name">Rice B</div>
                                        <div class="distribution-item-amount">3 Household Measurements</div>
                                    </div>
                                    <div class="distribution-item">
                                        <div class="distribution-item-icon">
                                            <i class="fas fa-drumstick-bite"></i>
                                        </div>
                                        <div class="distribution-item-name">Low Fat Meat</div>
                                        <div class="distribution-item-amount">1 Household Measurement</div>
                                    </div>
                                </div>
                                <div class="distribution-summary">
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">7.5</div>
                                        <div class="distribution-summary-label">Total Household Measurements</div>
                                    </div>
                                    <div class="distribution-summary-item">
                                        <div class="distribution-summary-value">445</div>
                                        <div class="distribution-summary-label">Calories</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="sample-menu">
                        <div class="section-header">
                            <h2>SAMPLE ONE-DAY MENU</h2>
                            <div class="info-btn" id="sampleMenuInfo">
                                <i class="fas fa-info"></i>
                            </div>
                        </div>
                        <div class="menu-container">
                            <!-- Breakfast Card -->
                            <div class="meal-card">
                                <div class="meal-header">
                                    <div class="meal-title"><i class="fas fa-coffee"></i> BREAKFAST <small class="meal-subtitle">Household Measurements</small></div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Mango, ripe</div>
                                    <div class="menu-measure">1 slice</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Vegetable Omelet</div>
                                    <div class="menu-measure">-</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Egg</div>
                                    <div class="menu-measure">1 pc</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Bell pepper, Onion</div>
                                    <div class="menu-measure">1/2 cup</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Oil, coconut</div>
                                    <div class="menu-measure">1 tsp</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Pan de sal</div>
                                    <div class="menu-measure">1 1/2 pcs</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Milk, powder, full cream</div>
                                    <div class="menu-measure">5 Tbsp</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Sugar, brown</div>
                                    <div class="menu-measure">1 tsp</div>
                                </div>
                            </div>

                            <!-- AM Snack Card -->
                            <div class="meal-card">
                                <div class="meal-header">
                                    <div class="meal-title"><i class="fas fa-apple-alt"></i> AM SNACK <small class="meal-subtitle">Household Measurements</small></div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Purple yam</div>
                                    <div class="menu-measure">1 slice</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Coconut water</div>
                                    <div class="menu-measure">1 glass</div>
                                </div>
                            </div>

                            <!-- Lunch Card -->
                            <div class="meal-card">
                                <div class="meal-header">
                                    <div class="meal-title"><i class="fas fa-utensils"></i> LUNCH <small class="meal-subtitle">Household Measurements</small></div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Chicken Thigh</div>
                                    <div class="menu-measure">1 medium</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Mulunggay leaves, Papaya</div>
                                    <div class="menu-measure">1 cup</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Oil, coconut</div>
                                    <div class="menu-measure">1 tsp</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Boiled Rice</div>
                                    <div class="menu-measure">1 cup</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Papaya</div>
                                    <div class="menu-measure">1 cup</div>
                                </div>
                            </div>

                            <!-- PM Snack Card -->
                            <div class="meal-card">
                                <div class="meal-header">
                                    <div class="meal-title"><i class="fas fa-cookie"></i> PM SNACK <small class="meal-subtitle">Household Measurements</small></div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Sweet potato, boiled</div>
                                    <div class="menu-measure">1 pc</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Sugar, brown</div>
                                    <div class="menu-measure">2 tsp</div>
                                </div>
                            </div>

                            <!-- Dinner Card -->
                            <div class="meal-card">
                                <div class="meal-header">
                                    <div class="meal-title"><i class="fas fa-moon"></i> DINNER <small class="meal-subtitle">Household Measurements</small></div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Bangus, sliced</div>
                                    <div class="menu-measure">1 slice</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Stringbeans, Squash</div>
                                    <div class="menu-measure">1 cup</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Tomato, Eggplant</div>
                                    <div class="menu-measure">1/2 cup</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Boiled Rice</div>
                                    <div class="menu-measure">1 cup</div>
                                </div>
                                <div class="menu-item">
                                    <div class="menu-food">Banana, Lacatan</div>
                                    <div class="menu-measure">1 pc</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="food-exchange-list">
                        <div class="section-header">
                            <h2>FOOD EXCHANGE LIST (FEL)</h2>
                        </div>

                        <div class="fel-vtabs d-none d-md-flex">
                            <!-- Vertical Tab Sidebar -->
                            <div class="fel-vtab-sidebar">
                                <button class="fel-vtab active" data-fel="fel-nuts"><i class="fas fa-seedling"></i> Nuts
                                    &amp; Seeds</button>
                                <button class="fel-vtab" data-fel="fel-vegetables"><i class="fas fa-leaf"></i>
                                    Vegetables</button>
                                <button class="fel-vtab" data-fel="fel-fruits"><i class="fas fa-apple-alt"></i>
                                    Fruits</button>
                                <button class="fel-vtab" data-fel="fel-misc"><i class="fas fa-th-list"></i>
                                    Miscellaneous</button>
                                <button class="fel-vtab" data-fel="fel-seafood"><i class="fas fa-fish"></i> Fish &amp;
                                    Seafood</button>
                                <button class="fel-vtab" data-fel="fel-hf-seafood"><i class="fas fa-star"></i> HF
                                    Seafood</button>
                                <button class="fel-vtab" data-fel="fel-appendix"><i class="fas fa-book"></i> Appendix
                                    B</button>
                                <button class="fel-vtab" data-fel="fel-milk"><i class="fas fa-wine-bottle"></i>
                                    Milk</button>
                                <button class="fel-vtab" data-fel="fel-sugar"><i class="fas fa-cube"></i> Sugar</button>
                                <button class="fel-vtab" data-fel="fel-condiments"><i class="fas fa-pepper-hot"></i>
                                    Condiments</button>
                                <button class="fel-vtab" data-fel="fel-alcohol"><i class="fas fa-cocktail"></i>
                                    Alcoholic Bev.</button>
                            </div>

                            <!-- Vertical Tab Content Panes -->
                            <div class="fel-vtab-content">

                                <!-- Nuts & Seeds -->
                                <div class="fel-pane active" id="fel-nuts">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th>Fat</th>
                                                        <th>Seeds</th>
                                                        <th>Appendix B / Selected food list</th>
                                                        <th>Other</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="4">NUTS, DRIED BEANS,
                                                            SEEDS, PRODUCTS FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Almond</td>
                                                        <td>Flaxseed</td>
                                                        <td>Pistachio</td>
                                                        <td>Lentils</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Macadamia</td>
                                                        <td>Pumpkin seed</td>
                                                        <td>Garbansas, tuyo</td>
                                                        <td>Pecan nut</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Mixed nuts</td>
                                                        <td>-</td>
                                                        <td>Hazelnut, w/wo skin, roasted</td>
                                                        <td>Sunflower seed</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Walnut</td>
                                                        <td>-</td>
                                                        <td>Snapbean seed</td>
                                                        <td>Peanut brittle</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Vegetables -->
                                <div class="fel-pane" id="fel-vegetables">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th colspan="3">Vegetable Varieties</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="3">VEGETABLES FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Artichoke</td>
                                                        <td>Coconut shoot</td>
                                                        <td>Tomato juice</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Kalamansi</td>
                                                        <td>Yacon</td>
                                                        <td>Alfalfa sprouts</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Brocolli</td>
                                                        <td>babycorn</td>
                                                        <td>Arugula</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Turnip, tuber</td>
                                                        <td>Chickpea</td>
                                                        <td>Bokchoy</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Jute, leaves</td>
                                                        <td>Mixed veg</td>
                                                        <td>Turnip pod</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Fruits -->
                                <div class="fel-pane" id="fel-fruits">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th colspan="5">Fruit Varieties</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="5">FRUITS FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Blueberries</td>
                                                        <td>Cherries, hinog</td>
                                                        <td>Kiwifruit, berde</td>
                                                        <td>Longan</td>
                                                        <td>Champoy</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Milon, tagalog</td>
                                                        <td>Orange, Florida</td>
                                                        <td>Orange, kiat kiat</td>
                                                        <td>Orange, ponkan</td>
                                                        <td>Passion fruit</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Strawberries, heavy syrup</td>
                                                        <td>Singkamas</td>
                                                        <td>Lemon juice</td>
                                                        <td>Niyog, tubig / coconut water</td>
                                                        <td>Orange juice</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Apple sauce, sweetened</td>
                                                        <td>Apple sauce, unsweetened</td>
                                                        <td>Blackberries, heavy syrup</td>
                                                        <td>Blueberries, light syrup</td>
                                                        <td>Strawberries, frozen, unsweetened</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Miscellaneous -->
                                <div class="fel-pane" id="fel-misc">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%">Classification / Filipino Name</th>
                                                        <th style="width:50%">Category / English Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="2">MISCELLANEOUS
                                                            CLASSIFICATIONS</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">EGG</td>
                                                        <td>MISCELLANEOUS</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">MEAT</td>
                                                        <td>NON-ALCOHOLIC BEV</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">COMMERCIAL BABY FOODS</td>
                                                        <td>COMBINATION FOODS/MIXED DISHES</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">STARCHY ROOTS, TUBERS, PRODUCTS</td>
                                                        <td>CEREAL AND PRODUCTS</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Fish / Seafood -->
                                <div class="fel-pane" id="fel-seafood">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%">Classification / Filipino Name</th>
                                                        <th style="width:50%">Category / English Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="2">FISH/SEAFOOD FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Dulong</td>
                                                        <td>anchovy fry</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">batotoy</td>
                                                        <td>mollusks, sakhalin surf clam/cockles</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">tuway</td>
                                                        <td>mollusks, hard clam</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup"
                                                            style="font-weight:600;color:var(--primary)">Daing/dried:
                                                        </td>
                                                        <td>-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">sapsap</td>
                                                        <td>slipmouth, common</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">tamban</td>
                                                        <td>sardine, indian</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">tanigi/tangigi</td>
                                                        <td>mackerel, spanish</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">tilapia</td>
                                                        <td>tilapia</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- HF Seafood -->
                                <div class="fel-pane" id="fel-hf-seafood">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%">Classification / Filipino Name</th>
                                                        <th style="width:50%">Category / English Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="2">HF SEAFOOD/FISH FEL
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup"
                                                            style="font-weight:600;color:var(--primary)">canned:</td>
                                                        <td>-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">sardines, spanish style</td>
                                                        <td>sardines, in spiced oil</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">tuna flakes in vegetable oil</td>
                                                        <td>tuna flakes in vegetable oil</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Appendix B -->
                                <div class="fel-pane" id="fel-appendix">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%">Classification / Filipino Name</th>
                                                        <th style="width:50%">Category / English Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="2">APPENDIX B SELECTED
                                                            FOOD LIST FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup"
                                                            style="font-weight:600;color:var(--primary)">shrimp/shells,
                                                            cooked:</td>
                                                        <td style="font-weight:600;color:var(--primary)">freefoods:</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">tulya</td>
                                                        <td>fishball</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup"
                                                            style="font-weight:600;color:var(--primary)">processed:</td>
                                                        <td>-</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">anchovy, spicy</td>
                                                        <td>dumpling seafood, fried/steamed</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Milk -->
                                <div class="fel-pane" id="fel-milk">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%">Classification / Filipino Name</th>
                                                        <th style="width:50%">Category / English Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="2">MILK FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Gatas lowfat</td>
                                                        <td>milk low fat</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">gatas skim</td>
                                                        <td>milk skim</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">yogurt plain skim</td>
                                                        <td>-</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Sugar -->
                                <div class="fel-pane" id="fel-sugar">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:50%">Classification / Filipino Name</th>
                                                        <th style="width:50%">Category / English Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="2">SUGAR FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">ASUKAL MUSOVADO</td>
                                                        <td>LOKUM</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">PRUNES</td>
                                                        <td>KIAMOY</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">CHEWING/BUBBLE GUM</td>
                                                        <td>DRIED PINEAPPLE</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">DATES PITTED</td>
                                                        <td>DRIED PAPAYA CHUNKS</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">DIKYAM</td>
                                                        <td>DRIED MANGO</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">DRIED JACKFRUIT</td>
                                                        <td>DRIED KIWI</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">TIRA-TIRA(CANDY,PULLED)</td>
                                                        <td>GELATIN UNSWEET(APPENDIX C FEL)</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">ICE CANDY</td>
                                                        <td>POLVORON</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">ICE DROP</td>
                                                        <td>KUNDOL,CANDIED(WAX GOURD)</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Condiments -->
                                <div class="fel-pane" id="fel-condiments">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th colspan="4">Condiment Varieties</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="4">CONDIMENTS FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Chili powder</td>
                                                        <td>Herbs</td>
                                                        <td>Paprika</td>
                                                        <td>Soy sauces</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Cinnamon</td>
                                                        <td>Hot pepper sauce</td>
                                                        <td>Pepper corn</td>
                                                        <td>Spices</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Curry</td>
                                                        <td>Mustard</td>
                                                        <td>Pimiento</td>
                                                        <td>Barbecue sauce</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Flavoring extract</td>
                                                        <td>Oregano</td>
                                                        <td>Saffron</td>
                                                        <td>Gravy, commercial</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Pickel</td>
                                                        <td>Sweet chili sauce</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                                <!-- Alcoholic Beverages -->
                                <div class="fel-pane" id="fel-alcohol">
                                    <div class="table-container">
                                        <div class="mobile-scroll-wrapper">

                                            <table class="food-exchange-table">
                                                <thead>
                                                    <tr>
                                                        <th colspan="6">Alcoholic Beverages</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="food-group-header" colspan="6">ALCOHOL BEV FEL</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Wine, rose</td>
                                                        <td>Beer, cerveza</td>
                                                        <td>Beer, fruit flavored</td>
                                                        <td>Beer, light</td>
                                                        <td>Wine, vermouth</td>
                                                        <td>Beer, strong</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Brandy</td>
                                                        <td>Brandy, cognac</td>
                                                        <td>Brandy, light</td>
                                                        <td>Daiquiri</td>
                                                        <td>Gin</td>
                                                        <td>Manhattan</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Martini</td>
                                                        <td>-</td>
                                                        <td>Sake/Soju</td>
                                                        <td>Tequila</td>
                                                        <td>Whisky, scotch</td>
                                                        <td>Vodka</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="food-subgroup">Wine, port</td>
                                                        <td>Wine, red</td>
                                                        <td>Wine, white</td>
                                                        <td>Wine, sparkling</td>
                                                        <td>Wine, fruit</td>
                                                        <td>-</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        </div>
                                    </div>
                                </div>

                            </div><!-- /.fel-vtab-content -->
                        </div><!-- /.fel-vtabs -->

                        <!-- MOBILE VIEW (Task 4) -->
                        <div class="fel-mobile-accordion d-block d-md-none" id="felMobileAccordion">

                            <!-- Nuts -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-seedling"></i> Nuts &amp; Seeds</div>
                                <div class="fel-accordion-content">
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">Fat</span><span
                                            class="fel-mobile-value">Almond, Macadamia, Mixed nuts, Walnut</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">Seeds</span><span
                                            class="fel-mobile-value">Flaxseed, Pumpkin seed</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">Appendix
                                            B</span><span class="fel-mobile-value">Pistachio, Garbansas, Hazelnut,
                                            Snapbean seed</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">Other</span><span
                                            class="fel-mobile-value">Lentils, Pecan nut, Sunflower seed, Peanut
                                            brittle</span></div>
                                </div>
                            </div>

                            <!-- Vegetables -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-leaf"></i> Vegetables</div>
                                <div class="fel-accordion-content">
                                    <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.6; margin: 0;">
                                        Artichoke, Coconut shoot, Tomato juice, Kalamansi, Yacon, Alfalfa sprouts,
                                        Brocolli, babycorn, Arugula, Turnip tuber, Chickpea, Bokchoy, Jute leaves, Mixed
                                        veg, Turnip pod
                                    </p>
                                </div>
                            </div>

                            <!-- Fruits -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-apple-alt"></i> Fruits</div>
                                <div class="fel-accordion-content">
                                    <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.6; margin: 0;">
                                        Blueberries, Cherries, Kiwifruit, Longan, Champoy, Milon, Orange (Florida, kiat
                                        kiat, ponkan), Passion fruit, Strawberries, Singkamas, Lemon juice, Coconut
                                        water, Orange juice, Apple sauce, Blackberries
                                    </p>
                                </div>
                            </div>

                            <!-- Miscellaneous -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-th-list"></i> Miscellaneous</div>
                                <div class="fel-accordion-content">
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">EGG</span><span
                                            class="fel-mobile-value">MISCELLANEOUS</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">MEAT</span><span
                                            class="fel-mobile-value">NON-ALCOHOLIC BEV</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">BABY
                                            FOODS</span><span class="fel-mobile-value">MIXED DISHES</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">STARCHY
                                            ROOTS</span><span class="fel-mobile-value">CEREAL PRODUCTS</span></div>
                                </div>
                            </div>

                            <!-- Seafood -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-fish"></i> Fish &amp; Seafood</div>
                                <div class="fel-accordion-content">
                                    <div class="fel-mobile-item-row"><span
                                            class="fel-mobile-label">Fresh/Raw</span><span
                                            class="fel-mobile-value">Dulong, batotoy, tuway</span></div>
                                    <div class="fel-mobile-item-row"><span
                                            class="fel-mobile-label">Daing/dried</span><span
                                            class="fel-mobile-value">sapsap, tamban, tanigi, tilapia</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">HF
                                            (Canned)</span><span class="fel-mobile-value">sardines, tuna flakes in
                                            vegetable oil</span></div>
                                    <div class="fel-mobile-item-row"><span class="fel-mobile-label">Processed (App.
                                            B)</span><span class="fel-mobile-value">tulya, fishball, spicy anchovy,
                                            seafood dumpling</span></div>
                                </div>
                            </div>

                            <!-- Milk -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-wine-bottle"></i> Milk</div>
                                <div class="fel-accordion-content">
                                    <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.6; margin: 0;">
                                        Lowfat Milk, Skim Milk, Yogurt (Plain Skim)
                                    </p>
                                </div>
                            </div>

                            <!-- Sugar -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-cube"></i> Sugar</div>
                                <div class="fel-accordion-content">
                                    <p style="font-size: 0.9rem; color: #4b5563; line-height: 1.6; margin: 0;">
                                        Asukal Muscovado, Prunes, Chewing Gum, Dates, Dikyam, Dried Fruit (Jackfruit,
                                        Pineapple, Papaya, Mango, Kiwi), Tira-tira, Ice Candy, Ice Drop, Lokum, Kiamoy,
                                        Gelatin, Polvoron, Kundol
                                    </p>
                                </div>
                            </div>

                            <!-- Condiments & Alcohol -->
                            <div class="fel-accordion-item">
                                <div class="fel-accordion-header"
                                    onclick="this.nextElementSibling.classList.toggle('active')"><i
                                        class="fas fa-pepper-hot"></i> Condiments &amp; Alcohol</div>
                                <div class="fel-accordion-content">
                                    <div style="margin-bottom: 12px;">
                                        <div class="fel-mobile-label" style="margin-bottom: 5px;">Condiments</div>
                                        <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">Chili powder,
                                            Cinnamon, Curry, Herbs, Mustard, Oregano, Paprika, Pepper, Soy sauce,
                                            Barbecue sauce</p>
                                    </div>
                                    <div>
                                        <div class="fel-mobile-label" style="margin-bottom: 5px;">Alcoholic Bev.</div>
                                        <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">Wine, Beer, Brandy,
                                            Martini, Sake, Daiquiri, Tequila, Gin, Whisky, Vodka, Manhattan</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>



                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            // Tab functionality
                            const tabs = document.querySelectorAll('.tab');
                            const tabContents = document.querySelectorAll('.tab-content');

                            tabs.forEach(tab => {
                                tab.addEventListener('click', () => {
                                    const tabId = tab.getAttribute('data-tab');

                                    // Remove active class from all tabs and contents
                                    tabs.forEach(t => t.classList.remove('active'));
                                    tabContents.forEach(content => content.classList.remove('active'));

                                    // Add active class to clicked tab and corresponding content
                                    tab.classList.add('active');
                                    document.getElementById(tabId).classList.add('active');
                                });
                            });

                            // Food Exchange List vertical tab switcher
                            const felTabs = document.querySelectorAll('.fel-vtab');
                            const felPanes = document.querySelectorAll('.fel-pane');
                            felTabs.forEach(btn => {
                                btn.addEventListener('click', () => {
                                    felTabs.forEach(b => b.classList.remove('active'));
                                    felPanes.forEach(p => p.classList.remove('active'));
                                    btn.classList.add('active');
                                    const target = btn.getAttribute('data-fel');
                                    document.getElementById(target).classList.add('active');
                                });
                            });

                            // Expandable calculation steps
                            const calculationSteps = document.querySelectorAll('.calculation-step');
                            calculationSteps.forEach(step => {
                                step.addEventListener('click', () => {
                                    step.classList.toggle('expanded');
                                });
                            });

                            // Expandable meal cards
                            const mealCards = document.querySelectorAll('.meal-card');
                            mealCards.forEach(card => {
                                card.addEventListener('click', () => {
                                    card.classList.toggle('expanded');
                                });
                            });

                            // Original functionality for food exchange table
                            const calorieSelector = document.getElementById('calorieSelector');
                            const tableHeaders = document.querySelectorAll('.food-exchange-table th');
                            const exchangeValues = document.querySelectorAll('.exchange-value');

                            // Set initial highlight for 1600 calories
                            highlightSelectedCalorie(1600);

                            // Add event listener to calorie selector
                            calorieSelector.addEventListener('change', function () {
                                const selectedCalories = parseInt(this.value);
                                highlightSelectedCalorie(selectedCalories);
                            });

                            // Function to highlight selected calorie column
                            function highlightSelectedCalorie(calories) {
                                // Remove current active highlight from headers and value spans
                                tableHeaders.forEach(header => header.classList.remove('active'));
                                exchangeValues.forEach(val => val.classList.remove('highlight'));

                                // Remove specialized active-cell class from all td elements
                                const allCells = document.querySelectorAll('.food-exchange-table td');
                                allCells.forEach(cell => cell.classList.remove('active-cell'));

                                // Find the index of the column to highlight
                                let columnIndex = -1;
                                for (let i = 1; i < tableHeaders.length; i++) {
                                    if (parseInt(tableHeaders[i].textContent) === calories) {
                                        columnIndex = i;
                                        break;
                                    }
                                }

                                if (columnIndex !== -1) {
                                    // Highlight the header
                                    tableHeaders[columnIndex].classList.add('active');

                                    // Highlight cells in the column
                                    const rows = document.querySelectorAll('.food-exchange-table tbody tr');
                                    rows.forEach(row => {
                                        const cells = row.querySelectorAll('td');
                                        if (cells.length > columnIndex) {
                                            // Add helper class for mobile show/hide
                                            cells[columnIndex].classList.add('active-cell');

                                            const exchangeValue = cells[columnIndex].querySelector('.exchange-value');
                                            if (exchangeValue) {
                                                exchangeValue.classList.add('highlight');
                                            }
                                        }
                                    });
                                }
                            }

                            // ============================================
                            // MOBILE CARD VIEW ENGINE
                            // ============================================
                            const mobileCalorieCards = document.getElementById('mobileCalorieCards');

                            // Data extracted from the table at runtime
                            const calorieData = {
                                columns: [], // [1200, 1300, ... 2400]
                                groups: []   // [{ header: 'Vegetable++', isGroup: false, values: [3,3,...] }, ...]
                            };

                            function buildCalorieData() {
                                const headers = document.querySelectorAll('.calorie-ref-table thead th');
                                headers.forEach((th, i) => {
                                    if (i === 0) return;
                                    calorieData.columns.push(parseInt(th.textContent.trim()));
                                });

                                const rows = document.querySelectorAll('.calorie-ref-table tbody tr');
                                rows.forEach(row => {
                                    const cells = row.querySelectorAll('td');
                                    const firstCell = cells[0];
                                    const isGroupHeader = firstCell && firstCell.classList.contains('food-group-header');
                                    const isSubgroup = firstCell && firstCell.classList.contains('food-subgroup');
                                    const isColspan = firstCell && firstCell.getAttribute('colspan');

                                    if (!firstCell) return;
                                    const name = firstCell.textContent.trim();
                                    const values = [];
                                    cells.forEach((td, i) => {
                                        if (i === 0) return;
                                        const span = td.querySelector('.exchange-value');
                                        values.push(span ? span.textContent.trim() : td.textContent.trim());
                                    });

                                    calorieData.groups.push({
                                        name,
                                        isGroupHeader,
                                        isSubgroup,
                                        isCategoryHeader: !!isColspan,
                                        values
                                    });
                                });
                            }

                            function renderMobileCards(calories) {
                                if (!mobileCalorieCards) return;
                                const colIndex = calorieData.columns.indexOf(calories);
                                if (colIndex === -1) return;

                                mobileCalorieCards.innerHTML = '';

                                calorieData.groups.forEach(group => {
                                    if (group.isCategoryHeader) {
                                        // Render a new category section header
                                        const catHeader = document.createElement('div');
                                        catHeader.className = 'mcv-category-header';
                                        catHeader.innerHTML = `<span>${group.name.toUpperCase()}</span>`;
                                        mobileCalorieCards.appendChild(catHeader);
                                        return;
                                    }

                                    const val = group.values[colIndex] || '-';
                                    if (val === '-') return; // Skip items with no value for this calorie level

                                    const row = document.createElement('div');
                                    row.className = group.isSubgroup ? 'mcv-row mcv-row--sub' : 'mcv-row';
                                    row.innerHTML = `
                            <span class="mcv-label">${group.name}</span>
                            <span class="mcv-value">${val}</span>
                        `;
                                    mobileCalorieCards.appendChild(row);
                                });

                                // Update active state of pills
                                const pills = document.querySelectorAll('.calorie-pill');
                                pills.forEach(pill => {
                                    if (parseInt(pill.getAttribute('data-value')) === calories) {
                                        pill.classList.add('active');
                                        // Ensure the active pill is visible in the scrollable container
                                        pill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                                    } else {
                                        pill.classList.remove('active');
                                    }
                                });
                            }

                            // Add pill-slider interaction
                            const pillSlider = document.getElementById('mobilePillSlider');
                            if (pillSlider) {
                                pillSlider.addEventListener('click', function (e) {
                                    const pill = e.target.closest('.calorie-pill');
                                    if (pill) {
                                        const value = parseInt(pill.getAttribute('data-value'));
                                        calorieSelector.value = value;
                                        highlightSelectedCalorie(value);
                                        renderMobileCards(value);
                                    }
                                });
                            }

                            buildCalorieData();
                            renderMobileCards(parseInt(calorieSelector.value));

                            calorieSelector.addEventListener('change', function () {
                                const selectedCalories = parseInt(this.value);
                                highlightSelectedCalorie(selectedCalories);
                                renderMobileCards(selectedCalories);
                            });


                            const tableRows = document.querySelectorAll('.food-exchange-table tbody tr');
                            tableRows.forEach(row => {
                                row.addEventListener('mouseenter', function () {
                                    this.style.transform = 'translateY(-1px)';
                                    this.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.05)';
                                });

                                row.addEventListener('mouseleave', function () {
                                    this.style.transform = 'translateY(0)';
                                    this.style.boxShadow = 'none';
                                });
                            });

                            // Info button functionality
                            document.getElementById('foodExchangeInfo').addEventListener('click', function () {
                                alert('The Food Exchange List helps you plan balanced meals based on your calorie needs. Each "exchange" represents a serving of a particular food group with similar nutritional value.');
                            });

                            document.getElementById('macronutrientInfo').addEventListener('click', function () {
                                alert('This section shows the macronutrient composition of each food group per exchange unit. Food items in the same list contain similar amounts of energy and macronutrients (carbohydrates, protein and fat).');
                            });

                            document.getElementById('sampleMenuInfo').addEventListener('click', function () {
                                alert('This sample one-day menu demonstrates how to apply the food exchange system to create balanced meals throughout the day. Each meal includes a variety of food groups to meet nutritional needs.');
                            });

                            document.getElementById('calculationInfo').addEventListener('click', function () {
                                alert('This section shows a sample computation for a 1500 kcal diet, demonstrating how to calculate the number of exchanges needed for each food group to meet specific macronutrient targets.');
                            });

                            document.getElementById('distributionInfo').addEventListener('click', function () {
                                alert('This timeline illustrates how to distribute the food exchanges across different meals throughout the day to create a balanced eating pattern.');
                            });

                            // Mobile sidebar toggle
                            function toggleSidebar() {
                                const sidebar = document.querySelector('.sidebar');
                                sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
                            }

                            // Dynamic data-label injection for mobile cards
                            document.querySelectorAll('#food-exchange-list .food-exchange-table').forEach(table => {
                                const headers = Array.from(table.querySelectorAll('thead th'));
                                const rows = table.querySelectorAll('tbody tr');

                                rows.forEach(row => {
                                    const cells = Array.from(row.querySelectorAll('td:not(.food-group-header)'));
                                    cells.forEach((cell, index) => {
                                        let label = '';
                                        if (headers.length === 1) {
                                            // For tables with a single spanning header (e.g. "Vegetable Varieties")
                                            label = headers[0].textContent.trim();
                                        } else if (headers[index]) {
                                            // For tables where columns map 1:1 to headers
                                            label = headers[index].textContent.trim();
                                        } else {
                                            label = 'Item';
                                        }
                                        if (label) {
                                            cell.setAttribute('data-label', label);
                                        }
                                    });
                                });
                            });

                        });

                        // Spotlight tracking
                        const spotlight = document.getElementById('spotlight');
                        if (spotlight) {
                            document.addEventListener('mousemove', (e) => {
                                spotlight.style.left = e.clientX + 'px';
                                spotlight.style.top = e.clientY + 'px';
                            });
                        }
                    </script>

</body>

</html>