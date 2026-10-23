<?php
$pageTitle = 'About';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>About <?= e(APP_NAME) ?></h1>
        <p><?= e(APP_NAME) ?> is a campus lost-and-found platform that helps students report lost items,
           list items they have found, and get belongings back to their rightful owners safely.</p>

        <h2>How it works</h2>
        <ol>
            <li><strong>Report</strong> &ndash; Create an account and post an item you lost or found, with a photo and details.
                Your report goes live immediately.</li>
            <li><strong>Claim</strong> &ndash; Think an item is yours (or that you found someone's item)? Submit a claim
                and the reporter's contact details are shown to you right away.</li>
            <li><strong>Reconnect</strong> &ndash; Meet on campus. The person handing the item over checks proof of
                ownership in person, then marks the item as returned.</li>
        </ol>

        <h2>Staying safe</h2>
        <ul>
            <li>Keep one identifying detail out of the description and photo so the real owner can prove it's theirs.</li>
            <li>Always check proof in person: a student card, or a detail only the owner would know.</li>
            <li>Meet in a public place on campus, such as the library or admin block.</li>
            <li>Valuable items such as IDs and bank cards can also be handed to campus security.</li>
        </ul>

        <p><a href="<?= BASE_URL ?>/items/list.php">Browse items</a> or
           <a href="<?= BASE_URL ?>/user/report-item.php">report one</a>.</p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>