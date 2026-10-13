<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    switch ($action) {
        case 'item_approve':
        case 'item_reject':
            $status = $action === 'item_approve' ? 'approved' : 'rejected';
            $pdo->prepare("UPDATE Items SET ItemStatus = ? WHERE itemId = ? AND ItemStatus = 'pending'")
                ->execute([$status, $id]);
            flash_set('success', 'Item ' . $status . '.');
            break;

        case 'claim_approve':
            $pdo->beginTransaction();
            try {
                $cl = $pdo->prepare(
                    "SELECT cl.itemId FROM Claims cl
                     JOIN Items i ON cl.itemId = i.itemId
                     WHERE cl.claimId = ? AND cl.ClaimStatus = 'pending' AND i.ItemStatus = 'approved'
                     FOR UPDATE"
                );
                $cl->execute([$id]);
                $itemId = $cl->fetchColumn();
                if ($itemId) {
                    $pdo->prepare("UPDATE Claims SET ClaimStatus = 'approved' WHERE claimId = ?")->execute([$id]);
                    $pdo->prepare("UPDATE Claims SET ClaimStatus = 'rejected' WHERE itemId = ? AND claimId <> ? AND ClaimStatus = 'pending'")
                        ->execute([$itemId, $id]);
                    $pdo->prepare("UPDATE Items SET ItemStatus = 'resolved' WHERE itemId = ?")->execute([$itemId]);
                    flash_set('success', 'Claim approved. The item is now marked as resolved.');
                } else {
                    flash_set('error', 'That claim can no longer be approved.');
                }
                $pdo->commit();
            } catch (Throwable $t) {
                $pdo->rollBack();
                flash_set('error', 'Something went wrong approving the claim.');
            }
            break;

        case 'claim_reject':
            $pdo->prepare("UPDATE Claims SET ClaimStatus = 'rejected' WHERE claimId = ? AND ClaimStatus = 'pending'")
                ->execute([$id]);
            flash_set('success', 'Claim rejected.');
            break;

        case 'message_delete':
            $pdo->prepare('DELETE FROM Messages WHERE messageId = ?')->execute([$id]);
            flash_set('success', 'Message deleted.');
            break;
    }
    redirect('/admin/dashboard.php');
}

$stats = [
    'users'    => $pdo->query('SELECT COUNT(*) FROM Users')->fetchColumn(),
    'pending'  => $pdo->query("SELECT COUNT(*) FROM Items WHERE ItemStatus = 'pending'")->fetchColumn(),
    'active'   => $pdo->query("SELECT COUNT(*) FROM Items WHERE ItemStatus = 'approved'")->fetchColumn(),
    'resolved' => $pdo->query("SELECT COUNT(*) FROM Items WHERE ItemStatus = 'resolved'")->fetchColumn(),
];

$pendingItems = $pdo->query(
    "SELECT i.itemId, i.title, i.type, i.description, i.dateLost, i.proofQuestion, i.proofAnswer,
            c.categoryName, l.locationName, cp.campusName, u.fullName, u.email
     FROM Items i
     JOIN Categories c ON i.categoryId = c.categoryId
     JOIN Location l ON i.locationId = l.locationId
     JOIN Campus cp ON l.campusId = cp.campusId
     JOIN Users u ON i.userId = u.userId
     WHERE i.ItemStatus = 'pending'
     ORDER BY i.TimeCreated"
)->fetchAll();

$pendingClaims = $pdo->query(
    "SELECT cl.claimId, cl.answer, cl.message, cl.TimeCreated,
            i.itemId, i.title, i.type, i.proofQuestion, i.proofAnswer,
            cu.fullName AS claimantName, cu.email AS claimantEmail,
            ou.fullName AS ownerName
     FROM Claims cl
     JOIN Items i ON cl.itemId = i.itemId
     JOIN Users cu ON cl.claimantId = cu.userId
     JOIN Users ou ON i.userId = ou.userId
     WHERE cl.ClaimStatus = 'pending' AND i.ItemStatus = 'approved'
     ORDER BY cl.TimeCreated"
)->fetchAll();

$messages = $pdo->query('SELECT * FROM Messages ORDER BY TimeCreated DESC LIMIT 20')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Admin dashboard</h1>

        <ul class="admin-stats">
            <li>Users: <strong><?= (int)$stats['users'] ?></strong></li>
            <li>Awaiting approval: <strong><?= (int)$stats['pending'] ?></strong></li>
            <li>Active listings: <strong><?= (int)$stats['active'] ?></strong></li>
            <li>Resolved: <strong><?= (int)$stats['resolved'] ?></strong></li>
        </ul>

        <h2>Items awaiting approval (<?= count($pendingItems) ?>)</h2>
        <?php if (!$pendingItems): ?>
            <p class="empty-state">Nothing to review.</p>
        <?php endif; ?>
        <?php foreach ($pendingItems as $it): ?>
            <article class="admin-card">
                <h3><?= e($it['title']) ?> <small>(<?= e($it['type']) ?>)</small></h3>
                <p>
                    <?= e($it['categoryName']) ?> &middot; <?= e($it['campusName']) ?> - <?= e($it['locationName']) ?>
                    &middot; <?= e(date('d M Y', strtotime($it['dateLost']))) ?><br>
                    Reported by <?= e($it['fullName']) ?> (<?= e($it['email']) ?>)
                </p>
                <p><?= nl2br(e($it['description'])) ?></p>
                <?php if ($it['proofQuestion']): ?>
                    <p>Proof question: <?= e($it['proofQuestion']) ?><br>Answer: <?= e($it['proofAnswer']) ?></p>
                <?php endif; ?>
                <p>
                    <a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$it['itemId'] ?>">Open page</a>
                </p>
                <form method="post" action="" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$it['itemId'] ?>">
                    <button type="submit" name="action" value="item_approve">Approve</button>
                    <button type="submit" name="action" value="item_reject">Reject</button>
                </form>
                <form method="post" action="<?= BASE_URL ?>/user/delete-item.php" style="display:inline" data-confirm="Delete this item permanently?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="itemId" value="<?= (int)$it['itemId'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </article>
        <?php endforeach; ?>

        <h2>Claims awaiting verification (<?= count($pendingClaims) ?>)</h2>
        <?php if (!$pendingClaims): ?>
            <p class="empty-state">No claims to verify.</p>
        <?php endif; ?>
        <?php foreach ($pendingClaims as $cl): ?>
            <article class="admin-card">
                <h3>
                    <a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$cl['itemId'] ?>"><?= e($cl['title']) ?></a>
                    <small>(<?= e($cl['type']) ?>, reported by <?= e($cl['ownerName']) ?>)</small>
                </h3>
                <p>Claimant: <?= e($cl['claimantName']) ?> (<?= e($cl['claimantEmail']) ?>)
                   &middot; <?= e(date('d M Y H:i', strtotime($cl['TimeCreated']))) ?></p>
                <?php if ($cl['proofQuestion']): ?>
                    <p>
                        Question: <?= e($cl['proofQuestion']) ?><br>
                        Correct answer: <strong><?= e($cl['proofAnswer']) ?></strong><br>
                        Claimant answered: <strong><?= e($cl['answer']) ?></strong>
                        <?php if (strcasecmp(trim((string)$cl['answer']), trim((string)$cl['proofAnswer'])) === 0): ?>
                            (exact match)
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if ($cl['message']): ?>
                    <p>Message: <?= nl2br(e($cl['message'])) ?></p>
                <?php endif; ?>
                <form method="post" action="" data-confirm="Are you sure?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$cl['claimId'] ?>">
                    <button type="submit" name="action" value="claim_approve">Approve &amp; mark resolved</button>
                    <button type="submit" name="action" value="claim_reject">Reject</button>
                </form>
            </article>
        <?php endforeach; ?>

        <h2>Contact messages</h2>
        <?php if (!$messages): ?>
            <p class="empty-state">No messages.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Date</th><th>From</th><th>Message</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr>
                        <td><?= e(date('d M Y H:i', strtotime($m['TimeCreated']))) ?></td>
                        <td><?= e($m['name']) ?><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></td>
                        <td><?= nl2br(e($m['message'])) ?></td>
                        <td>
                            <form method="post" action="" data-confirm="Delete this message?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$m['messageId'] ?>">
                                <button type="submit" name="action" value="message_delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
