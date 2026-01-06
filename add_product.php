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

/* ================= ADD PRODUCT ================= */
if (isset($_POST['save_product'])) {
    $product = trim($_POST['product_name']);

    if ($product == '') {
        $_SESSION['msg'] = "Product name required";
    } else {
        $product = mysqli_real_escape_string($conn, $product);

        $check = mysqli_query($conn, "SELECT id FROM products_master WHERE product_name='$product'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['msg'] = "Product already exists";
        } else {
            mysqli_query($conn, "INSERT INTO products_master (product_name, status) VALUES ('$product', 1)");
            $_SESSION['msg'] = "Product added successfully";
        }
    }

    header("Location: add_product.php");
    exit;
}

/* ================= DELETE PRODUCT ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id) {
        $result = mysqli_query($conn, "DELETE FROM products_master WHERE id=$id");
        $_SESSION['msg'] = $result ? "Product deleted successfully" : "Failed to delete product";
    }
    header("Location: add_product.php");
    exit;
}

/* ================= FETCH PRODUCT LIST ================= */
$productList = mysqli_query($conn, "SELECT id, product_name, status FROM products_master ORDER BY id DESC");

include 'header.php';
include 'navbar.php';
?>

<div class="content-area">
<div class="container mt-4">

<h3 class="mb-4 text-primary">Add Product</h3>

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-info text-center">
    <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<!-- ================= ADD FORM ================= -->
<div class="card shadow p-4 col-md-6 mx-auto mb-4">
<form method="post">
    <div class="mb-3">
        <label class="form-label text-muted">Product Name</label>
        <input type="text"
               name="product_name"
               class="form-control"
               placeholder="e.g. Website, SEO"
               required>
    </div>
    <button type="submit"
            name="save_product"
            class="btn btn-primary w-100">
        Save Product
    </button>
</form>
</div>

<!-- ================= PRODUCT LIST ================= -->
<h5 class="mb-3 text-primary">Product List</h5>

<div class="table-responsive">
<table id="productTable" class="table table-bordered table-striped align-middle">
    <thead class="table-light">
        <tr>
            <th width="10%">#</th>
            <th>Product Name</th>
            <th width="20%" class="text-center">Status</th>
            <th width="20%" class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php $i=1; while($row = mysqli_fetch_assoc($productList)): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['product_name']) ?></td>
            <td class="text-center">
                <?= $row['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?>
            </td>
            <td class="text-center">
                <a href="add_product.php?delete=<?= $row['id'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Are you sure you want to delete this product?')">
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
    $('#productTable').DataTable({
        pageLength: 5,
        lengthChange: false,
        ordering: true
    });
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000); // 3 seconds
});
</script>
