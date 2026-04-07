<?php
session_start();
require_once '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handled natively by process_register.php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="../manifest.json">
    <title>NutriDeq - Create Portal</title>
    <link rel="stylesheet" href="../css/base.css?v=205">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* =========================================================
           NUTRI-GLASS : ULTRA-DYNAMIC AUTH (WOW FACTOR)
           ========================================================= */
        :root {
            --text-dark: #0f172a;
            --text-muted: #475569;
            --primary: #10b981; 
            --primary-dark: #047857;
            --accent: #f59e0b; 
            --accent-alt: #3b82f6; 
            --glass-border: rgba(255, 255, 255, 0.8);
        }

        body {
            margin: 0; padding: 0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            overflow: hidden; 
        }

        /* --- Mega Gradient & Interactivity Base --- */
        .page-wrapper {
            position: absolute; top:0; left:0; width:100%; height:100%;
            z-index: 1; display:flex; justify-content:center; align-items:center;
        }

        /* Mouse Spotlight Follower */
        .spotlight {
            position: fixed;
            top: 0; left: 0; width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
            border-radius: 50%; pointer-events: none; z-index: -1;
            transform: translate(-50%, -50%); transition: opacity 0.5s ease;
        }

        /* Abstract Fluid Gradient Mesh Background */
        .gradient-mesh { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -2; background: #f8fafc; }
        .mesh-blob { position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.7; animation: moveBlobs 20s infinite alternate ease-in-out; }
        .mesh-1 { width: 50vw; height: 50vw; background: rgba(16, 185, 129, 0.15); top: -10%; left: -10%; }
        .mesh-2 { width: 40vw; height: 40vw; background: rgba(59, 130, 246, 0.1); bottom: -10%; right: -10%; animation-delay: -5s; }
        .mesh-3 { width: 45vw; height: 45vw; background: rgba(245, 158, 11, 0.1); top: 30%; left: 40%; animation-delay: -10s; }

        @keyframes moveBlobs {
            0% { transform: scale(1) translate(0,0) rotate(0deg); }
            50% { transform: scale(1.1) translate(10%, 10%) rotate(45deg); }
            100% { transform: scale(0.9) translate(-10%, -10%) rotate(90deg); }
        }

        /* 2D Floating Molecular Objects */
        .floating-object {
            position: absolute; background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: var(--primary); z-index: 0; pointer-events: none;
        }
        .obj-1 { width: 80px; height: 80px; top: 20%; left: 20%; animation: floatObj 8s infinite alternate; }
        .obj-2 { width: 60px; height: 60px; bottom: 20%; right: 20%; animation: floatObj 6s infinite alternate-reverse; color: var(--accent-alt);}

        @keyframes floatObj { 0% { transform: translateY(0); } 100% { transform: translateY(-30px); } }

        /* Perspective Wrapper for 3D Card Tilt */
        .tilt-wrapper { perspective: 1200px; z-index: 10; padding: 20px;}

        /* Central Glass Card */
        .auth-card {
            width: 100vw; max-width: 480px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,1); border-radius: 36px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.1), inset 0 0 0 2px rgba(255,255,255,0.8);
            transform-style: preserve-3d; transition: transform 0.1s ease;
            position: relative; 
            max-height: 90vh; display: flex; flex-direction: column;
        }

        .auth-card-inner {
            padding: 50px 40px; overflow-y: auto; overflow-x: hidden;
            transform: translateZ(40px); /* Pushes content out in 3D space */
        }
        .auth-card-inner::-webkit-scrollbar { width: 4px; }
        .auth-card-inner::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        /* Animating load-in */
        .tilt-wrapper { animation: popIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(30px) scale(0.95); }
        @keyframes popIn { to { transform: translateY(0) scale(1); opacity: 1; } }

        /* Logo & Header */
        .auth-header { text-align: center; margin-bottom: 30px; }
        .logo-link { display: inline-flex; align-items: center; justify-content: center; gap: 12px; text-decoration: none; margin-bottom: 24px; position: relative; }
        .logo-link img { height: 40px; border-radius: 10px; transition: 0.5s; }
        .logo-link:hover img { transform: rotate(15deg) scale(1.1); }
        .logo-text { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 900; color: var(--text-dark); }
        .logo-text span { color: var(--primary); }

        .auth-header h1 { font-family: 'Outfit'; font-size: 2.2rem; color: var(--text-dark); margin: 0 0 8px 0; letter-spacing: -1px; }
        .auth-header p { color: var(--text-muted); margin: 0; font-size: 1rem; line-height: 1.6;}

        /* Form styling */
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px; }
        
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 16px; color: #94a3b8; font-size: 16px; transition: 0.3s; pointer-events: none; }
        
        .form-control {
            width: 100%; padding: 12px 16px 12px 44px;
            background: rgba(255,255,255,0.8); border: 2px solid #e2e8f0; border-radius: 14px;
            font-size: 15px; font-family: 'Inter'; color: var(--text-dark);
            transition: all 0.3s ease; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .form-control::placeholder { color: #94a3b8; font-weight: 400; }
        .form-control:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 4px 15px rgba(16,185,129,0.1); transform: translateY(-2px); }
        .form-control:focus + .input-icon { color: var(--primary); transform: scale(1.1); }

        /* Submit Button Glow Effect */
        .btn-submit {
            width: 100%; padding: 14px; font-size: 16px; font-weight: 700;
            background: var(--primary); color: white; border: none; border-radius: 14px;
            cursor: pointer; transition: all 0.4s ease; box-shadow: 0 10px 25px rgba(16,185,129,0.3);
            position: relative; overflow: hidden; margin-top: 10px;
        }
        .btn-submit::after { content: ''; position: absolute; top:0; left:-100%; width: 100%; height:100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); transition: 0.5s; }
        .btn-submit:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 20px 40px rgba(16, 185, 129, 0.4); }
        .btn-submit:active { transform: translateY(-1px); }

        /* Google Login Button */
        .google-auth-separator {
            display: flex; align-items: center; text-align: center;
            margin: 25px 0; color: var(--text-muted); font-size: 0.85rem;
            font-weight: 500;
        }
        .google-auth-separator::before, .google-auth-separator::after {
            content: ''; flex: 1; border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .google-auth-separator:not(:empty)::before { margin-right: 15px; }
        .google-auth-separator:not(:empty)::after { margin-left: 15px; }

        .btn-google {
            width: 100%; padding: 14px; background: white; color: var(--text-dark);
            border: 1px solid #e2e8f0; border-radius: 20px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 12px;
            cursor: pointer; transition: all 0.3s var(--bounce);
            text-decoration: none; font-size: 0.95rem;
        }
        .btn-google img { width: 20px; height: 20px; }
        .btn-google:hover {
            background: #f8fafc; border-color: #cbd5e1;
            transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .auth-footer { margin-top: 30px; text-align: center; color: #64748b; font-size: 0.95rem; }
        .auth-footer a { color: var(--primary); font-weight: 800; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }

        .error-message { background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; animation: shake 0.5s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);}
        .success-message { background: #F0FDF4; border: 1px solid #BBF7D0; color: #15803D; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(21, 128, 61, 0.1);}
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        /* Form Stagger Reveals */
        .stagger { opacity: 0; transform: translateY(15px) translateZ(40px); animation: fadeUp 0.5s ease forwards; }
        .d-1 { animation-delay: 0.2s; } .d-2 { animation-delay: 0.3s; } .d-3 { animation-delay: 0.4s; } .d-4 { animation-delay: 0.5s; } .d-5 { animation-delay: 0.6s; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0) translateZ(40px); } }

        @media (max-width: 480px) {
            .tilt-wrapper { padding: 16px; perspective: none; }
            .auth-card { border-radius: 24px; transform: none !important;}
            .auth-card-inner { padding: 40px 24px; transform: none !important;}
            .auth-header h1 { font-size: 1.8rem; }
            .spotlight, .floating-object { display: none; }
        }
    </style>
</head>
<body>

    <div class="page-wrapper" id="page-wrapper">
        <!-- Background Mechanics -->
        <div class="gradient-mesh">
            <div class="mesh-blob mesh-1"></div>
            <div class="mesh-blob mesh-2"></div>
            <div class="mesh-blob mesh-3"></div>
        </div>

        <div class="spotlight" id="spotlight"></div>

        <div class="floating-object obj-1 parallax-obj" data-speed="1.5"><i class="fas fa-heartbeat"></i></div>
        <div class="floating-object obj-2 parallax-obj" data-speed="-2"><i class="fas fa-cookie-bite"></i></div>

        <!-- Central Auth Architecture -->
        <div class="tilt-wrapper" id="tilt-wrapper">
            <div class="auth-card" id="auth-card">
                <div class="auth-card-inner">
                    <a href="../index.php" class="back-home-btn stagger d-1" style="display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; margin-bottom: 20px; font-size: 0.9rem; transition: 0.3s;"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    <div class="auth-header">
                        <a href="../index.php" class="logo-link stagger d-1">
                            <img src="../assets/img/logo.png" alt="NutriDeq Logo">
                            <div class="logo-text">Nutri<span>Deq</span></div>
                        </a>
                        <h1 class="stagger d-1">Create Account</h1>
                        <p class="stagger d-1">Register for free forever access</p>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="error-message stagger d-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="success-message stagger d-2">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="process_register.php" method="POST">
                        <div class="form-group stagger d-2">
                            <label for="name">Full Name</label>
                            <div class="input-wrapper">
                                <input type="text" id="name" name="name" class="form-control" placeholder="Jane Doe" required value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group stagger d-2">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" class="form-control" placeholder="jane@clinic.com" required value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group stagger d-3">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group stagger d-3">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                                <i class="fas fa-check input-icon"></i>
                            </div>
                        </div>

                        <div class="stagger d-4" style="margin-top: 20px;">
                            <button type="submit" class="btn-submit">Initialize Portal</button>
                        </div>

                        <div class="google-auth-separator stagger d-4">OR</div>

                        <a href="google-callback.php" class="btn-google stagger d-4">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google Logo">
                            Sign up with Google
                        </a>
                    </form>

                    <div class="auth-footer stagger d-5">
                        Already registered? <a href="NutriDeqN-Login.php">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interface WOW Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const spotlight = document.getElementById('spotlight');
            const authCard = document.getElementById('auth-card');
            const parallaxObjs = document.querySelectorAll('.parallax-obj');

            let mouseX = window.innerWidth / 2;
            let mouseY = window.innerHeight / 2;

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
                
                requestAnimationFrame(() => {
                    // Spotlight Background
                    spotlight.style.left = mouseX + 'px';
                    spotlight.style.top = mouseY + 'px';

                    // Math for tilt
                    let xOffset = (mouseX - window.innerWidth / 2) / window.innerWidth;
                    let yOffset = (mouseY - window.innerHeight / 2) / window.innerHeight;

                    // 3D Card Tilt 
                    if (window.innerWidth > 480) { // Keep disabled on mobile
                        authCard.style.transform = `rotateY(${xOffset * 10}deg) rotateX(${-yOffset * 10}deg)`;
                    }

                    // 2D Floating Molecular Parallax
                    parallaxObjs.forEach(obj => {
                        let speed = parseFloat(obj.getAttribute('data-speed'));
                        obj.style.transform = `translate(${xOffset * 80 * speed}px, ${yOffset * 80 * speed}px)`;
                    });
                });
            });
        });

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../service-worker.js?v=3')
                    .catch(err => console.error('PWA Engine failure:', err));
            });
        }
    </script>
</body>
</html>