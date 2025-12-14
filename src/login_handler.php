<?php
session_start();
require_once 'db_config.php';

$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$email = sanitizeInput($_POST['email']);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = "Please enter both email and password.";
    $_SESSION['login_email'] = $email;
    header("Location: login.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = "Invalid email format.";
    $_SESSION['login_email'] = $email;
    header("Location: login.php");
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            m.member_id,
            m.first_name,
            m.last_name,
            m.email,
            m.password_hash,
            m.membership_type_id,
            m.is_active,
            m.member_since,
            m.membership_expiration_date,
            mt.type_name
        FROM members m
        LEFT JOIN membership_types mt ON m.membership_type_id = mt.type_id
        WHERE m.email = ?
    ");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['login_error'] = "Invalid email or password.";
        $_SESSION['login_email'] = $email;
        header("Location: login.php");
        exit();
    }
    
    $member = $result->fetch_assoc();
    
    if (!$member['is_active']) {
        $_SESSION['login_error'] = "Your account has been deactivated. Please contact support.";
        $_SESSION['login_email'] = $email;
        header("Location: login.php");
        exit();
    }
    
    if (!password_verify($password, $member['password_hash'])) {
        $_SESSION['login_error'] = "Invalid email or password.";
        $_SESSION['login_email'] = $email;
        header("Location: login.php");
        exit();
    }
    
    $_SESSION['member_id'] = $member['member_id'];
    $_SESSION['first_name'] = $member['first_name'];
    $_SESSION['last_name'] = $member['last_name'];
    $_SESSION['email'] = $member['email'];
    $_SESSION['membership_type_id'] = $member['membership_type_id'];
    $_SESSION['membership_type_name'] = $member['type_name'];
    $_SESSION['member_since'] = $member['member_since'];
    $_SESSION['membership_expiration_date'] = $member['membership_expiration_date'];
    $_SESSION['last_activity'] = time();
    
    $default_password = "KNAA2024!";
    if (password_verify($default_password, $member['password_hash'])) {
        $_SESSION['must_change_password'] = true;
        header("Location: change_password.php");
        exit();
    }
    
    header("Location: dashboard.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['login_error'] = "An error occurred. Please try again.";
    $_SESSION['login_email'] = $email;
    header("Location: login.php");
    exit();
}

$stmt->close();
$conn->close();
?>