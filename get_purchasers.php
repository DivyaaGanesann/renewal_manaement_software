<?php
header('Content-Type: application/json');

$csvFile = __DIR__ . 'accredited_registrars.csv';

$registrars = [];

// Open CSV and read rows
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $header = fgetcsv($handle); // skip the header row
    while (($row = fgetcsv($handle)) !== FALSE) {
        $registrars[] = [
            'id' => $row[0],       // Registrar Name
            'name' => $row[0],
            'country' => $row[2],  // Optional: country
            'website' => $row[4]   // Optional: website
        ];
    }
    fclose($handle);
}

echo json_encode(['status' => true, 'data' => $registrars]);
