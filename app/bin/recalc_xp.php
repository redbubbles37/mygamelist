<?php
declare(strict_types=1);

// Uso: php bin/recalc_xp.php  (após alterar valores na tabela settings)
require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Config;
use App\Services\XpService;

Config::load(require dirname(__DIR__) . '/config/config.php');

$n = (new XpService())->rebuildAll();
echo "XP recalculado para $n usuário(s).\n";
