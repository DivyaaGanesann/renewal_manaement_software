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

/* ---------- BASE64 URL SAFE ---------- */
function base_ecode64($id){
    return strtr(base64_encode($id), '+/=', '-_,');
}
function base_dcode64($code){
    return base64_decode(strtr($code, '-_,', '+/='));
}

/* ---------- FILTER ---------- */
$where = "WHERE 1"; // No year filter
$filters = [];

if (isset($_GET['filter'])) {
    $f = json_decode(base_dcode64($_GET['filter']), true);
    if (!empty($f['status'])) {
        $where .= " AND m.status='".mysqli_real_escape_string($conn,$f['status'])."'";
        $filters['status'] = $f['status'];
    }
    if (!empty($f['domain'])) {
        $where .= " AND d.domain_name='".mysqli_real_escape_string($conn,$f['domain'])."'";
        $filters['domain'] = $f['domain'];
    }
    if (!empty($f['business'])) {
        $where .= " AND d.business_name='".mysqli_real_escape_string($conn,$f['business'])."'";
        $filters['business'] = $f['business'];
    }
    if (!empty($f['customer'])) {
        $where .= " AND d.customer_name='".mysqli_real_escape_string($conn,$f['customer'])."'";
        $filters['customer'] = $f['customer'];
    }
}

/* ---------- QUERY ---------- */
$sql = "
SELECT 
    m.id,
    d.customer_name,
    d.domain_name,
    d.business_name,
    m.login_type,
    m.map_keyword,
    m.action_role,
    m.status,
    m.created_at
FROM map_configuration m
JOIN domain_list d ON d.id=m.domain_id
$where
ORDER BY m.id DESC
";

$res = mysqli_query($conn,$sql);
$rows = [];
while($r=mysqli_fetch_assoc($res)){ $rows[]=$r; }

include 'header.php';
include 'navbar.php';
?>

<div class="content-area">
<div class="container mt-4">

<h3 class="text-center mb-3">MAP Full Report</h3>

<!-- FILTERS -->
<div class="dr-filter-box" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end; margin-bottom:15px;">

    <div style="display:flex; flex-direction:column;">
        <label>Status</label>
        <select id="statusFilter" class="form-select select2" style="width:150px">
            <option value="">All</option>
            <option value="Pending" <?=($filters['status']??'')=='Pending'?'selected':''?>>Pending</option>
            <option value="Progress" <?=($filters['status']??'')=='Progress'?'selected':''?>>Progress</option>
            <option value="Verified" <?=($filters['status']??'')=='Verified'?'selected':''?>>Verified</option>
        </select>
    </div>

    <div style="display:flex; flex-direction:column;">
        <label>Domain</label>
        <select id="domainFilter" class="form-select select2" style="width:200px">
            <option value="">All Domains</option>
            <?php
            $dm = mysqli_query($conn,"SELECT DISTINCT domain_name FROM domain_list ORDER BY domain_name");
            while($d=mysqli_fetch_assoc($dm)){
                $sel = (($filters['domain']??'')==$d['domain_name'])?'selected':'';
                echo "<option value='{$d['domain_name']}' $sel>{$d['domain_name']}</option>";
            }
            ?>
        </select>
    </div>

    <div style="display:flex; flex-direction:column;">
        <label>Business</label>
        <select id="businessFilter" class="form-select select2" style="width:200px">
            <option value="">All Businesses</option>
            <?php
            $bm = mysqli_query($conn,"SELECT DISTINCT business_name FROM domain_list ORDER BY business_name");
            while($b=mysqli_fetch_assoc($bm)){
                $sel = (($filters['business']??'')==$b['business_name'])?'selected':'';
                echo "<option value='{$b['business_name']}' $sel>{$b['business_name']}</option>";
            }
            ?>
        </select>
    </div>

    <div style="display:flex; flex-direction:column;">
        <label>Customer Name</label>
        <select id="customerFilter" class="form-select select2" style="width:200px">
            <option value="">All Customers</option>
            <?php
            $cm = mysqli_query($conn,"SELECT DISTINCT customer_name FROM domain_list ORDER BY customer_name");
            while($c=mysqli_fetch_assoc($cm)){
                $sel = (($filters['customer']??'')==$c['customer_name'])?'selected':'';
                echo "<option value='{$c['customer_name']}' $sel>{$c['customer_name']}</option>";
            }
            ?>
        </select>
    </div>

    <div style="display:flex; flex-direction:column; justify-content:flex-end;">
        <button id="filterBtn" class="btn btn-primary mt-2">Search</button>
    </div>

</div>

<!-- TABLE -->
<table id="mapTable" class="table table-bordered table-striped table-sm">
<thead>
<tr>
    <th>SNO</th>
    <th>Customer Name</th>
    <th>Domain</th>
    <th>Business</th>
    <th>Login Type</th>
    <th>Main Keyword</th>
    <th>Action Role</th>
    <th>Status</th>
    <th>Date Added</th>
</tr>
</thead>
<tbody>
<?php $i=1; foreach($rows as $r):
$badge = match($r['status']){
    'Pending'=>'warning',
    'Progress'=>'info',
    'Verified'=>'success',
    default=>'secondary'
};
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($r['customer_name']) ?></td>
    <td><?= htmlspecialchars($r['domain_name']) ?></td>
    <td><?= htmlspecialchars($r['business_name']) ?></td>
    <td><span class="badge bg-dark"><?= $r['login_type'] ?></span></td>
    <td><?= htmlspecialchars($r['map_keyword']) ?></td>
    <td><?= htmlspecialchars($r['action_role']) ?></td>
    <td><span class="badge bg-<?= $badge ?>"><?= $r['status'] ?></span></td>
    <td><?= htmlspecialchars($r['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>

<script>
$(function(){
    $('.select2').select2({placeholder:"Select", allowClear:true, width:'resolve'});

    $('#mapTable').DataTable({
        searching:false,
        pageLength:20,
        order:[],
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
        columnDefs:[
            { targets:0, orderable:false }
        ],
        drawCallback:function(){
            $('#mapTable tbody td').each(function(){
                if ($(this).find('span,a,i,button').length) return;
                let t = $(this).text().trim();
                if (t === '' || t.toLowerCase() === 'null') {
                    $(this).html('<span class="text-muted">N/A</span>');
                }
            });
        }
    });
    $("#filterBtn").click(function(){
        let filter = {
            status: $("#statusFilter").val(),
            domain: $("#domainFilter").val(),
            business: $("#businessFilter").val(),
            customer: $("#customerFilter").val()
        };
        Object.keys(filter).forEach(k=>{ if(!filter[k]) delete filter[k]; });
        let encoded = btoa(JSON.stringify(filter)).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,',');
        window.location.href = "?filter="+encoded;
    });
});
</script>

<?php include 'footer.php'; ?>
