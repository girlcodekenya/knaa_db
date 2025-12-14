<?php
session_start();
$is_logged_in = isset($_SESSION['member_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNAA Member Portal | Kenya Nurses Association of America</title>
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

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

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-blue);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
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

        .hero {
            position: relative;
            background: rgba(0,82,165,0.85);
            color: var(--white);
            padding: 100px 20px;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
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

        .btn-secondary {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .btn-secondary:hover {
            background: var(--white);
            color: var(--primary-blue);
        }

        .mission-section {
            padding: 4rem 0;
            background: var(--light-gray);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: var(--primary-blue);
            margin-bottom: 3rem;
        }

        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .mission-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .mission-card:hover {
            transform: translateY(-5px);
        }

        .mission-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-blue);
        }

        .mission-icon svg {
            width: 50px;
            height: 50px;
        }

        .mission-card h3 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .services-section {
            padding: 4rem 0;
            background: var(--white);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .service-card {
            padding: 2rem;
            border-radius: 10px;
            border: 2px solid var(--light-gray);
            transition: all 0.3s;
        }

        .service-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 6px 20px rgba(0,82,165,0.15);
            transform: translateY(-5px);
        }

        .service-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .service-icon svg {
            width: 30px;
            height: 30px;
        }

        .service-icon.blue {
            background: rgba(0,82,165,0.1);
            color: var(--primary-blue);
        }

        .service-icon.red {
            background: rgba(220,20,60,0.1);
            color: var(--primary-red);
        }

        .service-card h3 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .service-card p {
            color: var(--text-gray);
        }

        .cta-section {
            padding: 4rem 0;
            background: var(--primary-blue);
            color: var(--white);
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-light {
            background: var(--white);
            color: var(--primary-blue);
        }

        .btn-light:hover {
            background: var(--light-gray);
        }

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

        @media (max-width: 768px) {
            .hero {
                padding: 80px 20px;
            }

            .hero-title {
                font-size: 1.8rem;
                padding: 0 1rem;
            }

            .hero-subtitle {
                font-size: 1rem;
                padding: 0 1rem;
            }

            .hero-buttons {
                padding: 0 1rem;
            }

            .btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.95rem;
                width: 100%;
                text-align: center;
            }

            .mission-section,
            .services-section,
            .cta-section {
                padding: 3rem 0;
            }

            .mission-grid,
            .services-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .section-title {
                font-size: 1.8rem;
                padding: 0 1rem;
            }

            .cta-section h2 {
                font-size: 2rem;
                padding: 0 1rem;
            }

            .cta-section p {
                font-size: 1.1rem;
                padding: 0 1rem;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 1.5rem;
            }

            .hero-subtitle {
                font-size: 0.9rem;
            }

            .section-title {
                font-size: 1.6rem;
            }

            .cta-section h2 {
                font-size: 1.7rem;
            }

            .cta-section p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">KNAA</div>
            <ul class="nav-menu">
                <li><a href="index.php" class="active">Home</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Join Us</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <h1 class="hero-title">Kenya Nurses Association of America</h1>
            <p class="hero-subtitle">Empowering Kenyan Nurses in America | Advancing Healthcare Excellence</p>
            <div class="hero-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary">Join KNAA</a>
                    <a href="login.php" class="btn btn-secondary">Member Login</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="mission-section">
        <div class="container">
            <div class="mission-grid">
                <div class="mission-card">
                    <div class="mission-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </div>
                    <h3>Mission</h3>
                    <p>The Kenyan Nurses Association of America (KNAA) is a non-profit organization registered in the United States to champion the advancement of nursing and healthcare in the US and Kenya.</p>
                </div>

                <div class="mission-card">
                    <div class="mission-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </div>
                    <h3>Vision</h3>
                    <p>To work towards healthcare systems that are professional, efficient, affordable, sustainable and meet the needs of the people.</p>
                </div>

                <div class="mission-card">
                    <div class="mission-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                        </svg>
                    </div>
                    <h3>Core Values</h3>
                    <p>Integrity, Service, Accountability, Compassion, and Excellence</p>
                </div>
            </div>
        </div>
    </section>

    <section class="services-section">
        <div class="container">
            <h2 class="section-title">What We Do</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                        </svg>
                    </div>
                    <h3>Advocacy & Lobbying</h3>
                    <p>Being a voice at state and national level for matters affecting Kenyan nurses in the USA and influencing favorable working conditions.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon red">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <h3>Education</h3>
                    <p>Partnering with universities for discounted tuition, providing CEU opportunities, and offering access to medical journals and resources.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3>Mentorship</h3>
                    <p>NCLEX preparation programs, school guidance, and scholarship opportunities to support members' professional growth.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon red">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                    <h3>Entrepreneurship</h3>
                    <p>Resources and mentorship for nurses starting healthcare businesses, plus assistance with grants and loans.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <h3>Networking</h3>
                    <p>Annual conferences, strategic networking platforms, and a closed online forum exclusively for KNAA members.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon red">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                    </div>
                    <h3>Professional Development</h3>
                    <p>Continuing education, certifications, webinars, and access to preceptors to advance your nursing career.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Join Our Community</h2>
            <p>Connect with fellow Kenyan nurses in the USA, access resources, and enhance your professional journey with us today.</p>
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-light">Go to Dashboard</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-light">Become a Member</a>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>KNAA</h4>
                    <p>Kenyan Nurses Association of America. A non-profit organization championing nursing excellence.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="register.php">Join Us</a></li>
                        <li><a href="login.php">Member Login</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li>Email: Info@KenyanNursesUSA.org</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Kenyan Nurses Association of America. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>