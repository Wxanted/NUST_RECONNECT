<?php
// Shared helper functions

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path)
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['userId']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'Admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Please log in to continue.');
        redirect('/auth/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied. Admins only.');
    }
}

// ---- Flash messages ----
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_show(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="flash flash-' . e($f['type']) . '" role="alert">' . e($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

// ---- CSRF ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Invalid or expired form. Go back, refresh the page and try again.');
    }
}

// ---- Image upload ----
// Returns relative path (e.g. uploads/items/abc.jpg), null if no file, or false on error.
function save_image(array $file, ?string &$error)
{
    $error = null;

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'The image could not be uploaded.';
        return false;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        $error = 'Image is too large (max 3 MB).';
        return false;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        $error = 'Only JPG, PNG or WEBP images are allowed.';
        return false;
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        $error = 'Upload folder is not writable.';
        return false;
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        $error = 'Could not save the uploaded image.';
        return false;
    }
    return UPLOAD_URL_PATH . '/' . $name;
}

function delete_image(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }
    $full = ROOT_PATH . '/' . $relativePath;
    if (strpos($relativePath, UPLOAD_URL_PATH . '/') === 0 && is_file($full)) {
        @unlink($full);
    }
}
