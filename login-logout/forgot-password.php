<?php
session_start();
require_once '../database.php';

// Load PHPMailer if available (Railway deployment)
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Fail-safe: Create table if not exists
        $conn->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (email),
            INDEX (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Check if user exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Delete any existing tokens for this email
            $delStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delStmt->execute([$email]);

            // Insert new token
            $insStmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $insStmt->execute([$email, $token, $expires]);

            // Determine environment and reset link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = $protocol . "://" . $host . "/login-logout/reset-password.php?token=" . $token;

            // Try sending real email if PHPMailer is loaded and SMTP is configured
            $mailSent = false;
            if (class_exists('PHPMailer\PHPMailer\PHPMailer') && getenv('SMTP_HOST')) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = getenv('SMTP_HOST');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('SMTP_USER');
                    $mail->Password   = getenv('SMTP_PASS');
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = getenv('SMTP_PORT') ?: 587;

                    $mail->setFrom(getenv('SMTP_FROM') ?: 'support@nutrideq.com', 'NutriDeq Support');
                    $mail->addAddress($email, $user['name']);
                    $mail->isHTML(true);
                    $mail->Subject = 'NutriDeq - Password Reset Request';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                            <h2 style='color: #10b981; text-align: center;'>NutriDeq Password Reset</h2>
                            <p>Hello <strong>{$user['name']}</strong>,</p>
                            <p>We received a request to reset your password. Click the button below to set a new one. This link will expire in 1 hour.</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$resetLink' style='background-color: #10b981; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Reset Password Now</a>
                            </div>
                            <p style='color: #666; font-size: 0.9rem;'>If you didn't request this, you can safely ignore this email.</p>
                            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='font-size: 0.8rem; color: #999; text-align: center;'>NutriDeq Clinical Platform &copy; " . date('Y') . "</p>
                        </div>
                    ";
                    $mail->send();
                    $mailSent = true;
                } catch (Exception $e) {
                    // Fallback to simulation if email fails
                    error_log("Mail Error: " . $mail->ErrorInfo);
                }
            }

            if ($mailSent) {
                $message = "A secure reset link has been sent to your Gmail address. Please check your inbox (and spam folder).";
            } else {
                // Simulation fallback for local development
                $message = "If an account exists for <strong>$email</strong>, a reset link has been generated.<br><br>
                            <div style='background: rgba(16,185,129,0.1); padding: 15px; border-radius: 10px; border: 1px dashed var(--primary);'>
                                <strong>DEVELOPMENT SIMULATION:</strong><br>
                                Click here to reset: <a href='$resetLink' style='color: var(--primary); font-weight: bold;'>Reset Password Now</a>
                            </div>";
            }
        } else {
            // Security: Generic message
            $message = "If an account exists for that email address, a reset link has been generated.";
        }
    } catch (Exception $e) {
        $error = "Error processing request: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - NutriDeq</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --text-dark: #0f172a;
            --bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        body {
            margin: 0; padding: 0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            overflow: hidden;
        }
        .gradient-bg {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1;
            background: radial-gradient(circle at 20% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 80% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 40%);
        }
        .auth-card {
            width: 100%; max-width: 450px; padding: 40px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px); border: 1px solid white;
            border-radius: 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            animation: popIn 0.6s var(--bounce);
        }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .logo { text-align: center; margin-bottom: 30px; }
        .logo img { height: 50px; border-radius: 12px; }
        
        h1 { font-size: 1.8rem; color: var(--text-dark); text-align: center; margin-bottom: 10px; }
        p { color: #64748b; text-align: center; margin-bottom: 30px; line-height: 1.6; }
        
        .form-group { margin-bottom: 20px; }
        .form-control {
            width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0;
            border-radius: 16px; box-sizing: border-box; font-size: 1rem;
            transition: all 0.3s; background: white;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
        
        .btn-submit {
            width: 100%; padding: 16px; background: var(--primary); color: white;
            border: none; border-radius: 16px; font-weight: 700; font-size: 1rem;
            cursor: pointer; transition: all 0.3s var(--bounce);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.3); }
        
        .alert { padding: 20px; border-radius: 16px; margin-bottom: 25px; font-size: 0.95rem; line-height: 1.5; }
        .alert-success { background: #ecfdf5; border: 1px solid #10b981; color: #065f46; }
        .alert-error { background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; }
        
        .back-link { display: block; text-align: center; margin-top: 25px; color: var(--primary); text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="gradient-bg"></div>
    <div class="auth-card">
        <div class="logo">
            <img src="../assets/img/logo.png" alt="NutriDeq">
        </div>
        <h1>Reset Password</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                <?php echo $message; ?>
            </div>
            <a href="NutriDeqN-Login.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
        <?php else: ?>
            <p>Enter your email address and we'll send you a secure link to reset your password.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
            <a href="NutriDeqN-Login.php" class="back-link">Return to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
