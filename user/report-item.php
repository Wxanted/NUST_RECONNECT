<?php
$pageTitle = 'Report an Item';
require_once __DIR__ . '/../includes/db.php';
require_login();

[$categories, $byCampus] = load_form_options($pdo);

$errors = [];
$old = [
    'type' => (($_GET['type'] ?? '') === 'found') ? 'found' : 'lost',
    'title' => '', 'categoryId' => 0, 'locationId' => 0,
    'dateLost' => date('Y-m-d'), 'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old = item_input($_POST);
    $errors = validate_item($pdo, $old);

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
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO Items (userId, categoryId, locationId, type, title, description, dateLost, imagePath, ItemStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')"
            );
            $stmt->execute([
                (int)$_SESSION['userId'], $old['categoryId'], $old['locationId'], $old['type'],
                $old['title'], $old['description'], $old['dateLost'], $imagePath,
            ]);
            $newId = (int)$pdo->lastInsertId();
            flash_set('success', 'Your report is live. People can now see it and get in touch.');
            redirect('/items/details.php?id=' . $newId);
        } catch (Throwable $t) {
            delete_image($imagePath);
            $errors[] = 'Could not save your report. Please try again.';
        }
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

        <form method="post" action="" enctype="multipart/form-data" data-once>
            <?= csrf_field() ?>

            <fieldset>
                <legend>What happened?</legend>
                <label><input type="radio" name="type" value="lost" <?= $old['type'] === 'lost' ? 'checked' : '' ?>> I lost something</label>
                <label><input type="radio" name="type" value="found" <?= $old['type'] === 'found' ? 'checked' : '' ?>> I found something</label>
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
                        <option value="<?= (int)$c['categoryId'] ?>" <?= (int)$old['categoryId'] === (int)$c['categoryId'] ? 'selected' : '' ?>>
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
                                <option value="<?= (int)$l['locationId'] ?>" <?= (int)$old['locationId'] === (int)$l['locationId'] ? 'selected' : '' ?>>
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
                <textarea id="description" name="description" rows="5" cols="50" required maxlength="2000"
                    placeholder="Colour, brand, marks, what was inside... Keep one detail back (e.g. the phone wallpaper) so the real owner can prove it's theirs in person."><?= e($old['description']) ?></textarea>
            </p>

            <p>
                <label for="image">Photo (recommended, JPG/PNG/WEBP, max 3 MB)</label><br>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                <br><small>Lost something? Add a photo of it (or an identical one) so finders can recognise it.
                    Found something? Hide serial numbers and personal details in the photo.</small><br>
                <img id="imagePreview" class="photo-preview" alt="Photo preview" hidden>
            </p>

            <button type="submit">Publish report</button>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>