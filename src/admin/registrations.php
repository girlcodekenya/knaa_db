<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/includes/admin_auth.php';

requireAdminLogin();
$conn = getDBConnection();
$admin = getAdminUser();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$event_filter = isset($_GET['event']) ? $_GET['event'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(er.first_name LIKE ? OR er.last_name LIKE ? OR er.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if ($filter === 'pending') {
    $where_clauses[] = "er.payment_status = 'pending'";
} elseif ($filter === 'completed') {
    $where_clauses[] = "er.payment_status = 'completed'";
} elseif ($filter === 'refunded') {
    $where_clauses[] = "er.payment_status = 'refunded'";
}

if ($event_filter !== 'all') {
    $where_clauses[] = "er.event_id = ?";
    $params[] = $event_filter;
    $types .= 'i';
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "
    SELECT 
        er.*,
        e.event_title,
        e.event_date,
        m.member_id
    FROM event_registrations er
    JOIN events e ON er.event_id = e.event_id
    LEFT JOIN members m ON er.email = m.email
    $where_sql
    ORDER BY er.registration_date DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$registrations = $stmt->get_result();

$events = $conn->query("SELECT event_id, event_title FROM events ORDER BY event_date DESC");

$total_registrations = $conn->query("SELECT COUNT(*) as count FROM event_registrations")->fetch_assoc()['count'];
$pending_payments = $conn->query("SELECT COUNT(*) as count FROM event_registrations WHERE payment_status = 'pending'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(registration_fee), 0) as revenue FROM event_registrations WHERE payment_status = 'completed'")->fetch_assoc()['revenue'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrations - KNAA Admin</title>
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

        .stat-card.warning {
            border-left-color: #f0ad4e;
        }

        .stat-card.success {
            border-left-color: #5cb85c;
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

        .filters-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: #0052A5;
        }

        .select-box {
            min-width: 200px;
        }

        .select-box select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }

        .select-box select:focus {
            outline: none;
            border-color: #0052A5;
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

        .registrations-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
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
            padding: 1rem;
            background: #0052A5;
            color: white;
            font-weight: 600;
        }

        td {
            padding: 1rem;
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

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #0052A5;
            color: white;
        }

        .btn-view:hover {
            background: #003875;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        @media (max-width: 768px) {
            .admin-nav-links {
                display: none;
            }

            .filters-row {
                flex-direction: column;
            }

            .search-box,
            .select-box {
                min-width: 100%;
            }

            .stats-row {
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
                <li><a href="events.php">Events</a></li>
                <li><a href="registrations.php" class="active">Registrations</a></li>
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
            <h2>Event Registrations</h2>
            <p>View and manage all event registrations</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Registrations</div>
                <div class="stat-value"><?php echo number_format($total_registrations); ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">Pending Payments</div>
                <div class="stat-value"><?php echo number_format($pending_payments); ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" class="filters-row">
                <div class="search-box">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by name or email..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="select-box">
                    <select name="event" onchange="this.form.submit()">
                        <option value="all">All Events</option>
                        <?php while ($event = $events->fetch_assoc()): ?>
                        <option value="<?php echo $event['event_id']; ?>" <?php echo $event_filter == $event['event_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['event_title']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="filter-btn">Search</button>
            </form>
            <div class="filter-buttons">
                <button onclick="window.location.href='registrations.php?filter=all&event=<?php echo $event_filter; ?>'" 
                    class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All
                </button>
                <button onclick="window.location.href='registrations.php?filter=pending&event=<?php echo $event_filter; ?>'" 
                    class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    Pending
                </button>
                <button onclick="window.location.href='registrations.php?filter=completed&event=<?php echo $event_filter; ?>'" 
                    class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                    Completed
                </button>
                <button onclick="window.location.href='registrations.php?filter=refunded&event=<?php echo $event_filter; ?>'" 
                    class="filter-btn <?php echo $filter === 'refunded' ? 'active' : ''; ?>">
                    Refunded
                </button>
            </div>
        </div>

        <div class="registrations-table">
            <?php if ($registrations->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Member</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($reg = $registrations->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $reg['registration_id']; ?></td>
                            <td><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                            <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($reg['registration_date'])); ?></td>
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
                            <td>
                                <?php if ($reg['member_id']): ?>
                                    <span class="badge badge-info">Member</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Guest</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="registration_view.php?id=<?php echo $reg['registration_id']; ?>" class="btn-small btn-view">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                No registrations found matching your criteria.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>