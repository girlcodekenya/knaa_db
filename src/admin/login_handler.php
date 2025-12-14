<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $conn->prepare("
    SELECT admin_id, username, email, password_hash, full_name, role, is_active 
    FROM admin_users 
    WHERE username = ? AND is_active = 1
");

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    
    if (password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_full_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_last_activity'] = time();
        
        updateAdminLastLogin($admin['admin_id'], $conn);
        
        header('Location: dashboard.php');
        exit();
    }
}

$stmt->close();
$conn->close();

header('Location: index.php?error=1');
exit();
?>