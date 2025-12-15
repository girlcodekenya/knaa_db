<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php?return=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

$member_id = $_GET['id'] ?? null;
if (!$member_id) {
    header('Location: members.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $street_address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $membership_type_id = intval($_POST['member_type']);
    $is_active = $_POST['membership_status'] === 'active' ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE members SET 
        first_name = ?, last_name = ?, email = ?, phone = ?, 
        street_address = ?, city = ?, state = ?, zip_code = ?,
        membership_type_id = ?, is_active = ?, updated_at = NOW()
        WHERE member_id = ?");
    
    $stmt->bind_param("ssssssssiis",
        $first_name, $last_name, $email, $phone,
        $street_address, $city, $state, $zip_code,
        $membership_type_id, $is_active, $member_id
    );
    
    if ($stmt->execute()) {
        $message = 'Member updated successfully!';
    } else {
        $error = 'Failed to update member.';
    }
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT m.*, mt.type_name 
    FROM members m
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.type_id
    WHERE m.member_id = ?
");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();
$stmt->close();

if (!$member) {
    header('Location: members.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT mt.type_id, mt.type_name
    FROM membership_types mt
    WHERE mt.is_active = 1
    ORDER BY mt.type_name
");
$stmt->execute();
$result = $stmt->get_result();
$membership_types = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("
    SELECT er.*, e.event_title, e.event_date 
    FROM event_registrations er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.member_id = ?
    ORDER BY e.event_date DESC
");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("
    SELECT p.*, mt.type_name 
    FROM membership_payments p
    LEFT JOIN membership_types mt ON p.membership_type_id = mt.type_id
    WHERE p.member_id = ?
    ORDER BY p.payment_date DESC
");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Details - KNAA Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .admin-header {
            background: #0052A5;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .admin-nav {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0 2rem;
        }

        .admin-nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
        }

        .admin-nav a {
            display: block;
            padding: 1rem 0;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            color: #0052A5;
            border-bottom-color: #0052A5;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-size: 1.8rem;
            color: #333;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-primary {
            background: #0052A5;
            color: white;
        }

        .btn-primary:hover {
            background: #003d7a;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card.full-width {
            grid-column: 1 / -1;
        }

        .card h3 {
            font-size: 1.2rem;
            color: #0052A5;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0052A5;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.2rem;
        }

        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }

        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .admin-nav ul {
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>KNAA Admin Panel</h1>
    </div>

    <nav class="admin-nav">
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="members.php" class="active">Members</a></li>
            <li><a href="events.php">Events</a></li>
            <li><a href="registrations.php">Registrations</a></li>
            <li><a href="emails.php">Email Logs</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></h2>
            <a href="members.php" class="btn btn-secondary">← Back to Members</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <h3>Member Information</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($member['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($member['street_address']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($member['city']); ?>">
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($member['state']); ?>" maxlength="2">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ZIP Code</label>
                        <input type="text" name="zip_code" value="<?php echo htmlspecialchars($member['zip_code']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Member Type</label>
                            <select name="member_type" required>
                                <?php foreach ($membership_types as $type): ?>
                                    <option value="<?php echo $type['type_id']; ?>" 
                                        <?php echo $member['membership_type_id'] == $type['type_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Membership Status</label>
                            <select name="membership_status" required>
                                <option value="active" <?php echo $member['is_active'] ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo !$member['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="members.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>Account Details</h3>
                <div class="info-item">
                    <div class="info-label">Member ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($member['member_id']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="badge <?php echo $member['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Member Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($member['type_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Member Since</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($member['member_since'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Expiration Date</div>
                    <div class="info-value">
                        <?php 
                        if ($member['membership_expiration_date']) {
                            echo date('F j, Y', strtotime($member['membership_expiration_date']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($member['updated_at'])); ?></div>
                </div>
            </div>

            <div class="card full-width">
                <h3>Event Registrations (<?php echo count($registrations); ?>)</h3>
                <?php if (count($registrations) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Event Date</th>
                                <th>Registration Date</th>
                                <th>Payment Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($reg['event_date'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($reg['registration_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $reg['payment_status'] === 'completed' ? 'badge-completed' : 'badge-pending'; ?>">
                                            <?php echo ucfirst($reg['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($reg['registration_fee'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">No event registrations found.</p>
                <?php endif; ?>
            </div>

            <div class="card full-width">
                <h3>Payment History (<?php echo count($payments); ?>)</h3>
                <?php if (count($payments) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Membership Type</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo ucfirst($payment['payment_type']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['type_name']); ?></td>
                                    <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $payment['payment_status'] === 'completed' ? 'badge-completed' : 'badge-pending'; ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">No payment history found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>