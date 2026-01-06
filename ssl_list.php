<?php
ob_start();
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

/* ---------- UTILITY FUNCTIONS ---------- */
function base_ecode64($id){
    return strtr(base64_encode($id), '+/=', '-_,');
}
function base_dcode64($code){
    return base64_decode(strtr($code, '-_,', '+/='));
}

/* ---------- DELETE LOGIC ---------- */
if (isset($_GET['delete'])) {
    $id = intval(base_dcode64($_GET['delete']));
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM ssl_list WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['msg'] = "SSL Configuration Deleted Successfully!";
        $_SESSION['msg_type'] = "danger";
        $stmt->close();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/* ---------- FILTER LOGIC ---------- */
$where = ""; 
$filters = [];

if (isset($_GET['filter'])) {
    $f = json_decode(base_dcode64($_GET['filter']), true);

    if (!empty($f['domain'])) {
        $where .= " AND s.domain_name='".mysqli_real_escape_string($conn,$f['domain'])."'";
        $filters['domain'] = $f['domain'];
    }
    if (!empty($f['customer'])) {
        $where .= " AND s.customer_name='".mysqli_real_escape_string($conn,$f['customer'])."'";
        $filters['customer'] = $f['customer'];
    }
    if (!empty($f['status'])) {
        // You can add status logic here, e.g., for 'Expired', 'Near Expiry', etc.
        // For simplicity, we'll implement this in PHP/JS coloring, not SQL filter,
        // unless you add a 'status' column to the 'ssl_list' table.
    }
}

/* ---------- SSL LIST QUERY (GET) ---------- */
// Fetch all SSL data for the list/table
$ssl_sql = "
    SELECT
        s.id,
        s.domain_name,
        s.customer_name,
        s.launch_date,
        s.last_renewal_date,
        s.ssl_expiry_date
    FROM ssl_list s
    WHERE 1=1 $where 
    ORDER BY s.ssl_expiry_date ASC
";
$ssl_res = mysqli_query($conn, $ssl_sql);
$ssl_rows = [];
while($r = mysqli_fetch_assoc($ssl_res)){ $ssl_rows[] = $r; }


include 'header.php';
include 'navbar.php';
?>

<style>
/* Base Select2 Styles */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
    height: 38px !important; padding: 6px 12px !important;
    border: 1px solid #ced4da !important; border-radius: 0.375rem !important;
    display: flex !important; align-items: center !important; background-color: #fff;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    padding-left: 0 !important; line-height: 1.5 !important; color: #212529;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #6c757d;
}
</style>

<div class="content-area">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-12">

            <h3 class="text-center mb-4"><i class="fas fa-shield-alt me-2"></i>SSL Configuration List</h3>
            
            <?php if(isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'success' ?> alert-dismissible fade show text-center">
                    <?= $_SESSION['msg']; unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

         
                <div class="dr-filter-box" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;margin-bottom:10px;">

                    <div style="display:flex; flex-direction:column; min-width:200px;">
                        <label class="form-label mb-0">Domain</label>
                        <select id="domainFilter" class="form-select select2">
                            <option value="">All Domains</option>
                            <?php
                            $dm = mysqli_query($conn,"SELECT DISTINCT domain_name FROM ssl_list ORDER BY domain_name");
                            while($d=mysqli_fetch_assoc($dm)){
                                $sel = (($filters['domain']??'')==$d['domain_name'])?'selected':'';
                                echo "<option value='{$d['domain_name']}' $sel>{$d['domain_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="display:flex; flex-direction:column; min-width:200px;">
                        <label class="form-label mb-0">Customer Name</label>
                        <select id="customerFilter" class="form-select select2">
                            <option value="">All Customers</option>
                            <?php
                            $cm = mysqli_query($conn,"SELECT DISTINCT customer_name FROM ssl_list ORDER BY customer_name");
                            while($c=mysqli_fetch_assoc($cm)){
                                $sel = (($filters['customer']??'')==$c['customer_name'])?'selected':'';
                                echo "<option value='{$c['customer_name']}' $sel>{$c['customer_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="display:flex; flex-direction:column; justify-content:flex-end;">
                        <button id="filterBtn" class="btn btn-primary">Search</button>
                    </div>

                </div>
       
         
                <table id="sslTable" class="table table-bordered table-striped table-sm mt-3">
                    <thead>
                        <tr>
                            <th>SNO</th>
                            <th>Domain</th>
                            <th>Customer Name</th>
                            <th>Launch Date</th>
                            <th>Last Renewal</th>
                            <th>Expiry Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach($ssl_rows as $r):
                            $expiry = new DateTime($r['ssl_expiry_date']);
                            $today = new DateTime();
                            $interval = $today->diff($expiry);
                            $days_left = $interval->days;
                            
                            $row_class = '';
                            if ($expiry < $today) {
                                $row_class = 'table-danger'; // Expired
                                $expiry_text = "Expired";
                            } elseif ($days_left <= 30) {
                                $row_class = 'table-warning'; // Warning
                                $expiry_text = "Expires";
                            } else {
                                $expiry_text = date('d-m-Y', strtotime($r['ssl_expiry_date']));
                            }
                            $id_encoded = base_ecode64($r['id']);
                        ?>
                        <tr class="<?= $row_class ?>">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($r['domain_name']) ?></td>
                            <td><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td><?= $r['launch_date'] ? date('d-m-Y', strtotime($r['launch_date'])) : 'N/A' ?></td>
                            <td><?= $r['last_renewal_date'] ? date('d-m-Y', strtotime($r['last_renewal_date'])) : 'N/A' ?></td>
                            <td class="fw-bold"><?= $expiry_text ?></td>
                            <td class="text-center">
                                <a href="ssl.php?edit_id=<?= $id_encoded ?>" class="text-primary me-2"><i class="fa fa-edit"></i></a>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>?delete=<?= $id_encoded ?>" 
                                   onclick="return confirm('Are you sure you want to delete SSL for <?= htmlspecialchars($r['domain_name']) ?>?')"
                                   class="text-danger">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </div>
 
</div>
</div>

<?php include 'footer.php'; ?>


<script>
$(function(){

    // 1. Initialize Select2 for Filters
    $('.select2').select2({
        placeholder: "Select",
        allowClear: true,
        width: 'resolve' 
    });
    
    // 2. Filter Button Handler
    $("#filterBtn").click(function(){
        let filter = {
            domain: $("#domainFilter").val(),
            customer: $("#customerFilter").val()
            // status: $("#statusFilter").val() // Add this if a status filter is implemented
        };
        // Remove empty values
        Object.keys(filter).forEach(k=>{ if(!filter[k]) delete filter[k]; });
        
        // Encode and redirect
        let encoded = btoa(JSON.stringify(filter))
                      .replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,',');
        window.location.href = "?filter="+encoded;
    });


    // 3. Initialize DataTables for SSL List
    $('#sslTable').DataTable({
        paging: true,
        searching: false,
        ordering: true,
        info: true,
        responsive: true,
        order: [[5, 'asc']], // Order by Expiry Date ascending (Column 6: index 5)
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fa fa-download"></i> Download',
                className: 'btn btn-primary btn-sm mb-2',
                buttons: [
                    { extend: 'copy', text: 'Copy' },
                    { extend: 'csv', text: 'CSV' },
                    { extend: 'excel', text: 'Excel' },
                    { extend: 'pdf', text: 'PDF' },
                    { extend: 'print', text: 'Print' }
                ]
            }
        ],
        columnDefs:[
            { targets:0, orderable:false, render:(d,t,r,m)=>m.row+1 }, // SNO column numbering
            { targets:6, orderable:false } // Disable ordering on Actions column
        ]
    });

    // 4. Hide success/error messages after a delay
    setTimeout(function(){
        $(".alert").fadeOut('slow');
    }, 4000);

});
</script>