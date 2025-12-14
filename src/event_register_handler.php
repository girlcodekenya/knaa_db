<?php
session_start();
require_once 'db_config.php';
$conn = getDBConnection();
require_once 'email_service.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events.php");
    $emailService = new EmailService();
    $emailService->sendEventRegistrationConfirmation($registrationId);
    exit();
}

// Get form data
$event_id = intval($_POST['event_id']);
$registration_fee = floatval($_POST['registration_fee']);
$is_guest = isset($_POST['is_guest']) && $_POST['is_guest'] == '1';
$member_id = $is_guest ? null : (isset($_POST['member_id']) ? $_POST['member_id'] : null);

// Sanitize input
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$street_address = isset($_POST['street_address']) ? trim($_POST['street_address']) : null;
$city = isset($_POST['city']) ? trim($_POST['city']) : null;
$state = isset($_POST['state']) ? trim($_POST['state']) : null;
$zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : null;

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: " . ($is_guest ? "event_register_guest.php" : "event_register.php") . "?id=" . $event_id);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Please enter a valid email address.";
    header("Location: " . ($is_guest ? "event_register_guest.php" : "event_register.php") . "?id=" . $event_id);
    exit();
}

// Check if event exists and is active
$event_check = $conn->prepare("SELECT event_id, max_attendees, current_attendees, event_title FROM events WHERE event_id = ? AND is_active = 1");
$event_check->bind_param("i", $event_id);
$event_check->execute();
$event_result = $event_check->get_result();

if ($event_result->num_rows === 0) {
    $_SESSION['error'] = "Event not found or no longer available.";
    header("Location: events.php");
    exit();
}

$event = $event_result->fetch_assoc();
$event_check->close();

// Check if event is full
if ($event['current_attendees'] >= $event['max_attendees']) {
    $_SESSION['error'] = "Sorry, this event is now full.";
    header("Location: event_details.php?id=" . $event_id);
    exit();
}

// For members, check if already registered
if (!$is_guest && $member_id) {
    $duplicate_check = $conn->prepare("SELECT registration_id FROM event_registrations WHERE event_id = ? AND member_id = ?");
    $duplicate_check->bind_param("is", $event_id, $member_id);
    $duplicate_check->execute();
    $duplicate_result = $duplicate_check->get_result();
    
    if ($duplicate_result->num_rows > 0) {
        $_SESSION['error'] = "You are already registered for this event.";
        header("Location: event_details.php?id=" . $event_id);
        exit();
    }
    $duplicate_check->close();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert registration
    $stmt = $conn->prepare("
        INSERT INTO event_registrations (
            event_id,
            member_id,
            first_name,
            last_name,
            email,
            phone,
            street_address,
            city,
            state,
            zip_code,
            registration_fee,
            payment_status,
            payment_method,
            registration_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL, NOW())
    ");
    
    $stmt->bind_param(
        "isssssssssd",
        $event_id,
        $member_id,
        $first_name,
        $last_name,
        $email,
        $phone,
        $street_address,
        $city,
        $state,
        $zip_code,
        $registration_fee
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create registration: " . $stmt->error);
    }
    
    $registration_id = $stmt->insert_id;
    $stmt->close();
    
    // Update event attendee count
    $update_stmt = $conn->prepare("UPDATE events SET current_attendees = current_attendees + 1 WHERE event_id = ?");
    $update_stmt->bind_param("i", $event_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update attendee count: " . $update_stmt->error);
    }
    $update_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Store registration details in session for payment page
    $_SESSION['pending_registration'] = [
        'registration_id' => $registration_id,
        'event_id' => $event_id,
        'event_title' => $event['event_title'],
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'registration_fee' => $registration_fee,
        'is_guest' => $is_guest
    ];
    
    // Redirect to payment page
    header("Location: event_payment.php?registration_id=" . $registration_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("Registration Error: " . $e->getMessage());
    
    $_SESSION['error'] = "An error occurred while processing your registration. Please try again.";
    header("Location: " . ($is_guest ? "event_register_guest.php" : "event_register.php") . "?id=" . $event_id);
    exit();
}

$conn->close();
?>