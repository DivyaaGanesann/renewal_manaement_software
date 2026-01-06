<?php
ob_start();
session_start();

/* ================= SECURITY ================= */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/* ================= AUTH ================= */
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

/* ================= INCLUDES ================= */
include 'config.php';
include 'get_counts.php';
include 'header.php';
include 'navbar.php';

/* ================= FETCH DOMAINS ================= */
$domains = [];
$res = $conn->query("
    SELECT id, domain_name, business_name, customer_name, phone, renewal_date
    FROM domain_list
    ORDER BY domain_name ASC
");
while ($row = $res->fetch_assoc()) {
    $domains[] = $row;
}

/* ================= FETCH STAFF ================= */
$staffList = [];
$res2 = $conn->query("SELECT id, name FROM staff WHERE status=1 ORDER BY name ASC");
while ($s = $res2->fetch_assoc()) {
    $staffList[] = [
        'id' => $s['id'],
        'name' => $s['name']
    ];
}

/* ================= POST SAVE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $domain_id = (int)$_POST['domain_id'];
    $final_date = $_POST['final_renewal_date'];

    $intervals = [];
    for ($i = 1; $i <= 3; $i++) {
        $intervals[$i] = [
            'date'  => $_POST["interval_{$i}_date"] ?? null,
            'desc'  => $_POST["interval_{$i}_desc"] ?? '',
            'staff' => $_POST["interval_{$i}_staff"] ?? null
        ];
    }

    $sql = "
        INSERT INTO domain_renewal_intervals (
            domain_id,
            first_interval_date, first_interval_desc, first_interval_staff,
            second_interval_date, second_interval_desc, second_interval_staff,
            third_interval_date, third_interval_desc, third_interval_staff,
            final_payment_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            first_interval_date = VALUES(first_interval_date),
            first_interval_desc = VALUES(first_interval_desc),
            first_interval_staff = VALUES(first_interval_staff),
            second_interval_date = VALUES(second_interval_date),
            second_interval_desc = VALUES(second_interval_desc),
            second_interval_staff = VALUES(second_interval_staff),
            third_interval_date = VALUES(third_interval_date),
            third_interval_desc = VALUES(third_interval_desc),
            third_interval_staff = VALUES(third_interval_staff),
            final_payment_date = VALUES(final_payment_date)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssssssss",
        $domain_id,
        $intervals[1]['date'], $intervals[1]['desc'], $intervals[1]['staff'],
        $intervals[2]['date'], $intervals[2]['desc'], $intervals[2]['staff'],
        $intervals[3]['date'], $intervals[3]['desc'], $intervals[3]['staff'],
        $final_date
    );
    $stmt->execute();
    $stmt->close();

    $stmt2 = $conn->prepare("UPDATE domain_list SET renewal_date=? WHERE id=?");
    $stmt2->bind_param("si", $final_date, $domain_id);
    $stmt2->execute();
    $stmt2->close();

    $_SESSION['msg'] = "Renewal details saved successfully!";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>

<div class="content-area">
<div class="container mt-4">

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-success"><?= $_SESSION['msg']; ?></div>
<?php unset($_SESSION['msg']); endif; ?>

<form method="POST" class="card shadow p-4">
<h4 class="text-center text-primary mb-4">Domain Renewal Form</h4>

<!-- DOMAIN -->
<div class="mb-3">
    <label class="form-label">Domain / Business</label>
    <select name="domain_id" id="domain_id" class="form-select select2" required>
        <option value="">Select Domain</option>
        <?php foreach ($domains as $d): ?>
        <option value="<?= $d['id'] ?>"
                data-renewal="<?= $d['renewal_date'] ?>">
            <?= htmlspecialchars($d['domain_name'].' / '.$d['business_name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- ACTUAL RENEWAL DATE -->
<div class="mb-3">
    <label class="form-label">Actual Renewal Date</label>
    <input type="date" id="actual_renewal_date"
           class="form-control bg-warning" readonly>
</div>

<!-- INTERVAL TABLE -->
<div class="mb-3">
    <label class="form-label">Renewal Intervals</label>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Interval</th>
                <th>Date</th>
                <th>Description</th>
                <th>Staff</th>
            </tr>
        </thead>
        <tbody id="interval_table"></tbody>
    </table>
</div>

<!-- FINAL DATE -->
<div class="mb-3">
    <label class="form-label">Final Renewal Payment Date</label>
    <input type="date" name="final_renewal_date"
           id="final_renewal_date"
           class="form-control bg-warning" readonly required>
</div>

<button class="btn btn-success w-100">Save Renewal</button>
</form>
</div>
</div>


<script>
$(document).ready(function(){

$('.select2').select2({
    placeholder: "Search domain",
    allowClear: true,
    width: "100%"
});

const staffList = <?php echo json_encode($staffList); ?>;

$('#domain_id').on('change', function(){

    $('#interval_table').html('');
    $('#final_renewal_date').val('');

    const opt = this.options[this.selectedIndex];
    if(!opt.value) return;

    let startDate = new Date(opt.dataset.renewal);
    if(isNaN(startDate)) return alert("Invalid renewal date");

    $('#actual_renewal_date').val(startDate.toISOString().slice(0,10));

    $.getJSON('fetch_domain_intervals.php', {domain_id: opt.value}, function(saved){

        const months = [4,8,10];
        const labels = ['Interval 1','Interval 2','Interval 3'];

        months.forEach((m,i)=>{
            let d = new Date(startDate);
            d.setMonth(d.getMonth() + m);

            let dateKey  = ['first','second','third'][i] + '_interval_date';
            let descKey  = ['first','second','third'][i] + '_interval_desc';
            let staffKey = ['first','second','third'][i] + '_interval_staff';

            let dateVal  = saved[dateKey] && saved[dateKey] !== '0000-00-00' ? saved[dateKey] : '';
            let descVal  = saved[descKey] ?? '';
            let staffVal = saved[staffKey] ?? '';

            // Only lock date if saved, description and staff are editable
            let lockDate  = dateVal !== '';
            let inputDate = dateVal || d.toISOString().slice(0,10);

            let staffOptions = staffList.map(s =>
                `<option value="${s.id}" ${staffVal==s.id?'selected':''}>${s.name}</option>`
            ).join('');

            $('#interval_table').append(`
            <tr>
                <td>${labels[i]}</td>
                <td>
                    <input type="date"
                        name="interval_${i+1}_date"
                        class="form-control"
                        value="${inputDate}"
                        ${lockDate?'readonly':''}>
                </td>
                <td>
                    <textarea name="interval_${i+1}_desc"
                        class="form-control">${descVal}</textarea>
                </td>
                <td>
                    <select name="interval_${i+1}_staff"
                        class="form-select">
                        <option value="">Select Staff</option>
                        ${staffOptions}
                    </select>
                </td>
            </tr>
            `);
        });

        // Final date
        if(saved.final_payment_date && saved.final_payment_date !== '0000-00-00'){
            $('#final_renewal_date').val(saved.final_payment_date);
        }else{
            let f = new Date(startDate);
            f.setMonth(f.getMonth()+12);
            $('#final_renewal_date').val(f.toISOString().slice(0,10));
        }
    });
});
});
</script>

<?php include 'footer.php'; ob_end_flush(); ?>
