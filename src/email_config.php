<?php

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.sendgrid.net');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'info@kenyannursesusa.org');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Kenyan Nurses Association of America');

define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost:8080');
define('SITE_NAME', 'KNAA');

define('EMAIL_ENABLED', getenv('EMAIL_ENABLED') !== 'false');