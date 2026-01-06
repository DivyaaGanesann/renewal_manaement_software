<?php
// check_domain_status.php
include 'config.php'; 

header('Content-Type: application/json');

if (!isset($_POST['domain_name'])) {
    echo json_encode(['error' => 'No domain specified']);
    exit;
}
$domainName = trim($_POST['domain_name']);
function getWebsiteOperationalStatus($domainName) {
    $url = $domainName;
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true); 
    curl_setopt($ch, CURLOPT_NOBODY, true); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout: Increased slightly for network resilience
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch); 
    return $httpCode;
}
$httpCode = getWebsiteOperationalStatus($domainName);
$response = [
    'domain' => $domainName,
    'httpCode' => $httpCode,
    'operationalStatusText' => 'Check Failed',
    'cardClass' => 'card-warning',
    'badgeText' => 'N/A',
    'badgeClass' => 'bg-warning text-dark'
];
if ($httpCode >= 200 && $httpCode < 300) {
    $response['operationalStatusText'] = 'Active';
    $response['cardClass'] = 'card-active';
    $response['badgeText'] = 'Active ('.$httpCode.')';
    $response['badgeClass'] = 'bg-success';
} elseif ($httpCode == 404) {
    $response['operationalStatusText'] = 'Expired';
    $response['cardClass'] = 'card-expired';
    $response['badgeText'] = 'Expired (404 Not Found)';
    $response['badgeClass'] = 'bg-danger';
} elseif ($httpCode >= 400 && $httpCode < 600 || $httpCode == 0) {
    $response['operationalStatusText'] = 'Expired';
    $response['cardClass'] = 'card-expired';
    $response['badgeText'] = 'Error (Code: '.$httpCode.')';
    $response['badgeClass'] = 'bg-danger';
} else {
    $response['operationalStatusText'] = 'Warning';
    $response['cardClass'] = 'card-warning';
    $response['badgeText'] = 'Warning (Code: '.$httpCode.')';
    $response['badgeClass'] = 'bg-warning text-dark';
}

echo json_encode($response);
?>