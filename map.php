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

/* BASE64 - URL-safe Base64 Encoding/Decoding */
function base_ecode64($id){ return strtr(base64_encode($id), '+/=', '-_,'); }
function base_dcode64($c){ return base64_decode(strtr($c, '-_,', '+/=')); }

/* VARIABLES */
$edit_id=null; $original_edit_id_encoded="";
$domain_id=$ownership=$map_keyword=$action_role="";
$map_description=$status=$status_description="";
$created_by=$business_profile_id=$map_case_id=$map_work_location="";
$map_creation_year="";
$login_type=$company_mail=$customer_mail=$customer_password=$customer_access="";

/* EDIT: Fetch existing data if ID is present in URL */
if(isset($_GET['id'])){
    $original_edit_id_encoded=$_GET['id'];
    $edit_id=base_dcode64($_GET['id']);

    // Use prepared statements for fetching data
    $stmt=$conn->prepare("SELECT * FROM map_configuration WHERE id=?");
    $stmt->bind_param("i",$edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $map = $result->fetch_assoc();
    $stmt->close();

    if($map){
        extract($map);
        
        // *****************************************************************
        // ⚠️ SECURITY WARNING: The original database password ($customer_password) 
        // will now be in plain text if it was not hashed previously.
        // It is extracted here, but cleared below so the input field is empty 
        // on load. To DISPLAY the password, you would need to set the input 
        // value to the extracted $customer_password.
        // *****************************************************************
        
        // If you want to view the plain text password:
        // $customer_password is already extracted if present in $map
        
        // Clear password field on edit for security/UX (it's kept empty in the input field)
        $customer_password = ''; 
    } else {
        // Handle invalid ID case if necessary
        $edit_id = null;
    }
}

/* SAVE: Handle form submission (INSERT or UPDATE) */
if(isset($_POST['save_map'])){
    $is_update = !empty($_POST['current_edit_id']);

    // 1. Data Collection
    $domain_id = $_POST['domain_id'];
    $login_type = $_POST['login_type'];
    // Use NULL coalescing operator for potential undefined index if field is hidden/not submitted
    $company_mail = $_POST['company_mail'] ?? '';
    $customer_mail = $_POST['customer_mail'] ?? '';
    $input_password = $_POST['customer_password'] ?? ''; // Raw password input
    $customer_access = $_POST['customer_access'] ?? '';
    $ownership = $_POST['ownership'];
    $map_keyword = $_POST['map_keyword'];
    $action_role = $_POST['action_role'];
    $map_description = $_POST['map_description'];
    $map_creation_year = $_POST['map_creation_year'];
    $status = $_POST['status'];
    $status_description = $_POST['status_description'];
    $created_by = $_POST['created_by'];
    $business_profile_id = $_POST['business_profile_id'];
    $map_case_id = $_POST['map_case_id'];
    $map_work_location = $_POST['map_work_location'];

    // 2. Storage Logic (NON-ENCRYPTED)
    // 🛑 WARNING: $input_password is now the plain text password.
    
    // 3. Execution
    if($is_update){
        $id = base_dcode64($_POST['current_edit_id']);
        
        if(!empty($input_password)) {
            // Update including the new UNHASHED password
            $stmt = $conn->prepare("
                UPDATE map_configuration SET
                    domain_id=?, login_type=?, company_mail=?, customer_mail=?, customer_password=?, customer_access=?,
                    ownership=?, map_keyword=?, action_role=?, map_description=?, map_creation_year=?,
                    status=?, status_description=?, created_by=?,
                    business_profile_id=?, map_case_id=?, map_work_location=?
                WHERE id=?
            ");
            $stmt->bind_param(
                "issssssssssssssssi",
                $domain_id,$login_type,$company_mail,$customer_mail,$input_password,$customer_access, // Use $input_password
                $ownership,$map_keyword,$action_role,$map_description,$map_creation_year,
                $status,$status_description,$created_by,
                $business_profile_id,$map_case_id,$map_work_location,$id
            );
        } else {
            // Update without changing the password (if input was empty)
            $stmt = $conn->prepare("
                UPDATE map_configuration SET
                    domain_id=?, login_type=?, company_mail=?, customer_mail=?, customer_access=?,
                    ownership=?, map_keyword=?, action_role=?, map_description=?, map_creation_year=?,
                    status=?, status_description=?, created_by=?,
                    business_profile_id=?, map_case_id=?, map_work_location=?
                WHERE id=?
            ");
            $stmt->bind_param(
                "isssssssssssssssi",
                $domain_id,$login_type,$company_mail,$customer_mail,$customer_access,
                $ownership,$map_keyword,$action_role,$map_description,$map_creation_year,
                $status,$status_description,$created_by,
                $business_profile_id,$map_case_id,$map_work_location,$id
            );
        }
        
        $stmt->execute();
        $stmt->close();
        $_SESSION['msg'] = "MAP Configuration Updated Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: map_list.php");
        exit;
    } else {
        // INSERT: Enforce password requirement based on login_type
        
        // Determine if customer credentials are required for the selected login_type
        $customer_login_required = ($login_type == 'Customer' || $login_type == 'Both');
        
        // Check if password or email is missing when customer login is required
        if ($customer_login_required && empty($input_password)) {
             $_SESSION['msg'] = "Error: Customer Mail and Password are required for new configurations when Login Type is 'Customer' or 'Both'.";
             $_SESSION['msg_type'] = "danger";
             // Fall through to display form with error
        } else {
            // INSERT: Execute the insert query, saving the plain text password
            $stmt = $conn->prepare("
                INSERT INTO map_configuration (
                    domain_id, login_type, company_mail, customer_mail, customer_password, customer_access,
                    ownership, map_keyword, action_role, map_description, map_creation_year,
                    status, status_description, created_by,
                    business_profile_id, map_case_id, map_work_location, created_at
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
            ");
            $stmt->bind_param(
                "issssssssssssssss",
                $domain_id,$login_type,$company_mail,$customer_mail,$input_password,$customer_access, // Use $input_password
                $ownership,$map_keyword,$action_role,$map_description,$map_creation_year,
                $status,$status_description,$created_by,
                $business_profile_id,$map_case_id,$map_work_location
            );
            $stmt->execute();
            $stmt->close();
            $_SESSION['msg'] = "MAP Configuration Added Successfully!";
            $_SESSION['msg_type'] = "success";
            header("Location: map_list.php");
            exit;
        }
    }
}
?>

<style>
/* --- ENHANCED UI STYLES --- */
body{background:#f0f2f5;}
.form-container{
    background:#fff;max-width:920px;margin:40px auto;
    padding:32px;border-radius:12px;
    box-shadow:0 10px 30px rgba(0 0 0 /.08)
}
.form-container h2{font-weight:600;color:#0d6efd;margin-bottom:28px}

/* Custom input styling */
.form-control-custom, .form-select-custom, .select2-container--default .select2-selection--single {
    height: 48px !important; 
    font-size: 16px; 
    padding: 10px 14px; 
    border-radius: 8px;
    border: 1px solid #ced4da;
    background-color: #f8f9fa;
}

textarea.form-control-custom{height:110px;resize:none;padding:14px}

/* Label styling (Larger and Bolder) */
.form-label {
    font-weight: 600; 
    font-size: 16px; 
    color: #343a40; 
    margin-bottom: 8px; 
}
.required-label::after {content: " *"; color: red;}

/* Select2 Customization */
.select2-container .select2-selection--single {
    height: 48px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px; 
}

.btn-success{height:52px;font-size:16px;border-radius:10px;font-weight:600;}
</style>


<div class="content-area">
<div class="form-container">
<form method="POST">
<h2 class="text-center"><?= $edit_id ? 'Edit MAP Configuration' : 'Add MAP Configuration' ?></h2>

<?php $is_insert = !$edit_id; // Define is_insert for client-side use ?>
<?php if($edit_id): ?>
<input type="hidden" name="current_edit_id" id="current_edit_id" value="<?= $original_edit_id_encoded ?>">
<?php endif; ?>

<?php 
// Display Session Message (if any)
if (isset($_SESSION['msg'])) {
    echo '<div class="alert alert-' . ($_SESSION['msg_type'] ?? 'success') . '">' . $_SESSION['msg'] . '</div>';
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}
?>

<div class="row g-4">
    <div class="col-md-6">
        
        <div class="mb-3">
            <label for="domain_id" class="form-label required-label">Customer / Domain / Business</label>
            <select name="domain_id" id="domain_id" class="form-select form-select-custom select2" required>
            <option value="">Select Customer / Domain / Business</option>
            <?php
            // NOTE: Using mysqli_query/fetch_assoc here, as prepared statements for dynamic dropdowns can be complex for simple SELECT ALL queries.
            $r = mysqli_query($conn,"SELECT id, domain_name, business_name, customer_name FROM domain_list ORDER BY domain_name ASC");
            while ($d = mysqli_fetch_assoc($r)) {
                $sel = ($domain_id == $d['id']) ? 'selected' : '';
                echo "<option value='{$d['id']}' $sel>"
                    . htmlspecialchars($d['customer_name']) . " - " 
                    . htmlspecialchars($d['domain_name']) . " - " 
                    . htmlspecialchars($d['business_name'])
                    . "</option>";
            }
            ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="login_type" class="form-label required-label">Login Type</label>
            <select name="login_type" id="login_type" class="form-select form-select-custom" required>
                <option value="">Select Login Type</option>
                <option value="Company" <?= $login_type=="Company"?"selected":"" ?>>Company</option>
                <option value="Customer" <?= $login_type=="Customer"?"selected":"" ?>>Customer</option>
                <option value="Both" <?= $login_type=="Both"?"selected":"" ?>>Both</option>
            </select>
        </div>

        <div id="company_fields" style="display:none;" class="mb-3">
            <label for="company_mail" class="form-label">Company Mail</label>
            <input type="email" name="company_mail" id="company_mail" class="form-control form-control-custom" 
            placeholder="Company Mail" value="<?= htmlspecialchars($company_mail) ?>">
        </div>

        <div id="customer_fields" style="display:none;" class="mb-3">
            <label class="form-label">Customer Credentials</label>
            <input type="email" name="customer_mail" id="customer_mail" class="form-control form-control-custom mb-2" 
            placeholder="Customer Mail" value="<?= htmlspecialchars($customer_mail) ?>">
            
            <label for="customer_password" class="form-label" id="password_label">
                Customer Password 
                <?php if ($is_insert) : ?>
                    <?php endif; ?>
            </label>
            <input type="text" name="customer_password" id="customer_password" class="form-control form-control-custom mb-2" 
            placeholder="Customer Password (<?= $edit_id ? 'Leave blank to keep existing' : 'Required for new record' ?>)" value="">
            
            <select name="customer_access" class="form-select form-select-custom">
                <option value="">Select Customer Access</option>
                <option value="Access" <?= $customer_access=="Access"?"selected":"" ?>>Access</option>
                <option value="No Access" <?= $customer_access=="No Access"?"selected":"" ?>>No Access</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label for="action_role" class="form-label">Action Role</label>
            <select name="action_role" id="action_role" class="form-select form-select-custom">
                <option value="">Select Action Role</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="ownership" class="form-label required-label">Ownership Status</label>
            <select name="ownership" id="ownership" class="form-select form-select-custom" required>
                <option value="">Select Ownership</option>
                <option value="Verified" <?= $ownership=="Verified"?"selected":"" ?>>Verified</option>
                <option value="Not Verified" <?= $ownership=="Not Verified"?"selected":"" ?>>Not Verified</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="map_keyword" class="form-label">Main Keyword</label>
            <textarea name="map_keyword" id="map_keyword" class="form-control form-control-custom"
            placeholder="Main Keyword"><?= htmlspecialchars($map_keyword) ?></textarea>
        </div>
        
        <div class="mb-3">
            <label for="status" class="form-label required-label">MAP Status</label>
            <select name="status" id="status" class="form-select form-select-custom" required>
                <option value="">Select MAP Status</option>
                <option value="Progress" <?= $status=="Progress"?"selected":"" ?>>Progress</option>
                <option value="Pending" <?= $status=="Pending"?"selected":"" ?>>Pending</option>
                <option value="No" <?= $status=="No"?"selected":"" ?>>No</option>
                <option value="Verified" <?= $status=="Verified"?"selected":"" ?>>Verified</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label for="status_description" class="form-label">Status Description</label>
            <textarea name="status_description" id="status_description" class="form-control form-control-custom"
            placeholder="Status Description"><?= htmlspecialchars($status_description) ?></textarea>
        </div>

    </div>

    <div class="col-md-6">

        <div class="mb-3">
            <label for="map_description" class="form-label">MAP Description</label>
            <textarea name="map_description" id="map_description" class="form-control form-control-custom"
            placeholder="MAP Description"><?= htmlspecialchars($map_description) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="map_creation_year" class="form-label">MAP Creation Year</label>
            <input type="number" name="map_creation_year" id="map_creation_year" class="form-control form-control-custom" 
            placeholder="MAP Creation Year" value="<?= htmlspecialchars($map_creation_year) ?>">
        </div>

        <div class="mb-3">
            <label for="created_by" class="form-label required-label">Created By</label>
            <select name="created_by" id="created_by" class="form-select form-select-custom" required>
                <option value="">Created By</option>
                <option value="Company" <?= $created_by=="Company"?"selected":"" ?>>Company</option>
                <option value="Customer" <?= $created_by=="Customer"?"selected":"" ?>>Customer</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="business_profile_id" class="form-label">Business Profile ID</label>
            <input type="text" name="business_profile_id" id="business_profile_id" class="form-control form-control-custom" 
            placeholder="Google Business Profile ID" value="<?= htmlspecialchars($business_profile_id) ?>">
        </div>
        
        <div class="mb-3">
            <label for="map_case_id" class="form-label">MAP Case ID</label>
            <input type="text" name="map_case_id" id="map_case_id" class="form-control form-control-custom" 
            placeholder="Internal/Client Case ID" value="<?= htmlspecialchars($map_case_id) ?>">
        </div>
        
        <div class="mb-3">
            <label for="map_work_location" class="form-label">MAP Work Location</label>
            <input type="text" name="map_work_location" id="map_work_location" class="form-control form-control-custom" 
            placeholder="Local or Drive path" value="<?= htmlspecialchars($map_work_location) ?>">
        </div>
        
        </div>
</div>

<button type="submit" name="save_map" class="btn btn-primary w-100 mt-0">
<?= $edit_id ? "Update MAP Configuration" : "Save MAP Configuration" ?>
</button>

</form>
</div>
</div>


<script>
$(function(){
    const isInsert = !$('#current_edit_id').length; // Determine if it's an insert or update

    // Initialize Select2 with new styles
    $('#domain_id').select2({
        width:'100%',
        placeholder: "Select Customer - Domain - Business",
        allowClear: true
    });

    function toggle() {
        let t = $('#login_type').val();
        let companyFieldsDiv = $('#company_fields');
        let customerFieldsDiv = $('#customer_fields');
        
        // Hide all fields initially
        companyFieldsDiv.hide();
        customerFieldsDiv.hide();

        // Target customer mail/password fields and label
        const customerMailField = $('#customer_mail');
        const customerPasswordField = $('#customer_password');
        const passwordLabel = $('#password_label');

        // Reset required attributes for customer fields
        customerMailField.removeAttr('required').prev('label').removeClass('required-label');
        customerPasswordField.removeAttr('required');
        passwordLabel.removeClass('required-label').text('Customer Password');
        
        let customerLoginRequired = false;
        
        if (t==='Company') {
            companyFieldsDiv.show();
        }
        if (t==='Customer') {
            customerFieldsDiv.show();
            customerLoginRequired = true;
        }
        if (t==='Both') {
            companyFieldsDiv.show();
            customerFieldsDiv.show();
            customerLoginRequired = true;
        }

        // Conditional requirement logic for customer credentials (only on INSERT)
        if (isInsert && customerLoginRequired) {
            customerMailField.attr('required', true);
            customerPasswordField.attr('required', true);
            
            // Add required visual cues for new records
            customerMailField.prev('label').addClass('required-label');
            passwordLabel.addClass('required-label');
        }

        // Logic for Action Role
        let role = $('#action_role');
        let selected = "<?= $action_role ?>";
        role.html('<option value="">Select Action Role</option>');
        
        if(t==='Company'){ 
            role.append('<option value="Owner">Owner</option>'); 
            role.append('<option value="Manager">Manager</option>'); 
        }
        if(t==='Customer'){ 
            role.append('<option value="Primary">Primary</option>'); 
        }
        if(t==='Both'){ 
            role.append('<option value="Owner">Owner</option>'); 
            role.append('<option value="Manager">Manager</option>'); 
            role.append('<option value="Primary">Primary</option>'); 
        }
        
        // Re-select the saved value after options are rebuilt
        if(selected) role.val(selected);
    }
    
    // Attach change listener and trigger on load
    $('#login_type').on('change', toggle);
    toggle(); 
});
</script>

<?php include 'footer.php'; ob_end_flush(); ?>