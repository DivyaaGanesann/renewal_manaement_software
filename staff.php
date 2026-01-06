<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config.php';
include 'get_counts.php';

// =========================================================
// !!! CORE CHANGE: DETERMINE CURRENT USER'S ROLE !!!
// Assuming $_SESSION['admin'] holds the logged-in username.
// Adjust '$_SESSION['admin']' if your key is different (e.g., $_SESSION['admin_username']).
// =========================================================
$current_user_role = 'Guest'; 
$is_admin = false;

if (isset($_SESSION['admin'])) {
    $logged_in_username = $_SESSION['admin']; // Assuming 'admin' key holds the username string

    // Query the database to get the role of the logged-in user
    $stmt = $conn->prepare("SELECT role FROM staff WHERE username = ?");
    $stmt->bind_param("s", $logged_in_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $current_user_role = $row['role'];
    }
    $stmt->close();
    
    // Check if the determined role is 'Admin'
    $is_admin = ($current_user_role === 'Admin');
}
// =========================================================

// DELETE STAFF (Only an Admin user should be able to trigger this)
if (isset($_GET['delete']) && $is_admin) { 
    $del_id = base64_decode($_GET['delete']);
    
    // Use prepared statement for secure deletion
    $stmt = $conn->prepare("DELETE FROM staff WHERE id=?");
    $stmt->bind_param("i", $del_id); // Assuming ID is integer
    $stmt->execute();
    $stmt->close();
    
    header("Location: staff.php?deleted=1");
    exit;
}

// VARIABLES
$id = "";
$name = $role = $username = $status = "";

// EDIT STAFF - FETCH DATA (Only an Admin user should be able to trigger this)
if (isset($_GET['edit']) && $is_admin) { 
    $id = base64_decode($_GET['edit']);
    
    // Use prepared statement for secure fetching
    $stmt = $conn->prepare("SELECT * FROM staff WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $name      = $row['name'];
        $role      = $row['role'];
        $username  = $row['username'];
        $status    = $row['status'];
    }
    $stmt->close();
}

// INSERT / UPDATE STAFF (Only an Admin user should be able to trigger this)
if ($_SERVER['REQUEST_METHOD'] == "POST" && $is_admin) { 

    // Use prepared statements for security (replacing original direct query)
    $id        = (int)$_POST['id'];
    $name      = $_POST['name'];
    $role      = $_POST['role'];
    $username  = $_POST['username'];
    $password  = $_POST['password'];
    $status    = (int)$_POST['status'];

    if ($id) {  
        // UPDATE
        if (!empty($password)) {
            $pass_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Prepared Statement for UPDATE with password
            $stmt = $conn->prepare("UPDATE staff SET name=?, role=?, username=?, password=?, status=? WHERE id=?");
            $stmt->bind_param("ssssii", $name, $role, $username, $pass_hash, $status, $id);
            $stmt->execute();
            $stmt->close();
            
        } else {
            // Prepared Statement for UPDATE without password
            $stmt = $conn->prepare("UPDATE staff SET name=?, role=?, username=?, status=? WHERE id=?");
            $stmt->bind_param("sssii", $name, $role, $username, $status, $id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: staff.php?updated=1");
        exit;

    } else {    
        // INSERT
        $pass_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Prepared Statement for INSERT
        $stmt = $conn->prepare("INSERT INTO staff(name, role, username, password, status) VALUES(?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $role, $username, $pass_hash, $status);
        $stmt->execute();
        $stmt->close();
        
        header("Location: staff.php?saved=1");
        exit;
    }
}
$rolesList = mysqli_query($conn, "SELECT id, role_name FROM roles_master WHERE status=1 ORDER BY role_name ASC");

include 'header.php';
include 'navbar.php';
?>

<div id="staffpage" class="content-area">

    <div class="container mt-4">
        <div class="card mx-auto" style="max-width:600px;">
<h2 class="text-center text-primary mt-2"><?= ($id && $is_admin) ? "Edit Staff" : "Add Staff" ?></h2>
             

            <div class="card-body">
    
        <?php if (isset($_GET['saved'])) { ?>
            <div class="alert alert-success text-center">Staff Added Successfully!</div>
        <?php } ?>

        <?php if (isset($_GET['updated'])) { ?>
            <div class="alert alert-success text-center">Staff Updated Successfully!</div>
        <?php } ?>

        <?php if (isset($_GET['deleted'])) { ?>
            <div class="alert alert-danger text-center">Staff Deleted Successfully!</div>
        <?php } ?>

                <?php if ($is_admin): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
<div class="mb-3">
    <label class="form-label">Role</label>
    <select name="role" class="form-select" required>
        <option value="" disabled selected>Select Role</option>
        <?php while($r = mysqli_fetch_assoc($rolesList)): ?>
            <option value="<?= htmlspecialchars($r['role_name']) ?>"
                <?= ($role == $r['role_name']) ? "selected" : "" ?>>
                <?= htmlspecialchars($r['role_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>


                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $id ? "Change Password (optional)" : "Password" ?></label>
                            <input type="password" name="password" class="form-control" <?= $id ? "" : "required" ?>>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="1" <?= $status=="1"?"selected":"" ?>>Active</option>
                                <option value="0" <?= $status=="0"?"selected":"" ?>>Deactive</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><?= $id ? "Update" : "Save" ?></button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        You do not have permission to add or edit staff records.
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <div class="container mt-4">
            <h3 class="mb-3 text-center text-primary"><b>Staff List</b></h3>

            <div class="table-responsive mx-auto">
                <table id="staffTable" class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>SNO</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
<?php
// Ensure order is consistent (newest first) for stable SNO display
$res = mysqli_query($conn, "SELECT * FROM staff ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $statusBadge = $row['status'] == 1
        ? "<span class='badge bg-success'>Active</span>"
        : "<span class='badge bg-danger'>Deactive</span>";

    // Add red background if deactive
    $rowClass = $row['status'] == 0 ? 'table-danger' : '';

    echo "<tr class='{$rowClass}'>";
    echo "<td></td>"; // SNO Placeholder for DataTables
    echo "<td>".htmlspecialchars($row['name'])."</td>";
    echo "<td>".htmlspecialchars($row['role'])."</td>";
    echo "<td>".htmlspecialchars($row['username'])."</td>";
    echo "<td>$statusBadge</td>";
    
    // START: CONDITIONAL ACTIONS COLUMN
    echo "<td class='text-center'>";
    if ($is_admin) { // <-- CHECK IF ADMIN
        echo "
            <a href='staff.php?edit=" . base64_encode($row['id']) . "' title='Edit'>
                <i class='fa-solid fa-pencil'></i>
            </a>

            <a href='staff.php?delete=" . base64_encode($row['id']) . "' 
               onclick=\"return confirm('Delete this staff?')\" 
               class='ms-2' title='Delete'>
                <i class='fa-solid fa-trash'></i>
            </a>";
    } else {
        echo "<span class='text-muted'>N/A</span>"; // Show N/A if not admin
    }
    echo "</td>";
    // END: CONDITIONAL ACTIONS COLUMN
    
    echo "</tr>";
}
?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>

setTimeout(() => {
    let alertBox = document.querySelector('.alert');
    if (alertBox) {
        alertBox.style.display = "none";
    }
}, 10000);

$(document).ready(function() {
    $('#staffTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthChange: true,
        pageLength: 10,
        // Remove default initial sort to respect the SQL order (newest first)
        order: [],
        columnDefs: [
            {
                // Target SNO column (index 0)
                targets: 0,
                orderable: false,
                // Use the render function to dynamically generate the SNO
                render: function (data, type, row, meta) {
                    // meta.row is the 0-based index of the row *after* sorting/filtering/paging
                    return meta.row + 1; 
                }
            },
            // Disable sorting on Actions column (index 5)
            { orderable: false, targets: 5 }
        ]
    });
});
</script>

<?php include 'footer.php'; ?>