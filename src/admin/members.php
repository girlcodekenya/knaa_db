<?php
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/includes/admin_auth.php';

requireAdminLogin();
$conn = getDBConnection();
$admin = getAdminUser();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR m.member_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if ($filter === 'active') {
    $where_clauses[] = "m.is_active = 1";
} elseif ($filter === 'inactive') {
    $where_clauses[] = "m.is_active = 0";
} elseif ($filter === 'expiring') {
    $where_clauses[] = "m.membership_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "
    SELECT 
        m.*,
        mt.type_name as membership_type,
        CASE 
            WHEN m.membership_expiration_date < CURDATE() THEN 'Expired'
            WHEN m.membership_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
            ELSE 'Active'
        END as status
    FROM members m
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.type_id
    $where_sql
    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$members = $stmt->get_result();

$total_members = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
$active_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE is_active = 1")->fetch_assoc()['count'];
$expiring_soon = $conn->query("SELECT COUNT(*) as count FROM members WHERE membership_expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Management - KNAA Admin</title>
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
        }

        .search-box {
            flex: 1;
            min-width: 300px;
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

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
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

        .members-table {
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

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
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

        .btn-edit {
            background: #f0ad4e;
            color: white;
        }

        .btn-edit:hover {
            background: #ec971f;
        }

        .btn-toggle {
            background: #5cb85c;
            color: white;
        }

        .btn-toggle:hover {
            background: #449d44;
        }

        .btn-toggle.deactivate {
            background: #d9534f;
        }

        .btn-toggle.deactivate:hover {
            background: #c9302c;
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

            .search-box {
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
                <li><a href="members.php" class="active">Members</a></li>
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
            <h2>Member Management</h2>
            <p>View and manage all KNAA members</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Members</div>
                <div class="stat-value"><?php echo number_format($total_members); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Members</div>
                <div class="stat-value"><?php echo number_format($active_members); ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">Expiring Soon</div>
                <div class="stat-value"><?php echo number_format($expiring_soon); ?></div>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" class="filters-row">
                <div class="search-box">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by name, email, or member ID..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="filter" value="all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        All
                    </button>
                    <button type="submit" name="filter" value="active" class="filter-btn <?php echo $filter === 'active' ? 'active' : ''; ?>">
                        Active
                    </button>
                    <button type="submit" name="filter" value="inactive" class="filter-btn <?php echo $filter === 'inactive' ? 'active' : ''; ?>">
                        Inactive
                    </button>
                    <button type="submit" name="filter" value="expiring" class="filter-btn <?php echo $filter === 'expiring' ? 'active' : ''; ?>">
                        Expiring Soon
                    </button>
                </div>
            </form>
        </div>

        <div class="members-table">
            <?php if ($members->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Member Since</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $members->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                            <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['membership_type']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($member['member_since'])); ?></td>
                            <td><?php echo $member['membership_expiration_date'] ? date('M d, Y', strtotime($member['membership_expiration_date'])) : 'N/A'; ?></td>
                            <td>
                                <?php
                                $badge_class = 'badge-success';
                                if ($member['status'] === 'Expired') $badge_class = 'badge-danger';
                                if ($member['status'] === 'Expiring Soon') $badge_class = 'badge-warning';
                                if (!$member['is_active']) $badge_class = 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo $member['is_active'] ? $member['status'] : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="member_view.php?id=<?php echo $member['member_id']; ?>" class="btn-small btn-view">View</a>
                                    <a href="member_edit.php?id=<?php echo $member['member_id']; ?>" class="btn-small btn-edit">Edit</a>
                                    <button 
                                        onclick="toggleMember('<?php echo $member['member_id']; ?>', <?php echo $member['is_active'] ? 0 : 1; ?>)"
                                        class="btn-small btn-toggle <?php echo $member['is_active'] ? 'deactivate' : ''; ?>"
                                    >
                                        <?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                No members found matching your criteria.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleMember(memberId, activate) {
            const action = activate ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this member?`)) {
                window.location.href = `member_toggle.php?id=${memberId}&action=${activate}`;
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>