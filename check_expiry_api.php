<?php
header('Content-Type: application/json');

$domain = trim($_GET['domain'] ?? '');
if (!$domain) {
    http_response_code(400);
    echo json_encode(['error'=>'No domain provided']);
    exit;
}

/* CLEAN DOMAIN */
$domain = preg_replace('#^https?://#','',$domain);
$domain = preg_replace('#^www\.#','',$domain);

/* DEFAULT RESULT */
$result = [
    'domain_expiry' => 'Unknown',
    'ssl_expiry' => 'Unknown',
    'name_servers' => [], // NS records
    'registrar' => 'Unknown'
];

/* ---------------- DNS CHECK ---------------- */
if (!checkdnsrr($domain,'A') && !checkdnsrr($domain,'AAAA')){
    http_response_code(404);
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

/* ---------------- DOMAIN EXPIRY & REGISTRAR (RDAP) ---------------- */
$tld = substr(strrchr($domain, "."), 1);
$rdapServers = [
    'com' => 'https://rdap.verisign.com/com/v1/domain/',
    'net' => 'https://rdap.verisign.com/net/v1/domain/',
    'org' => 'https://rdap.publicinterestregistry.org/rdap/org/domain/',
    'in'  => 'https://rdap.registry.in/rdap/domain/'
];

if(isset($rdapServers[$tld])){
    $rdapUrl = $rdapServers[$tld] . $domain;

    $ch = curl_init($rdapUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6
    ]);
    $rdap = json_decode(curl_exec($ch), true);
    curl_close($ch);

    /* DOMAIN EXPIRY */
    if(!empty($rdap['events'])){
        foreach($rdap['events'] as $event){
            if($event['eventAction'] === 'expiration'){
                $date = strtotime($event['eventDate']);
                if($date){
                    $result['domain_expiry'] = date('Y-m-d',$date);
                }
            }
        }
    }

    /* REGISTRAR */
    $registrar = 'Unknown';

    if(!empty($rdap['registrar'])){
        if(is_array($rdap['registrar'])){
            $registrar = $rdap['registrar']['name'] ?? implode(', ', $rdap['registrar']);
        } elseif(is_string($rdap['registrar'])){
            $registrar = $rdap['registrar'];
        }
    }
    // fallback: entities array with role "registrar"
    elseif(!empty($rdap['entities']) && is_array($rdap['entities'])){
        foreach($rdap['entities'] as $ent){
            if(!empty($ent['roles']) && in_array('registrar',$ent['roles']) && !empty($ent['vcardArray'][1])){
                foreach($ent['vcardArray'][1] as $v){
                    if($v[0]==='fn' && !empty($v[3])){
                        $registrar = $v[3];
                        break 2;
                    }
                }
            }
        }
    }

    $result['registrar'] = $registrar;
}

/* ---------------- SSL EXPIRY ---------------- */
$context = stream_context_create([
    'ssl'=>[
        'capture_peer_cert'=>true,
        'verify_peer'=>false,
        'verify_peer_name'=>false
    ]
]);

$client = @stream_socket_client(
    "ssl://$domain:443",
    $e,
    $s,
    5,
    STREAM_CLIENT_CONNECT,
    $context
);

if($client){
    $params = stream_context_get_params($client);
    if(!empty($params['options']['ssl']['peer_certificate'])){
        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if(!empty($cert['validTo_time_t'])){
            $sslTs = $cert['validTo_time_t'];
            $result['ssl_expiry'] = date('Y-m-d',$sslTs);
        }
    }
}

/* ---------------- NAME SERVERS ---------------- */
$nsRecords = dns_get_record($domain, DNS_NS);
if(!empty($nsRecords)){
    foreach($nsRecords as $ns){
        if(!empty($ns['target'])){
            $result['name_servers'][] = $ns['target'];
        }
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);
