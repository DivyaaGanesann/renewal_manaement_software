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
include 'get_counts.php'; // Include necessary files

/* ---------- UTILITY FUNCTIONS (Needed here for decoding/encoding links) ---------- */
function base_ecode64($data){
    return strtr(base64_encode($data), '+/=', '-_,');
}
function base_dcode64($code){
    return base64_decode(strtr($code, '-_,', '+/='));
}
/* -------------------------------------- */

/* -------------------------------------- */
/* ---------- DATA LIST PREPARATION (Filters & Fetch) ---------- */
/* -------------------------------------- */

$list_where = ""; 
$list_filters = [];

// Filtering logic
if (isset($_GET['filter'])) {
    $f = json_decode(base_dcode64($_GET['filter']), true);

    if (!empty($f['domain'])) {
        $list_where .= " AND domain_name='".mysqli_real_escape_string($conn,$f['domain'])."'";
        $list_filters['domain'] = $f['domain'];
    }
    if (!empty($f['customer'])) {
        $list_where .= " AND customer_name='".mysqli_real_escape_string($conn,$f['customer'])."'";
        $list_filters['customer'] = $f['customer'];
    }
    if (!empty($f['status'])) {
        $list_where .= " AND status='".mysqli_real_escape_string($conn,$f['status'])."'";
        $list_filters['status'] = $f['status'];
    }
    if (!empty($f['meta_status'])) {
        $list_where .= " AND meta_status='".mysqli_real_escape_string($conn,$f['meta_status'])."'";
        $list_filters['meta_status'] = $f['meta_status'];
    }
}

// Fetch list data
$wab_sql = "
    SELECT
        domain_name,
        customer_name,
        whatsapp_number,
        facebook_business_id,
        status,
        meta_status
    FROM wab_config
    WHERE 1=1 $list_where
    ORDER BY domain_name ASC
";
$wab_res = mysqli_query($conn, $wab_sql);
$wab_rows = [];
while($r = mysqli_fetch_assoc($wab_res)){ $wab_rows[] = $r; }


/* -------------------------------------- */
/* ---------- HTML OUTPUT (TABLE SECTION) ---------- */
/* -------------------------------------- */

include 'header.php';
include 'navbar.php';
?>
<style>
/* Styles for Select2 for filters */
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
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}
</style>

<div class="content-area">
<div class="container py-5">
    
    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            <h3 class="text-center mb-3">WAB Configuration List</h3>
            
            <div class="text-end mb-3">
                <a href="wab.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Add New WAB Config
                </a>
            </div>


                
                <?php if(isset($_SESSION['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show text-center">
                        <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['msg_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show text-center">
                        <?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?>
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="dr-filter-box mb-3 d-flex flex-wrap gap-3 align-items-end">

                    <div style="min-width:200px;">
                        <label class="form-label mb-0">Domain</label>
                        <select id="domainFilter" class="form-select select2">
                            <option value="">All Domains</option>
                            <?php
                            $dm = mysqli_query($conn,"SELECT DISTINCT domain_name FROM wab_config ORDER BY domain_name");
                            while($d=mysqli_fetch_assoc($dm)){
                                $sel = (($list_filters['domain']??'')==''.htmlspecialchars($d['domain_name']))?'selected':'';
                                echo "<option value='".htmlspecialchars($d['domain_name'])."' $sel>".htmlspecialchars($d['domain_name'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="min-width:200px;">
                        <label class="form-label mb-0">Customer Name</label>
                        <select id="customerFilter" class="form-select select2">
                            <option value="">All Customers</option>
                            <?php
                            $cm = mysqli_query($conn,"SELECT DISTINCT customer_name FROM wab_config ORDER BY customer_name");
                            while($c=mysqli_fetch_assoc($cm)){
                                $sel = (($list_filters['customer']??'')==''.htmlspecialchars($c['customer_name']))?'selected':'';
                                echo "<option value='".htmlspecialchars($c['customer_name'])."' $sel>".htmlspecialchars($c['customer_name'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div style="min-width:150px;">
                        <label class="form-label mb-0">Status</label>
                        <select id="statusFilter" class="form-select select2">
                            <option value="">All</option>
                            <option value="Active" <?=($list_filters['status']??'')=='Active'?'selected':''?>>Active</option>
                            <option value="Inactive" <?=($list_filters['status']??'')=='Inactive'?'selected':''?>>Inactive</option>
                        </select>
                    </div>

                    <div style="min-width:150px;">
                        <label class="form-label mb-0">Meta Status</label>
                        <select id="metaStatusFilter" class="form-select select2">
                            <option value="">All</option>
                            <option value="Verified" <?=($list_filters['meta_status']??'')=='Verified'?'selected':''?>>Verified</option>
                            <option value="Not Verified" <?=($list_filters['meta_status']??'')=='Not Verified'?'selected':''?>>Not Verified</option>
                        </select>
                    </div>

                    <div>
                        <button id="filterBtn" class="btn btn-primary">Search</button>
                    </div>
                    
                    <?php if (!empty($list_filters)): ?>
                    <div class="ms-auto">
                        <a href="wab_list.php" class="btn btn-warning">Reset Filters</a>
                    </div>
                    <?php endif; ?>

                </div>
                <table id="wabTable" class="table table-bordered table-striped table-sm mt-3">
                    <thead>
                        <tr>
                            <th>SNO</th>
                            <th>Domain</th>
                            <th>Customer Name</th>
                            <th>WhatsApp</th>
                            <th>FB Business ID</th>
                            <th>Status</th>
                            <th>Meta Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach($wab_rows as $r):
                            
                            $status_badge = match($r['status']){
                                'Active' => 'success',
                                'Inactive' => 'secondary',
                                default => 'info'
                            };
                            
                            $meta_badge = match($r['meta_status']){
                                'Verified' => 'success',
                                'Not Verified' => 'warning',
                                default => 'secondary'
                            };

                            // Use the utility function defined above
                            $domain_encoded = base_ecode64($r['domain_name']);
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($r['domain_name']) ?></td>
                            <td><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td><?= htmlspecialchars($r['whatsapp_number']) ?></td>
                            <td><?= htmlspecialchars($r['facebook_business_id']) ?></td>
                            <td><span class="badge bg-<?= $status_badge ?>"><?= $r['status'] ?></span></td>
                            <td><span class="badge bg-<?= $meta_badge ?>"><?= $r['meta_status'] ?></span></td>
<td class="text-center">
    <a href="wab_view.php?domain=<?= $domain_encoded ?>" class="text-info me-2" title="View">
        <i class="fa fa-eye"></i>
    </a>
    
    <a href="wab.php?edit_domain=<?= $domain_encoded ?>" class="text-primary me-2" title="Edit">
        <i class="fa fa-edit"></i>
    </a>
    
    <a href="wab.php?delete=<?= $domain_encoded ?>" 
        onclick="return confirm('Delete WAB config for <?= htmlspecialchars($r['domain_name']) ?>?')"
        class="text-danger" title="Delete">
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
    $('#domainFilter, #customerFilter, #statusFilter, #metaStatusFilter').select2({
        placeholder: "Select",
        allowClear: true,
        width: '100%'
    });
    
    // 2. Filter Button Handler 
    $("#filterBtn").click(function(){
        let filter = {
            domain: $("#domainFilter").val(),
            customer: $("#customerFilter").val(),
            status: $("#statusFilter").val(),
            meta_status: $("#metaStatusFilter").val()
        };
        // Remove empty values
        Object.keys(filter).forEach(k=>{ if(!filter[k]) delete filter[k]; });
        
        // Encode and redirect
        let encoded = btoa(JSON.stringify(filter))
                      .replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,',');
        // Redirects to the current file (wab_list.php) with filters
        window.location.href = "<?= $_SERVER['PHP_SELF'] ?>?filter="+encoded;
    });

    // 3. Hide success/error messages
    setTimeout(function(){
        $(".alert").fadeOut('slow');
    }, 4000);

    // 4. Initialize DataTables for WAB List
    $('#wabTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        responsive: true,
        order: [[1, 'asc']], // Order by Domain Name ascending (index 1)
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
            { targets:7, orderable:false } // Disable ordering on Actions column
        ]
    });
});
</script>