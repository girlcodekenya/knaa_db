<?php
require_once 'session_check.php';
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit_profile.php');
    exit();
}

$conn = getDBConnection();
$member_id = $_SESSION['member_id'];

$first_name = sanitizeInput($_POST['first_name']);
$last_name = sanitizeInput($_POST['last_name']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone']);
$address = sanitizeInput($_POST['address']);
$city = sanitizeInput($_POST['city']);
$state = sanitizeInput($_POST['state']);
$zip_code = sanitizeInput($_POST['zip_code']);
$country = sanitizeInput($_POST['country']);

if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || 
    empty($address) || empty($city) || empty($state) || empty($zip_code) || empty($country)) {
    $_SESSION['error'] = 'All required fields must be filled out.';
    header('Location: edit_profile.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header('Location: edit_profile.php');
    exit();
}

$check_query = "SELECT member_id FROM members WHERE email = ? AND member_id != ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("si", $email, $member_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error'] = 'This email address is already in use by another member.';
    header('Location: edit_profile.php');
    exit();
}

$conn->begin_transaction();

try {
    $update_member = "UPDATE members SET 
                      first_name = ?, 
                      last_name = ?, 
                      email = ?, 
                      phone = ?, 
                      address = ?, 
                      city = ?, 
                      state = ?, 
                      zip_code = ?, 
                      country = ?
                      WHERE member_id = ?";
    
    $stmt = $conn->prepare($update_member);
    $stmt->bind_param("sssssssssi", 
        $first_name, $last_name, $email, $phone, 
        $address, $city, $state, $zip_code, $country, 
        $member_id
    );
    $stmt->execute();

    $membership_query = "SELECT membership_type_id FROM members WHERE member_id = ?";
    $membership_stmt = $conn->prepare($membership_query);
    $membership_stmt->bind_param("i", $member_id);
    $membership_stmt->execute();
    $membership_result = $membership_stmt->get_result();
    $membership = $membership_result->fetch_assoc();

    if ($membership['membership_type_id'] == 1) {
        $specialization = isset($_POST['specialization']) ? sanitizeInput($_POST['specialization']) : '';
        
        $update_full = "UPDATE full_membership_details SET specialization = ? WHERE member_id = ?";
        $full_stmt = $conn->prepare($update_full);
        $full_stmt->bind_param("si", $specialization, $member_id);
        $full_stmt->execute();
    }

    $conn->commit();
    
    $_SESSION['success'] = 'Profile updated successfully!';
    header('Location: edit_profile.php');
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'An error occurred while updating your profile. Please try again.';
    header('Location: edit_profile.php');
    exit();
}
?>