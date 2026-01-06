<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';

/* FETCH DOMAIN LIST */
$domainList = mysqli_query($conn,
    "SELECT id, domain_name, renewal_date FROM domain_list ORDER BY domain_name ASC"
);

/* EDIT MODE */
$id = "";
$domain_id = $renewal = $amount = $status = $desc = "";
$items_data = [];
$apply_gst = 0;
$cgst = 0;
$sgst = 0;

if (isset($_GET['edit'])) {
    $id = base64_decode($_GET['edit']);
    $res = mysqli_query($conn, "SELECT * FROM invoice WHERE id='$id'");
    if ($row = mysqli_fetch_assoc($res)) {
        $domain_id    = $row['domain_id'];
        $renewal      = $row['expiry_date'];
        $amount       = $row['amount'];
        $status       = $row['status'];
        $desc         = $row['description'];
        $items_data   = json_decode($row['items'], true);
        $apply_gst    = $row['gst'] ?? 0;
        $cgst         = $row['cgst'] ?? 0;
        $sgst         = $row['sgst'] ?? 0;
    }
}

/* SAVE */
if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $id         = $_POST['id'] ?? "";
    $domain_id  = $_POST['domain_id'];
    $renewal    = $_POST['renewal_date'];
    $amount     = $_POST['amount'];
    $status     = $_POST['status'];
    $desc       = $_POST['description'];
    $apply_gst  = isset($_POST['gst_check']) ? 1 : 0;
    $cgst       = $_POST['cgst'] ?? 0;
    $sgst       = $_POST['sgst'] ?? 0;

    /* ITEMS */
    $items = [];
    if (!empty($_POST['item_name'])) {
        foreach ($_POST['item_name'] as $i => $name) {
            $items[] = [
                "name"   => $name,
                "amount" => $_POST['item_amount'][$i]
            ];
        }
    }
    $items_json = json_encode($items);

    $r = mysqli_query($conn,
        "SELECT domain_name FROM domain_list WHERE id='$domain_id'"
    );
    $d = mysqli_fetch_assoc($r);
    $domain_name = $d['domain_name'];

    if ($id != "") {
        mysqli_query($conn,"UPDATE invoice SET
            domain_id='$domain_id',
            domain_name='$domain_name',
            items='$items_json',
            expiry_date='$renewal',
            amount='$amount',
            gst='$apply_gst',
            cgst='$cgst',
            sgst='$sgst',
            status='$status',
            description='$desc'
            WHERE id='$id'");
        $_SESSION['msg'] = "Invoice Updated Successfully!";
    } else {
        mysqli_query($conn,"INSERT INTO invoice
            (domain_id, domain_name, items, expiry_date, amount, gst, cgst, sgst, status, description)
            VALUES
            ('$domain_id','$domain_name','$items_json','$renewal','$amount','$apply_gst','$cgst','$sgst','$status','$desc')");
        $_SESSION['msg'] = "Invoice Saved Successfully!";
    }

    header("Location: invoice_list.php");
    exit;
}

include 'header.php';
include 'navbar.php';
?>
<style>
    .autofill-bg {
        background-color: #d1ecf1; /* light yellow to indicate auto-filled */
        cursor: not-allowed; /* shows the user it's not editable */
    }
</style>

<div class="content-area">
<div class="card shadow-sm" style="width:650px;margin:auto">
<div class="card-header bg-white">
<h4><?= $id ? "Edit Invoice" : "Create Invoice" ?></h4>
</div>

<div class="card-body">

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-success text-center">
<?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<form method="POST">

<?php if($id!=""): ?>
<input type="hidden" name="id" value="<?= $id ?>">
<?php endif; ?>

<!-- DOMAIN -->
<div class="mb-3">
<label class="form-label">Domain</label>
<select name="domain_id" id="domainSelect" class="form-select" required>
<option value="">Select Domain</option>
<?php while($d=mysqli_fetch_assoc($domainList)): ?>
<option value="<?= $d['id'] ?>"
        data-renewal="<?= $d['renewal_date'] ?>"
        <?= $d['id']==$domain_id?'selected':'' ?>>
<?= $d['domain_name'] ?>
</option>
<?php endwhile; ?>
</select>
</div>

<!-- RENEWAL DATE -->
<div class="mb-3">
    <label class="form-label">Renewal Date</label>
    <input type="text" name="renewal_date" id="renewDate"
           class="form-control autofill-bg" readonly required
           value="<?= $renewal ?>"
           title="This date is automatically set">
</div>




<!-- ITEMS -->
<div class="mb-3">
<label class="form-label">Invoice Items</label>
<div id="itemsContainer" class="border rounded p-2">

<?php if(!empty($items_data)): ?>
<?php foreach($items_data as $item): ?>
<div class="row g-2 item-row mb-2">
    <div class="col-6">
        <input type="text" name="item_name[]" class="form-control item-name"
               value="<?= $item['name'] ?>">
    </div>
    <div class="col-4">
        <input type="number" name="item_amount[]" class="form-control item-amount"
               value="<?= $item['amount'] ?>">
    </div>
    <div class="col-1">
        <button type="button" class="btn btn-danger btn-sm remove">×</button>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="row g-2 item-row mb-2">
    <div class="col-6">
        <input type="text" name="item_name[]" class="form-control item-name" value="Domain Renewal Charge">
    </div>
    <div class="col-4">
        <input type="number" name="item_amount[]" class="form-control item-amount" value="3000">
    </div>
</div>
<?php endif; ?>

<button type="button" class="btn btn-sm btn-outline-primary" id="addItem">+ Add Item</button>
</div>
</div>


<div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="gstCheck" name="gst_check" <?= $apply_gst?'checked':'' ?>>
    <label class="form-check-label" for="gstCheck">Apply GST (18%)</label>
</div>

<div id="gstFields" style="display: <?= $apply_gst ? 'block' : 'none' ?>;">
    <div class="mb-3">
        <label class="form-label">CGST (9%)</label>
        <input type="number" name="cgst" id="cgst" class="form-control" readonly value="<?= $cgst ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">SGST (9%)</label>
        <input type="number" name="sgst" id="sgst" class="form-control" readonly value="<?= $sgst ?>">
    </div>
</div>

<!-- TOTAL & GST -->
<div class="mb-3">
<label class="form-label">Total Amount</label>
<input type="number" name="amount" id="totalAmount" class="form-control" readonly value="<?= $amount ?: 3000 ?>">
</div>
<!-- STATUS -->
<div class="mb-3">
<label class="form-label">Payment Status</label>
<select name="status" class="form-select" required>
<option value="Paid" <?= $status=="Paid"?'selected':'' ?>>Paid</option>
<option value="Unpaid" <?= $status=="Unpaid"?'selected':'' ?>>Unpaid</option>
</select>
</div>

<!-- DESCRIPTION -->
<div class="mb-3">
<label class="form-label">Description</label>
<textarea name="description" class="form-control"><?= $desc ?></textarea>
</div>

<button class="btn btn-primary w-100"><?= $id ? "Update Invoice" : "Save Invoice" ?></button>

</form>
</div>
</div>
</div>

<script>



let maxItems = 5;

/* AUTO RENEWAL DATE */
document.getElementById('domainSelect').addEventListener('change', function(){
    document.getElementById('renewDate').value =
    this.options[this.selectedIndex].getAttribute('data-renewal');
    calculateTotal();
});

/* ADD ITEM */
document.getElementById('addItem').onclick = function(){
    if(document.querySelectorAll('.item-row').length >= maxItems){
        alert("Maximum 5 items only");
        return;
    }

    let row = document.createElement('div');
    row.className = "row g-2 item-row mb-2";
    row.innerHTML = `
        <div class="col-6">
            <input type="text" name="item_name[]" class="form-control item-name">
        </div>
        <div class="col-4">
            <input type="number" name="item_amount[]" class="form-control item-amount" value="0">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-danger btn-sm remove">×</button>
        </div>
    `;
    document.getElementById('addItem').before(row);
    calculateTotal();
};

/* REMOVE ITEM */
document.addEventListener('click', function(e){
    if(e.target.classList.contains('remove')){
        e.target.closest('.item-row').remove();
        calculateTotal();
    }
});

/* CALCULATE TOTAL & GST */
document.addEventListener('input', function(e){
    if(e.target.classList.contains('item-amount')){
        calculateTotal();
    }
});

document.getElementById('gstCheck').addEventListener('change', function(){
    document.getElementById('gstFields').style.display = this.checked ? 'block' : 'none';
    calculateTotal();
});

function calculateTotal(){
    let total = 0;
    document.querySelectorAll('.item-amount').forEach(el=>{
        total += parseFloat(el.value) || 0;
    });

    let cgst = 0, sgst = 0;
    const gstChecked = document.getElementById('gstCheck').checked;

    if(gstChecked){
        cgst = total * 0.09;
        sgst = total * 0.09;
        total += cgst + sgst;
    }

    document.getElementById('totalAmount').value = total.toFixed(2);
    document.getElementById('cgst').value = cgst.toFixed(2);
    document.getElementById('sgst').value = sgst.toFixed(2);
}

// initial calculation on load
calculateTotal();
</script>

<?php include 'footer.php'; ?>
