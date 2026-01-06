<?php
session_start();
// NOTE: Assuming 'config.php' establishes the $conn variable for database connection.
include 'config.php'; // DB connection (must be correctly configured)

// --- 1. Security: Decode, Validate, and Santitize Invoice ID ---
$invoice_id = 0;
if (isset($_GET['invoice_id'])) {
    // Decode the base64 ID. The 'true' argument ensures it returns false on failure.
    $decoded_id = base64_decode($_GET['invoice_id'], true);
    // Check if decoding was successful and the result is a numeric string
    if ($decoded_id !== false && is_numeric($decoded_id)) {
        // Cast to integer to secure against unexpected input
        $invoice_id = (int) $decoded_id;
    }
}

$invoice = null;

// --- 2. Database Fetch using Prepared Statement ---
if ($invoice_id > 0) {
    // Select invoice (i) and customer (d) data using a LEFT JOIN
    $stmt = $conn->prepare("
        SELECT 
            i.id, i.domain_name, i.amount, i.status, i.expiry_date, i.items, 
            d.customer_name, d.whatsapp
        FROM invoice i
        LEFT JOIN domain_list d ON i.domain_name = d.domain_name
        WHERE i.id = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $invoice_id); // Bind the integer parameter
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $invoice = $result->fetch_assoc();
            // Standardize status for reliable comparison (e.g., PAID vs. Paid)
            $invoice['status'] = trim(strtoupper($invoice['status'])); 
        }
        $stmt->close();
    } else {
        // Professional error handling (e.g., logging) would go here
        // $invoice remains null, triggering the error message below.
    }
}

// --- 3. Helper Function for Status Badge Styling ---
function get_status_badge_class($status) {
    switch ($status) {
        case 'PAID':
            return 'bg-success';
        case 'PENDING':
            return 'bg-warning text-dark';
        case 'OVERDUE':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= htmlspecialchars($invoice['id'] ?? 'N/A'); ?> | Payment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa; /* Light gray background */
        }
        .invoice-card {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .item-list li {
            border-bottom: 1px dashed #eee;
            padding: 5px 0;
        }
        .item-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-lg-8 col-xl-7">

<?php if ($invoice): ?>
            <div class="card invoice-card">
                <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 text-primary">
                        <i class="fas fa-file-invoice me-2"></i> Invoice #<?= htmlspecialchars($invoice['id']); ?>
                    </h3>
                    <span class="badge <?= get_status_badge_class($invoice['status']); ?> fs-6 py-2 px-3">
                        <?= htmlspecialchars($invoice['status']); ?>
                    </span>
                </div>
                
                <div class="card-body p-4">
                    <div class="row mb-4 border-bottom pb-3">
                        <div class="col-md-6">
                            <h5 class="text-muted">Customer Details</h5>
                            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($invoice['customer_name'] ?: 'N/A'); ?></p>
                            <p class="mb-0"><strong>Domain:</strong> <?= htmlspecialchars($invoice['domain_name'] ?: 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5 class="text-muted">Payment Due</h5>
                            <p class="mb-1"><strong>Due Date:</strong> <span class="text-danger"><?= htmlspecialchars($invoice['expiry_date'] ?: 'N/A'); ?></span></p>
                            <p class="mb-0"><strong>Contact:</strong> 
                                <?php if ($invoice['whatsapp']): ?>
                                    WhatsApp Available
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <h5 class="mb-3"><i class="fas fa-list-alt me-2"></i> Items Included</h5>
                    <ul class="list-unstyled item-list">
                    <?php  
                    $items = json_decode($invoice['items'], true);
                    if ($items && is_array($items) && count($items) > 0) {
                        foreach($items as $item) {
                            $name = htmlspecialchars($item['name'] ?? 'Item');
                            // Ensure amount is float for number_format
                            $amount = (float)($item['amount'] ?? 0.00); 
                            echo '<li class="d-flex justify-content-between">';
                            echo '<span>' . $name . '</span>';
                            echo '<span>₹' . number_format($amount, 2) . '</span>';
                            echo '</li>';
                        }
                    } else {
                        echo '<li>No detailed items found for this invoice.</li>';
                    }
                    ?>
                    </ul>
                    <hr>

                    <div class="text-end mt-4">
                        <h4 class="text-dark">
                            <strong>Total Amount Due:</strong> 
                            <span class="text-primary ms-3">₹<?= number_format((float)$invoice['amount'], 2); ?></span>
                        </h4>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-end p-4">
                    <?php if($invoice['status'] !== 'PAID'): ?>
                        <a href="process_payment.php?invoice_id=<?= base64_encode($invoice['id']); ?>" class="btn btn-primary btn-lg px-4 shadow-sm">
                            <i class="fas fa-credit-card me-2"></i> Proceed to Payment
                        </a>
                    <?php else: ?>
                        <button class="btn btn-success btn-lg px-4" disabled>
                            <i class="fas fa-check-circle me-2"></i> Payment Confirmed
                        </button>
                    <?php endif; ?>
                </div>
            </div>


<?php else: ?>
            <div class="alert alert-danger text-center shadow">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Error</h4>
                <p class="mb-0">The requested invoice was not found or the link is invalid.</p>
            </div>
<?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>