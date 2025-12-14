<?php
session_start();
require_once 'db_config.php';
$conn = getDBConnection();

// Get event ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = intval($_GET['id']);

// Fetch event details
$stmt = $conn->prepare("
    SELECT 
        event_id,
        event_title,
        event_description,
        event_date,
        event_time,
        street_address,
        city,
        state,
        zip_code,
        venue_name,
        early_bird_fee,
        standard_fee,
        member_discount_fee,
        early_bird_deadline,
        max_attendees,
        current_attendees,
        is_active
    FROM events 
    WHERE event_id = ? AND is_active = 1
");

$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: events.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Calculate which fee applies
$is_early_bird = false;
$current_fee = $event['standard_fee'];

if ($event['early_bird_deadline']) {
    $early_deadline = new DateTime($event['early_bird_deadline']);
    $today = new DateTime();
    
    if ($today <= $early_deadline) {
        $is_early_bird = true;
        $current_fee = $event['early_bird_fee'];
    }
}

// Check if member is logged in
$is_member = isset($_SESSION['member_id']);
$member_fee = $event['member_discount_fee'] ?? $current_fee;

// Format date and time
$event_date_obj = new DateTime($event['event_date']);
$formatted_date = $event_date_obj->format('F j, Y');
$day_of_week = $event_date_obj->format('l');

$formatted_time = '';
if ($event['event_time']) {
    $time_obj = new DateTime($event['event_time']);
    $formatted_time = $time_obj->format('g:i A');
}

// Calculate spots remaining
$spots_remaining = $event['max_attendees'] - $event['current_attendees'];
$is_full = $spots_remaining <= 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['event_title']); ?> - KNAA</title>
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
            line-height: 1.6;
            color: var(--dark-gray);
            background: var(--light-gray);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navigation */
        .navbar {
            background: #ffe8e8;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo-img {
            height: 120px;
            width: auto;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--dark-gray);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: var(--primary-red);
        }

        /* Event Details Section */
        .event-details-section {
            padding: 3rem 0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-blue);
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--primary-red);
        }

        .event-content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .event-main-content {
            padding: 3rem;
        }

        .event-title {
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-gray);
        }

        .meta-item svg {
            width: 20px;
            height: 20px;
            color: var(--primary-blue);
        }

        .event-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-gray);
            margin-bottom: 2rem;
        }

        .event-location-details {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .event-location-details h3 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .location-item {
            display: flex;
            align-items: start;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .location-item svg {
            width: 18px;
            height: 18px;
            color: var(--primary-blue);
            margin-top: 2px;
        }

        /* Registration Sidebar */
        .registration-sidebar {
            background: var(--light-gray);
            padding: 2rem;
        }

        .price-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .price-label {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 0.5rem;
        }

        .price-amount {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .price-note {
            font-size: 0.85rem;
            color: var(--text-gray);
            font-style: italic;
        }

        .early-bird-badge {
            display: inline-block;
            background: var(--primary-red);
            color: var(--white);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .pricing-breakdown {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--light-gray);
        }

        .pricing-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }

        .pricing-item.highlight {
            font-weight: 600;
            color: var(--primary-blue);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: var(--primary-red);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #B01030;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,20,60,0.4);
        }

        .btn-disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }

        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .event-info-box {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .info-value {
            color: var(--text-gray);
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 968px) {
            .event-content-grid {
                grid-template-columns: 1fr;
            }

            .registration-sidebar {
                order: -1;
            }

            .event-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .event-main-content,
            .registration-sidebar {
                padding: 1.5rem;
            }

            .event-title {
                font-size: 1.7rem;
            }

            .price-amount {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <img src="./assets/images/KNAA logo-1.png" alt="KNAA Logo" class="logo-img">
            </div>
            <ul class="nav-menu">
                <?php if ($is_member): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="login.php">Member Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Event Details Section -->
    <section class="event-details-section">
        <div class="container">
            <a href="events.php" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Events
            </a>

            <div class="event-content-grid">
                <!-- Main Content -->
                <div class="event-main-content">
                    <h1 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h1>

                    <div class="event-meta">
                        <div class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span><strong><?php echo $day_of_week; ?>,</strong> <?php echo $formatted_date; ?></span>
                        </div>

                        <?php if ($formatted_time): ?>
                        <div class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span><?php echo $formatted_time; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="event-description">
                        <?php echo nl2br(htmlspecialchars($event['event_description'])); ?>
                    </div>

                    <?php if ($event['venue_name'] || $event['street_address']): ?>
                    <div class="event-location-details">
                        <h3>Event Location</h3>
                        <?php if ($event['venue_name']): ?>
                        <div class="location-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            </svg>
                            <span><strong><?php echo htmlspecialchars($event['venue_name']); ?></strong></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($event['street_address']): ?>
                        <div class="location-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>
                                <?php echo htmlspecialchars($event['street_address']); ?>
                                <?php if ($event['city']): ?>
                                    <br><?php echo htmlspecialchars($event['city']); ?><?php if ($event['state']): ?>, <?php echo htmlspecialchars($event['state']); ?><?php endif; ?> <?php echo htmlspecialchars($event['zip_code']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Registration Sidebar -->
                <div class="registration-sidebar">
                    <?php if ($is_full): ?>
                        <div class="alert alert-warning">
                            <strong>Event Full</strong><br>
                            This event has reached maximum capacity.
                        </div>
                    <?php elseif ($spots_remaining <= 10): ?>
                        <div class="alert alert-warning">
                            <strong>Limited Spots!</strong><br>
                            Only <?php echo $spots_remaining; ?> spots remaining
                        </div>
                    <?php endif; ?>

                    <div class="price-card">
                        <?php if ($is_early_bird): ?>
                        <span class="early-bird-badge">Early Bird Pricing!</span>
                        <?php endif; ?>

                        <div class="price-label">
                            <?php echo $is_member ? 'Member Price' : 'Registration Fee'; ?>
                        </div>
                        <div class="price-amount">
                            $<?php echo number_format($is_member ? $member_fee : $current_fee, 2); ?>
                        </div>

                        <?php if ($is_early_bird && $event['early_bird_deadline']): ?>
                        <div class="price-note">
                            Early bird pricing until <?php echo date('M j, Y', strtotime($event['early_bird_deadline'])); ?>
                        </div>
                        <?php endif; ?>

                        <div class="pricing-breakdown">
                            <div class="pricing-item">
                                <span>Standard Fee:</span>
                                <span>$<?php echo number_format($event['standard_fee'], 2); ?></span>
                            </div>
                            <?php if ($event['early_bird_fee'] && $event['early_bird_fee'] < $event['standard_fee']): ?>
                            <div class="pricing-item">
                                <span>Early Bird Fee:</span>
                                <span>$<?php echo number_format($event['early_bird_fee'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($event['member_discount_fee']): ?>
                            <div class="pricing-item highlight">
                                <span>Member Discount:</span>
                                <span>$<?php echo number_format($event['member_discount_fee'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$is_full): ?>
                        <?php if ($is_member): ?>
                            <a href="event_register.php?id=<?php echo $event_id; ?>" class="btn btn-primary">
                                Register Now
                            </a>
                        <?php else: ?>
                            <a href="event_register_guest.php?id=<?php echo $event_id; ?>" class="btn btn-primary">
                                Register as Guest
                            </a>
                            <div class="alert alert-info" style="margin-top: 1rem;">
                                <strong>KNAA Members:</strong> <a href="login.php" style="color: var(--primary-blue);">Log in</a> to receive member discount pricing!
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-disabled" disabled>Event Full</button>
                    <?php endif; ?>

                    <div class="event-info-box" style="margin-top: 1.5rem;">
                        <div class="info-item">
                            <span class="info-label">Total Capacity:</span>
                            <span class="info-value"><?php echo $event['max_attendees']; ?> attendees</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Registered:</span>
                            <span class="info-value"><?php echo $event['current_attendees']; ?> attendees</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Available:</span>
                            <span class="info-value"><?php echo max(0, $spots_remaining); ?> spots</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
<?php $conn->close(); ?>