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

/* ================= ADD ROLE ================= */
if (isset($_POST['save_role'])) {
    $role_name = trim($_POST['role_name']);
    $status    = isset($_POST['status']) ? 1 : 0;

    if ($role_name == '') {
        $_SESSION['msg'] = "Role name required";
    } else {
        $role_name = mysqli_real_escape_string($conn, $role_name);

        $check = mysqli_query($conn, "SELECT id FROM roles_master WHERE role_name='$role_name'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['msg'] = "Role already exists";
        } else {
            mysqli_query($conn, "INSERT INTO roles_master (role_name, status) VALUES ('$role_name', '$status')");
            $_SESSION['msg'] = "Role added successfully";
        }
    }

    header("Location: roles_demo.php");
    exit;
}

/* ================= DELETE ROLE ================= */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id) {
        $result = mysqli_query($conn, "DELETE FROM roles_master WHERE id=$id");
        $_SESSION['msg'] = $result ? "Role deleted successfully" : "Failed to delete role";
    }
    header("Location: roles_demo.php");
    exit;
}

/* ================= FETCH ROLE LIST ================= */
$roleList = mysqli_query($conn, "SELECT id, role_name, status FROM roles_master ORDER BY id DESC");

include 'header.php';
include 'navbar.php';
?>

<div class="content-area">
<div class="container mt-4">

<h3 class="mb-4 text-primary">Add Role</h3>

<?php if(isset($_SESSION['msg'])): ?>
<div class="alert alert-info text-center">
    <?= $_SESSION['msg']; unset($_SESSION['msg']); ?>
</div>
<?php endif; ?>

<!-- ================= ADD FORM ================= -->
<div class="card shadow p-4 col-md-6 mx-auto mb-4">
<form method="post">
    <div class="mb-3">
        <label class="form-label text-muted">Role Name</label>
        <input type="text"
               name="role_name"
               class="form-control"
               placeholder="e.g. Admin, Marketing"
               required>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="status" id="statusCheck" checked>
        <label class="form-check-label" for="statusCheck">Active</label>
    </div>

    <button type="submit"
            name="save_role"
            class="btn btn-primary w-100">
        Save Role
    </button>
</form>
</div>

<!-- ================= ROLE LIST ================= -->
<h5 class="mb-3 text-primary">Role List</h5>

<div class="table-responsive">
<table id="roleTable" class="table table-bordered table-striped align-middle">
    <thead class="table-light">
        <tr>
            <th width="10%">#</th>
            <th>Role Name</th>

            <th width="20%" class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php $i=1; while($row = mysqli_fetch_assoc($roleList)): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['role_name']) ?></td>

            <td class="text-center">
                <a href="roles_demo.php?delete=<?= $row['id'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Are you sure you want to delete this role?')">
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
    $('#roleTable').DataTable({
        pageLength: 5,
        lengthChange: false,
        ordering: true
    });

    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000); // 3 seconds
});
</script>
