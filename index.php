<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>NutriDeq - Health & Nutrition Tracker</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/mobile-style.css">
    <style>
        :root {
            --primary: #2E8B57;
            --primary-dark: #1e6b42;
            --primary-light: #3cb371;
            --secondary: #4A90E2;
            --accent: #FF6B6B;
            --light: #F8F9FA;
            --dark: #212529;
            --gray: #6C757D;
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header & Navigation */

        header {
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(15px);
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            z-index: 1000;
            padding: 20px 0;
            transition: var(--transition);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        header.scrolled {
            padding: 10px 0;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo i {
            margin-right: 10px;
            font-size: 32px;
        }

        .logo-img {
            height: 40px;
            width: auto;
            border-radius: 6px;

            vertical-align: middle;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--primary);
            transition: var(--transition);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        /* Hero Section */

        .hero {
            padding: 180px 0 120px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(46, 139, 87, 0.1);
            top: -300px;
            right: -200px;
            z-index: 0;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(74, 144, 226, 0.1);
            bottom: -200px;
            left: -100px;
            z-index: 0;
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }

        .hero-text {
            flex: 1;
            max-width: 600px;
        }

        .hero-image {
            flex: 1;
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
            transform: perspective(1000px) rotateY(-10deg);
            transition: var(--transition);
        }

        .hero-image img:hover {
            transform: perspective(1000px) rotateY(0deg);
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: var(--gray);
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
        }

        /* Why Be Healthy Section */

        .why-healthy {
            padding: 100px 0;
            background-color: white;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: var(--primary);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .benefit-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .benefit-card::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 5px;
            background: var(--primary);
            top: 0;
            left: 0;
            transform: scaleX(0);
            transform-origin: left;
            transition: var(--transition);
            z-index: -1;
        }

        .benefit-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .benefit-card:hover::before {
            transform: scaleX(1);
        }

        .benefit-icon {
            width: 70px;
            height: 70px;
            background: rgba(46, 139, 87, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary);
            font-size: 28px;
        }

        .benefit-card h3 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .benefit-card p {
            color: var(--gray);
        }

        /* Nutritional Calculator Section */

        .nutrition-calculator {
            padding: 100px 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .calculator-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 50px;
        }

        .calculator-content {
            flex: 1;
        }

        .calculator-visual {
            flex: 1;
            text-align: center;
        }

        .calculator-visual img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: var(--shadow-hover);
        }

        .calculator-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .calculator-content p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: var(--gray);
        }

        .goal-options {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .goal-option {
            flex: 1;
            text-align: center;
            padding: 20px 15px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .goal-option:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .goal-option.active {
            border-color: var(--primary);
            background: rgba(46, 139, 87, 0.05);
        }

        .goal-option i {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .goal-option h4 {
            font-size: 1.1rem;
            color: var(--dark);
        }

        .features-list {
            list-style: none;
            margin-top: 30px;
        }

        .features-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .features-list i {
            color: var(--primary);
            margin-right: 15px;
            font-size: 18px;
        }

        /* CTA Section */

        .cta-section {
            padding: 100px 0;
            background-color: white;
            text-align: center;
        }

        .cta-content {
            max-width: 700px;
            margin: 0 auto;
        }

        .cta-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: var(--gray);
        }

        /* Footer */

        footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            width: 40px;
            height: 3px;
            background: var(--primary);
            bottom: 0;
            left: 0;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #b0b7c3;
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #b0b7c3;
            font-size: 0.9rem;
        }

        /* Animation Classes */

        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .bounce {
            animation: bounce 2s infinite;
        }

        /* Feature Box Styles */
        .feature-box {
            width: 80%;
            height: 300px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            transition: var(--transition);
        }

        .hero-feature-box {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            margin-left: 50px;
        }

        .calc-feature-box {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--primary) 100%);
            margin-left: 50px;
            margin-bottom: 160px;
        }

        .feature-box i {
            font-size: 4rem;
            margin-right: 20px;
        }

        @media (max-width: 991px) {
            .hero h1 { font-size: 2.8rem; }
            .hero-content, .calculator-container { flex-direction: column; text-align: center; }
            .hero-text, .calculator-content { max-width: 100%; margin-bottom: 50px; }
            .hero-buttons, .goal-options { justify-content: center; }
            .hero-feature-box, .calc-feature-box { margin-left: 0; margin-top: 30px; }
        }

        @media (max-width: 768px) {
            .navbar { padding: 0 5px; }
            
            #landingNavToggle { display: flex !important; }

            .nav-links {
                position: fixed;
                top: 75px; 
                left: -100%;
                width: 100%;
                height: calc(100vh - 75px);
                background: white;
                flex-direction: column;
                align-items: center;
                padding: 40px 0;
                transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                z-index: 999;
                overflow-y: auto;
            }

            .nav-links.active { left: 0; }

            .nav-links li { margin: 20px 0; }

            .auth-buttons {
                display: flex !important;
                gap: 10px;
            }

            .btn { padding: 8px 18px; font-size: 14px; }

            .hero { padding: 140px 0 80px; }
            .hero h1 { font-size: 2.2rem; }
            .hero p { font-size: 1rem; }

            .section-title h2 { font-size: 2rem; }
            
            .feature-box {
                width: 100% !important;
                height: 180px !important;
                margin-left: 0 !important;
                margin-bottom: 20px !important;
                font-size: 1.1rem !important;
                padding: 20px !important;
                flex-direction: column !important;
                text-align: center;
                justify-content: center;
            }

            .feature-box i {
                font-size: 2.5rem !important;
                margin-right: 0 !important;
                margin-bottom: 10px !important;
            }

            .calculator-visual { height: auto !important; }
            .calculator-content h2 { font-size: 2rem; }
            .goal-options { flex-direction: column; }
            .goal-option { max-width: 100% !important; }
        }

        @media (max-width: 480px) {
            .hero h1 { font-size: 1.8rem; }
            .hero-buttons .btn, .calculator-content .btn, .cta-content .btn { width: 100%; }
            .auth-buttons .btn { width: auto; padding: 6px 12px; font-size: 13px; }
            .logo { font-size: 20px; }
            .logo i { font-size: 24px; }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        /* Desktop Optimization Constraints */
        @media (min-width: 1024px) {
            .hero-text h1, .hero-text p {
                max-width: 600px;
            }

            .calculator-container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .hero .container, 
            .why-healthy .container, 
            .nutrition-calculator .container, 
            .cta-section .container {
                padding-left: 40px;
                padding-right: 40px;
            }

            .why-healthy, 
            .nutrition-calculator, 
            .cta-section {
                padding: 120px 0;
            }

            .hero-feature-box, .calc-feature-box {
                max-height: 500px;
                height: 400px;
            }

            .navbar {
                max-width: 1200px;
                margin: 0 auto;
                width: 100%;
            }

            .goal-option {
                max-width: 250px;
            }

            .goal-options {
                justify-content: flex-start;
                gap: 30px;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header id="header">
        <div class="container">
            <nav class="navbar">
                <a href="#" class="logo">
                    <img src="assets/img/logo.png" alt="NutriDeq" class="logo-img" style="height: 60px;"> NutriDeq
                </a>
                <ul class="nav-links">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#why-healthy">Benefits</a></li>
                    <li><a href="#calculator">Calculator</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                <div class="auth-buttons">
                    <a href="login-logout/NutriDeqN-Login.php" class="btn btn-outline">Sign In</a>
                </div>
                <button class="mobile-nav-toggle" id="landingNavToggle"
                    style="position: static; margin-left: 15px; display: none;">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text fade-in">
                    <h1>Invest in yourself and boost your health, body and confidence</h1>
                    <p>Good nutrition creates health in all areas of our existence. <br>All of our parts are
                        interconnected.</p>
                    <div class="hero-buttons">
                        <a href="#calculator" class="btn btn-outline">Learn More</a>
                    </div>
                </div>
                <div class="hero-image fade-in">
                    <!-- Feature box with class instead of inline style -->
                    <div class="feature-box hero-feature-box">
                        <i class="fas fa-chart-line bounce"></i> Track Your
                        Progress
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Be Healthy Section -->
    <section class="why-healthy" id="why-healthy">
        <div class="container">
            <div class="section-title fade-in">
                <h2>Why Be Healthy?</h2>
                <p>Discover the amazing benefits of a healthy lifestyle</p>
            </div>
            <div class="benefits-grid">
                <div class="benefit-card fade-in">
                    <div class="benefit-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h3>Improved Physical Health</h3>
                    <p>Boost your energy levels, strengthen your immune system, and reduce the risk of chronic diseases.
                    </p>
                </div>
                <div class="benefit-card fade-in">
                    <div class="benefit-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Better Mental Health</h3>
                    <p>Enhance your mood, reduce anxiety, and improve cognitive function through proper nutrition.</p>
                </div>
                <div class="benefit-card fade-in">
                    <div class="benefit-icon">
                        <i class="fas fa-birthday-cake"></i>
                    </div>
                    <h3>Increased Longevity</h3>
                    <p>Add healthy years to your life by making smart nutritional choices and maintaining an active
                        lifestyle.</p>
                </div>
                <div class="benefit-card fade-in">
                    <div class="benefit-icon">
                        <i class="fas fa-weight"></i>
                    </div>
                    <h3>Weight Management</h3>
                    <p>Achieve and maintain your ideal weight with personalized nutrition plans and tracking tools.</p>
                </div>
                <div class="benefit-card fade-in">
                    <div class="benefit-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3>Improved Self-Confidence</h3>
                    <p>Feel better about yourself as you achieve your health goals and see positive changes in your
                        body.</p>
                </div>
                <div class="benefit-card fade-in">
                    <div class="benefit-icon">
                        <i class="fas fa-wind"></i>
                    </div>
                    <h3>Reduced Stress</h3>
                    <p>Proper nutrition helps regulate stress hormones and improves your ability to cope with daily
                        challenges.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Nutritional Calculator Section -->
    <section class="nutrition-calculator" id="calculator">
        <div class="container">
            <div class="calculator-container">
                <div class="calculator-content fade-in">
                    <h2>Nutritional Calculator</h2>
                    <p>Build healthier habits with personalized lessons tailored to your specific goals and lifestyle.
                    </p>

                    <div class="goal-options">
                        <div class="goal-option active">
                            <i class="fas fa-arrow-down"></i>
                            <h4>Losing Weight</h4>
                        </div>
                        <div class="goal-option">
                            <i class="fas fa-arrow-up"></i>
                            <h4>Gaining Weight</h4>
                        </div>
                        <div class="goal-option">
                            <i class="fas fa-balance-scale"></i>
                            <h4>Maintaining Weight</h4>
                        </div>
                    </div>

                    <h3>What do you need to get healthy?</h3>
                    <ul class="features-list">
                        <li><i class="fas fa-check-circle"></i> Diet tracker with detailed analytics</li>
                        <li><i class="fas fa-check-circle"></i> Best nutrition advice from experts</li>
                        <li><i class="fas fa-check-circle"></i> Personalized meal planner</li>
                        <li><i class="fas fa-check-circle"></i> Specific nutrition information for foods</li>
                        <li><i class="fas fa-check-circle"></i> Comprehensive statistic tracker</li>
                    </ul>

                    <a href="login-logout/NutriDeqN-Signup.php" class="btn btn-primary" style="margin-top: 20px;">Get
                        Started Today</a>
                </div>
                <div class="calculator-visual fade-in">
                    <!-- Feature box with class instead of inline style -->
                    <div class="feature-box calc-feature-box">
                        <i class="fas fa-calculator bounce"></i> Calculate
                        Your Nutrition
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="features">
        <div class="container">
            <div class="cta-content fade-in">
                <h2>Improve Your Lifestyle and Diet</h2>
                <p>Track your nutrition, know what and how much you need for your daily intake!</p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="login-logout/NutriDeqN-Signup.php" class="btn btn-primary">Start your health journey
                        today</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>NutriDeq</h3>
                    <p>Your personal health and nutrition companion. We help you achieve your wellness goals through
                        personalized tracking and guidance.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#why-healthy">Benefits</a></li>
                        <li><a href="#calculator">Calculator</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Features</h3>
                    <ul class="footer-links">
                        <li><a href="#">Diet Tracker</a></li>
                        <li><a href="#">Meal Planner</a></li>
                        <li><a href="#">Nutrition Advice</a></li>
                        <li><a href="#">Progress Analytics</a></li>
                        <li><a href="#">Recipe Database</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> 123 Health Street, Wellness City</li>
                        <li><i class="fas fa-phone"></i> +1 (555) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@nutrideq.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2023 NutriDeq. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Landing Page Mobile Menu Logic
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('landingNavToggle');
            const navLinks = document.querySelector('.nav-links');
            const header = document.getElementById('header');

            if (toggle && navLinks) {
                toggle.addEventListener('click', function () {
                    navLinks.classList.toggle('active');
                    const icon = toggle.querySelector('i');
                    if (navLinks.classList.contains('active')) {
                        icon.className = 'fas fa-times';
                    } else {
                        icon.className = 'fas fa-bars';
                    }
                });

                // Close menu when link clicked
                navLinks.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        navLinks.classList.remove('active');
                        toggle.querySelector('i').className = 'fas fa-bars';
                    });
                });
            }
        });

        // Header scroll effect
        window.addEventListener('scroll', function () {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Fade in animation on scroll
        const fadeElements = document.querySelectorAll('.fade-in');

        const fadeInOnScroll = function () {
            fadeElements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;

                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('visible');
                }
            });
        };

        window.addEventListener('scroll', fadeInOnScroll);
        // Initial check in case elements are already in view
        fadeInOnScroll();

        // Goal option selection
        const goalOptions = document.querySelectorAll('.goal-option');

        goalOptions.forEach(option => {
            option.addEventListener('click', function () {
                goalOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>

</html>