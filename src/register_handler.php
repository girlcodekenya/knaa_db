<?php

require_once 'db_config.php';
require_once 'email_service.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $conn = getDBConnection();
    
    $membership_category = sanitizeInput($_POST['membership_category']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $street_address = sanitizeInput($_POST['street_address']);
    $address_line2 = sanitizeInput($_POST['address_line2']);
    $city = sanitizeInput($_POST['city']);
    $state = sanitizeInput($_POST['state']);
    $zip_code = sanitizeInput($_POST['zip_code']);
    $education_level = sanitizeInput($_POST['education_level']);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }
    
    $check_email = $conn->prepare("SELECT member_id FROM members WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();
    
    if ($result->num_rows > 0) {
        die("This email is already registered. Please use a different email or contact support.");
    }
    $check_email->close();
    
    $year = date('Y');
    
    $count_query = $conn->prepare("SELECT member_id FROM members WHERE member_id LIKE ? ORDER BY member_id DESC LIMIT 1");
    $year_pattern = "KNAA-" . $year . "-%";
    $count_query->bind_param("s", $year_pattern);
    $count_query->execute();
    $count_result = $count_query->get_result();
    
    if ($count_result->num_rows > 0) {
        $last_id = $count_result->fetch_assoc()['member_id'];
        $last_number = intval(substr($last_id, -5));
        $next_number = $last_number + 1;
    } else {
        $next_number = 1;
    }
    $count_query->close();
    
    $member_id = "KNAA-" . $year . "-" . str_pad($next_number, 5, '0', STR_PAD_LEFT);
    
    $member_since = date('Y-m-d');
    $expiration_date = date('Y-m-d', strtotime('+1 year'));
    
    $membership_type_id = ($membership_category == 'Full') ? 1 : 2;
    
    $default_password = password_hash('KNAA2024!', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO members (member_id, membership_type_id, first_name, last_name, email, phone, street_address, city, state, zip_code, password_hash, member_since, membership_expiration_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    $stmt->bind_param("sisssssssssss", $member_id, $membership_type_id, $first_name, $last_name, $email, $phone, $street_address, $city, $state, $zip_code, $default_password, $member_since, $expiration_date);
    
    if ($stmt->execute()) {
        
        if ($membership_category == 'Full') {
            $license_type = sanitizeInput($_POST['license_type']);
            $licensure_state = sanitizeInput($_POST['licensure_state']);
            
            $full_stmt = $conn->prepare("INSERT INTO full_membership_details (member_id, highest_education, license_type, licensure_state) VALUES (?, ?, ?, ?)");
            $full_stmt->bind_param("ssss", $member_id, $education_level, $license_type, $licensure_state);
            
            if (!$full_stmt->execute()) {
                error_log("Full membership details insert failed: " . $full_stmt->error);
            }
            $full_stmt->close();
            
        } else if ($membership_category == 'Student') {
            $current_school = sanitizeInput($_POST['current_school']);
            $anticipated_completion = sanitizeInput($_POST['anticipated_completion']);
            
            $student_stmt = $conn->prepare("INSERT INTO student_membership_details (member_id, highest_education, current_college_university, anticipated_completion_date) VALUES (?, ?, ?, ?)");
            $student_stmt->bind_param("ssss", $member_id, $education_level, $current_school, $anticipated_completion);
            
            if (!$student_stmt->execute()) {
                error_log("Student membership details insert failed: " . $student_stmt->error);
            }
            $student_stmt->close();
        }
        
        // Send welcome email
        $emailService = new EmailService();
        $membershipTypeName = ($membership_category == 'Full') ? 'Full Member' : 'Student Member';
        $fullName = $first_name . ' ' . $last_name;
        
        // Send welcome email (will be logged to database in dev mode, sent in production)
        $emailSent = $emailService->sendWelcomeEmail($member_id, $email, $fullName, $membershipTypeName);
        
        if (!$emailSent) {
            error_log("Welcome email failed for member: " . $member_id);
            // Continue anyway - registration is successful even if email fails
        }

        header("Location: registration_success.php?member_id=" . urlencode($member_id));
        exit();
        
    } else {
        error_log("Member insert failed: " . $stmt->error);
        die("Registration failed. Please try again or contact support.");
    }
    
    $stmt->close();
    $conn->close();
    
} else {
    header("Location: register.php");
    exit();
}
?>