<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'get_counts.php';
include 'config.php';
include 'header.php';
include 'navbar.php';

if (!isset($_GET['id'])) {
    echo "Invalid Request!";
    exit;
}

$id = intval(base64_decode($_GET['id']));

$sql = "
    SELECT 
        s.*,
        d.domain_name,
        d.business_name
    FROM seo_details s
    JOIN domain_list d ON d.id = s.domain_id
    WHERE s.id = $id
";
$res = mysqli_query($conn, $sql);
$seo = mysqli_fetch_assoc($res);

if (!$seo) {
    echo "No SEO Record Found!";
    exit;
}

/* SEO CHECKLIST TEXT */
$checklist = [
    'gse' => 'Google Search Console',
    'ga' => 'Google Analytics',
    'gtm' => 'Google Tag Manager',
    'sitemap' => 'Sitemap XML',
    'robots' => 'Robots.txt',
    'schema_markup' => 'Schema Markup'
];
?>

<style>
.detail-label {
    font-weight: bold;
    color: #0048A7;
}
.detail-row {
    margin-bottom: 12px;
    font-size: 16px;
}
.check-yes {
    color: green;
    font-weight: bold;
}
.check-no {
    color: red;
    font-weight: bold;
}
</style>

<div class="container py-5">
<div class="card shadow-lg mx-auto" style="max-width: 800px; margin-top: 60px;">

    <div class="card-header text-center" style="background:#0048A7;">
        <h4 style="color:white;">SEO Details</h4>
    </div>

    <div class="card-body">

        <div class="detail-row">
            <span class="detail-label">Domain Name :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['domain_name']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Business Name :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['business_name']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">SEO Mail ID :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['mail_id']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Main Keyword :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['main_keyword']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Previous Page Text :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['previous_page_visible']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Current Page Text :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['current_page_text']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Work Location Path :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['work_location']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">SEO Date :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['seo_data_date']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Status :</span>
            <span class="detail-value"><?= htmlspecialchars($seo['status']) ?></span>
        </div>

        <hr>

        <h5 class="mb-3" style="color:#0048A7;">SEO Verification Checklist</h5>

        <?php foreach ($checklist as $key => $label): ?>
            <div class="detail-row">
                <span class="detail-label"><?= $label ?> :</span>
                <?php if ($seo[$key] == 1): ?>
                    <span class="check-yes">✔ Verified</span>
                <?php else: ?>
                    <span class="check-no">✖ Not Verified</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="text-center mt-4">
            <a href="seo_list.php" class="btn btn-secondary" style="background:#0048A7;">
                Back to SEO List
            </a>
        </div>

    </div>
</div>
</div>

<?php include 'footer.php'; ?>
