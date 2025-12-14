<?php
session_start();
require_once 'db_config.php';
require_once 'email_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email address';
    header('Location: forgot_password.php');
    exit;
}

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT member_id, first_name FROM members WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    if ($member) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $insert_stmt = $conn->prepare("INSERT INTO password_resets (member_id, token, expires_at) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $member['member_id'], $token, $expires_at);
        $insert_stmt->execute();
        $insert_stmt->close();

        $emailService = new EmailService();
        $emailService->sendPasswordResetEmail($email, $token);
    }

    $_SESSION['success'] = 'If an account exists with that email, a password reset link has been sent.';
    header('Location: forgot_password.php');
    exit;

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: forgot_password.php');
    exit;
}

$stmt->close();
$conn->close();