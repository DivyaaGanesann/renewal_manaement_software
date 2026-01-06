<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// NOTE: Ensure 'config.php', 'get_counts.php', 'header.php', and 'navbar.php' exist
include 'get_counts.php';
include 'config.php';
include 'header.php';
include 'navbar.php';

// Utility function from the main WAB script to decode the domain_name identifier
function base_dcode64($code){
    return base64_decode(strtr($code, '-_,', '+/='));
}

if (!isset($_GET['domain'])) {
    echo "Invalid Request!";
    exit;
}

// The 'domain' parameter in WAB is typically the domain name itself, 
// encoded using base_ecode64 (as used in the list/delete links).
$domain_identifier = base_dcode64($_GET['domain']);

$sql = "
    SELECT *
    FROM wab_config
    WHERE domain_name = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $domain_identifier);
$stmt->execute();
$res = $stmt->get_result();
$wab = $res->fetch_assoc();
$stmt->close();

if (!$wab) {
    echo "No WAB Configuration Record Found!";
    exit;
}

/* WAB Status & Verification Checklist TEXT */
// Define critical checks for WAB setup
$checklist = [
    'whatsapp_number' => 'WhatsApp Number Configured',
    'facebook_username' => 'Facebook Credentials Entered',
    'facebook_business_id' => 'Facebook Business ID Entered',
    'fb_page_url' => 'Facebook Page URL Entered'
];

// Determine CSS for Meta Status for quick visual check
$meta_status_color = ($wab['meta_status'] == 'Verified') ? 'green' : '#ffc107'; // yellow for Not Verified
$connection_status_color = ($wab['status'] == 'Active') ? 'green' : 'red';
?>

<style>
.detail-label {
    font-weight: bold;
    color: #0048A7; /* Dark Blue for labels */
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
.status-badge {
    padding: 4px 8px;
    border-radius: 5px;
    font-weight: bold;
    color: white;
    display: inline-block;
}
</style>

<div class="container py-5">
<div class="card shadow-lg mx-auto" style="max-width: 800px; margin-top: 60px;">

    <div class="card-header text-center" style="background:#0048A7;">
        <h4 style="color:white;">WhatsApp Business (WAB) Configuration Details</h4>
    </div>

    <div class="card-body">

        <div class="detail-row">
            <span class="detail-label">Domain Name :</span>
            <span class="detail-value"><?= htmlspecialchars($wab['domain_name']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Customer Name :</span>
            <span class="detail-value"><?= htmlspecialchars($wab['customer_name']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">WhatsApp Number :</span>
            <span class="detail-value"><?= htmlspecialchars($wab['whatsapp_number']) ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Facebook Business ID :</span>
            <span class="detail-value"><?= htmlspecialchars($wab['facebook_business_id']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Facebook Username :</span>
            <span class="detail-value"><?= htmlspecialchars($wab['facebook_username']) ?></span>
        </div>        
		<div class="detail-row">
            <span class="detail-label">Facebook Password :</span>
            <span class="detail-value"><?= htmlspecialchars($wab['password']) ?></span>
        </div>


        <div class="detail-row">
            <span class="detail-label">FB Page URL :</span>
            <span class="detail-value"><a href="<?= htmlspecialchars($wab['fb_page_url']) ?>" target="_blank"><?= htmlspecialchars($wab['fb_page_url']) ?></a></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Status :</span>
            <span class="status-badge" style="background:<?= $connection_status_color ?>;">
                <?= htmlspecialchars($wab['status']) ?>
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Meta Status (Verification) :</span>
            <span class="status-badge" style="background:<?= $meta_status_color ?>;">
                <?= htmlspecialchars($wab['meta_status']) ?>
            </span>
        </div>

        <hr>



        <div class="text-center mt-4">
            <a href="wab_list.php" class="btn btn-secondary" style="background:#0048A7;">
                <i class="fas fa-list me-2"></i>Back to WAB Configuration List
            </a>

        </div>

    </div>
</div>
</div>

<?php include 'footer.php'; ?>