<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'header.php';
include 'navbar.php';
?>

<div class="content-area">
<div class="container mt-4">

<h3 class="mb-4 text-primary">Settings</h3>

<div class="row g-4">

    <!-- CATEGORY CARD -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-folder-plus text-primary" style="font-size:40px;"></i>
                </div>
                <h5 class="card-title">Manage Categories</h5>
                <p class="text-muted">Add and manage business categories</p>
                <a href="add_category.php" class="btn btn-primary">
                    Add Category
                </a>
            </div>
        </div>
    </div>

    <!-- PRODUCT CARD -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-box-seam text-success" style="font-size:40px;"></i>
                </div>
                <h5 class="card-title">Manage Products</h5>
                <p class="text-muted">Add and manage products/services</p>
                <a href="add_product.php" class="btn btn-success">
                    Add Product
                </a>
            </div>
        </div>
    </div>
	
    <!-- SEO CARD -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-search text-danger" style="font-size:40px;"></i>
                </div>
                <h5 class="card-title">Manage SEO </h5>
                <p class="text-muted">Add and manage SEO Verification</p>
                <a href="manage_seo_items.php" class="btn btn-danger">
                    Add Items
                </a>
            </div>
        </div>
    </div>    
	<!-- Role CARD -->
<div class="col-md-6">
    <div class="card shadow-sm h-100 border-0">
        <div class="card-body text-center">
            <div class="mb-3">
                <!-- Changed icon to a "people" icon and made it yellow -->
                <i class="bi bi-people-fill text-warning" style="font-size:40px;"></i>
            </div>
            <h5 class="card-title">Manage Staff Roles</h5>
            <p class="text-muted">Add and manage Staff Roles</p>
            <!-- Changed button color to yellow -->
            <a href="roles_demo.php" class="btn btn-warning">
                Add Items
            </a>
        </div>
    </div>
</div>


</div>

</div>
</div>

<?php include 'footer.php'; ?>
