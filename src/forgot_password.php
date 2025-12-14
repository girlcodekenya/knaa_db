<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - KNAA</title>
    <style>
        :root {
            --white: #FFFFFF;
            --primary-blue: #0052A5;
            --primary-red: #DC143C;
            --light-gray: #E0E0E0;
            --dark-gray: #333333;
            --text-gray: #666666;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: #FFE8E8;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer {
            background: #000514;
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: 3rem;
        }
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
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
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
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-img {
            height: 60px;
            width: auto;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }

        .nav-menu li {
            margin: 0;
        }

        .nav-menu a {
            text-decoration: none;
            color: #0052A5;
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #DC143C;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-menu {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <div class="logo">
                <a href="index.html">
                    <img src="assets/images/KNAA logo-1.png" alt="KNAA Logo" class="logo-img">
                </a>
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.html">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="auth-container">
        <div class="container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Reset Your Password</h1>
                    <p>Enter your email address and we'll send you a link to reset your password</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo htmlspecialchars($_SESSION['success']); 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="forgot_password_handler.php" method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <button type="submit" class="btn-submit">Send Reset Link</button>
                </form>

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

    <script src="scripts.js"></script>
</body>
</html>