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

// ---------- CUSTOM BASE64 ENCODER/DECODER ----------
function base_ecode64($id) {
    return strtr(base64_encode($id), '+/=', '-_,');
}

function base_dcode64($code) {
    return base64_decode(strtr($code, '-_,', '+/='));
}

// ---------- PROCESS FILTERS ----------
$filters = [];
$where = "WHERE 1";

if (isset($_GET['filter'])) {
    $filter_array = json_decode(base_dcode64($_GET['filter']), true);

    if (!empty($filter_array['domain'])) {
        $domain = mysqli_real_escape_string($conn, $filter_array['domain']);
        $where .= " AND domain_name='$domain'";
        $filters['domain'] = $domain;
    }

    if (!empty($filter_array['customer'])) {
        $customer = mysqli_real_escape_string($conn, $filter_array['customer']);
        $where .= " AND customer_name='$customer'";
        $filters['customer'] = $customer;
    }

    if (!empty($filter_array['status'])) {
        $status = $filter_array['status'] === 'Active' ? 1 : 0;
        $where .= " AND status='$status'";
        $filters['status'] = $filter_array['status'];
    }

    if (!empty($filter_array['from']) && !empty($filter_array['to'])) {
        $from = $filter_array['from'];
        $to = $filter_array['to'];
        $where .= " AND renewal_date BETWEEN '$from' AND '$to'";
        $filters['from'] = $from;
        $filters['to'] = $to;
    }
}

// ---------- MAIN QUERY ----------
$res = mysqli_query($conn, "SELECT * FROM domain_list $where ORDER BY id DESC");

// ---------- ENCODE FILTERS FOR URL ----------
$filter_encoded = base_ecode64(json_encode($filters));
?>

<div id="domainreportpage" class="content-area">
<div class="container mt-4">

    <h3 class="text-center mb-3">Overall Domain Report</h3>

    <div class="text-end mb-3">
        <a href="domain.php" class="btn btn-primary btn-sm">+ Add New Domain</a>
    </div>

    <div class="dr-filter-box" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">

        <div style="display:flex; flex-direction:column;">
            <label>Status:</label>
            <select id="statusFilter" class="form-select" style="width:150px;">
                <option value="">All Status</option>
                <option value="Active" <?= (isset($filters['status']) && $filters['status']=='Active')?'selected':'' ?>>Active</option>
                <option value="Deactive" <?= (isset($filters['status']) && $filters['status']=='Deactive')?'selected':'' ?>>Deactive</option>
            </select>
        </div>

        <div style="display:flex; flex-direction:column;">
            <label>Domain:</label>
            <select id="domainFilter" class="form-select select2" style="width:200px;">
                <option value="">All Domains</option>
                <?php
                $dm = mysqli_query($conn, "SELECT DISTINCT domain_name FROM domain_list ORDER BY domain_name ASC");
                while ($d = mysqli_fetch_assoc($dm)) {
                    $sel = (isset($filters['domain']) && $filters['domain']==$d['domain_name']) ? 'selected' : '';
                    echo "<option value='".htmlspecialchars($d['domain_name'])."' $sel>".htmlspecialchars($d['domain_name'])."</option>";
                }
                ?>
            </select>
        </div>

        <div style="display:flex; flex-direction:column;">
            <label>Customer:</label>
            <select id="customerFilter" class="form-select select2" style="width:200px;">
                <option value="">All Customers</option>
                <?php
                $cust = mysqli_query($conn, "SELECT DISTINCT customer_name FROM domain_list ORDER BY customer_name ASC");
                while ($c = mysqli_fetch_assoc($cust)) {
                    $sel = (isset($filters['customer']) && $filters['customer']==$c['customer_name']) ? 'selected' : '';
                    echo "<option value='".htmlspecialchars($c['customer_name'])."' $sel>".htmlspecialchars($c['customer_name'])."</option>";
                }
                ?>
            </select>
        </div>

        <div style="display:flex; flex-direction:column;">
            <label>From (Renewal):</label>
            <input type="date" id="fromFilter" class="form-control" value="<?= $filters['from'] ?? '' ?>" style="width:150px;">
        </div>

        <div style="display:flex; flex-direction:column;">
            <label>To (Renewal):</label>
            <input type="date" id="toFilter" class="form-control" value="<?= $filters['to'] ?? '' ?>" style="width:150px;">
        </div>

        <div style="display:flex; flex-direction:column; justify-content:flex-end;">
            <button id="filterBtn" class="btn btn-primary mt-2">Search</button>
        </div>

    </div>

    <!-- DOMAIN TABLE -->
    <div class="table-responsive mt-3">
        <table id="domainReportTable" class="table table-bordered table-striped table-sm">
            <thead>
                <tr>
                    <th>SNO</th>
                    <th>Domain</th>
                    <th>Customer</th>
                    <th>Renewal Date</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            while($row = mysqli_fetch_assoc($res)):
                $statusBadge = $row['status']==1 
                    ? "<span class='badge bg-success'>Active</span>" 
                    : "<span class='badge bg-danger'>Deactive</span>";
                $rowClass = $row['status']==0 ? 'table-danger' : '';
                $id_encoded = base_ecode64($row['id']);
            ?>
            <tr class="<?= $rowClass ?>">
                <td></td>
                <td><?= htmlspecialchars($row['domain_name']) ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= $row['renewal_date'] ?></td>
                <td><?= $statusBadge ?></td>
                <td class="text-center">
                    <a href="view_domain.php?id=<?= $id_encoded ?>"><i class="fa fa-eye text-success"></i></a>
                    <a href="domain.php?edit=<?= $id_encoded ?>" class="ms-2"><i class="fa fa-edit text-primary"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({ placeholder: "Select option", allowClear: true, width: 'resolve' });
$('#domainReportTable').DataTable({
    paging: true,
    searching: false,
    ordering: true,
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
        {
            targets: 0,
            orderable: false,
            render: function (data, type, row, meta) {
                return meta.row + 1;
            }
        },
        { orderable: false, targets: 5 } // Actions column
    ],

    drawCallback: function () {
        $('#domainReportTable tbody tr').each(function () {
            $(this).find('td').not(':last').each(function () {
                if ($.trim($(this).text()) === '') {
                    $(this).html('<span class="text-muted">N/A</span>');
                }
            });
        });
    }
});


    $("#filterBtn").on("click", function () {
        let filter = {
            status: $("#statusFilter").val(),
            domain: $("#domainFilter").val(),
            customer: $("#customerFilter").val(),
            from: $("#fromFilter").val(),
            to: $("#toFilter").val()
        };
        Object.keys(filter).forEach(key => { if (!filter[key]) delete filter[key]; });
        let encoded = btoa(JSON.stringify(filter)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, ',');
        window.location.href = "?filter=" + encoded;
    });
});
</script>

<?php include 'footer.php'; ?>
