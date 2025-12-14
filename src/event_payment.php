<?php
session_start();
require_once 'db_config.php';
$conn = getDBConnection();

// Check if registration exists in session
if (!isset($_SESSION['pending_registration']) || !isset($_GET['registration_id'])) {
    header("Location: events.php");
    exit();
}

$registration_data = $_SESSION['pending_registration'];
$registration_id = intval($_GET['registration_id']);

// Verify registration ID matches
if ($registration_data['registration_id'] != $registration_id) {
    header("Location: events.php");
    exit();
}

// Fetch registration details from database
$stmt = $conn->prepare("
    SELECT 
        er.registration_id,
        er.event_id,
        er.first_name,
        er.last_name,
        er.email,
        er.registration_fee,
        er.payment_status,
        e.event_title,
        e.event_date,
        e.venue_name
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

// If already paid, redirect to confirmation
if ($registration['payment_status'] === 'completed') {
    header("Location: event_confirmation.php?registration_id=" . $registration_id);
    exit();
}

// Format date
$event_date = new DateTime($registration['event_date']);
$formatted_date = $event_date->format('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - KNAA Event Registration</title>
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

        .payment-section {
            padding: 3rem 0;
        }

        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .payment-header {
            background: var(--primary-blue);
            color: var(--white);
            padding: 2rem;
            text-align: center;
        }

        .payment-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .payment-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .payment-body {
            padding: 3rem;
        }

        .registration-summary {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .registration-summary h2 {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #ddd;
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

        .amount-due {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin: 2rem 0;
            border: 3px solid var(--primary-blue);
        }

        .amount-label {
            font-size: 1rem;
            color: var(--text-gray);
            margin-bottom: 0.5rem;
        }

        .amount-value {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-blue);
        }

        .payment-options {
            margin-top: 2rem;
        }

        .payment-options h3 {
            font-size: 1.3rem;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
        }

        .payment-method {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 15px rgba(0,82,165,0.1);
        }

        .payment-method h4 {
            font-size: 1.2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .payment-method p {
            color: var(--text-gray);
            line-height: 1.7;
            margin-bottom: 1rem;
        }

        .payment-details {
            background: var(--white);
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin-top: 1rem;
        }

        .payment-details strong {
            color: var(--primary-blue);
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
            background: var(--primary-red);
            color: var(--white);
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

        .alert {
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .button-group .btn {
            flex: 1;
        }

        @media (max-width: 768px) {
            .payment-body {
                padding: 2rem;
            }

            .payment-header h1 {
                font-size: 1.7rem;
            }

            .amount-value {
                font-size: 2.5rem;
            }

            .button-group {
                flex-direction: column;
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
                <?php if (!$registration_data['is_guest']): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="events.php">Events</a></li>
                <?php else: ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <section class="payment-section">
        <div class="container">
            <div class="payment-container">
                <div class="payment-header">
                    <h1>Complete Your Payment</h1>
                    <p>Registration ID: #<?php echo str_pad($registration_id, 6, '0', STR_PAD_LEFT); ?></p>
                </div>

                <div class="payment-body">
                    <div class="alert alert-info">
                        <strong>Registration Reserved!</strong><br>
                        Your spot has been reserved. Please complete payment to confirm your registration.
                    </div>

                    <div class="registration-summary">
                        <h2>Registration Details</h2>
                        <div class="summary-item">
                            <span class="summary-label">Event:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($registration['event_title']); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Date:</span>
                            <span class="summary-value"><?php echo $formatted_date; ?></span>
                        </div>
                        <?php if ($registration['venue_name']): ?>
                        <div class="summary-item">
                            <span class="summary-label">Venue:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($registration['venue_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-item">
                            <span class="summary-label">Attendee:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Email:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($registration['email']); ?></span>
                        </div>
                    </div>

                    <div class="amount-due">
                        <div class="amount-label">Amount Due</div>
                        <div class="amount-value">$<?php echo number_format($registration['registration_fee'], 2); ?></div>
                    </div>

                    <div class="payment-options">
                        <h3>Payment Methods</h3>

                        <div class="payment-method">
                            <h4>💳 Option 1: Zelle</h4>
                            <p>Send payment via Zelle for instant confirmation:</p>
                            <div class="payment-details">
                                <strong>Zelle Email:</strong> Info@KenyanNursesUSA.org<br>
                                <strong>Amount:</strong> $<?php echo number_format($registration['registration_fee'], 2); ?><br>
                                <strong>Note:</strong> Include "Event Registration #<?php echo $registration_id; ?>" in memo
                            </div>
                            <p style="margin-top: 1rem;"><strong>After sending payment:</strong> Click "I've Paid with Zelle" button below and we'll confirm your registration within 24 hours.</p>
                        </div>

                        <div class="payment-method">
                            <h4>🌐 Option 2: Every.org (Online Payment)</h4>
                            <p>Make a secure online payment through Every.org:</p>
                            <p><strong>Instructions:</strong></p>
                            <ol style="margin-left: 1.5rem; line-height: 1.8;">
                                <li>Click "Pay with Every.org" button below</li>
                                <li>Enter amount: $<?php echo number_format($registration['registration_fee'], 2); ?></li>
                                <li>In the donation note, include: "Event Registration #<?php echo $registration_id; ?>"</li>
                                <li>Complete payment with credit card or bank account</li>
                            </ol>
                        </div>

                        <div class="alert alert-warning" style="margin-top: 1.5rem;">
                            <strong>⏰ Important:</strong> Please complete payment within 24 hours to secure your spot. Unpaid registrations will be automatically cancelled.
                        </div>
                    </div>

                    <div class="button-group">
                        <form action="event_payment_confirm.php" method="POST" style="flex: 1;">
                            <input type="hidden" name="registration_id" value="<?php echo $registration_id; ?>">
                            <input type="hidden" name="payment_method" value="zelle">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                I've Paid with Zelle
                            </button>
                        </form>
                        <a href="https://www.every.org/kenyan-nurses-association-of-america" target="_blank" class="btn btn-secondary">
                            Pay with Every.org
                        </a>
                    </div>

                    <p style="text-align: center; margin-top: 2rem; color: var(--text-gray);">
                        Questions? Email us at <a href="mailto:Info@KenyanNursesUSA.org" style="color: var(--primary-blue);">Info@KenyanNursesUSA.org</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
<?php $conn->close(); ?>