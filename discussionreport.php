<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';
include 'header.php';
include 'navbar.php';

// Base64 encode/decode functions
function base_ecode64($id) {
    return strtr(base64_encode($id), '+/=', '-_,');
}

function base_dcode64($code) {
    return base64_decode(strtr($code, '-_,', '+/='));
}

// Filters
$filters = [];
$where = "WHERE 1";

if (isset($_GET['filter'])) {
    $filter_array = json_decode(base_dcode64($_GET['filter']), true);

    if (!empty($filter_array['staff'])) {
        $staff = mysqli_real_escape_string($conn, $filter_array['staff']);
        $where .= " AND d.staff_name='$staff'";
        $filters['staff'] = $staff;
    }

    if (!empty($filter_array['domain'])) {
        $domain = mysqli_real_escape_string($conn, $filter_array['domain']);
        $where .= " AND d.domain_name='$domain'";
        $filters['domain'] = $domain;
    }

    if (!empty($filter_array['customer'])) {
        $customer = mysqli_real_escape_string($conn, $filter_array['customer']);
        $where .= " AND dl.customer_name='$customer'";
        $filters['customer'] = $customer;
    }

    if (!empty($filter_array['from']) && !empty($filter_array['to'])) {
        $from = $filter_array['from'];
        $to = $filter_array['to'];
        $where .= " AND d.discussion_date BETWEEN '$from' AND '$to'";
        $filters['from'] = $from;
        $filters['to'] = $to;
    }
}

// SQL with Customer
$sql = "
SELECT d.*, 
       s.name AS staff_real_name, 
       dl.customer_name, 
       dl.business_name
FROM discussion d
LEFT JOIN staff s ON d.staff_name = s.username
LEFT JOIN domain_list dl ON d.domain_name = dl.domain_name
$where
ORDER BY d.id DESC
";
$res = mysqli_query($conn, $sql);

$filter_encoded = base_ecode64(json_encode($filters));
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<div id="discussionreportpage" class="content-area">
  <div class="container mt-4">

    <h3 class="text-center mb-4">Discussion Report</h3>

    <?php if (isset($_SESSION['msg'])): ?>
      <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'success' ?> text-center">
        <?= $_SESSION['msg']; ?>
      </div>
      <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-3">
      <a href="discussion.php" class="btn btn-primary btn-sm">+ Add New Discussion</a>
    </div>
<!-- Filters: All in one row -->
<form id="discussionFilterForm" class="row g-2 align-items-end mb-3">
  <div class="col-auto">
    <label for="staff" class="form-label">Staff</label>
    <select id="staff" class="dr-filter-input form-select select2" style="min-width:150px">
      <option value="">All Staff</option>
      <?php
      $st = mysqli_query($conn, "SELECT username, name FROM staff WHERE status=1");
      while ($s = mysqli_fetch_assoc($st)) {
          $sel = ($filters['staff'] ?? '') == $s['username'] ? 'selected' : '';
          echo "<option value='{$s['username']}' $sel>{$s['name']}</option>";
      }
      ?>
    </select>
  </div>

  <div class="col-auto">
    <label for="domain" class="form-label">Domain</label>
    <select id="domain" class="dr-filter-input form-select select2" style="min-width:150px">
      <option value="">All Domains</option>
      <?php
      $dm = mysqli_query($conn, "SELECT domain_name FROM domain_list");
      while ($d = mysqli_fetch_assoc($dm)) {
          $sel = ($filters['domain'] ?? '') == $d['domain_name'] ? 'selected' : '';
          echo "<option value='{$d['domain_name']}' $sel>{$d['domain_name']}</option>";
      }
      ?>
    </select>
  </div>

  <div class="col-auto">
    <label for="customer" class="form-label">Customer</label>
    <select id="customer" class="dr-filter-input form-select select2" style="min-width:150px">
      <option value="">All Customers</option>
      <?php
      $custRes = mysqli_query($conn, "SELECT DISTINCT customer_name FROM domain_list");
      while ($c = mysqli_fetch_assoc($custRes)) {
          $sel = ($filters['customer'] ?? '') == $c['customer_name'] ? 'selected' : '';
          echo "<option value='{$c['customer_name']}' $sel>{$c['customer_name']}</option>";
      }
      ?>
    </select>
  </div>

  <div class="col-auto">
    <label for="from" class="form-label">From</label>
    <input type="date" id="from" class="dr-filter-input form-control" value="<?= $filters['from'] ?? '' ?>" style="min-width:130px">
  </div>

  <div class="col-auto">
    <label for="to" class="form-label">To</label>
    <input type="date" id="to" class="dr-filter-input form-control" value="<?= $filters['to'] ?? '' ?>" style="min-width:130px">
  </div>

  <div class="col-auto d-grid">
    <button type="button" id="searchBtn" class="btn btn-primary mt-2">Search</button>
  </div>
</form>


    <!-- Discussion Table -->
    <div class="table-responsive">
      <table id="discussionTable" class="table table-bordered table-striped table-hover table-sm align-middle">
        <thead class="table-light text-center">
          <tr>
            <th>SNO</th>
            <th>Staff</th>
            <th>Domain</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Purpose</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($res)):
              $id_encoded = base_ecode64($row['id']);
          ?>
          <tr>
            <td class="text-center"></td>
            <td><?= htmlspecialchars($row['staff_real_name']) ?></td>
            <td><?= htmlspecialchars($row['domain_name']) ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['discussion_date']) ?></td>
            <td><?= htmlspecialchars($row['purpose']) ?></td>
            <td class="text-center">
              <a href="view_discussion.php?id=<?= $id_encoded ?>" title="View">
                <i class="fa fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {

    // Activate Select2
    $('.select2').select2({
        placeholder: "Select option",
        allowClear: true,
        width: '100%'
    });

    // Initialize DataTable
    $('#discussionTable').DataTable({
        paging: true,
        searching: false,
        ordering: true,
        lengthChange: true,
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
        order: [],
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                render: function(data, type, row, meta) {
                    return meta.row + 1; // Auto SNO
                }
            },
            { orderable: false, targets: 6 } // Actions column
        ]
    });

    // Filter button
    $("#searchBtn").on("click", function () {
        let filter = {
            staff: $("#staff").val(),
            domain: $("#domain").val(),
            customer: $("#customer").val(),
            from: $("#from").val(),
            to: $("#to").val()
        };

        Object.keys(filter).forEach(key => {
            if (!filter[key]) delete filter[key];
        });

        let encoded = btoa(JSON.stringify(filter))
                        .replace(/\+/g, '-')
                        .replace(/\//g, '_')
                        .replace(/=/g, ',');

        window.location.href = "?filter=" + encoded;
    });

});
</script>

<?php include 'footer.php'; ?>
