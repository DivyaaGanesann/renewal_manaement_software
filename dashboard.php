<?php
/**
 * PHP Initialization
 */
session_start();

// Ensure no caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Admin authentication check
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Includes
include 'config.php';   
include 'get_counts.php'; // This now contains all domain, staff, and renewal counts
include 'header.php';   
include 'navbar.php';   

// --- Fetch Paid and Unpaid invoice counts from the database ---
// Note: This logic could also be moved to get_counts.php for centralization.
$invoice_paid_count = 0;
$invoice_unpaid_count = 0;

$res = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM invoice GROUP BY status");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $status = strtolower($row['status']);
        if ($status == 'paid') {
            $invoice_paid_count = $row['total'];
        } elseif ($status == 'unpaid') {
            $invoice_unpaid_count = $row['total'];
        }
    }
}
?>

<style>
/* Reset Container Padding for a cleaner look */
.content-area {
    padding: 20px; 
}
.dashboard-title {
    font-size: 1.8rem;
    font-weight: 300; /* Lighter font weight for a clean header */
    color: #343a40;
    margin-bottom: 25px;
    border-bottom: 1px solid #f1f1f1;
    padding-bottom: 15px;
}
.stat-box {
    border-radius: 10px; /* Slightly reduced rounding */
    padding: 20px;
    background-color: #ffffff; 
    border: none; /* Removed standard border for a cleaner look */
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.05); /* Soft, subtle shadow */
    transition: transform 0.3s, box-shadow 0.3s;
    text-align: left;
    min-height: 160px;
    display: flex; 
    flex-direction: column;
    justify-content: space-between;
}
.stat-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); 
    cursor: pointer;
}
.stat-text-title {
    font-size: 0.9rem; /* Smaller, uppercase title */
    font-weight: 600;
    color: #a0a0a0; /* Very subtle gray */
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}
.stat-text-number {
    font-size: 2.5rem; /* Large, bold number */
    font-weight: 700;
    color: #343a40; 
}
/* Icon Box positioned on the right, large but subtle */
.stat-icon-box {
    width: 70px; /* Larger box */
    height: 70px;
    line-height: 70px;
    border-radius: 50%; /* Circular box */
    text-align: center;
    font-size: 1.8rem; /* Large icon */
    opacity: 0.9; /* Slightly transparent */
    align-self: flex-end; 
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
.stat-content-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
/* Custom border colors */
.border-success-subtle { border-left: 5px solid #28a745 !important; }
.border-warning-subtle { border-left: 5px solid #ffc107 !important; }
.border-danger-subtle { border-left: 5px solid #dc3545 !important; } 
.border-info-subtle { border-left: 5px solid #17a2b8 !important; } /* Added for new card */

/* Placeholder for Sparkline/Progress bar at the bottom of the card */
.stat-footer-progress {
    margin-top: 10px;
    font-size: 0.8rem;
    color: #a0a0a0;
}

a.text-decoration-none { 
    color: inherit; 
}
</style>

<div class="content-area">
<div id="dashboardpage" class="container-fluid py-4">

    
    <div class="stats-wrapper">
  <h4 class="mb-3 mt-4 text-secondary">Administration Dashboard</h4>
        <div class="row g-4 mb-5">

            <div class="col-sm-6 col-lg-3">
                <a href="domainreport.php" class="text-decoration-none">
                    <div class="stat-box">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Total Domains</div>
                                <div class="stat-text-number text-primary"><?php echo number_format($total_domain); ?></div>
                            </div>
                            <div class="stat-icon-box bg-primary-subtle text-primary">
                                <i class="fas fa-globe"></i>
                            </div>
                        </div>
                        <div class="stat-footer-progress">
                            <small>View Detailed Domain Report <i class="fas fa-arrow-circle-right"></i></small>
                        </div>
                    </div>
                </a>
            </div>
<div class="col-sm-6 col-lg-3">
    <a href="domainreport.php?status=active" class="text-decoration-none">
        <div class="stat-box">
            <div class="stat-content-wrapper">
                <div class="stat-content">
                    <div class="stat-text-title">Active Domains</div>
                    <div class="stat-text-number text-success"><?php echo number_format($active_domain); ?></div>
                </div>
                <div class="stat-icon-box bg-success-subtle text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-footer-progress">
                <small class="text-success">Currently active domains in system</small>
            </div>
        </div>
    </a>
</div>


            <div class="col-sm-6 col-lg-3">
                <a href="domainreport.php?status=inactive" class="text-decoration-none">
                    <div class="stat-box">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Inactive Domains</div>
                                <div class="stat-text-number text-danger"><?php echo number_format($inactive_domain); ?></div>
                            </div>
                            <div class="stat-icon-box bg-danger-subtle text-danger">
                                <i class="fas fa-ban"></i>
                            </div>
                        </div>
                        <div class="stat-footer-progress">
                            <small class="text-danger">Action Required: Check Renewals</small>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3">
                <a href="staff.php" class="text-decoration-none">
                    <div class="stat-box">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Active Staff</div>
                                <div class="stat-text-number text-success"><?php echo number_format($active_staff); ?></div>
                            </div>
                            <div class="stat-icon-box bg-success-subtle text-success">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                         <div class="stat-footer-progress">
                            <small>Staff Count Stability</small>
                        </div>
                    </div>
                </a>
            </div>
            

        </div>

        <h4 class="mb-3 mt-4 text-secondary">Financial & Renewal Overview</h4>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-3">
                <a href="invoice_list.php" class="text-decoration-none">
                    <div class="stat-box">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Total Invoices</div>
                                <div class="stat-text-number text-info"><?php echo number_format($invoice_paid_count + $invoice_unpaid_count); ?></div>
                            </div>
                            <div class="stat-icon-box bg-info-subtle text-info">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                        </div>
                        <div class="stat-footer-progress">
                            <small>All records included</small>
                        </div>
                    </div>
                </a>
            </div>


            <div class="col-sm-6 col-lg-3">
                <a href="invoice_list.php?status=paid" class="text-decoration-none">
                    <div class="stat-box border-success-subtle">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Paid Invoices</div>
                                <div class="stat-text-number text-success"><?php echo number_format($invoice_paid_count); ?></div>
                            </div>
                            <div class="stat-icon-box bg-success-subtle text-success">
                                <i class="fas fa-credit-card"></i>
                            </div>
                        </div>
                         <div class="stat-footer-progress">
                            <small class="text-success">Current Month Paid: <?php echo number_format($invoice_paid_count); ?></small>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3">
                <a href="invoice_list.php?status=unpaid" class="text-decoration-none">
                    <div class="stat-box border-warning-subtle">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Unpaid Invoices</div>
                                <div class="stat-text-number text-warning"><?php echo number_format($invoice_unpaid_count); ?></div>
                            </div>
                            <div class="stat-icon-box bg-warning-subtle text-warning">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                        <div class="stat-footer-progress">
                            <small class="text-warning">Immediate Follow-up Required</small>
                        </div>
                    </div>
                </a>
            </div>
                  <div class="col-sm-6 col-lg-3">
                <a href="notification.php" class="text-decoration-none">
                    <div class="stat-box border-danger-subtle">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Domains Renewing This Month</div>
                                <div class="stat-text-number text-danger"><?php echo number_format($this_month_expiry_count); ?></div>
                            </div>
                            <div class="stat-icon-box bg-danger-subtle text-danger">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="stat-footer-progress">
                            <small class="text-danger">High Priority: Follow up on renewals!</small>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <a href="renewal_notification.php?month=2" class="text-decoration-none">
                    <div class="stat-box border-info-subtle">
                        <div class="stat-content-wrapper">
                            <div class="stat-content">
                                <div class="stat-text-title">Renewing in 2 Months</div>
                                <div class="stat-text-number text-info"><?php echo number_format($two_months_expiry_count); ?></div>
                            </div>
                            <div class="stat-icon-box bg-info-subtle text-info">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-footer-progress">
                            <small class="text-info">Prepare early renewal reminders.</small>
                        </div>
                    </div>
                </a>
            </div>      
        </div>
    </div>
</div>
</div>
<?php 
// Include the footer file to close HTML tags
include 'footer.php'; 
?>