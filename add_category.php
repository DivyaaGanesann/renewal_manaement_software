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

/* ================= ADD CATEGORY ================= */
if (isset($_POST['save_category'])) {
    $category = trim($_POST['category_name']);

    if ($category == '') {
        $_SESSION['msg'] = "❌ Category name required";
    } else {
        $category = mysqli_real_escape_string($conn, $category);

        $check = mysqli_query($conn, "SELECT id FROM categories_master WHERE category_name='$category'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['msg'] = "⚠️ Category already exists";
        } else {
            mysqli_query($conn, "INSERT INTO categories_master (category_name) VALUES ('$category')");
            $_SESSION['msg'] = "✅ Category added successfully";
        }
    }

    header("Location: add_category.php");
    exit;
}

/* ================= DELETE CATEGORY ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id) {
        $result = mysqli_query($conn, "DELETE FROM categories_master WHERE id=$id");
        $_SESSION['msg'] = $result ? "✅ Category deleted successfully" : "⚠️ Failed to delete category";
    }
    header("Location: add_category.php");
    exit;
}

/* ================= FETCH CATEGORY LIST ================= */
$categoryList = mysqli_query($conn, "SELECT id, category_name FROM categories_master ORDER BY id DESC");

include 'header.php';
include 'navbar.php';
?>

<div class="content-area">
<div class="container mt-4">

<h3 class="mb-4 text-primary">Add Category</h3>

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-info text-center">
    <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<!-- ================= ADD FORM ================= -->
<div class="card shadow p-4 col-md-6 mx-auto mb-4">
<form method="post">
    <div class="mb-3">
        <label class="form-label text-muted">Category Name</label>
        <input type="text"
               name="category_name"
               class="form-control"
               placeholder="e.g. Web Hosting"
               required>
    </div>
    <button type="submit"
            name="save_category"
            class="btn btn-primary w-100">
        Save Category
    </button>
</form>
</div>

<!-- ================= CATEGORY LIST ================= -->
<h5 class="mb-3 text-primary">Category List</h5>

<div class="table-responsive">
<table id="categoryTable" class="table table-bordered table-striped align-middle">
    <thead class="table-light">
        <tr>
            <th width="10%">#</th>
            <th>Category Name</th>
            <th width="20%" class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php $i=1; while($row = mysqli_fetch_assoc($categoryList)): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['category_name']) ?></td>
            <td class="text-center">
                <a href="add_category.php?delete=<?= $row['id'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Are you sure you want to delete this category?')">
                    <i class="bi bi-trash"></i>
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

</div>
</div>

<?php include 'footer.php'; ?>

<script>
$(document).ready(function () {
    $('#categoryTable').DataTable({
        pageLength: 5,
        lengthChange: false,
        ordering: true
    });
	// Auto-hide alert after 3 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000); // 3000ms = 3 seconds
});
</script>
