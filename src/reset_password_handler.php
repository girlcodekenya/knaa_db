<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password) || empty($confirm_password)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error'] = 'Password must be at least 8 characters long';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT pr.reset_id, pr.member_id, pr.expires_at, pr.used
        FROM password_resets pr
        WHERE pr.token = ? AND pr.used = 0
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Invalid or already used reset link';
        header('Location: forgot_password.php');
        exit;
    }

    if (strtotime($reset['expires_at']) < time()) {
        $pdo->rollBack();
        $_SESSION['error'] = 'This reset link has expired';
        header('Location: forgot_password.php');
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("UPDATE members SET password_hash = ?, updated_at = NOW() WHERE member_id = ?");
    $stmt->execute([$password_hash, $reset['member_id']]);

    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE reset_id = ?");
    $stmt->execute([$reset['reset_id']]);

    $pdo->commit();

    $_SESSION['success'] = 'Your password has been reset successfully. You can now log in with your new password.';
    header('Location: login.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Password reset error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit;
}