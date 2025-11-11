<?php

require_once __DIR__ . '/../vendor/autoload.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/') {
    require_once __DIR__ . '/index.php';
} else if ($uri === '/vpn') {
    require_once __DIR__ . '/ishosting/ishostingVPN.php';
} else if ($uri === '/account') {
    require_once __DIR__ . '/ishosting/ishostingAccount.php';
} else {
    require_once __DIR__ . $uri . '.php';
}
