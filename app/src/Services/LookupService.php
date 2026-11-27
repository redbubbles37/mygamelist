<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, Str};

final class LookupService
{
    public const TABLES = ['platforms', 'genres', 'companies'];

    /** Busca pelo nome normalizado ou cria. Retorna o id. */
    public function findOrCreate(string $table, string $name): int
    {
        $this->assertTable($table);
        $name = Str::clean($name);
        Database::query("INSERT OR IGNORE INTO `$table` (name, name_key) VALUES (?, ?)", [$name, Str::key($name)]);
        return (int) Database::value("SELECT id FROM `$table` WHERE name_key = ?", [Str::key($name)]);
    }

    /** Sugestões para autocomplete. */
    public function search(string $table, string $q, int $limit = 10): array
    {
        $this->assertTable($table);
        $like = '%' . addcslashes(Str::key($q), '%_\\') . '%';
        return array_column(
            Database::all("SELECT name FROM `$table` WHERE name_key LIKE ? ORDER BY name LIMIT " . max(1, min($limit, 50)), [$like]),
            'name'
        );
    }

    private function assertTable(string $table): void
    {
        if (!in_array($table, self::TABLES, true)) {
            throw new \InvalidArgumentException("Tabela inválida: $table");
        }
    }
}
