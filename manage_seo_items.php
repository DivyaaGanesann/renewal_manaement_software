<?php
session_start();
// Add cache control headers from add_category.php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Add admin session check from add_category.php
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config.php';
// Include 'get_counts.php' if it is used elsewhere in the application, similar to add_category.php
include 'get_counts.php';

/* ================= ADD ITEM ================= */
if (isset($_POST['save_item'])) {
    $item = trim($_POST['item_name']);
    if ($item == '') {
        $_SESSION['msg'] = "Item name required";
    } else {
        $item = mysqli_real_escape_string($conn, $item);
        $check = mysqli_query($conn, "SELECT id FROM seo_checklist_items WHERE item_name='$item'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['msg'] = "Item already exists";
        } else {
            mysqli_query($conn, "INSERT INTO seo_checklist_items (item_name) VALUES ('$item')");
            $_SESSION['msg'] = "Item added successfully";
        }
    }
    // Ensure the redirect uses the correct file name
    header("Location: manage_seo_items.php");
    exit;
}

/* ================= DELETE ITEM ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id) {
        $result = mysqli_query($conn, "DELETE FROM seo_checklist_items WHERE id=$id");
        $_SESSION['msg'] = $result ? "Item deleted successfully" : "Failed to delete item";
    }
    // Ensure the redirect uses the correct file name
    header("Location: manage_seo_items.php");
    exit;
}

/* ================= FETCH ITEM LIST ================= */
$itemList = mysqli_query($conn, "SELECT id, item_name FROM seo_checklist_items ORDER BY id DESC");

// Move includes here, similar to add_category.php
include 'header.php';
include 'navbar.php';
?>

<div class="content-area"> <div class="container mt-4">
<h3 class="mb-4 text-primary">Manage SEO Checklist Items</h3>

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-info text-center">
    <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<div class="card shadow p-4 col-md-6 mx-auto mb-4">
<form method="post">
    <div class="mb-3">
        <label class="form-label text-muted">Item Name</label>
        <input type="text"
               name="item_name"
               class="form-control"
               placeholder="e.g. Google Analytics"
               required>
    </div>
    <button type="submit"
            name="save_item"
            class="btn btn-primary w-100">
        Add Item </button>
</form>
</div>

<h5 class="mb-3 text-primary">SEO Checklist Items</h5>
<div class="table-responsive">
<table id="itemTable" class="table table-bordered table-striped align-middle">
    <thead class="table-light">
        <tr>
            <th width="10%">#</th>
            <th>Item Name</th>
            <th width="20%" class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php $i=1; while($row = mysqli_fetch_assoc($itemList)): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td class="text-center">
                <a href="manage_seo_items.php?delete=<?= $row['id'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Are you sure you want to delete this item?')">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>
</div>
</div> <?php include 'footer.php'; ?>
<script>
$(document).ready(function () {
    $('#itemTable').DataTable({ 
        pageLength: 5, 
        lengthChange: false, 
        ordering: true 
    });
    // Ensure the timeout function is exactly the same as add_category.php
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000); 
});
</script>