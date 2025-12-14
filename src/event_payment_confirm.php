<?php
session_start();
require_once 'db_config.php';
require_once 'email_service.php';

$conn = getDBConnection();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events.php");
    exit();
}

$registration_id = intval($_POST['registration_id']);
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'zelle';

// Verify registration exists
$stmt = $conn->prepare("
    SELECT 
        er.registration_id,
        er.event_id,
        er.email,
        er.first_name,
        er.last_name,
        er.registration_fee,
        er.payment_status,
        e.event_title,
        e.event_date
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

// Update payment status to pending (awaiting manual confirmation)
$update_stmt = $conn->prepare("
    UPDATE event_registrations 
    SET payment_method = ?, 
        payment_status = 'pending',
        payment_date = NOW() 
    WHERE registration_id = ?
");

$update_stmt->bind_param("si", $payment_method, $registration_id);

if ($update_stmt->execute()) {
    
    // Send event registration confirmation email
    $emailService = new EmailService();
    $emailSent = $emailService->sendEventRegistrationConfirmation($registration_id);
    
    if (!$emailSent) {
        error_log("Event confirmation email failed for registration ID: " . $registration_id);
        // Continue anyway - registration is successful even if email fails
    }
    
    $_SESSION['success'] = "Payment notification received! We'll confirm your registration within 24 hours. A confirmation email has been sent to " . htmlspecialchars($registration['email']);
    
    // Clear pending registration from session
    unset($_SESSION['pending_registration']);
    
    // Redirect to confirmation page
    header("Location: event_confirmation.php?registration_id=" . $registration_id);
} else {
    $_SESSION['error'] = "Error processing payment notification. Please contact us.";
    header("Location: event_payment.php?registration_id=" . $registration_id);
}

$update_stmt->close();
$conn->close();
exit();
?>