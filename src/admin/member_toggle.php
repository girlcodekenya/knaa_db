<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/includes/admin_auth.php';

requireAdminLogin();
$conn = getDBConnection();
$admin = getAdminUser();

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header('Location: members.php');
    exit();
}

$member_id = $_GET['id'];
$action = (int)$_GET['action'];

$stmt = $conn->prepare("SELECT first_name, last_name, is_active FROM members WHERE member_id = ?");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: members.php?error=not_found');
    exit();
}

$member = $result->fetch_assoc();
$old_value = $member['is_active'] ? 'active' : 'inactive';
$new_value = $action ? 'active' : 'inactive';

$stmt = $conn->prepare("UPDATE members SET is_active = ? WHERE member_id = ?");
$stmt->bind_param("is", $action, $member_id);

if ($stmt->execute()) {
    logAdminAction(
        $admin['admin_id'],
        $action ? 'activated_member' : 'deactivated_member',
        'members',
        $member_id,
        $old_value,
        $new_value,
        $conn
    );
    
    header('Location: members.php?success=' . ($action ? 'activated' : 'deactivated'));
} else {
    header('Location: members.php?error=update_failed');
}

$stmt->close();
$conn->close();
exit();
?>