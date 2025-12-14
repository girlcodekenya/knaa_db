<?php
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/email_service.php';

$conn = getDBConnection();
$emailService = new EmailService();

echo "Starting automated reminder system...\n";

$today = date('Y-m-d');
$sevenDaysFromNow = date('Y-m-d', strtotime('+7 days'));

echo "\n=== Checking for Event Reminders ===\n";
$stmt = $conn->prepare("
    SELECT event_id, event_title, event_date 
    FROM events 
    WHERE event_date = ? AND is_active = 1
");
$stmt->bind_param("s", $sevenDaysFromNow);
$stmt->execute();
$upcomingEvents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($upcomingEvents as $event) {
    echo "Sending reminders for: {$event['event_title']}\n";
    $sentCount = $emailService->sendEventReminder($event['event_id'], 7);
    echo "Sent {$sentCount} reminder emails\n";
}

if (empty($upcomingEvents)) {
    echo "No events in 7 days requiring reminders\n";
}

echo "\n=== Checking for Membership Expiry Reminders ===\n";
$stmt = $conn->prepare("
    SELECT member_id, first_name, last_name, email, membership_expiration_date, reminder_days_before_expiry
    FROM members 
    WHERE is_active = 1 
    AND email_notifications_enabled = 1
    AND membership_expiration_date IS NOT NULL
");
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$remindersSent = 0;
foreach ($members as $member) {
    $reminderDays = $member['reminder_days_before_expiry'] ?? 30;
    $reminderDate = date('Y-m-d', strtotime($member['membership_expiration_date'] . " -{$reminderDays} days"));
    
    if ($today === $reminderDate) {
        echo "Sending expiry reminder to: {$member['first_name']} {$member['last_name']} ({$member['email']})\n";
        if ($emailService->sendMembershipExpiryReminder($member['member_id'])) {
            $remindersSent++;
        }
    }
}

echo "Sent {$remindersSent} membership expiry reminders\n";

if ($remindersSent === 0 && empty($upcomingEvents)) {
    echo "No reminders sent today\n";
}

echo "\n=== Reminder System Complete ===\n";
echo "Run time: " . date('Y-m-d H:i:s') . "\n";