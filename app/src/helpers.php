<?php
declare(strict_types=1);

use App\Core\Config;

/** Escapa texto para HTML (inclui atributos). */
function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL de um arquivo de public/ com ?v=<data de modificação>: o navegador guarda em cache e renova sozinho quando o arquivo muda. */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/public' . $path;
    return url($path) . '?v=' . (is_file($file) ? filemtime($file) : 0);
}

/** URL absoluta ao caminho base do app, ex.: url('/login') => /mygamelist/login */
function url(string $path = '/'): string
{
    return Config::get('base_path', '') . $path;
}

/**
 * Ícone de interface (busca, fechar, mais…): o mesmo desenho e a mesma espessura em todas as telas.
 * Emojis continuam sendo usados dentro do conteúdo (títulos, selos, notas); isto é só para os controles.
 */
function icon(string $name, string $class = 'h-4 w-4', string $stroke = '2'): string
{
    static $paths = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'close'  => '<path d="M6 6l12 12M18 6L6 18"/>',
        'plus'   => '<path d="M12 5v14M5 12h14"/>',
        'trash'  => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . e($stroke) . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}
