<?php /** @var array $menu  username, xp, path, calm, usage (item => visitas) */ ?>
<div x-data='gameMenu(<?= e(json_encode($menu, JSON_UNESCAPED_UNICODE)) ?>)'>

    <!-- Botão flutuante -->
    <button type="button" @click="toggle()" :aria-expanded="open" aria-haspopup="dialog" aria-label="Abrir menu"
            title="Menu (tecla M ou Start do controle)"
            class="group fixed bottom-5 right-5 z-fab grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-brand-600 to-accent text-on-brand shadow-lg shadow-brand-600/40 ring-1 ring-slate-50/20 transition hover:scale-110 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-400/60 motion-reduce:transition-none motion-reduce:hover:scale-100">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 11h4M8 9v4M15 12h.01M18 10h.01M17.32 5H6.68a4 4 0 00-3.98 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 003 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 019.828 16h4.344a2 2 0 011.414.586L17 18c.5.5 1 1 2 1a3 3 0 003-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0017.32 5z"/>
        </svg>
    </button>

    <!-- Atalhos do teclado (tecla ? ou busca "atalhos" no menu) -->
    <div x-show="keysOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="keys-title" class="modal-back !z-menu" @click.self="keysOpen = false" @keydown.escape.window="keysOpen = false">
        <div class="modal-box !max-w-xl">
            <div class="flex items-start justify-between gap-3"><h2 id="keys-title" class="t-section">Atalhos do teclado</h2>
                <button type="button" class="icon-btn" @click="keysOpen = false" aria-label="Fechar">✕</button></div>
            <dl class="mt-4 grid grid-cols-[auto_1fr] items-center gap-x-4 gap-y-2 text-sm">
                <template x-for="it in [['M', 'Abrir o menu (ou Start do controle)'], ['/', 'Buscar na página (catálogo, franquias)'], ['G e depois P', 'Ir para o Painel'], ['G e depois J', 'Ir para o Catálogo (jogos)'], ['G e depois N', 'Registrar um jogo zerado'], ['G e depois B', 'Ir para o Backlog'], ['G e depois D', 'Ir para os Dropados'], ['G e depois F', 'Ir para as Franquias'], ['G e depois E / T / C / Y / O', 'Estatísticas / Timeline / Mapa de calor / Retrospectiva / GOTY'], ['← →', 'Jogo ou franquia anterior/seguinte (nas páginas deles)'], ['E', 'Editar (página do jogo)'], ['N', 'Nova nota (página do jogo)'], ['↑ ↓ ← → no menu', 'Andar pelos ícones; Enter abre'], ['?', 'Mostrar esta janela'], ['Esc', 'Fechar janelas e menus']]" :key="it[0]">
                    <div class="contents"><dt><kbd class="rounded border border-slate-50/20 bg-slate-950/50 px-1.5 py-0.5 text-xs font-bold text-slate-100" x-text="it[0]"></kbd></dt><dd class="text-slate-300" x-text="it[1]"></dd></div>
                </template>
            </dl>
        </div>
    </div>

    <!-- Modal em tela cheia: tudo numa tela só -->
    <div x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Menu principal"
         x-transition:enter="transition duration-200 ease-out motion-reduce:duration-0" x-transition:enter-start="opacity-0 scale-105"
         x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition duration-150 ease-in motion-reduce:duration-0"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-105"
         class="fixed inset-0 z-menu overflow-y-auto bg-slate-950/95 backdrop-blur-xl">
        <div class="menu-bg pointer-events-none absolute inset-0"></div>

        <div class="relative mx-auto flex min-h-full max-w-6xl flex-col px-4 py-4 sm:px-8 sm:py-5 xl:max-w-[min(94vw,96rem)]">

            <!-- Perfil -->
            <header class="flex items-center gap-4">
                <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand-600 to-accent text-2xl font-bold text-on-brand shadow-lg shadow-brand-600/30"
                     x-html="UI.avatarContent(me.username, me.avatar)" aria-hidden="true"></div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xl font-semibold text-slate-50" x-text="me.username"></p>
                    <div class="xp-ui mt-1 flex items-center gap-3">
                        <span class="rounded-md bg-brand-500/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-brand-400">Nível <span x-text="me.xp.level"></span></span>
                        <span class="text-xs text-slate-400"><span x-text="me.xp.total_xp.toLocaleString('pt-BR')"></span><template x-if="me.xp.next_level_xp"><span> / <span x-text="me.xp.next_level_xp.toLocaleString('pt-BR')"></span></span></template> XP</span>
                    </div>
                    <div class="xp-ui mt-2 h-1.5 max-w-sm overflow-hidden rounded-full bg-slate-50/10" role="progressbar" aria-label="Progresso de XP" :aria-valuenow="me.xp.progress_pct" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="`width:${me.xp.progress_pct}%`"></div>
                    </div>
                </div>
                <button type="button" @click="close()" aria-label="Fechar menu"
                        class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-slate-50/10 text-slate-300 transition hover:bg-slate-50/10 hover:text-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                    <?= icon('close', 'h-5 w-5') ?>
                </button>
            </header>

            <!-- ===== Menu: busca, mais acessados e seções ===== -->
            <div x-show="view === 'main'" class="my-3 flex-1">
                <label class="relative block">
                    <span class="sr-only">Buscar no menu</span>
                    <?= icon('search', 'pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400') ?>
                    <input x-ref="search" type="search" x-model="q" @input="selected = 0" autocomplete="off" placeholder="Buscar… (ex.: estatísticas, backlog, tema)"
                           class="w-full rounded-2xl border border-slate-50/15 bg-slate-900/80 py-3.5 pl-12 pr-4 text-base text-slate-50 placeholder-slate-500 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-500/40"
                           aria-describedby="menu-hint">
                </label>
                <p id="menu-hint" class="sr-only">Digite para filtrar. Setas para escolher, Enter para abrir, Esc para limpar ou fechar.</p>

                <!-- Resultados da busca -->
                <section x-show="q.trim()" x-cloak class="mt-5" aria-label="Resultados da busca">
                    <p x-show="!results.length" class="empty">Nada encontrado para “<span x-text="q.trim()"></span>”.</p>
                    <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="(t, i) in results" :key="'r-' + t.id">
                            <li><?php $mode = 'row'; $keyExpr = "'r-' + t.id"; require __DIR__ . '/menu_item.php'; ?></li>
                        </template>
                    </ul>
                    <!-- seus jogos e franquias com esse nome -->
                    <div x-show="found.length" x-cloak class="mt-5">
                        <h2 class="mb-2 text-xs font-bold uppercase tracking-[.2em] text-slate-400">Nos seus dados</h2>
                        <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="(t, i) in found" :key="'r-' + t.id">
                                <li><?php $mode = 'row'; $keyExpr = "'r-' + t.id"; require __DIR__ . '/menu_item.php'; ?></li>
                            </template>
                        </ul>
                    </div>
                </section>

                <div x-show="!q.trim()">
                    <!-- Continue de onde parou: a última página e o que está em andamento no backlog -->
                    <section x-show="cont.length" x-cloak class="mt-4" aria-labelledby="h-cont">
                        <h2 id="h-cont" class="sr-only">Continue de onde parou</h2>
                        <ul class="flex flex-wrap items-center gap-2">
                            <template x-for="c in cont" :key="c.href">
                                <li><a :href="base + c.href" class="flex items-center gap-2 rounded-full border border-slate-50/15 bg-slate-900/60 px-3.5 py-1.5 text-sm text-slate-200 transition hover:border-brand-400/60 hover:text-brand-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"><span aria-hidden="true" x-text="c.icon"></span><span class="max-w-[16rem] truncate" x-text="c.label"></span></a></li>
                            </template>
                        </ul>
                    </section>

                    <!-- Todas as páginas: um ícone para cada, em 4 grupos lado a lado (2 em telas médias, 1 no celular) -->
                    <div class="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2 xl:grid-cols-4">
                        <template x-for="(sec, si) in sections" :key="sec.id">
                            <section :aria-labelledby="'h-' + sec.id">
                                <h2 :id="'h-' + sec.id" class="menu-h mb-1.5" x-text="sec.title"></h2>
                                <ul class="menu-grid">
                                    <template x-for="(t, i) in sec.items" :key="sec.id + '-' + t.id">
                                        <li><?php $mode = 'icon'; $keyExpr = "sec.id + '-' + t.id"; $idxExpr = 'si * 9 + i'; require __DIR__ . '/menu_item.php'; ?></li>
                                    </template>
                                </ul>
                            </section>
                        </template>
                    </div>

                    <!-- o que o ícone selecionado faz -->
                    <p class="menu-hintbar" aria-live="polite" x-html="hint"></p>
                </div>
            </div>
            <!-- ===== Seletor de temas ===== -->
            <section x-show="view === 'themes'" x-cloak class="my-8 flex-1" aria-label="Temas">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="back()" class="rounded-lg border border-slate-50/10 px-3 py-2 text-sm text-slate-300 hover:bg-slate-50/10 hover:text-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">← Voltar</button>
                        <h2 class="t-section">Temas</h2>
                    </div>
                    <button type="button" role="switch" :aria-checked="fx" @click="toggleFx()"
                            class="flex items-center gap-2 rounded-lg border border-slate-50/10 px-3 py-2 text-sm text-slate-300 hover:bg-slate-50/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                        <span class="relative h-5 w-9 rounded-full transition" :class="fx ? 'bg-brand-600' : 'bg-slate-50/20'">
                            <span class="absolute top-0.5 h-4 w-4 rounded-full bg-slate-50 transition-all" :class="fx ? 'left-[1.15rem]' : 'left-0.5'"></span>
                        </span>
                        Efeitos visuais
                    </button>
                </div>
                <p class="mb-3 text-xs text-slate-400">Passe o mouse (ou use as setas) para ver o site com cada tema. Clique ou Enter para ficar com ele.</p>
                <div x-ref="tgrid" @mouseleave="endPreview()" class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
                    <template x-for="(t, i) in themes" :key="t.id">
                        <button type="button" :data-theme-card="i" @click="pickTheme(t.id)" @mouseenter="tsel = i; preview(t.id)" @focus="tsel = i; preview(t.id)"
                                :tabindex="tsel === i ? 0 : -1" :aria-pressed="savedTheme === t.id"
                                class="relative rounded-2xl border border-slate-50/10 bg-slate-900/60 p-2.5 text-left transition duration-150 focus:outline-none motion-reduce:transition-none"
                                :class="tsel === i ? 'scale-[1.04] ring-4 ring-slate-50/80 motion-reduce:scale-100' : 'opacity-90'">
                            <span class="block overflow-hidden rounded-xl border border-slate-50/10 bg-slate-950 p-3" :style="Theme.styleText(t.id)" style="font-family: var(--font-sans)">
                                <span class="block text-sm font-bold text-slate-50" style="font-family: var(--font-head)" x-text="t.name"></span>
                                <span class="mt-2 block rounded-lg bg-slate-900 p-2">
                                    <span class="block h-1.5 w-full rounded bg-slate-600"></span>
                                    <span class="mt-1.5 block h-1.5 w-2/3 rounded bg-slate-700"></span>
                                    <span class="mt-2 block h-4 w-14 rounded bg-brand-600"></span>
                                </span>
                                <span class="mt-2 flex gap-1.5">
                                    <i class="block h-3 w-3 rounded-full bg-brand-500"></i><i class="block h-3 w-3 rounded-full bg-accent"></i><i class="block h-3 w-3 rounded-full bg-c3"></i>
                                </span>
                            </span>
                            <span class="mt-2 block text-sm font-semibold text-slate-50" x-text="t.name"></span>
                            <span class="block text-xs text-slate-400" x-text="t.desc"></span>
                            <span x-show="savedTheme === t.id" x-cloak class="absolute right-4 top-4 rounded-full bg-brand-600 px-2 py-0.5 text-2xs font-bold uppercase tracking-wider text-on-brand">Ativo</span>
                        </button>
                    </template>
                </div>
            </section>

            <!-- Dicas -->
            <footer class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 border-t border-slate-50/10 pt-4 text-xs text-slate-400">
                <span><kbd class="rounded bg-slate-50/10 px-1.5 py-0.5 font-mono text-slate-200">digite</kbd> Buscar</span>
                <span><kbd class="rounded bg-slate-50/10 px-1.5 py-0.5 font-mono text-slate-200">←↑↓→</kbd> Escolher</span>
                <span><kbd class="rounded bg-slate-50/10 px-1.5 py-0.5 font-mono text-slate-200">Enter</kbd> / <span class="font-semibold text-slate-200">A</span> Abrir</span>
                <span><kbd class="rounded bg-slate-50/10 px-1.5 py-0.5 font-mono text-slate-200">Esc</kbd> / <span class="font-semibold text-slate-200">B</span> Voltar</span>
                <span x-show="pad" x-cloak class="text-ok">● Controle conectado</span>
            </footer>
        </div>
    </div>
</div>

<script>
function gameMenu(me) {
    const base = window.APP.base;
    const ICONS = {
        dashboard: 'M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z',
        library: 'M4 19.5A2.5 2.5 0 016.5 17H20V3H6.5A2.5 2.5 0 004 5.5v14zM6.5 17H20v4H6.5A2.5 2.5 0 014 18.5',
        add: 'M12 5v14M5 12h14',
        franchises: 'M12 2l9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 17l9 5 9-5',
        ranking: 'M8 21V11H3v10M14 21V3H9v18M21 21v-7h-5v7M3 21h18',
        goty: 'M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zM17 5h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3',
        retro: 'M3 12a9 9 0 109-9 9 9 0 00-6.4 2.6L3 8M3 3v5h5M12 7v5l3 2',
        examples: 'M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9zM19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9z',
        timeline: 'M12 2v20M12 6h7M12 12H5M12 18h7M19 6v0M5 12v0M19 18v0',
        heat: 'M3 4h18v16H3zM3 9h18M3 14h18M9 4v16M15 4v16',
        backlog: 'M6 4h12v17l-6-4-6 4zM9 8h6',
        goals: 'M12 21a9 9 0 100-18 9 9 0 000 18zM12 16a4 4 0 100-8 4 4 0 000 8zM12 12h.01',
        import: 'M12 3v12m0 0l-4-4m4 4l4-4M4 17v3h16v-3',
        stats: 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        book: 'M4 19.5A2.5 2.5 0 016.5 17H20V3H6.5A2.5 2.5 0 004 5.5v14zM8 7h8M8 11h6',
        visual: 'M3 5h18v14H3zM3 9h18M8 9v10',
        time: 'M12 8v4l3 2M3 12a9 9 0 109-9 9 9 0 00-6.4 2.6L3 8M3 3v5h5',
        journal: 'M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4L16.5 3.5z',
        people: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8',
        organize: 'M4 6h16M4 12h10M4 18h6M17 14l3 3-3 3',
        dropped: 'M18 6L6 18M6 6l12 12',
        account: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6zM9 12l2 2 4-4',
        profile: 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z',
        platform: 'M3 5h18v11H3zM8 20h8M12 16v4',
        genre: 'M20.6 13.4l-7.2 7.2a2 2 0 01-2.8 0L3 13V3h10l7.6 7.6a2 2 0 010 2.8zM7.5 7.5h.01',
        difficulty: 'M12 3c1 4 5 5 5 10a5 5 0 01-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-5 1-9z',
        rating: 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z',
        year: 'M7 3v4M17 3v4M4 8h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z',
        developer: 'M8 7l-5 5 5 5M16 7l5 5-5 5M14 4l-4 16',
        publisher: 'M4 21V7l8-4 8 4v14M9 21v-6h6v6M9 10h.01M15 10h.01',
        guide: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        tutorial: 'M22 10L12 5 2 10l10 5 10-5zM6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5M22 10v6',
        themes: 'M12 3a9 9 0 100 18c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3-.3-.4-.5-.8-.5-1.3 0-1.1.9-2 2-2h2.3a3.2 3.2 0 003.2-3.2C21 7 17 3 12 3zM7.5 12h.01M10 8h.01M14 8h.01',
        soundOn: 'M11 5L6 9H3v6h3l5 4V5zM15.5 8.5a5 5 0 010 7M18.5 5.5a9 9 0 010 13',
        soundOff: 'M11 5L6 9H3v6h3l5 4V5zM16 9l5 6M21 9l-5 6',
        logout: 'M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9',
    };

    // Seções e itens: vêm de NavService (a mesma lista da faixa "onde você está" nas páginas). "k" são palavras extras para a busca.
    const ALL = <?= json_encode(\App\Services\NavService::groups(), JSON_UNESCAPED_UNICODE) ?>.map((g) => ({ ...g, items: g.items.map((t) => (t.href ? { ...t, href: t.href.replace('{me}', encodeURIComponent(me.username)) } : t)) }));
    const HIDDEN_IN_CALM = ['ranking', 'goals', 'stats'];
    const usage = me.usage || {};
    const norm = (s) => String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const isCurrent = (t) => !!t.href && (t.href.toLowerCase() === me.path.toLowerCase()
        || (t.id === 'library' && /^\/jogos\/\d+/.test(me.path)) || (t.id === 'franchises' && me.path.startsWith('/franquias')));
    const byUse = (a, b) => (usage[b.id] || 0) - (usage[a.id] || 0) || a.order - b.order;

    // ---- áudio sintetizado (sem arquivos) ----
    let ctx = null;
    const tone = (sound, f1, f2, dur, type = 'square', vol = 0.035, delay = 0) => {
        if (!sound) return;
        try {
            ctx ??= new (window.AudioContext || window.webkitAudioContext)();
            if (ctx.state === 'suspended') ctx.resume();
            const t0 = ctx.currentTime + delay;
            const osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(f1, t0);
            if (f2 !== f1) osc.frequency.exponentialRampToValueAtTime(f2, t0 + dur);
            gain.gain.setValueAtTime(vol, t0);
            gain.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
            osc.connect(gain).connect(ctx.destination);
            osc.start(t0);
            osc.stop(t0 + dur + 0.02);
        } catch { /* áudio indisponível: ignora */ }
    };

    // Ações que só aparecem na busca (além das páginas)
    const ACTIONS = [
        { id: 'act-roll', label: 'Sortear o próximo do backlog', desc: 'O que eu jogo agora?', href: '/backlog?sortear=1', k: 'sortear sorteio jogar agora aleatorio escolher', icon: 'backlog', order: 900 },
        { id: 'act-keys', label: 'Atalhos do teclado', desc: 'Veja todas as teclas do site', k: 'atalhos teclas teclado ajuda', icon: 'guide', order: 901 },
    ];
    let franchiseCache = null;
    // "G" e depois uma letra leva para a página (estilo editor de código)
    const GO = { p: '/', j: '/jogos', n: '/jogos/novo', b: '/backlog', d: '/dropados', f: '/franquias', u: '/u', r: '/ranking', e: '/estatisticas', t: '/timeline', c: '/calor', y: '/retrospectiva', o: '/goty', a: '/conta' };

    let order = 0;
    const sectionsAll = ALL.map((s) => ({
        ...s,
        items: s.items.filter((t) => !(me.calm && HIDDEN_IN_CALM.includes(t.id))).map((t) => ({ ...t, order: order++, current: isCurrent(t) })),
    }));

    return {
        me, base, open: false, view: 'main', q: '', selected: -1, tsel: 0, sound: true, pad: false,
        found: [], cont: [], keysOpen: false, gPending: false, lookupTimer: null, lookupSeq: 0,
        icons: ICONS, themes: Theme.list, currentTheme: Theme.current, savedTheme: Theme.current, fx: Theme.fx,

        /** Seções na ordem fixa (os ícones não mudam de lugar: dá para decorar). */
        get sections() { return sectionsAll; },
        /** texto da barra de baixo: o que o ícone selecionado faz */
        get hint() {
            const t = this.flat[this.selected]?.t;
            if (!t) return 'Passe o mouse ou use as setas para ver o que cada ícone faz. Digite para buscar.';
            const e = (s) => String(s).replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
            return '<strong class="text-slate-50">' + e(this.labelOf(t)) + '</strong> · ' + e(t.current ? 'Você está aqui' : this.descOf(t));
        },
        /** As 6 páginas mais abertas (só as que já foram visitadas). */
        get top() { return sectionsAll.flatMap((s) => s.items).filter((t) => usage[t.id] > 0).sort(byUse).slice(0, 6); },
        /** Busca sem acento; quem começa com o termo vem antes, depois os mais acessados. */
        get results() {
            const q = norm(this.q.trim());
            if (!q) return [];
            const words = q.split(/\s+/);
            return [...ACTIONS, ...sectionsAll.flatMap((s) => s.items)]
                .map((t) => {
                    const hay = norm(`${t.label} ${t.desc} ${t.k || ''}`);
                    if (!words.every((w) => hay.includes(w))) return null;
                    return { t, rank: norm(t.label).startsWith(q) ? 0 : norm(t.label).includes(q) ? 1 : 2 };
                })
                .filter(Boolean)
                .sort((a, b) => a.rank - b.rank || byUse(a.t, b.t))
                .map((r) => r.t);
        },
        /** Ordem de navegação pelo teclado/controle: tudo o que está na tela, de cima para baixo. */
        get flat() {
            if (this.q.trim()) return [...this.results, ...this.found].map((t) => ({ key: 'r-' + t.id, t }));
            return [
                ...this.sections.flatMap((s) => s.items.map((t) => ({ key: s.id + '-' + t.id, t }))),
            ];
        },
        isSel(key) { return this.flat[this.selected]?.key === key; },
        hover(key) { const i = this.flat.findIndex((f) => f.key === key); if (i >= 0) this.selected = i; },
        labelOf(t) { return t.id === 'sound' ? (this.sound ? 'Sons: ligados' : 'Sons: desligados') : t.label; },
        descOf(t) { return t.id === 'themes' ? 'Atual: ' + this.currentName : t.desc; },
        iconOf(t) { return this.icons[t.icon || (t.id === 'sound' ? (this.sound ? 'soundOn' : 'soundOff') : t.id)]; },
        get currentName() { return this.themes.find((t) => t.id === this.currentTheme)?.name ?? ''; },

        init() {
            try { this.sound = localStorage.getItem('mgl.sound') !== '0'; } catch { /* sem storage */ }
            this.$watch('open', (v) => this.onToggle(v));
            this.$watch('q', (v) => this.lookup(v));
            this.rememberPage();
            window.addEventListener('keydown', (e) => this.onKey(e));
            window.addEventListener('themechange', (e) => { this.currentTheme = e.detail.id; this.fx = e.detail.fx; });
            window.addEventListener('gamepadconnected', () => { this.pad = true; this.startPad(); });
            window.addEventListener('gamepaddisconnected', () => { this.pad = [...(navigator.getGamepads?.() ?? [])].some(Boolean); });
            if ([...(navigator.getGamepads?.() ?? [])].some(Boolean)) { this.pad = true; this.startPad(); }
        },

        // ---- abrir / fechar ----
        toggle() { this.open ? this.close() : this.openMenu(); },
        /** guarda a página atual; a anterior vira o "continue de onde parou" */
        rememberPage() {
            const here = sectionsAll.flatMap((s) => s.items).find((t) => t.current);
            if (!here) return;
            try {
                const cur = JSON.parse(localStorage.getItem('mgl.cur') || 'null');
                if (cur && cur.href !== here.href) localStorage.setItem('mgl.last', JSON.stringify(cur));
                localStorage.setItem('mgl.cur', JSON.stringify({ href: here.href, label: here.label }));
            } catch { /* sem storage */ }
        },
        async loadCont() {
            const out = [];
            try {
                const last = JSON.parse(localStorage.getItem('mgl.last') || 'null');
                if (last && last.href !== me.path) out.push({ href: last.href, label: 'Voltar para ' + last.label, icon: '↩' });
            } catch { /* sem storage */ }
            this.cont = out;
            if (me.demo) return;
            try {
                const rows = (await api.get('/api/backlog')).data.filter((g) => g.status === 'playing').slice(0, 3);
                this.cont = [...rows.map((g) => ({ href: '/backlog/' + g.id + '/editar', label: 'Jogando: ' + g.title, icon: '▶' })), ...out];
            } catch { /* mostra só a última página */ }
        },
        /** busca nos seus jogos e franquias enquanto digita (a partir de 2 letras) */
        lookup(v) {
            clearTimeout(this.lookupTimer);
            const q = v.trim();
            if (q.length < 2) { this.found = []; return; }
            const seq = ++this.lookupSeq;
            this.lookupTimer = setTimeout(async () => {
                try {
                    franchiseCache ??= (await api.get('/api/franchises')).data;
                    const [g] = await Promise.all([api.get('/api/games?per_page=4&q=' + encodeURIComponent(q))]);
                    if (seq !== this.lookupSeq) return;
                    const nq = norm(q);
                    this.found = [
                        ...franchiseCache.filter((f) => norm(f.name).includes(nq)).slice(0, 3).map((f) => ({ id: 'f-' + f.id, label: f.name, desc: 'Franquia · ' + f.done + ' de ' + f.total + ' zerados', href: '/franquias/' + f.id, icon: 'franchises' })),
                        ...g.data.map((x) => ({ id: 'g-' + x.id, label: x.title, desc: 'Jogo zerado · ' + x.platform + ' · nota ' + x.rating, href: '/jogos/' + x.id, icon: 'library' })),
                    ];
                } catch { this.found = []; }
            }, 220);
        },
        openMenu() {
            this.loadCont();
            this.view = 'main'; this.q = ''; this.selected = -1;
            this.open = true;
            tone(this.sound, 330, 660, 0.12);
        },
        close() {
            if (!this.open) return;
            this.endPreview();
            this.open = false;
            tone(this.sound, 660, 330, 0.1);
        },
        onToggle(v) {
            document.body.classList.toggle('overflow-hidden', v);
            document.querySelector('main')?.toggleAttribute('inert', v);
            if (v) this.$nextTick(() => this.$refs.search?.focus());   // abre já pronto para digitar
        },
        focusSelected() {
            if (this.view === 'themes') return this.$el.querySelector(`[data-theme-card="${this.tsel}"]`)?.focus();
            const key = this.flat[this.selected]?.key;
            if (key) this.$el.querySelector(`[data-key="${CSS.escape(key)}"]`)?.focus();
            else this.$refs.search?.focus();
        },

        // ---- temas ----
        openThemes() {
            Theme.loadAllFonts();
            this.view = 'themes';
            this.tsel = Math.max(0, this.themes.findIndex((t) => t.id === this.currentTheme));
            this.$nextTick(() => this.focusSelected());
        },
        back() {
            if (this.view === 'themes') {
                this.endPreview();
                this.view = 'main';
                tone(this.sound, 660, 330, 0.08);
                return this.$nextTick(() => this.focusSelected());
            }
            if (this.q) { this.q = ''; this.selected = -1; return this.$nextTick(() => this.$refs.search?.focus()); }
            this.close();
        },
        /** mostra o tema na página inteira, sem salvar */
        preview(id) { if (id !== this.currentTheme) Theme.apply(id, this.fx); },
        endPreview() { if (this.currentTheme !== this.savedTheme) Theme.apply(this.savedTheme, this.fx); },
        pickTheme(id) {
            this.savedTheme = id;
            Theme.set(id, this.fx);
            tone(this.sound, 660, 660, 0.06);
            tone(this.sound, 880, 880, 0.1, 'square', 0.035, 0.07);
        },
        toggleFx() {
            Theme.set(this.savedTheme, !this.fx);
            tone(this.sound, 520, 780, 0.08);
        },

        // ---- navegação ----
        move(d) {
            if (this.view === 'themes') {
                const n = this.themes.length, c = getComputedStyle(this.$refs.tgrid).gridTemplateColumns.split(' ').length;
                const i = this.tsel + ({ left: -1, right: 1, up: -c, down: c }[d] ?? 0);
                if (i < 0 || i >= n) return tone(this.sound, 140, 120, 0.06, 'sawtooth');
                this.tsel = i;
                tone(this.sound, 520, 520, 0.05);
                return this.$nextTick(() => this.focusSelected());
            }
            const n = this.flat.length;
            if (!n) return;
            if (!this.q.trim() && (d === 'up' || d === 'down')) return this.moveGrid(d);   // grade: sobe e desce de linha
            const step = d === 'left' || d === 'up' ? -1 : 1;
            const i = this.selected + step;
            if (i < -1 || i >= n) return tone(this.sound, 140, 120, 0.06, 'sawtooth');
            this.selected = i;   // -1 = volta para a busca
            tone(this.sound, 520, 520, 0.05);
            this.$nextTick(() => this.focusSelected());
        },
        /** Setas ↑ ↓ na grade de ícones: vai para o ícone mais próximo na linha de cima ou de baixo. */
        moveGrid(d) {
            const els = [...this.$el.querySelectorAll('[data-key]')].filter((e) => e.offsetParent !== null);
            const curKey = this.flat[this.selected]?.key;
            const cur = els.find((e) => e.dataset.key === curKey);
            if (!cur) { this.selected = d === 'down' ? 0 : -1; return this.$nextTick(() => this.focusSelected()); }
            const c = cur.getBoundingClientRect(), cx = c.left + c.width / 2;
            let best = null, score = Infinity;
            for (const e of els) {
                if (e === cur) continue;
                const r = e.getBoundingClientRect(), dy = d === 'down' ? r.top - c.top : c.top - r.top;
                if (dy < c.height * 0.6) continue;
                const s = dy * 4 + Math.abs(r.left + r.width / 2 - cx);
                if (s < score) { best = e; score = s; }
            }
            if (!best) {
                if (d === 'up') { this.selected = -1; tone(this.sound, 520, 520, 0.05); return this.$nextTick(() => this.focusSelected()); }
                return tone(this.sound, 140, 120, 0.06, 'sawtooth');
            }
            this.selected = this.flat.findIndex((f) => f.key === best.dataset.key);
            tone(this.sound, 520, 520, 0.05);
            this.$nextTick(() => this.focusSelected());
        },
        activateKey(key) { const i = this.flat.findIndex((f) => f.key === key); if (i >= 0) this.activate(i); },
        activate(i) {
            if (this.view === 'themes') return this.pickTheme(this.themes[this.tsel].id);
            const t = this.flat[i]?.t;
            if (!t) return;
            if (t.id === 'themes') { tone(this.sound, 660, 880, 0.08); return this.openThemes(); }
            if (t.id === 'act-keys') { this.close(); this.keysOpen = true; return; }
            if (t.id === 'sound') {
                this.sound = !this.sound;
                try { localStorage.setItem('mgl.sound', this.sound ? '1' : '0'); } catch { /* sem storage */ }
                return tone(this.sound, 660, 880, 0.08);
            }
            tone(this.sound, 660, 660, 0.06);
            tone(this.sound, 880, 880, 0.1, 'square', 0.035, 0.07);
            if (t.id === 'logout') api.post('/api/auth/logout').finally(() => { location.href = base + '/login'; });
            else if (t.current) this.close();
            else location.href = base + t.href;
        },

        // ---- teclado ----
        onKey(e) {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            const inSearch = document.activeElement === this.$refs.search;
            if (!this.open) {
                const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
                if (typing) return;
                if (this.keysOpen) { if (e.key === 'Escape' || e.key === '?') { e.preventDefault(); this.keysOpen = false; } return; }
                if (document.querySelector('[role=dialog]:not([style*="display: none"]), [role=alertdialog]:not([style*="display: none"])')) return;
                const k = e.key.toLowerCase();
                if (this.gPending) {          // segunda tecla do atalho "G" + letra
                    this.gPending = false;
                    const to = GO[k];
                    if (to) { e.preventDefault(); location.href = base + (to === '/u' ? '/u/' + encodeURIComponent(me.username) : to); }
                    return;
                }
                if (k === 'm') { e.preventDefault(); this.openMenu(); }
                else if (k === 'g') { this.gPending = true; setTimeout(() => (this.gPending = false), 1200); }
                else if (e.key === '?') { e.preventDefault(); this.keysOpen = true; }
                return;
            }
            const arrows = { ArrowLeft: 'left', ArrowRight: 'right', ArrowUp: 'up', ArrowDown: 'down' };
            if (e.key === 'Escape') { e.preventDefault(); this.back(); return; }
            if (e.key === 'Enter' && inSearch) { e.preventDefault(); this.activate(this.selected < 0 ? 0 : this.selected); return; }
            if (arrows[e.key] && !(inSearch && (e.key === 'ArrowLeft' || e.key === 'ArrowRight'))) { e.preventDefault(); this.move(arrows[e.key]); return; }
            // letra digitada fora da busca: volta para a busca já com a letra
            if (this.view === 'main' && !inSearch && e.key.length === 1 && !/^(INPUT|TEXTAREA)$/.test(document.activeElement?.tagName)) {
                this.selected = -1;
                this.$refs.search?.focus();
            }
        },

        // ---- controle (Gamepad API) ----
        padLoop: false,
        startPad() {
            if (this.padLoop) return;
            this.padLoop = true;
            const prev = {}, repeat = {};
            const dirs = { 12: 'up', 13: 'down', 14: 'left', 15: 'right' };
            const tick = (now) => {
                const pads = [...(navigator.getGamepads?.() ?? [])].filter(Boolean);
                if (!pads.length) { this.padLoop = false; return; }
                const pressed = new Set();
                for (const p of pads) {
                    p.buttons.forEach((b, i) => b.pressed && pressed.add(i));
                    const [x = 0, y = 0] = p.axes;
                    if (x < -0.6) pressed.add(14); if (x > 0.6) pressed.add(15);
                    if (y < -0.6) pressed.add(12); if (y > 0.6) pressed.add(13);
                }
                for (const b of pressed) {
                    const isNew = !prev[b];
                    if (dirs[b]) {
                        if (isNew) repeat[b] = now + 380;
                        else if (now < repeat[b]) continue;
                        else repeat[b] = now + 140;
                        if (this.open) this.move(dirs[b]);
                    } else if (isNew) {
                        if (b === 9) this.toggle();                                          // Start
                        else if (this.open && b === 0) this.activate(Math.max(0, this.selected)); // A
                        else if (this.open && b === 1) this.back();                          // B
                    }
                }
                for (const k of Object.keys(prev)) delete prev[k];
                pressed.forEach((b) => (prev[b] = true));
                requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        },
    };
}
</script>
