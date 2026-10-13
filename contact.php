<?php
$pageTitle = 'Contact';
require_once __DIR__ . '/includes/db.php';

$errors = [];
$old = [
    'name'    => $_SESSION['fullName'] ?? '',
    'email'   => '',
    'message' => '',
];

if (is_logged_in()) {
    $s = $pdo->prepare('SELECT email FROM Users WHERE userId = ?');
    $s->execute([(int)$_SESSION['userId']]);
    $old['email'] = (string)$s->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    if ($old['name'] === '' || mb_strlen($old['name']) > 100) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['message'] === '' || mb_strlen($old['message']) > 2000) {
        $errors[] = 'Please enter a message (max 2000 characters).';
    }

    if (!$errors) {
        $pdo->prepare('INSERT INTO Messages (name, email, message) VALUES (?, ?, ?)')
            ->execute([$old['name'], $old['email'], $old['message']]);
        flash_set('success', 'Thanks! Your message was sent. We will get back to you soon.');
        redirect('/contact.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Contact us</h1>
        <p>Questions, feedback, or a problem with a listing? Send us a message.</p>

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
                <label for="name">Name</label><br>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required maxlength="100">
            </p>
            <p>
                <label for="email">Email</label><br>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required maxlength="150">
            </p>
            <p>
                <label for="message">Message</label><br>
                <textarea id="message" name="message" rows="6" cols="50" required maxlength="2000"><?= e($old['message']) ?></textarea>
            </p>
            <button type="submit">Send message</button>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
