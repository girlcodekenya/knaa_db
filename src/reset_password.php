<?php
session_start();
require_once 'db_config.php';

$token = $_GET['token'] ?? '';
$valid_token = false;
$error = '';

if ($token) {
    try {
        $stmt = $pdo->prepare("
            SELECT pr.reset_id, pr.member_id, pr.expires_at, pr.used, m.email, m.first_name
            FROM password_resets pr
            JOIN members m ON pr.member_id = m.member_id
            WHERE pr.token = ? AND pr.used = 0
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            $error = 'Invalid or expired reset link';
        } elseif (strtotime($reset['expires_at']) < time()) {
            $error = 'This reset link has expired';
        } else {
            $valid_token = true;
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
} else {
    $error = 'No reset token provided';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - KNAA</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .auth-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .auth-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2.5rem;
            max-width: 500px;
            width: 100%;
            border: 2px solid var(--light-gray);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-header h1 {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }
        .auth-header p {
            color: var(--text-gray);
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-gray);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
        }
        .password-requirements {
            font-size: 0.85rem;
            color: var(--text-gray);
            margin-top: 0.5rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary-red);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: #B01030;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,20,60,0.4);
        }
        .btn-secondary {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary-blue);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-secondary:hover {
            background: #003d7a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,82,165,0.4);
        }
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
        }
        .auth-footer a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .auth-footer a:hover {
            color: var(--primary-red);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <div class="logo">
                <a href="../index.html">
                    <img src="../assets/images/KNAA logo-1.png" alt="KNAA Logo" class="logo-img">
                </a>
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="../index.html">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="auth-container">
        <div class="container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Reset Your Password</h1>
                    <?php if ($valid_token): ?>
                        <p>Hello <?php echo htmlspecialchars($reset['first_name']); ?>, enter your new password below</p>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <a href="forgot_password.php" class="btn-secondary">Request New Reset Link</a>
                <?php elseif ($valid_token): ?>
                    <form action="reset_password_handler.php" method="POST" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="password-requirements">
                                Must be at least 8 characters long
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>

                        <button type="submit" class="btn-submit">Reset Password</button>
                    </form>
                <?php endif; ?>

                <div class="auth-footer">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 Kenyan Nurses Association of America. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../scripts.js"></script>
    <script>
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    </script>
</body>
</html>