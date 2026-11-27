<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Banco local em um arquivo SQLite (na pasta de dados do usuário). Na primeira abertura o arquivo é criado e o
 * esquema de database/schema é aplicado sozinho; versões novas entram como 002_*.sql, 003_*.sql…
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $file = (string) Config::get('db.file');
            $dir = dirname($file);
            if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException("Não foi possível criar a pasta de dados: $dir");
            }
            $pdo = new PDO('sqlite:' . $file, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::register($pdo);
            $pdo->exec('PRAGMA busy_timeout = 8000');
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
            self::migrate($pdo);
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }

    /** Aplica os arquivos de database/schema que ainda não entraram (o número do arquivo é a versão). */
    private static function migrate(PDO $pdo): void
    {
        $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
        $files = glob(dirname(__DIR__, 2) . '/database/schema/*.sql') ?: [];
        sort($files);
        foreach ($files as $file) {
            $n = (int) basename($file);
            if ($n <= $version) {
                continue;
            }
            $pdo->exec('BEGIN IMMEDIATE');
            try {
                // outra requisição pode ter aplicado enquanto esperávamos o bloqueio
                if ((int) $pdo->query('PRAGMA user_version')->fetchColumn() < $n) {
                    $pdo->exec((string) file_get_contents($file));
                    $pdo->exec("PRAGMA user_version = $n");
                }
                $pdo->exec('COMMIT');
            } catch (\Throwable $e) {
                $pdo->exec('ROLLBACK');
                throw $e;
            }
        }
    }

    // ---------- Compatibilidade: o que o código usa e o SQLite não traz pronto ----------

    /** Texto sem maiúsculas e sem acentos, para comparar, ordenar e buscar ("Pokémon" = "pokemon"). */
    public static function fold(string $s): string
    {
        static $cache = [];
        static $map = null;
        if (isset($cache[$s])) {
            return $cache[$s];
        }
        if ($map === null) {
            $map = [];
            foreach (['a' => 'áàâãäåāăą', 'c' => 'çćĉċč', 'd' => 'ďđ', 'e' => 'éèêëēĕėęě', 'g' => 'ĝğġģ', 'h' => 'ĥħ', 'i' => 'íìîïĩīĭįı',
                      'j' => 'ĵ', 'k' => 'ķ', 'l' => 'ĺļľŀł', 'n' => 'ñńņňŉ', 'o' => 'óòôõöøōŏő', 'r' => 'ŕŗř', 's' => 'śŝşšș', 't' => 'ţťŧț',
                      'u' => 'úùûüũūŭůűų', 'w' => 'ŵ', 'y' => 'ýÿŷ', 'z' => 'źżž'] as $plain => $accented) {
                foreach (mb_str_split($accented) as $ch) {
                    $map[$ch] = $plain;
                }
            }
        }
        $out = rtrim(strtr(mb_strtolower($s, 'UTF-8'), $map));
        if (count($cache) < 20000) {
            $cache[$s] = $out;
        }
        return $out;
    }

    private static function register(PDO $pdo): void
    {
        $pdo->sqliteCreateCollation('UCI', fn(string $a, string $b): int => self::fold($a) <=> self::fold($b));

        // LIKE sem ligar para acentos; "\" escapa % e _ (como no banco antigo)
        $like = function ($pattern, $value, $escape = '\\'): ?int {
            if ($pattern === null || $value === null) {
                return null;
            }
            $re = '';
            $chars = mb_str_split(self::fold((string) $pattern));
            for ($i = 0, $n = count($chars); $i < $n; $i++) {
                $c = $chars[$i];
                if ($c === $escape && $i + 1 < $n) {
                    $re .= preg_quote($chars[++$i], '~');
                } elseif ($c === '%') {
                    $re .= '.*';
                } elseif ($c === '_') {
                    $re .= '.';
                } else {
                    $re .= preg_quote($c, '~');
                }
            }
            return preg_match('~^' . $re . '$~su', self::fold((string) $value)) ? 1 : 0;
        };
        $pdo->sqliteCreateFunction('like', $like, -1);

        $part = fn(int $from, int $len) => fn($d) => $d === null || $d === '' ? null : (int) substr((string) $d, $from, $len);
        $pdo->sqliteCreateFunction('YEAR', $part(0, 4), 1);
        $pdo->sqliteCreateFunction('MONTH', $part(5, 2), 1);
        $pdo->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'), 0);
        $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'), 0);
        // FIELD(valor, a, b, c): posição do valor na lista (0 se não estiver)
        $pdo->sqliteCreateFunction('FIELD', function (...$args): int {
            $v = array_shift($args);
            if ($v === null) {
                return 0;
            }
            foreach ($args as $i => $a) {
                if ($a !== null && self::fold((string) $a) === self::fold((string) $v)) {
                    return $i + 1;
                }
            }
            return 0;
        }, -1);
    }

    // ---------- Consultas ----------

    /** Os parâmetros vão com o tipo certo: o SQLite não converte texto em número ao comparar com COUNT(), YEAR() etc. */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $i = 0;
        foreach ($params as $key => $value) {
            $name = is_int($key) ? ++$i : $key;
            if (is_int($value) || is_bool($value)) {
                $st->bindValue($name, (int) $value, PDO::PARAM_INT);
            } elseif ($value === null) {
                $st->bindValue($name, null, PDO::PARAM_NULL);
            } else {
                $st->bindValue($name, (string) $value, PDO::PARAM_STR);
            }
        }
        $st->execute();
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::query($sql, $params);
        $row = $st->fetch();
        $st->closeCursor();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $st = self::query($sql, $params);
        $v = $st->fetchColumn();
        $st->closeCursor();
        return $v === false ? null : $v;
    }

    public static function insertId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /** O erro é de valor repetido (índice único)? */
    public static function isDuplicate(\PDOException $e): bool
    {
        return str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }

    /** BEGIN IMMEDIATE: a transação já nasce com o direito de gravar, evitando conflito no meio do caminho. */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->exec('BEGIN IMMEDIATE');
        try {
            $result = $fn();
            $pdo->exec('COMMIT');
            return $result;
        } catch (\Throwable $e) {
            $pdo->exec('ROLLBACK');
            throw $e;
        }
    }
}
