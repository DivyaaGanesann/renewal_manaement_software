<?php
// get_counts.php
include_once 'config.php';

// ----------------------------
// HELPER FUNCTION: safely fetch count from query
// ----------------------------
function fetch_count($conn, $query) {
    $res = mysqli_query($conn, $query);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return (int)$row['count_val'];
    }
    return 0;
}

// ----------------------------
// THIS MONTH RENEWAL COUNT
// ----------------------------
$this_month_start = date('Y-m-01'); // first day of this month
$this_month_end   = date('Y-m-t');  // last day of this month

$this_month_expiry_count = fetch_count($conn, "
    SELECT COUNT(*) AS count_val
    FROM domain_list
    WHERE renewal_date BETWEEN '$this_month_start' AND '$this_month_end'
");

// ----------------------------
// 2 MONTHS AHEAD RENEWAL COUNT
// ----------------------------
$two_months_start = date('Y-m-01', strtotime('+2 months'));
$two_months_end   = date('Y-m-t', strtotime('+2 months'));

$two_months_expiry_count = fetch_count($conn, "
    SELECT COUNT(*) AS count_val
    FROM domain_list
    WHERE renewal_date BETWEEN '$two_months_start' AND '$two_months_end'
");

// ----------------------------
// TOTAL DOMAIN COUNT
// ----------------------------
$total_domain = fetch_count($conn, "SELECT COUNT(*) AS count_val FROM domain_list");

// ----------------------------
// INACTIVE DOMAINS (status = 0)
// ----------------------------
$inactive_domain = fetch_count($conn, "SELECT COUNT(*) AS count_val FROM domain_list WHERE status='0'");

// ----------------------------
// ACTIVE DOMAINS (status = 'active')
// ----------------------------
$active_domain = fetch_count($conn, "SELECT COUNT(*) AS count_val FROM domain_list WHERE status='1'");

// ----------------------------
// ACTIVE STAFF COUNT
// ----------------------------
$active_staff = fetch_count($conn, "SELECT COUNT(*) AS count_val FROM staff WHERE status='1'");
