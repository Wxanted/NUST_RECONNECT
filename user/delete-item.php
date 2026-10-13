<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/user/dashboard.php');
}
csrf_check();

$itemId = (int)($_POST['itemId'] ?? 0);
$stmt = $pdo->prepare('SELECT userId, imagePath, ItemStatus FROM Items WHERE itemId = ?');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item || ((int)$item['userId'] !== (int)$_SESSION['userId'] && !is_admin())) {
    flash_set('error', 'You cannot delete that item.');
    redirect('/user/dashboard.php');
}
if ($item['ItemStatus'] === 'resolved') {
    flash_set('error', 'Resolved items are kept as a record and cannot be deleted.');
    redirect('/user/dashboard.php');
}

$pdo->prepare('DELETE FROM Items WHERE itemId = ?')->execute([$itemId]);
delete_image($item['imagePath']);

flash_set('success', 'Report deleted.');
redirect(is_admin() && (int)$item['userId'] !== (int)$_SESSION['userId'] ? '/admin/dashboard.php' : '/user/dashboard.php');
