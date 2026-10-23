<?php
$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/db.php';
require_login();

$userId = (int)$_SESSION['userId'];

$stmt = $pdo->prepare(
    'SELECT itemId, title, type, ItemStatus, dateLost, TimeCreated
     FROM Items WHERE userId = ? ORDER BY TimeCreated DESC'
);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

// Open claims people made on MY items, grouped by item
$stmt = $pdo->prepare(
    "SELECT cl.claimId, cl.itemId, cl.message, cl.TimeCreated,
            u.fullName, u.email, u.studentNumber
     FROM Claims cl
     JOIN Items i ON cl.itemId = i.itemId
     JOIN Users u ON cl.claimantId = u.userId
     WHERE i.userId = ? AND cl.ClaimStatus = 'pending'
     ORDER BY cl.TimeCreated"
);
$stmt->execute([$userId]);
$incoming = [];
foreach ($stmt->fetchAll() as $row) {
    $incoming[$row['itemId']][] = $row;
}

// Claims I made on other people's items
$stmt = $pdo->prepare(
    'SELECT cl.claimId, cl.ClaimStatus, cl.TimeCreated,
            i.itemId, i.title, i.type,
            u.fullName AS ownerName, u.email AS ownerEmail
     FROM Claims cl
     JOIN Items i ON cl.itemId = i.itemId
     JOIN Users u ON i.userId = u.userId
     WHERE cl.claimantId = ?
     ORDER BY cl.TimeCreated DESC'
);
$stmt->execute([$userId]);
$claims = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Hello, <?= e($_SESSION['fullName']) ?></h1>
        <p><a href="<?= BASE_URL ?>/user/report-item.php">Report a new item</a></p>

        <h2>My reports</h2>
        <?php if (!$items): ?>
            <p class="empty-state">You haven't reported anything yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$it['itemId'] ?>"><?= e($it['title']) ?></a></td>
                        <td><?= e($it['type']) ?></td>
                        <td><?= e(item_status_label($it['type'], $it['ItemStatus'])) ?></td>
                        <td><?= e(date('d M Y', strtotime($it['dateLost']))) ?></td>
                        <td>
                            <?php if ($it['ItemStatus'] !== 'resolved'): ?>
                                <a href="<?= BASE_URL ?>/user/edit-item.php?id=<?= (int)$it['itemId'] ?>">Edit</a>
                                <form method="post" action="<?= BASE_URL ?>/user/delete-item.php" class="inline-form" data-confirm="Delete this report? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="itemId" value="<?= (int)$it['itemId'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($incoming[$it['itemId']])): ?>
                        <tr>
                            <td colspan="5">
                                <strong><?= count($incoming[$it['itemId']]) ?> open claim<?= count($incoming[$it['itemId']]) === 1 ? '' : 's' ?> on "<?= e($it['title']) ?>"</strong>
                                <?php foreach ($incoming[$it['itemId']] as $cl): ?>
                                    <div class="claim-box">
                                        <p>
                                            <strong><?= e($cl['fullName']) ?></strong>
                                            (<a href="mailto:<?= e($cl['email']) ?>"><?= e($cl['email']) ?></a>)
                                            <?php if ($cl['studentNumber']): ?> &middot; Student no. <?= e($cl['studentNumber']) ?><?php endif; ?>
                                            &middot; <?= e(date('d M Y H:i', strtotime($cl['TimeCreated']))) ?>
                                        </p>
                                        <p><?= nl2br(e($cl['message'])) ?></p>
                                        <p><small>Ask for proof in person (student card, a detail only the owner knows) before handing over.</small></p>
                                        <form method="post" action="<?= BASE_URL ?>/user/claim-action.php" class="inline-form"
                                              data-confirm="Mark this item as returned? This closes all other claims.">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="claimId" value="<?= (int)$cl['claimId'] ?>">
                                            <button type="submit" name="action" value="complete">
                                                <?= $it['type'] === 'found' ? 'Handed over - mark as returned' : 'I got it back' ?>
                                            </button>
                                        </form>
                                        <form method="post" action="<?= BASE_URL ?>/user/claim-action.php" class="inline-form"
                                              data-confirm="Decline this claim?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="claimId" value="<?= (int)$cl['claimId'] ?>">
                                            <button type="submit" name="action" value="reject">Decline</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>My claims</h2>
        <?php if (!$claims): ?>
            <p class="empty-state">You haven't claimed any items.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Item</th><th>Submitted</th><th>Status</th><th>Next step</th></tr>
                </thead>
                <tbody>
                <?php foreach ($claims as $cl): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$cl['itemId'] ?>"><?= e($cl['title']) ?></a></td>
                        <td><?= e(date('d M Y', strtotime($cl['TimeCreated']))) ?></td>
                        <td><?= e($cl['ClaimStatus']) ?></td>
                        <td>
                            <?php if ($cl['ClaimStatus'] === 'pending'): ?>
                                <?php if ($cl['type'] === 'found'): ?>
                                    Contact <?= e($cl['ownerName']) ?> at
                                    <a href="mailto:<?= e($cl['ownerEmail']) ?>"><?= e($cl['ownerEmail']) ?></a>
                                    and collect it in person. Bring your student card and be ready to prove it's yours.
                                <?php else: ?>
                                    Contact <?= e($cl['ownerName']) ?> at
                                    <a href="mailto:<?= e($cl['ownerEmail']) ?>"><?= e($cl['ownerEmail']) ?></a>
                                    to arrange handing the item back.
                                <?php endif; ?>
                                <form method="post" action="<?= BASE_URL ?>/user/claim-action.php" class="inline-form" data-confirm="Withdraw this claim?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="claimId" value="<?= (int)$cl['claimId'] ?>">
                                    <button type="submit" name="action" value="withdraw">Withdraw</button>
                                </form>
                            <?php elseif ($cl['ClaimStatus'] === 'completed'): ?>
                                Done. The item was handed over.
                            <?php elseif ($cl['ClaimStatus'] === 'withdrawn'): ?>
                                You withdrew this claim.
                            <?php else: ?>
                                The reporter declined this claim or the item went to someone else.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>