<?php
require_once 'email_service.php';

// This page lets you preview email templates before sending
$emailService = new EmailService();

// Get the template type from URL
$type = isset($_GET['type']) ? $_GET['type'] : 'welcome';

// Reflection to access private method for preview
$reflection = new ReflectionClass($emailService);
$method = $reflection->getMethod('getEmailTemplate');
$method->setAccessible(true);

// Sample data for each template type
$templates = [
    'welcome' => [
        'title' => 'Welcome to KNAA!',
        'content' => "
            <p>Dear Jane Doe,</p>
            <p>Welcome to the Kenyan Nurses Association of America! We're thrilled to have you join our community of dedicated healthcare professionals.</p>
            <p><strong>Your Membership Details:</strong></p>
            <ul>
                <li>Member ID: KNAA-2025-00123</li>
                <li>Membership Type: Full Member</li>
                <li>Email: jane.doe@example.com</li>
            </ul>
            <p>As a KNAA member, you now have access to:</p>
            <ul>
                <li>Professional networking opportunities</li>
                <li>Continuing education resources</li>
                <li>Career development support</li>
                <li>Exclusive member events</li>
                <li>Advocacy and mentorship programs</li>
            </ul>
            <p>Visit your member dashboard to explore all the benefits available to you.</p>
        ",
        'button_text' => 'Go to Dashboard',
        'button_link' => 'http://localhost:8080/src/dashboard.php'
    ],
    'event_confirmation' => [
        'title' => 'Event Registration Confirmed!',
        'content' => "
            <p>Dear John Smith,</p>
            <p>Your registration for <strong>End Year KNAA Gala</strong> has been confirmed!</p>
            <p><strong>Event Details:</strong></p>
            <ul>
                <li><strong>Date:</strong> Saturday, December 6, 2025</li>
                <li><strong>Time:</strong> 6:00 PM</li>
                <li><strong>Location:</strong> The Grand Sapphire, 800 Rahway Ave, Woodbridge, NJ 07095</li>
                <li><strong>Registration Fee:</strong> $130.00</li>
            </ul>
            <p><strong>Payment Status:</strong> Pending</p>
            <p><em>Please complete your payment to secure your spot. Your registration will be confirmed once payment is received.</em></p>
            <p>We look forward to seeing you at the event!</p>
        ",
        'button_text' => 'View Event Details',
        'button_link' => 'http://localhost:8080/src/event_details.php?id=1'
    ],
    'payment_receipt' => [
        'title' => 'Payment Receipt',
        'content' => "
            <p>Dear Jane Doe,</p>
            <p>Thank you for your payment. This email confirms your membership payment to the Kenyan Nurses Association of America.</p>
            <p><strong>Payment Details:</strong></p>
            <ul>
                <li><strong>Receipt Number:</strong> #KNAA-456</li>
                <li><strong>Payment Date:</strong> December 14, 2025</li>
                <li><strong>Amount Paid:</strong> $100.00</li>
                <li><strong>Payment Type:</strong> Renewal</li>
                <li><strong>Payment Method:</strong> Credit Card</li>
                <li><strong>Membership Type:</strong> Full Member</li>
                <li><strong>Coverage Period:</strong> January 1, 2026 - December 31, 2026</li>
            </ul>
            <p>Your membership is now active and you have full access to all KNAA benefits.</p>
            <p>If you have any questions about this payment, please contact us at info@kenyannursesusa.org.</p>
        ",
        'button_text' => 'View Dashboard',
        'button_link' => 'http://localhost:8080/src/dashboard.php'
    ],
    'expiry_reminder' => [
        'title' => 'Membership Renewal Reminder',
        'content' => "
            <p>Dear John Smith,</p>
            <p>This is a friendly reminder that your KNAA membership will expire in <strong>30 days</strong> on January 15, 2026.</p>
            <p>Don't miss out on these valuable benefits:</p>
            <ul>
                <li>Professional networking opportunities</li>
                <li>Access to continuing education resources</li>
                <li>Exclusive member events and conferences</li>
                <li>Career development support</li>
                <li>Advocacy representation</li>
            </ul>
            <p>Renew your membership today to ensure uninterrupted access to all KNAA benefits.</p>
        ",
        'button_text' => 'Renew Membership',
        'button_link' => 'http://localhost:8080/payments.html'
    ]
];

$templateData = $templates[$type];
$html = $method->invoke($emailService, $templateData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template Preview - KNAA</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .preview-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        .preview-header {
            background: #ffe8e8;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .preview-header h1 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        .template-selector {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .template-selector a {
            padding: 0.7rem 1.5rem;
            background: white;
            color: var(--primary-blue);
            text-decoration: none;
            border-radius: 5px;
            border: 2px solid var(--primary-blue);
            transition: all 0.3s;
        }
        .template-selector a:hover,
        .template-selector a.active {
            background: var(--primary-blue);
            color: white;
        }
        .email-preview {
            background: #f5f5f5;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .preview-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <div class="logo">
                <img src="../assets/images/KNAA logo-1.png" alt="KNAA Logo" class="logo-img">
            </div>
            <ul class="nav-menu">
                <li><a href="../index.html">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
            </ul>
        </div>
    </nav>

    <div class="preview-container">
        <div class="preview-header">
            <h1>📧 Email Template Preview</h1>
            <p>Select a template to preview how emails will look when sent to members:</p>
            <div class="template-selector">
                <a href="?type=welcome" class="<?php echo $type == 'welcome' ? 'active' : ''; ?>">Welcome Email</a>
                <a href="?type=event_confirmation" class="<?php echo $type == 'event_confirmation' ? 'active' : ''; ?>">Event Confirmation</a>
                <a href="?type=payment_receipt" class="<?php echo $type == 'payment_receipt' ? 'active' : ''; ?>">Payment Receipt</a>
                <a href="?type=expiry_reminder" class="<?php echo $type == 'expiry_reminder' ? 'active' : ''; ?>">Expiry Reminder</a>
            </div>
        </div>

        <div class="preview-note">
            <strong>ℹ️ Note:</strong> This is a preview of the email template. In development mode (EMAIL_ENABLED=false), emails are only logged to the database. In production (EMAIL_ENABLED=true), these emails will be sent via SendGrid.
        </div>

        <div class="email-preview">
            <?php echo $html; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 Kenyan Nurses Association of America. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>