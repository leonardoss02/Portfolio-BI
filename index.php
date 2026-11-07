<?php
if (php_sapi_name() === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($uri, '/.mrphp') === 0) {
        http_response_code(403);
        exit;
    }
    $file = __DIR__ . $uri;
    if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
        return false;
    }
}

require_once 'mrphp.php';
$framework = new MRPHP();
$framework->start();

