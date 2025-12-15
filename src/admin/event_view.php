<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php?return=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    header('Location: events.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_title = trim($_POST['event_name']);
    $event_description = trim($_POST['event_description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $venue_name = trim($_POST['venue_name']);
    $max_attendees = intval($_POST['max_capacity']) ?: null;
    $standard_fee = floatval($_POST['registration_fee']);
    $early_bird_fee = floatval($_POST['early_bird_fee']) ?: null;
    $early_bird_deadline = $_POST['early_bird_deadline'] ?: null;
    $is_active = $_POST['event_status'] === 'active' ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE events SET 
        event_title = ?, event_description = ?, event_date = ?, event_time = ?,
        city = ?, state = ?, venue_name = ?, max_attendees = ?,
        standard_fee = ?, early_bird_fee = ?, early_bird_deadline = ?,
        is_active = ?, updated_at = NOW()
        WHERE event_id = ?");
    
    $stmt->bind_param("sssssssiddsii",
        $event_title, $event_description, $event_date, $event_time,
        $city, $state, $venue_name, $max_attendees,
        $standard_fee, $early_bird_fee, $early_bird_deadline,
        $is_active, $event_id
    );
    
    if ($stmt->execute()) {
        $message = 'Event updated successfully!';
    } else {
        $error = 'Failed to update event.';
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    $conn->close();
    header('Location: events.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as paid,
           SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending,
           SUM(registration_fee) as total_revenue
    FROM event_registrations
    WHERE event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT er.*, 
           m.first_name, m.last_name,
           m.email, m.phone
    FROM event_registrations er
    LEFT JOIN members m ON er.member_id = m.member_id
    WHERE er.event_id = ?
    ORDER BY er.registration_date DESC
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$registrations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - KNAA Admin</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #0052A5;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
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
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
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
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

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

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
            <li><a href="events.php" class="active">Events</a></li>
            <li><a href="registrations.php">Registrations</a></li>
            <li><a href="emails.php">Email Logs</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2><?php echo htmlspecialchars($event['event_title']); ?></h2>
            <a href="events.php" class="btn btn-secondary">← Back to Events</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Registrations</div>
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Paid</div>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['paid'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value" style="color: #ffc107;"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value" style="font-size: 1.5rem;">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <h3>Event Information</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Event Name</label>
                        <input type="text" name="event_name" value="<?php echo htmlspecialchars($event['event_title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="event_description" required><?php echo htmlspecialchars($event['event_description']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Event Date</label>
                            <input type="date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Event Time</label>
                            <input type="time" name="event_time" value="<?php echo $event['event_time']; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($event['city']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" value="<?php echo htmlspecialchars($event['state']); ?>" maxlength="2" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Venue Name</label>
                        <input type="text" name="venue_name" value="<?php echo htmlspecialchars($event['venue_name']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Max Capacity</label>
                        <input type="number" name="max_capacity" value="<?php echo $event['max_attendees']; ?>" min="0">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Registration Fee</label>
                            <input type="number" name="registration_fee" value="<?php echo $event['standard_fee']; ?>" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Early Bird Fee</label>
                            <input type="number" name="early_bird_fee" value="<?php echo $event['early_bird_fee']; ?>" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Early Bird Deadline</label>
                        <input type="date" name="early_bird_deadline" value="<?php echo $event['early_bird_deadline']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Event Status</label>
                        <select name="event_status" required>
                            <option value="active" <?php echo $event['is_active'] ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo !$event['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="events.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <div class="card">
                <h3>Event Details</h3>
                <div class="info-item">
                    <div class="info-label">Event ID</div>
                    <div class="info-value">#<?php echo $event['event_id']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="badge <?php echo $event['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo $event['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Capacity</div>
                    <div class="info-value">
                        <?php echo $stats['total'] ?? 0; ?> / <?php echo $event['max_attendees'] ?: 'Unlimited'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Occupancy Rate</div>
                    <div class="info-value">
                        <?php 
                        if ($event['max_attendees'] > 0) {
                            $rate = (($stats['total'] ?? 0) / $event['max_attendees']) * 100;
                            echo number_format($rate, 1) . '%';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Created</div>
                    <div class="info-value"><?php echo date('M j, Y', strtotime($event['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($event['updated_at'])); ?></div>
                </div>
            </div>

            <div class="card full-width">
                <h3>Registered Attendees (<?php echo count($registrations); ?>)</h3>
                <?php if (count($registrations) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Registration Date</th>
                                <th>Payment Status</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if ($reg['member_id']) {
                                            echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']);
                                        } else {
                                            echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($reg['registration_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $reg['payment_status'] === 'completed' ? 'badge-completed' : 'badge-pending'; ?>">
                                            <?php echo ucfirst($reg['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($reg['registration_fee'], 2); ?></td>
                                    <td>
                                        <?php if ($reg['member_id']): ?>
                                            <a href="member_view.php?id=<?php echo urlencode($reg['member_id']); ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">View</a>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.85rem;">Guest</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">No registrations yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>