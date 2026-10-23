<?php
// Shared helper functions

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Safe string from $_POST / $_GET (arrays or missing keys become '')
function input_str(array $src, string $key): string
{
    $v = $src[$key] ?? '';
    return is_string($v) ? trim($v) : '';
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
        // Remember where the visitor wanted to go so login can send them back
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            $_SESSION['next'] = $_SERVER['REQUEST_URI'] ?? '';
        }
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

// ---- Login helpers ----
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['userId']   = (int)$user['userId'];
    $_SESSION['fullName'] = $user['fullName'];
    $_SESSION['role']     = $user['role'];
}

// Send the user to the page they originally wanted, or to $default.
function after_login_redirect(string $default): void
{
    $next = $_SESSION['next'] ?? null;
    unset($_SESSION['next']);

    if (is_string($next) && strpos($next, BASE_URL . '/') === 0) {
        $path = substr($next, strlen(BASE_URL));
        if (strpos($path, '//') !== 0
            && strpos($path, '\\') === false
            && !preg_match('/[\r\n]/', $path)
            && strpos($path, '/auth/') !== 0) {
            redirect($path);
        }
    }
    redirect($default);
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

// ---- Item helpers ----
function item_status_label(string $type, string $status): string
{
    if ($status === 'resolved') {
        return 'Reunited';
    }
    return $type === 'found' ? 'Waiting for owner' : 'Still missing';
}

// Categories + locations grouped by campus (for report / edit forms)
function load_form_options(PDO $pdo): array
{
    $categories = $pdo->query('SELECT categoryId, categoryName FROM Categories ORDER BY categoryName')->fetchAll();
    $rows = $pdo->query(
        'SELECT l.locationId, l.locationName, cp.campusName
         FROM Location l JOIN Campus cp ON l.campusId = cp.campusId
         ORDER BY cp.campusName, l.locationName'
    )->fetchAll();
    $byCampus = [];
    foreach ($rows as $r) {
        $byCampus[$r['campusName']][] = $r;
    }
    return [$categories, $byCampus];
}

function item_input(array $src): array
{
    return [
        'type'        => input_str($src, 'type'),
        'title'       => input_str($src, 'title'),
        'categoryId'  => (int)($src['categoryId'] ?? 0),
        'locationId'  => (int)($src['locationId'] ?? 0),
        'dateLost'    => input_str($src, 'dateLost'),
        'description' => input_str($src, 'description'),
    ];
}

function validate_item(PDO $pdo, array $d): array
{
    $errors = [];

    if (!in_array($d['type'], ['lost', 'found'], true)) {
        $errors[] = 'Choose whether the item was lost or found.';
    }
    if ($d['title'] === '' || mb_strlen($d['title']) > 150) {
        $errors[] = 'Title is required (max 150 characters).';
    }
    if ($d['description'] === '' || mb_strlen($d['description']) > 2000) {
        $errors[] = 'Description is required (max 2000 characters).';
    }

    $s = $pdo->prepare('SELECT 1 FROM Categories WHERE categoryId = ?');
    $s->execute([$d['categoryId']]);
    if (!$s->fetchColumn()) {
        $errors[] = 'Choose a category.';
    }
    $s = $pdo->prepare('SELECT 1 FROM Location WHERE locationId = ?');
    $s->execute([$d['locationId']]);
    if (!$s->fetchColumn()) {
        $errors[] = 'Choose a location.';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $d['dateLost']);
    if (!$dt || $dt->format('Y-m-d') !== $d['dateLost']) {
        $errors[] = 'Enter a valid date.';
    } elseif ($d['dateLost'] > date('Y-m-d')) {
        $errors[] = 'The date cannot be in the future.';
    }

    return $errors;
}

// ---- Image upload ----
// Returns relative path (e.g. uploads/items/abc.jpg), null if no file, or false on error.
function save_image(array $file, ?string &$error)
{
    $error = null;

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Image is too large (max 3 MB).';
        return false;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'The image could not be uploaded. Please try again.';
        return false;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        $error = 'Image is too large (max 3 MB).';
        return false;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
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