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

$id = base64_decode($_GET['id']);
$id = intval($id);

// FETCH DATA
$res = mysqli_query($conn, "SELECT * FROM discussion WHERE id = $id");
$discussion = mysqli_fetch_assoc($res);

if (!$discussion) {
    echo "No Discussion Found!";
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
            <h4 style="color:white;">Discussion Details</h4>
        </div>

        <div class="card-body">

            <div class="detail-row">
                <span class="detail-label">Staff Name :</span>
                <span class="detail-value"><?= htmlspecialchars($discussion['staff_name']) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Domain Name :</span>
                <span class="detail-value"><?= htmlspecialchars($discussion['domain_name']) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Discussion Date :</span>
                <span class="detail-value"><?= htmlspecialchars($discussion['discussion_date']) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Purpose :</span>
                <span class="detail-value"><?= htmlspecialchars($discussion['purpose']) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Description :</span>
                <span class="detail-value"><?= htmlspecialchars($discussion['description']) ?></span>
            </div>

            <div class="text-center mt-4">
                <a href="discussion.php" class="btn btn-secondary" style="background:#0048A7;">Back to Discussions</a>
            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
