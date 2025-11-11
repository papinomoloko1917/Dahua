<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$user = $_ENV['DAHUA_USER'] ?? 'admin';
$pass = $_ENV['DAHUA_PASS'] ?? '';
$ip = $_ENV['DAHUA_IP'] ?? '10.10.0.181';

echo "Мониторинг SMD событий на камере $ip\n";
echo "Нажмите Ctrl+C для остановки\n\n";

$lastEventTime = time();

function checkSMDEvents($ip, $user, $pass)
{
    // Подписываемся на события SMD
    $url = "http://$ip/cgi-bin/eventManager.cgi?action=attach&codes=[SmartMotionHuman,SmartMotionVehicle]";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);

    // Обработка событий в реальном времени
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($ip, $user, $pass) {
        static $buffer = '';
        $buffer .= $data;

        // Проверяем наличие события SMD
        if (
            strpos($buffer, 'SmartMotionHuman') !== false ||
            strpos($buffer, 'SmartMotionVehicle') !== false
        ) {

            echo "[" . date('Y-m-d H:i:s') . "] SMD событие обнаружено!\n";

            // Делаем снимок
            $snapshot_url = "http://$ip/cgi-bin/snapshot.cgi?channel=1&subtype=0";
            $ch2 = curl_init($snapshot_url);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($ch2, CURLOPT_USERPWD, "$user:$pass");
            $image = curl_exec($ch2);
            curl_close($ch2);

            if (strlen($image) > 1000) {
                $dir = dirname(__DIR__) . '/dahua_images';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $filename = $dir . '/smd_' . date('Y-m-d_H-i-s') . '.jpg';
                file_put_contents($filename, $image);
                echo "Снимок сохранён: $filename (размер: " . strlen($image) . " байт)\n\n";
            } else {
                echo "Ошибка получения снимка\n\n";
            }

            // Очищаем буфер
            $buffer = '';
        }

        // Ограничиваем размер буфера
        if (strlen($buffer) > 10000) {
            $buffer = substr($buffer, -5000);
        }

        return strlen($data);
    });

    curl_exec($ch);
    curl_close($ch);
}

// Запускаем мониторинг
checkSMDEvents($ip, $user, $pass);
