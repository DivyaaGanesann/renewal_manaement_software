<?php
session_start();
include 'config.php';
include 'get_counts.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

/* ---------------- DOWNLOAD DEMO CSV ---------------- */
if(isset($_GET['download_demo'])){
    $header = [
        'domain_name','launch_date','customer_name','phone','whatsapp','business_name',
        'address','email','renewal_cycle','renewal_date','last_renewal_date','purchase_name',
        'product','category_name','status','category_id','country','state','city','description','created_at'
    ];
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=bulk_upload_demo.csv");
    $output = fopen('php://output','w');
    fputcsv($output, $header);

    fputcsv($output, [
        'example.com','2025-01-01','John Doe','9999999999','9999999999','Example Business',
        '123 Example St','john@example.com','1','2026-01-01','2025-01-01','John Doe',
        'Domain,SEO,Map','Web Hosting','1','1','India','Tamil Nadu','Chennai',
        'Special client notes or instructions.','2025-12-31 12:00:00'
    ]);

    fclose($output);
    exit;
}

/* ---------------- BULK CSV UPLOAD ---------------- */
$uploadedData = $rejectedData = [];
$msg = $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $msg = "Only CSV files are allowed."; 
        $msg_type="danger";
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            $i = 0; $inserted = 0; $skipped = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if ($i++ == 0) continue; // skip header

                // Map CSV columns
                $domain_name   = mysqli_real_escape_string($conn, $data[0] ?? '');
                $customer_name = mysqli_real_escape_string($conn, $data[2] ?? '');
                $phone         = mysqli_real_escape_string($conn, $data[3] ?? '');
                $whatsapp      = mysqli_real_escape_string($conn, $data[4] ?? '');
                $email         = mysqli_real_escape_string($conn, $data[7] ?? '');

                // Check duplicates
                $dupCheck = mysqli_query($conn, "
                    SELECT id FROM domain_list 
                    WHERE 
                        (domain_name = '$domain_name' AND domain_name != '') OR
                        (customer_name = '$customer_name' AND customer_name != '') OR
                        (phone = '$phone' AND phone != '') OR
                        (whatsapp = '$whatsapp' AND whatsapp != '') OR
                        (email = '$email' AND email != '')
                ");
                if(mysqli_num_rows($dupCheck) > 0){
                    $rejectedData[] = $data;
                    $skipped++;
                    continue;
                }

                // Prepare other columns
                $launch_date        = !empty($data[1]) ? date('Y-m-d', strtotime($data[1])) : null;
                $business_name      = mysqli_real_escape_string($conn, $data[5] ?? '');
                $address            = mysqli_real_escape_string($conn, $data[6] ?? '');
                $renewal_cycle      = intval($data[8] ?? 1);
                $renewal_date       = !empty($data[9]) ? date('Y-m-d', strtotime($data[9])) : null;
                $last_renewal_date  = !empty($data[10]) ? date('Y-m-d', strtotime($data[10])) : null;
                $purchase_name      = mysqli_real_escape_string($conn, $data[11] ?? '');
                $product            = mysqli_real_escape_string($conn, $data[12] ?? '');
                $category_name      = mysqli_real_escape_string($conn, $data[13] ?? '');
                $status             = isset($data[14]) ? intval($data[14]) : 1;
                $category_id        = intval($data[15] ?? 0);
                $country            = mysqli_real_escape_string($conn, $data[16] ?? '');
                $state              = mysqli_real_escape_string($conn, $data[17] ?? '');
                $city               = mysqli_real_escape_string($conn, $data[18] ?? '');
                $description        = mysqli_real_escape_string($conn, $data[19] ?? '');
                $created_at         = !empty($data[20]) ? date('Y-m-d H:i:s', strtotime($data[20])) : date('Y-m-d H:i:s');

                // Insert
                $stmt = $conn->prepare("INSERT INTO domain_list (
                    domain_name, launch_date, customer_name, phone, whatsapp,
                    business_name, address, email, renewal_cycle, renewal_date,
                    last_renewal_date, purchase_name, product, category_name, status,
                    category_id, country, state, city, description, created_at
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

                $stmt->bind_param(
                    "ssssssssissssssisssss",
                    $domain_name, $launch_date, $customer_name, $phone, $whatsapp,
                    $business_name, $address, $email, $renewal_cycle, $renewal_date,
                    $last_renewal_date, $purchase_name, $product, $category_name, $status,
                    $category_id, $country, $state, $city, $description, $created_at
                );

                if($stmt->execute()) {
                    $inserted++;
                    $uploadedData[] = $data;
                }
                $stmt->close();
            }
            fclose($handle);
            $msg = "CSV uploaded: $inserted records added, $skipped duplicates skipped."; 
            $msg_type="success";
        } else { 
            $msg="Failed to open CSV file."; 
            $msg_type="danger"; 
        }
    }
}

include 'header.php';
include 'navbar.php';
?>

<div class="content-area">

    <!-- Back Button -->

<div class="container py-4">
    <a href="domain.php" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left">Back</i> 
    </a>
    <h4 class="text-primary">Bulk Domain Upload</h4>



    <?php if($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <a href="?download_demo=1" class="btn btn-outline-primary w-100 mb-2">
                <i class="bi bi-download"></i> Download Demo CSV
            </a>
        </div>
        <div class="col-md-6">
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" class="form-control mb-2" accept=".csv" required>
                <button class="btn btn-success w-100">
                    <i class="bi bi-upload"></i> Upload CSV
                </button>
            </form>
        </div>
    </div>

    <?php if(!empty($uploadedData)): ?>
    <h5>Uploaded Records</h5>
    <table class="table table-bordered table-striped" id="uploadedTable">
        <thead>
            <tr>
                <?php foreach($uploadedData[0] as $key => $val) echo "<th>Column ".($key+1)."</th>"; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($uploadedData as $row): ?>
                <tr>
                    <?php foreach($row as $cell): ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if(!empty($rejectedData)): ?>
    <h5 class="text-danger mt-4">Rejected/Duplicate Records</h5>
    <table class="table table-bordered table-striped" id="rejectedTable">
        <thead>
            <tr>
                <?php foreach($rejectedData[0] as $key => $val) echo "<th>Column ".($key+1)."</th>"; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rejectedData as $row): ?>
                <tr>
                    <?php foreach($row as $cell): ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>
</div>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function(){
    $('#uploadedTable').DataTable();
    $('#rejectedTable').DataTable();
});
</script>

<?php include 'footer.php'; ?>
