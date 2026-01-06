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
include 'header.php';
include 'navbar.php';

$domain_name = $launch_date = $last_renewal_date = $ssl_expiry_date = null;
$customer_name = null;

// --- IMPORTANT: Removed $_SESSION['last_saved_domain'] for full form reset ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $domain_name = trim($_POST['domain_name']);

    if ($domain_name == '') {
        $_SESSION['msg_error'] = "Domain is required!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    /* 1. Fetch domain details securely */
    $stmt = $conn->prepare("
        SELECT launch_date, last_renewal_date, customer_name
        FROM domain_list
        WHERE domain_name=?
    ");
    $stmt->bind_param("s", $domain_name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $_SESSION['msg_error'] = "Invalid domain selected!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    $launch_date = $row['launch_date'] ?: null;
    $last_renewal_date = $row['last_renewal_date'] ?: null;
    $customer_name = $row['customer_name'] ?: null;

    /* 2. Calculate Expiry Date (60 days) */
    $base_date = $last_renewal_date ?: $launch_date;
    $ssl_expiry_date = $base_date
        ? date('Y-m-d', strtotime($base_date . ' +60 days'))
        : null;

    /* 3. Check if SSL already exists */
    $check = $conn->prepare("SELECT id FROM ssl_list WHERE domain_name=?");
    $check->bind_param("s", $domain_name);
    $check->execute();
    $exists = $check->get_result()->num_rows;
    $check->close();

    if ($exists) {
        /* UPDATE */
        $stmt = $conn->prepare("
            UPDATE ssl_list SET
                customer_name=?,
                launch_date=?,
                last_renewal_date=?,
                ssl_expiry_date=?
            WHERE domain_name=?
        ");
        $stmt->bind_param(
            "sssss",
            $customer_name,
            $launch_date,
            $last_renewal_date,
            $ssl_expiry_date,
            $domain_name
        );
        $_SESSION['msg'] = "SSL details UPDATED";
    } else {
        /* INSERT */
        $stmt = $conn->prepare("
            INSERT INTO ssl_list
            (domain_name, customer_name, launch_date, last_renewal_date, ssl_expiry_date)
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param(
            "sssss",
            $domain_name,
            $customer_name,
            $launch_date,
            $last_renewal_date,
            $ssl_expiry_date
        );
        $_SESSION['msg'] = "SSL details SAVED ";
    }

    $stmt->execute();
    $stmt->close();

    // Redirect for full form reset
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
?>

<style>
/* Existing CSS Styles for Select2 */
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
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #86b7fe !important; box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25) !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
    border-radius: 0.375rem; border: 1px solid #ced4da;
    padding: 6px 12px; font-size: 14px;
}
</style>

<div class="content-area">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-primary text-white text-center rounded-top-4">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Add SSL Details</h4>
                </div>

                <div class="card-body p-4">

                    <?php if(isset($_SESSION['msg'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
                            <button class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['msg_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?>
                            <button class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="sslForm">

                        <div class="mb-4">
                            <label class="fw-bold">Domain Name <span class="text-danger">*</span></label>
                            <select name="domain_name" id="domain_name" class="form-select select2" required>
                                <option value="">Select Domain</option>
                                <?php
                                $res = $conn->query("
                                    SELECT domain_name, customer_name, launch_date, last_renewal_date
                                    FROM domain_list
                                    ORDER BY domain_name
                                ");
                                while($d = $res->fetch_assoc()){
                                    // No pre-selection logic needed here. All options are unselected.
                                    echo "<option
                                        value='{$d['domain_name']}'
                                        data-customer='{$d['customer_name']}'
                                        data-launch='{$d['launch_date']}'
                                        data-renewal='{$d['last_renewal_date']}'>
                                        {$d['domain_name']} ({$d['customer_name']})
                                    </option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="fw-bold">Launch Date</label>
                                <input type="text" id="launch_date" class="form-control bg-light" readonly placeholder="DD-MM-YYYY">
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold">Last Renewal</label>
                                <input type="text" id="last_renewal_date" class="form-control bg-light" readonly placeholder="DD-MM-YYYY">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-success">SSL Expiry (60 Days)</label>
                            <input type="text" id="ssl_expiry_date" class="form-control fw-bold text-success bg-light" readonly placeholder="DD-MM-YYYY">
                        </div>

                        <button class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-save me-2"></i>Save SSL
                        </button>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</div>

<?php include 'footer.php'; ?>


<script>
$(function(){

    // 1. Initialize Select2
    $('.select2').select2({
        placeholder: "Select Domain",
        allowClear: true,
        width: '100%'
    });

    // 2. Date Formatting Utility (YYYY-MM-DD to DD-MM-YYYY)
    function dmy(date){
        if(!date || date === '0000-00-00') return '';
        let d = new Date(date + 'T00:00:00');
        return ('0'+d.getDate()).slice(-2)+'-'+
               ('0'+(d.getMonth()+1)).slice(-2)+'-'+
               d.getFullYear();
    }

    // 3. Calculation Logic
    function calcSSL(){
        let base = $('#last_renewal_date').val() || $('#launch_date').val();
        if(!base){
            $('#ssl_expiry_date').val('');
            return;
        }

        let p = base.split('-');
        let d = new Date(p[2], p[1]-1, p[0]);

        d.setDate(d.getDate()+60);

        $('#ssl_expiry_date').val(
            ('0'+d.getDate()).slice(-2)+'-'+
            ('0'+(d.getMonth()+1)).slice(-2)+'-'+
            d.getFullYear()
        );
    }

    // 4. Change Handler for Domain Selection
    $('#domain_name').on('change', function(){
        let selectedOption = $(this).find(':selected');

        if (selectedOption.val()) {
            // A domain is selected, populate and calculate
            $('#launch_date').val(dmy(selectedOption.data('launch')));
            $('#last_renewal_date').val(dmy(selectedOption.data('renewal')));
            calcSSL();
        } else {
            // No domain selected (Cleared), reset all fields
            $('#launch_date').val('');
            $('#last_renewal_date').val('');
            $('#ssl_expiry_date').val('');
        }
    });

    // 5. Initial Load Check & Form Reset (FIX for "Empty Content Area")
    // This is the primary fix for the empty appearance on load/after redirect.
    // It programmatically clears the Select2 selection and its associated fields.
    function resetFormFields() {
        // Clear the Select2 selection visually and internally
        $('#domain_name').val(null).trigger('change'); 
        
        // Explicitly clear the readonly fields (trigger('change') should handle this, 
        // but explicit clearing ensures a blank start)
        $('#launch_date').val('');
        $('#last_renewal_date').val('');
        $('#ssl_expiry_date').val('');
    }

    // Call the reset function once the document and Select2 are fully initialized.
    resetFormFields();

});
</script>