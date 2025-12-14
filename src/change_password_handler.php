<?php
require_once 'session_check.php';
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: change_password.php');
    exit();
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header('Location: change_password.php?error=' . urlencode('All fields are required'));
    exit();
}

if ($new_password !== $confirm_password) {
    header('Location: change_password.php?error=' . urlencode('New passwords do not match'));
    exit();
}

if (strlen($new_password) < 8) {
    header('Location: change_password.php?error=' . urlencode('Password must be at least 8 characters long'));
    exit();
}

if (!preg_match('/[A-Z]/', $new_password)) {
    header('Location: change_password.php?error=' . urlencode('Password must contain at least one uppercase letter'));
    exit();
}

if (!preg_match('/[a-z]/', $new_password)) {
    header('Location: change_password.php?error=' . urlencode('Password must contain at least one lowercase letter'));
    exit();
}

if (!preg_match('/[0-9]/', $new_password)) {
    header('Location: change_password.php?error=' . urlencode('Password must contain at least one number'));
    exit();
}

if ($current_password === $new_password) {
    header('Location: change_password.php?error=' . urlencode('New password must be different from current password'));
    exit();
}

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT password_hash FROM members WHERE member_id = ? AND is_active = 1");
$stmt->bind_param("s", $_SESSION['member_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header('Location: change_password.php?error=' . urlencode('Account not found'));
    exit();
}

$member = $result->fetch_assoc();
$stmt->close();

if (!password_verify($current_password, $member['password_hash'])) {
    $conn->close();
    header('Location: change_password.php?error=' . urlencode('Current password is incorrect'));
    exit();
}

$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE members SET password_hash = ?, updated_at = NOW() WHERE member_id = ?");
$stmt->bind_param("ss", $new_password_hash, $_SESSION['member_id']);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: change_password.php?success=1');
    exit();
} else {
    $stmt->close();
    $conn->close();
    header('Location: change_password.php?error=' . urlencode('Failed to update password. Please try again.'));
    exit();
}
?>