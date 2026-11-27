<?php
/** @var array $me */
// cada passo: imagem em assets/tutorial (feita com os dados de exemplo), título, explicação, dicas e o botão para experimentar
$steps = [
    ['painel', '🏠', 'O Painel é a sua casa', 'Mostra o que você está jogando, os números do ano, a sua semana e lembranças de outros anos.',
        ['Em "Personalizar" você escolhe e arrasta os blocos.', 'Os blocos aparecem conforme você registra jogos.'], '/', 'Abrir o Painel'],
    ['registrar', '✅', 'Registre os jogos que você zerou', 'É o coração do programa: título, sistema, gênero, ano de lançamento, data, tempo de jogo, nota de 1 a 11 e dificuldade. Cada registro rende XP.',
        ['Desenvolvedora, publisher, data de início, capa e memórias são opcionais; o resto é obrigatório.', 'Arraste ou cole (Ctrl+V) a capa. Dá para guardar memórias: quem apresentou, com quem jogou, a música da época.', 'Notas muito altas são raras: há uma cota para a 10 e a 11.'], '/jogos/novo', 'Registrar um jogo'],
    ['catalogo', '📚', 'O Catálogo reúne tudo', 'Todos os zerados em capas, com busca, filtros, ordenação e vários visuais.',
        ['Aperte / para buscar.', 'Salve combinações de filtros em "Visões" e use "Me surpreenda" para relembrar um jogo.', 'Dá para trazer uma lista pronta em Configurações → Importar jogos.'], '/jogos', 'Abrir o Catálogo'],
    ['backlog', '🎯', 'Backlog: o que vem a seguir', 'Anote o que quer jogar. Quando começar, ele vai para "Jogando agora" (até 4 ao mesmo tempo).',
        ['Escreva no diário de bordo do jogo: quem recebeu a anotação mais recente fica primeiro.', '"Zerei!" leva para o registro; "Dropei" guarda com o motivo.', '"O que eu jogo agora?" sorteia da fila.'], '/backlog', 'Abrir o Backlog'],
    ['franquias', '🧩', 'Franquias: complete suas séries', 'Monte a lista de uma franquia em seções. O programa marca sozinho o que você já zerou.',
        ['A trilha mostra o progresso e o próximo jogo.', 'Mande os que faltam para o backlog com um clique.'], '/franquias', 'Abrir as Franquias'],
    ['goty', '🏆', 'GOTY, Retrospectiva e Best of', 'Eleja o melhor jogo de cada ano de lançamento, o seu pódio do ano e o melhor de cada sistema, gênero ou empresa.',
        ['Só jogos com nota 9, 10 ou 11 podem ser eleitos.', 'Em Análises há também Estatísticas, Timeline, Mapa de calor, Metas e o Livro da vida gamer.'], '/goty', 'Ver o GOTY'],
    ['menu', '🎮', 'O menu leva a qualquer lugar', 'O botão redondo no canto (ou a tecla M) abre todas as páginas de uma vez. Digite para buscar, inclusive seus jogos pelo nome.',
        ['Aperte ? para ver todos os atalhos do teclado.', 'G e depois uma letra navega: G J abre o catálogo, G B o backlog.', 'Em Temas há 20 visuais: passe o mouse para ver o programa mudar.'], null, 'Abrir o menu'],
    ['conta', '🔒', 'Seus dados ficam no seu computador', 'Tudo é salvo na pasta %LOCALAPPDATA%\\MyGameList, sem internet.',
        ['Para fazer backup, feche o programa e copie essa pasta.', 'Em Minha conta você troca a imagem e o nome e exporta os seus dados.', 'Outra pessoa? Use "Sair" no menu e escolha "Novo usuário".'], '/conta', 'Abrir Minha conta'],
];
?>
<main class="page page-normal" x-data="tutorial(<?= count($steps) ?>)" @keydown.window="keys($event)">
    <?php require __DIR__ . '/partials/crumb.php'; ?>
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="t-page">Como usar o MyGameList</h1>
            <p class="page-sub">Um tour opcional. Use as setas do teclado ou os botões; saia quando quiser.</p>
        </div>
        <a href="<?= e(url('/')) ?>" class="btn-ghost">Pular o tour</a>
    </header>

    <ol class="mt-6 flex gap-1.5" aria-label="Passos do tour">
        <?php foreach ($steps as $i => $s): ?>
        <li class="flex-1"><button type="button" @click="step = <?= $i ?>" :aria-current="step === <?= $i ?> ? 'step' : null" aria-label="Passo <?= $i + 1 ?>: <?= e($s[2]) ?>"
                class="block h-2 w-full rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" :class="step >= <?= $i ?> ? 'bg-brand-500' : 'bg-slate-50/10'"></button></li>
        <?php endforeach; ?>
    </ol>

    <?php foreach ($steps as $i => [$img, $icon, $title, $text, $tips, $href, $cta]): ?>
    <section x-show="step === <?= $i ?>" <?= $i ? 'x-cloak' : '' ?> class="panel mt-5 grid gap-6 p-5 lg:grid-cols-[1.35fr_1fr] lg:p-7" aria-label="<?= e($title) ?>">
        <figure class="min-w-0 self-center">
            <img src="<?= e(asset("/assets/tutorial/$img.webp")) ?>" alt="Tela de exemplo: <?= e($title) ?>" <?= $i ? 'loading="lazy"' : '' ?> class="w-full rounded-xl shadow-2xl ring-1 ring-slate-50/15">
            <figcaption class="caption mt-2">Imagem com jogos de exemplo.</figcaption>
        </figure>
        <div class="flex min-w-0 flex-col">
            <p class="kicker">Passo <?= $i + 1 ?> de <?= count($steps) ?></p>
            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-50"><span aria-hidden="true"><?= $icon ?></span> <?= e($title) ?></h2>
            <p class="mt-3 text-slate-300"><?= e($text) ?></p>
            <ul class="mt-4 space-y-2 text-sm text-slate-400">
                <?php foreach ($tips as $tip): ?>
                <li class="flex gap-2"><span class="text-brand-400" aria-hidden="true">▸</span><span><?= e($tip) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <div class="mt-auto pt-6">
                <?php if ($href !== null): ?>
                <a href="<?= e(url($href)) ?>" class="btn-ghost"><?= e($cta) ?> →</a>
                <?php else: ?>
                <button type="button" class="btn-ghost" onclick="document.querySelector('button[aria-label=&quot;Abrir menu&quot;]').click()"><?= e($cta) ?></button>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <div class="mt-6 flex items-center justify-between gap-3">
        <button type="button" @click="move(-1)" :disabled="step === 0" class="btn-ghost btn-lg">← Anterior</button>
        <button type="button" x-show="step < total - 1" @click="move(1)" class="btn btn-lg">Próximo →</button>
        <a x-show="step === total - 1" x-cloak href="<?= e(url('/jogos/novo')) ?>" class="btn btn-lg">Registrar o meu primeiro jogo 🎮</a>
    </div>
</main>

<script>
function tutorial(total) {
    return {
        total, step: 0,
        move(d) { this.step = Math.min(this.total - 1, Math.max(0, this.step + d)); },
        keys(e) {
            if (e.altKey || e.ctrlKey || e.metaKey || /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName)) return;
            if (document.querySelector('[role=dialog]:not([style*="display: none"])')) return;
            if (e.key === 'ArrowRight') this.move(1);
            if (e.key === 'ArrowLeft') this.move(-1);
        },
    };
}
</script>
