<?php
require_once 'session_check.php';
require_once 'db_config.php';

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        m.*,
        mt.type_name,
        mt.annual_fee
    FROM members m
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.type_id
    WHERE m.member_id = ?
");
$stmt->bind_param("s", $_SESSION['member_id']);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_expired = false;
$days_until_expiry = null;
if ($member['membership_expiration_date']) {
    $expiry_date = new DateTime($member['membership_expiration_date']);
    $today = new DateTime();
    $interval = $today->diff($expiry_date);
    $days_until_expiry = (int)$interval->format('%r%a');
    $is_expired = $days_until_expiry < 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - KNAA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #0052A5;
            --primary-red: #DC143C;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --text-gray: #555555;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            line-height: 1.6;
        }

        .navbar {
            background: #ffe8e8;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-blue);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--dark-gray);
            font-weight: 500;
            transition: color 0.3s;
            padding: 0.5rem 0;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--primary-red);
        }

        .header {
            background: var(--primary-blue);
            color: white;
            padding: 40px 0;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2em;
        }

        .user-info {
            text-align: right;
        }

        .user-info .name {
            font-weight: 600;
            font-size: 1.2em;
            margin-bottom: 5px;
        }

        .user-info .member-id {
            font-size: 0.95em;
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-blue);
        }

        .welcome-section h2 {
            color: var(--primary-blue);
            margin-bottom: 10px;
            font-size: 1.8em;
        }

        .welcome-section p {
            color: var(--text-gray);
            font-size: 1.05em;
        }

        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid var(--light-gray);
            transition: all 0.3s;
        }

        .card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,82,165,0.15);
        }

        .card h3 {
            color: var(--primary-blue);
            margin-bottom: 20px;
            font-size: 1.3em;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-gray);
            font-weight: 500;
        }

        .info-value {
            color: var(--dark-gray);
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-expired {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-expiring {
            background-color: #fff3e0;
            color: #e65100;
        }

        .alert {
            padding: 18px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 1.05em;
        }

        .alert strong {
            display: block;
            margin-bottom: 5px;
            font-size: 1.1em;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            color: #856404;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-left: 5px solid var(--primary-red);
            color: #721c24;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .quick-link {
            display: block;
            padding: 20px;
            background: var(--primary-blue);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid var(--primary-blue);
        }

        .quick-link:hover {
            background: var(--primary-red);
            border-color: var(--primary-red);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(220, 20, 60, 0.4);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .user-info {
                text-align: center;
            }

            .header h1 {
                font-size: 1.5em;
            }

            .welcome-section {
                padding: 20px;
            }

            .welcome-section h2 {
                font-size: 1.4em;
            }

            .status-cards {
                grid-template-columns: 1fr;
            }

            .quick-links {
                grid-template-columns: 1fr;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.3em;
            }

            .user-info .name {
                font-size: 1.1em;
            }

            .welcome-section h2 {
                font-size: 1.2em;
            }

            .card {
                padding: 20px;
            }

            .card h3 {
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">KNAA</div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="header">
        <div class="header-content">
            <h1>Member Dashboard</h1>
            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></div>
                <div class="member-id"><?php echo htmlspecialchars($member['member_id']); ?></div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($member['first_name']); ?></h2>
            <p>Member since <?php echo date('F Y', strtotime($member['member_since'])); ?></p>
        </div>

        <?php if ($is_expired): ?>
            <div class="alert alert-danger">
                <strong>Membership Expired</strong>
                Your membership expired on <?php echo date('F j, Y', strtotime($member['membership_expiration_date'])); ?>. 
                Please renew to continue enjoying member benefits.
            </div>
        <?php elseif ($days_until_expiry !== null && $days_until_expiry <= 30): ?>
            <div class="alert alert-warning">
                <strong>Membership Expiring Soon</strong>
                Your membership expires in <?php echo $days_until_expiry; ?> day<?php echo $days_until_expiry != 1 ? 's' : ''; ?> 
                (<?php echo date('F j, Y', strtotime($member['membership_expiration_date'])); ?>).
            </div>
        <?php endif; ?>

        <div class="status-cards">
            <div class="card">
                <h3>Membership Status</h3>
                <div class="info-row">
                    <span class="info-label">Type</span>
                    <span class="info-value"><?php echo htmlspecialchars($member['type_name']); ?> Member</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <?php if ($is_expired): ?>
                            <span class="status-badge status-expired">Expired</span>
                        <?php elseif ($days_until_expiry !== null && $days_until_expiry <= 30): ?>
                            <span class="status-badge status-expiring">Expiring Soon</span>
                        <?php else: ?>
                            <span class="status-badge status-active">Active</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Expires</span>
                    <span class="info-value">
                        <?php 
                        echo $member['membership_expiration_date'] 
                            ? date('M j, Y', strtotime($member['membership_expiration_date'])) 
                            : 'Not Set'; 
                        ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <h3>Contact Information</h3>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($member['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($member['phone'] ?: 'Not provided'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Location</span>
                    <span class="info-value">
                        <?php 
                        if ($member['city'] && $member['state']) {
                            echo htmlspecialchars($member['city'] . ', ' . $member['state']);
                        } else {
                            echo 'Not provided';
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Quick Actions</h3>
            <div class="quick-links">
                <a href="change_password.php" class="quick-link">Change Password</a>
                <a href="edit_profile.php" class="quick-link">Edit Profile</a>
                <a href="index.php" class="quick-link">Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>