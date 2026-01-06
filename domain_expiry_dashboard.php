<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location:index.php"); 
    exit;
}

include 'config.php';
include 'get_counts.php';
include 'header.php';
include 'navbar.php';

/* FETCH DOMAINS */
$domains = [];
$res = mysqli_query($conn,"SELECT id, domain_name, renewal_date FROM domain_list WHERE domain_name != '' ORDER BY id ASC");
while($row = mysqli_fetch_assoc($res)) $domains[] = $row;
?>

<style>
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dt-buttons {
    margin-bottom: 20px;
}
</style>

<div class="content-area">
<div class="card shadow p-4">
<h4 class="text-primary mb-4 text-center">Live Domain & SSL Expiry Dashboard</h4>

<table id="domainTable" class="table table-bordered table-striped" style="width:100%; margin-top: 20px;">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Domain</th>
    <th>WS Expiry (DB)</th>
    <th>Domain Expiry (WHOIS/RDAP)</th>
    <th>Days Left</th>
    <th>SSL Expiry</th>
    <th>Name Servers</th>
    <th>Registrar</th> <!-- NEW -->
</tr>
</thead>
<tbody>
<?php foreach($domains as $i => $d): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($d['domain_name']) ?></td>
    <td><?= (!empty($d['renewal_date']) && $d['renewal_date'] != '0000-00-00') ? date('Y-m-d', strtotime($d['renewal_date'])) : '<span class="text-danger">N/A</span>' ?></td>
    <td id="domain_expiry_<?= $d['id'] ?>">Loading...</td>
    <td id="domain_days_<?= $d['id'] ?>">Loading...</td>
    <td id="ssl_expiry_<?= $d['id'] ?>">Loading...</td>
    <td id="ns_<?= $d['id'] ?>">Loading...</td>
    <td id="registrar_<?= $d['id'] ?>">Loading...</td> <!-- NEW -->
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>


<script>
$(function(){
    // Initialize DataTable with export buttons
    var table = $('#domainTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'collection',
                text: '<i class="fa fa-download"></i> Export',
                className: 'btn btn-primary btn-sm',
                buttons: [
                    { extend: 'copy', text: 'Copy' },
                    { extend: 'csv', text: 'CSV' },
                    { extend: 'excel', text: 'Excel' },
                    { extend: 'pdf', text: 'PDF' },
                    { extend: 'print', text: 'Print' }
                ]
            }
        ]
    });

    // Load dynamic WHOIS/SSL info
    let domains = <?= json_encode($domains); ?>;

    domains.forEach(d=>{
        $.getJSON('check_expiry_api.php',{domain:d.domain_name}, function(res){
            // DOMAIN EXPIRY
            $('#domain_expiry_'+d.id).text(res.domain_expiry);

            // DAYS LEFT
            if(res.domain_expiry !== 'Unknown'){
                let expiry = new Date(res.domain_expiry);
                let today  = new Date();
                expiry.setHours(0,0,0,0); 
                today.setHours(0,0,0,0);
                let diff = Math.ceil((expiry - today)/(1000*60*60*24));
                if(diff>0) $('#domain_days_'+d.id).html(`<span class="text-success fw-bold">${diff} Days Left</span>`);
                else if(diff===0) $('#domain_days_'+d.id).html(`<span class="text-warning fw-bold">Expires Today</span>`);
                else $('#domain_days_'+d.id).html(`<span class="text-danger fw-bold">Expired</span>`);
            } else $('#domain_days_'+d.id).text('N/A');

            // SSL EXPIRY
            $('#ssl_expiry_'+d.id).text(res.ssl_expiry);

            // NAME SERVERS
            if(res.name_servers && res.name_servers.length){
                $('#ns_'+d.id).html(res.name_servers.join('<br>'));
            } else {
                $('#ns_'+d.id).text('N/A');
            }

            // REGISTRAR
            $('#registrar_'+d.id).text(res.registrar || 'N/A');
        });
    });
});
</script>

<?php include 'footer.php'; ?>
