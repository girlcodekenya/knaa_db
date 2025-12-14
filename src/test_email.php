<?php
require_once 'email_service.php';

$emailService = new EmailService();

echo "Testing email service...\n\n";

$result = $emailService->sendEmail(
    'test@example.com',
    'Test User',
    'Test Email - KNAA',
    '<h1>Test Email</h1><p>This is a test from KNAA email system.</p>',
    'test',
    null
);

echo $result ? "✅ Email logged successfully (dev mode - not actually sent)\n" : "❌ Email failed\n";

echo "\nChecking database logs...\n";
require_once 'db_config.php';
$conn = getDBConnection();
$result = $conn->query("SELECT * FROM email_logs ORDER BY sent_date DESC LIMIT 1");
$log = $result->fetch_assoc();

if ($log) {
    echo "Latest email log:\n";
    echo "- Email: " . $log['email_address'] . "\n";
    echo "- Type: " . $log['email_type'] . "\n";
    echo "- Status: " . $log['status'] . "\n";
    echo "- Date: " . $log['sent_date'] . "\n";
} else {
    echo "No email logs found.\n";
}
?>