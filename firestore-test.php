<?php

require 'vendor/autoload.php';

$json = json_decode(
    file_get_contents('storage/app/firebase/service-account.json'),
    true
);

$creds = new Google\Auth\Credentials\ServiceAccountCredentials(
    ['https://www.googleapis.com/auth/cloud-platform'],
    $json
);

$firestore = new Google\Cloud\Firestore\FirestoreClient([
    'projectId' => 'laravel-crm-fablead',
    'credentials' => $creds,
    'transport' => 'rest',
]);

echo "CLIENT_OK" . PHP_EOL;

$ref = $firestore->collection('test')->add([
    'message' => 'rest-test',
    'created_at' => date('c'),
]);

echo "WRITE_OK" . PHP_EOL;
echo $ref->name() . PHP_EOL;
