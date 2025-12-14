<?php
session_start();
require_once 'db_config.php';
$conn = getDBConnection();

// Get registration ID
if (!isset($_GET['registration_id'])) {
    header("Location: events.php");
    exit();
}

$registration_id = intval($_GET['registration_id']);

// Fetch registration details
$stmt = $conn->prepare("
    SELECT 
        er.registration_id,
        er.event_id,
        er.member_id,
        er.first_name,
        er.last_name,
        er.email,
        er.phone,
        er.registration_fee,
        er.payment_status,
        er.payment_method,
        er.registration_date,
        e.event_title,
        e.event_description,
        e.event_date,
        e.event_time,
        e.venue_name,
        e.street_address,
        e.city,
        e.state,
        e.zip_code
    FROM event_registrations er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.registration_id = ?
");

$stmt->bind_param("i", $registration_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Registration not found.";
    header("Location: events.php");
    exit();
}

$registration = $result->fetch_assoc();
$stmt->close();

// Format dates
$event_date = new DateTime($registration['event_date']);
$formatted_date = $event_date->format('l, F j, Y');

$formatted_time = '';
if ($registration['event_time']) {
    $time_obj = new DateTime($registration['event_time']);
    $formatted_time = $time_obj->format('g:i A');
}

$registration_date = new DateTime($registration['registration_date']);
$formatted_registration_date = $registration_date->format('F j, Y g:i A');

$is_member = !empty($registration['member_id']);
$is_pending = $registration['payment_status'] === 'pending';
$is_paid = $registration['payment_status'] === 'completed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation - KNAA</title>
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

        .confirmation-section {
            padding: 3rem 0;
        }

        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .confirmation-header {
            background: var(--primary-blue);
            color: var(--white);
            padding: 3rem 2rem;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--white);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-icon svg {
            width: 50px;
            height: 50px;
            color: #28a745;
        }

        .confirmation-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .confirmation-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .confirmation-body {
            padding: 3rem;
        }

        .alert {
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .details-section {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .details-section h2 {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ddd;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #ddd;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--dark-gray);
        }

        .detail-value {
            color: var(--text-gray);
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #003d7a;
            transform: translateY(-2px);
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

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-buttons .btn {
            flex: 1;
        }

        .next-steps {
            background: #d1ecf1;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .next-steps h3 {
            font-size: 1.3rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .next-steps ol {
            margin-left: 1.5rem;
            line-height: 2;
            color: var(--text-gray);
        }

        @media (max-width: 768px) {
            .confirmation-body {
                padding: 2rem;
            }

            .confirmation-header {
                padding: 2rem 1.5rem;
            }

            .confirmation-header h1 {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .detail-item {
                flex-direction: column;
                gap: 0.5rem;
            }

            .detail-value {
                text-align: left;
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
                <?php if ($is_member): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="events.php">Events</a></li>
                <?php else: ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <section class="confirmation-section">
        <div class="container">
            <div class="confirmation-container">
                <div class="confirmation-header">
                    <div class="success-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <h1>Registration Received!</h1>
                    <p>Confirmation #<?php echo str_pad($registration_id, 6, '0', STR_PAD_LEFT); ?></p>
                </div>

                <div class="confirmation-body">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($is_pending): ?>
                    <div class="alert alert-warning">
                        <strong>⏳ Payment Pending</strong><br>
                        We've received your payment notification. Your registration will be confirmed once we verify your payment (typically within 24 hours). You'll receive a confirmation email at <?php echo htmlspecialchars($registration['email']); ?>.
                    </div>
                    <?php elseif ($is_paid): ?>
                    <div class="alert alert-success">
                        <strong>✅ Payment Confirmed</strong><br>
                        Your registration is complete! A confirmation email has been sent to <?php echo htmlspecialchars($registration['email']); ?>.
                    </div>
                    <?php endif; ?>

                    <div class="details-section">
                        <h2>Event Details</h2>
                        <div class="detail-item">
                            <span class="detail-label">Event:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($registration['event_title']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value"><?php echo $formatted_date; ?></span>
                        </div>
                        <?php if ($formatted_time): ?>
                        <div class="detail-item">
                            <span class="detail-label">Time:</span>
                            <span class="detail-value"><?php echo $formatted_time; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($registration['venue_name']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Venue:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($registration['venue_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($registration['street_address']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value">
                                <?php echo htmlspecialchars($registration['street_address']); ?><br>
                                <?php echo htmlspecialchars($registration['city']); ?>, <?php echo htmlspecialchars($registration['state']); ?> <?php echo htmlspecialchars($registration['zip_code']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="details-section">
                        <h2>Registration Information</h2>
                        <div class="detail-item">
                            <span class="detail-label">Attendee:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($registration['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($registration['phone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Registration Date:</span>
                            <span class="detail-value"><?php echo $formatted_registration_date; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Registration Fee:</span>
                            <span class="detail-value"><strong>$<?php echo number_format($registration['registration_fee'], 2); ?></strong></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value">
                                <span class="status-badge <?php echo $is_paid ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $is_paid ? 'Confirmed' : 'Pending Verification'; ?>
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="next-steps">
                        <h3>What's Next?</h3>
                        <ol>
                            <li>You'll receive a confirmation email once payment is verified</li>
                            <li>Check your email closer to the event date for any updates</li>
                            <li>Mark your calendar for <?php echo $formatted_date; ?></li>
                            <li>We look forward to seeing you at the event!</li>
                        </ol>
                    </div>

                    <div class="action-buttons">
                        <?php if ($is_member): ?>
                            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                        <?php else: ?>
                            <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                        <?php endif; ?>
                        <a href="events.php" class="btn btn-secondary">View More Events</a>
                    </div>

                    <p style="text-align: center; margin-top: 2rem; color: var(--text-gray);">
                        Questions? Contact us at <a href="mailto:Info@KenyanNursesUSA.org" style="color: var(--primary-blue);">Info@KenyanNursesUSA.org</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
<?php $conn->close(); ?>