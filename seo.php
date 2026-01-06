<?php
ob_start();
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION['admin'])){
    header("Location:index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';

/* ---------- BASE64 URL SAFE ---------- */
function base_ecode64($id){ return strtr(base64_encode($id), '+/=', '-_,'); }
function base_dcode64($code){ return base64_decode(strtr($code, '-_,', '+/=')); }

$errors = [];
$success = false;

/* ---------- LOAD DOMAINS ---------- */
$query_domains = "SELECT id, customer_name, domain_name, business_name FROM domain_list WHERE status=1 ORDER BY domain_name ASC";
$result_domains = mysqli_query($conn,$query_domains);

/* ---------- LOAD SEO CHECKLIST ITEMS ---------- */
$seo_checklist_items = [];
$seo_res = mysqli_query($conn,"SELECT id,item_name FROM seo_checklist_items ORDER BY id ASC");
while($row = mysqli_fetch_assoc($seo_res)){
    $seo_checklist_items[$row['id']] = $row['item_name'];
}

/* ---------- DEFAULT VALUES ---------- */
$edit_id = 0;
$selected_domain_id = "";
$mail_id = "";
$main_keyword = "";
$status = "Pending";
$previous_page_visible = "";
$current_page_text = "";
$seo_data_date = date('Y-m-d');
$work_location = "";
$selected_checks = []; // array of selected item ids

/* ---------- AJAX ADD ITEM (REMOVED - THIS LOGIC IS NOW HANDLED BY REDIRECT) ---------- */
// if(isset($_POST['add_item_ajax'])){
//     // ... AJAX logic removed ...
//     // It will no longer be called since the button is a link
// }

/* ---------- EDIT MODE ---------- */
if(isset($_GET['id'])){
    $edit_id = intval(base_dcode64($_GET['id']));
    $stmt = $conn->prepare("SELECT * FROM seo_details WHERE id=?");
    $stmt->bind_param("i",$edit_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows == 1){
        $row = $res->fetch_assoc();
        $selected_domain_id      = $row['domain_id'];
        $mail_id                 = $row['mail_id'];
        $main_keyword            = $row['main_keyword'];
        $status                  = $row['status'];
        $previous_page_visible   = $row['previous_page_visible'];
        $current_page_text       = $row['current_page_text'];
        $work_location           = $row['work_location'];
        $seo_data_date           = $row['seo_data_date'];
        // Get selected checks as array
        $selected_checks = explode(',', $row['seo_items'] ?? '');
    }
    $stmt->close();
}

/* ---------- FORM SUBMIT ---------- */
if($_SERVER['REQUEST_METHOD']=='POST' /* && !isset($_POST['add_item_ajax']) removed check */ ){
    $edit_id             = intval($_POST['edit_id'] ?? 0);
    $selected_domain_id  = intval($_POST['domain_select'] ?? 0);
    $mail_id             = trim($_POST['mail_id'] ?? '');
    $main_keyword        = trim($_POST['main_keyword'] ?? '');
    $status              = $_POST['status'] ?? 'Pending';
    $previous_page_visible = trim($_POST['previous_page_visible'] ?? '');
    $current_page_text   = trim($_POST['current_page_text'] ?? '');
    $seo_data_date       = $_POST['seo_data_date'] ?? '';
    $work_location       = trim($_POST['work_location'] ?? '');
    $selected_checks     = $_POST['seo_items'] ?? []; // array
    $seo_items_str       = implode(',', $selected_checks); // store comma separated

    if($selected_domain_id<=0) $errors[] = "Please select Domain / Business.";
    if(!$main_keyword) $errors[] = "Main Keyword is required.";
    if($mail_id && !filter_var($mail_id,FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid Mail ID.";

    if(!$errors){
        if($edit_id>0){
            $stmt = $conn->prepare("UPDATE seo_details SET domain_id=?, mail_id=?, main_keyword=?, status=?, previous_page_visible=?, current_page_text=?, seo_items=?, work_location=?, seo_data_date=? WHERE id=?");
            $stmt->bind_param("issssssssi",$selected_domain_id,$mail_id,$main_keyword,$status,$previous_page_visible,$current_page_text,$seo_items_str,$work_location,$seo_data_date,$edit_id);
            $msg = "SEO Data Updated Successfully!";
        }else{
            $stmt = $conn->prepare("INSERT INTO seo_details (domain_id, mail_id, main_keyword, status, previous_page_visible, current_page_text, seo_items, work_location, seo_data_date) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issssssss",$selected_domain_id,$mail_id,$main_keyword,$status,$previous_page_visible,$current_page_text,$seo_items_str,$work_location,$seo_data_date);
            $msg = "SEO Data Saved Successfully!";
        }
        if($stmt->execute()){
            $_SESSION['msg']=$msg;
            $_SESSION['msg_type']='success';
            ob_end_clean();
            header("Location: seo.php"); exit;
        }else{
            $errors[]="Database Error: ".$conn->error;
        }
        $stmt->close();
    }
}

include 'header.php';
include 'navbar.php';
?>

<style>
/* ... (CSS remains unchanged) ... */
body{background:#f5f6fa;}
.content-area{max-width:1000px;margin:40px auto;padding:0 15px;}
.form-container{
    background:#fff;
    padding:30px 40px;
    border-radius:12px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}
h2{color:#0d6efd;margin-bottom:30px;font-weight:600;text-align:center;}
.form-label{font-weight:600;color:#495057;margin-bottom:6px;}
.form-control, .form-select{height:45px;border-radius:8px;font-size:15px;}
textarea.form-control{height:100px;padding:10px;}
.seo-checklist{
    border:1px solid #dee2e6;
    border-radius:10px;
    padding:15px;
    background:#f8f9fa;
}
.seo-checklist .form-check{margin-bottom:8px; display:flex; align-items:center;}
.form-check-input{width:18px;height:18px;margin-right:10px;}
.checkbox-label{font-size:14px;color:#343a40;}
.btn-primary{height:50px;border-radius:10px;font-weight:600;font-size:16px;}
/* .modal-header{background:#0d6efd;color:#fff;} Removed modal styles */
/* .modal-title{font-weight:600;} Removed modal styles */
/* .modal-content{border-radius:12px;} Removed modal styles */
</style>

<div class="content-area">
<div class="form-container">

<h2><?= $edit_id?'Edit SEO Details':'Add SEO Details' ?></h2>

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>
<?php if($errors): ?>
<div class="alert alert-danger"><ul class="mb-0">
<?php foreach($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="edit_id" value="<?= $edit_id ?>">

<div class="row g-4">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Customer / Domain / Business</label>
            <select name="domain_select" class="form-select" required>
                <option value="">Select</option>
                <?php while($row=mysqli_fetch_assoc($result_domains)):
                    $sel = ($selected_domain_id==$row['id'])?'selected':''; ?>
                    <option value="<?= $row['id'] ?>" <?= $sel ?>><?= htmlspecialchars($row['customer_name'].' - '.$row['domain_name'].' - '.$row['business_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">SEO Mail ID</label>
            <input type="email" name="mail_id" class="form-control" value="<?= htmlspecialchars($mail_id) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Primary Keyword</label>
            <textarea name="main_keyword" class="form-control"><?= htmlspecialchars($main_keyword) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="Pending" <?= $status=='Pending'?'selected':'' ?>>Pending</option>
                <option value="In Progress" <?= $status=='In Progress'?'selected':'' ?>>In Progress</option>
                <option value="Completed" <?= $status=='Completed'?'selected':'' ?>>Completed</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">SEO Date</label>
            <input type="date" name="seo_data_date" class="form-control" value="<?= $seo_data_date ?>">
        </div>
    </div>

    <div class="col-md-6">
<div class="mb-4">
    <label class="form-label d-flex justify-content-between align-items-center">
        SEO Verification
        <a href="manage_seo_items.php" class="btn btn-sm btn-success">
             <i class="bi bi-plus"></i>
        </a>
    </label>
    <div class="seo-checklist">
        <div class="row">
            <?php foreach($seo_checklist_items as $id=>$name): ?>
            <div class="col-6 form-check">
                <input type="checkbox" class="form-check-input" name="seo_items[]" id="seo_<?= $id ?>" value="<?= $id ?>" <?= in_array($id,$selected_checks)?'checked':'' ?>>
                <label class="form-check-label" for="seo_<?= $id ?>"><?= $name ?></label>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

        <div class="mb-3">
            <label class="form-label">Work Location</label>
            <input type="text" name="work_location" class="form-control" value="<?= htmlspecialchars($work_location) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Previous Page Rank</label>
            <input type="text" name="previous_page_visible" class="form-control" value="<?= htmlspecialchars($previous_page_visible) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Current Page Rank</label>
            <input type="text" name="current_page_text" class="form-control" value="<?= htmlspecialchars($current_page_text) ?>">
        </div>
    </div>
</div>

<button class="btn btn-primary mt-3 w-100"><?= $edit_id?'Update':'Save' ?> SEO Details</button>
</form>
</div>
</div>

<?php include 'footer.php';
ob_end_flush();
?>