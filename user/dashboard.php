<?php
$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/db.php';
require_login();

$userId = (int)$_SESSION['userId'];

$items = $pdo->prepare(
    'SELECT itemId, title, type, ItemStatus, dateLost, TimeCreated
     FROM Items WHERE userId = ? ORDER BY TimeCreated DESC'
);
$items->execute([$userId]);
$items = $items->fetchAll();

$claims = $pdo->prepare(
    'SELECT cl.claimId, cl.ClaimStatus, cl.TimeCreated, i.itemId, i.title,
            u.fullName AS ownerName, u.email AS ownerEmail
     FROM Claims cl
     JOIN Items i ON cl.itemId = i.itemId
     JOIN Users u ON i.userId = u.userId
     WHERE cl.claimantId = ?
     ORDER BY cl.TimeCreated DESC'
);
$claims->execute([$userId]);
$claims = $claims->fetchAll();

// Claims other people made on my items (just a count per item)
$incoming = $pdo->prepare(
    "SELECT itemId, COUNT(*) AS n FROM Claims
     WHERE ClaimStatus = 'pending' AND itemId IN (SELECT itemId FROM Items WHERE userId = ?)
     GROUP BY itemId"
);
$incoming->execute([$userId]);
$incomingMap = array_column($incoming->fetchAll(), 'n', 'itemId');

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
                    <tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th><th>Pending claims</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$it['itemId'] ?>"><?= e($it['title']) ?></a></td>
                        <td><?= e($it['type']) ?></td>
                        <td><?= e($it['ItemStatus']) ?></td>
                        <td><?= e(date('d M Y', strtotime($it['dateLost']))) ?></td>
                        <td><?= (int)($incomingMap[$it['itemId']] ?? 0) ?></td>
                        <td>
                            <?php if ($it['ItemStatus'] !== 'resolved'): ?>
                                <form method="post" action="<?= BASE_URL ?>/user/delete-item.php" data-confirm="Delete this report? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="itemId" value="<?= (int)$it['itemId'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                    </tr>
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
                            <?php if ($cl['ClaimStatus'] === 'approved'): ?>
                                Approved! Contact <?= e($cl['ownerName']) ?> at
                                <a href="mailto:<?= e($cl['ownerEmail']) ?>"><?= e($cl['ownerEmail']) ?></a> to arrange the handover.
                            <?php elseif ($cl['ClaimStatus'] === 'pending'): ?>
                                Waiting for admin review.
                            <?php else: ?>
                                Claim was not approved.
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
