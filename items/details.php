<?php
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT i.*, c.categoryName, l.locationName, cp.campusName, u.fullName AS reporterName
     FROM Items i
     JOIN Categories c ON i.categoryId = c.categoryId
     JOIN Location l ON i.locationId = l.locationId
     JOIN Campus cp ON l.campusId = cp.campusId
     JOIN Users u ON i.userId = u.userId
     WHERE i.itemId = ?"
);
$stmt->execute([$id]);
$item = $stmt->fetch();

$userId = $_SESSION['userId'] ?? 0;
$isOwner = $item && (int)$item['userId'] === (int)$userId;

// Hidden items (pending/rejected) are only visible to the owner and admins
if (!$item || (!in_array($item['ItemStatus'], ['approved', 'resolved'], true) && !$isOwner && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'Item not found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<section class="section"><div class="container"><h1>Item not found</h1>
          <p><a href="' . BASE_URL . '/items/list.php">Back to browse</a></p></div></section>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = $item['title'];

// Has this user already got a pending claim?
$hasPending = false;
if ($userId) {
    $c = $pdo->prepare("SELECT 1 FROM Claims WHERE itemId = ? AND claimantId = ? AND ClaimStatus = 'pending'");
    $c->execute([$id, $userId]);
    $hasPending = (bool)$c->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <p><a href="<?= BASE_URL ?>/items/list.php">&laquo; Back to browse</a></p>

        <h1><?= e($item['title']) ?></h1>
        <p>
            <span class="badge badge-<?= e($item['type']) ?>"><?= e($item['type']) ?></span>
            <span class="badge badge-<?= e($item['ItemStatus']) ?>"><?= e($item['ItemStatus']) ?></span>
        </p>

        <?php if ($item['imagePath']): ?>
            <p><img src="<?= BASE_URL . '/' . e($item['imagePath']) ?>" alt="<?= e($item['title']) ?>" width="320"></p>
        <?php else: ?>
            <p>No photo provided.</p>
        <?php endif; ?>

        <dl>
            <dt>Category</dt><dd><?= e($item['categoryName']) ?></dd>
            <dt>Where</dt><dd><?= e($item['campusName']) ?> - <?= e($item['locationName']) ?></dd>
            <dt>Date <?= $item['type'] === 'lost' ? 'lost' : 'found' ?></dt>
            <dd><?= e(date('d M Y', strtotime($item['dateLost']))) ?></dd>
            <dt>Reported by</dt><dd><?= e($item['reporterName']) ?></dd>
            <dt>Description</dt><dd><?= nl2br(e($item['description'])) ?></dd>
        </dl>

        <h2>Claim this item</h2>
        <?php if ($item['ItemStatus'] === 'resolved'): ?>
            <p>This item has already been reunited with its owner.</p>
        <?php elseif ($item['ItemStatus'] !== 'approved'): ?>
            <p>This item is waiting for admin approval (<?= e($item['ItemStatus']) ?>) and cannot be claimed yet.</p>
        <?php elseif (!is_logged_in()): ?>
            <p><a href="<?= BASE_URL ?>/auth/login.php">Log in</a> or
               <a href="<?= BASE_URL ?>/auth/register.php">register</a> to claim this item.</p>
        <?php elseif ($isOwner): ?>
            <p>You reported this item, so you cannot claim it.</p>
        <?php elseif ($hasPending): ?>
            <p>You already have a claim waiting for admin review. Track it on your
               <a href="<?= BASE_URL ?>/user/dashboard.php">dashboard</a>.</p>
        <?php else: ?>
            <form method="post" action="<?= BASE_URL ?>/items/claim.php">
                <?= csrf_field() ?>
                <input type="hidden" name="itemId" value="<?= (int)$item['itemId'] ?>">

                <?php if ($item['proofQuestion']): ?>
                    <p>
                        <label for="answer">Proof question: <strong><?= e($item['proofQuestion']) ?></strong></label><br>
                        <input type="text" id="answer" name="answer" required maxlength="255">
                    </p>
                <?php endif; ?>

                <p>
                    <label for="message">
                        <?= $item['type'] === 'lost' ? 'Tell the owner where you found it / how to reach you' : 'Anything else to help verify the item is yours (optional)' ?>
                    </label><br>
                    <textarea id="message" name="message" rows="4" cols="50" maxlength="500"
                        <?= ($item['type'] === 'lost' && !$item['proofQuestion']) ? 'required' : '' ?>></textarea>
                </p>
                <button type="submit"><?= $item['type'] === 'lost' ? 'I found this item' : 'This is mine' ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
