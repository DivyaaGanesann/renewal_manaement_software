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
if (file_exists('get_counts.php')) include 'get_counts.php';

// DELETE DOMAIN
if (isset($_GET['delete'])) {
    $id = base64_decode($_GET['delete']);
    $safe_id = mysqli_real_escape_string($conn, $id);
    mysqli_query($conn, "DELETE FROM domain_list WHERE id='$safe_id'");
    $_SESSION['msg'] = "Domain Deleted Successfully!";
    $_SESSION['msg_type'] = "danger";
    header("Location: domainlist.php");
    exit;
}

include 'header.php';
include 'navbar.php';
?>

<div id="domainpage" class="content-area">
<div class="container mt-4">



<h4 class="mb-3 text-center">Domain expiry in 2 Months</h4>
<a href="domain.php" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add New Domain</a>

<div class="dr-filter-box mb-3" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">

    <!-- Status Filter -->
    <div>
        <label>Status</label>
        <select id="statusFilter" class="form-select" style="width:150px;">
            <option value="">All</option>
            <option value="Active">Active</option>
            <option value="Deactive">Deactive</option>
        </select>
    </div>

    <!-- Domain Filter -->
    <div>
        <label>Domain</label>
        <select id="domainFilter" class="form-select select2" style="width:200px;">
            <option value="">All Domains</option>
            <?php
            $dm = mysqli_query($conn, "SELECT DISTINCT domain_name FROM domain_list ORDER BY domain_name ASC");
            while ($d = mysqli_fetch_assoc($dm)) {
                echo "<option>" . htmlspecialchars($d['domain_name']) . "</option>";
            }
            ?>
        </select>
    </div>

    <!-- Customer Filter -->
    <div>
        <label>Customer</label>
        <select id="customerFilter" class="form-select select2" style="width:200px;">
            <option value="">All Customers</option>
            <?php
            $cust = mysqli_query($conn, "SELECT DISTINCT customer_name FROM domain_list ORDER BY customer_name ASC");
            while ($c = mysqli_fetch_assoc($cust)) {
                echo "<option>" . htmlspecialchars($c['customer_name']) . "</option>";
            }
            ?>
        </select>
    </div>

    <!-- City Filter -->
    <div>
        <label>City</label>
        <select id="cityFilter" class="form-select select2" style="width:200px;">
            <option value="">All Cities</option>
            <?php
            $cities = mysqli_query($conn, "SELECT DISTINCT city FROM domain_list ORDER BY city ASC");
            while ($c = mysqli_fetch_assoc($cities)) {
                echo "<option>" . htmlspecialchars($c['city']) . "</option>";
            }
            ?>
        </select>
    </div>

    <!-- Reset Button -->
    <div>
        <button id="resetFilter" class="btn btn-secondary mt-4"><i class="fas fa-redo"></i> Reset Filters</button>
    </div>
</div>

<div class="table-responsive">
<table id="domainTable" class="table table-bordered table-striped table-hover">
<thead>
<tr>
    <th>SNO</th>
    <th>Domain</th>
    <th>Customer</th>
    <th>Phone</th>
    <th>City</th>
    <th>Country</th>
    <th>Renewal Date</th>
    <th>Status</th>
    <th class="text-center">Actions</th>
</tr>
</thead>
<tbody>
<?php
$serial = 1;
$res = mysqli_query($conn, "SELECT * FROM domain_list ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $statusText = $row['status'] == 1 ? 'Active' : 'Deactive';
    $statusBadge = $row['status'] == 1
        ? "<span class='badge bg-success'>Active</span>"
        : "<span class='badge bg-danger'>Deactive</span>";
    $rowClass = $row['status'] == 0 ? 'table-danger' : '';
?>
<tr class="<?= $rowClass ?>">
    <td><?= $serial++ ?></td>
    <td><?= htmlspecialchars($row['domain_name']) ?></td>
    <td><?= htmlspecialchars($row['customer_name']) ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td><?= htmlspecialchars($row['city']) ?></td>
    <td><?= htmlspecialchars($row['country']) ?></td>
    <td><?= htmlspecialchars($row['renewal_date']) ?></td>
    <td data-search="<?= $statusText ?>"><?= $statusBadge ?></td>
    <td class="text-center">
        <a href="view_domain.php?id=<?= base64_encode($row['id']) ?>" title="View Details">
            <i class="fa fa-eye text-success"></i>
        </a>
        <a href="domain.php?edit=<?= base64_encode($row['id']) ?>" class="ms-2" title="Edit Domain">
            <i class="fa fa-edit text-primary"></i>
        </a>
        <a href="domainlist.php?delete=<?= base64_encode($row['id']) ?>"
           onclick="return confirm('Are you sure you want to permanently delete this domain record?')" class="ms-2" title="Delete Domain">
            <i class="fa fa-trash text-danger"></i>
        </a>
    </td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>

<script>
$(document).ready(function () {

    // Initialize SELECT2
    $('.select2').select2({
        placeholder: "Select option",
        allowClear: true,
        width: 'resolve'
    });

// Initialize DataTable with export dropdown
var table = $('#domainTable').DataTable({
    paging: true,
    searching: false,
    ordering: true,
    pageLength: 10,
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

drawCallback: function () {
    $('#domainTable tbody tr').each(function () {
        $(this).find('td').not(':last').each(function () { // skip Actions column
            if ($.trim($(this).text()) === '') {
                $(this).html('<span class="text-muted">N/A</span>');
            }
        });
    });
}

});



    // Filters
    $('#domainFilter').on('change', function () {
        table.column(1).search(this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '', true, false).draw();
    });

    $('#customerFilter').on('change', function () {
        table.column(2).search(this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '', true, false).draw();
    });

    $('#cityFilter').on('change', function () {
        table.column(4).search(this.value ? $.fn.dataTable.util.escapeRegex(this.value) : '', true, false).draw();
    });

    $('#statusFilter').on('change', function () {
        var val = $(this).val();
        table.column(7).search(
            val === "Active" ? "Active" :
            val === "Deactive" ? "Deactive" : ''
        ).draw();
    });

    // Reset Filter
    $('#resetFilter').on('click', function () {
        $('#statusFilter').val('');
        $('#domainFilter').val('').trigger('change');
        $('#customerFilter').val('').trigger('change');
        $('#cityFilter').val('').trigger('change');
        table.search('').columns().search('').draw();
    });

    // Auto hide alert
    setTimeout(() => {
        const alertBox = document.querySelector('.alert');
        if (alertBox) alertBox.style.display = 'none';
    }, 3000);

});
</script>


<?php include 'footer.php'; ?>
