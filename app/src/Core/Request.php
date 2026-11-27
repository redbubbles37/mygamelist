<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public readonly string $method;
    public readonly string $path;
    public readonly array $query;
    private ?array $input = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->query  = $_GET;

        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $base = (string) Config::get('base_path', '');
        // Aceita tanto /base/public/... quanto /base/... (via .htaccess da raiz)
        foreach ([$base . '/public', $base] as $prefix) {
            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }
        $path = '/' . trim($path, '/');
        $this->path = $path;
    }

    /** Corpo da requisição: JSON (application/json) ou form/multipart. */
    public function input(): array
    {
        if ($this->input !== null) {
            return $this->input;
        }
        $type = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($type, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = $raw === '' ? [] : json_decode($raw, true);
            if (!is_array($data)) {
                throw new HttpException(400, 'JSON inválido.');
            }
            return $this->input = $data;
        }
        return $this->input = $_POST;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function file(string $name): ?array
    {
        $f = $_FILES[$name] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $f;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
