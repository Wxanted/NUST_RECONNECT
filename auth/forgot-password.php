<?php
$pageTitle = 'Forgot password';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mail.php';

if (is_logged_in()) {
    redirect('/user/dashboard.php');
}

$sent = false;
$devLink = null;
$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(input_str($_POST, 'email'));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Please enter a valid email address.';
    } else {
        // Tidy up old tokens
        $pdo->exec("DELETE FROM PasswordResets WHERE expiresAt < (NOW() - INTERVAL 1 DAY)");

        $stmt = $pdo->prepare('SELECT userId, fullName FROM Users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Max 3 requests per hour per account
            $c = $pdo->prepare('SELECT COUNT(*) FROM PasswordResets WHERE userId = ? AND TimeCreated > (NOW() - INTERVAL 1 HOUR)');
            $c->execute([(int)$user['userId']]);

            if ((int)$c->fetchColumn() < 3) {
                $token = bin2hex(random_bytes(32));
                $pdo->prepare(
                    'INSERT INTO PasswordResets (userId, tokenHash, expiresAt)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                )->execute([(int)$user['userId'], hash('sha256', $token)]);

                $link = SITE_URL . '/auth/reset-password.php?token=' . $token;
                $body = "Hi " . $user['fullName'] . ",\n\n"
                      . "We received a request to reset your " . APP_NAME . " password.\n"
                      . "Open this link within 1 hour to choose a new password:\n\n"
                      . $link . "\n\n"
                      . "If you didn't ask for this, you can ignore this email. Your password will not change.\n";

                $ok = send_mail($email, 'Reset your ' . APP_NAME . ' password', $body);
                if (!$ok) {
                    error_log('Password reset email could not be sent to ' . $email);
                }
                if (APP_ENV === 'development') {
                    $devLink = $link;
                }
            }
        }
        // Same answer whether or not the account exists
        $sent = true;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Forgot your password?</h1>

        <?php if ($error): ?>
            <div class="flash flash-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($sent): ?>
            <div class="flash flash-success" role="status">
                If an account exists for <strong><?= e($email) ?></strong>, we've sent a reset link.
                It is valid for 1 hour. Check your spam folder too.
            </div>
            <?php if ($devLink): ?>
                <div class="claim-box">
                    <p><strong>Development mode:</strong> email may not be set up on localhost, so here is your link:</p>
                    <p><a href="<?= e($devLink) ?>"><?= e($devLink) ?></a></p>
                </div>
            <?php endif; ?>
            <p><a href="<?= BASE_URL ?>/auth/login.php">Back to log in</a></p>
        <?php else: ?>
            <p>Enter the email you registered with and we'll send you a link to choose a new password.</p>
            <form method="post" action="" data-once>
                <?= csrf_field() ?>
                <p>
                    <label for="email">Email</label><br>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="150" autocomplete="email">
                </p>
                <button type="submit">Send reset link</button>
            </form>
            <p><a href="<?= BASE_URL ?>/auth/login.php">Back to log in</a></p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>