<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;
$isLoggedIn = isset($_SESSION['userId']);
$isAdmin = $isLoggedIn && ($_SESSION['role'] ?? '') === 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NUST LostAndFound: report lost items, list found items and get belongings back to their owners.">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= BASE_URL ?>/index.php">NUST <span>LostAndFound</span></a>

            <button class="nav-toggle" id="navToggle" type="button"
            aria-label="Toggle menu" aria-expanded="false" aria-controls="mainNav">&#9776;</button>

            <nav class="main-nav" id="mainNav" aria-label="Main navigation">
                <ul>
                    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/items/list.php">Browse Items</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php">About</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>

                <?php if ($isLoggedIn): ?>
                    <li><a href="<?= BASE_URL ?>/user/report-item.php">Report Item</a></li>
                    <li><a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a></li>

                    <?php if ($isAdmin): ?>
                       <li><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>

                    <li><a href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>

                <?php else: ?>
                    <li><a href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
                    <li><a href="<?= BASE_URL ?>/auth/register.php">Register</a></li>
                <?php endif; ?>

                </ul>
            </nav>
        </div>
    </header>
<main>
<div class="container"><?php flash_show(); ?></div>