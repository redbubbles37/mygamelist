<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Config, Database, HttpException};

/**
 * A imagem de cada usuário (tela "Quem vai jogar?", menu, perfil e ranking). Pode ser uma foto enviada, recortada em
 * quadrado e guardada na pasta de dados, ou um dos ícones prontos. Sem nenhuma, aparece a inicial do nome.
 */
final class AvatarService
{
    public const PRESETS = ['🎮', '🕹️', '👾', '🐉', '🧙', '🥷', '🤖', '🧟', '🦊', '🐺', '🐱', '🐸', '⚔️', '🏎️', '🚀', '👑'];
    private const SIZE = 384;
    private const FILE_RE = '#^uploads/avatars/[a-f0-9]{32}\.(webp|jpg)$#';

    public function __construct(private UploadService $uploads = new UploadService()) {}

    /**
     * Valida a escolha sem gravar nada (assim um erro na imagem não deixa uma conta pela metade).
     * @return array{preset?: string, bytes?: string, ext?: string}|null null = nada escolhido
     */
    public function prepare(?array $file, mixed $preset): ?array
    {
        if ($file !== null) {
            try {
                $img = $this->uploads->process($file, 'upload.avatar');
            } catch (HttpException $e) {
                throw new HttpException(422, 'Imagem inválida.', ['avatar' => $e->errors['cover'] ?? $e->getMessage()]);
            }
            return $this->square($img['bytes']);
        }
        $preset = is_string($preset) ? $preset : '';
        if ($preset === '') {
            return null;
        }
        if (!in_array($preset, self::PRESETS, true)) {
            throw new HttpException(422, 'Ícone inválido.', ['avatar' => 'Escolha um dos ícones da lista.']);
        }
        return ['preset' => $preset];
    }

    /** Grava a escolha (null = voltar para a inicial do nome) e apaga a foto anterior. */
    public function save(int $userId, ?array $choice): ?array
    {
        $old = Database::value('SELECT avatar FROM users WHERE id = ?', [$userId]);
        $value = null;
        if (isset($choice['preset'])) {
            $value = 'preset:' . $choice['preset'];
        } elseif (isset($choice['bytes'])) {
            $dir = $this->dir();
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                throw new \RuntimeException('Não foi possível criar a pasta de imagens.');
            }
            $name = bin2hex(random_bytes(16)) . '.' . $choice['ext'];
            if (file_put_contents("$dir/$name", $choice['bytes']) === false) {
                throw new \RuntimeException('Falha ao gravar a imagem.');
            }
            $value = "uploads/avatars/$name";
        }
        Database::query('UPDATE users SET avatar = ? WHERE id = ?', [$value, $userId]);
        $this->deleteFile(is_string($old) ? $old : null);
        return $this->present($value);
    }

    public function deleteFile(?string $value): void
    {
        if ($value !== null && preg_match(self::FILE_RE, $value)) {
            $file = $this->dir() . '/' . basename($value);
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /** Formato usado nas telas: {img: url} ou {icon: emoji}; null = mostrar a inicial. */
    public function present(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (str_starts_with($value, 'preset:')) {
            $icon = substr($value, 7);
            return in_array($icon, self::PRESETS, true) ? ['icon' => $icon] : null;
        }
        return preg_match(self::FILE_RE, $value) && is_file($this->dir() . '/' . basename($value))
            ? ['img' => Config::get('base_path', '') . '/' . $value]
            : null;
    }

    /** Os usuários deste computador, para a tela de entrada. */
    public function users(): array
    {
        return array_map(fn(array $u) => [
            'username' => $u['username'], 'level' => (int) $u['level'], 'avatar' => $this->present($u['avatar']),
        ], Database::all('SELECT username, level, avatar FROM users ORDER BY username'));
    }

    /** @return array<string, array> nome de usuário => imagem (só quem escolheu uma) */
    public function map(): array
    {
        $out = [];
        foreach ($this->users() as $u) {
            if ($u['avatar'] !== null) {
                $out[$u['username']] = $u['avatar'];
            }
        }
        return $out;
    }

    private function dir(): string
    {
        return Config::get('data_dir') . '/uploads/avatars';
    }

    /** Recorta o centro em quadrado e reduz. */
    private function square(string $bytes): array
    {
        $src = imagecreatefromstring($bytes);
        if ($src === false) {
            throw new HttpException(422, 'Imagem inválida.', ['avatar' => 'Não foi possível ler a imagem.']);
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $size = min(self::SIZE, $side);
        $dst = imagecreatetruecolor($size, $size);
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), $size, $size, $side, $side);
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($dst, null, 88);
            $ext = 'webp';
        } else {
            imagejpeg($dst, null, 90);
            $ext = 'jpg';
        }
        return ['bytes' => (string) ob_get_clean(), 'ext' => $ext];
    }
}
