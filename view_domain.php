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

// FETCH DOMAIN DETAILS
$res = mysqli_query($conn, "SELECT * FROM domain_list WHERE id = $id");
$domain = mysqli_fetch_assoc($res);

if (!$domain) {
    echo "No Domain Found!";
    exit;
}

// --- FETCH ALL DISCUSSIONS FOR THE DOMAIN ---
$all_discussions = [];

$rawDomainName = $domain['domain_name'];
$normalized = preg_replace('#^https?://#i', '', trim($rawDomainName));
$normalized = rtrim($normalized, '/');

$dn_exact = mysqli_real_escape_string($conn, $rawDomainName);
$dn_norm  = mysqli_real_escape_string($conn, $normalized);

$sql = "
    SELECT * FROM discussion
    WHERE domain_name = '{$dn_exact}'
       OR REPLACE(REPLACE(domain_name, 'http://', ''), 'https://', '') = '{$dn_norm}'
    ORDER BY discussion_date DESC
";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $all_discussions[] = $row;
    }
}
?>

<div id="domainlistpage" class="content-area">
<div class="container py-5">

  <div class="row g-4">

    <!-- DOMAIN DETAILS -->
    <div class="col-lg-5">
      <div class="card shadow border-0">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Domain Details</h5>
        </div>
        <div class="card-body">
          <?php foreach ($domain as $key => $value): ?>
            <p>
              <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?>:</strong>
              <?php 
                if($key === 'status') {
                    echo ($value == 1) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                } else {
                    echo htmlspecialchars($value);
                }
              ?>
            </p>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- DISCUSSION DETAILS -->
    <div class="col-lg-7">
      <div class="card shadow border-0">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Discussion Details</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($all_discussions)) { ?>
            <?php foreach ($all_discussions as $disc) { ?>
              <div class="discussion-card p-3 mb-3 bg-light rounded shadow-sm">
                <p><strong>Staff Name:</strong> <?= htmlspecialchars($disc['staff_name']) ?></p>
                <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($disc['description'])) ?></p>
                <p><strong>Discussion Date:</strong> <?= htmlspecialchars($disc['discussion_date']) ?></p>
                <p><strong>Purpose:</strong> <?= htmlspecialchars($disc['purpose']) ?></p>
              </div>
            <?php } ?>
          <?php } else { ?>
            <p class="text-muted">No discussions found for this domain.</p>
          <?php } ?>
        </div>
      </div>
    </div>

  </div>

  <div class="text-center mt-4">
    <a href="domain.php" class="btn btn-primary">Back to Domain List</a>
  </div>

</div>
</div>

<?php include 'footer.php'; ?>
