<?php
/** @var string $title */
/** @var string $content */
/** @var array|null $menu */
/** @var bool $charts */
$account = \App\Core\Auth::user();
$serverTheme = [
    'theme' => $account['theme'] ?? null,
    'fx'    => isset($account['theme_fx']) ? (bool) $account['theme_fx'] : null,
];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full"<?= !empty($account['calm_mode']) ? ' data-calm="1"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · MyGameList</title>
    <meta name="description" content="<?= e($description ?? 'MyGameList: o banco de dados dos jogos que você zerou, com análises, metas e gamificação para acompanhar tudo o que já terminou.') ?>">
    <meta property="og:site_name" content="MyGameList">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($ogTitle ?? $title . ' · MyGameList') ?>">
    <meta property="og:description" content="<?= e($description ?? 'O banco de dados dos jogos que você zerou, com análises e gamificação.') ?>">
    <meta name="theme-color" content="#0c0c12">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('/assets/favicon.svg')) ?>">
    <!-- Tudo hospedado no próprio site: CSS compilado (bin/build_css.bat), fontes e scripts locais -->
    <link rel="stylesheet" href="<?= e(asset('/assets/css/themes.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('/assets/css/layouts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('/assets/fonts/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
    <script>window.APP = { base: <?= json_encode(\App\Core\Config::get('base_path', '')) ?>, avatars: <?= json_encode((object) ($account ? (new \App\Services\AvatarService())->map() : []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> }; window.__THEME = <?= json_encode($serverTheme) ?>;</script>
    <script src="<?= e(asset('/assets/js/theme.js')) ?>"></script>
    <script src="<?= e(asset('/assets/js/api.js')) ?>"></script>
    <script src="<?= e(asset('/assets/js/ui.js')) ?>"></script>
<?php if ($charts): ?>
    <script defer src="<?= e(asset('/assets/vendor/chart-4.4.7.umd.min.js')) ?>"></script>
<?php endif; ?>
    <script defer src="<?= e(asset('/assets/vendor/alpine-3.14.8.min.js')) ?>"></script>
    <style>[x-cloak]{display:none !important}</style>
</head>
<body class="min-h-full bg-slate-950 font-sans text-slate-200 antialiased">
    <div class="theme-bg" aria-hidden="true"></div>
<?php if ($menu !== null): ?>
    <!-- faixa fina com o progresso do nível: a mesma em todas as telas (some no modo calmo) -->
    <div class="xp-strip xp-ui no-print" role="progressbar" aria-label="Progresso do nível" aria-valuenow="<?= (int) $menu['xp']['progress_pct'] ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= (int) $menu['xp']['progress_pct'] ?>%"></span></div>
<?php endif; ?>
    <?= $content ?>
<?php if ($menu !== null): ?><div class="no-print"><?php require __DIR__ . '/partials/menu.php'; ?></div><?php endif; ?>
    <div class="theme-fx" aria-hidden="true"></div>
</body>
</html>
