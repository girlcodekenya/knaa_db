<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - KNAA</title>
    <link rel="stylesheet" href="../../styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0052A5 0%, #003875 100%);
            padding: 2rem;
        }
        
        .login-box {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header img {
            width: 150px;
            margin-bottom: 1rem;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            color: #0052A5;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #0052A5;
        }
        
        .login-btn {
            width: 100%;
            padding: 1rem;
            background: #DC143C;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .login-btn:hover {
            background: #B01030;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-to-site a {
            color: #0052A5;
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="../../assets/images/KNAA logo-1.png" alt="KNAA Logo">
                <h1>Admin Portal</h1>
                <p>Kenyan Nurses Association of America</p>
            </div>

            <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-info">
                Your session has expired. Please log in again.
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                Invalid username or password. Please try again.
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-info">
                You have been successfully logged out.
            </div>
            <?php endif; ?>

            <form action="login_handler.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <div class="back-to-site">
                <a href="../../index.html">Back to Main Site</a>
            </div>
        </div>
    </div>
</body>
</html>