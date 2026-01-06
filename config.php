<?php
// ---------------- DATABASE CONNECTION ----------------
$host = "localhost";
$user = "root";
$pass = "";
$db   = "renewal";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// ---------------- RAZORPAY CONFIG ----------------
if (!defined('RAZORPAY_KEY_ID')) {
    define('RAZORPAY_KEY_ID', 'rzp_test_Rsd3O1lKsct2PN');
}

if (!defined('RAZORPAY_KEY_SECRET')) {
    define('RAZORPAY_KEY_SECRET', 'yunExhfteAKKIrkU1X2KyPM1');
}

if (!defined('RAZORPAY_CURRENCY')) {
    define('RAZORPAY_CURRENCY', 'INR');
}


?>
