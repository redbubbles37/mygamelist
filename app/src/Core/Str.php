<?php
declare(strict_types=1);

namespace App\Core;

final class Str
{
    /** Remove espaços nas pontas e colapsa espaços internos. Não-escalares viram "". */
    public static function clean(mixed $v): string
    {
        if (!is_scalar($v)) {
            return '';
        }
        return trim((string) preg_replace('/\s+/u', ' ', (string) $v));
    }

    /** Chave de normalização para comparar textos livres ("Resident  Evil 4" == "resident evil 4"). */
    public static function key(string $v): string
    {
        return mb_strtolower(self::clean($v));
    }
}
