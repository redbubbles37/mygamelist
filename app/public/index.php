<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/src/autoload.php';
require $root . '/src/helpers.php';

use App\Core\{Config, HttpException, Request, Response, Router};

Config::load(require $root . '/config/config.php');
date_default_timezone_set((string) Config::get('timezone', 'UTC'));

// sessões e registro de erros na pasta de dados do usuário
$dataDir = (string) Config::get('data_dir');
foreach (["$dataDir/sessoes", "$dataDir/logs"] as $d) {
    if (!is_dir($d)) {
        @mkdir($d, 0777, true);
    }
}
ini_set('session.save_path', "$dataDir/sessoes");
ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 30));
ini_set('log_errors', '1');
ini_set('error_log', "$dataDir/logs/erros.log");
ini_set('display_errors', '0');

/** Navegador pedindo uma página (e não a API): mostramos uma tela do site em vez de JSON. */
function wantsPage(): bool
{
    return !str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/api/') && str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html');
}

function errorPage(int $status, string $message): Response
{
    $title = $status === 404 ? 'Fase não encontrada' : ($status === 401 ? 'Entre para continuar' : 'Algo deu errado');
    $render = static function (string $file, array $vars): string {
        extract($vars, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    };
    $dir = dirname(__DIR__) . '/views';
    $content = $render("$dir/error.php", ['status' => $status, 'message' => $message, 'signedIn' => (static function (): bool { try { return \App\Core\Auth::user() !== null; } catch (Throwable) { return false; } })()]);
    return Response::html($render("$dir/layout.php", ['title' => $title, 'content' => $content, 'menu' => null, 'charts' => false, 'description' => null]), $status);
}

try {
    $router = new Router();
    require $root . '/routes/api.php';
    $response = $router->dispatch(new Request());
} catch (HttpException $e) {
    $response = wantsPage() ? errorPage($e->status, $e->getMessage()) : Response::error($e->status, $e->getMessage(), $e->errors);
} catch (Throwable $e) {
    error_log((string) $e);
    $msg = Config::get('debug') ? $e->getMessage() : 'Erro interno.';
    $response = wantsPage() ? errorPage(500, $msg) : Response::error(500, $msg);
}

$response->send();
