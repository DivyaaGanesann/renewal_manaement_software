<?php
// verify_payment.php

session_start();
include 'config.php'; // Contains database connection and RAZORPAY_KEY_SECRET

use Razorpay\Api\Api;

// Ensure this is an AJAX POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$razorpay_order_id = $_POST['razorpay_order_id'];
$razorpay_payment_id = $_POST['razorpay_payment_id'];
$razorpay_signature = $_POST['razorpay_signature'];
$invoice_id = $_POST['invoice_id'];

// 1. Initialize Razorpay API
$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

// 2. Verify Signature
try {
    $attributes = array(
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    );

    $api->utility->verifyPaymentSignature($attributes);

    // Signature is valid! Update the invoice status in the database.

    // 3. Get Payment Details (Optional, but good for logging)
    $payment = $api->payment->fetch($razorpay_payment_id);
    $amount_paid = $payment->amount / 100; // Convert back to currency unit

    // 4. Update Database
    $update_sql = "
        UPDATE invoice 
        SET 
            status = 'Paid', 
            payment_id = ?, 
            paid_date = NOW(),
            payment_details = ? 
        WHERE id = ? 
        AND status != 'Paid'
    ";
    
    $stmt = mysqli_prepare($conn, $update_sql);
    $payment_details_json = json_encode(['order_id' => $razorpay_order_id, 'signature' => $razorpay_signature]);
    mysqli_stmt_bind_param($stmt, "ssi", $razorpay_payment_id, $payment_details_json, $invoice_id);
    mysqli_stmt_execute($stmt);

    // 5. Send Success Response
    echo json_encode(['status' => 'success', 'message' => 'Payment verified and invoice updated.']);

} catch (\Exception $e) {
    // Signature is NOT valid! Log this and send failure response.
    error_log("Razorpay Signature Verification Failed: " . $e->getMessage() . " for Invoice ID: " . $invoice_id);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payment signature verification failed.']);
}
?>