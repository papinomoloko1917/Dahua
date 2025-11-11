<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$apiToken = $_ENV['API_TOKEN'] ?? null;
$apiUrl = 'https://api.ishosting.com/vpn/list?query=amnezia&limit=10&offset=0';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Api-Token: $apiToken",
    "Accept: application/json",
    "Accept-Language: en"
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
dump($data);
