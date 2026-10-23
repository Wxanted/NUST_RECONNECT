<?php
date_default_timezone_set('Africa/Windhoek');

// Start a session only if one isn't active already (with safer cookie settings).
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// Base URL of the application. NO trailing slash.
define('BASE_URL', '/nust_lostandfound');

define('APP_NAME', 'NUST LostAndFound');

// 'development' = on localhost, the reset link is also shown on screen (no email setup needed).
// 'production'  = live site, reset links are ONLY emailed. Never leave 'development' on a public site.
define('APP_ENV', 'development');

// Full public address of the site (used inside emails). NO trailing slash.
define('SITE_URL', 'http://localhost/nust_lostandfound');

// Email settings (needed for real emails)
define('MAIL_FROM', 'noreply@example.com');   // Gmail users: use your own Gmail address here
define('MAIL_FROM_NAME', APP_NAME);
define('SMTP_HOST', '');                      // e.g. 'smtp.gmail.com' (leave '' to use PHP mail())
define('SMTP_PORT', 587);                     // 587 = STARTTLS, 465 = SSL
define('SMTP_USER', '');                      // e.g. your Gmail address
define('SMTP_PASS', '');                      // Gmail "App password" (not your normal password)

// Filesystem root of the project (used for uploads)
define('ROOT_PATH', dirname(__DIR__));

// Upload settings
define('UPLOAD_DIR', ROOT_PATH . '/uploads/items');
define('UPLOAD_URL_PATH', 'uploads/items');
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024); // 3 MB