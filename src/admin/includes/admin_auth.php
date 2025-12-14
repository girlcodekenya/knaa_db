<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit();
    }
    
    if (isset($_SESSION['admin_last_activity'])) {
        $inactive_time = time() - $_SESSION['admin_last_activity'];
        if ($inactive_time > 1800) {
            session_unset();
            session_destroy();
            header('Location: index.php?timeout=1');
            exit();
        }
    }
    
    $_SESSION['admin_last_activity'] = time();
}

function getAdminUser() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    return [
        'admin_id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'email' => $_SESSION['admin_email'],
        'full_name' => $_SESSION['admin_full_name'],
        'role' => $_SESSION['admin_role']
    ];
}

function updateAdminLastLogin($admin_id, $conn) {
    $stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->close();
}

function logAdminAction($admin_id, $action, $target_table, $target_id, $old_value, $new_value, $conn) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("
        INSERT INTO admin_logs (admin_id, action, target_table, target_id, old_value, new_value, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("issssss", $admin_id, $action, $target_table, $target_id, $old_value, $new_value, $ip_address);
    $stmt->execute();
    $stmt->close();
}
?>