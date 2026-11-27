<?php
/** @var array|null $me */
$groups = [
    'Catálogo e estatísticas' => [
        ['/jogos/exemplo',      'Catálogo',            '60 jogos com capas, busca, filtros e ordenação funcionando.'],
        ['/exemplo/dashboard',  'Dashboard',           'Números do ano, tabela mês a mês e gráficos de gêneros e sistemas.'],
        ['/timeline/exemplo',   'Timeline',            'Seus jogos no tempo, em modo vertical ou horizontal, com marcos.'],
        ['/calor/exemplo',      'Mapa de calor',       'Um quadradinho por dia, com sequências, pausas e o dia da semana favorito.'],
        ['/dropados/exemplo',   'Jogos dropados',      'Os jogos largados no meio, com a chance de voltar: baixa, média ou alta.'],
        ['/livro/exemplo',      'Livro da vida gamer', 'Todos os jogos por ano, com memórias e diário, prontos para imprimir.'],
        ['/maquina-do-tempo/exemplo', 'Máquina do tempo', 'Seu catálogo como era em qualquer data, e o que você zerava em dias parecidos.'],
        ['/diario/exemplo',     'Diário',              'A linha do tempo de tudo: fila, começados, zerados, largados, anotações e registros do dia.'],
        ['/pessoas/exemplo',    'Pessoas e lugares',   'Com quem e onde você jogou, com a fileira de capas de cada um.'],
        ['/estatisticas/exemplo', 'Estatísticas',      'Recordes, hábitos, curiosidades e gráficos sobre todos os jogos zerados.'],
        ['/organizar/exemplo',  'Organizar nomes',     'Juntar variações como PS2 e Playstation 2 em um nome só.'],
        ['/importar/exemplo',   'Importar jogos',      'Uma planilha fictícia lida, com colunas associadas e linhas prontas, repetidas e com erro.'],
    ],
    'Navegar por…' => [
        ['/exemplo/sistemas',          'Sistemas',          'Seus jogos agrupados por sistema, com mosaico de capas.'],
        ['/exemplo/notas',             'Notas',             'Da 11 à 1, com a cota de notas 10 e 11 (1 a cada 10 e 1 a cada 50 jogos).'],
        ['/exemplo/generos',           'Gêneros',           'Quanto você joga de cada gênero.'],
        ['/exemplo/dificuldades',      'Dificuldades',      'De C até AAA, com os cinco níveis sempre visíveis.'],
        ['/exemplo/ano-de-lancamento', 'Ano de lançamento', 'Os jogos por época, de 1991 a 2024.'],
        ['/exemplo/desenvolvedoras',   'Desenvolvedoras',   'Quem fez os jogos que você zerou.'],
        ['/exemplo/publishers',        'Publishers',        'Quem publicou os jogos que você zerou.'],
    ],
    'Coleções, anos e escolhas' => [
        ['/exemplo/franquias',    'Franquias',     'Séries organizadas em seções, com o progresso de cada uma.'],
        ['/goty/exemplo',         'GOTY',          'O melhor jogo lançado em cada ano, com visão geral por década.'],
        ['/retrospectiva/exemplo', 'Retrospectiva', 'O seu ano em jogos e o top 3 do ano.'],
    ],
];
?>
<main class="page page-narrow">
    <?php require __DIR__ . '/partials/crumb.php'; ?>
    <header>
        <span class="rating-gold inline-block rounded-md px-2.5 py-1 text-xs font-black uppercase tracking-[0.2em] text-[#3b1d00]">Exemplos</span>
        <h1 class="mt-3 t-page">Páginas de exemplo</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-400">
            Como cada tela fica com bastante conteúdo. Tudo aqui usa jogos fictícios, não mexe no seu catálogo e abre sem login.
            Os links entre as páginas de exemplo funcionam, e o tema que você escolheu vale em todas.
        </p>
    </header>

    <?php foreach ($groups as $title => $items): ?>
    <section class="mt-10" aria-label="<?= e($title) ?>">
        <h2 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-400"><?= e($title) ?></h2>
        <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($items as [$path, $name, $desc]): ?>
            <li>
                <a href="<?= e(url($path)) ?>" class="group flex h-full flex-col panel p-5 transition hover:-translate-y-0.5 hover:border-brand-400/60 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                    <span class="flex items-center justify-between gap-2">
                        <span class="text-lg font-bold text-slate-50 group-hover:text-brand-400"><?= e($name) ?></span>
                        <span class="text-slate-500 transition group-hover:translate-x-0.5 group-hover:text-brand-400" aria-hidden="true">→</span>
                    </span>
                    <span class="mt-1 text-sm text-slate-400"><?= e($desc) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>

    <?php if (!$me): ?>
    <p class="mt-10 text-center text-sm text-slate-500">Quer usar de verdade? <a href="<?= e(url('/cadastro')) ?>" class="font-semibold text-brand-400 hover:underline">Criar conta</a> ou <a href="<?= e(url('/login')) ?>" class="font-semibold text-brand-400 hover:underline">entrar</a>.</p>
    <?php endif; ?>
</main>
