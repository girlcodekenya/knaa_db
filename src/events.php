<?php
session_start();
require_once 'db_config.php';
$conn = getDBConnection();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['member_id']);
$memberName = $isLoggedIn ? ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '') : '';

// Fetch upcoming events
$upcomingQuery = "SELECT e.*, 
                  e.current_attendees as registered_count
                  FROM events e 
                  WHERE e.event_date >= CURDATE() 
                  AND e.is_active = 1
                  ORDER BY e.event_date ASC";
$upcomingResult = $conn->query($upcomingQuery);

// Fetch past events
$pastQuery = "SELECT e.*, 
              e.current_attendees as registered_count
              FROM events e 
              WHERE e.event_date < CURDATE() 
              AND e.is_active = 1
              ORDER BY e.event_date DESC 
              LIMIT 6";
$pastResult = $conn->query($pastQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - KNAA Member Portal</title>
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
            line-height: 1.6;
            color: var(--dark-gray);
            background: var(--light-gray);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Navigation */
        .navbar {
            background: #ffe8e8;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 20px;
        }

        .logo-img {
            height: 120px;
            width: auto;
            object-fit: contain;
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
            padding: 0.5rem 0;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--primary-red);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .btn-logout {
            background-color: var(--primary-red);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-logout:hover {
            background-color: #B01030;
        }

        /* Page Header */
        .page-header {
            background: var(--primary-blue);
            color: var(--white);
            padding: 4rem 0 3rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        /* Calendar Section */
        .calendar-section {
            padding: 4rem 0;
            background: var(--white);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 3rem;
        }

        .calendar-container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--white);
            border: 2px solid var(--light-gray);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-gray);
        }

        .calendar-header h3 {
            font-size: 1.8rem;
            color: var(--primary-blue);
            font-weight: 600;
        }

        .calendar-nav {
            background: none;
            border: 2px solid var(--primary-blue);
            border-radius: 5px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--primary-blue);
        }

        .calendar-nav:hover {
            background: var(--primary-blue);
            color: var(--white);
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .calendar-weekdays div {
            text-align: center;
            font-weight: 600;
            color: var(--primary-blue);
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
            border: 1px solid transparent;
        }

        .calendar-day:hover {
            background: var(--light-gray);
        }

        .calendar-day.other-month {
            color: #ccc;
        }

        .calendar-day.today {
            background: var(--primary-blue);
            color: var(--white);
            font-weight: 600;
        }

        .calendar-day.event-day {
            background: var(--primary-red);
            color: var(--white);
            font-weight: 600;
        }

        /* Upcoming Events Section */
        .upcoming-events-section {
            padding: 4rem 0;
            background: var(--light-gray);
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .event-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,82,165,0.2);
        }

        .event-card-header {
            position: relative;
            padding: 2rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #003d7a 100%);
            color: white;
        }

        .event-date-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary-red);
            color: var(--white);
            width: 60px;
            height: 60px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .date-day {
            font-size: 1.5rem;
            font-weight: bold;
            line-height: 1;
        }

        .date-month {
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.2rem;
        }

        .event-card-header h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            padding-right: 70px;
        }

        .event-card-content {
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .event-meta {
            margin-bottom: 1.5rem;
        }

        .event-time,
        .event-location,
        .event-capacity {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: var(--text-gray);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .event-description {
            color: var(--text-gray);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            flex: 1;
        }

        .event-pricing {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .event-pricing h4 {
            font-size: 0.9rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.3rem;
            font-size: 0.95rem;
        }

        .price-label {
            color: var(--text-gray);
        }

        .price-value {
            color: var(--primary-red);
            font-weight: 600;
        }

        .early-bird-notice {
            font-size: 0.85rem;
            color: var(--primary-red);
            font-style: italic;
            margin-top: 0.5rem;
        }

        .btn {
            padding: 0.9rem 2rem;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid transparent;
            cursor: pointer;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary-red);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #B01030;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,20,60,0.4);
        }

        .btn-full {
            width: 100%;
        }

        /* Past Events Section */
        .past-events-section {
            padding: 4rem 0;
            background: var(--white);
        }

        .past-events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .past-event-card {
            background: var(--white);
            border: 2px solid var(--light-gray);
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .past-event-card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,82,165,0.15);
        }

        .past-event-card h4 {
            font-size: 1.2rem;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
        }

        .past-event-date {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 0.5rem;
        }

        .past-event-location {
            font-size: 0.85rem;
            color: var(--text-gray);
            margin-bottom: 1rem;
        }

        .past-event-attendees {
            font-size: 0.9rem;
            color: var(--primary-red);
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background: #000514;
            color: var(--white);
            padding: 3rem 0 1rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-col h4 {
            color: var(--white);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .footer-col p {
            color: rgba(255,255,255,0.8);
            line-height: 1.8;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 0.5rem;
        }

        .footer-col a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-col a:hover {
            color: var(--primary-red);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
        }

        .no-events {
            text-align: center;
            padding: 3rem;
            color: var(--text-gray);
            font-size: 1.2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                gap: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .calendar-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <div class="logo">
                <img src="./assets/images/KNAA logo-1.png" alt="KNAA Logo" class="logo-img">
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="events.php" class="active">Events</a></li>
                    <li class="user-info">
                        <span class="user-name">👋 <?php echo htmlspecialchars($memberName); ?></span>
                        <a href="logout.php" class="btn-logout">Logout</a>
                    </li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>KNAA Events</h1>
            <p>Connect, Learn, and Grow Together</p>
        </div>
    </section>

    <!-- Calendar Section -->
    <section class="calendar-section">
        <div class="container">
            <h2 class="section-title">Event Calendar</h2>
            <div class="calendar-container">
                <div class="calendar-header">
                    <button class="calendar-nav" id="prevMonth">‹</button>
                    <h3 id="currentMonth">December 2024</h3>
                    <button class="calendar-nav" id="nextMonth">›</button>
                </div>
                <div class="calendar-weekdays">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>
                <div class="calendar-days" id="calendarDays"></div>
            </div>
        </div>
    </section>

    <!-- Upcoming Events Section -->
    <section class="upcoming-events-section">
        <div class="container">
            <h2 class="section-title">Upcoming Events</h2>
            <div class="events-grid">
                <?php if ($upcomingResult && $upcomingResult->num_rows > 0): ?>
                    <?php while($event = $upcomingResult->fetch_assoc()): ?>
                        <?php
                        $eventDate = new DateTime($event['event_date']);
                        $day = $eventDate->format('d');
                        $month = $eventDate->format('M');
                        $fullDate = $eventDate->format('l, F j, Y');
                        
                        // Check if early bird pricing is available
                        $isEarlyBird = false;
                        if ($event['early_bird_deadline']) {
                            $deadline = new DateTime($event['early_bird_deadline']);
                            $today = new DateTime();
                            $isEarlyBird = ($today <= $deadline);
                        }
                        
                        // Build location string
                        $location = '';
                        if ($event['venue_name']) $location .= $event['venue_name'] . ', ';
                        if ($event['city']) $location .= $event['city'];
                        if ($event['state']) $location .= ', ' . $event['state'];
                        ?>
                        <div class="event-card">
                            <div class="event-card-header">
                                <div class="event-date-badge">
                                    <span class="date-day"><?php echo $day; ?></span>
                                    <span class="date-month"><?php echo $month; ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars($event['event_title']); ?></h3>
                            </div>
                            <div class="event-card-content">
                                <div class="event-meta">
                                    <p class="event-time">
                                        🕐 <?php echo $fullDate; ?>
                                        <?php if ($event['event_time']): ?>
                                            at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($location): ?>
                                        <p class="event-location">📍 <?php echo htmlspecialchars($location); ?></p>
                                    <?php endif; ?>
                                    <?php if ($event['max_attendees']): ?>
                                        <p class="event-capacity">
                                            👥 <?php echo $event['registered_count']; ?> / <?php echo $event['max_attendees']; ?> registered
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($event['event_description']): ?>
                                    <p class="event-description">
                                        <?php echo htmlspecialchars(substr($event['event_description'], 0, 150)); ?>...
                                    </p>
                                <?php endif; ?>
                                
                                <div class="event-pricing">
                                    <h4>Event Pricing:</h4>
                                    <?php if ($isEarlyBird && $event['early_bird_fee']): ?>
                                        <div class="price-item">
                                            <span class="price-label">Early Bird:</span>
                                            <span class="price-value">$<?php echo number_format($event['early_bird_fee'], 2); ?></span>
                                        </div>
                                        <p class="early-bird-notice">
                                            ⏰ Until <?php echo date('M j, Y', strtotime($event['early_bird_deadline'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($event['member_discount_fee']): ?>
                                        <div class="price-item">
                                            <span class="price-label">KNAA Members:</span>
                                            <span class="price-value">$<?php echo number_format($event['member_discount_fee'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($event['standard_fee']): ?>
                                        <div class="price-item">
                                            <span class="price-label">Standard:</span>
                                            <span class="price-value">$<?php echo number_format($event['standard_fee'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$event['standard_fee'] && !$event['member_discount_fee'] && !$event['early_bird_fee']): ?>
                                        <div class="price-item">
                                            <span class="price-value">Free Event</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-primary btn-full">
                                    View Details & Register
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-events">
                        <p>No upcoming events at this time. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Past Events Section -->
    <?php if ($pastResult && $pastResult->num_rows > 0): ?>
    <section class="past-events-section">
        <div class="container">
            <h2 class="section-title">Past Events</h2>
            <div class="past-events-grid">
                <?php while($pastEvent = $pastResult->fetch_assoc()): ?>
                    <?php
                    $location = '';
                    if ($pastEvent['venue_name']) $location .= $pastEvent['venue_name'] . ', ';
                    if ($pastEvent['city']) $location .= $pastEvent['city'];
                    if ($pastEvent['state']) $location .= ', ' . $pastEvent['state'];
                    ?>
                    <div class="past-event-card">
                        <h4><?php echo htmlspecialchars($pastEvent['event_title']); ?></h4>
                        <p class="past-event-date">
                            <?php echo date('F j, Y', strtotime($pastEvent['event_date'])); ?>
                        </p>
                        <?php if ($location): ?>
                            <p class="past-event-location">📍 <?php echo htmlspecialchars($location); ?></p>
                        <?php endif; ?>
                        <p class="past-event-attendees">✓ <?php echo $pastEvent['registered_count']; ?> Attendees</p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>KNAA</h4>
                    <p>Kenyan Nurses Association of America - A non-profit organization championing nursing excellence.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="events.php">Events</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li>Email: Info@KenyanNursesUSA.org</li>
                        <li>Zelle: Info@KenyanNursesUSA.org</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Kenyan Nurses Association of America. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Calendar functionality
        let currentDate = new Date();
        const today = new Date();

        // Fetch event dates from server
        const eventDates = {};
        <?php
        // Fetch all event dates for calendar
        $calendarQuery = "SELECT event_date, event_title FROM events WHERE is_active = 1";
        $calendarResult = $conn->query($calendarQuery);
        if ($calendarResult) {
            while($calEvent = $calendarResult->fetch_assoc()) {
                $date = date('Y-n-j', strtotime($calEvent['event_date']));
                echo "eventDates['$date'] = '" . addslashes($calEvent['event_title']) . "';\n";
            }
        }
        ?>

        function renderCalendar(date) {
            const year = date.getFullYear();
            const month = date.getMonth();
            
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            const calendarDays = document.getElementById('calendarDays');
            calendarDays.innerHTML = '';

            // Previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day other-month';
                dayDiv.textContent = daysInPrevMonth - i;
                calendarDays.appendChild(dayDiv);
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                dayDiv.textContent = day;

                if (year === today.getFullYear() && 
                    month === today.getMonth() && 
                    day === today.getDate()) {
                    dayDiv.classList.add('today');
                }

                const dateKey = `${year}-${month + 1}-${day}`;
                if (eventDates[dateKey]) {
                    dayDiv.classList.add('event-day');
                    dayDiv.title = eventDates[dateKey];
                }

                calendarDays.appendChild(dayDiv);
            }

            // Next month days
            const totalCells = calendarDays.children.length;
            const remainingCells = 42 - totalCells;
            for (let i = 1; i <= remainingCells; i++) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day other-month';
                dayDiv.textContent = i;
                calendarDays.appendChild(dayDiv);
            }
        }

        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar(currentDate);
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar(currentDate);
        });

        renderCalendar(currentDate);
    </script>
</body>
</html>

<?php $conn->close(); ?>