<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Загружаем .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Логируем все входящие запросы для отладки
$log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'body' => file_get_contents('php://input'),
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

file_put_contents('/tmp/webhook_log.txt', json_encode($log, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Простое логирование каждого обращения
file_put_contents('/tmp/webhook_calls.log', date('Y-m-d H:i:s') . " - Webhook вызван\n", FILE_APPEND);

// Функция для получения снимка с камеры
function takeSnapshot($ip, $user, $pass, $channel = 1)
{
    $url = "http://$ip/cgi-bin/snapshot.cgi?channel=$channel&subtype=0";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $image = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Логируем результат запроса
    $debug = [
        'timestamp' => date('Y-m-d H:i:s'),
        'url' => $url,
        'http_code' => $http_code,
        'image_size' => strlen($image),
        'curl_error' => $curl_error,
        'user' => $user,
        'ip' => $ip
    ];
    file_put_contents('/tmp/snapshot_debug.txt', json_encode($debug, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

    if ($http_code == 200 && strlen($image) > 1000) {
        $dir = dirname(__DIR__) . '/dahua_images';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $dir . '/smd_' . date('Y-m-d_H-i-s') . '.jpg';

        $write_result = file_put_contents($filename, $image);

        // Логируем результат записи
        $write_debug = [
            'timestamp' => date('Y-m-d H:i:s'),
            'filename' => $filename,
            'write_result' => $write_result,
            'file_exists' => file_exists($filename),
            'dir_writable' => is_writable($dir)
        ];
        file_put_contents('/tmp/write_debug.txt', json_encode($write_debug, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

        return $filename;
    }

    return false;
}

// Обрабатываем webhook от камеры
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {

    $user = $_ENV['DAHUA_USER'] ?? 'admin';
    $pass = $_ENV['DAHUA_PASS'] ?? '';
    $ip = $_ENV['DAHUA_IP'] ?? '10.10.0.181';

    // Делаем снимок
    $snapshot = takeSnapshot($ip, $user, $pass);

    if ($snapshot) {
        // Сохраняем информацию о событии
        $event = [
            'timestamp' => date('Y-m-d H:i:s'),
            'snapshot' => $snapshot,
            'type' => 'SMD',
            'request' => $log
        ];

        file_put_contents('/tmp/smd_events.json', json_encode($event, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        // Отправляем успешный ответ камере
        http_response_code(200);
        echo json_encode(['status' => 'success', 'snapshot' => $snapshot]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to capture snapshot']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
