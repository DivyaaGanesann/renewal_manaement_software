<?php
session_start();
include 'config.php';

if (!isset($_GET['id'])) {
    die("Invalid Invoice");
}

$invoice_id = base64_decode($_GET['id']);

$sql = "
    SELECT 
        i.id, i.amount, i.description, i.domain_name, i.status,
        dl.customer_name, dl.whatsapp, dl.email
    FROM invoice i
    LEFT JOIN domain_list dl ON i.domain_name = dl.domain_name
    WHERE i.id = '$invoice_id'
    LIMIT 1
";
$res = mysqli_query($conn, $sql);
$invoice = mysqli_fetch_assoc($res);

if (!$invoice) {
    die("Invoice not found");
}

if (strtolower($invoice['status']) === 'paid') {
    die("Invoice already paid");
}

$amount_paise = $invoice['amount'] * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Invoice #<?= $invoice['id'] ?></title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h5>Invoice Payment</h5>
                </div>
                <div class="card-body">

                    <p><strong>Domain:</strong> <?= htmlspecialchars($invoice['domain_name']) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($invoice['description']) ?></p>
                    <p><strong>Amount:</strong>
                        <span class="fw-bold text-danger fs-4">₹<?= $invoice['amount'] ?></span>
                    </p>

                    <hr>

                    <p><strong>Name:</strong> <?= htmlspecialchars($invoice['customer_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($invoice['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($invoice['whatsapp']) ?></p>

                    <div class="text-center mt-4">
                        <button id="rzp-button1" class="btn btn-success btn-lg">
                            Pay ₹<?= $invoice['amount'] ?>
                        </button>
                    </div>

                    <div id="payment-status" class="mt-3 text-center"></div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
var options = {
    key: "<?= RAZORPAY_KEY_ID ?>",
    amount: <?= $amount_paise ?>,
    currency: "INR",
    name: "Winzone Softech",
    description: "Invoice #<?= $invoice['id'] ?>",
    image: "images/logo.png",
    handler: function (response) {
        $.post("verify_payment.php", {
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_signature: response.razorpay_signature,
            invoice_id: "<?= $invoice_id ?>"
        }, function () {
            $("#payment-status").html(
                "<div class='alert alert-success'>Payment Successful</div>"
            );
        });
    },
    prefill: {
        name: "<?= htmlspecialchars($invoice['customer_name']) ?>",
        email: "<?= htmlspecialchars($invoice['email']) ?>",
        contact: "<?= htmlspecialchars($invoice['whatsapp']) ?>"
    },
    theme: {
        color: "#3399cc"
    }
};

var rzp = new Razorpay(options);
document.getElementById("rzp-button1").onclick = function (e) {
    rzp.open();
    e.preventDefault();
};
</script>

</body>
</html>
