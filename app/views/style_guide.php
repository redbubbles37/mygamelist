<?php
/** @var array $me */
$tokens = [['brand-400', 'bg-brand-400'], ['brand-500', 'bg-brand-500'], ['brand-600', 'bg-brand-600'], ['accent', 'bg-accent'], ['ok', 'bg-ok'], ['danger', 'bg-danger'], ['neutral', 'bg-neutral'],
    ['gold-300', 'bg-gold-300'], ['gold-400', 'bg-gold-400'], ['gold-500', 'bg-gold-500'], ['gold-700', 'bg-gold-700'], ['hero', 'bg-hero']];
$slate = [950, 900, 800, 700, 600, 500, 400, 300, 200, 100, 50];
$glossary = [
    ['Painel', 'A página inicial', 'Dashboard'],
    ['zerado / zerar', 'Jogo terminado', 'finalizado, finalização'],
    ['Registrar', 'Botão que cria um jogo zerado', 'Novo jogo'],
    ['Backlog', 'A fila (estados: Quero jogar e Jogando)', 'fila solta, + Fila'],
    ['Dropado', 'Largado no meio do caminho', 'abandonado'],
    ['Franquia', 'Uma saga com seções', 'série'],
    ['Catálogo', 'Todos os seus jogos zerados', 'Meu catálogo, Meus jogos'],
    ['Timeline', 'A página; “linha do tempo” só como gráfico dentro de outras páginas', 'os dois misturados'],
    ['Nota', 'De 1 a 11', 'avaliação'],
];
?>
<main class="page page-normal">
    <?php require __DIR__ . '/partials/crumb.php'; ?>
    <header>
        <h1 class="t-page">Guia visual</h1>
        <p class="page-sub ">Todas as peças oficiais do site, no tema que está ativo. Troque de tema aqui embaixo para conferir se alguma quebra.</p>
        <div class="mt-4 flex flex-wrap gap-2" x-data="{ id: Theme.current, list: Theme.list }" role="group" aria-label="Trocar de tema para conferir">
            <template x-for="t in list" :key="t.id">
                <button type="button" @click="Theme.apply(t.id); id = t.id" class="btn-ghost btn-sm" :class="id === t.id && '!border-brand-400 !text-brand-400'" x-text="t.name"></button>
            </template>
        </div>
        <p class="caption mt-2">Aqui o tema só é trocado para conferir (não é salvo); para escolher de verdade, use Temas no menu.</p>
    </header>

    <section class="mt-10" aria-labelledby="g-cores"><h2 id="g-cores" class="t-section">Cores</h2>
        <p class="caption">Tudo vem de variáveis do tema. O ouro (troféus, GOTY, best of) e o fundo dos heróis também.</p>
        <ul class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
            <?php foreach ($tokens as [$name, $cls]): ?>
            <li><span class="block h-12 rounded-lg ring-1 ring-slate-50/15 <?= $cls ?>"></span><span class="mt-1 block text-xs text-slate-400"><?= $name ?></span></li>
            <?php endforeach; ?>
        </ul>
        <ul class="mt-4 flex overflow-hidden rounded-lg ring-1 ring-slate-50/15">
            <?php foreach ($slate as $k): ?><li class="h-8 flex-1" style="background:rgb(var(--s-<?= $k ?>))" title="slate-<?= $k ?>"></li><?php endforeach; ?>
        </ul>
    </section>

    <section class="mt-10" aria-labelledby="g-tipo"><h2 id="g-tipo" class="t-section">Tipografia</h2>
        <div class="panel mt-3 space-y-4 p-5">
            <p class="kicker">Rótulo (kicker)</p>
            <p class="t-page">Título de página (t-page)</p>
            <p class="t-section">Título de seção (t-section)</p>
            <p class="t-card">Título de cartão (t-card)</p>
            <p class="text-sm text-slate-300">Texto corrido (text-sm). Os pesos oficiais são normal, medium, semibold, bold e black.</p>
            <p class="caption">Legenda (caption)</p>
            <div class="hero-box p-5"><p class="kicker-hero">Rótulo no herói</p><p class="t-hero mt-1">Título de herói (t-hero)</p></div>
        </div>
    </section>

    <section class="mt-10" aria-labelledby="g-btn"><h2 id="g-btn" class="t-section">Botões</h2>
        <div class="panel mt-3 space-y-4 p-5">
            <?php foreach (['btn' => 'Primário', 'btn-ghost' => 'Secundário', 'btn-danger' => 'Perigo'] as $c => $l): ?>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="<?= $c ?> btn-sm"><?= $l ?> pequeno</button>
                <button type="button" class="<?= $c ?>"><?= $l ?></button>
                <button type="button" class="<?= $c ?> btn-lg"><?= $l ?> grande</button>
                <button type="button" class="<?= $c ?>" disabled>Desativado</button>
            </div>
            <?php endforeach; ?>
            <div class="hero-box flex flex-wrap items-center gap-3 p-5"><button type="button" class="btn-light">Botão claro (sobre herói)</button><button type="button" class="btn-ghost !border-white/30 !text-white">Secundário no herói</button></div>
            <div x-data="{ v: 'a' }" class="flex flex-wrap items-center gap-3">
                <div class="seg" role="group" aria-label="Controle segmentado">
                    <?php foreach (['a' => 'Estantes', 'b' => 'Grade', 'c' => 'Lista'] as $k => $l): ?>
                    <button type="button" class="seg-btn" @click="v = '<?= $k ?>'" :aria-pressed="v === '<?= $k ?>'" :class="v === '<?= $k ?>' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'"><?= $l ?></button>
                    <?php endforeach; ?>
                </div>
                <span class="caption">Controle segmentado (abas e modos de exibição)</span>
            </div>
        </div>
    </section>

    <section class="mt-10" aria-labelledby="g-sup"><h2 id="g-sup" class="t-section">Superfícies e estados</h2>
        <div class="mt-3 grid gap-4 md:grid-cols-2">
            <div class="card"><p class="t-card">card</p><p class="mt-1 text-sm text-slate-400">Cartão padrão (p-5, p-6 em telas maiores).</p></div>
            <div class="card-sm"><p class="t-card">card-sm</p><p class="mt-1 text-sm text-slate-400">Cartão compacto para listas densas.</p></div>
            <div class="empty md:col-span-2"><p class="empty-title">Estado vazio</p><p class="mt-1">Uma frase que explica e um botão que resolve.</p><button type="button" class="btn mt-4">Fazer a primeira coisa</button></div>
            <div class="skeleton h-24" aria-label="Carregando"></div>
            <p class="alert-error" role="note">Mensagem de erro padrão: o que houve e o que fazer.</p>
            <div class="demo-note md:col-span-2" role="note"><p><strong class="text-slate-50">Página de exemplo</strong> com dados fictícios.</p><span class="font-semibold text-brand-400">Todos os exemplos →</span></div>
            <div class="hero-box p-5 md:col-span-2"><p class="kicker-hero">Herói (hero-box)</p><p class="t-hero mt-1">Fundo escuro tingido pelo tema</p><p class="mt-1 text-sm text-white/70">Sempre escuro, com texto claro; a cor vem da marca do tema.</p></div>
            <div class="hero-box border-gold-400/35 p-5 md:col-span-2"><p class="best-kicker">🏆 Ouro</p><p class="mt-1 font-bold">Herói com borda dourada: troféus, GOTY e best of.</p></div>
        </div>
    </section>

    <section class="mt-10" aria-labelledby="g-voc"><h2 id="g-voc" class="t-section">Vocabulário oficial</h2>
        <p class="caption">Linguagem de jogador: os termos em inglês ficam. Uma palavra para cada coisa.</p>
        <div class="panel mt-3 overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr class="border-b border-slate-50/10 text-xs uppercase tracking-wider text-slate-500"><th class="p-3">Use</th><th class="p-3">Quando</th><th class="p-3">Não use</th></tr></thead>
            <tbody class="divide-y divide-slate-50/10">
                <?php foreach ($glossary as [$use, $when, $not]): ?>
                <tr><td class="p-3 font-bold text-slate-50"><?= e($use) ?></td><td class="p-3 text-slate-300"><?= e($when) ?></td><td class="p-3 text-slate-500"><?= e($not) ?></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
    </section>
</main>
