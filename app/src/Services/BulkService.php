<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/** Edição em massa de jogos do catálogo: trocar um campo em vários de uma vez ou excluir vários. */
final class BulkService
{
    public const MAX_IDS = 500;
    public const FIELDS = ['platform', 'genre', 'developer', 'publisher', 'difficulty', 'rating', 'release_year'];

    public function __construct(
        private LookupService $lookup = new LookupService(),
        private XpService $xp = new XpService(),
        private GameService $games = new GameService(),
    ) {}

    /** @return array{updated: int, skipped: list<array{id:int,title:string,reason:string}>, xp: array} */
    public function update(int $userId, array $ids, string $field, mixed $value): array
    {
        if (!in_array($field, self::FIELDS, true)) {
            throw new HttpException(422, 'Campo inválido.', ['field' => 'Escolha o que deseja alterar.']);
        }
        $rows = $this->owned($userId, $ids);
        [$column, $newValue] = $this->resolve($field, $value);
        if ($field === 'rating' && isset(RatingQuotaService::RULES[$newValue])) {
            $in = implode(',', array_fill(0, count($rows), '?'));
            $already = (int) Database::value("SELECT COUNT(*) FROM games WHERE user_id = ? AND rating = ? AND id IN ($in)", [$userId, $newValue, ...array_column($rows, 'id')]);
            (new RatingQuotaService())->check($userId, (int) $newValue, 0, count($rows) - $already);
        }

        $updated = 0;
        $skipped = [];
        Database::transaction(function () use ($userId, $rows, $field, $column, $newValue, &$updated, &$skipped) {
            foreach ($rows as $g) {
                if ($field === 'release_year' && $newValue > (int) substr($g['finished_at'], 0, 4)) {
                    $skipped[] = ['id' => (int) $g['id'], 'title' => $g['title'], 'reason' => 'O ano de lançamento ficaria depois da finalização.'];
                    continue;
                }
                try {
                    Database::query("UPDATE games SET `$column` = ? WHERE id = ? AND user_id = ?", [$newValue, $g['id'], $userId]);
                } catch (\PDOException $e) {
                    if (!Database::isDuplicate($e)) {
                        throw $e;
                    }
                    $skipped[] = ['id' => (int) $g['id'], 'title' => $g['title'], 'reason' => 'Já existe um jogo igual (mesmo título, sistema e ano).'];
                    continue;
                }
                if ($field === 'difficulty') {
                    $tasks = Database::all('SELECT description, tier FROM game_tasks WHERE game_id = ?', [$g['id']]);
                    $this->xp->rebuildGame($userId, (int) $g['id'], (string) $newValue, $tasks, (bool) $g['used_save_states']);
                }
                $updated++;
            }
        });
        return ['updated' => $updated, 'skipped' => $skipped, 'xp' => $this->progress($userId)];
    }

    /** @return array{deleted: int, xp: array} */
    public function delete(int $userId, array $ids): array
    {
        $rows = $this->owned($userId, $ids);
        foreach ($rows as $g) {
            $this->games->delete($userId, (int) $g['id']);
        }
        return ['deleted' => count($rows), 'xp' => $this->progress($userId)];
    }

    private function progress(int $userId): array
    {
        return $this->xp->progress((int) Database::value('SELECT total_xp FROM users WHERE id = ?', [$userId]));
    }

    private function owned(int $userId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
        if (!$ids) {
            throw new HttpException(422, 'Selecione ao menos um jogo.', ['ids' => 'Nenhum jogo selecionado.']);
        }
        if (count($ids) > self::MAX_IDS) {
            throw new HttpException(422, 'Selecione no máximo ' . self::MAX_IDS . ' jogos por vez.', ['ids' => 'Seleção grande demais.']);
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        return Database::all("SELECT id, title, finished_at, used_save_states FROM games WHERE user_id = ? AND id IN ($in)", [$userId, ...$ids]);
    }

    /** @return array{0: string, 1: string|int|null} coluna e valor já validados */
    private function resolve(string $field, mixed $value): array
    {
        $text = Str::clean($value);
        switch ($field) {
            case 'platform':
            case 'genre':
                $max = $field === 'platform' ? 60 : 50;
                if ($text === '' || mb_strlen($text) > $max) {
                    throw new HttpException(422, 'Informe o novo valor (até ' . $max . ' caracteres).', ['value' => 'Valor inválido.']);
                }
                return [$field . '_id', $this->lookup->findOrCreate($field === 'platform' ? 'platforms' : 'genres', $text)];
            case 'developer':
            case 'publisher':
                if (mb_strlen($text) > 100) {
                    throw new HttpException(422, 'Máximo de 100 caracteres.', ['value' => 'Valor inválido.']);
                }
                return [$field . '_id', $text === '' ? null : $this->lookup->findOrCreate('companies', $text)];
            case 'difficulty':
                if (!in_array($text, ['C', 'B', 'A', 'AA', 'AAA'], true)) {
                    throw new HttpException(422, 'Dificuldade deve ser C, B, A, AA ou AAA.', ['value' => 'Valor inválido.']);
                }
                return ['difficulty', $text];
            case 'rating':
                $n = filter_var($text, FILTER_VALIDATE_INT);
                if ($n === false || $n < 1 || $n > 11) {
                    throw new HttpException(422, 'Nota de 1 a 11.', ['value' => 'Valor inválido.']);
                }
                return ['rating', $n];
            default: // release_year
                $n = filter_var($text, FILTER_VALIDATE_INT);
                if ($n === false || $n < 1950 || $n > (int) date('Y')) {
                    throw new HttpException(422, 'Ano de lançamento inválido.', ['value' => 'Valor inválido.']);
                }
                return ['release_year', $n];
        }
    }
}
