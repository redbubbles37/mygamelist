<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Config, HttpException};

final class UploadService
{
    private const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Valida e reprocessa a imagem (remove EXIF/metadados). Retorna ['bytes' => ..., 'ext' => ...].
     * $profile escolhe os limites em config ('upload' para capas, 'upload.banner' para imagens largas, como a da franquia).
     */
    public function process(array $file, string $profile = 'upload'): array
    {
        $cfg = fn(string $k) => Config::get("$profile.$k") ?? Config::get("upload.$k");
        $err = fn(string $m) => new HttpException(422, 'Capa inválida.', ['cover' => $m]);

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw $err(match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo grande demais.',
                default => 'Falha no upload.',
            });
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            throw $err('Upload inválido.');
        }
        if (filesize($tmp) > $cfg('max_bytes')) {
            throw $err('Máximo de ' . ($cfg('max_bytes') / 1048576) . ' MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $info = @getimagesize($tmp);
        if (!in_array($mime, self::MIMES, true) || $info === false || $info[0] > 8000 || $info[1] > 8000) {
            throw $err('Use uma imagem JPEG, PNG ou WebP válida.');
        }

        $src = @imagecreatefromstring((string) file_get_contents($tmp));
        if ($src === false) {
            throw $err('Não foi possível ler a imagem.');
        }

        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, $cfg('max_w') / $w, $cfg('max_h') / $h);
        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // achata transparência
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($dst, null, (int) $cfg('quality'));
            $ext = 'webp';
        } else {
            imagejpeg($dst, null, min(95, (int) $cfg('quality') + 3));
            $ext = 'jpg';
        }
        $bytes = (string) ob_get_clean();

        return ['bytes' => $bytes, 'ext' => $ext];
    }

    /** Grava no disco com nome aleatório. Retorna o caminho relativo a public/. */
    public function store(array $processed): string
    {
        $dir = Config::get('upload.dir');
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException('Não foi possível criar a pasta de uploads.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $processed['ext'];
        if (file_put_contents("$dir/$name", $processed['bytes']) === false) {
            throw new \RuntimeException('Falha ao gravar a capa.');
        }
        return Config::get('upload.url_path') . '/' . $name;
    }

    /** Copia uma capa já salva para um arquivo novo (cada registro tem a sua; apagar um não apaga o outro). */
    public function copy(?string $path): ?string
    {
        if ($path === null || !preg_match('#^' . preg_quote(Config::get('upload.url_path'), '#') . '/[a-f0-9]{32}\.(webp|jpg)$#', $path, $m)) {
            return null;
        }
        $file = Config::get('upload.dir') . '/' . basename($path);
        if (!is_file($file)) {
            return null;
        }
        return $this->store(['bytes' => (string) file_get_contents($file), 'ext' => $m[1]]);
    }

    public function delete(?string $path): void
    {
        if ($path === null || !preg_match('#^' . preg_quote(Config::get('upload.url_path'), '#') . '/[a-f0-9]{32}\.(webp|jpg)$#', $path)) {
            return;
        }
        $file = Config::get('upload.dir') . '/' . basename($path);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function url(?string $path): ?string
    {
        return $path === null ? null : Config::get('base_path', '') . '/' . $path;
    }
}
