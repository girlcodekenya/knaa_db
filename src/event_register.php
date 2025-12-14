<?php
session_start();
require_once 'db_config.php';
$conn = getDBConnection();

// Check if member is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php?redirect=event_register.php&id=" . (isset($_GET['id']) ? $_GET['id'] : ''));
    exit();
}

// Get event ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = intval($_GET['id']);
$member_id = $_SESSION['member_id'];

// Fetch event details
$stmt = $conn->prepare("
    SELECT 
        event_id,
        event_title,
        event_date,
        event_time,
        venue_name,
        early_bird_fee,
        standard_fee,
        member_discount_fee,
        early_bird_deadline,
        max_attendees,
        current_attendees
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

// Check if already registered
$check_stmt = $conn->prepare("SELECT registration_id FROM event_registrations WHERE event_id = ? AND member_id = ?");
$check_stmt->bind_param("is", $event_id, $member_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error'] = "You are already registered for this event.";
    header("Location: event_details.php?id=" . $event_id);
    exit();
}
$check_stmt->close();

// Check if event is full
$spots_remaining = $event['max_attendees'] - $event['current_attendees'];
if ($spots_remaining <= 0) {
    $_SESSION['error'] = "This event is full.";
    header("Location: event_details.php?id=" . $event_id);
    exit();
}

// Calculate registration fee
$is_early_bird = false;
$registration_fee = $event['standard_fee'];

if ($event['early_bird_deadline']) {
    $early_deadline = new DateTime($event['early_bird_deadline']);
    $today = new DateTime();
    
    if ($today <= $early_deadline) {
        $is_early_bird = true;
        $registration_fee = $event['early_bird_fee'];
    }
}

// Apply member discount if available
if ($event['member_discount_fee']) {
    $registration_fee = $event['member_discount_fee'];
}

// Fetch member details
$member_stmt = $conn->prepare("
    SELECT first_name, last_name, email, phone, street_address, city, state, zip_code
    FROM members 
    WHERE member_id = ?
");
$member_stmt->bind_param("s", $member_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();
$member = $member_result->fetch_assoc();
$member_stmt->close();

// Format event date
$event_date = new DateTime($event['event_date']);
$formatted_date = $event_date->format('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Event - KNAA</title>
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

        .registration-section {
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

        .registration-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 3rem;
        }

        .registration-form-container {
            background: var(--white);
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        h1 {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .event-title-sub {
            font-size: 1.2rem;
            color: var(--text-gray);
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
        }

        .required {
            color: var(--primary-red);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--light-gray);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        input[readonly] {
            background: var(--light-gray);
            cursor: not-allowed;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-red);
            color: var(--white);
            width: 100%;
        }

        .btn-primary:hover {
            background: #B01030;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,20,60,0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary-blue);
            border: 2px solid var(--primary-blue);
        }

        .btn-secondary:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        .summary-sidebar {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-sidebar h2 {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .summary-value {
            color: var(--text-gray);
        }

        .total-amount {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-blue);
            text-align: center;
            margin: 2rem 0;
            padding: 1.5rem;
            background: var(--light-gray);
            border-radius: 10px;
        }

        .discount-badge {
            display: inline-block;
            background: var(--primary-red);
            color: var(--white);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .info-note {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1.5rem;
            font-size: 0.95rem;
            border: 1px solid #bee5eb;
        }

        @media (max-width: 968px) {
            .registration-grid {
                grid-template-columns: 1fr;
            }

            .summary-sidebar {
                order: -1;
                position: static;
            }

            .registration-form-container {
                padding: 2rem;
            }

            h1 {
                font-size: 1.7rem;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .registration-form-container {
                padding: 1.5rem;
            }

            .summary-sidebar {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <img src="./assets/images/KNAA logo-1.png" alt="KNAA Logo" class="logo-img">
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <section class="registration-section">
        <div class="container">
            <a href="event_details.php?id=<?php echo $event_id; ?>" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Event Details
            </a>

            <div class="registration-grid">
                <!-- Registration Form -->
                <div class="registration-form-container">
                    <h1>Event Registration</h1>
                    <div class="event-title-sub"><?php echo htmlspecialchars($event['event_title']); ?></div>

                    <form action="event_register_handler.php" method="POST">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member_id); ?>">
                        <input type="hidden" name="registration_fee" value="<?php echo $registration_fee; ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($member['first_name']); ?>" readonly required>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($member['last_name']); ?>" readonly required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" readonly required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="street_address">Street Address</label>
                            <input type="text" id="street_address" name="street_address" value="<?php echo htmlspecialchars($member['street_address']); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($member['city']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($member['state']); ?>" maxlength="2">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="zip_code">ZIP Code</label>
                            <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($member['zip_code']); ?>" maxlength="10">
                        </div>

                        <div class="info-note">
                            <strong>Note:</strong> After submitting this registration, you will be directed to complete payment. Your registration will be confirmed once payment is received.
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 2rem;">
                            Proceed to Payment
                        </button>
                    </form>
                </div>

                <!-- Summary Sidebar -->
                <div class="summary-sidebar">
                    <h2>Registration Summary</h2>

                    <div class="summary-item">
                        <span class="summary-label">Event:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($event['event_title']); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Date:</span>
                        <span class="summary-value"><?php echo $formatted_date; ?></span>
                    </div>

                    <?php if ($event['venue_name']): ?>
                    <div class="summary-item">
                        <span class="summary-label">Venue:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($event['venue_name']); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-item">
                        <span class="summary-label">Registrant:</span>
                        <span class="summary-value">
                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                            <span class="discount-badge">Member</span>
                        </span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Member ID:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($member_id); ?></span>
                    </div>

                    <div class="total-amount">
                        $<?php echo number_format($registration_fee, 2); ?>
                        <?php if ($is_early_bird): ?>
                        <br><span style="font-size: 0.9rem; color: var(--primary-red);">Early Bird Rate</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($event['member_discount_fee'] && $event['member_discount_fee'] < $event['standard_fee']): ?>
                    <div class="info-note">
                        <strong>Member Discount Applied!</strong><br>
                        You're saving $<?php echo number_format($event['standard_fee'] - $event['member_discount_fee'], 2); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
<?php $conn->close(); ?>