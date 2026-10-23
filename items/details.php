<?php
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT i.*, c.categoryName, l.locationName, cp.campusName,
            u.fullName AS reporterName, u.email AS reporterEmail
     FROM Items i
     JOIN Categories c ON i.categoryId = c.categoryId
     JOIN Location l ON i.locationId = l.locationId
     JOIN Campus cp ON l.campusId = cp.campusId
     JOIN Users u ON i.userId = u.userId
     WHERE i.itemId = ?"
);
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    $pageTitle = 'Item not found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="section"><div class="container"><h1>Item not found</h1>
          <p><a href="' . BASE_URL . '/items/list.php">Back to browse</a></p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$userId = (int)($_SESSION['userId'] ?? 0);
$isOwner = (int)$item['userId'] === $userId;
$isFound = $item['type'] === 'found';
$resolved = $item['ItemStatus'] === 'resolved';
$canManage = ($isOwner || is_admin()) && !$resolved;
$canDelete = $canManage || is_admin();

$pageTitle = $item['title'];

// Does this user already have an open claim? How many open claims in total?
$hasPending = false;
if ($userId) {
    $c = $pdo->prepare("SELECT 1 FROM Claims WHERE itemId = ? AND claimantId = ? AND ClaimStatus = 'pending'");
    $c->execute([$id, $userId]);
    $hasPending = (bool)$c->fetchColumn();
}
$cnt = $pdo->prepare("SELECT COUNT(*) FROM Claims WHERE itemId = ? AND ClaimStatus = 'pending'");
$cnt->execute([$id]);
$waiting = (int)$cnt->fetchColumn();

// Needed to withdraw a claim
$myClaimId = 0;
if ($hasPending) {
    $m = $pdo->prepare("SELECT claimId FROM Claims WHERE itemId = ? AND claimantId = ? AND ClaimStatus = 'pending' LIMIT 1");
    $m->execute([$id, $userId]);
    $myClaimId = (int)$m->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <p><a href="<?= BASE_URL ?>/items/list.php">&laquo; Back to browse</a></p>

        <h1><?= e($item['title']) ?></h1>
        <p>
            <span class="badge badge-<?= e($item['type']) ?>"><?= e($item['type']) ?></span>
            <span class="badge badge-<?= e($item['ItemStatus']) ?>"><?= e(item_status_label($item['type'], $item['ItemStatus'])) ?></span>
        </p>

        <?php if ($item['imagePath']): ?>
            <p><img class="item-photo" src="<?= BASE_URL . '/' . e($item['imagePath']) ?>" alt="<?= e($item['title']) ?>" width="420"></p>
        <?php else: ?>
            <p>No photo provided.</p>
        <?php endif; ?>

        <dl>
            <dt>Category</dt><dd><?= e($item['categoryName']) ?></dd>
            <dt>Where</dt><dd><?= e($item['campusName']) ?> - <?= e($item['locationName']) ?></dd>
            <dt>Date <?= $isFound ? 'found' : 'lost' ?></dt>
            <dd><?= e(date('d M Y', strtotime($item['dateLost']))) ?></dd>
            <dt>Reported by</dt><dd><?= e($item['reporterName']) ?></dd>
            <dt>Description</dt><dd><?= nl2br(e($item['description'])) ?></dd>
        </dl>

        <?php if ($canManage || $canDelete): ?>
            <p>
                <?php if ($canManage): ?>
                    <a href="<?= BASE_URL ?>/user/edit-item.php?id=<?= (int)$item['itemId'] ?>">Edit / change photo</a>
                <?php endif; ?>
            </p>
            <?php if ($canDelete): ?>
                <form method="post" action="<?= BASE_URL ?>/user/delete-item.php" data-confirm="Delete this report permanently?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="itemId" value="<?= (int)$item['itemId'] ?>">
                    <button type="submit">Delete report</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <h2><?= $isFound ? 'Is this yours?' : 'Did you find this?' ?></h2>
        <?php if ($resolved): ?>
            <p>This item has already been reunited with its owner.</p>
        <?php elseif (!is_logged_in()): ?>
            <p><a href="<?= BASE_URL ?>/auth/login.php?next=1">Log in</a> or
               <a href="<?= BASE_URL ?>/auth/register.php">register</a> to <?= $isFound ? 'claim this item' : 'contact the owner' ?>.</p>
        <?php elseif ($isOwner): ?>
            <p>You reported this item.
                <?= $waiting > 0
                    ? '<strong>' . $waiting . '</strong> claim' . ($waiting === 1 ? ' is' : 's are') . ' waiting for you on your '
                    : 'Claims will appear on your ' ?>
                <a href="<?= BASE_URL ?>/user/dashboard.php">dashboard</a>.</p>
        <?php elseif ($hasPending): ?>
            <div class="claim-box">
                <p><strong>Your claim is recorded.</strong> No approval needed. Next steps:</p>
                <?php if ($isFound): ?>
                    <p>Contact <?= e($item['reporterName']) ?> at
                       <a href="mailto:<?= e($item['reporterEmail']) ?>"><?= e($item['reporterEmail']) ?></a>
                       to arrange collection. You will be asked to prove the item is yours in person, so bring your
                       student card and be ready to describe it (marks, contents, wallpaper, etc.).</p>
                <?php else: ?>
                    <p>Contact <?= e($item['reporterName']) ?> at
                       <a href="mailto:<?= e($item['reporterEmail']) ?>"><?= e($item['reporterEmail']) ?></a>
                       to let them know you have their item and arrange the handover.</p>
                <?php endif; ?>
                <p>Meet in a public place on campus, such as the library or admin block.</p>
                <form method="post" action="<?= BASE_URL ?>/user/claim-action.php" data-confirm="Withdraw your claim?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="claimId" value="<?= $myClaimId ?>">
                    <button type="submit" name="action" value="withdraw">Withdraw my claim</button>
                </form>
            </div>
        <?php else: ?>
            <?php if ($waiting > 0): ?>
                <p><small><?= $waiting ?> other claim<?= $waiting === 1 ? '' : 's' ?> already submitted. The owner will check everyone in person.</small></p>
            <?php endif; ?>
            <form method="post" action="<?= BASE_URL ?>/items/claim.php" data-once>
                <?= csrf_field() ?>
                <input type="hidden" name="itemId" value="<?= (int)$item['itemId'] ?>">
                <p>
                    <label for="message">
                        <?= $isFound
                            ? 'Describe the item so the finder knows it is yours (marks, contents, when you lost it)'
                            : 'Where did you find it? Any details that help the owner' ?>
                    </label><br>
                    <textarea id="message" name="message" rows="4" cols="50" minlength="10" maxlength="500" required></textarea>
                </p>
                <p><small>You'll get the reporter's contact details straight after submitting.
                    <?= $isFound ? 'Real proof of ownership is checked in person when you collect it.' : '' ?></small></p>
                <button type="submit"><?= $isFound ? 'This is mine' : 'I found this item' ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>