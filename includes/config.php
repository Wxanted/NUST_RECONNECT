<?php
// Start a session only if one isn't active already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL of the application. NO trailing slash (links add "/path" themselves).
// The folder name has a space, so it is URL-encoded. Rename the folder to
// "nust-reconnect" if you want to avoid this, and change it here too.
define('BASE_URL', '/nust_lostandfound');

// Application name
define('APP_NAME', 'NUST Reconnect');

// Filesystem root of the project (used for uploads)
define('ROOT_PATH', dirname(__DIR__));

// Upload settings
define('UPLOAD_DIR', ROOT_PATH . '/uploads/items');
define('UPLOAD_URL_PATH', 'uploads/items');
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024); // 3 MB
