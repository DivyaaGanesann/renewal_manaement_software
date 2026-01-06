<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

$message = "";

if (isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // ✅ FIXED TABLE NAME
    $stmt = $conn->prepare("SELECT password FROM staff WHERE username=? AND status=1");
    $stmt->bind_param("s", $_SESSION['admin']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        $message = "<div class='alert alert-danger'>User not found</div>";
    } elseif (!password_verify($current_password, $row['password'])) {
        $message = "<div class='alert alert-danger'>Current password is incorrect</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger'>New passwords do not match</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='alert alert-warning'>Password must be at least 6 characters</div>";
    } else {

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // ✅ FIXED TABLE NAME
        $update = $conn->prepare("UPDATE staff SET password=? WHERE username=?");
        $update->bind_param("ss", $new_hash, $_SESSION['admin']);
        $update->execute();

        session_destroy();
        header("Location: index.php?password_changed=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Change Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    Change Password
                </div>

                <div class="card-body">
                    <?php echo $message; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button name="change_password" class="btn btn-primary w-100">
                            Update Password
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
