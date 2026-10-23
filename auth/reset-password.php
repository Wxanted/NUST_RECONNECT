<?php
$pageTitle = 'Reset password';
require_once __DIR__ . '/../includes/db.php';

// Don't leak the token to other sites through the Referer header
header('Referrer-Policy: no-referrer');

$token = '';
if (is_string($_POST['token'] ?? null)) {
    $token = $_POST['token'];
} elseif (is_string($_GET['token'] ?? null)) {
    $token = $_GET['token'];
}

$row = null;
if (preg_match('/^[a-f0-9]{64}$/', $token) === 1) {
    $stmt = $pdo->prepare(
        'SELECT resetId, userId FROM PasswordResets
         WHERE tokenHash = ? AND usedAt IS NULL AND expiresAt > NOW()'
    );
    $stmt->execute([hash('sha256', $token)]);
    $row = $stmt->fetch() ?: null;
}

$errors = [];

if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['confirm'] ?? null) ? $_POST['confirm'] : '';

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
            $pdo->beginTransaction();

            // Re-check inside the transaction so a link can only be used once
            $s = $pdo->prepare(
                'SELECT resetId, userId FROM PasswordResets
                 WHERE resetId = ? AND usedAt IS NULL AND expiresAt > NOW() FOR UPDATE'
            );
            $s->execute([(int)$row['resetId']]);
            $locked = $s->fetch();

            if (!$locked) {
                $pdo->rollBack();
                $row = null; // shows the "invalid link" message below
            } else {
                $pdo->prepare('UPDATE Users SET passwordHash = ? WHERE userId = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), (int)$locked['userId']]);
                // Void this link and every other open link for the user
                $pdo->prepare('UPDATE PasswordResets SET usedAt = NOW() WHERE userId = ? AND usedAt IS NULL')
                    ->execute([(int)$locked['userId']]);
                $pdo->commit();

                flash_set('success', 'Your password was changed. You can now log in.');
                redirect('/auth/login.php');
            }
        } catch (Throwable $t) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Choose a new password</h1>

        <?php if (!$row): ?>
            <div class="flash flash-error" role="alert">
                This reset link is invalid, has expired, or was already used.
            </div>
            <p><a href="<?= BASE_URL ?>/auth/forgot-password.php">Request a new link</a></p>
        <?php else: ?>
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
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <p>
                    <label for="password">New password (min 8 characters)</label><br>
                    <input type="password" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
                </p>
                <p>
                    <label for="confirm">Confirm new password</label><br>
                    <input type="password" id="confirm" name="confirm" required minlength="8" maxlength="72" autocomplete="new-password">
                </p>
                <button type="submit">Change password</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>