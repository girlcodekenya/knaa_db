<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Start a new session for the message
session_start();
$_SESSION['login_error'] = "You have been logged out successfully.";

// Redirect to login page
header("Location: login.php");
exit();
?>