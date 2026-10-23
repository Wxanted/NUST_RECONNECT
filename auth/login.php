<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/db.php';

if (is_logged_in()) {
    redirect('/user/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = strtolower(input_str($_POST, 'email'));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $stmt = $pdo->prepare('SELECT userId, fullName, passwordHash, role FROM Users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always run password_verify so unknown emails take the same time as wrong passwords
    $hash = $user['passwordHash'] ?? '$2y$10$kkiPReFhWQVBvgYl7krtJObk1VbUS9x.SAtt3gS2o2RBfkoBO0Tai';
    $ok = password_verify($password, $hash) && $user;

    if ($ok) {
        login_user($user);
        flash_set('success', 'Welcome back, ' . $user['fullName'] . '.');
        after_login_redirect($user['role'] === 'Admin' ? '/admin/dashboard.php' : '/user/dashboard.php');
    }

    $error = 'Incorrect email or password.';
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Log in</h1>

        <?php if ($error): ?>
            <div class="flash flash-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            <p>
                <label for="email">Email</label><br>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
            </p>
            <p>
                <label for="password">Password</label><br>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </p>
            <button type="submit">Log in</button>
            <p><a href="<?= BASE_URL ?>/auth/forgot-password.php">Forgot your password?</a></p>
        </form>

        <p>No account yet? <a href="<?= BASE_URL ?>/auth/register.php">Register</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>