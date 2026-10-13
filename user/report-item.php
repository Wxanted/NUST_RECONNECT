<?php
$pageTitle = 'Report an Item';
require_once __DIR__ . '/../includes/db.php';
require_login();

$categories = $pdo->query('SELECT categoryId, categoryName FROM Categories ORDER BY categoryName')->fetchAll();
$locRows = $pdo->query(
    'SELECT l.locationId, l.locationName, cp.campusName
     FROM Location l JOIN Campus cp ON l.campusId = cp.campusId
     ORDER BY cp.campusName, l.locationName'
)->fetchAll();
$byCampus = [];
foreach ($locRows as $r) {
    $byCampus[$r['campusName']][] = $r;
}

$errors = [];
$old = [
    'type' => 'lost', 'title' => '', 'categoryId' => 0, 'locationId' => 0,
    'dateLost' => date('Y-m-d'), 'description' => '', 'proofQuestion' => '', 'proofAnswer' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['type'] = $_POST['type'] ?? '';
    $old['title'] = trim($_POST['title'] ?? '');
    $old['categoryId'] = (int)($_POST['categoryId'] ?? 0);
    $old['locationId'] = (int)($_POST['locationId'] ?? 0);
    $old['dateLost'] = trim($_POST['dateLost'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['proofQuestion'] = trim($_POST['proofQuestion'] ?? '');
    $old['proofAnswer'] = trim($_POST['proofAnswer'] ?? '');

    if (!in_array($old['type'], ['lost', 'found'], true)) {
        $errors[] = 'Choose whether the item was lost or found.';
    }
    if ($old['title'] === '' || mb_strlen($old['title']) > 150) {
        $errors[] = 'Title is required (max 150 characters).';
    }
    if ($old['description'] === '' || mb_strlen($old['description']) > 2000) {
        $errors[] = 'Description is required (max 2000 characters).';
    }

    $catOk = $pdo->prepare('SELECT 1 FROM Categories WHERE categoryId = ?');
    $catOk->execute([$old['categoryId']]);
    if (!$catOk->fetchColumn()) {
        $errors[] = 'Choose a category.';
    }
    $locOk = $pdo->prepare('SELECT 1 FROM Location WHERE locationId = ?');
    $locOk->execute([$old['locationId']]);
    if (!$locOk->fetchColumn()) {
        $errors[] = 'Choose a location.';
    }

    $d = DateTime::createFromFormat('Y-m-d', $old['dateLost']);
    if (!$d || $d->format('Y-m-d') !== $old['dateLost']) {
        $errors[] = 'Enter a valid date.';
    } elseif ($old['dateLost'] > date('Y-m-d')) {
        $errors[] = 'The date cannot be in the future.';
    }

    if ($old['type'] === 'found' && ($old['proofQuestion'] === '' || $old['proofAnswer'] === '')) {
        $errors[] = 'For found items, add a proof question and its answer so the real owner can be verified.';
    }
    if (mb_strlen($old['proofQuestion']) > 255 || mb_strlen($old['proofAnswer']) > 255) {
        $errors[] = 'Proof question/answer is too long (max 255 characters).';
    }

    $imagePath = null;
    if (!$errors) {
        $imgError = null;
        $saved = save_image($_FILES['image'] ?? [], $imgError);
        if ($saved === false) {
            $errors[] = $imgError;
        } else {
            $imagePath = $saved;
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO Items (userId, categoryId, locationId, type, title, description, dateLost,
                                imagePath, proofQuestion, proofAnswer, ItemStatus)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([
            (int)$_SESSION['userId'], $old['categoryId'], $old['locationId'], $old['type'],
            $old['title'], $old['description'], $old['dateLost'], $imagePath,
            $old['proofQuestion'] !== '' ? $old['proofQuestion'] : null,
            $old['proofAnswer'] !== '' ? $old['proofAnswer'] : null,
        ]);
        flash_set('success', 'Item submitted. It will appear on the site once an admin approves it.');
        redirect('/user/dashboard.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Report an item</h1>

        <?php if ($errors): ?>
            <div class="flash flash-error" role="alert">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <fieldset>
                <legend>What happened?</legend>
                <label><input type="radio" name="type" value="lost" data-proof-toggle <?= $old['type'] === 'lost' ? 'checked' : '' ?>> I lost something</label>
                <label><input type="radio" name="type" value="found" data-proof-toggle <?= $old['type'] === 'found' ? 'checked' : '' ?>> I found something</label>
            </fieldset>

            <p>
                <label for="title">Title</label><br>
                <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" required maxlength="150" placeholder="e.g. Black student card">
            </p>

            <p>
                <label for="categoryId">Category</label><br>
                <select id="categoryId" name="categoryId" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['categoryId'] ?>" <?= $old['categoryId'] === (int)$c['categoryId'] ? 'selected' : '' ?>>
                            <?= e($c['categoryName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="locationId">Location</label><br>
                <select id="locationId" name="locationId" required>
                    <option value="">Select a location</option>
                    <?php foreach ($byCampus as $campus => $locs): ?>
                        <optgroup label="<?= e($campus) ?>">
                            <?php foreach ($locs as $l): ?>
                                <option value="<?= (int)$l['locationId'] ?>" <?= $old['locationId'] === (int)$l['locationId'] ? 'selected' : '' ?>>
                                    <?= e($l['locationName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="dateLost">Date lost / found</label><br>
                <input type="date" id="dateLost" name="dateLost" value="<?= e($old['dateLost']) ?>" max="<?= date('Y-m-d') ?>" required>
            </p>

            <p>
                <label for="description">Description</label><br>
                <textarea id="description" name="description" rows="5" cols="50" required maxlength="2000"><?= e($old['description']) ?></textarea>
            </p>

            <p>
                <label for="image">Photo (optional, JPG/PNG/WEBP, max 3 MB)</label><br>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            </p>

            <fieldset>
                <legend>Proof question</legend>
                <p id="proofNote">Required for found items: ask something only the real owner would know
                   (e.g. "What is the phone wallpaper?"). Don't reveal the answer in the description.</p>
                <p>
                    <label for="proofQuestion">Question</label><br>
                    <input type="text" id="proofQuestion" name="proofQuestion" value="<?= e($old['proofQuestion']) ?>" maxlength="255">
                </p>
                <p>
                    <label for="proofAnswer">Correct answer (only admins see this)</label><br>
                    <input type="text" id="proofAnswer" name="proofAnswer" value="<?= e($old['proofAnswer']) ?>" maxlength="255">
                </p>
            </fieldset>

            <button type="submit">Submit report</button>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
