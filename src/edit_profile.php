<?php
require_once 'session_check.php';
require_once 'db_config.php';

$conn = getDBConnection();

$member_id = $_SESSION['member_id'];
$query = "SELECT m.*, mt.type_name
          FROM members m 
          LEFT JOIN membership_types mt ON m.membership_type_id = mt.type_id
          WHERE m.member_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

$full_details = null;
$student_details = null;

if ($member['membership_type_id'] == 1) {
    $full_query = "SELECT * FROM full_membership_details WHERE member_id = ?";
    $full_stmt = $conn->prepare($full_query);
    $full_stmt->bind_param("i", $member_id);
    $full_stmt->execute();
    $full_details = $full_stmt->get_result()->fetch_assoc();
} else {
    $student_query = "SELECT * FROM student_membership_details WHERE member_id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $member_id);
    $student_stmt->execute();
    $student_details = $student_stmt->get_result()->fetch_assoc();
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - KNAA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #0052A5;
            --primary-red: #DC143C;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --text-gray: #555555;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-gray);
            min-height: 100vh;
            padding: 20px;
        }

        .navbar {
            background: #ffe8e8;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-blue);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--dark-gray);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: var(--primary-red);
        }

        .container {
            max-width: 800px;
            margin: 100px auto 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: var(--primary-blue);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .nav-back {
            display: inline-block;
            margin: 20px 30px;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-back:hover {
            color: var(--primary-red);
        }

        .content {
            padding: 30px;
        }

        .message {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 18px;
            color: var(--primary-blue);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--dark-gray);
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        input[readonly] {
            background: var(--light-gray);
            cursor: not-allowed;
            color: var(--text-gray);
        }

        .btn-container {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--light-gray);
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-red);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 20, 60, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .container {
                margin-top: 80px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .btn-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">KNAA</div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Edit Profile</h1>
            <p>Update your contact and professional information</p>
        </div>

        <a href="dashboard.php" class="nav-back">&larr; Back to Dashboard</a>

        <div class="content">
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="edit_profile_handler.php">
                <div class="form-section">
                    <h2>Personal Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($member['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($member['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Address</h2>
                    <div class="form-group">
                        <label for="address">Street Address *</label>
                        <input type="text" id="address" name="address" 
                               value="<?php echo htmlspecialchars($member['address'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($member['city'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State *</label>
                            <input type="text" id="state" name="state" 
                                   value="<?php echo htmlspecialchars($member['state'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">ZIP Code *</label>
                            <input type="text" id="zip_code" name="zip_code" 
                                   value="<?php echo htmlspecialchars($member['zip_code'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country *</label>
                            <input type="text" id="country" name="country" 
                                   value="<?php echo htmlspecialchars($member['country'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <?php if ($member['membership_type_id'] == 1 && $full_details): ?>
                <div class="form-section">
                    <h2>Professional Information</h2>
                    <div class="form-group">
                        <label for="membership_type">Membership Type</label>
                        <input type="text" value="Full Member" readonly>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="license_number">License Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($full_details['license_number'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="license_state">License State</label>
                            <input type="text" value="<?php echo htmlspecialchars($full_details['license_state'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization" 
                               value="<?php echo htmlspecialchars($full_details['specialization'] ?? ''); ?>">
                    </div>
                </div>
                <?php elseif ($student_details): ?>
                <div class="form-section">
                    <h2>Student Information</h2>
                    <div class="form-group">
                        <label for="membership_type">Membership Type</label>
                        <input type="text" value="Student Member" readonly>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="school_name">School Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($student_details['school_name'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="expected_graduation">Expected Graduation</label>
                            <input type="text" value="<?php echo htmlspecialchars($student_details['expected_graduation_year'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="btn-container">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>