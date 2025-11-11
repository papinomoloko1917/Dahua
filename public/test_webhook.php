<?php

// Тестовый скрипт для проверки webhook без камеры

echo "Симуляция webhook от камеры...\n\n";

// Вызываем наш webhook_receiver
$ch = curl_init('http://localhost/webhook_receiver.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'event' => 'SmartMotionHuman',
    'timestamp' => date('Y-m-d H:i:s')
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n\n";

// Проверяем лог
if (file_exists('/tmp/webhook_log.txt')) {
    echo "=== Последние записи в логе ===\n";
    echo exec('tail -20 /tmp/webhook_log.txt');
}

echo "\n\n=== События SMD ===\n";
if (file_exists('/tmp/smd_events.json')) {
    echo exec('tail -20 /tmp/smd_events.json');
}
