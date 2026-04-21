<?php
// ANTI-CACHE HEADERS
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#059669">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    <title>NutriDeq - Clinical Nutrition Intelligence</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="stylesheet" href="css/base.css?v=205">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.29/bundled/lenis.min.js"></script>
    <style>
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: linear-gradient(90deg, #059669, #10b981, #34d399);
            z-index: 10000;
            transform-origin: left;
        }

        .cursor-highlight {
            position: fixed;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(5, 150, 105, 0.15) 0%, transparent 70%);
            pointer-events: none;
            z-index: 1;
            transform: translate(-50%, -50%);
            mix-blend-mode: multiply;
        }

        .cursor-dot {
            position: fixed;
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
            pointer-events: none;
            z-index: 10001;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 10px rgba(5, 150, 105, 0.8);
            transition: width 0.3s, height 0.3s, background 0.3s;
        }

        .cursor-dot.active {
            width: 40px;
            height: 40px;
            background: rgba(5, 150, 105, 0.1);
            border: 2px solid var(--primary);
            box-shadow: none;
            mix-blend-mode: normal;
        }

        /* Lenis Setup */
        html.lenis,
        html.lenis body {
            height: auto;
        }

        .lenis.lenis-smooth {
            scroll-behavior: auto !important;
        }

        .lenis.lenis-smooth [data-lenis-prevent] {
            overscroll-behavior: contain;
        }

        .lenis.lenis-stopped {
            overflow: hidden;
        }

        .lenis.lenis-smooth iframe {
            pointer-events: none;
        }

        .split-h1 {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .h1-word {
            display: inline-block;
            transform-origin: bottom;
        }

        .gsap-failsafe {
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.03);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-fadeIn {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .animate-pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        .h1-highlight {
            background: linear-gradient(135deg, #059669, #065f46);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            position: relative;
            overflow: hidden;
        }

        .btn .btn-magnetic {
            position: relative;
            z-index: 2;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        :root {
            --primary: #059669;
            --primary-dark: #064e3b;
            --secondary: #3b82f6;
            --accent: #d97706;
            --gradient: linear-gradient(135deg, #059669, #065f46);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-main: #f1f5f9;
            --bg-surface: #ffffff;
            --border-color: #e2e8f0;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 50%, #f1f5f9 100%);
            color: var(--text-primary);
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        .page {
            position: relative;
            z-index: 1;
        }

        .bg-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.45;
            will-change: transform;
        }

        .orb-1 {
            width: 700px;
            height: 700px;
            background: #059669;
            top: -250px;
            left: -200px;
        }

        .orb-2 {
            width: 600px;
            height: 600px;
            background: #10b981;
            bottom: -200px;
            right: -150px;
        }

        .orb-3 {
            width: 450px;
            height: 450px;
            background: #34d399;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .food-animation-container {
            position: absolute;
            top: 10%;
            right: 5%;
            z-index: 5;
            opacity: 0;
        }

        .food-item {
            position: absolute;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 15px 35px rgba(5, 150, 105, 0.15);
            color: var(--primary);
        }

        .food-1 {
            top: 0;
            left: 0;
        }

        .food-2 {
            top: 50px;
            left: 90px;
            color: #d97706;
        }

        .food-3 {
            top: 120px;
            left: 10px;
            color: #3b82f6;
        }

        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.07) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 10;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.4s ease;
        }

        .nav.scrolled {
            background: rgba(248, 250, 252, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-img {
            height: 45px;
            border-radius: 10px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.7rem;
            font-weight: 900;
            color: var(--text-primary);
            text-decoration: none;
        }

        .logo span {
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn {
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(27, 67, 50, 0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(27, 67, 50, 0.25);
            filter: brightness(1.05);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.6);
            color: var(--primary);
            border: 1.5px solid var(--primary);
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            background: rgba(27, 67, 50, 0.04);
            transform: translateY(-2px);
        }

        .hero {
            min-height: 100vh;
            padding: 13rem 0 7rem;
            display: flex;
            align-items: center;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 5rem;
            align-items: center;
            perspective: 1200px;
        }

        .hero-left {
            position: relative;
            will-change: transform, opacity;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(5, 150, 105, 0.12);
            color: var(--primary-dark);
            padding: 0.7rem 1.4rem;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 1.8rem;
            border: 1px solid rgba(5, 150, 105, 0.25);
        }

        .hero-text h1 {
            font-size: clamp(3rem, 7vw, 5.8rem);
            line-height: 1.03;
            letter-spacing: -0.02em;
            margin-bottom: 1.5rem;
        }

        .hero-text h1 span {
            background: linear-gradient(135deg, #059669, #065f46);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 1.4rem;
            color: var(--text-secondary);
            margin-bottom: 2.8rem;
            line-height: 1.75;
            max-width: 620px;
        }

        .hero-cta {
            display: flex;
            gap: 1.2rem;
            flex-wrap: wrap;
        }

        .hero-right {
            position: relative;
            perspective: 1200px;
            will-change: transform, opacity;
        }

        .model-card {
            background: var(--glass-bg);
            backdrop-filter: blur(35px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 2.2rem;
            box-shadow: var(--glass-shadow);
            transform-style: preserve-3d;
            position: relative;
            overflow: hidden;
        }

        .model-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(5, 150, 105, 0.15) 0%, transparent 60%);
            animation: pulse-glow 6s ease-in-out infinite;
        }

        @keyframes pulse-glow {

            0%,
            100% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }
        }

        .model-content {
            position: relative;
            z-index: 2;
        }

        .icon-placeholder {
            width: 100%;
            height: 200px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.15), rgba(59, 130, 246, 0.08));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .icon-placeholder i {
            font-size: 4rem;
            color: var(--primary);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
            margin-top: 9rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 3rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card h3 {
            font-size: 3.8rem;
            background: linear-gradient(135deg, #059669, #065f46);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.6rem;
        }

        .stat-card p {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .section {
            padding: 10rem 0;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 5.5rem;
        }

        .section-header h2 {
            font-size: clamp(2.6rem, 5vw, 4.2rem);
            margin-bottom: 1rem;
        }

        .section-header p {
            font-size: 1.35rem;
            color: var(--text-secondary);
            max-width: 750px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.8rem;
            perspective: 1500px;
        }

        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .feature-card:hover .glass-sweep {
            left: 200%;
            transition: left 0.8s ease-in-out;
        }

        .feature-card:hover {
            transform: translateY(-14px) scale(1.02);
            box-shadow: 0 28px 55px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .feature-icon {
            width: 85px;
            height: 85px;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.18), rgba(59, 130, 246, 0.12));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .feature-card h3 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .how-grid-container {
            position: relative;
        }

        .how-grid-svg {
            position: absolute;
            top: 20%;
            left: 0;
            width: 100%;
            height: 60%;
            z-index: 0;
            pointer-events: none;
            overflow: visible;
        }

        .how-grid-svg path {
            fill: none;
            stroke: url(#strokeGradient);
            stroke-width: 4;
            stroke-dasharray: 2000;
            stroke-dashoffset: 2000;
            stroke-linecap: round;
        }

        .how-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.8rem;
            position: relative;
            z-index: 2;
            perspective: 1500px;
        }

        .step-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .step-card:hover .glass-sweep {
            left: 200%;
            transition: left 0.8s ease-in-out;
        }

        .step-number {
            font-size: 4.2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #059669, #065f46);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .showcase {
            padding: 9rem 0;
        }

        .showcase-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            align-items: center;
        }

        .showcase-content h3 {
            font-size: 3rem;
            margin-bottom: 1.6rem;
        }

        .showcase-content p {
            color: var(--text-secondary);
            font-size: 1.25rem;
            margin-bottom: 2.2rem;
            line-height: 1.8;
        }

        .check-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .check-item {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .check-item i {
            color: var(--primary);
            font-size: 1.3rem;
            margin-top: 0.1rem;
        }

        .capabilities-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2.5rem;
            margin-top: 4.5rem;
        }

        .cap-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .cap-card:hover .glass-sweep {
            left: 200%;
            transition: left 0.8s ease-in-out;
        }

        .cap-card h4 {
            font-size: 1.4rem;
            margin-bottom: 1.2rem;
            color: var(--primary-dark);
        }

        .cap-card ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            color: var(--text-secondary);
        }

        .cap-card ul li i {
            color: var(--primary);
            margin-right: 0.7rem;
        }

        .advanced-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
            perspective: 1500px;
        }

        .cta {
            padding: 11rem 0;
            text-align: center;
        }

        .cta h2 {
            font-size: clamp(2.7rem, 5vw, 4.4rem);
            margin-bottom: 1.5rem;
        }

        .cta p {
            font-size: 1.4rem;
            color: var(--text-secondary);
            margin-bottom: 3.2rem;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        footer {
            padding: 4.5rem 0;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        footer p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Max Premium Upgrades */
        .noise-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://grainy-gradients.vercel.app/noise.svg');
            opacity: 0.05;
            pointer-events: none;
            z-index: 9999;
        }

        #splash-screen {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 20000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: var(--primary-dark);
            overflow: hidden;
            will-change: transform, opacity;
        }

        .splash-bg-decor {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
        }

        .splash-logo-container {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .splash-icon-symbol {
            width: 100px;
            margin-bottom: 2rem;
            opacity: 0;
            transform: scale(0.8);
        }

        .splash-title {
            font-family: 'Outfit', sans-serif;
            font-size: 3.8rem;
            font-weight: 900;
            letter-spacing: -2px;
            margin-bottom: 2.5rem;
            display: flex;
            gap: 2px;
            overflow: hidden;
            padding-bottom: 5px;
            color: var(--primary-dark);
        }

        .splash-char {
            display: inline-block;
            transform: translateY(105%);
        }

        .splash-progress-container {
            width: 280px;
            height: 3px;
            background: rgba(5, 150, 105, 0.1);
            position: relative;
            overflow: hidden;
            border-radius: 20px;
        }

        .splash-progress-bar {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: var(--primary);
            box-shadow: 0 0 15px rgba(5, 150, 105, 0.3);
            transform: scaleX(0);
            transform-origin: left;
        }

        .splash-percent {
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 1rem;
            margin-top: 1.2rem;
            letter-spacing: 5px;
            color: var(--primary);
            opacity: 0.8;
        }

        .glass-sweep {
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: skewX(-25deg);
            pointer-events: none;
        }

        .scroll-indicator {
            position: relative;
            margin-top: 5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.8rem;
            color: var(--text-secondary);
            font-weight: 700;
            font-size: 0.9rem;
            z-index: 10;
        }

        .scroll-mouse {
            width: 24px;
            height: 40px;
            border: 2px solid var(--text-secondary);
            border-radius: 20px;
            position: relative;
        }

        .scroll-wheel {
            width: 4px;
            height: 8px;
            background: var(--primary);
            border-radius: 2px;
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            animation: scrollWheel 2s infinite;
        }

        @keyframes scrollWheel {
            0% { transform: translate(-50%, 0); opacity: 1; }
            100% { transform: translate(-50%, 15px); opacity: 0; }
        }

        .name-blend-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 2.8rem;
        }

        .blend-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.4rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            transition: transform 0.3s ease;
        }

        .blend-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .blend-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .blend-card h4 {
            font-size: 1.25rem;
            margin-bottom: 0.8rem;
            color: var(--primary-dark);
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .blend-sub {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .blend-card ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .blend-card li {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 0.9rem;
            color: var(--text-primary);
            font-weight: 500;
            line-height: 1.3;
        }

        .blend-card li i {
            color: var(--primary);
            font-size: 1rem;
            width: 18px;
            text-align: center;
            margin-top: 0.15rem;
        }

        .holo-tag {
            position: absolute;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary-dark);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            z-index: 10;
            pointer-events: none;
        }

        .holo-tag i {
            color: var(--accent);
        }

        .tag-1 {
            top: -20px;
            left: -40px;
            animation: floatHolo 5s ease-in-out infinite;
        }

        .tag-2 {
            bottom: -20px;
            right: -20px;
            animation: floatHolo 6s ease-in-out infinite reverse;
        }

        @keyframes floatHolo {

            0%,
            100% {
                transform: translateY(0) rotate(-2deg);
            }

            50% {
                transform: translateY(-15px) rotate(2deg);
            }
        }

        .marquee-container {
            width: 100%;
            overflow: hidden;
            background: var(--primary-dark);
            color: rgba(255, 255, 255, 0.95);
            padding: 1.2rem 0;
            display: flex;
            white-space: nowrap;
            align-items: center;
            transform: skewY(-2deg) translateY(-2rem);
            box-shadow: 0 15px 40px rgba(5, 150, 105, 0.25);
            position: relative;
            z-index: 10;
            border-top: 2px solid #34d399;
            border-bottom: 2px solid #34d399;
            margin-bottom: 2rem;
        }

        .marquee-content {
            display: flex;
            gap: 3rem;
            animation: marquee 35s linear infinite;
            font-weight: 700;
            letter-spacing: 2px;
            font-size: 0.9rem;
        }

        .marquee-content i {
            color: #34d399;
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        @media (max-width:1024px) {

            .hero-grid,
            .showcase-grid,
            .capabilities-grid,
            .advanced-grid {
                grid-template-columns: 1fr;
            }

            .features-grid,
            .how-grid,
            .stats-row {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .nav-links {
                display: none;
            }
        }

        @media (max-width:640px) {

            .hero-cta,
            .cta-buttons {
                flex-direction: column;
                width: 100%;
            }

            .hero-cta .btn,
            .cta-buttons .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div id="splash-screen">
        <div class="splash-bg-decor">
            <div class="bg-orb orb-1"></div>
            <div class="bg-orb orb-2"></div>
            <div class="bg-grid"></div>
        </div>
        
        <div class="splash-logo-container">
            <img src="assets/img/logo.png" alt="NutriDeq Logo" class="splash-icon-symbol">
            <div class="splash-title">
                <span class="splash-char">N</span>
                <span class="splash-char">u</span>
                <span class="splash-char">t</span>
                <span class="splash-char">r</span>
                <span class="splash-char">i</span>
                <span class="splash-char">D</span>
                <span class="splash-char">e</span>
                <span class="splash-char">q</span>
            </div>
            <div class="splash-progress-container">
                <div class="splash-progress-bar"></div>
            </div>
            <div class="splash-percent">0%</div>
        </div>
    </div>
    <div class="noise-overlay"></div>
    <div class="scroll-progress" id="scroll-progress"></div>
    <div class="cursor-highlight" id="cursor-highlight"></div>
    <div class="cursor-dot" id="cursor-dot"></div>
    <div class="page" style="filter: blur(15px); transform: scale(0.96); opacity: 0; will-change: transform, filter, opacity;">
        <div class="bg-layer">
            <div class="bg-orb orb-1"></div>
            <div class="bg-orb orb-2"></div>
            <div class="bg-orb orb-3"></div>
            <div class="bg-grid"></div>
            <div id="particles-container" style="position:absolute; width:100%; height:100%; top:0; left:0; z-index:1;">
            </div>
        </div>
        <nav class="nav" id="nav">
            <div class="container">
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                    <div class="logo-container">
                        <img src="assets/img/logo.png" alt="NutriDeq Logo" class="logo-img">
                        <a href="#" class="logo">NutriDeq</span></a>
                    </div>
                    <ul class="nav-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#showcase">Showcase</a></li> I
                        <li><a href="#capabilities">Capabilities</a></li>
                        <li><a href="#advanced">Advanced</a></li>
                    </ul>
                    <div style="display:flex; gap:1rem;">
                        <a href="login-logout/NutriDeqN-Login.php" class="btn btn-outline">Sign In</a>
                    </div>
                </div>
            </div>
        </nav>
        <section class="hero" id="hero">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-left">
                        <div class="hero-badge"><i class="fas fa-leaf"></i> Clinical Nutrition Intelligence</div>
                        <div class="hero-text">
                            <h1 class="split-h1">
                                <span class="h1-word">Transform</span>
                                <span class="h1-word">Your</span>
                                <span class="h1-word h1-highlight">Nutrition</span>
                                <span class="h1-word h1-highlight">Clinic</span>
                                <span class="h1-word">with</span>
                                <span class="h1-word">NutriDeq</span>
                            </h1>
                            <p>From patient management to meal calculation, anthropometric tracking to secure
                                messaging—everything you need to run a world‑class nutrition clinic in one beautiful
                                platform.</p>
                        </div>
                        <div class="hero-cta">
                            <a href="login-logout/NutriDeqN-Login.php" class="btn btn-primary"><i
                                    class="fas fa-sign-in-alt"></i> Sign In</a>
                        </div>
                        <div class="name-blend-grid">
                            <div class="blend-card">
                                <div class="glass-sweep"></div>
                                <h4>NUTRI <span class="blend-sub">Nutrition</span></h4>
                                <ul>
                                    <li><i class="fas fa-apple-whole"></i> Food intake</li>
                                    <li><i class="fas fa-fire"></i> Calories & servings</li>
                                    <li><i class="fas fa-utensils"></i> Diet tracking</li>
                                </ul>
                            </div>
                            <div class="blend-card">
                                <div class="glass-sweep"></div>
                                <h4>DEQ <span class="blend-sub">Data, Equity, Quantification</span></h4>
                                <ul>
                                    <li><i class="fas fa-chart-pie"></i> Health statistics</li>
                                    <li><i class="fas fa-weight-scale"></i> BMI computation</li>
                                    <li><i class="fas fa-database"></i> Structured records</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="hero-right">
                        <div class="holo-tag tag-1"><i class="fas fa-shield-alt"></i> HIPAA Compliant</div>
                        <div class="holo-tag tag-2"><i class="fas fa-bolt"></i> Real-Time Sync</div>
                        <div class="model-card" id="model-card">
                            <div class="glass-sweep"></div>
                            <div class="model-content">
                                <div class="icon-placeholder">
                                    <i class="fas fa-stethoscope"></i>
                                </div>
                                <h3 style="font-size:1.3rem; margin-bottom:1.2rem; color:var(--primary-dark);">Live Patient Overview</h3>
                                <div style="display:grid; gap:1.5rem;">
                                    <div style="background: linear-gradient(135deg, rgba(5,150,105,0.14), rgba(59,130,246,0.08)); border-radius:20px; padding:1.5rem;">
                                        <p style="color:var(--text-secondary); font-size:0.95rem; margin-bottom:0.6rem;">Today's Progress</p>
                                        <h4 style="font-size:2.4rem;">98.7% <span style="font-size:1.1rem; color:var(--primary);">↑</span></h4>
                                    </div>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem;">
                                        <div style="background:rgba(255,255,255,0.85); border-radius:16px; padding:1.4rem;">
                                            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:0.5rem;">Protein</p>
                                            <h4 style="font-size:1.7rem;">165g</h4>
                                        </div>
                                        <div style="background:rgba(255,255,255,0.85); border-radius:16px; padding:1.4rem;">
                                            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:0.5rem;">Calories</p>
                                            <h4 style="font-size:1.7rem;">2,200</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="food-animation-container" id="food-animation">
                    <div class="food-item food-1"><i class="fas fa-leaf"></i></div>
                    <div class="food-item food-2"><i class="fas fa-apple-whole"></i></div>
                    <div class="food-item food-3"><i class="fas fa-droplet"></i></div>
                </div>
                <div class="scroll-indicator">
                    <span>EXPLORE</span>
                    <div class="scroll-mouse">
                        <div class="scroll-wheel"></div>
                    </div>
                </div>
                <div class="stats-row">
                    <div class="stat-card">
                        <h3 data-count="20" data-suffix="+">0</h3>
                        <p>Features Built</p>
                    </div>
                    <div class="stat-card">
                        <h3 data-count="10" data-suffix="+">0</h3>
                        <p>Clinical Tools</p>
                    </div>
                    <div class="stat-card">
                        <h3 data-count="5" data-suffix="+">0</h3>
                        <p>User Roles</p>
                    </div>
                </div>
            </div>
        </section>
        
        <div class="marquee-container">
            <div class="marquee-content">
                <span><i class="fas fa-check-circle"></i> CLINICALLY VERIFIED</span>
                <span><i class="fas fa-bolt"></i> ADVANCED MACRO ENGINE</span>
                <span><i class="fas fa-lock"></i> SECURE PATIENT MESSAGING</span>
                <span><i class="fas fa-chart-line"></i> DATA-DRIVEN HEALTH</span>
                <span><i class="fas fa-check-circle"></i> CLINICALLY VERIFIED</span>
                <span><i class="fas fa-bolt"></i> ADVANCED MACRO ENGINE</span>
                <span><i class="fas fa-lock"></i> SECURE PATIENT MESSAGING</span>
                <span><i class="fas fa-chart-line"></i> DATA-DRIVEN HEALTH</span>
                <span><i class="fas fa-check-circle"></i> CLINICALLY VERIFIED</span>
                <span><i class="fas fa-bolt"></i> ADVANCED MACRO ENGINE</span>
                <span><i class="fas fa-lock"></i> SECURE PATIENT MESSAGING</span>
                <span><i class="fas fa-chart-line"></i> DATA-DRIVEN HEALTH</span>
            </div>
        </div>

        <section id="features" class="section">
            <div class="container">
                <div class="section-header">
                    <h2>Everything You Need to Excel</h2>
                    <p>From macro calculation to patient messaging, we've got every detail covered for your nutrition clinic</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="glass-sweep"></div>
                        <div class="feature-icon"><i class="fas fa-calculator"></i></div>
                        <h3>Meal & Macro Calculator</h3>
                        <p>Search our verified food database and calculate perfect macros in milliseconds. No manual math ever again for your patients.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-users"></i></div>
                        <h3>Patient Management</h3>
                        <p>Complete patient records, appointment tracking, and beautiful progress visualization right at your fingertips.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-heartbeat"></i></div>
                        <h3>Anthropometric Tracking</h3>
                        <p>Track BMI, waist-to-hip ratio, body fat percentage, and more with beautiful charts and printable reports.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-comments"></i></div>
                        <h3>Secure Messaging</h3>
                        <p>End-to-end encrypted communication with patients. Always organized, always professional and compliant.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                        <h3>Appointment Scheduler</h3>
                        <p>Manage your clinic schedule, set appointments, and send automatic reminders—all in one unified place.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <h3>Analytics & Reports</h3>
                        <p>Beautiful, printable reports and real-time analytics to keep your clinic running at peak performance.</p>
                    </div>
                </div>
            </div>
        </section>
        <section id="how-it-works" class="section">
            <div class="container">
                <div class="section-header">
                    <h2>How NutriDeq Works</h2>
                    <p>Simple, 3-step process to get your clinic up and running in no time</p>
                </div>
                <div class="how-grid-container" id="how-grid-container">
                    <svg class="how-grid-svg" viewBox="0 0 1000 200" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="strokeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#059669" />
                                <stop offset="50%" stop-color="#3b82f6" />
                                <stop offset="100%" stop-color="#10b981" />
                            </linearGradient>
                        </defs>
                        <!-- A curved line bridging the 3 cards horizontally -->
                        <path id="connection-path" d="M 150,100 C 300, -50 400, 250 500, 100 C 600, -50 700, 250 850, 100"></path>
                    </svg>
                    <div class="how-grid">
                    <div class="step-card">
                        <div class="glass-sweep"></div>
                        <div class="step-number">01</div>
                        <h3>Create Your Account</h3>
                        <p>Sign up for free, set up your clinic profile, and add your staff members in just a few minutes.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">02</div>
                        <h3>Onboard Your Patients</h3>
                        <p>Add patient profiles, track anthropometric data, and start creating meal plans right away.</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">03</div>
                        <h3>Deliver Exceptional Care</h3>
                        <p>Use real-time messaging, beautiful analytics, and printable reports to provide the best possible care.</p>
                    </div>
                </div>
            </div>
        </section>
        <section id="showcase" class="section showcase">
            <div class="container">
                <div class="showcase-grid">
                    <div class="showcase-content">
                        <h3>Built for Speed & Precision</h3>
                        <p>Every second counts in a busy clinic. NutriDeq is optimized to be fast, responsive, and incredibly intuitive—so you can focus on what matters most: your patients.</p>
                        <div class="check-list">
                            <div class="check-item"><i class="fas fa-check-circle"></i><span>Calculates 100+ meals per second with verified data</span></div>
                            <div class="check-item"><i class="fas fa-check-circle"></i><span>Real-time sync across all your devices</span></div>
                            <div class="check-item"><i class="fas fa-check-circle"></i><span>Beautiful progress visualizations & charts</span></div>
                            <div class="check-item"><i class="fas fa-check-circle"></i><span>Professional, printable patient reports</span></div>
                        </div>
                    </div>
                    <div class="showcase-visual">
                        <div class="model-card" style="transform-style:preserve-3d;">
                            <div class="model-content">
                                <div class="icon-placeholder">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h3 style="font-size:1.3rem; margin-bottom:1.2rem; color:var(--primary-dark);">Meal Plan Engine</h3>
                                <div style="display:grid; gap:1.1rem;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:1.1rem; background:rgba(255,255,255,0.88); border-radius:16px;">
                                        <span style="font-weight:600;">Breakfast</span><span style="color:var(--primary); font-weight:700;">520 kcal</span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:1.1rem; background:rgba(255,255,255,0.88); border-radius:16px;">
                                        <span style="font-weight:600;">Lunch</span><span style="color:var(--primary); font-weight:700;">780 kcal</span>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:1.1rem; background:rgba(255,255,255,0.88); border-radius:16px;">
                                        <span style="font-weight:600;">Dinner</span><span style="color:var(--primary); font-weight:700;">900 kcal</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="capabilities" class="section">
            <div class="container">
                <div class="section-header">
                    <h2>Complete Clinic Capabilities</h2>
                    <p>All the tools you need to manage every aspect of your nutrition practice in one place</p>
                </div>
                <div class="capabilities-grid">
                    <div class="cap-card">
                        <div class="glass-sweep"></div>
                        <h4><i class="fas fa-user-md" style="margin-right:0.7rem; color:var(--primary);"></i>Admin & Staff Management</h4>
                        <ul>
                            <li><i class="fas fa-check"></i>Admin dashboard with full controls</li>
                            <li><i class="fas fa-check"></i>Staff management & role permissions</li>
                            <li><i class="fas fa-check"></i>Clinic settings & configuration</li>
                            <li><i class="fas fa-check"></i>User activity logs & reports</li>
                        </ul>
                    </div>
                    <div class="cap-card">
                        <h4><i class="fas fa-file-medical" style="margin-right:0.7rem; color:var(--primary);"></i>Patient Records</h4>
                        <ul>
                            <li><i class="fas fa-check"></i>Complete patient profiles</li>
                            <li><i class="fas fa-check"></i>Anthropometric data tracking</li>
                            <li><i class="fas fa-check"></i>Progress history & clinical notes</li>
                            <li><i class="fas fa-check"></i>Document storage & attachments</li>
                        </ul>
                    </div>
                    <div class="cap-card">
                        <h4><i class="fas fa-utensils" style="margin-right:0.7rem; color:var(--primary);"></i>Nutrition Tools</h4>
                        <ul>
                            <li><i class="fas fa-check"></i>Verified food database search</li>
                            <li><i class="fas fa-check"></i>Macro & calorie calculator</li>
                            <li><i class="fas fa-check"></i>Meal plan builder & templates</li>
                            <li><i class="fas fa-check"></i>Dietary restriction management</li>
                        </ul>
                    </div>
                    <div class="cap-card">
                        <h4><i class="fas fa-sync-alt" style="margin-right:0.7rem; color:var(--primary);"></i>Real-Time Sync</h4>
                        <ul>
                            <li><i class="fas fa-check"></i>Data syncs instantly across all devices</li>
                            <li><i class="fas fa-check"></i>Secure cloud backup</li>
                            <li><i class="fas fa-check"></i>Multi-device & offline support</li>
                            <li><i class="fas fa-check"></i>Data export & import</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section id="advanced" class="section">
            <div class="container">
                <div class="section-header">
                    <h2>Advanced Features</h2>
                    <p>Powerful tools to take your nutrition practice to the next level</p>
                </div>
                <div class="advanced-grid">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-database"></i></div>
                        <h3>Food Database</h3>
                        <p>Access thousands of verified foods with accurate nutrition information, including custom food entry.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-print"></i></div>
                        <h3>Printable Reports</h3>
                        <p>Generate professional, customizable patient reports that you can print or share digitally.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h3>Mobile Friendly</h3>
                        <p>Works perfectly on phones and tablets, so you can access your clinic anywhere, anytime.</p>
                    </div>
                </div>
            </div>
        </section>
        <section class="cta">
            <div class="container">
                <h2>Ready to Transform Your Clinic?</h2>
                <p>Join thousands of dietitians already using NutriDeq to deliver exceptional care</p>
                <div class="cta-buttons">
                    <a href="login-logout/NutriDeqN-Login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                </div>
            </div>
        </section>
        <footer>
            <div class="container">
                <div style="display:flex; justify-content:center; align-items:center; gap:1rem; margin-bottom:1rem;">
                    <img src="assets/img/logo.png" alt="NutriDeq Logo" class="logo-img" style="height:40px;">
                    <span style="font-size:1.5rem; font-weight:900;">Nutri<span style="color:var(--primary);">Deq</span></span>
                </div>
                <p>&copy; <?php echo date('Y'); ?> NutriDeq Intelligence. All rights reserved.</p>
            </div>
        </footer>
    </div>
    <script src="assets/js/landing-animations.js"></script>
</body>
</html>
