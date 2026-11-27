<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Config, Database, HttpException, Str};

/**
 * Screenshots dos jogos: galeria por jogo (ou jogo do backlog) e anexos das notas do diário.
 * Cada imagem é reprocessada (sem EXIF), limitada a 1920 px no lado maior e salva em WebP, com uma miniatura de 480 px.
 */
final class ShotService
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    public const MAX_PER_GAME = 300;
    private const MAX_SIDE = 1920;
    private const THUMB_W = 480;
    private const OWNERS = ['games' => ['games', 'game_id'], 'backlog' => ['backlog_games', 'backlog_id'], 'dropped' => ['dropped_games', 'dropped_id']];
    private const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    // ---------- Leitura ----------

    public function list(int $userId, string $kind, int $ownerId): array
    {
        [, $col] = $this->owner($userId, $kind, $ownerId);
        return array_map(fn($r) => $this->format($r), Database::all(
            "SELECT s.id, s.note_id, s.path, s.width, s.height, s.caption, s.created_at, n.note_date
             FROM game_shots s LEFT JOIN game_notes n ON n.id = s.note_id
             WHERE s.user_id = ? AND s.`$col` = ? ORDER BY s.id DESC", [$userId, $ownerId]
        ));
    }

    /** Screenshots de várias notas de uma vez (para o diário). @return array<int, list<array>> por note_id */
    public function forNotes(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($noteIds), '?'));
        $out = [];
        foreach (Database::all("SELECT id, note_id, path, width, height, caption, created_at FROM game_shots WHERE note_id IN ($in) ORDER BY id", array_values($noteIds)) as $r) {
            $out[(int) $r['note_id']][] = $this->format($r);
        }
        return $out;
    }

    /** Screenshots de vários jogos (para o livro). @return array<int, list<array>> por game_id */
    public function forGames(int $userId, int $limitEach = 4): array
    {
        $out = [];
        foreach (Database::all('SELECT id, game_id, note_id, path, width, height, caption, created_at FROM game_shots WHERE user_id = ? AND game_id IS NOT NULL ORDER BY id', [$userId]) as $r) {
            $gid = (int) $r['game_id'];
            if (count($out[$gid] ?? []) < $limitEach) {
                $out[$gid][] = $this->format($r);
            }
        }
        return $out;
    }

    // ---------- Escrita ----------

    /** Recebe um ou vários arquivos (campo "shots" ou "shots[]"). Grava os válidos e devolve também os erros por arquivo. */
    public function upload(int $userId, string $kind, int $ownerId, array $files, mixed $noteId, mixed $caption): array
    {
        [, $col] = $this->owner($userId, $kind, $ownerId);
        $files = $this->normalizeFiles($files);
        if (!$files) {
            throw new HttpException(422, 'Escolha ao menos uma imagem.', ['shots' => 'Nenhum arquivo.']);
        }
        if (count($files) > 10) {
            throw new HttpException(422, 'Envie no máximo 10 imagens por vez.', ['shots' => 'Arquivos demais.']);
        }
        $count = (int) Database::value("SELECT COUNT(*) FROM game_shots WHERE `$col` = ?", [$ownerId]);
        if ($count + count($files) > self::MAX_PER_GAME) {
            throw new HttpException(422, 'Limite de ' . self::MAX_PER_GAME . ' screenshots por jogo.', ['shots' => 'Limite atingido.']);
        }

        $note = null;
        if ($noteId !== null && $noteId !== '') {
            $note = (int) $noteId;
            if (Database::one("SELECT id FROM game_notes WHERE id = ? AND user_id = ? AND `$col` = ?", [$note, $userId, $ownerId]) === null) {
                throw new HttpException(422, 'Nota inválida.', ['note_id' => 'A nota não é deste jogo.']);
            }
        }
        $caption = Str::clean($caption);
        $caption = $caption === '' ? null : mb_substr($caption, 0, 200);

        $saved = $errors = [];
        foreach ($files as $f) {
            try {
                $img = $this->process($f);
                $path = $this->store($img);
                Database::query(
                    "INSERT INTO game_shots (user_id, `$col`, note_id, path, width, height, caption) VALUES (?,?,?,?,?,?,?)",
                    [$userId, $ownerId, $note, $path, $img['w'], $img['h'], $caption]
                );
                $saved[] = $this->format(Database::one('SELECT id, note_id, path, width, height, caption, created_at FROM game_shots WHERE id = ?', [Database::insertId()]));
            } catch (HttpException $e) {
                $errors[] = ['name' => (string) ($f['name'] ?? 'arquivo'), 'message' => array_values($e->errors)[0] ?? $e->getMessage()];
            }
        }
        return ['saved' => $saved, 'errors' => $errors];
    }

    public function update(int $userId, int $id, array $in): array
    {
        $this->owned($userId, $id);
        $caption = Str::clean($in['caption'] ?? '');
        if (mb_strlen($caption) > 200) {
            throw new HttpException(422, 'A legenda pode ter até 200 caracteres.', ['caption' => 'Máximo de 200 caracteres.']);
        }
        Database::query('UPDATE game_shots SET caption = ? WHERE id = ? AND user_id = ?', [$caption === '' ? null : $caption, $id, $userId]);
        return $this->format(Database::one('SELECT id, note_id, path, width, height, caption, created_at FROM game_shots WHERE id = ?', [$id]));
    }

    public function delete(int $userId, int $id): void
    {
        $row = $this->owned($userId, $id);
        Database::query('DELETE FROM game_shots WHERE id = ? AND user_id = ?', [$id, $userId]);
        $this->deleteFiles([$row['path']]);
    }

    /** Caminhos das imagens de um jogo/backlog/usuário (para apagar os arquivos depois que as linhas somem em cascata). */
    public static function pathsOf(string $column, int $value): array
    {
        if (!in_array($column, ['game_id', 'backlog_id', 'dropped_id', 'user_id'], true)) {
            return [];
        }
        return array_column(Database::all("SELECT path FROM game_shots WHERE `$column` = ?", [$value]), 'path');
    }

    public function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_string($path) || !preg_match('#^uploads/shots/([a-f0-9]{32})\.webp$#', $path, $m)) {
                continue;
            }
            foreach ([$m[1] . '.webp', $m[1] . '_t.webp'] as $name) {
                $file = $this->dir() . '/' . $name;
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    // ---------- Internos ----------

    private function owner(int $userId, string $kind, int $ownerId): array
    {
        $o = self::OWNERS[$kind] ?? throw new HttpException(404, 'Não encontrado.');
        if (Database::one("SELECT id FROM `{$o[0]}` WHERE id = ? AND user_id = ?", [$ownerId, $userId]) === null) {
            throw new HttpException(404, 'Jogo não encontrado.');
        }
        return $o;
    }

    private function owned(int $userId, int $id): array
    {
        $row = Database::one('SELECT id, path FROM game_shots WHERE id = ? AND user_id = ?', [$id, $userId]);
        if ($row === null) {
            throw new HttpException(404, 'Screenshot não encontrada.');
        }
        return $row;
    }

    /** $_FILES['shots'] pode vir como um arquivo só ou como lista (shots[]). */
    private function normalizeFiles(array $f): array
    {
        if (!isset($f['name'])) {
            return [];
        }
        if (!is_array($f['name'])) {
            return ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$f];
        }
        $out = [];
        foreach (array_keys($f['name']) as $i) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $out[] = ['name' => $f['name'][$i], 'type' => $f['type'][$i] ?? '', 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i] ?? 0];
            }
        }
        return $out;
    }

    /** @return array{main: string, thumb: string, w: int, h: int} */
    private function process(array $file): array
    {
        $err = fn(string $m) => new HttpException(422, 'Imagem inválida.', ['shots' => $m]);
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw $err(in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'Arquivo grande demais.' : 'Falha no envio.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp)) {
            throw $err('Envio inválido.');
        }
        if (filesize($tmp) > self::MAX_BYTES) {
            throw $err('Máximo de 8 MB por imagem.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $info = @getimagesize($tmp);
        if (!in_array($mime, self::MIMES, true) || $info === false || $info[0] > 10000 || $info[1] > 10000) {
            throw $err('Use uma imagem JPEG, PNG ou WebP válida.');
        }
        $src = @imagecreatefromstring((string) file_get_contents($tmp));
        if ($src === false) {
            throw $err('Não foi possível ler a imagem.');
        }
        $main = $this->resize($src, self::MAX_SIDE, self::MAX_SIDE, 82);
        $thumb = $this->resize($src, self::THUMB_W, self::THUMB_W * 2, 72);
        imagedestroy($src);
        return ['main' => $main['bytes'], 'thumb' => $thumb['bytes'], 'w' => $main['w'], 'h' => $main['h']];
    }

    private function resize(\GdImage $src, int $maxW, int $maxH, int $quality): array
    {
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, $maxW / $w, $maxH / $h);
        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 0, 0, 0));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start();
        imagewebp($dst, null, $quality);
        $bytes = (string) ob_get_clean();
        imagedestroy($dst);
        return ['bytes' => $bytes, 'w' => $nw, 'h' => $nh];
    }

    private function dir(): string
    {
        return dirname(Config::get('upload.dir')) . '/shots';
    }

    private function store(array $img): string
    {
        $dir = $this->dir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException('Não foi possível criar a pasta de screenshots.');
        }
        if (!is_file("$dir/.htaccess")) {
            @copy(dirname($dir) . '/covers/.htaccess', "$dir/.htaccess");
        }
        $name = bin2hex(random_bytes(16));
        if (file_put_contents("$dir/$name.webp", $img['main']) === false || file_put_contents("$dir/{$name}_t.webp", $img['thumb']) === false) {
            throw new \RuntimeException('Falha ao gravar a screenshot.');
        }
        return "uploads/shots/$name.webp";
    }

    private function format(array $r): array
    {
        $base = (string) Config::get('base_path', '');
        return [
            'id' => (int) $r['id'], 'note_id' => $r['note_id'] === null ? null : (int) $r['note_id'],
            'note_date' => $r['note_date'] ?? null,
            'url' => $base . '/' . $r['path'], 'thumb' => $base . '/' . preg_replace('/\.webp$/', '_t.webp', $r['path']),
            'width' => (int) $r['width'], 'height' => (int) $r['height'], 'caption' => $r['caption'],
            'created_at' => substr((string) $r['created_at'], 0, 10),
        ];
    }
}
