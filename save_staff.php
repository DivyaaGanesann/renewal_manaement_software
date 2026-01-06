<?php
include 'config.php'; // DB connection

if($_SERVER['REQUEST_METHOD']=='POST'){
    $id       = $_POST['id'];
    $name     = $_POST['name'];
    $role     = $_POST['role'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $status   = $_POST['status'];

    if($id){ // Update
        if(!empty($password)){
            $pass_hash = password_hash($password,PASSWORD_BCRYPT);
            $sql = "UPDATE staff SET name='$name', role='$role', password='$pass_hash', status='$status' WHERE id='$id'";
        } else {
            $sql = "UPDATE staff SET name='$name', role='$role', status='$status' WHERE id='$id'";
        }
    } else { // Insert
        $pass_hash = password_hash($password,PASSWORD_BCRYPT);
        $sql = "INSERT INTO staff(name,role,username,password,status) VALUES('$name','$role','$username','$pass_hash','$status')";
    }

    mysqli_query($conn,$sql);
    header("Location: staff.php");
    exit;
}

?>
