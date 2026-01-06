<?php
ob_start();
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['admin'])) {
    header("Location:index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';

/* ---------------- VARIABLES ---------------- */
$id = 0;
$domain_name = $launch_date = $last_renewal_date = "";
$customer_name = $phone = $whatsapp = "";
$business_name = $address = $email = "";
$renewal_date = "";
$purchase_name = "";
$renewal_cycle =null;
$status = 0;
$product = "";
$category_id = 0;
$category_name = "";
$country = $state = $city = "";
$description = "";

/* ---------------- LOAD JSON ---------------- */
$jsonPath = __DIR__ . "/countries+states+cities.json";
$geoData = json_decode(file_get_contents($jsonPath), true);

/* ---------------- SAVE / UPDATE ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['csv_file'])) {

    $id = intval($_POST['id'] ?? 0);
$domain_name = trim($_POST['domain_name']);
if ($domain_name === '' || strtoupper($domain_name) === 'NULL') {
    $domain_name = null; // save as NULL in DB
}

    $launch_date = $_POST['launch_date'] ? date('Y-m-d', strtotime($_POST['launch_date'])) : null;
    $last_renewal_date = $_POST['last_renewal_date'] ? date('Y-m-d', strtotime($_POST['last_renewal_date'])) : null;
    $customer_name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone']);
    $whatsapp = trim($_POST['whatsapp']);
    $business_name = trim($_POST['business']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $purchase_name = trim($_POST['purchased_by']);
    $renewal_cycle = intval($_POST['renewal_cycle']);
    $renewal_date = $_POST['renewal_date'] ? date('Y-m-d', strtotime($_POST['renewal_date'])) : null;
    $product = isset($_POST['product']) ? implode(',', $_POST['product']) : '';

    $category_id = intval($_POST['category_id']);
    $country = trim($_POST['country']);
    $state = trim($_POST['state']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);

    /* STATUS AUTO CALCULATION - PHP SIDE */
    // Note: The next renewal date calculation in PHP must also rely solely on $last_renewal_date
    $base_date_for_status = $last_renewal_date ?? $launch_date;
    
    if ($last_renewal_date && $renewal_cycle) {
        // Calculate $renewal_date based on $last_renewal_date and $renewal_cycle (to ensure consistent DB save)
        $date_obj = new DateTime($last_renewal_date);
        $date_obj->modify("+{$renewal_cycle} years");
        $calculated_renewal_date = $date_obj->format('Y-m-d');
        $renewal_date = $calculated_renewal_date;

        $status = (strtotime($renewal_date) >= strtotime(date('Y-m-d'))) ? 1 : 0;

    } else {
        // If last renewal date is missing, status is Deactive (0)
        $status = 0;
        $renewal_date = null; // Ensure renewal_date is null if calculation base is missing
    }


    /* CATEGORY NAME */
    $category_name = '';
    $cat = mysqli_query($conn, "SELECT category_name FROM categories_master WHERE id='$category_id'");
    if ($c = mysqli_fetch_assoc($cat)) $category_name = $c['category_name'];

    // === SAVE DOMAIN LIST ===
    if ($id > 0) {
        $stmt = $conn->prepare("
            UPDATE domain_list SET
                domain_name=?, launch_date=?, last_renewal_date=?, customer_name=?, phone=?, whatsapp=?,
                business_name=?, address=?, email=?, renewal_date=?, purchase_name=?,
                renewal_cycle=?, status=?, product=?, category_id=?, category_name=?,
                country=?, state=?, city=?, description=?
            WHERE id=?
        ");
        $stmt->bind_param(
            "sssssssssssiisisssssi",
            $domain_name, $launch_date, $last_renewal_date, $customer_name, $phone, $whatsapp,
            $business_name, $address, $email, $renewal_date, $purchase_name,
            $renewal_cycle, $status, $product, $category_id, $category_name,
            $country, $state, $city, $description, $id
        );
        $stmt->execute();
        $stmt->close();
        $domain_id = $id;
        $_SESSION['msg'] = "Domain updated successfully";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO domain_list (
                domain_name, launch_date, last_renewal_date, customer_name, phone, whatsapp,
                business_name, address, email, renewal_date,
                purchase_name, renewal_cycle, status, product, category_id, category_name,
                country, state, city, description
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "sssssssssssiisisssss",
            $domain_name, $launch_date, $last_renewal_date, $customer_name, $phone, $whatsapp,
            $business_name, $address, $email, $renewal_date,
            $purchase_name, $renewal_cycle, $status, $product, $category_id, $category_name,
            $country, $state, $city, $description
        );
        $stmt->execute();
        $domain_id = $stmt->insert_id;
        $stmt->close();
        $_SESSION['msg'] = "Domain added successfully";
    }

   // --- AFTER DOMAIN INSERT/UPDATE ---
$domainId = $id; // For UPDATE
if (!$id) $domainId = mysqli_insert_id($conn); // For INSERT

$selectedProducts = isset($_POST['product']) ? $_POST['product'] : [];

// --- SEO ---
if (in_array('SEO', $selectedProducts)) {
    $main_keyword = $_POST['main_keyword'] ?? '';
    $mail_id = $_POST['mail_id'] ?? '';
    
    $seoExists = mysqli_query($conn, "SELECT id FROM seo_details WHERE domain_id='$domainId'");
    if (mysqli_num_rows($seoExists) > 0) {
        // UPDATE
        $row = mysqli_fetch_assoc($seoExists);
        mysqli_query($conn, "UPDATE seo_details SET main_keyword='".mysqli_real_escape_string($conn,$main_keyword)."',
            mail_id='".mysqli_real_escape_string($conn,$mail_id)."' WHERE id=".$row['id']);
    } else {
        // INSERT
        mysqli_query($conn, "INSERT INTO seo_details (domain_id, main_keyword, mail_id) VALUES (
            '$domainId','".mysqli_real_escape_string($conn,$main_keyword)."','".mysqli_real_escape_string($conn,$mail_id)."'
        )");
    }
}

// --- MAP ---
if (in_array('Map', $selectedProducts)) {

    $mapKeyword      = trim($_POST['map_keyword'] ?? '');
    $mapDescription  = trim($_POST['map_description'] ?? '');
    $mapYear         = trim($_POST['map_creation_year'] ?? date('Y'));
    $mapWorkLocation = trim($_POST['map_work_location'] ?? '');

    // Check if a map record already exists for this domain
    $mapExists = mysqli_query($conn, "SELECT id FROM map_configuration WHERE domain_id='$domainId'");
    if (mysqli_num_rows($mapExists) > 0) {
        $row = mysqli_fetch_assoc($mapExists);
        $mapId = $row['id'];
        mysqli_query($conn, "
            UPDATE map_configuration SET
                map_keyword='".mysqli_real_escape_string($conn, $mapKeyword)."',
                map_description='".mysqli_real_escape_string($conn, $mapDescription)."',
                map_creation_year='".mysqli_real_escape_string($conn, $mapYear)."',
                map_work_location='".mysqli_real_escape_string($conn, $mapWorkLocation)."',
                updated_at=NOW()
            WHERE id='$mapId'
        ");
    } else {
        mysqli_query($conn, "
            INSERT INTO map_configuration 
                (domain_id, map_keyword, map_description, map_creation_year, map_work_location, status, created_at, updated_at) 
            VALUES 
                ('$domainId',
                 '".mysqli_real_escape_string($conn, $mapKeyword)."',
                 '".mysqli_real_escape_string($conn, $mapDescription)."',
                 '".mysqli_real_escape_string($conn, $mapYear)."',
                 '".mysqli_real_escape_string($conn, $mapWorkLocation)."',
                 'Pending',
                 NOW(),
                 NOW()
                )
        ");
    }
}




    $_SESSION['msg_type'] = "success";
    header("Location: domain.php");
    exit;
}

/* ---------------- EDIT LOAD ---------------- */
if (isset($_GET['edit'])) {
    $id = base64_decode($_GET['edit']);
    $res = mysqli_query($conn, "SELECT * FROM domain_list WHERE id='$id'");
    if ($row = mysqli_fetch_assoc($res)) extract($row);
}

/* ---------------- CATEGORY AND PRODUCTS ---------------- */
$categories = [];
$r = mysqli_query($conn, "SELECT id, category_name FROM categories_master");
while ($c = mysqli_fetch_assoc($r)) $categories[] = $c;

$products = [];
$pq = mysqli_query($conn, "SELECT product_name FROM products_master WHERE status = 1 ORDER BY product_name");
while ($pr = mysqli_fetch_assoc($pq)) $products[] = $pr['product_name'];

include 'header.php';
include 'navbar.php';
?>


<style>
.form-control, .form-select { height: 42px; margin-bottom: 1rem; background-color:#d6ecff; }
/* Modified form-control for textarea */
textarea.form-control { height: auto; min-height: 100px; } 

input[type='date'] { background-color:#d6ecff; }
.select2-container .select2-selection--single { height: 42px; padding: 6px 12px; font-size: 1rem; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #d6ecff; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px; top: 0; right: 10px; }
.select2-container { margin-bottom: 15px; }
.text-danger {
    display: block;
    margin-bottom: 1rem; /* space after error to next field */
    font-size: 0.85rem;
    color: #dc3545;
}

textarea.form-control { 
    height: auto; 
    min-height: 100px; 
}

</style>

<div class="content-area">
<div class="card shadow-lg p-4" style="margin:auto;">
<div class="d-flex justify-content-between mb-3">
    <h4 class="text-primary"><?= $id ? "Edit Domain Details" : "Add Domain Details" ?></h4>
    <a href="bulk_upload.php" class="btn btn-success">
        <i class="bi bi-upload"></i> Bulk Upload
    </a>
</div>



<form method="POST" id="domainForm">


<?php if (isset($_SESSION['msg'])): ?>
    <div   id="session-alert" class="alert alert-<?= $_SESSION['msg_type'] ?? 'success' ?> text-center">
        <?= $_SESSION['msg']; ?>
    </div>
<?php unset($_SESSION['msg'], $_SESSION['msg_type']); endif; ?>

<?php if($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

<div class="row">
<div class="col-md-6">
<input type="text" name="domain_name" id="domain_name" class="form-control" placeholder="Domain Name" 
       value="<?= htmlspecialchars($domain_name ?? 'NULL') ?>">
<span id="domain_name_error" class="text-danger"></span>

<input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Customer Name *" value="<?= htmlspecialchars($customer_name) ?>" required>
<span id="customer_name_error" class="text-danger"></span>

<input type="tel" name="phone" id="phone" class="form-control" placeholder="Phone *" value="<?= htmlspecialchars($phone) ?>" required>
<span id="phone_error" class="text-danger"></span>

<input type="tel" name="whatsapp" id="whatsapp" class="form-control" placeholder="WhatsApp*" value="<?= htmlspecialchars($whatsapp) ?>" required>
<span id="whatsapp_error" class="text-danger"></span>

<input type="email" name="email" id="email" class="form-control" placeholder="Email*" value="<?= htmlspecialchars($email) ?>" required>
<span id="email_error" class="text-danger"></span>

<input type="text" name="business" class="form-control" placeholder="Business Name" value="<?= htmlspecialchars($business_name) ?>">

<div class="border rounded p-2 mb-3 position-relative" style="background:#d6ecff">

    <!-- Plus icon (redirect) -->
    <a href="add_category.php"
       class="btn btn-sm btn-primary position-absolute"
       style="top:8px; right:8px;"
       title="Add New Category">
        <i class="bi bi-plus-lg"></i>
    </a>

    <label>Business Category</label>

    <div class="mt-2">
        <select name="category_id"
                class="form-select select2"
                data-placeholder="Select Category"
                required>
            <option></option>
            <?php foreach($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"
                <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

</div>

<input type="text" name="address" class="form-control" placeholder="Business Address" value="<?= htmlspecialchars($address) ?>">



<select id="country" name="country" class="form-select select2" data-placeholder="Select Country" required>
    <option></option>
    <?php foreach($geoData as $c): ?>
    <option value="<?= htmlspecialchars($c['name']) ?>" <?= $country==$c['name']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
    <?php endforeach; ?>
</select>

<select id="state" name="state" class="form-select select2" data-placeholder="Select State" required>
    <option></option>
</select>

<select id="city" name="city" class="form-select select2" data-placeholder="Select City" required>
    <option></option>
</select>

<div class="border rounded p-2 mb-3 position-relative" style="background:#d6ecff">

    <!-- Plus icon (redirect) -->
    <a href="add_product.php"
       class="btn btn-sm btn-primary position-absolute"
       style="top:8px; right:8px;"
       title="Add New Product">
        <i class="bi bi-plus-lg"></i>
    </a>

    <label class="form-label fw-bold">Product</label>

    <div class="row mt-2">
    <?php
    $selectedProducts = isset($product) ? array_map('trim', explode(',', $product)) : [];
    $colSize = 4;

    foreach ($products as $p):
        $pSafe = htmlspecialchars($p);
    ?>
        <div class="col-md-<?= $colSize ?> mb-2">
            <div class="form-check">
                <input class="form-check-input"
                        type="checkbox"
                        name="product[]"
                        value="<?= $pSafe ?>"
                        id="product_<?= md5($p) ?>"
                        <?= in_array($p, $selectedProducts) ? 'checked' : '' ?>>
                <label class="form-check-label" for="product_<?= md5($p) ?>">
                    <?= $pSafe ?>
                </label>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

</div>

<div class="col-md-6">

<input type="text" id="purchased_by" name="purchased_by" class="form-control" placeholder="Registrar / Purchased By" 
       value="<?= htmlspecialchars($purchase_name ?? '') ?>" required>




<label>Launched Date</label>
<input type="date" id="launch_date" name="launch_date" class="form-control" value="<?= $launch_date ?>">


<label>Last Renewal Date</label>
<input type="date" id="last_renewal_date" name="last_renewal_date" class="form-control" value="<?= $last_renewal_date ?>">

<select id="renewal_cycle" name="renewal_cycle" class="form-select select2" data-placeholder="-- Select Renewal Cycle --" required>
    <option value="" disabled selected>-- Select Renewal Cycle --</option>
    <?php for($i = 1; $i <= 10; $i++): ?>
        <option value="<?= $i ?>" <?= (isset($renewal_cycle) && $renewal_cycle == $i) ? 'selected' : '' ?>>
            <?= $i ?> Year<?= $i > 1 ? 's' : '' ?>
        </option>
    <?php endfor; ?>
</select>


<label>Next Renewal Date</label>
<input type="date" id="renewal_date" name="renewal_date" class="form-control" readonly value="<?= $renewal_date ?>">


<label>Status</label>
<input type="text" id="status_text" class="form-control" 
       value="<?= !empty($domain_name) ? ($status ? 'Active' : 'Deactive') : 'No Status' ?>" readonly>

<label>Description</label>
<textarea name="description" class="form-control mb-3" placeholder="Detailed description here."><?= htmlspecialchars($description) ?></textarea> 

<button class="btn btn-primary w-100 mt-2"><?= $id ? "Update" : "Submit" ?></button>

</div>

</div>
</form>
</div>
</div>
<script>
$(document).ready(function(){

    // Store current domain and ID for edit mode checks
    const currentId = '<?= $id ?>';
    const currentDomain = '<?= addslashes($domain_name) ?>';

    // --- Initialize Select2 ---
    $('.select2').select2({
        width: '100%',
        placeholder: function(){ return $(this).data('placeholder'); },
        allowClear: true
    });

    // Automatically focus Select2 search on open
    $('.select2').on('select2:open', function(){
        let searchField = $(this).data('select2').dropdown?.$search || $(this).data('select2').selection?.$search;
        if(searchField) searchField.focus();
    });

    // --- NEXT RENEWAL DATE CALCULATION ---
    function calculateRenewalDate(){
        const base = $('#last_renewal_date').val();
        const cycle = parseInt($('#renewal_cycle').select2('val')); // Fixed Select2 reading

        if(!base || isNaN(cycle) || cycle < 1){
            $('#renewal_date').val('');
            $('#status_text').val($('#domain_name').val() ? 'Deactive' : 'No Status');
            return;
        }

        const d = new Date(base + "T00:00:00"); // Correct date parsing
        d.setFullYear(d.getFullYear() + cycle);

        const today = new Date();
        today.setHours(0,0,0,0);

        $('#renewal_date').val(d.toISOString().split('T')[0]);
        $('#status_text').val(d >= today ? 'Active' : 'Deactive');
    }

    // Trigger calc on change events
    $('#last_renewal_date, #launch_date, #renewal_cycle').on('change', calculateRenewalDate);
    
    // Run once on page load (after Select2 initialized)
    setTimeout(calculateRenewalDate, 100);

    // --- GEO SELECT HANDLING ---
    const geoData = <?= json_encode($geoData) ?>;

    function populateStates(country, selectedState=''){
        $('#state').html('<option></option>').trigger('change');
        $('#city').html('<option></option>').trigger('change');
        if(country){
            let states = geoData.find(c=>c.name===country)?.states||[];
            states.forEach(s=>$('#state').append('<option value="'+s.name+'">'+s.name+'</option>'));
            if(selectedState) $('#state').val(selectedState).trigger('change');
        }
    }

    function populateCities(country, state, selectedCity=''){
        $('#city').html('<option></option>').trigger('change');
        if(country && state){
            let states = geoData.find(c=>c.name===country)?.states||[];
            let cities = states.find(s=>s.name===state)?.cities||[];
            cities.forEach(c=>$('#city').append('<option value="'+c.name+'">'+c.name+'</option>'));
            if(selectedCity) $('#city').val(selectedCity).trigger('change');
        }
    }

    $('#country').on('change', function(){ populateStates($(this).val()); });
    $('#state').on('change', function(){ populateCities($('#country').val(), $(this).val()); });

    // Edit mode: preselect country/state/city
    const selectedCountry = '<?= addslashes($country) ?>';
    const selectedState = '<?= addslashes($state) ?>';
    const selectedCity = '<?= addslashes($city) ?>';
    if(selectedCountry){
        $('#country').val(selectedCountry).trigger('change');
        setTimeout(()=>{
            if(selectedState) populateStates(selectedCountry, selectedState);
            setTimeout(()=>{
                if(selectedCity) populateCities(selectedCountry, selectedState, selectedCity);
            },100);
        },100);
    }

    // --- REAL-TIME FIELD VALIDATION ---
    const fieldsToValidate = [
        {id:'#customer_name', fieldName:'customer_name', error:'#customer_name_error'},
        {id:'#domain_name', fieldName:'domain_name', error:'#domain_name_error'},
        {id:'#phone', fieldName:'phone', error:'#phone_error'},
        {id:'#whatsapp', fieldName:'whatsapp', error:'#whatsapp_error'},
        {id:'#email', fieldName:'email', error:'#email_error'}
    ];

    function validateField(field, value, errorSelector, isFinalSubmit){
        const deferred = $.Deferred();

        // Skip optional empty fields
        if(value === '' && (field==='whatsapp'||field==='email')){
            $(errorSelector).text('');
            deferred.resolve(true);
            return deferred.promise();
        }

        // Skip unchanged domain/customer in edit
        if((field==='domain_name' && currentId && value.toLowerCase()===currentDomain.toLowerCase()) ||
           (field==='customer_name' && currentId && value.toLowerCase()==='<?= addslashes(strtolower($customer_name)) ?>')){
            $(errorSelector).text('');
            deferred.resolve(true);
            return deferred.promise();
        }

        $.ajax({
            url:'validate_field.php',
            method:'POST',
            data:{field:field,value:value,id:currentId},
            dataType:'json',
            success:function(res){
                $(errorSelector).text(res.status?'':res.message);
                deferred.resolve(res.status);
            },
            error:function(){ deferred.resolve(false); }
        });
        return deferred.promise();
    }

    fieldsToValidate.forEach(item=>{
        $(item.id).on('keyup blur', function(){
            const val = $(this).val().trim();
            if(val==='') { $(item.error).text(''); return; }
            validateField(item.fieldName,val,item.error,false);
        });
    });

    // --- PRODUCT DOMAIN CHECK ---
    function checkDomainHighlight(){
        if($('input[name="product[]"][value="Domain"]').is(':checked') && $('#domain_name').val().trim()===''){
            $('#domain_name').css('border','2px solid red');
        }else{
            $('#domain_name').css('border','');
        }
    }
    $('input[name="product[]"][value="Domain"]').on('change', checkDomainHighlight);
    checkDomainHighlight();

    // --- SHOW/HIDE SEO & MAP FIELDS ---
    function toggleSeoMapFields(){
        $('#seo_fields').toggle($('input[name="product[]"][value="SEO"]').is(':checked'));
        $('#map_fields').toggle($('input[name="product[]"][value="Map"]').is(':checked'));
    }
    $('input[name="product[]"]').on('change', toggleSeoMapFields);
    toggleSeoMapFields();

    // --- FORM SUBMIT VALIDATION ---
    $('#domainForm').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        let formError = false;

        // Required Fields
        const requiredFields = [
            {id:'#customer_name',name:'Customer Name'},
            {id:'#phone',name:'Phone'},
            {id:'#country',name:'Country'},
            {id:'#state',name:'State'},
            {id:'#city',name:'City'}
        ];
        for(const f of requiredFields){
            if($(f.id).val().trim()===''){ alert(f.name+' is required'); $(f.id).focus(); formError=true; break; }
        }
        if(formError) return false;

        // At least 1 product
        if($('input[name="product[]"]:checked').length===0){ alert('Please select at least one product.'); return false; }

        // Domain product must have domain name
        if($('input[name="product[]"][value="Domain"]').is(':checked') && $('#domain_name').val().trim()===''){
            alert('Please enter Domain Name.');
            $('#domain_name').focus().css('border','2px solid red');
            return false;
        }

        // AJAX checks
        let checks = [];
        checks.push(validateField('customer_name',$('#customer_name').val().trim(),'#customer_name_error',true));
        if($('input[name="product[]"][value="Domain"]').is(':checked')) checks.push(validateField('domain_name',$('#domain_name').val().trim(),'#domain_name_error',true));
        checks.push(validateField('phone',$('#phone').val().trim(),'#phone_error',true));
        if($('#whatsapp').val().trim()!=='') checks.push(validateField('whatsapp',$('#whatsapp').val().trim(),'#whatsapp_error',true));
        if($('#email').val().trim()!=='') checks.push(validateField('email',$('#email').val().trim(),'#email_error',true));

        $.when.apply($,checks).then(function(){
            let allValid = true;
            for(let i=0;i<arguments.length;i++){ if(arguments[i]===false){ allValid=false; break; } }
            if(allValid) $form.off('submit').submit();
            else alert('Fix validation errors before submitting.');
        });
    });

    // Fade out session alert
    setTimeout(function(){
        const alertBox = document.getElementById('session-alert');
        if(alertBox){ alertBox.style.transition='opacity 0.5s'; alertBox.style.opacity='0'; setTimeout(()=>alertBox.remove(),500); }
    }, 3000);

});
</script>



<?php include 'footer.php'; ?>