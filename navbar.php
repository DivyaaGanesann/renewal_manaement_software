<?php
// Start session at the very top
if (!isset($_SESSION)) {
    session_start();
}

// Include config & counts
include 'config.php';
include_once 'get_counts.php';

?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Renewal</div>
    <ul class="nav flex-column mt-4">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="domain_expiry_dashboard.php"><i class="bi bi-calendar-x"></i> Domain Expiry Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="staff.php"><i class="bi bi-person"></i> Staff</a></li>
        <li class="nav-item"><a class="nav-link" href="domain.php"><i class="bi bi-globe2"></i> Add Domain</a></li>
        <li class="nav-item"><a class="nav-link" href="domainlist.php"><i class="bi bi-list-task"></i> Domain Expiry in 2 Months</a></li>
        <li class="nav-item"><a class="nav-link" href="domainreport.php"><i class="bi bi-file-earmark-text"></i> Overall Domain Report</a></li>
        <li class="nav-item"><a class="nav-link" href="renewaltable.php"><i class="bi bi-file-spreadsheet"></i> Renewal Follow Report</a></li>
        <li class="nav-item"><a class="nav-link" href="seo_list.php"><i class="bi bi-card-checklist"></i> SEO List</a></li>
        <li class="nav-item"><a class="nav-link" href="seo_report.php"><i class="bi bi-file-text-fill"></i> Overall SEO Report</a></li>
        <li class="nav-item"><a class="nav-link" href="map_list.php"><i class="bi bi-map"></i> MapList</a></li>
        <li class="nav-item"><a class="nav-link" href="map_report.php"><i class="bi bi-file-earmark-ruled"></i> Overall Map Report</a></li>
        <li class="nav-item"><a class="nav-link" href="ssl.php"><i class="bi bi-shield-lock-fill"></i> SSL</a></li>
        <li class="nav-item"><a class="nav-link" href="ssl_list.php"><i class="bi bi-list-check"></i> SSL List</a></li>
        <li class="nav-item"><a class="nav-link" href="wab.php"><i class="bi bi-chat-dots"></i> WAB</a></li>
        <li class="nav-item"><a class="nav-link" href="wab_list.php"><i class="bi bi-list-check"></i> WAB List</a></li>
        <li class="nav-item"><a class="nav-link" href="invoice.php"><i class="bi bi-receipt-cutoff"></i> Invoice</a></li>
        <li class="nav-item"><a class="nav-link" href="invoice_list.php"><i class="bi bi-file-pdf"></i> Invoice Download</a></li>
        <li class="nav-item"><a class="nav-link" href="discussion.php"><i class="bi bi-chat-dots"></i> Discussion</a></li>
        <li class="nav-item"><a class="nav-link" href="discussionlist.php"><i class="bi bi-person-lines-fill"></i> Discussion List</a></li>
        <li class="nav-item"><a class="nav-link" href="discussionreport.php"><i class="bi bi-files"></i> Discussion Report</a></li>
        <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-gear-fill"></i> Settings</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>
</div>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-left d-flex align-items-center">

        <!-- Two months expiry reminder -->
        <div class="icon-wrapper position-relative me-3">
            <a href="remainder.php" class="icon-link">
                <i class="bi bi-alarm fs-4"></i>
                <?php if (!isset($_SESSION['notification_read']) && !empty($two_months_expiry_count)): ?>
                    <span class="notify-badge"><?php echo $two_months_expiry_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Expiring this month notification -->
        <div class="icon-wrapper position-relative me-3">
            <a href="notification.php" class="icon-link">
                <i class="bi bi-bell fs-4"></i>
                <?php if (!isset($_SESSION['notification_read']) && !empty($this_month_expiry_count)): ?>
                    <span class="notify-badge"><?php echo $this_month_expiry_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Domain Status Check -->
        <div class="icon-wrapper me-3">
            <a href="domain_status.php" class="icon-link">
                <i class="bi bi-clipboard-check fs-4"></i>
            </a>
        </div>

    </div>

    <!-- Admin dropdown -->
<div class="dropdown topbar-right">
    <button type="button"
            class="btn d-flex align-items-center dropdown-toggle border-0 bg-transparent"
            data-bs-toggle="dropdown"
            aria-expanded="false">
        <i class="bi bi-person-circle fs-4 me-2"></i>
        <span class="fw-semibold text-dark">
            <?php echo htmlspecialchars($_SESSION['admin']); ?>
        </span>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow">
        <li>
            <a class="dropdown-item" href="change_password.php">
                <i class="bi bi-key me-2"></i> Change Password
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-danger" href="logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>

</div>
