<?php
declare(strict_types=1);

// Copia os dados do site antigo (MySQL do XAMPP) para o banco local do programa. Só LÊ o site antigo.
// Uso (com o MySQL do XAMPP ligado):  C:\xampp\php\php.exe importar_do_xampp.php [--substituir]
//   --substituir : se o banco local já tiver contas, guarda uma cópia dele e importa tudo de novo.
$old = 'C:/xampp/htdocs/mygamelist';
$app = dirname(__DIR__) . '/app';
require $app . '/src/autoload.php';

use App\Core\{Config, Database};

Config::load(require $app . '/config/config.php');
date_default_timezone_set((string) Config::get('timezone'));

if (!extension_loaded('pdo_mysql')) {
    exit("Use o PHP do XAMPP (C:\\xampp\\php\\php.exe) para importar.\n");
}
$env = [];
foreach (is_file("$old/.env") ? file("$old/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
    if ($line[0] !== '#' && str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }
}
try {
    $my = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? '127.0.0.1', $env['DB_PORT'] ?? 3306, $env['DB_NAME'] ?? 'mygamelist'),
        $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $e) {
    exit("Não consegui ler o banco antigo. O MySQL do XAMPP está ligado?\n" . $e->getMessage() . "\n");
}

$data = (string) Config::get('data_dir');
$file = (string) Config::get('db.file');
$users = (int) Database::value('SELECT COUNT(*) FROM users');
if ($users > 0) {
    if (!in_array('--substituir', $argv, true)) {
        exit("O banco local já tem $users conta(s) em:\n  $file\nNada foi alterado. Para apagar e importar de novo (guardando uma cópia), rode com --substituir.\n");
    }
    $copy = $data . '/backup-antes-de-importar-' . date('Y-m-d-His') . '.db';
    Database::pdo()->exec('VACUUM INTO ' . Database::pdo()->quote($copy));
    echo "Cópia do banco atual: $copy\n";
}

// ordem que respeita as ligações entre as tabelas
$tables = ['users', 'platforms', 'genres', 'companies', 'settings', 'games', 'game_tasks', 'xp_events', 'game_people', 'franchises', 'franchise_sections',
    'franchise_entries', 'goty_picks', 'retro_top', 'best_picks', 'dropped_games', 'backlog_games', 'game_notes', 'game_shots', 'goals', 'journal_entries', 'menu_usage', 'login_events'];

$pdo = Database::pdo();
Database::transaction(function () use ($pdo, $my, $tables) {
    foreach (array_reverse($tables) as $t) {
        $pdo->exec("DELETE FROM $t");
    }
    $pdo->exec('DELETE FROM login_attempts');
    foreach ($tables as $t) {
        // só as colunas que existem nos dois bancos (o e-mail fica de fora, o que é novo no programa fica vazio, e colunas calculadas se recalculam)
        $oldCols = array_column($my->query("SHOW COLUMNS FROM `$t`")->fetchAll(), 'Field');
        $cols = [];
        foreach ($pdo->query("PRAGMA table_xinfo($t)")->fetchAll() as $c) {
            if ((int) $c['hidden'] === 0 && in_array($c['name'], $oldCols, true)) {
                $cols[] = $c['name'];
            }
        }
        $list = implode(', ', array_map(fn($c) => "`$c`", $cols));
        $insert = "INSERT INTO $t (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        foreach ($my->query("SELECT $list FROM `$t`") as $row) {
            Database::query($insert, array_values($row));
        }
    }
});

// confere as contagens
$bad = 0;
foreach ($tables as $t) {
    $a = (int) $my->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $b = (int) Database::value("SELECT COUNT(*) FROM $t");
    printf("  %-20s antigo %5d | local %5d %s\n", $t, $a, $b, $a === $b ? '' : '<-- DIFERENTE');
    $bad += $a === $b ? 0 : 1;
}
$fk = Database::all('PRAGMA foreign_key_check');
echo 'Ligações entre tabelas: ' . ($fk ? count($fk) . " problema(s)\n" : "ok\n");

// imagens (capas e screenshots)
$copied = 0;
foreach (['covers', 'shots'] as $dir) {
    $from = "$old/public/uploads/$dir";
    $to = "$data/uploads/$dir";
    if (!is_dir($to)) {
        mkdir($to, 0777, true);
    }
    foreach (glob("$from/*.{webp,jpg,jpeg,png}", GLOB_BRACE) ?: [] as $img) {
        $dest = $to . '/' . basename($img);
        if (!is_file($dest) && copy($img, $dest)) {
            $copied++;
        }
    }
}
echo "Imagens copiadas: $copied\n";
echo $bad || $fk ? "ATENÇÃO: confira as diferenças acima.\n" : "Importação concluída.\nBanco local: $file\n";
echo "No programa, é só clicar no seu usuário (não há senha nem e-mail).\n";
