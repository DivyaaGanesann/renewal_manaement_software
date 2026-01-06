<?php
session_start();
include 'config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM staff WHERE username='$username' LIMIT 1");

    if ($query && mysqli_num_rows($query) == 1) {

        $row = mysqli_fetch_assoc($query);

        if (password_verify($password, $row['password'])) {

            if ($row['status'] == 1) {

                $_SESSION['admin'] = $row['username'];
                $_SESSION['admin_id'] = $row['id'];

                header("Location: dashboard.php");
                exit;

            } else {
                $message = "Your account is deactivated. Contact admin.";
            }

        } else {
            $message = "Incorrect password!";
        }

    } else {
        $message = "Username does not exist!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd, #f8f9fa);
            font-family: Arial, sans-serif;
        }
        .card {
            max-width: 420px;
            width: 100%;
            border-radius: 12px;
        }
        .demo-box {
            font-size: 14px;
            background: #f1f8ff;
            border: 1px dashed #0d6efd;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="height:100vh;">
    <div class="card shadow p-4">

        <h3 class="text-center mb-3">Company Login</h3>
        <p class="text-center text-muted mb-4">Authorized users only</p>

        <?php if ($message != "") { ?>
            <div class="alert alert-danger text-center">
                <?php echo $message; ?>
            </div>
        <?php } ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <!-- DEMO CREDENTIALS -->
        <div class="demo-box text-center p-3 mt-4">
            <strong>Demo Login </strong><br>
            Username: <b>admin</b><br>
            Password: <b>admin123</b><br>

        </div>

    </div>
</div>

</body>
</html>
