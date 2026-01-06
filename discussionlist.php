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
?>

<div id="discussionlistpage" class="content-area">
<div class="container mt-4">

<h3 class="text-center mb-3">Discussion List</h3>



<div class="text-end mb-3">
    <a href="discussion.php" class="btn btn-primary btn-sm">+ Add New Discussion</a>
</div>

<!-- ================= SELECT2 FILTER UI ================= -->
<div class="dr-filter-box mb-3"
     style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">

    <div>
        <label>Staff</label>
        <select id="staffFilter" class="form-select select2" style="width:180px;">
            <option value="">All Staff</option>
            <?php
            $st = mysqli_query($conn, "SELECT DISTINCT staff_name FROM discussion");
            while ($s = mysqli_fetch_assoc($st)) {
                echo "<option>".htmlspecialchars($s['staff_name'])."</option>";
            }
            ?>
        </select>
    </div>

    <div>
        <label>Domain</label>
        <select id="domainFilter" class="form-select select2" style="width:180px;">
            <option value="">All Domains</option>
            <?php
            $dm = mysqli_query($conn, "SELECT DISTINCT domain_name FROM discussion");
            while ($d = mysqli_fetch_assoc($dm)) {
                echo "<option>".htmlspecialchars($d['domain_name'])."</option>";
            }
            ?>
        </select>
    </div>

    <div>
        <label>Customer</label>
        <select id="customerFilter" class="form-select select2" style="width:180px;">
            <option value="">All Customers</option>
            <?php
            $custRes = mysqli_query($conn, "
                SELECT DISTINCT dl.customer_name 
                FROM discussion d
                LEFT JOIN domain_list dl ON d.domain_name = dl.domain_name
            ");
            while ($c = mysqli_fetch_assoc($custRes)) {
                echo "<option>".htmlspecialchars($c['customer_name'])."</option>";
            }
            ?>
        </select>
    </div>

    <div>
        <label>Date</label>
        <input type="date" id="dateFilter" class="form-control" style="width:160px;">
    </div>

    <div>
        <label>Purpose</label>
        <input type="text" id="purposeFilter" class="form-control" placeholder="Search purpose" style="width:200px;">
    </div>

    <div>
        <button id="resetFilter" class="btn btn-secondary mt-4">Filter</button>
    </div>
</div>
<!-- ================= END FILTER UI ================= -->

<div class="table-responsive">
<table id="discussionTable" class="table table-bordered table-striped table-sm">
<thead>
<tr>
    <th>SNO</th>
    <th>Staff</th>
    <th>Domain</th>
    <th>Customer</th>
    <th>Date</th>
    <th>Purpose</th>
    <th class="text-center">Actions</th>
</tr>
</thead>
<tbody>

<?php
$currentYear = date('Y');
$currentMonth = date('m');
$lastMonth = date('m', strtotime('-1 month'));
$lastMonthYear = date('Y', strtotime('-1 month'));

$res = mysqli_query($conn, "
    SELECT d.*, dl.customer_name 
    FROM discussion d
    LEFT JOIN domain_list dl ON d.domain_name = dl.domain_name
    WHERE (YEAR(d.discussion_date) = $currentYear AND MONTH(d.discussion_date) = $currentMonth)
       OR (YEAR(d.discussion_date) = $lastMonthYear AND MONTH(d.discussion_date) = $lastMonth)
    ORDER BY d.id DESC
");

$serial = 1;
while ($row = mysqli_fetch_assoc($res)) {
    echo "<tr>
            <td>{$serial}</td>
            <td>".htmlspecialchars($row['staff_name'])."</td>
            <td>".htmlspecialchars($row['domain_name'])."</td>
            <td>".htmlspecialchars($row['customer_name'])."</td>
            <td>".htmlspecialchars($row['discussion_date'])."</td>
            <td>".htmlspecialchars($row['purpose'])."</td>
            <td class='text-center'>
                <a href='view_discussion.php?id=".base64_encode($row['id'])."'>
                    <i class='fa fa-eye text-success'></i>
                </a>
            </td>
          </tr>";
    $serial++;
}
?>

</tbody>
</table>
</div>

</div>
</div>

<script>
$(document).ready(function () {

    // SELECT2
    $('.select2').select2({
        placeholder: "Select option",
        allowClear: true,
        width: 'resolve'
    });

    // DATATABLE
    var table = $('#discussionTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthChange: true,
        pageLength: 10,
        order: [],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fa fa-download"></i> Download',
                className: 'btn btn-primary btn-sm',
                buttons: [
                    { extend: 'copy', text: 'Copy' },
                    { extend: 'csv', text: 'CSV' },
                    { extend: 'excel', text: 'Excel' },
                    { extend: 'pdf', text: 'PDF' },
                    { extend: 'print', text: 'Print' }
                ]
            }
        ],
        columnDefs: [
            { orderable: false, targets: [0, 6] }
        ]
    });

    // FILTERS
    $('#staffFilter').on('change', function () {
        table.column(1).search(this.value).draw();
    });
    $('#domainFilter').on('change', function () {
        table.column(2).search(this.value).draw();
    });
    $('#customerFilter').on('change', function () {
        table.column(3).search(this.value).draw();
    });
    $('#dateFilter').on('change', function () {
        table.column(4).search(this.value).draw();
    });
    $('#purposeFilter').on('keyup', function () {
        table.column(5).search(this.value).draw();
    });

    // RESET FILTER
    $('#resetFilter').on('click', function () {
        $('#staffFilter').val('').trigger('change');
        $('#domainFilter').val('').trigger('change');
        $('#customerFilter').val('').trigger('change');
        $('#dateFilter').val('');
        $('#purposeFilter').val('');
        table.search('').columns().search('').draw();
    });

});
</script>

<?php include 'footer.php'; ?>
