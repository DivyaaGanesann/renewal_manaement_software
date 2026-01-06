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

function base_ecode64($id) {
    return strtr(base64_encode($id), '+/=', '-_,');

}
function base_dcode64($code) {
    return base64_decode(strtr($code, '-_,', '+/='));
}

/* FILTER PROCESS */
$where = "WHERE 1"; // No year filter
$filters = [];

if (isset($_GET['filter'])) {
    $filter_array = json_decode(base_dcode64($_GET['filter']), true);
    if (!empty($filter_array['customer'])) {
        $customer = mysqli_real_escape_string($conn, $filter_array['customer']);
        $where .= " AND d.customer_name='$customer'";
        $filters['customer'] = $customer;
    }
    if (!empty($filter_array['domain'])) {
        $domain = mysqli_real_escape_string($conn, $filter_array['domain']);
        $where .= " AND d.domain_name='$domain'";
        $filters['domain'] = $domain;
    }
    if (!empty($filter_array['business'])) {
        $business = mysqli_real_escape_string($conn, $filter_array['business']);
        $where .= " AND d.business_name='$business'";
        $filters['business'] = $business;
    }
    if (!empty($filter_array['status'])) {
        $status = mysqli_real_escape_string($conn, $filter_array['status']);
        $where .= " AND s.status='$status'";
        $filters['status'] = $status;
    }
}

/* MAIN QUERY */
$sql = "
SELECT 
    s.id,
    d.customer_name,
    d.domain_name,
    d.business_name,
    s.mail_id,
    s.main_keyword,
    s.status,
    s.created_at
FROM seo_details s
JOIN domain_list d ON d.id = s.domain_id
$where
ORDER BY s.id DESC
";

$res = mysqli_query($conn, $sql);
include 'get_counts.php';
include 'header.php';
include 'navbar.php';
?>
<div class="content-area">
<div class="container mt-4">
<h3 class="text-center mb-3">SEO Full Report</h3>

<!-- FILTER BOX -->
<div class="dr-filter-box" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">

    <div style="display:flex; flex-direction:column;">
        <label>Customer</label>
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
        <label>Status</label>
        <select id="statusFilter" class="form-select" style="width:150px">
            <option value="">All</option>
            <option value="Pending" <?=($filters['status']??'')=='Pending'?'selected':''?>>Pending</option>
            <option value="In Progress" <?=($filters['status']??'')=='In Progress'?'selected':''?>>In Progress</option>
            <option value="Completed" <?=($filters['status']??'')=='Completed'?'selected':''?>>Completed</option>
        </select>
    </div>

    <div style="display:flex; flex-direction:column; justify-content:flex-end;">
        <button id="filterBtn" class="btn btn-primary mt-2">Search</button>
    </div>

</div>
<div class="table-responsive mt-3">
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
    <th>SNO</th>
    <th>Customer</th>
    <th>Domain</th>
    <th>Business</th>
    <th>SEO Mail</th>
    <th>Main Keyword</th>
    <th>Status</th>
    <th>Date Added</th>
</tr>
</thead>
<tbody>
<?php while($row = mysqli_fetch_assoc($res)):
    $badge = match($row['status']) {
        'Pending' => 'warning',
        'In Progress' => 'info',
        'Completed' => 'success',
        default => 'secondary'
    };
?>
<tr>
    <td></td>
    <td><?=htmlspecialchars($row['customer_name'])?></td>
    <td><?=htmlspecialchars($row['domain_name'])?></td>
    <td><?=htmlspecialchars($row['business_name'])?></td>
    <td><?=htmlspecialchars($row['mail_id'])?></td>
    <td><?=htmlspecialchars($row['main_keyword'])?></td>
    <td><span class="badge bg-<?=$badge?>"><?=$row['status']?></span></td>
    <td><?=htmlspecialchars($row['created_at'])?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>
<script>
$(document).ready(function(){
    $('.select2').select2({placeholder:"Select", allowClear:true, width:'resolve'});

 $('table').DataTable({
    searching:true,
    order:[],
    pageLength:20,
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
        {
            targets:0,
            orderable:false,
            render:function(d,t,r,m){
                return m.row + 1;
            }
        }
    ],

    drawCallback: function () {
        $('tbody tr').each(function () {
            $(this).find('td').each(function () {

                // Skip STATUS column (badge)
                if ($(this).find('.badge').length) return;

                let text = $(this).text().trim();

                if (text === '' || text.toLowerCase() === 'null') {
                    $(this).html('<span class="text-muted">N/A</span>');
                }
            });
        });
    }
});

    $("#filterBtn").click(function(){
        let filter = {
            customer: $("#customerFilter").val(),
            domain: $("#domainFilter").val(),
            business: $("#businessFilter").val(),
            status: $("#statusFilter").val()
        };
        Object.keys(filter).forEach(k => { if (!filter[k]) delete filter[k]; });
        let encoded = btoa(JSON.stringify(filter)).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,',');
        window.location.href = "?filter="+encoded;
    });
});
</script>

<?php include 'footer.php'; ?>
