<?php
header('Content-Type: application/json');

$csvFile = __DIR__ . '/Accredited-Registrars.csv';
$search  = strtolower(trim($_GET['q'] ?? ''));

$registrars = [];
$limit = 100; // 🔥 show only first 100 when empty search

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    fgetcsv($handle); // skip header

    while (($row = fgetcsv($handle)) !== FALSE) {
        $name = trim($row[0] ?? '');

        // Filter only when user types
        if ($search !== '' && stripos($name, $search) === false) {
            continue;
        }

        $registrars[] = [
            'id'      => $name,
            'name'    => $name,
            'country' => $row[2] ?? '',
            'website' => $row[4] ?? ''
        ];

        // ⚡ LIMIT results for empty search
        if ($search === '' && count($registrars) >= $limit) {
            break;
        }
    }

    fclose($handle);
}

echo json_encode(['status' => true, 'data' => $registrars]);
