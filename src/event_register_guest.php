<?php
session_start();
require_once 'db_config.php';
$conn = getDBConnection();

if (!isset($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT event_id, event_title, event_description, event_date, event_time, 
           street_address, city, state, zip_code, venue_name,
           early_bird_fee, standard_fee, member_discount_fee, early_bird_deadline,
           max_attendees, current_attendees
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

$today = date('Y-m-d');
$is_early_bird = ($event['early_bird_deadline'] && $today <= $event['early_bird_deadline']);
$registration_fee = $is_early_bird ? $event['early_bird_fee'] : $event['standard_fee'];

$is_full = ($event['max_attendees'] && $event['current_attendees'] >= $event['max_attendees']);

$member_savings = $registration_fee - $event['member_discount_fee'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Event Registration - KNAA</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .register-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .register-header {
            background: var(--primary-blue);
            color: var(--white);
            padding: 2rem;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }

        .register-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            opacity: 0.9;
        }

        .register-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            background: var(--white);
            padding: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .register-form {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 10px;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
            font-size: 1.3rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-blue);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .register-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .event-summary {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .event-summary h3 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
            font-size: 1.3rem;
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
            color: var(--text-gray);
            font-weight: 600;
        }

        .summary-value {
            color: var(--dark-gray);
            font-weight: 600;
        }

        .price-badge {
            background: var(--primary-red);
            color: var(--white);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .early-bird-badge {
            background: #28a745;
            color: var(--white);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 0.5rem;
        }

        .total-price {
            background: var(--primary-blue);
            color: var(--white);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-top: 1rem;
        }

        .total-price .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .total-price .amount {
            font-size: 2rem;
            font-weight: bold;
            margin-top: 0.3rem;
        }

        .member-promo {
            background: var(--primary-blue);
            color: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }

        .member-promo h4 {
            font-size: 1.2rem;
            margin-bottom: 0.8rem;
        }

        .member-promo p {
            font-size: 0.95rem;
            margin-bottom: 1rem;
            opacity: 0.95;
        }

        .savings-highlight {
            background: rgba(255,255,255,0.2);
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .savings-amount {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .member-promo .btn {
            background: var(--white);
            color: var(--primary-blue);
            display: inline-block;
            margin-top: 0.5rem;
        }

        .member-promo .btn:hover {
            background: var(--light-gray);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-back {
            background: var(--text-gray);
            color: var(--white);
        }

        .btn-back:hover {
            background: var(--dark-gray);
        }

        .alert-full {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        @media (max-width: 968px) {
            .register-content {
                grid-template-columns: 1fr;
            }

            .register-sidebar {
                position: static;
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .register-container {
                margin: 1rem auto;
            }

            .register-header {
                padding: 1.5rem;
            }

            .register-header h1 {
                font-size: 1.5rem;
            }

            .register-content {
                padding: 1.5rem;
            }

            .register-form {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <div class="logo">
                <img src="./assets/images/KNAA logo-1.png" alt="KNAA Logo" class="logo-img">
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="btn-payment">Join KNAA</a></li>
            </ul>
        </div>
    </nav>

    <div class="register-container">
        <div class="register-header">
            <h1>Guest Event Registration</h1>
            <p>Register for: <?php echo htmlspecialchars($event['event_title']); ?></p>
        </div>

        <div class="register-content">
            <div class="register-form">
                <?php if ($is_full): ?>
                    <div class="alert-full">
                        This event is currently at full capacity. Please check back later or contact us for more information.
                    </div>
                <?php else: ?>
                    <form method="POST" action="event_register_handler.php">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <input type="hidden" name="registration_type" value="guest">
                        <input type="hidden" name="registration_fee" value="<?php echo $registration_fee; ?>">

                        <div class="form-section">
                            <h3>Personal Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3>Additional Information</h3>
                            
                            <div class="form-group">
                                <label for="organization">Organization/Company (Optional)</label>
                                <input type="text" id="organization" name="organization">
                            </div>

                            <div class="form-group">
                                <label for="special_requirements">Special Requirements or Dietary Restrictions (Optional)</label>
                                <textarea id="special_requirements" name="special_requirements"></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="events.php" class="btn btn-back">Back to Events</a>
                            <button type="submit" class="btn btn-payment">Proceed to Payment</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <div class="register-sidebar">
                <div class="event-summary">
                    <h3>Event Details</h3>
                    
                    <div class="summary-item">
                        <span class="summary-label">Event:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($event['event_title']); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Date:</span>
                        <span class="summary-value"><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Time:</span>
                        <span class="summary-value"><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Venue:</span>
                        <span class="summary-value"><?php echo htmlspecialchars($event['venue_name']); ?></span>
                    </div>

                    <div class="summary-item">
                        <span class="summary-label">Registration:</span>
                        <span class="summary-value">
                            <span class="price-badge">$<?php echo number_format($registration_fee, 2); ?></span>
                            <?php if ($is_early_bird): ?>
                                <span class="early-bird-badge">Early Bird</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if ($event['max_attendees']): ?>
                        <div class="summary-item">
                            <span class="summary-label">Spots Available:</span>
                            <span class="summary-value"><?php echo ($event['max_attendees'] - $event['current_attendees']); ?> / <?php echo $event['max_attendees']; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="total-price">
                        <div class="label">Total Amount</div>
                        <div class="amount">$<?php echo number_format($registration_fee, 2); ?></div>
                    </div>
                </div>

                <?php if ($member_savings > 0): ?>
                    <div class="member-promo">
                        <h4>Save as a Member!</h4>
                        <div class="savings-highlight">
                            <div>Save</div>
                            <div class="savings-amount">$<?php echo number_format($member_savings, 2); ?></div>
                            <div>on this event</div>
                        </div>
                        <p>KNAA members pay only $<?php echo number_format($event['member_discount_fee'], 2); ?> for this event plus enjoy exclusive benefits year-round.</p>
                        <a href="register.php" class="btn">Become a Member</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navMenu = document.getElementById('navMenu');

        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            mobileMenuBtn.classList.toggle('active');
        });
    </script>
</body>
</html>