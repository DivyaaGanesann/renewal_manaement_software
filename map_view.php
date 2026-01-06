<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';
include 'header.php';
include 'navbar.php';

/* BASE64 URL SAFE */
function base_dcode64($c){
    return base64_decode(strtr($c, '-_,', '+/='));
}

/* Helper function for displaying fields */
function displayField($val){
    return !empty($val) ? nl2br(htmlspecialchars($val)) : '-';
}

if (!isset($_GET['id'])) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Invalid Request!</div></div>";
    include 'footer.php';
    exit;
}

$id = intval(base_dcode64($_GET['id']));

$stmt = $conn->prepare("
    SELECT 
        m.*,
        d.domain_name,
        d.business_name
    FROM map_configuration m
    JOIN domain_list d ON d.id = m.domain_id
    WHERE m.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$map = $res->fetch_assoc();
$stmt->close();

if (!$map) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>MAP Record Not Found!</div></div>";
    include 'footer.php';
    exit;
}
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
</style>

<div class="container py-5">
<div class="card shadow-lg mx-auto" style="max-width: 800px; margin-top: 60px;">

    <div class="card-header text-center" style="background:#0048A7;">
        <h4 style="color:white;">MAP Details</h4>
    </div>

    <div class="card-body">

        <div class="detail-row">
            <span class="detail-label">Domain / Business Name :</span>
            <?= displayField($map['domain_name'].' / '.$map['business_name']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Login Type :</span>
            <?= displayField($map['login_type']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Company Mail ID :</span>
            <?= displayField($map['company_mail']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Customer Mail ID :</span>
            <?= displayField($map['customer_mail']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Customer Password :</span>
            <?= displayField($map['customer_password']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Customer Access :</span>
            <?= displayField($map['customer_access']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Ownership :</span>
            <?= displayField($map['ownership']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Main Keyword :</span>
            <?= displayField($map['map_keyword']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Action Role :</span>
            <?= displayField($map['action_role']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">MAP Description :</span>
            <?= displayField($map['map_description']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">MAP Creation Year :</span>
            <?= displayField($map['map_creation_year']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Status :</span>
            <?= displayField($map['status']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Status Description :</span>
            <?= displayField($map['status_description']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Created By :</span>
            <?= displayField($map['created_by']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Business Profile ID :</span>
            <?= displayField($map['business_profile_id']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">MAP Case ID :</span>
            <?= displayField($map['map_case_id']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">MAP Work Location :</span>
            <?= displayField($map['map_work_location']) ?>
        </div>

        <div class="detail-row">
            <span class="detail-label">Created At :</span>
            <?= displayField($map['created_at']) ?>
        </div>

        <div class="text-center mt-4">
            <a href="map_list.php" class="btn btn-secondary" style="background:#0048A7;">
                Back to MAP List
            </a>
        </div>

    </div>
</div>
</div>

<?php include 'footer.php'; ?>
