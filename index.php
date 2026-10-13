<?php
// index.php = Home Page

$pageTitle = 'Home';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Database queries
$lostCount = $pdo->query("SELECT COUNT(*) FROM Items WHERE type = 'lost' AND ItemStatus = 'approved'")->fetchColumn();
$foundCount = $pdo->query("SELECT COUNT(*) FROM Items WHERE type = 'found' AND ItemStatus = 'approved'")->fetchColumn();
$resolvedCount = $pdo->query("SELECT COUNT(*) FROM Items WHERE ItemStatus = 'resolved'")->fetchColumn();

$recent = $pdo->query(
    "SELECT i.itemId, i.title, i.type, i.dateLost, i.imagePath,
            c.categoryName, l.locationName, cp.campusName
     FROM Items i
     JOIN Categories c ON i.categoryId = c.categoryId
     JOIN Location l ON i.locationId = l.locationId
     JOIN Campus cp ON l.campusId = cp.campusId
     WHERE i.ItemStatus = 'approved'
     ORDER BY i.TimeCreated DESC
     LIMIT 6"
)->fetchAll();
?>

<section class="hero">
    <div class="container">
        <h1>Lost something on campus? <span>Let's Find It</span></h1>
        <p>NUST Reconnect helps students report lost items, list found items, and get belongings back to their owners safely.</p>

        <form class="search-form" action="<?= BASE_URL ?>/items/list.php" method="get">
            <label for="q" class="visually-hidden">Search items</label>
            <input type="search" id="q" name="q" placeholder="Search e.g. student card">

            <label for="type" class="visually-hidden">Type</label>
            <select name="type" id="type">
                <option value="">Lost &amp; Found</option>
                <option value="lost">Lost</option>
                <option value="found">Found</option>
            </select>
            <button type="submit">Search</button>
        </form>
    </div>
</section>

<div class="container">
    <div class="stats">
        <div class="stat"><strong><?= (int)$lostCount ?></strong><span>Items reported lost</span></div>
        <div class="stat"><strong><?= (int)$foundCount ?></strong><span>Items found</span></div>
        <div class="stat"><strong><?= (int)$resolvedCount ?></strong><span>Reunited with owners</span></div>
    </div>
</div>

<section class="section">
    <div class="container">
        <h2 class="section-title">Recently reported</h2>
        <?php if (count($recent) === 0): ?>
            <p class="empty-state">No items yet. Be the first to
                <a href="<?= BASE_URL ?>/user/report-item.php">report an item</a>.
            </p>
        <?php else: ?>
            <div class="item-grid">
                <?php foreach ($recent as $item): ?>
                    <article class="item-card">
                        <div class="thumb">
                            <?php if ($item['imagePath']): ?>
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($item['imagePath']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" width="200">
                            <?php else: ?>
                                No photo
                            <?php endif; ?>
                        </div>
                        <div class="body">
                            <span class="badge badge-<?= htmlspecialchars($item['type']) ?>">
                                <?= htmlspecialchars($item['type']) ?>
                            </span>

                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="meta">
                                <?= htmlspecialchars($item['categoryName']) ?> &middot;
                                <?= htmlspecialchars($item['campusName']) ?> - <?= htmlspecialchars($item['locationName']) ?>
                                <br>
                                <?= htmlspecialchars(date('d M Y', strtotime($item['dateLost']))) ?>
                            </p>
                            <a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$item['itemId'] ?>">View details</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">How it works</h2>
        <div class="steps">
            <div class="step">
                <div class="num">1</div>
                <h3>Report</h3>
                <p>Post what you lost or found, with photos and details.</p>
            </div>
            <div class="step">
                <div class="num">2</div>
                <h3>Claim</h3>
                <p>Answer a proof question to claim an item.</p>
            </div>
            <div class="step">
                <div class="num">3</div>
                <h3>Reconnect</h3>
                <p>An admin verifies the claim and the item is returned.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
