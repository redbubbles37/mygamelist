<?php
declare(strict_types=1);

// Roteador do servidor local do PHP (substitui o Apache): entrega os arquivos do programa e as imagens da pasta
// de dados; todo o resto vai para o index.php.
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// o lançador usa este endereço para saber que o servidor que respondeu é o nosso
if ($path === '/__mygamelist') {
    header('Content-Type: text/plain');
    echo 'MyGameList';
    return true;
}

// só atende o próprio computador, mesmo que alguém mude o endereço de escuta
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    return true;
}
// e só pelo endereço local (barra páginas de fora que apontem um domínio para o seu computador)
if (!preg_match('/^(127\.0\.0\.1|localhost)(:\d+)?$/', (string) ($_SERVER['HTTP_HOST'] ?? ''))) {
    http_response_code(403);
    return true;
}

$send = static function (string $file): bool {
    $types = ['css' => 'text/css; charset=utf-8', 'js' => 'application/javascript; charset=utf-8', 'svg' => 'image/svg+xml', 'png' => 'image/png',
              'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'woff2' => 'font/woff2', 'ico' => 'image/x-icon', 'json' => 'application/json'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!isset($types[$ext])) {
        http_response_code(404);
        return true;
    }
    header('Content-Type: ' . $types[$ext]);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=31536000');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return true;
};

// imagens enviadas por você: ficam na pasta de dados
if (preg_match('#^/uploads/(covers|shots|avatars)/([A-Za-z0-9_-]+\.(?:webp|jpg|jpeg|png))$#', $path, $m)) {
    $config = require dirname(__DIR__) . '/config/config.php';
    $file = $config['data_dir'] . '/uploads/' . $m[1] . '/' . $m[2];
    if (is_file($file)) {
        return $send($file);
    }
    http_response_code(404);
    return true;
}

// arquivos do programa (assets)
if (str_starts_with($path, '/assets/')) {
    $base = realpath(__DIR__ . '/assets');
    $file = realpath(__DIR__ . $path);
    if ($file !== false && $base !== false && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file)) {
        return $send($file);
    }
    http_response_code(404);
    return true;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
return true;
