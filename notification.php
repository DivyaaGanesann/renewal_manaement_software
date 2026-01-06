<?php
session_start();
$_SESSION['notification_read'] = true;
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check admin session
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';

// Fetch records renewing this month
$this_month_start = date('Y-m-01'); // first day of this month
$this_month_end   = date('Y-m-t');  // last day of this month

$query = "
    SELECT id, domain_name, customer_name, phone, status, launch_date, renewal_date
    FROM domain_list
    WHERE renewal_date BETWEEN '$this_month_start' AND '$this_month_end'
";

$result = mysqli_query($conn, $query);

include 'header.php';
include 'navbar.php';
?>

<div id="discussionpage" class="content-area">
<div class="container mt-5">
    <h4 class="mb-4 text-center text-primary">Customers with Renewal Expiry This Month</h4>
    <table class="table table-bordered table-striped" id="notificationtable">
        <thead>
            <tr>
                <th>SNO</th>
                <th>Customer Name</th>
                <th>Domain Name</th>
                <th>Launch Date</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Renewal Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($result) > 0): 
                $serial = 1;
                while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $serial ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars($row['domain_name']) ?></td>
                        <td><?= $row['launch_date'] ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td>
                            <?= ($row['status'] == 1) 
                                ? "<span class='badge bg-success'>Active</span>" 
                                : "<span class='badge bg-danger'>Inactive</span>"; ?>
                        </td>
                        <td><?= $row['renewal_date'] ?></td>
                    </tr>
                <?php 
                $serial++;
                endwhile; 
            endif; ?>
        </tbody>
    </table>
</div>
</div>

<script>
$(document).ready(function() {
    $('#notificationtable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthChange: true,
        pageLength: 10,
        order: [],
        columnDefs: [
            { targets: 0, orderable: false }, // SNO non-sortable
            { targets: 5, orderable: false }  // Status non-sortable
        ],
        language: {
            emptyTable: "No records found for this month's renewal."
        }
    });
});
</script>

<?php include 'footer.php'; ?>
