<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/items/list.php');
}
csrf_check();

$itemId = (int)($_POST['itemId'] ?? 0);
$answer = trim($_POST['answer'] ?? '');
$message = trim($_POST['message'] ?? '');
$userId = (int)$_SESSION['userId'];
$back = '/items/details.php?id=' . $itemId;

$stmt = $pdo->prepare('SELECT itemId, userId, type, ItemStatus, proofQuestion FROM Items WHERE itemId = ?');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item || $item['ItemStatus'] !== 'approved') {
    flash_set('error', 'This item cannot be claimed.');
    redirect('/items/list.php');
}
if ((int)$item['userId'] === $userId) {
    flash_set('error', 'You cannot claim your own item.');
    redirect($back);
}
if (mb_strlen($answer) > 255 || mb_strlen($message) > 500) {
    flash_set('error', 'Your answer or message is too long.');
    redirect($back);
}
if ($item['proofQuestion'] && $answer === '') {
    flash_set('error', 'Please answer the proof question.');
    redirect($back);
}
if (!$item['proofQuestion'] && $message === '') {
    flash_set('error', 'Please add a message.');
    redirect($back);
}

$dup = $pdo->prepare("SELECT 1 FROM Claims WHERE itemId = ? AND claimantId = ? AND ClaimStatus = 'pending'");
$dup->execute([$itemId, $userId]);
if ($dup->fetchColumn()) {
    flash_set('error', 'You already have a pending claim on this item.');
    redirect($back);
}

$ins = $pdo->prepare('INSERT INTO Claims (itemId, claimantId, answer, message) VALUES (?, ?, ?, ?)');
$ins->execute([$itemId, $userId, $answer !== '' ? $answer : null, $message !== '' ? $message : null]);

flash_set('success', 'Claim submitted. An admin will review it shortly.');
redirect('/user/dashboard.php');
