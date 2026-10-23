<?php
require_once __DIR__ . '/../includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/user/dashboard.php');
}
csrf_check();

$claimId = (int)($_POST['claimId'] ?? 0);
$action = input_str($_POST, 'action');
$userId = (int)$_SESSION['userId'];

if (!in_array($action, ['complete', 'reject', 'withdraw'], true)) {
    flash_set('error', 'Unknown action.');
    redirect('/user/dashboard.php');
}

$result = ['error', 'Something went wrong. Please try again.'];

try {
    $pdo->beginTransaction();

    $s = $pdo->prepare(
        "SELECT cl.claimId, cl.itemId, cl.claimantId, cl.ClaimStatus, i.userId AS ownerId
         FROM Claims cl JOIN Items i ON i.itemId = cl.itemId
         WHERE cl.claimId = ? FOR UPDATE"
    );
    $s->execute([$claimId]);
    $c = $s->fetch();

    $isOwner = $c && ((int)$c['ownerId'] === $userId || is_admin());

    if (!$c || $c['ClaimStatus'] !== 'pending') {
        $result = ['error', 'That claim is no longer open.'];
    } elseif ($action === 'withdraw') {
        if ((int)$c['claimantId'] !== $userId) {
            $result = ['error', 'You cannot withdraw someone else\'s claim.'];
        } else {
            $pdo->prepare("UPDATE Claims SET ClaimStatus = 'withdrawn' WHERE claimId = ?")->execute([$claimId]);
            $result = ['success', 'Claim withdrawn.'];
        }
    } elseif (!$isOwner) {
        $result = ['error', 'Only the person who reported the item can do that.'];
    } elseif ($action === 'complete') {
        $pdo->prepare("UPDATE Claims SET ClaimStatus = 'completed' WHERE claimId = ?")->execute([$claimId]);
        $pdo->prepare("UPDATE Claims SET ClaimStatus = 'rejected' WHERE itemId = ? AND claimId <> ? AND ClaimStatus = 'pending'")
            ->execute([$c['itemId'], $claimId]);
        $pdo->prepare("UPDATE Items SET ItemStatus = 'resolved' WHERE itemId = ?")->execute([$c['itemId']]);
        $result = ['success', 'Marked as returned. Great news, the item is reunited!'];
    } else { // reject
        $pdo->prepare("UPDATE Claims SET ClaimStatus = 'rejected' WHERE claimId = ?")->execute([$claimId]);
        $result = ['success', 'Claim declined.'];
    }

    $pdo->commit();
} catch (Throwable $t) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $result = ['error', 'Something went wrong. Please try again.'];
}

flash_set($result[0], $result[1]);
redirect('/user/dashboard.php');