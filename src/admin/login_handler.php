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
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_full_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_last_activity'] = time();
        
        updateAdminLastLogin($admin['admin_id'], $conn);
        
        if (isset($_GET['return']) && !empty($_GET['return'])) {
            $return_url = $_GET['return'];
            $allowed_pages = ['event_create.php', 'event_view.php', 'member_view.php', 'registration_view.php', 'dashboard.php'];
            $page = basename($return_url);
            
            if (in_array($page, $allowed_pages)) {
                header('Location: ' . $page);
                exit();
            }
        }
        
        header('Location: dashboard.php');
        exit();
    }
}

$stmt->close();
$conn->close();

$return_param = isset($_GET['return']) ? '&return=' . urlencode($_GET['return']) : '';
header('Location: index.php?error=1' . $return_param);
exit();
?>