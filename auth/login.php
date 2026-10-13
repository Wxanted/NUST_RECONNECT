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

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT userId, fullName, passwordHash, role FROM Users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['passwordHash'])) {
        session_regenerate_id(true);
        $_SESSION['userId'] = (int)$user['userId'];
        $_SESSION['fullName'] = $user['fullName'];
        $_SESSION['role'] = $user['role'];

        flash_set('success', 'Welcome back, ' . $user['fullName'] . '.');
        redirect($user['role'] === 'Admin' ? '/admin/dashboard.php' : '/user/dashboard.php');
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
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
            </p>
            <p>
                <label for="password">Password</label><br>
                <input type="password" id="password" name="password" required>
            </p>
            <button type="submit">Log in</button>
        </form>

        <p>No account yet? <a href="<?= BASE_URL ?>/auth/register.php">Register</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
