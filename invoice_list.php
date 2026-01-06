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

// DELETE INVOICE
if (isset($_GET['delete'])) {
    $id = base64_decode($_GET['delete']);
    mysqli_query($conn, "DELETE FROM invoice WHERE id='$id'");
    $_SESSION['msg'] = "Invoice Deleted Successfully!";
    $_SESSION['msg_type'] = "danger";
    header("Location: invoice_list.php");
    exit;
}

include 'header.php';
include 'navbar.php';
?>

<div id="invoicepage" class="content-area">
<div class="container mt-4">

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-<?= $_SESSION['msg_type'] ?? 'success' ?> text-center fw-bold">
    <?= htmlspecialchars($_SESSION['msg']); unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
</div>
<?php endif; ?>

<h4 class="mb-3 text-center">Invoice List</h4>
<a href="invoice.php" class="btn btn-primary mb-3">+ Add New Invoice</a>

<!-- ================= FILTER UI ================= -->
<div class="dr-filter-box mb-3 d-flex flex-wrap gap-3 align-items-end">
    <div>
        <label>Domain</label>
        <select id="domainFilter" class="form-select select2" style="width:200px;">
            <option value="">All Domains</option>
            <?php
            $dm = mysqli_query($conn, "SELECT DISTINCT domain_name FROM invoice ORDER BY domain_name ASC");
            while ($d = mysqli_fetch_assoc($dm)) {
                echo "<option>".htmlspecialchars($d['domain_name'])."</option>";
            }
            ?>
        </select>
    </div>

    <div>
        <label>Status</label>
        <select id="statusFilter" class="form-select select2" style="width:160px;">
            <option value="Both">Both</option>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>
        </select>
    </div>

    <div>
        <label>Renewal Date From</label>
        <input type="date" id="renewalFrom" class="form-control" style="width:160px;">
    </div>
    <div>
        <label>Renewal Date To</label>
        <input type="date" id="renewalTo" class="form-control" style="width:160px;">
    </div>

    <div>
        <button id="resetFilter" class="btn btn-secondary mt-4">Reset</button>
    </div>
</div>
<!-- ================= END FILTER UI ================= -->

<div class="table-responsive">
<table id="invoiceTable" class="table table-bordered table-striped table-hover">
<thead>
<tr>
    <th>SNO</th>
    <th>Domain Name</th>
    <th>Description</th>
    <th>Renewal Date</th>
    <th>Amount</th>
    <th>Status</th>
    <th class="text-center">Actions</th>
</tr>
</thead>
<tbody>
<?php
$sql = "
    SELECT i.*, d.customer_name, d.whatsapp
    FROM invoice i
    LEFT JOIN domain_list d ON i.domain_id = d.id
    ORDER BY i.id DESC
";
$res = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($res)) {

    // Status Badge
    $statusLower = strtolower($row['status']);
    if ($statusLower == 'paid') {
        $statusBadge = "<span class='badge bg-success'>Paid</span>";
    } elseif ($statusLower == 'unpaid') {
        $statusBadge = "<span class='badge bg-warning text-dark'>Unpaid</span>";
    } else {
        $statusBadge = "<span class='badge bg-danger'>Unknown</span>";
    }

    $renewal = $row['expiry_date'] ?? '';
?>
<tr>
    <td></td>
    <td><?= htmlspecialchars($row['domain_name']) ?></td>
    <td><?= htmlspecialchars($row['description']) ?></td>
    <td><?= htmlspecialchars($renewal) ?></td>
    <td><?= number_format($row['amount'], 2) ?></td>
    <td><?= $statusBadge ?></td>
    <td class="text-center" style="white-space:nowrap;">
        <a href="invoice.php?edit=<?= base64_encode($row['id']) ?>"><i class="fa fa-edit text-primary"></i></a>
        <a href="invoice_pdf.php?id=<?= base64_encode($row['id']) ?>" target="_blank" class="ms-2"><i class="fa fa-file-pdf text-danger"></i></a>
        <a href="process_payment.php?id=<?= base64_encode($row['id']) ?>" target="_blank" class="ms-2"><i class="fa fa-credit-card text-primary"></i></a>
        <a href="invoice_list.php?delete=<?= base64_encode($row['id']) ?>" onclick="return confirm('Delete this invoice?')" class="ms-2"><i class="fa fa-trash text-danger"></i></a>
    </td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
<script>
$(document).ready(function () {

    setTimeout(()=>{$('.alert').fadeOut('slow')},3000);

    $('.select2').select2({placeholder:"Select option",allowClear:true,width:'resolve'});

    var table = $('#invoiceTable').DataTable({
        paging:true,
        searching:true,
        ordering:true,
        pageLength:10,
        order:[],
        dom:'Bfrtip',
        buttons:[
            {
                extend:'collection',
                text:'<i class="fa fa-download"></i> Download',
                className:'btn btn-primary btn-sm',
                buttons:[
                    {extend:'copy'},
                    {extend:'csv'},
                    {extend:'excel'},
                    {extend:'pdf'},
                    {extend:'print'}
                ]
            }
        ],
        columnDefs:[
            {targets:[0,6],orderable:false}
        ],
        drawCallback:function(){
            $('#invoiceTable tbody tr').each(function(){
                $(this).find('td').each(function(i){
                    if(i===6) return; // skip actions
                    if($(this).find('a,span,i,button').length) return;
                    let v = $(this).text().trim();
                    if(v==='' || v.toLowerCase()==='null'){
                        $(this).html('<span class="text-muted">N/A</span>');
                    }
                });
            });
        }
    });

    table.on('order.dt search.dt draw.dt',function(){
        let start=table.page.info().start;
        table.column(0,{search:'applied',order:'applied'}).nodes()
        .each((cell,i)=>cell.innerHTML=start+i+1);
    }).draw();

    $.fn.dataTable.ext.search.push(function(settings,data){
        let min=$('#renewalFrom').val();
        let max=$('#renewalTo').val();
        let date=data[3]||'';
        if(!date) return true;
        let d=new Date(date).toISOString().split('T')[0];
        return ((min===''||d>=min)&&(max===''||d<=max));
    });

    $('#renewalFrom,#renewalTo').on('change',()=>table.draw());

    $('#domainFilter').on('change',function(){
        table.column(1).search(this.value).draw();
    });

    $('#statusFilter').on('change',function(){
        let val=this.value;
        $.fn.dataTable.ext.search.push(function(s,d){
            let t=$('<div>').html(d[5]).text().trim();
            if(val==='Both'||val==='') return true;
            return t===val;
        });
        table.draw();
        $.fn.dataTable.ext.search.pop();
    });

    $('#resetFilter').on('click',function(){
        $('.select2').val('').trigger('change');
        $('#renewalFrom,#renewalTo').val('');
        table.search('').columns().search('').draw();
    });

});
</script>


<?php include 'footer.php'; ?>
