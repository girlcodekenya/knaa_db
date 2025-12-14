<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/includes/admin_auth.php';

requireAdminLogin();
$conn = getDBConnection();
$admin = getAdminUser();

$stats = [
    'total_members' => 0,
    'active_members' => 0,
    'upcoming_events' => 0,
    'total_registrations' => 0,
    'pending_payments' => 0,
    'revenue_this_month' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM members");
$stats['total_members'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM members WHERE is_active = 1");
$stats['active_members'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE() AND is_active = 1");
$stats['upcoming_events'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM event_registrations");
$stats['total_registrations'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM event_registrations WHERE payment_status = 'pending'");
$stats['pending_payments'] = $result->fetch_assoc()['count'];

$result = $conn->query("
    SELECT COALESCE(SUM(registration_fee), 0) as revenue 
    FROM event_registrations 
    WHERE payment_status = 'completed' 
    AND MONTH(payment_date) = MONTH(CURDATE()) 
    AND YEAR(payment_date) = YEAR(CURDATE())
");
$stats['revenue_this_month'] = $result->fetch_assoc()['revenue'];

$recent_members = $conn->query("
    SELECT member_id, first_name, last_name, email, member_since 
    FROM members 
    ORDER BY created_at DESC 
    LIMIT 5
");

$recent_registrations = $conn->query("
    SELECT 
        er.registration_id,
        er.first_name,
        er.last_name,
        er.registration_fee,
        er.payment_status,
        e.event_title,
        er.registration_date
    FROM event_registrations er
    JOIN events e ON er.event_id = e.event_id
    ORDER BY er.registration_date DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KNAA</title>
    <link rel="stylesheet" href="../../styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-navbar {
            background: #ffe8e8;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-logo img {
            height: 60px;
        }

        .admin-logo h1 {
            font-size: 1.3rem;
            color: #0052A5;
        }

        .admin-nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }

        .admin-nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .admin-nav-links a:hover,
        .admin-nav-links a.active {
            color: #DC143C;
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-user-name {
            font-weight: 600;
            color: #0052A5;
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: #DC143C;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #B01030;
        }

        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-size: 2rem;
            color: #0052A5;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #0052A5;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.red {
            border-left-color: #DC143C;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #0052A5;
        }

        .stat-card.red .stat-value {
            color: #DC143C;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
        }

        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: #0052A5;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem;
            background: #f5f5f5;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        @media (max-width: 768px) {
            .admin-nav-links {
                display: none;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="admin-navbar">
        <div class="admin-nav-container">
            <div class="admin-logo">
                <img src="../../assets/images/KNAA logo-1.png" alt="KNAA Logo">
                <h1>Admin Portal</h1>
            </div>
            <ul class="admin-nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="members.php">Members</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="registrations.php">Registrations</a></li>
                <li><a href="emails.php">Emails</a></li>
            </ul>
            <div class="admin-user-info">
                <span class="admin-user-name"><?php echo htmlspecialchars($admin['full_name']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="page-header">
            <h2>Dashboard</h2>
            <p>Welcome back, <?php echo htmlspecialchars($admin['full_name']); ?>!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Members</div>
                <div class="stat-value"><?php echo number_format($stats['total_members']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Members</div>
                <div class="stat-value"><?php echo number_format($stats['active_members']); ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Upcoming Events</div>
                <div class="stat-value"><?php echo number_format($stats['upcoming_events']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Registrations</div>
                <div class="stat-value"><?php echo number_format($stats['total_registrations']); ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Pending Payments</div>
                <div class="stat-value"><?php echo number_format($stats['pending_payments']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Revenue This Month</div>
                <div class="stat-value">$<?php echo number_format($stats['revenue_this_month'], 2); ?></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <div class="card-header">Recent Members</div>
                <div class="card-body">
                    <?php if ($recent_members->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Member ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($member = $recent_members->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                                    <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($member['member_since'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">No members yet</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">Recent Registrations</div>
                <div class="card-body">
                    <?php if ($recent_registrations->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Event</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($reg = $recent_registrations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                                    <td>$<?php echo number_format($reg['registration_fee'], 2); ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-warning';
                                        if ($reg['payment_status'] === 'completed') $badge_class = 'badge-success';
                                        if ($reg['payment_status'] === 'refunded') $badge_class = 'badge-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($reg['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">No registrations yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>