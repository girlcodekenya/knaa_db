<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php?return=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

$registration_id = $_GET['id'] ?? null;
if (!$registration_id) {
    header('Location: registrations.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_status = $_POST['payment_status'];
    $registration_fee = floatval($_POST['amount_paid']);
    $payment_method = $_POST['payment_method'];
    
    $stmt = $conn->prepare("UPDATE event_registrations SET 
        payment_status = ?, registration_fee = ?, payment_method = ?
        WHERE registration_id = ?");
    
    $stmt->bind_param("sdsi", $payment_status, $registration_fee, $payment_method, $registration_id);
    
    if ($stmt->execute()) {
        $message = 'Registration updated successfully!';
    } else {
        $error = 'Failed to update registration.';
    }
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT er.*, 
           m.first_name as member_first_name, 
           m.last_name as member_last_name,
           m.email as member_email, 
           m.phone as member_phone, 
           m.membership_type_id,
           m.is_active as member_is_active,
           mt.type_name as member_type,
           e.event_title, e.event_date, e.event_time, 
           e.city, e.state, e.venue_name,
           e.standard_fee, e.early_bird_fee
    FROM event_registrations er
    LEFT JOIN members m ON er.member_id = m.member_id
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.type_id
    JOIN events e ON er.event_id = e.event_id
    WHERE er.registration_id = ?
");
$stmt->bind_param("i", $registration_id);
$stmt->execute();
$result = $stmt->get_result();
$registration = $result->fetch_assoc();
$stmt->close();

if (!$registration) {
    $conn->close();
    header('Location: registrations.php');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Details - KNAA Admin</title>
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
            max-width: 1200px;
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
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0052A5;
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-refunded {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }

        .link-button {
            color: #0052A5;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .link-button:hover {
            color: #003d7a;
            text-decoration: underline;
        }

        @media (max-width: 968px) {
            .content-grid {
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
            <li><a href="members.php">Members</a></li>
            <li><a href="events.php">Events</a></li>
            <li><a href="registrations.php" class="active">Registrations</a></li>
            <li><a href="emails.php">Email Logs</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Registration #<?php echo $registration_id; ?></h2>
            <a href="registrations.php" class="btn btn-secondary">← Back to Registrations</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <h3>Event Information</h3>
                <div class="info-item">
                    <div class="info-label">Event Name</div>
                    <div class="info-value">
                        <a href="event_view.php?id=<?php echo $registration['event_id']; ?>" class="link-button">
                            <?php echo htmlspecialchars($registration['event_title']); ?>
                        </a>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Event Date & Time</div>
                    <div class="info-value">
                        <?php echo date('F j, Y', strtotime($registration['event_date'])); ?> at 
                        <?php echo date('g:i A', strtotime($registration['event_time'])); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Location</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($registration['city'] . ', ' . $registration['state']); ?>
                    </div>
                </div>
                <?php if ($registration['venue_name']): ?>
                    <div class="info-item">
                        <div class="info-label">Venue</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['venue_name']); ?></div>
                    </div>
                <?php endif; ?>
                <div class="info-item">
                    <div class="info-label">Standard Fee</div>
                    <div class="info-value">$<?php echo number_format($registration['standard_fee'], 2); ?></div>
                </div>
                <?php if ($registration['early_bird_fee']): ?>
                    <div class="info-item">
                        <div class="info-label">Early Bird Fee</div>
                        <div class="info-value">$<?php echo number_format($registration['early_bird_fee'], 2); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Attendee Information</h3>
                <div class="info-item">
                    <div class="info-label">Name</div>
                    <div class="info-value">
                        <?php 
                        $display_name = '';
                        if ($registration['member_id']) {
                            $display_name = htmlspecialchars($registration['member_first_name'] . ' ' . $registration['member_last_name']);
                            echo '<a href="member_view.php?id=' . urlencode($registration['member_id']) . '" class="link-button">' . $display_name . '</a>';
                        } else {
                            echo htmlspecialchars($registration['first_name'] . ' ' . $registration['last_name']);
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($registration['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($registration['phone']); ?></div>
                </div>
                <?php if ($registration['member_id']): ?>
                    <div class="info-item">
                        <div class="info-label">Member Type</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['member_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Membership Status</div>
                        <div class="info-value">
                            <span class="badge <?php echo $registration['member_is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $registration['member_is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info-item">
                        <div class="info-label">Member Type</div>
                        <div class="info-value">Guest Registration</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card full-width">
                <h3>Payment & Registration Details</h3>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <div class="info-item">
                                <div class="info-label">Registration Date</div>
                                <div class="info-value">
                                    <?php echo date('F j, Y g:i A', strtotime($registration['registration_date'])); ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Payment Status</label>
                                <select name="payment_status" required>
                                    <option value="pending" <?php echo $registration['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $registration['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="refunded" <?php echo $registration['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Amount Paid</label>
                                <input type="number" name="amount_paid" value="<?php echo $registration['registration_fee']; ?>" step="0.01" min="0" required>
                            </div>

                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method">
                                    <option value="">Not specified</option>
                                    <option value="credit_card" <?php echo $registration['payment_method'] === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="debit_card" <?php echo $registration['payment_method'] === 'debit_card' ? 'selected' : ''; ?>>Debit Card</option>
                                    <option value="paypal" <?php echo $registration['payment_method'] === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                    <option value="zelle" <?php echo $registration['payment_method'] === 'zelle' ? 'selected' : ''; ?>>Zelle</option>
                                    <option value="cash" <?php echo $registration['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="check" <?php echo $registration['payment_method'] === 'check' ? 'selected' : ''; ?>>Check</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <?php if ($registration['payment_date']): ?>
                                <div class="info-item">
                                    <div class="info-label">Payment Date</div>
                                    <div class="info-value">
                                        <?php echo date('F j, Y g:i A', strtotime($registration['payment_date'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($registration['confirmation_email_sent']): ?>
                                <div class="info-item">
                                    <div class="info-label">Confirmation Email</div>
                                    <div class="info-value">
                                        Sent on <?php echo date('M j, Y', strtotime($registration['confirmation_sent_date'])); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="info-item">
                                    <div class="info-label">Confirmation Email</div>
                                    <div class="info-value" style="color: #856404;">Not sent yet</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="registrations.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>