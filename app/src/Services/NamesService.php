<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, HttpException, Str};

/**
 * "Organizar nomes": os textos livres (sistema, gênero, desenvolvedora, publisher) acabam com variações
 * ("PS2" / "Playstation 2", "Capcom" / "Capcom Co., Ltd."). Aqui o usuário junta variações em um nome só
 * (ou renomeia). A mudança vale só para os dados dele; as tabelas de nomes são compartilhadas.
 */
final class NamesService
{
    private const KINDS = [
        'platform'  => ['table' => 'platforms', 'max' => 60,  'refs' => [['games', 'platform_id'], ['backlog_games', 'platform_id'], ['dropped_games', 'platform_id']], 'unique' => true,  'fuzzy' => false],
        'genre'     => ['table' => 'genres',    'max' => 50,  'refs' => [['games', 'genre_id'], ['backlog_games', 'genre_id'], ['dropped_games', 'genre_id']],          'unique' => false, 'fuzzy' => true],
        'developer' => ['table' => 'companies', 'max' => 100, 'refs' => [['games', 'developer_id']],                                                                        'unique' => false, 'fuzzy' => true],
        'publisher' => ['table' => 'companies', 'max' => 100, 'refs' => [['games', 'publisher_id']],                                                                        'unique' => false, 'fuzzy' => true],
    ];

    /** Apelidos conhecidos de sistemas (comparados depois de tirar acentos e símbolos). */
    private const PLATFORM_ALIASES = [
        'playstation' => 'ps', 'supernintendo' => 'snes', 'superfamicom' => 'snes', 'nintendoentertainmentsystem' => 'nes', 'famicom' => 'nes',
        'nintendo64' => 'n64', 'nintendogamecube' => 'gamecube', 'gc' => 'gamecube', 'megadrive' => 'genesis', 'segagenesis' => 'genesis', 'segamegadrive' => 'genesis',
        'nintendoswitch' => 'switch', 'nintendods' => 'nds', 'ds' => 'nds', 'nintendo3ds' => '3ds', 'microsoftwindows' => 'pc', 'windows' => 'pc', 'computador' => 'pc',
        'xbox360' => 'x360', 'xboxone' => 'xone', 'xboxseriesx' => 'xsx', 'segasaturn' => 'saturn', 'segadreamcast' => 'dreamcast',
        'gameboyadvance' => 'gba', 'gameboycolor' => 'gbc', 'gameboy' => 'gb',
    ];
    private const COMPANY_NOISE = ['co', 'ltd', 'inc', 'corp', 'corporation', 'company', 'llc', 'limited', 'entertainment', 'games', 'game', 'studios', 'studio', 'interactive', 'software', 'ltda', 'sa', 'gmbh'];

    public function __construct(private LookupService $lookup = new LookupService()) {}

    /** @return array{items: list<array>, suggestions: list<array>} */
    public function list(int $userId, string $kind): array
    {
        $k = $this->kind($kind);
        $label = ['games' => 'games', 'backlog_games' => 'backlog', 'dropped_games' => 'dropped'];

        $items = [];
        foreach ($k['refs'] as [$table, $column]) {
            foreach (Database::all("SELECT l.id, l.name, COUNT(*) AS n FROM `$table` t JOIN `{$k['table']}` l ON l.id = t.`$column` WHERE t.user_id = ? GROUP BY l.id, l.name", [$userId]) as $r) {
                $items[$r['id']] ??= ['id' => (int) $r['id'], 'name' => $r['name'], 'games' => 0, 'backlog' => 0, 'dropped' => 0];
                $items[$r['id']][$label[$table]] += (int) $r['n'];
            }
        }
        $items = array_values($items);
        foreach ($items as &$i) {
            $i['total'] = $i['games'] + $i['backlog'] + $i['dropped'];
        }
        unset($i);
        usort($items, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return ['items' => $items, 'suggestions' => $this->suggest($kind, $items)];
    }

    /** @return array{moved: int, skipped: list<array{title:string,reason:string}>, name: string} */
    public function merge(int $userId, string $kind, array $fromIds, mixed $toName): array
    {
        $k = $this->kind($kind);
        $name = Str::clean($toName);
        if ($name === '' || mb_strlen($name) > $k['max']) {
            throw new HttpException(422, 'Informe o nome final (até ' . $k['max'] . ' caracteres).', ['to' => 'Nome inválido.']);
        }
        $from = array_values(array_unique(array_filter(array_map('intval', $fromIds), fn($i) => $i > 0)));
        if (!$from) {
            throw new HttpException(422, 'Escolha ao menos um nome para juntar.', ['from' => 'Nada selecionado.']);
        }

        $targetId = $this->lookup->findOrCreate($k['table'], $name);
        $from = array_values(array_diff($from, [$targetId]));
        $moved = 0;
        $skipped = [];

        if ($from) {
            $in = implode(',', array_fill(0, count($from), '?'));
            Database::transaction(function () use ($userId, $k, $targetId, $from, $in, &$moved, &$skipped) {
                foreach ($k['refs'] as [$table, $column]) {
                    $ignore = $k['unique'] ? 'IGNORE ' : '';
                    $stmt = Database::query("UPDATE {$ignore}`$table` SET `$column` = ? WHERE user_id = ? AND `$column` IN ($in)", [$targetId, $userId, ...$from]);
                    $moved += $stmt->rowCount();
                    if ($k['unique']) { // sobraram linhas que não puderam mudar: já existe o mesmo jogo no nome de destino
                        foreach (Database::all("SELECT title FROM `$table` WHERE user_id = ? AND `$column` IN ($in) LIMIT 20", [$userId, ...$from]) as $r) {
                            $skipped[] = ['title' => $r['title'], 'reason' => 'Já existe o mesmo jogo (título e ano) com o nome de destino.'];
                        }
                    }
                }
            });
            $this->dropUnused($k['table'], $from);
        }
        return ['moved' => $moved, 'skipped' => $skipped, 'name' => $name];
    }

    /** Listas fictícias, com variações de propósito, para a página de exemplo. */
    public function demo(): array
    {
        $sets = [
            'platform' => [['PS2', 14, 1, 0], ['Playstation 2', 6, 0, 0], ['Sony PlayStation 2', 1, 0, 0], ['Switch', 9, 2, 1], ['Nintendo Switch', 5, 0, 0], ['PC', 22, 3, 2], ['Windows', 3, 0, 0], ['SNES', 8, 0, 0], ['Super Nintendo', 2, 0, 0], ['GameCube', 7, 0, 0], ['PS5', 12, 4, 1]],
            'genre' => [['RPG', 20, 3, 1], ['Aventura', 15, 1, 0], ['Aventuras', 2, 0, 0], ['Ação', 18, 2, 1], ['Survival Horror', 6, 0, 0], ['Plataforma', 9, 1, 0], ['Plataformas', 3, 0, 0], ['Roguelike', 4, 1, 1]],
            'developer' => [['Capcom', 12, 0, 0], ['Capcom Co., Ltd.', 3, 0, 0], ['Nintendo', 11, 0, 0], ['Nintendo EPD', 4, 0, 0], ['FromSoftware', 6, 0, 0], ['From Software', 2, 0, 0], ['Supergiant Games', 3, 0, 0], ['Supergiant', 1, 0, 0]],
            'publisher' => [['Capcom', 14, 0, 0], ['Square Enix', 7, 0, 0], ['Square Enix Co.', 2, 0, 0], ['Bandai Namco', 6, 0, 0], ['Bandai Namco Entertainment', 3, 0, 0], ['Sony Interactive Entertainment', 8, 0, 0]],
        ];
        $out = [];
        foreach ($sets as $kind => $rows) {
            $items = [];
            foreach ($rows as $i => [$name, $g, $b, $d]) {
                $items[] = ['id' => $i + 1, 'name' => $name, 'games' => $g, 'backlog' => $b, 'dropped' => $d, 'total' => $g + $b + $d];
            }
            usort($items, fn($x, $y) => strcasecmp($x['name'], $y['name']));
            $out[$kind] = ['items' => $items, 'suggestions' => $this->suggest($kind, $items)];
        }
        return $out;
    }

    // ---------- Sugestões de duplicados ----------

    private function suggest(string $kind, array $items): array
    {
        $k = self::KINDS[$kind];
        $canon = [];
        foreach ($items as $i) {
            $canon[$i['id']] = $this->canon($kind, $i['name']);
        }
        $parent = array_combine(array_column($items, 'id'), array_column($items, 'id')) ?: [];
        $find = function (int $x) use (&$parent, &$find): int {
            return $parent[$x] === $x ? $x : ($parent[$x] = $find($parent[$x]));
        };
        $n = count($items);
        for ($a = 0; $a < $n; $a++) {
            for ($b = $a + 1; $b < $n; $b++) {
                $x = $canon[$items[$a]['id']];
                $y = $canon[$items[$b]['id']];
                $same = $x !== '' && $x === $y;
                if (!$same && $k['fuzzy'] && min(strlen($x), strlen($y)) >= 7 && levenshtein($x, $y) <= 1) {
                    $same = true;
                }
                if ($same) {
                    $parent[$find($items[$a]['id'])] = $find($items[$b]['id']);
                }
            }
        }
        $groups = [];
        foreach ($items as $i) {
            $groups[$find($i['id'])][] = $i;
        }
        $out = [];
        foreach ($groups as $members) {
            if (count($members) < 2) {
                continue;
            }
            usort($members, fn($p, $q) => $q['total'] <=> $p['total'] ?: strlen($p['name']) <=> strlen($q['name']));
            $out[] = ['ids' => array_column($members, 'id'), 'names' => array_column($members, 'name'), 'suggested' => $members[0]['name']];
        }
        return $out;
    }

    private function canon(string $kind, string $name): string
    {
        $s = mb_strtolower($name);
        $s = strtr($s, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'í' => 'i', 'ï' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', '&' => ' and ']);
        if ($kind === 'developer' || $kind === 'publisher') {
            $tokens = preg_split('/[^a-z0-9]+/', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $kept = array_values(array_filter($tokens, fn($t) => !in_array($t, self::COMPANY_NOISE, true)));
            return implode('', $kept ?: $tokens);
        }
        $s = (string) preg_replace('/[^a-z0-9]+/', '', $s);
        if ($kind === 'platform') {
            if (isset(self::PLATFORM_ALIASES[$s])) {
                return self::PLATFORM_ALIASES[$s];
            }
            $s = (string) preg_replace('/^(sony|microsoft)/', '', $s);
            $s = (string) preg_replace('/^playstation/', 'ps', $s);   // "Sony Playstation 2" -> ps2
            $s = (string) preg_replace('/^nintendo(?!64)/', '', $s);
            $s = self::PLATFORM_ALIASES[$s] ?? $s;
        }
        return $s;
    }

    // ---------- Internos ----------

    private function kind(string $kind): array
    {
        return self::KINDS[$kind] ?? throw new HttpException(422, 'Tipo inválido.', ['kind' => 'Use platform, genre, developer ou publisher.']);
    }

    /** Apaga da tabela de nomes os que ninguém mais usa (o banco recusa se ainda houver referência). */
    private function dropUnused(string $table, array $ids): void
    {
        foreach ($ids as $id) {
            try {
                Database::query("DELETE FROM `$table` WHERE id = ?", [$id]);
            } catch (\PDOException) {
                // ainda em uso por outro usuário ou por outra linha: mantém
            }
        }
    }
}
