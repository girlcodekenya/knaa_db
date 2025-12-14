<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/includes/admin_auth.php';

requireAdminLogin();
$conn = getDBConnection();
$admin = getAdminUser();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where_clause = '';
if ($filter === 'upcoming') {
    $where_clause = 'WHERE event_date >= CURDATE()';
} elseif ($filter === 'past') {
    $where_clause = 'WHERE event_date < CURDATE()';
} elseif ($filter === 'active') {
    $where_clause = 'WHERE is_active = 1';
}

$events = $conn->query("
    SELECT 
        e.*,
        COUNT(DISTINCT er.registration_id) as registration_count,
        COALESCE(SUM(CASE WHEN er.payment_status = 'completed' THEN er.registration_fee ELSE 0 END), 0) as revenue
    FROM events e
    LEFT JOIN event_registrations er ON e.event_id = er.event_id
    $where_clause
    GROUP BY e.event_id
    ORDER BY e.event_date DESC
");

$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$upcoming_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE()")->fetch_assoc()['count'];
$total_registrations = $conn->query("SELECT COUNT(*) as count FROM event_registrations")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - KNAA Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h2 {
            font-size: 2rem;
            color: #0052A5;
        }

        .btn-primary {
            padding: 0.8rem 1.5rem;
            background: #DC143C;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #B01030;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #0052A5;
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

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.8rem 1.5rem;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .filter-btn:hover {
            border-color: #0052A5;
            color: #0052A5;
        }

        .filter-btn.active {
            background: #0052A5;
            color: white;
            border-color: #0052A5;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .event-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .event-card:hover {
            transform: translateY(-5px);
        }

        .event-header {
            background: #0052A5;
            color: white;
            padding: 1rem;
        }

        .event-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .event-date {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .event-body {
            padding: 1.5rem;
        }

        .event-info {
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            font-weight: 600;
            color: #333;
        }

        .event-actions {
            display: flex;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            flex: 1;
        }

        .btn-view {
            background: #0052A5;
            color: white;
        }

        .btn-view:hover {
            background: #003875;
        }

        .btn-edit {
            background: #f0ad4e;
            color: white;
        }

        .btn-edit:hover {
            background: #ec971f;
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

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
            background: white;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .admin-nav-links {
                display: none;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .events-grid {
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="members.php">Members</a></li>
                <li><a href="events.php" class="active">Events</a></li>
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
            <div>
                <h2>Event Management</h2>
                <p>Manage all KNAA events</p>
            </div>
            <a href="event_create.php" class="btn-primary">Create New Event</a>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Events</div>
                <div class="stat-value"><?php echo number_format($total_events); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Upcoming Events</div>
                <div class="stat-value"><?php echo number_format($upcoming_events); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Registrations</div>
                <div class="stat-value"><?php echo number_format($total_registrations); ?></div>
            </div>
        </div>

        <div class="filters-section">
            <div class="filter-buttons">
                <a href="events.php?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All Events
                </a>
                <a href="events.php?filter=upcoming" class="filter-btn <?php echo $filter === 'upcoming' ? 'active' : ''; ?>">
                    Upcoming
                </a>
                <a href="events.php?filter=past" class="filter-btn <?php echo $filter === 'past' ? 'active' : ''; ?>">
                    Past Events
                </a>
                <a href="events.php?filter=active" class="filter-btn <?php echo $filter === 'active' ? 'active' : ''; ?>">
                    Active Only
                </a>
            </div>
        </div>

        <?php if ($events->num_rows > 0): ?>
        <div class="events-grid">
            <?php while ($event = $events->fetch_assoc()): ?>
            <div class="event-card">
                <div class="event-header">
                    <div class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></div>
                    <div class="event-date">
                        <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                        <?php if ($event['event_time']): ?>
                            at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="event-body">
                    <div class="event-info">
                        <div class="info-row">
                            <span class="info-label">Location:</span>
                            <span class="info-value"><?php echo htmlspecialchars($event['city'] . ', ' . $event['state']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Registrations:</span>
                            <span class="info-value">
                                <?php echo $event['registration_count']; ?>
                                <?php if ($event['max_attendees']): ?>
                                    / <?php echo $event['max_attendees']; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Revenue:</span>
                            <span class="info-value">$<?php echo number_format($event['revenue'], 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value">
                                <span class="badge <?php echo $event['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $event['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="event-actions">
                        <a href="event_view.php?id=<?php echo $event['event_id']; ?>" class="btn-small btn-view">View Details</a>
                        <a href="event_edit.php?id=<?php echo $event['event_id']; ?>" class="btn-small btn-edit">Edit</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            No events found matching your criteria.
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>