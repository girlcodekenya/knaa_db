<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php?return=' . urlencode(basename($_SERVER['PHP_SELF'])));
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_title = trim($_POST['event_name']);
    $event_description = trim($_POST['event_description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $street_address = trim($_POST['street_address']);
    $city = trim($_POST['city']);
    $state = $_POST['state'];
    $zip_code = trim($_POST['zip_code']);
    $venue_name = trim($_POST['venue_name']);
    $max_attendees = intval($_POST['max_capacity']) ?: null;
    $standard_fee = floatval($_POST['registration_fee']);
    $early_bird_fee = floatval($_POST['early_bird_fee']) ?: null;
    $early_bird_deadline = $_POST['early_bird_deadline'] ?: null;
    $is_active = $_POST['event_status'] === 'active' ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO events (
        event_title, event_description, event_date, event_time,
        street_address, city, state, zip_code, venue_name, 
        max_attendees, standard_fee, early_bird_fee, early_bird_deadline,
        is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssssssiddssi",
        $event_title, $event_description, $event_date, $event_time,
        $street_address, $city, $state, $zip_code, $venue_name,
        $max_attendees, $standard_fee, $early_bird_fee, $early_bird_deadline,
        $is_active
    );
    
    if ($stmt->execute()) {
        $new_event_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        header("Location: event_view.php?id=$new_event_id&created=1");
        exit;
    } else {
        $error = 'Failed to create event.';
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - KNAA Admin</title>
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
            max-width: 900px;
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            font-size: 1.1rem;
            color: #0052A5;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
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
            min-height: 120px;
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

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }

        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
        }

        @media (max-width: 768px) {
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
            <li><a href="members.php">Members</a></li>
            <li><a href="events.php" class="active">Events</a></li>
            <li><a href="registrations.php">Registrations</a></li>
            <li><a href="emails.php">Email Logs</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="page-header">
            <h2>Create New Event</h2>
            <a href="events.php" class="btn btn-secondary">← Back to Events</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div class="form-section">
                    <h3>Basic Information</h3>
                    
                    <div class="form-group">
                        <label>Event Name *</label>
                        <input type="text" name="event_name" required>
                    </div>

                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="event_description" required></textarea>
                        <div class="help-text">Provide a detailed description of the event</div>
                    </div>

                    <div class="form-group">
                        <label>Event Status *</label>
                        <select name="event_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Date & Time</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Event Date *</label>
                            <input type="date" name="event_date" required>
                        </div>
                        <div class="form-group">
                            <label>Event Time *</label>
                            <input type="time" name="event_time" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Early Bird Deadline</label>
                        <input type="date" name="early_bird_deadline">
                        <div class="help-text">Optional: Deadline for early bird pricing</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Location</h3>
                    
                    <div class="form-group">
                        <label>Street Address</label>
                        <input type="text" name="street_address" placeholder="123 Main St">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" required>
                        </div>
                        <div class="form-group">
                            <label>State *</label>
                            <input type="text" name="state" maxlength="2" placeholder="e.g., NY" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>ZIP Code</label>
                            <input type="text" name="zip_code" placeholder="12345">
                        </div>
                        <div class="form-group">
                            <label>Venue Name</label>
                            <input type="text" name="venue_name" placeholder="Hotel/Conference Center">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Registration Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Registration Fee *</label>
                            <input type="number" name="registration_fee" step="0.01" min="0" value="0" required>
                            <div class="help-text">Standard registration price</div>
                        </div>
                        <div class="form-group">
                            <label>Early Bird Fee</label>
                            <input type="number" name="early_bird_fee" step="0.01" min="0">
                            <div class="help-text">Optional: Discounted price for early registration</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Max Capacity</label>
                        <input type="number" name="max_capacity" min="0" placeholder="Leave empty for unlimited">
                        <div class="help-text">Optional: Maximum number of attendees</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Event</button>
                    <a href="events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>