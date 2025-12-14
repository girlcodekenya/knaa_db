<?php
$member_id = isset($_GET['member_id']) ? htmlspecialchars($_GET['member_id']) : '';

if (empty($member_id)) {
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - KNAA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        
        h1 {
            color: #10b981;
            margin-bottom: 20px;
        }
        
        .member-id-box {
            background: #f3f4f6;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .member-id-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .member-id {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 2px;
        }
        
        .info-text {
            color: #666;
            line-height: 1.6;
            margin: 20px 0;
        }
        
        .next-steps {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .next-steps h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .next-steps ul {
            list-style-position: inside;
            color: #666;
            line-height: 2;
        }
        
        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        
        <h1>Registration Successful</h1>
        
        <p class="info-text">
            Welcome to the Kenyan Nurses Association of America. Your membership has been successfully registered.
        </p>
        
        <div class="member-id-box">
            <div class="member-id-label">Your Member ID</div>
            <div class="member-id"><?php echo $member_id; ?></div>
        </div>
        
        <p class="info-text">
            Please save this Member ID for future reference. You will need it when registering for events and accessing member benefits.
        </p>
        
        <div class="next-steps">
            <h3>What's Next</h3>
            <ul>
                <li>Check your email for a confirmation message</li>
                <li>Your membership is valid for one year from today</li>
                <li>You can now register for upcoming events</li>
                <li>Access member-only resources and benefits</li>
            </ul>
        </div>
        
        <div class="btn-container">
            <a href="index.php" class="btn btn-secondary">Go to Homepage</a>
            <a href="events.php" class="btn btn-primary">Browse Events</a>
        </div>
    </div>
</body>
</html>