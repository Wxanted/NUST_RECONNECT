<?php
$pageTitle = 'About';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1>About NUST Reconnect</h1>
        <p>NUST Reconnect is a campus lost-and-found platform that helps students report lost items,
           list items they have found, and get belongings back to their rightful owners safely.</p>

        <h2>How it works</h2>
        <ol>
            <li><strong>Report</strong> &ndash; Create an account and post an item you lost or found, with a photo and details.
                An admin reviews every post before it goes public.</li>
            <li><strong>Claim</strong> &ndash; Think an item is yours? Answer the finder's proof question
                (or tell the owner where you found their item).</li>
            <li><strong>Reconnect</strong> &ndash; An admin checks your answer. When approved, you get the
                reporter's contact details to arrange the handover.</li>
        </ol>

        <h2>Staying safe</h2>
        <ul>
            <li>Never put the answer to your proof question in the description or photo.</li>
            <li>Meet in a public place on campus, such as the library or admin block.</li>
            <li>Valuable items such as IDs and bank cards can also be handed to campus security.</li>
        </ul>

        <p><a href="<?= BASE_URL ?>/items/list.php">Browse items</a> or
           <a href="<?= BASE_URL ?>/user/report-item.php">report one</a>.</p>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
