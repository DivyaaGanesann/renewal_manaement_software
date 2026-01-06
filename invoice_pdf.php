<?php
session_start();
if (!isset($_SESSION['admin'])) exit('Unauthorized');

require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include 'config.php';

// ---------------- FETCH INVOICE ----------------
if (!isset($_GET['id'])) exit("Invoice ID missing");
$id = base64_decode($_GET['id']);

$inv = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT i.*, d.customer_name, d.whatsapp
    FROM invoice i
    LEFT JOIN domain_list d ON i.domain_id = d.id
    WHERE i.id='$id'
"));

if (!$inv) exit("Invoice not found");

// ---------------- FETCH ITEMS ----------------
$items = json_decode($inv['items'], true) ?: [];

// ---------------- DOMPDF OPTIONS ----------------
$options = new Options();
$options->set('isRemoteEnabled', true);

// Set temp folder (writable)
$tmpDir = __DIR__ . '/tmp';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
$options->set('tempDir', $tmpDir);

$dompdf = new Dompdf($options);

// ---------------- BASE64 SVG LOGO ----------------
$logoPath = __DIR__ . '/images/logo.svg';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $svgData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode($svgData);
}

// ---------------- HTML TEMPLATE ----------------
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body{ font-family: DejaVu Sans, sans-serif; font-size:12px; color:#000; }
/* Modified styles for the new table header structure */
.header-table{ width:100%; border-bottom:2px solid #1b75bc; padding-bottom:10px; border-collapse: collapse; }
/* Set top alignment for all content inside the header cells */
.company-info-cell{ vertical-align: top; text-align: left; }
.invoice-details-cell{ vertical-align: top; text-align: right; }

.company{ font-size:16px; font-weight:bold; }
.address{ font-size:11px; }
/* Increased size and added color for emphasis */
.invoice-title{ 
    font-size:24px; 
    font-weight:bold; 
    color: #1b75bc; 
    margin: 0; /* Remove default margin */
    padding: 0; /* Remove default padding */
} 

/* Existing styles */
.table{ width:100%; border-collapse:collapse; margin-top:15px; }
.table th{ background:#000; color:#fff; padding:6px; border:1px solid #000; }
.table td{ padding:6px; border:1px solid #000; }
.gray{ background:#c0c0c0; }
.text-right{ text-align:right; }
.footer{ position:fixed; bottom:20px; width:100%; border-top:2px solid #1b75bc; font-size:11px; text-align:center; padding-top:5px; }
</style>
</head>
<body>

<table class="header-table">
    <tr>
<td class="company-info-cell" style="width: 70%;">
    <div style="margin-bottom: 5px;">
        <div style="float:left; margin-right: 15px;">
            <?php if($logoBase64): ?>
                <img src="<?= $logoBase64 ?>" style="height:50px; display: block;">
            <?php endif; ?>
        </div>
        <div style="overflow: hidden; width: auto; padding-top: 10px;">
            <div class="invoice-title">Winzone Softech</div>
        </div>
    </div>
    
    <div style="clear: both; margin-top: 5px;">
<div class="address" style="font-size:11px; color:#555; margin-bottom:20px;">
    No. 7E, Eswari Complex, College Road,
    Karaikudi – 630002, Tamilnadu – India
</div>

    </div>
</td>

        <td class="invoice-details-cell" style="width: 30%;">
            <div class="invoice-title">Invoice</div>
            <div style="font-size:14px;">
                <b>Invoice No:</b> #IN<?= date('Y') ?>00<?= str_pad($inv['id'], 2, '0', STR_PAD_LEFT) ?>
            </div>
        </td>
    </tr>
	
</table>

<br>

<table width="100%">
<tr>
<td>
<b>To:</b> <?= htmlspecialchars($inv['customer_name'] ?? 'Customer') ?><br>

</td>
<td class="text-right">
<b>Date:</b> <?= date('d-m-Y') ?><br>

</tr>
</table>

<table class="table">
<thead>
<tr>
    <th width="8%">S.No</th>
    <th width="62%">Items & Description</th>
    <th width="30%">Amount</th>
</tr>
</thead>
<tbody>
<?php
$subTotal = 0;
foreach ($items as $i => $it):
    $subTotal += (float)$it['amount'];
?>
<tr>
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($it['name']) ?></td>
    <td class="text-right"><?= number_format($it['amount'], 2) ?></td>
</tr>
<?php endforeach; ?>

<tr class="gray">
    <td colspan="2" class="text-right"><b>Sub Total</b></td>
    <td class="text-right"><?= number_format($subTotal, 2) ?></td>
</tr>

<?php if (!empty($inv['gst'])): ?>
<tr class="gray">
    <td colspan="2" class="text-right">CGST</td>
    <td class="text-right"><?= number_format($inv['cgst'], 2) ?></td>
</tr>
<tr class="gray">
    <td colspan="2" class="text-right">SGST</td>
    <td class="text-right"><?= number_format($inv['sgst'], 2) ?></td>
</tr>
<?php endif; ?>

<tr class="gray">
    <td colspan="2" class="text-right"><b>Grand Total</b></td>
    <td class="text-right"><b><?= number_format($inv['amount'], 2) ?></b></td>
</tr>


</tbody>
</table>

<br>

<b>Terms & Conditions</b><br>
Payments once made are non-refundable.

<br><br>

<b>Account Details</b><br>
Company Name: Winzone Softech<br>
A/c No: 00342000005397<br>
Bank: Indian Overseas Bank<br>
IFSC: IOBA0001344<br>
Branch: Karaikudi<br>
GPay / PhonePe: 8807445788

<br><br><br>

<div style="text-align:right;">
<b>Authorized Signatory</b><br>
G. Darvinraj
</div>

<div class="footer">
© 2026 Winzone Softech | www.winzoneinfotech.in | +91 8807445788
</div>

</body>
</html>

<?php
$html = ob_get_clean();

// ---------------- RENDER PDF ----------------
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Invoice_{$inv['id']}.pdf", ["Attachment" => false]);