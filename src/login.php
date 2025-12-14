<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['member_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if there's an error message from login attempt
$error_message = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login - KNAA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            padding: 40px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section h1 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 5px;
        }

        .logo-section p {
            color: #666;
            font-size: 0.95em;
        }

        .error-message {
            background-color: #fee;
            border-left: 4px solid #f44336;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #c62828;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.95em;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .links-section {
            margin-top: 25px;
            text-align: center;
        }

        .links-section a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95em;
        }

        .links-section a:hover {
            text-decoration: underline;
        }

        .divider {
            margin: 15px 0;
            color: #999;
        }

        .default-password-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #856404;
        }

        .default-password-note strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <h1>KNAA</h1>
            <p>Kenya Nurses Association of America</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="default-password-note">
            <strong>First Time Login?</strong>
            Your default password is: <strong>KNAA2024!</strong><br>
            You'll be prompted to change it after logging in.
        </div>

        <form action="login_handler.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    placeholder="Enter your email"
                    value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="Enter your password"
                >
            </div>

            <button type="submit" class="submit-btn">Login</button>
        </form>

        <div class="links-section">
            <a href="forgot_password.php">Forgot Password?</a>
            <div class="divider">•</div>
            <a href="register.php">New Member? Register Here</a>
            <div class="divider">•</div>
            <a href="index.php">Back to Homepage</a>
        </div>
    </div>
</body>
</html>
<?php
// Clear the email session variable
unset($_SESSION['login_email']);
?>