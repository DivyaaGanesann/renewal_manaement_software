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
// INSERT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_name      = mysqli_real_escape_string($conn, $_POST['staff_name']);
    $domain_name     = mysqli_real_escape_string($conn, $_POST['domain_name']);
    $description     = mysqli_real_escape_string($conn, $_POST['description']);
    $discussion_date = mysqli_real_escape_string($conn, $_POST['discussion_date']);
    $purpose         = mysqli_real_escape_string($conn, $_POST['purpose']);

    mysqli_query($conn, "INSERT INTO discussion (staff_name, domain_name, description, discussion_date, purpose)
        VALUES ('$staff_name', '$domain_name', '$description', '$discussion_date', '$purpose')");

    header("Location: discussion.php?msg=added");
    exit;
}
include 'header.php';
include 'navbar.php';
?>
<style>
/* Ensure the Select2 field matches your form control height */
.select2-container--default .select2-selection--single {
    height: 42px;               /* Match other input fields */
    padding: 6px 12px;
    font-size: 1rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;    /* Rounded corners */

}

/* Text inside the Select2 field */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
}

/* Dropdown arrow */
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 42px;
    top: 0;
    right: 10px;
}

/* Container spacing */
.select2-container {
    margin-bottom: 15px;
    width: 100% !important; /* Ensure it fills the column */
}
</style>

<div id="discussionpage" class="content-area">
 <div class="container mt-4">
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
    <div class="alert alert-success text-center">Discussion Added Successfully</div>
<?php endif; ?>
    <div class="d-flex justify-content-center mt-4">
        <div class="card shadow-sm discussion-card" style="width: 600px;">
            <h4 class="mb-4 text-primary text-center">Add Discussion</h4>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label">Staff Name</label>
                    <input type="text" name="staff_name" class="form-control" 
                           value="<?php echo htmlspecialchars($_SESSION['admin']); ?>" readonly>
                </div>
<div class="mb-3">
    <label class="form-label">Domain Name</label>
    <select name="domain_name" class="form-select select2" required>
        <option value="">-- Select Domain --</option>
        <?php
        $result = mysqli_query($conn, "SELECT domain_name, customer_name FROM domain_list ORDER BY domain_name ASC");
        while ($row = mysqli_fetch_assoc($result)) {
            $domain = htmlspecialchars($row['domain_name']);
            $customer = htmlspecialchars($row['customer_name']);
            echo "<option value='{$domain}'>{$domain} ({$customer})</option>";
        }
        ?>
    </select>
</div>


                <div class="mb-3">
                    <label class="form-label">Purpose</label>
                    <select name="purpose" class="form-select" required>
                        <option value="">-- Select Purpose --</option>
                        <option value="Renewal Follow">Renewal Follow</option>
                        <option value="Customer Follow">Customer Follow</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="discussion_date" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>


                <button type="submit" class="btn btn-primary w-100">Add Discussion</button>
            </form>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.select2').select2({
        width: '100%',
        placeholder: "-- Select Domain --",
        allowClear: true
    });
});
</script>

<?php include 'footer.php'; ?>
