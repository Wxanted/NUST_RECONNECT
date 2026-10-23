<?php
$pageTitle = 'Edit Item';
require_once __DIR__ . '/../includes/db.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM Items WHERE itemId = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item || ((int)$item['userId'] !== (int)$_SESSION['userId'] && !is_admin())) {
    flash_set('error', 'You cannot edit that item.');
    redirect('/user/dashboard.php');
}
if ($item['ItemStatus'] === 'resolved') {
    flash_set('error', 'Resolved items can no longer be edited.');
    redirect('/user/dashboard.php');
}

[$categories, $byCampus] = load_form_options($pdo);

$errors = [];
$old = [
    'type'        => $item['type'],
    'title'       => $item['title'],
    'categoryId'  => (int)$item['categoryId'],
    'locationId'  => (int)$item['locationId'],
    'dateLost'    => $item['dateLost'],
    'description' => $item['description'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old = item_input($_POST);
    $old['type'] = $item['type']; // type cannot be changed
    $errors = validate_item($pdo, $old);

    $newPath = $item['imagePath'];
    $deleteAfter = null;
    $saved = null;

    if (!$errors) {
        $imgError = null;
        $saved = save_image($_FILES['image'] ?? [], $imgError);
        if ($saved === false) {
            $errors[] = $imgError;
        } elseif ($saved !== null) {
            $deleteAfter = $item['imagePath'];
            $newPath = $saved;
        } elseif (!empty($_POST['removeImage'])) {
            $deleteAfter = $item['imagePath'];
            $newPath = null;
        }
    }

    if (!$errors) {
        try {
            $pdo->prepare(
                'UPDATE Items SET categoryId = ?, locationId = ?, title = ?, description = ?, dateLost = ?, imagePath = ?
                 WHERE itemId = ?'
            )->execute([
                $old['categoryId'], $old['locationId'], $old['title'],
                $old['description'], $old['dateLost'], $newPath, $id,
            ]);
            delete_image($deleteAfter);
            flash_set('success', 'Your report was updated.');
            redirect('/items/details.php?id=' . $id);
        } catch (Throwable $t) {
            if (is_string($saved)) {
                delete_image($saved);
            }
            $errors[] = 'Could not save your changes. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <p><a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$id ?>">&laquo; Back to item</a></p>
        <h1>Edit <?= e($item['type']) ?> report</h1>

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
            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <p>
                <label for="title">Title</label><br>
                <input type="text" id="title" name="title" value="<?= e($old['title']) ?>" required maxlength="150">
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
                <textarea id="description" name="description" rows="5" cols="50" required maxlength="2000"><?= e($old['description']) ?></textarea>
            </p>

            <p>
                <?php if ($item['imagePath']): ?>
                    Current photo:<br>
                    <img src="<?= BASE_URL . '/' . e($item['imagePath']) ?>" class="photo-preview" alt="Current photo"><br>
                    <label><input type="checkbox" name="removeImage" value="1"> Remove this photo</label><br>
                <?php endif; ?>
                <label for="image"><?= $item['imagePath'] ? 'Replace photo' : 'Add a photo' ?> (JPG/PNG/WEBP, max 3 MB)</label><br>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"><br>
                <img id="imagePreview" class="photo-preview" alt="New photo preview" hidden>
            </p>

            <button type="submit">Save changes</button>
            <a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$id ?>">Cancel</a>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>