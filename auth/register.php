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

    $old['fullName'] = input_str($_POST, 'fullName');
    $old['email'] = strtolower(input_str($_POST, 'email'));
    $old['studentNumber'] = input_str($_POST, 'studentNumber');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['confirm'] ?? null) ? $_POST['confirm'] : '';

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
    } elseif (strlen($password) > 72) {
        $errors[] = 'Password must be at most 72 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
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

            // Log the new user in straight away
            login_user([
                'userId'   => (int)$pdo->lastInsertId(),
                'fullName' => $old['fullName'],
                'role'     => 'User',
            ]);
            flash_set('success', 'Welcome to ' . APP_NAME . ', ' . $old['fullName'] . '! Your account is ready.');
            after_login_redirect('/user/dashboard.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $errors[] = 'An account with that email already exists.';
            } else {
                $errors[] = 'Something went wrong creating your account. Please try again.';
            }
        }
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

        <form method="post" action="" data-once>
            <?= csrf_field() ?>

            <p>
                <label for="fullName">Full name</label><br>
                <input type="text" id="fullName" name="fullName" value="<?= e($old['fullName']) ?>" required maxlength="100" autocomplete="name">
            </p>
            <p>
                <label for="email">Email</label><br>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required maxlength="150" autocomplete="email">
            </p>
            <p>
                <label for="studentNumber">Student number (optional)</label><br>
                <input type="text" id="studentNumber" name="studentNumber" value="<?= e($old['studentNumber']) ?>" maxlength="30">
            </p>
            <p>
                <label for="password">Password (min 8 characters)</label><br>
                <input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
            </p>
            <p>
                <label for="confirm">Confirm password</label><br>
                <input type="password" id="confirm" name="confirm" required minlength="8" maxlength="72" autocomplete="new-password">
            </p>
            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Log in</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>