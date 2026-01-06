<?php
include 'config.php';

$domain_id = (int)($_GET['domain_id'] ?? 0);

$data = [];

$q = $conn->prepare("
    SELECT 
        first_interval_date, first_interval_desc, first_interval_staff,
        second_interval_date, second_interval_desc, second_interval_staff,
        third_interval_date, third_interval_desc, third_interval_staff,
        final_payment_date
    FROM domain_renewal_intervals
    WHERE domain_id = ?
");
$q->bind_param("i", $domain_id);
$q->execute();
$res = $q->get_result();

if ($row = $res->fetch_assoc()) {
    $data = $row;
}

echo json_encode($data);
