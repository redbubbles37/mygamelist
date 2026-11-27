<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    private function __construct(
        private int $status,
        private ?array $body = null,
        private ?string $html = null,
        private ?string $location = null,
        private ?string $file = null,
        private array $fileHeaders = [],
    ) {}

    public static function json(array $data, int $status = 200): self
    {
        return new self($status, $data);
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, html: $html);
    }

    public static function redirect(string $location): self
    {
        return new self(302, location: $location);
    }

    /** Arquivo para baixar (CSV, JSON...). Nunca guardado em cache. */
    public static function download(string $content, string $filename, string $mime): self
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        return new self(200, file: $content, fileHeaders: [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $safe . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    public static function error(int $status, string $message, array $errors = []): self
    {
        $body = ['error' => $message];
        if ($errors) {
            $body['errors'] = $errors;
        }
        return new self($status, $body);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('X-Content-Type-Options: nosniff');
        if ($this->file !== null) {
            foreach ($this->fileHeaders as $name => $value) {
                header("$name: $value");
            }
            echo $this->file;
        } elseif ($this->location !== null) {
            header('Location: ' . $this->location);
        } elseif ($this->html !== null) {
            header('Content-Type: text/html; charset=utf-8');
            echo $this->html;
        } elseif ($this->body !== null) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
    }
}
