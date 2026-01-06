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

// NOTE: Ensure 'config.php' and 'get_counts.php' exist and define $conn
include 'config.php';
include 'get_counts.php';

/* ---------- UTILITY FUNCTIONS (Required for URL encoding/decoding) ---------- */
function base_ecode64($data){
    return strtr(base64_encode($data), '+/=', '-_,');
}
function base_dcode64($code){
    return base64_decode(strtr($code, '-_,', '+/='));
}

/* -------------------------------------- */
/* ---------- FORM VARIABLE INIT AND DATA PREP ---------- */
/* -------------------------------------- */

$domain_identifier = null; 
$domain_name = $customer_name = $whatsapp_number = $facebook_username = '';
$password = $facebook_business_id = $fb_page_url = '';
$status = 'Active'; 
$meta_status = 'Not Verified'; 
$is_edit = false;
$existing_domains = [];

/* -------------------------------------- */
/* ---------- DELETE LOGIC (GET) ---------- */
/* -------------------------------------- */
if (isset($_GET['delete'])) {
    $domain_to_delete = base_dcode64($_GET['delete']);
    if ($domain_to_delete !== false) {
        $stmt = $conn->prepare("DELETE FROM wab_config WHERE domain_name=?");
        $stmt->bind_param("s", $domain_to_delete);
        $stmt->execute();
        $_SESSION['msg'] = "WAB Configuration Deleted Successfully for <b>$domain_to_delete</b>!";
        $stmt->close();
    }
    header("Location: wab_list.php"); // Redirect back to the list page
    exit;
}

/* -------------------------------------- */
/* ---------- EDIT FETCH LOGIC (GET) ---------- */
/* -------------------------------------- */
if (isset($_GET['edit_domain'])) {
    $domain_identifier = base_dcode64($_GET['edit_domain']);
    $stmt = $conn->prepare("SELECT * FROM wab_config WHERE domain_name=?");
    $stmt->bind_param("s", $domain_identifier);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $domain_name = $row['domain_name'];
        $customer_name = $row['customer_name'];
        $whatsapp_number = $row['whatsapp_number'];
        $facebook_username = $row['facebook_username'];
        $password = $row['password']; 
        $facebook_business_id = $row['facebook_business_id'];
        $fb_page_url = $row['fb_page_url'];
        $status = $row['status'];
        $meta_status = $row['meta_status'];
        $is_edit = true;
    }
}

/* -------------------------------------- */
/* ---------- SAVE/UPDATE LOGIC (POST) - FIXED WITH STRICT DUPLICATE CHECK ---------- */
/* -------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine the domain name to use for the database operation
    $domain_name = trim($_POST['domain_name']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $facebook_username = trim($_POST['facebook_username']);
    $password = $_POST['password']; 
    $facebook_business_id = trim($_POST['facebook_business_id']);
    $fb_page_url = trim($_POST['fb_page_url']);
    $status = $_POST['status'];
    $meta_status = $_POST['meta_status'];
    
    // 1. Fetch customer name based on domain
    $stmt = $conn->prepare("SELECT customer_name FROM domain_list WHERE domain_name=?");
    $stmt->bind_param("s", $domain_name);
    $stmt->execute();
    $d_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $customer_name = $d_row['customer_name'] ?? 'N/A';

    // 2. Validation 
    if ($domain_name == '' || $whatsapp_number == '' || $facebook_username == '') {
        $_SESSION['msg_error'] = "Domain, WhatsApp, and Facebook Username are required!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    // 3. Check if domain already exists in wab_config
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM wab_config WHERE domain_name=?");
    $check_stmt->bind_param("s", $domain_name);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];
    $check_stmt->close();

    // 4. *** CRITICAL: STRICT DUPLICATE CHECK ***
    // If we are NOT in edit mode AND the domain already exists, show an error and stop.
    if (!$is_edit && $exists) {
        $_SESSION['msg_error'] = "The Domain <b>$domain_name</b> is already configured! Please use the 'Edit' link from the list to modify it.";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    // 5. MAIN LOGIC BRANCH (If execution reaches here, it's a valid INSERT or UPDATE)
    if ($exists) {
        /* UPDATE */
        $stmt = $conn->prepare("
            UPDATE wab_config SET
                customer_name=?, whatsapp_number=?, facebook_username=?, 
                password=?, facebook_business_id=?, fb_page_url=?, status=?, meta_status=?
            WHERE domain_name=?
        ");
        $stmt->bind_param(
            "sssssssss",
            $customer_name, $whatsapp_number, $facebook_username, $password, 
            $facebook_business_id, $fb_page_url, $status, $meta_status, $domain_name
        );
        $_SESSION['msg'] = "WAB Configuration UPDATED for <b>$domain_name</b>!";
    } else {
        /* INSERT */
        $stmt = $conn->prepare("
            INSERT INTO wab_config
            (domain_name, customer_name, whatsapp_number, facebook_username, password, facebook_business_id, fb_page_url, status, meta_status)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "sssssssss",
            $domain_name, $customer_name, $whatsapp_number, $facebook_username, $password, 
            $facebook_business_id, $fb_page_url, $status, $meta_status
        );
        $_SESSION['msg'] = "WAB Configuration SAVED for <b>$domain_name</b>!";
    }

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['msg_error'] = "Database error: " . $conn->error;
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}


/* -------------------------------------- */
/* ---------- DATA FETCH FOR FORM DROPDOWNS ONLY ---------- */
/* -------------------------------------- */

// 1. Fetch domains for form dropdown (domain_list)
$res_domains = $conn->query("SELECT domain_name, customer_name FROM domain_list ORDER BY domain_name");
$domain_options = [];
while($d = $res_domains->fetch_assoc()){
    $domain_options[] = $d;
}

// 2. Fetch existing domains just to show 'Configured' status (wab_config)
$wab_domains_res = $conn->query("SELECT domain_name FROM wab_config");
while($wd = $wab_domains_res->fetch_assoc()){
    $existing_domains[$wd['domain_name']] = true;
}


/* -------------------------------------- */
/* ---------- HTML OUTPUT (FORM SECTION) - FIXED DROPDOWN DISPLAY ---------- */
/* -------------------------------------- */

include 'header.php';
include 'navbar.php';
?>
<style>
/* Styles for Select2 */
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
        <div class="col-lg-8">

            <div class="card shadow-lg border-0 rounded-4 mb-3">
                <div class="card-header bg-primary text-white text-center rounded-top-4">
                    <h4 class="mb-0"><i class="fab fa-facebook-messenger me-2"></i><?= $is_edit ? 'Edit' : 'Add' ?> WAB Configuration</h4>
                </div>

                <div class="card-body p-4">

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
<form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="fw-bold">Domain Name <span class="text-danger">*</span></label>
            <select name="domain_name" id="domain_name" class="form-select select2" required 
                <?= $is_edit ? 'disabled' : '' ?>>
                <option value="">Select Domain</option>
                <?php
                foreach($domain_options as $d){
                    $domain_val = htmlspecialchars($d['domain_name']);
                    $customer_val = htmlspecialchars($d['customer_name']);
                    $selected = ($d['domain_name'] == $domain_name) ? 'selected' : '';
                    
                    // Display both domain and customer name
                    $display_text = $domain_val . " (" . $customer_val . ")";

                    // Show 'Configured' status for user context
                    if (isset($existing_domains[$d['domain_name']])) {
                        $display_text .= " - Configured";
                    }

                    // Options are never explicitly disabled here
                    echo "<option value='{$domain_val}' {$selected}>{$display_text}</option>";
                }
                ?>
            </select>
            <?php if ($is_edit): ?>
                <input type="hidden" name="domain_name" value="<?= htmlspecialchars($domain_name) ?>">
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <label class="fw-bold">WhatsApp Number <span class="text-danger">*</span></label>
            <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($whatsapp_number) ?>" required>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <label class="fw-bold">FB Username <span class="text-danger">*</span></label>
            <input type="text" name="facebook_username" class="form-control" value="<?= htmlspecialchars($facebook_username) ?>" required>
        </div>

        <div class="col-md-6">
            <label class="fw-bold">FB Password <span class="text-danger">*</span></label>
            <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($password) ?>" required>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <label class="fw-bold">Facebook Business ID</label>
            <input type="text" name="facebook_business_id" class="form-control" value="<?= htmlspecialchars($facebook_business_id) ?>">
        </div>

        <div class="col-md-6">
            <label class="fw-bold">FB Page URL</label>
            <input type="url" name="fb_page_url" class="form-control" value="<?= htmlspecialchars($fb_page_url) ?>" placeholder="https://facebook.com/page-name">
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <label class="fw-bold">Status</label>
            <select name="status" class="form-select">
                <option value="Active" <?= ($status == 'Active') ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= ($status == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="fw-bold">Meta Status</label>
            <select name="meta_status" class="form-select">
                <option value="Verified" <?= ($meta_status == 'Verified') ? 'selected' : '' ?>>Verified</option>
                <option value="Not Verified" <?= ($meta_status == 'Not Verified') ? 'selected' : '' ?>>Not Verified</option>
            </select>
        </div>
    </div>

    <button class="btn btn-primary btn-lg w-100 mt-4">
        <i class="fas fa-save me-2"></i><?= $is_edit ? 'Update' : 'Save' ?> WAB Config
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
    // Initialize Select2 for form fields
    $('.select2').select2({
        placeholder: "Select",
        allowClear: true,
        width: '100%'
    });
    
    // Hide success/error messages after a delay
    setTimeout(function(){
        $(".alert").fadeOut('slow');
    }, 4000);
});
</script>