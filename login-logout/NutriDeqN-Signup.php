<?php
session_start();

// Use the same database configuration as login page
require_once '../database.php';

$signup_error = "";
$signup_success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>NutriDeq - Sign Up</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/mobile-style.css">
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
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header & Navigation */

        header {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            z-index: 1000;
            padding: 15px 0;
            transition: var(--transition);
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
            margin-right: 0px;
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

        /* Auth Pages Layout */

        .auth-container {
            display: flex;
            min-height: 100vh;
            padding-top: 80px;
            position: relative;
            overflow: hidden;
        }

        .auth-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.03;
        }

        .auth-bg-circle {
            position: absolute;
            border-radius: 50%;
            background: var(--gradient);
        }

        .auth-bg-circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            right: -100px;
        }

        .auth-bg-circle:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -80px;
            left: -80px;
        }

        .auth-bg-circle:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 60%;
        }

        .auth-left {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
        }

        .auth-left-content {
            max-width: 500px;
            z-index: 1;
            text-align: center;
        }

        .auth-left-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            margin-bottom: 20px;
            line-height: 1.2;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-left-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: var(--gray);
        }

        .floating-icons {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 40px;
        }

        .floating-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            animation: float 6s ease-in-out infinite;
            color: var(--primary);
            font-size: 32px;
        }

        .floating-icon:nth-child(2) {
            animation-delay: 1s;
        }

        .floating-icon:nth-child(3) {
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .auth-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .auth-form-container {
            width: 100%;
            max-width: 450px;
            animation: fadeInUp 0.8s ease;
            position: relative;
        }

        .form-decoration {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            border-radius: 25px;
        }

        .form-decoration::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(46, 139, 87, 0.05);
            top: -100px;
            right: -100px;
        }

        .form-decoration::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(74, 144, 226, 0.05);
            bottom: -80px;
            left: -80px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .auth-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .auth-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .auth-form {
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            transition: var(--transition);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
            transition: var(--transition);
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            font-size: 16px;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            background: rgba(248, 249, 250, 0.5);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
            background: white;
        }

        .form-control:focus+i {
            color: var(--primary);
        }

        .password-requirements {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
            padding-left: 10px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .auth-button {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-bottom: 20px;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }

        .auth-button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .auth-button:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }

            100% {
                transform: scale(100, 100);
                opacity: 0;
            }
        }

        .auth-redirect {
            text-align: center;
            margin-top: 25px;
        }

        .auth-redirect a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .auth-redirect a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Success and Error Messages */
        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        /* Footer */

        footer {
            background: var(--dark);
            color: white;
            padding: 40px 0 20px;
            margin-top: auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
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
            margin-top: 15px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
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
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #b0b7c3;
            font-size: 0.9rem;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 15px;
            font-weight: 500;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Animation Classes */

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Styles */

        @media (max-width: 991px) {
            .auth-container { flex-direction: column; }
            .auth-left { padding: 40px 20px 20px; }
            .auth-left-content h1 { font-size: 2.2rem; }
            .floating-icons { margin-top: 20px; gap: 15px; }
            .floating-icon { width: 50px; height: 50px; font-size: 20px; }
            .auth-right { padding: 20px; }
        }

        @media (max-width: 768px) {
            .navbar { padding: 0 5px; }
            .nav-links { display: none; }
            #landingNavToggle { display: flex !important; }

            .auth-header h2 { font-size: 2rem; }
            .auth-form { padding: 25px 20px; border-radius: 20px; }
            .form-control { padding: 12px 12px 12px 45px; font-size: 15px; }
            .auth-button { padding: 12px !important; font-size: 1rem !important; }
            
            .auth-left { display: none; } /* Focus exclusively on signup form on small screens */
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header id="header">
        <div class="container">
            <nav class="navbar">
                <a href="../index.php" class="logo">
                    <img src="../assets/img/logo.png" alt="NutriDeq" class="logo-img" style="height: 60px;"> NutriDeq
                </a>
                <ul class="nav-links">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../index.php#why-healthy">Benefits</a></li>
                    <li><a href="../index.php#calculator">Calculator</a></li>
                    <li><a href="../index.php#features">Features</a></li>
                    <li><a href="../index.php#contact">Contact</a></li>
                </ul>
                <div class="auth-buttons">
                    <a href="NutriDeqN-Login.php" class="btn btn-outline" style="padding: 6px 14px; font-size: 14px; display: none;">Sign In</a>
                    <a href="NutriDeqN-Signup.php" class="btn btn-primary" style="padding: 6px 14px; font-size: 14px;">Sign Up</a>
                </div>
                <button class="mobile-nav-toggle" id="landingNavToggle"
                    style="position: static; margin-left: 10px; display: none; width: 40px; height: 40px; justify-content: center; align-items: center; border-radius: 10px; border: 1px solid #ddd; background: #fff;">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Sign Up Content -->
    <div class="auth-container">
        <div class="auth-bg">
            <div class="auth-bg-circle"></div>
            <div class="auth-bg-circle"></div>
            <div class="auth-bg-circle"></div>
        </div>

        <div class="auth-left">
            <div class="auth-left-content">
                <h1>Start Your Health Journey Today</h1>
                <p>Join thousands of users who have transformed their lives with personalized nutrition tracking and
                    expert guidance.</p>
                <div class="floating-icons">
                    <div class="floating-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="floating-icon">
                        <i class="fas fa-apple-alt"></i>
                    </div>
                    <div class="floating-icon">
                        <i class="fas fa-running"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="auth-right">
            <div class="auth-form-container">
                <div class="form-decoration"></div>
                <div class="auth-header">
                    <h2>Welcome to NutriDeq</h2>
                    <p>Register your account</p>
                </div>

                <!-- Display error/success messages from URL parameters -->
                <?php
                if (isset($_GET['error'])) {
                    echo '<div class="alert alert-error">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                if (isset($_GET['success'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
                }
                ?>

                <!-- Form that submits to process_register.php -->
                <form class="auth-form" action="process_register.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" class="form-control" placeholder="John Doe"
                                required
                                value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" class="form-control"
                                placeholder="example@email.com" required
                                value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="••••••••" required>
                        </div>
                        <div class="password-requirements">Minimum 8+ characters with letters and numbers</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary auth-button">Create Account</button>

                    <div class="auth-redirect">
                        Already have an account? <a href="NutriDeqN-Login.php">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#why-healthy">Benefits</a></li>
                        <li><a href="index.php#calculator">Calculator</a></li>
                        <li><a href="index.php#features">Features</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
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
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('landingNavToggle');
            const navLinks = document.querySelector('.nav-links');
            const header = document.getElementById('header');
            
            if (toggle && navLinks) {
                toggle.addEventListener('click', function() {
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

        // Input focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function () {
                const label = this.parentElement.parentElement.querySelector('label');
                if (label) label.style.color = 'var(--primary)';
            });

            input.addEventListener('blur', function () {
                const label = this.parentElement.parentElement.querySelector('label');
                if (label) label.style.color = 'var(--dark)';
            });
        });
    </script>
</body>

</html>