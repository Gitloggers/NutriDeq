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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    <title>NutriDeq - Next-Gen Clinical Intelligence</title>

    <link rel="stylesheet" href="css/base.css?v=205">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* =========================================================
           NUTRI-GLASS : ULTRA-DYNAMIC (WOW FACTOR)
           ========================================================= */
        :root {
            --text-dark: #0f172a;
            --text-muted: #475569;
            --primary: #10b981;
            /* Vibrant Emerald */
            --primary-dark: #047857;
            --accent: #f59e0b;
            /* Amber */
            --accent-alt: #3b82f6;
            /* Blue for contrast */
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-border: rgba(255, 255, 255, 0.6);
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: var(--text-dark);
            overflow-x: hidden;
            scroll-behavior: smooth;
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        .logo-text {
            font-family: 'Outfit', sans-serif;
        }

        /* --- Mega Gradient & Interactivity Base --- */
        .page-wrapper {
            position: relative;
            z-index: 1;
        }

        /* Mouse Spotlight Follower */
        .spotlight {
            position: fixed;
            top: 0;
            left: 0;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 60%);
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
            transform: translate(-50%, -50%);
            transition: opacity 0.5s ease;
        }

        /* Abstract Fluid Gradient Mesh Background */
        .gradient-mesh {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -2;
            background: #f8fafc;
        }

        .mesh-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(140px);
            opacity: 0.8;
            animation: moveBlobs 20s infinite alternate ease-in-out;
        }

        .mesh-1 {
            width: 50vw;
            height: 50vw;
            background: rgba(16, 185, 129, 0.2);
            top: -20%;
            left: -10%;
        }

        .mesh-2 {
            width: 40vw;
            height: 40vw;
            background: rgba(59, 130, 246, 0.15);
            bottom: -10%;
            right: -10%;
            animation-delay: -5s;
        }

        .mesh-3 {
            width: 45vw;
            height: 45vw;
            background: rgba(245, 158, 11, 0.1);
            top: 30%;
            left: 40%;
            animation-delay: -10s;
        }

        @keyframes moveBlobs {
            0% {
                transform: scale(1) translate(0, 0) rotate(0deg);
            }

            50% {
                transform: scale(1.2) translate(10%, 10%) rotate(45deg);
            }

            100% {
                transform: scale(0.8) translate(-10%, -10%) rotate(90deg);
            }
        }

        /* 2D Floating Molecular Objects */
        .floating-object {
            position: absolute;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--primary);
            z-index: 0;
            pointer-events: none;
        }

        /* JS Parallax Targets */
        .obj-1 {
            width: 80px;
            height: 80px;
            top: 15%;
            left: 10%;
            font-size: 24px;
            animation: floatObj 8s infinite alternate;
        }

        .obj-2 {
            width: 40px;
            height: 40px;
            top: 40%;
            right: 15%;
            animation: floatObj 6s infinite alternate-reverse;
            color: var(--accent-alt);
        }

        .obj-3 {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation: floatObj 7s infinite alternate;
            color: var(--accent);
        }

        .obj-4 {
            width: 50px;
            height: 50px;
            top: 60%;
            right: 5%;
            animation: floatObj 9s infinite alternate-reverse;
        }

        @keyframes floatObj {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-30px);
            }
        }

        .container {
            width: 100%;
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
            z-index: 10;
        }

        /* Header Engine */
        header {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 95%;
            max-width: 1300px;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.4s ease;
        }

        header.scrolled {
            top: 10px;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .logo img {
            height: 38px;
            margin-right: 12px;
            border-radius: 8px;
            z-index: 2;
            transition: transform 0.5s;
        }

        .logo:hover img {
            transform: rotate(15deg) scale(1.1);
        }

        .logo-text {
            font-size: 26px;
            font-weight: 900;
            color: var(--text-dark);
            z-index: 2;
        }

        .logo-text span {
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 32px;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 15px;
            position: relative;
            padding: 5px 0;
        }

        /* Hover line animation */
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 100px;
            padding: 12px 28px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            transition: 0.4s;
            cursor: pointer;
            border: none;
            letter-spacing: 0.3px;
        }

        /* Magnetic / Animated Buttons */
        .btn-glass {
            background: rgba(255, 255, 255, 0.5);
            color: var(--text-dark);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .btn-glass:hover {
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .btn-glow {
            background: var(--primary);
            color: white;
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: 0.5s;
            z-index: -1;
        }

        .btn-glow:hover {
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
            transform: translateY(-3px) scale(1.02);
        }

        .btn-glow:hover::before {
            left: 100%;
        }

        /* ================= HERO SECTION ================= */
        .hero {
            padding: 220px 0 120px;
            min-height: 90vh;
            display: flex;
            align-items: center;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-alt);
            padding: 8px 16px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 24px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            animation: fadeUp 1s ease forwards;
        }

        .hero-text h1 {
            font-size: 5rem;
            line-height: 1.05;
            margin-bottom: 24px;
            letter-spacing: -2px;
            text-wrap: balance;
            animation: fadeUp 1s ease forwards 0.1s;
            opacity: 0;
            transform: translateY(30px);
        }

        .hero-text h1 span {
            background: linear-gradient(to right, var(--primary), var(--accent-alt));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 40px;
            max-width: 500px;
            animation: fadeUp 1s ease forwards 0.2s;
            opacity: 0;
            transform: translateY(30px);
        }

        .hero-cta {
            display: flex;
            gap: 16px;
            animation: fadeUp 1s ease forwards 0.3s;
            opacity: 0;
            transform: translateY(30px);
        }

        /* 3D Tilt Mockup */
        .mockup-wrapper {
            perspective: 1500px;
            position: relative;
            animation: floatGently 6s infinite ease-in-out;
        }

        .mockup-card {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 1);
            border-radius: 36px;
            padding: 4px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.1), inset 0 0 0 2px rgba(255, 255, 255, 0.5);
            transform-style: preserve-3d;
            transition: transform 0.1s ease;
        }

        .mockup-inner {
            background: white;
            border-radius: 32px;
            padding: 30px;
            transform: translateZ(30px);
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        }

        .mock-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .mh-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mh-ava {
            width: 50px;
            height: 50px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 20px;
            box-shadow: inset 0 0 0 1px #e2e8f0;
        }

        .mh-line1 {
            width: 120px;
            height: 14px;
            background: #cbd5e1;
            border-radius: 7px;
            margin-bottom: 8px;
        }

        .mh-line2 {
            width: 80px;
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
        }

        .mock-chart {
            height: 180px;
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%);
            border-radius: 20px;
            margin-bottom: 24px;
            position: relative;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            padding: 0 16px;
        }

        .c-bar {
            flex: 1;
            background: var(--primary);
            border-radius: 8px 8px 0 0;
            opacity: 0.1;
            transition: 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            transform-origin: bottom;
            transform: scaleY(0.1);
        }

        .mockup-wrapper:hover .c-bar {
            opacity: 0.9;
        }

        .mockup-wrapper:hover .c-bar:nth-child(1) {
            transform: scaleY(0.4);
        }

        .mockup-wrapper:hover .c-bar:nth-child(2) {
            transform: scaleY(0.7);
        }

        .mockup-wrapper:hover .c-bar:nth-child(3) {
            transform: scaleY(0.5);
        }

        .mockup-wrapper:hover .c-bar:nth-child(4) {
            transform: scaleY(1.0);
            background: var(--accent-alt);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }

        .mockup-wrapper:hover .c-bar:nth-child(5) {
            transform: scaleY(0.6);
        }

        .data-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .d-card {
            background: #f8fafc;
            padding: 16px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .d-title {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .d-val {
            font-size: 24px;
            font-weight: 900;
            font-family: 'Outfit';
            color: var(--text-dark);
        }

        /* Floating interaction widgets */
        .float-badge {
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 100px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #ffffff;
            z-index: 10;
            font-size: 14px;
            animation: floatBadge 4s infinite alternate ease-in-out;
        }

        .fb-1 {
            top: 40px;
            right: -40px;
            transform: translateZ(60px);
        }

        .fb-2 {
            bottom: 60px;
            left: -50px;
            transform: translateZ(80px);
            animation-delay: -2s;
        }

        .fb-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
        }

        @keyframes floatBadge {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-15px);
            }
        }

        /* ================= FEATURES SECTION (REACTABLE) ================= */
        .section {
            padding: 140px 0;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .section-header h2 {
            font-size: 3.5rem;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }

        .section-header p {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 32px;
            padding: 40px;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        /* Mouse Reactivity via CSS Hover Layer */
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 1), rgba(255, 255, 255, 0));
            opacity: 0;
            transition: 0.5s;
            z-index: -1;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .f-icon-box {
            width: 72px;
            height: 72px;
            background: #ffffff;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: 0.5s;
            transform-origin: center;
        }

        .feature-card:hover .f-icon-box {
            background: var(--primary);
            color: white;
            border-radius: 40px;
            transform: rotate(360deg);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.3);
        }

        .feature-card h3 {
            font-size: 1.8rem;
            margin-bottom: 16px;
            font-weight: 800;
        }

        .feature-card p {
            font-size: 1.1rem;
            line-height: 1.6;
        }

        /* ================= DATABASE INFO & BENEFITS (Information Replaced) ================= */
        .data-showcase {
            background: var(--text-dark);
            border-radius: 40px;
            padding: 80px;
            position: relative;
            overflow: hidden;
            text-align: center;
            color: white;
            box-shadow: 0 40px 100px rgba(15, 23, 42, 0.3);
            margin: 40px 0;
        }

        .data-showcase::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 60%);
            pointer-events: none;
            animation: spinBg 20s linear infinite;
        }

        @keyframes spinBg {
            100% {
                transform: rotate(360deg);
            }
        }

        .ds-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 40px;
            margin-top: 60px;
            position: relative;
            z-index: 2;
        }

        .ds-stat h4 {
            font-size: 4rem;
            margin: 0 0 8px 0;
            color: white;
        }

        .ds-stat h4 span {
            color: var(--primary);
        }

        .ds-stat p {
            color: #94a3b8;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* ================= FOOTER ================= */
        footer {
            margin-top: 100px;
            padding: 80px 0 40px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.9);
        }

        .f-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 60px;
            margin-bottom: 60px;
        }

        .f-brand {
            display: flex;
            flex-direction: column;
        }

        .f-logo {
            font-size: 32px;
            font-weight: 900;
            font-family: 'Outfit';
            color: var(--text-dark);
            text-decoration: none;
            margin-bottom: 20px;
        }

        .f-logo span {
            color: var(--primary);
        }

        .f-col h4 {
            font-size: 1.2rem;
            margin-bottom: 24px;
        }

        .f-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .f-col li {
            margin-bottom: 16px;
        }

        .f-col a {
            color: var(--text-muted);
            text-decoration: none;
            transition: 0.3s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .f-col a:hover {
            color: var(--primary);
            gap: 12px;
        }

        /* Scroll Reveals */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 60px;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 4rem;
                margin: 0 auto 24px;
            }

            .hero-text p {
                margin: 0 auto 40px;
            }

            .hero-cta {
                justify-content: center;
            }

            .feature-grid,
            .ds-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .f-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            header {
                padding: 12px 20px;
            }

            .nav-links {
                display: none;
            }

            .hero-text h1 {
                font-size: 3rem;
            }

            .spotlight,
            .floating-object {
                display: none;
            }

            .data-showcase {
                padding: 60px 24px;
            }

            .ds-stat h4 {
                font-size: 3rem;
            }
        }
    </style>
</head>

<body>
    <!-- PWA Preamble Intro Splash -->
    <div id="pwa-intro-splash" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(248, 250, 252, 0.85); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); z-index: 999999; display: flex; align-items: center; justify-content: center; transition: opacity 0.8s ease, visibility 0.8s ease;">
        <div style="text-align: center; animation: introPulse 2s ease-in-out infinite;">
            <img src="assets/img/logo.png" alt="NutriDeq Start" style="width: 120px; height: 120px; border-radius: 28px; box-shadow: 0 20px 50px rgba(16, 185, 129, 0.2);">
            <div style="margin-top: 20px; font-family: 'Outfit', sans-serif; font-size: 32px; font-weight: 900; color: #0f172a; letter-spacing: -1px;">Nutri<span style="color: #10b981;">Deq</span></div>
            <div style="font-family: 'Inter', sans-serif; color: #64748b; font-size: 14px; margin-top: 5px; font-weight: 500;">Initializing Clinical Environment...</div>
        </div>
    </div>
    <style>
        @keyframes introPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>

    <!-- Ultra Dynamic Background Wrapper -->
    <div class="page-wrapper">
        <div class="gradient-mesh">
            <div class="mesh-blob mesh-1"></div>
            <div class="mesh-blob mesh-2"></div>
            <div class="mesh-blob mesh-3"></div>
        </div>

        <div class="spotlight" id="spotlight"></div>

        <!-- Floating Objects for Parallax -->
        <div class="floating-object obj-1 data-parallax" data-speed="-1"><i class="fas fa-apple-alt"></i></div>
        <div class="floating-object obj-2 data-parallax" data-speed="2"><i class="fas fa-dna"></i></div>
        <div class="floating-object obj-3 data-parallax" data-speed="-1.5"><i class="fas fa-heartbeat"></i></div>
        <div class="floating-object obj-4 data-parallax" data-speed="1.2"><i class="fas fa-seedling"></i></div>

        <!-- Header -->
        <header id="header">
            <a href="#" class="logo">
                <img src="assets/img/logo.png" alt="NutriDeq Logo">
                <div class="logo-text">Nutri<span>Deq</span></div>
            </a>
            <ul class="nav-links">
                <li><a href="#features">Capabilities</a></li>
                <li><a href="#database">Database Integrity</a></li>
            </ul>
            <div class="auth-buttons" style="display: flex; gap: 10px; align-items: center;">
                <button id="pwa-install-btn" class="btn btn-glow" style="display: none; font-size: 0.9rem; padding: 10px 18px;"><i class="fas fa-download"></i> Install App</button>
                <a href="login-logout/NutriDeqN-Login.php" class="btn btn-glass">Sign in</a>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-text">
                    <div class="badge"><i class="fas fa-bolt"></i> Fast, Smart & Free</div>
                    <h1>Smarter Nutrition <span>Made Simple</span></h1>
                    <p>The easiest way to track patient progress, calculate meals, and manage your health clinic. Built
                        for dietitians who want to save time and get results.</p>
                    <div class="hero-cta">
                        <a href="login-logout/NutriDeqN-Signup.php" class="btn btn-glow"><i
                                class="fas fa-user-plus"></i> Get Started</a>
                        <a href="#features" class="btn btn-glass"><i class="fas fa-star"></i> See Features</a>
                    </div>
                </div>

                <!-- 3D Interactive Reactable Mockup -->
                <div class="mockup-wrapper" id="tilting-mockup">

                    <div class="float-badge fb-1">
                        <div class="fb-icon" style="background:#f59e0b;"><i class="fas fa-fire"></i></div>
                        Macro Sync Active
                    </div>
                    <div class="float-badge fb-2">
                        <div class="fb-icon" style="background:var(--primary);"><i class="fas fa-check"></i></div>
                        Diet Plan Generated
                    </div>

                    <div class="mockup-card">
                        <div class="mockup-inner">
                            <div class="mock-head">
                                <div class="mh-group">
                                    <div class="mh-ava"><i class="fas fa-user-circle"></i></div>
                                    <div>
                                        <div class="mh-line1"></div>
                                        <div class="mh-line2"></div>
                                    </div>
                                </div>
                                <div
                                    style="background: rgba(16,185,129,0.1); color: var(--primary); padding: 4px 12px; border-radius: 100px; font-size: 13px; font-weight:700;">
                                    Live Feed</div>
                            </div>

                            <div style="font-family:'Outfit'; font-size: 20px; font-weight: 800; margin-bottom:16px;">
                                Formulation Trajectory</div>

                            <div class="mock-chart">
                                <div class="c-bar"></div>
                                <div class="c-bar"></div>
                                <div class="c-bar"></div>
                                <div class="c-bar"></div>
                                <div class="c-bar"></div>
                            </div>

                            <div class="data-cards">
                                <div class="d-card">
                                    <div class="d-title">Caloric Threshold</div>
                                    <div class="d-val">2,150 <span style="font-size:14px;color:#94a3b8;">kcal</span>
                                    </div>
                                </div>
                                <div class="d-card">
                                    <div class="d-title">Protein Target</div>
                                    <div class="d-val">160 <span style="font-size:14px;color:#94a3b8;">g</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Matrix -->
        <section class="section" id="features">
            <div class="container">
                <div class="section-header reveal">
                    <h2>Powerful Tools <span>for Better Health</span></h2>
                    <p>Everything you need to manage your patients, automatically calculate meals, and track progress
                        without the headache.</p>
                </div>

                <div class="feature-grid">
                    <div class="feature-card reveal" style="transition-delay: 0.1s;">
                        <div class="f-icon-box"><i class="fas fa-calculator"></i></div>
                        <h3>Auto-Calculate Macros</h3>
                        <p>No more manual math. Just search our huge food database, and we instantly calculate calories,
                            protein, carbs, and fats for your patient's meal plan.</p>
                    </div>
                    <div class="feature-card reveal" style="transition-delay: 0.2s;">
                        <div class="f-icon-box"><i class="fas fa-users"></i></div>
                        <h3>Simple Client Management</h3>
                        <p>Keep all your patients organized in one place. Track their weight loss, measurements, and
                            daily habits with easy-to-read charts.</p>
                    </div>
                    <div class="feature-card reveal" style="transition-delay: 0.3s;">
                        <div class="f-icon-box"><i class="fas fa-comments"></i></div>
                        <h3>Direct Patient Messaging</h3>
                        <p>Stay connected! Patients can securely message you directly through your dashboard, keeping
                            them engaged and on track with their goals.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Data Infograph -->
        <section class="section" style="padding-top:0;" id="database">
            <div class="container">
                <div class="data-showcase reveal">
                    <h2 style="font-size: 3.5rem; margin-bottom: 24px; position:relative; z-index:2;">Massive Food
                        Database</h2>
                    <p
                        style="font-size: 1.25rem; max-width: 700px; margin: 0 auto; color: #cbd5e1; position:relative; z-index:2; line-height: 1.8;">
                        NutriDeq connects to verified, official nutrition databases so you can instantly search for
                        thousands of real foods and get perfectly accurate nutritional facts without guessing.</p>

                    <div class="ds-grid">
                        <div class="ds-stat">
                            <h4>0<span>ms</span></h4>
                            <p>Calculation Latency</p>
                        </div>
                        <div class="ds-stat">
                            <h4>100<span>%</span></h4>
                            <p>Free for Clinics</p>
                        </div>
                        <div class="ds-stat">
                            <h4>24<span>/7</span></h4>
                            <p>Real-time Access</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container f-grid reveal">
                <div class="f-brand">
                    <a href="#" class="f-logo">Nutri<span>Deq</span></a>
                    <p style="color: var(--text-muted); line-height: 1.7; max-width: 300px;">The pinnacle of clinical
                        dietary formulation. Smooth. Accurate. Free.</p>
                </div>
                <div class="f-col">
                    <h4>Operations</h4>
                    <ul>
                        <li><a href="login-logout/NutriDeqN-Signup.php">Sign Up Free <i
                                    class="fas fa-arrow-right"></i></a></li>
                        <li><a href="login-logout/NutriDeqN-Login.php">Log In <i class="fas fa-arrow-right"></i></a>
                        </li>
                    </ul>
                </div>
                <div class="f-col">
                    <h4>Information</h4>
                    <ul>
                        <li><a href="#">Privacy Protocol <i class="fas fa-arrow-right"></i></a></li>
                        <li><a href="#">Security Integrity <i class="fas fa-arrow-right"></i></a></li>
                    </ul>
                </div>
            </div>
            <div
                style="text-align:center; padding: 24px 0; border-top: 1px solid rgba(0,0,0,0.05); color: #94a3b8; font-size: 0.9rem;">
                &copy; <?php echo date('Y'); ?> NutriDeq Intelligence. All rights reserved.
            </div>
        </footer>

    </div>

    <!-- JS for WOW Factor interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Header scroll
            const header = document.getElementById('header');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) header.classList.add('scrolled');
                else header.classList.remove('scrolled');
            });

            // Intersection Observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('active'); });
            }, { threshold: 0.1 });
            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

            // Mouse interactives
            const spotlight = document.getElementById('spotlight');
            const mockup = document.getElementById('tilting-mockup');
            const parallaxObjs = document.querySelectorAll('.data-parallax');

            let mouseX = window.innerWidth / 2;
            let mouseY = window.innerHeight / 2;

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;

                // Spotlight follow
                requestAnimationFrame(() => {
                    spotlight.style.left = mouseX + 'px';
                    spotlight.style.top = mouseY + 'px';

                    // Center calculations for tilt and parallax
                    let xOffset = (mouseX - window.innerWidth / 2) / window.innerWidth;
                    let yOffset = (mouseY - window.innerHeight / 2) / window.innerHeight;

                    // 3D Tilt Mockup Card
                    if (mockup) {
                        mockup.style.transform = `rotateY(${-12 + xOffset * 15}deg) rotateX(${8 + -yOffset * 15}deg)`;
                    }

                    // 2D Floating Objects Mouse Parallax
                    parallaxObjs.forEach(obj => {
                        let speed = parseFloat(obj.getAttribute('data-speed'));
                        obj.style.transform = `translate(${xOffset * 100 * speed}px, ${yOffset * 100 * speed}px)`;
                    });
                });
            });
        });

        // PWA Splash Screen Logic
        window.addEventListener('load', () => {
            const splash = document.getElementById('pwa-intro-splash');
            if (splash) {
                // If it's the very first visit this session, show the animation longer
                const isFirstVisit = !sessionStorage.getItem('nutrideq_splash_shown');
                const delay = isFirstVisit ? 1500 : 300;
                
                setTimeout(() => {
                    splash.style.opacity = '0';
                    splash.style.visibility = 'hidden';
                    sessionStorage.setItem('nutrideq_splash_shown', 'true');
                }, delay);
            }
        });

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('service-worker.js?v=3')
                    .then(reg => console.log('NutriDeq PWA Engine Registered', reg.scope))
                    .catch(err => console.error('PWA Engine failure:', err));
            });
        }

        // PWA Install Prompt Logic
        let deferredPrompt;
        const installBtn = document.getElementById('pwa-install-btn');
        
        // Ensure the button is visible by default so it's always accessible
        if (installBtn) {
            installBtn.style.display = 'block';
            
            // Handle clicks
            installBtn.addEventListener('click', () => {
                if (deferredPrompt) {
                    // Show the native prompt
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the A2HS prompt');
                            installBtn.style.display = 'none';
                        }
                        deferredPrompt = null;
                    });
                } else {
                    // Fallback for when the native prompt isn't available (iOS, already installed, etc)
                    alert("To install NutriDeq: tap the Share icon (iOS) or browser menu (Android/Desktop), then select 'Add to Home Screen'.");
                }
            });
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e; // Stash it for when they click the button
        });

        window.addEventListener('appinstalled', (evt) => {
            console.log('INSTALL: Success');
            if (installBtn) {
                installBtn.style.display = 'none';
            }
        });
    </script>
</body>

</html>