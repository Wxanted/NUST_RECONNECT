<?php
$pageTitle = 'Register';
require_once __DIR__ . '/../includes/db.php';

if (is_logged_in()) {
    redirect('/user/dashboard.php');
}

$errors = [];
$old = ['fullName' => '', 'email' => '', 'studentNumber' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['fullName'] = trim($_POST['fullName'] ?? '');
    $old['email'] = strtolower(trim($_POST['email'] ?? ''));
    $old['studentNumber'] = trim($_POST['studentNumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($old['fullName'] === '' || mb_strlen($old['fullName']) > 100) {
        $errors[] = 'Please enter your full name (max 100 characters).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($old['studentNumber']) > 30) {
        $errors[] = 'Student number is too long.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT userId FROM Users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO Users (fullName, email, studentNumber, passwordHash, role)
             VALUES (?, ?, ?, ?, 'User')"
        );
        $stmt->execute([
            $old['fullName'],
            $old['email'],
            $old['studentNumber'] !== '' ? $old['studentNumber'] : null,
            password_hash($password, PASSWORD_DEFAULT),
        ]);
        flash_set('success', 'Account created. You can now log in.');
        redirect('/auth/login.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Create an account</h1>

        <?php if ($errors): ?>
            <div class="flash flash-error" role="alert">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>

            <p>
                <label for="fullName">Full name</label><br>
                <input type="text" id="fullName" name="fullName" value="<?= e($old['fullName']) ?>" required maxlength="100">
            </p>
            <p>
                <label for="email">Email</label><br>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required maxlength="150">
            </p>
            <p>
                <label for="studentNumber">Student number (optional)</label><br>
                <input type="text" id="studentNumber" name="studentNumber" value="<?= e($old['studentNumber']) ?>" maxlength="30">
            </p>
            <p>
                <label for="password">Password (min 8 characters)</label><br>
                <input type="password" id="password" name="password" required minlength="8">
            </p>
            <p>
                <label for="confirm">Confirm password</label><br>
                <input type="password" id="confirm" name="confirm" required minlength="8">
            </p>
            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Log in</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
