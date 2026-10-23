<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/items/list.php');
}
csrf_check();

$itemId = (int)($_POST['itemId'] ?? 0);
$message = input_str($_POST, 'message');
$userId = (int)$_SESSION['userId'];
$back = '/items/details.php?id=' . $itemId;

$stmt = $pdo->prepare('SELECT itemId, userId, type, ItemStatus FROM Items WHERE itemId = ?');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item || $item['ItemStatus'] !== 'active') {
    flash_set('error', 'This item can no longer be claimed.');
    redirect('/items/list.php');
}
if ((int)$item['userId'] === $userId) {
    flash_set('error', 'You cannot claim your own item.');
    redirect($back);
}
if (mb_strlen($message) < 10) {
    flash_set('error', 'Please add a few details (at least 10 characters).');
    redirect($back);
}
if (mb_strlen($message) > 500) {
    flash_set('error', 'Your message is too long (max 500 characters).');
    redirect($back);
}

$dup = $pdo->prepare("SELECT 1 FROM Claims WHERE itemId = ? AND claimantId = ? AND ClaimStatus = 'pending'");
$dup->execute([$itemId, $userId]);
if ($dup->fetchColumn()) {
    flash_set('error', 'You already have an open claim on this item.');
    redirect($back);
}

// Simple spam guard
$open = $pdo->prepare("SELECT COUNT(*) FROM Claims WHERE claimantId = ? AND ClaimStatus = 'pending'");
$open->execute([$userId]);
if ((int)$open->fetchColumn() >= 10) {
    flash_set('error', 'You have too many open claims. Withdraw or complete some first.');
    redirect('/user/dashboard.php');
}

$pdo->prepare('INSERT INTO Claims (itemId, claimantId, message) VALUES (?, ?, ?)')
    ->execute([$itemId, $userId, $message]);

flash_set('success', 'Claim recorded. The contact details are below. Remember to bring proof when you meet.');
redirect($back);