<?php
declare(strict_types=1);

// Programa local: tudo o que é seu (banco, capas, screenshots, sessões) fica na pasta de dados, fora do programa.
// O lançador informa a pasta em MGL_DATA; sem ele, usa %LOCALAPPDATA%\MyGameList.
$data = getenv('MGL_DATA') ?: ((getenv('LOCALAPPDATA') ?: dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'MyGameList');
$data = rtrim(str_replace('\\', '/', $data), '/');

return [
    'debug'     => getenv('MGL_DEBUG') === '1',
    'base_path' => '',
    'timezone'  => getenv('MGL_TIMEZONE') ?: 'America/Sao_Paulo',
    'data_dir'  => $data,
    'db' => [
        'file' => $data . '/mygamelist.db',
    ],
    'upload' => [
        'dir'       => $data . '/uploads/covers',
        'url_path'  => 'uploads/covers',
        'max_bytes' => 2 * 1024 * 1024,
        'max_w'     => 600,
        'max_h'     => 900,
        'quality'   => 82,
        // imagens largas (banner/card da franquia): exibidas com até ~1300 px, o dobro em telas de alta densidade
        'banner'    => [
            'max_bytes' => 8 * 1024 * 1024,
            'max_w'     => 2560,
            'max_h'     => 1600,
            'quality'   => 90,
        ],
        // foto de usuário (o AvatarService recorta o centro em quadrado depois)
        'avatar'    => ['max_bytes' => 8 * 1024 * 1024, 'max_w' => 1600, 'max_h' => 1600, 'quality' => 90],
    ],
];
