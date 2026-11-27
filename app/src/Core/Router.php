<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, array $handler, bool $auth = false): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'auth');
    }

    public function dispatch(Request $req): Response
    {
        $allowed = [];
        foreach ($this->routes as $r) {
            if (!preg_match($r['regex'], $req->path, $m)) {
                continue;
            }
            if ($r['method'] !== $req->method) {
                $allowed[] = $r['method'];
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            if ($req->method !== 'GET') {
                Csrf::verify($req);
            }
            if ($r['auth']) {
                Auth::require();
            }
            [$class, $action] = $r['handler'];
            return (new $class())->$action($req, $params);
        }

        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            throw new HttpException(405, 'Método não permitido.');
        }
        throw new HttpException(404, 'Rota não encontrada.');
    }
}
