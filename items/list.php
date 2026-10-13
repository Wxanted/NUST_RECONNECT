<?php
$pageTitle = 'Browse Items';
require_once __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$categoryId = (int)($_GET['category'] ?? 0);
$campusId = (int)($_GET['campus'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

if (!in_array($type, ['lost', 'found'], true)) {
    $type = '';
}

$categories = $pdo->query('SELECT categoryId, categoryName FROM Categories ORDER BY categoryName')->fetchAll();
$campuses = $pdo->query('SELECT campusId, campusName FROM Campus ORDER BY campusName')->fetchAll();

// Build the filter
$where = ["i.ItemStatus IN ('approved','resolved')"];
$params = [];

if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(i.title LIKE :q1 OR i.description LIKE :q2)';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
}
if ($type !== '') {
    $where[] = 'i.type = :type';
    $params[':type'] = $type;
}
if ($categoryId > 0) {
    $where[] = 'i.categoryId = :cat';
    $params[':cat'] = $categoryId;
}
if ($campusId > 0) {
    $where[] = 'l.campusId = :campus';
    $params[':campus'] = $campusId;
}
$whereSql = implode(' AND ', $where);

$from = "FROM Items i
         JOIN Categories c ON i.categoryId = c.categoryId
         JOIN Location l ON i.locationId = l.locationId
         JOIN Campus cp ON l.campusId = cp.campusId";

$countStmt = $pdo->prepare("SELECT COUNT(*) $from WHERE $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT i.itemId, i.title, i.type, i.dateLost, i.imagePath, i.ItemStatus,
            c.categoryName, l.locationName, cp.campusName
     $from
     WHERE $whereSql
     ORDER BY i.TimeCreated DESC
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

function page_link(int $p): string
{
    $query = $_GET;
    $query['page'] = $p;
    return '?' . http_build_query($query);
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>Browse items</h1>

        <form method="get" action="" class="filter-form">
            <label for="q">Search
                <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="e.g. student card">
            </label>

            <label for="type">Type
                <select id="type" name="type">
                    <option value="">Lost &amp; Found</option>
                    <option value="lost" <?= $type === 'lost' ? 'selected' : '' ?>>Lost</option>
                    <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>Found</option>
                </select>
            </label>

            <label for="category">Category
                <select id="category" name="category">
                    <option value="0">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['categoryId'] ?>" <?= $categoryId === (int)$c['categoryId'] ? 'selected' : '' ?>>
                            <?= e($c['categoryName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label for="campus">Campus
                <select id="campus" name="campus">
                    <option value="0">All campuses</option>
                    <?php foreach ($campuses as $c): ?>
                        <option value="<?= (int)$c['campusId'] ?>" <?= $campusId === (int)$c['campusId'] ? 'selected' : '' ?>>
                            <?= e($c['campusName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit">Filter</button>
            <a href="<?= BASE_URL ?>/items/list.php">Clear</a>
        </form>

        <p><?= $total ?> item<?= $total === 1 ? '' : 's' ?> found.</p>

        <?php if (!$items): ?>
            <p class="empty-state">No items match your search.</p>
        <?php else: ?>
            <div class="item-grid">
                <?php foreach ($items as $item): ?>
                    <article class="item-card">
                        <div class="thumb">
                            <?php if ($item['imagePath']): ?>
                                <img src="<?= BASE_URL . '/' . e($item['imagePath']) ?>" alt="<?= e($item['title']) ?>" width="200">
                            <?php else: ?>
                                No photo
                            <?php endif; ?>
                        </div>
                        <div class="body">
                            <span class="badge badge-<?= e($item['type']) ?>"><?= e($item['type']) ?></span>
                            <?php if ($item['ItemStatus'] === 'resolved'): ?>
                                <span class="badge badge-resolved">resolved</span>
                            <?php endif; ?>
                            <h3><?= e($item['title']) ?></h3>
                            <p class="meta">
                                <?= e($item['categoryName']) ?> &middot;
                                <?= e($item['campusName']) ?> - <?= e($item['locationName']) ?>
                                <br>
                                <?= e(date('d M Y', strtotime($item['dateLost']))) ?>
                            </p>
                            <a href="<?= BASE_URL ?>/items/details.php?id=<?= (int)$item['itemId'] ?>">View details</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="pagination" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(page_link($page - 1)) ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    Page <?= $page ?> of <?= $pages ?>
                    <?php if ($page < $pages): ?>
                        <a href="<?= e(page_link($page + 1)) ?>">Next &raquo;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
