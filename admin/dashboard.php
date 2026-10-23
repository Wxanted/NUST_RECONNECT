<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = input_str($_POST, 'action');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'message_delete') {
        $pdo->prepare('DELETE FROM Messages WHERE messageId = ?')->execute([$id]);
        flash_set('success', 'Message deleted.');
    }
    redirect('/admin/dashboard.php');
}

$stats = [
    'users'    => $pdo->query('SELECT COUNT(*) FROM Users')->fetchColumn(),
    'active'   => $pdo->query("SELECT COUNT(*) FROM Items WHERE ItemStatus = 'active'")->fetchColumn(),
    'resolved' => $pdo->query("SELECT COUNT(*) FROM Items WHERE ItemStatus = 'resolved'")->fetchColumn(),
    'claims'   => $pdo->query("SELECT COUNT(*) FROM Claims WHERE ClaimStatus = 'pending'")->fetchColumn(),
];

$recentItems = $pdo->query(
    "SELECT i.itemId, i.title, i.type, i.ItemStatus, i.TimeCreated,
            c.categoryName, l.locationName, cp.campusName, u.fullName, u.email
     FROM Items i
     JOIN Categories c ON i.categoryId = c.categoryId
     JOIN Location l ON i.locationId = l.locationId
     JOIN Campus cp ON l.campusId = cp.campusId
     JOIN Users u ON i.userId = u.userId
     ORDER BY i.TimeCreated DESC
     LIMIT 50"
)->fetchAll();

$recentClaims = $pdo->query(
    "SELECT cl.claimId, cl.ClaimStatus, cl.message, cl.TimeCreated,
            i.itemId, i.title,
            cu.fullName AS claimantName, ou.fullName AS ownerName
     FROM Claims cl
     JOIN Items i ON cl.itemId = i.itemId
     JOIN Users cu ON cl.claimantId = cu.userId
     JOIN Users ou ON i.userId = ou.userId
     ORDER BY cl.TimeCreated DESC
     LIMIT 30"
)->fetchAll();

$messages = $pdo->query('SELECT * FROM Messages ORDER BY TimeCreated DESC LIMIT 20')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Admin dashboard</h1>

        <ul class="admin-stats">
            <li>Users: <strong><?= (int)$stats['users'] ?></strong></li>
            <li>Active listings: <strong><?= (int)$stats['active'] ?></strong></li>
            <li>Reunited: <strong><?= (int)$stats['resolved'] ?></strong></li>
            <li>Open claims: <strong><?= (int)$stats['claims'] ?></strong></li>
        </ul>

        <h2>Latest listings (moderation)</h2>
        <?php if (!$recentItems): ?>
            <p class="empty-state">No listings yet.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Item</th><th>Type</th><th>Status</th><th>Reported by</th><th>Posted</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentItems as $it): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$it['itemId'] ?>"><?= e($it['title']) ?></a><br>
                            <small><?= e($it['categoryName']) ?> &middot; <?= e($it['campusName']) ?> - <?= e($it['locationName']) ?></small></td>
                        <td><?= e($it['type']) ?></td>
                        <td><?= e(item_status_label($it['type'], $it['ItemStatus'])) ?></td>
                        <td><?= e($it['fullName']) ?><br><a href="mailto:<?= e($it['email']) ?>"><?= e($it['email']) ?></a></td>
                        <td><?= e(date('d M Y H:i', strtotime($it['TimeCreated']))) ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL ?>/user/delete-item.php" data-confirm="Delete this listing permanently?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="itemId" value="<?= (int)$it['itemId'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Latest claims</h2>
        <?php if (!$recentClaims): ?>
            <p class="empty-state">No claims yet.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Item</th><th>Claimant</th><th>Reporter</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentClaims as $cl): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$cl['itemId'] ?>"><?= e($cl['title']) ?></a></td>
                        <td><?= e($cl['claimantName']) ?></td>
                        <td><?= e($cl['ownerName']) ?></td>
                        <td><?= e($cl['ClaimStatus']) ?></td>
                        <td><?= e(date('d M Y H:i', strtotime($cl['TimeCreated']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

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