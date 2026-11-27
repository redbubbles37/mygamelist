<?php
/** @var array|null $me */
/** @var array|null $demo  dados fictícios da página de exemplo (nulo no mapa de calor real) */
$demo = $demo ?? null;
?>
<main class="page page-normal" x-data='heat(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com dois anos fictícios. Passe o mouse (ou toque) nos quadradinhos, troque a métrica e clique em um dia para ver os jogos.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="t-page">Mapa de calor</h1>
            <p class="page-sub ">Cada quadradinho é um dia. Quanto mais forte a cor, mais você zerou naquele dia.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3" x-show="ready" x-cloak>
            <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-sm font-semibold" role="group" aria-label="Métrica">
                <template x-for="m in metrics" :key="m.v">
                    <button type="button" @click="setMetric(m.v)" :aria-pressed="metric === m.v" class="seg-btn focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                            :class="metric === m.v ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'" x-text="m.l"></button>
                </template>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="step(-1)" :disabled="!neighbor(-1) || loading" class="btn-ghost" :aria-label="'Ano anterior'">←</button>
                <label class="sr-only" for="heat-year">Ano</label>
                <select id="heat-year" :value="year" @change="go(Number($event.target.value))" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 font-semibold text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                    <template x-for="y in years" :key="y"><option :value="y" x-text="y"></option></template>
                </select>
                <button type="button" @click="step(1)" :disabled="!neighbor(1) || loading" class="btn-ghost" :aria-label="'Próximo ano'">→</button>
            </div>
        </div>
    </header>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="init()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>
    <div x-show="!ready && !error" class="mt-8 animate-pulse space-y-4" aria-label="Carregando"><div class="h-40 rounded-2xl bg-slate-50/5"></div></div>

    <template x-if="ready && stats">
        <div :class="loading && 'opacity-60 transition-opacity'">

            <!-- Destaque: a sequência (a maior e a atual) e as últimas 12 semanas em chamas -->
            <section class="relative isolate mt-6 overflow-hidden rounded-3xl border border-slate-50/10 bg-hero p-5 text-white sm:p-7" aria-labelledby="h-streak">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    <div class="absolute -bottom-28 left-1/4 h-72 w-96 rounded-full bg-orange-500/25 blur-3xl"></div>
                    <div class="absolute -top-24 right-0 h-64 w-64 rounded-full bg-brand-500/20 blur-3xl"></div>
                </div>
                <div class="grid gap-6 md:grid-cols-[auto_1fr] md:items-center">
                    <div class="flex items-center gap-4">
                        <span class="text-6xl leading-none drop-shadow-[0_0_18px_rgba(251,146,60,.6)] sm:text-7xl" aria-hidden="true" :class="stats.currentStreak ? '' : 'grayscale opacity-50'">🔥</span>
                        <div>
                            <h2 id="h-streak" class="kicker-hero">Maior sequência de <span x-text="year"></span></h2>
                            <p class="text-4xl font-black tabular-nums sm:text-5xl" x-text="stats.weekStreak + (stats.weekStreak === 1 ? ' semana' : ' semanas')"></p>
                            <p class="text-sm text-white/70" x-text="stats.currentStreak ? 'Sequência atual: ' + stats.currentStreak + (stats.currentStreak === 1 ? ' semana' : ' semanas') + ' seguidas zerando algo' : 'Sem sequência ativa agora: zere um jogo esta semana para acender.'"></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-white/55">As últimas 12 semanas</p>
                        <ol class="mt-2 flex flex-wrap gap-1.5" aria-label="Semanas com e sem jogo zerado">
                            <template x-for="(w, i) in stats.recentWeeks" :key="i">
                                <li class="grid h-9 w-9 place-items-center rounded-lg text-lg" :class="w ? 'bg-orange-500/25 ring-1 ring-orange-400/50' : 'bg-white/[.05]'" :title="w ? 'Semana com jogo zerado' : 'Semana sem jogo'">
                                    <span x-show="w" aria-hidden="true">🔥</span><span x-show="!w" class="h-1.5 w-1.5 rounded-full bg-white/25"></span>
                                </li>
                            </template>
                        </ol>
                        <p class="mt-3 text-sm text-white/70"><strong class="text-white" x-text="stats.activeWeeks"></strong> de <span x-text="stats.weekCount"></span> semanas com pelo menos um jogo zerado (<span x-text="stats.weekCount ? Math.round(stats.activeWeeks / stats.weekCount * 100) : 0"></span>%).</p>
                    </div>
                </div>
            </section>

            <!-- Resumo -->
            <section class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Resumo do ano">
                <template x-for="c in cards" :key="c.label">
                    <article class="panel p-4">
                        <p class="text-2xs font-medium uppercase tracking-wider text-slate-500" x-text="c.label"></p>
                        <p class="mt-1 text-2xl font-black tabular-nums text-slate-50" x-text="c.value"></p>
                        <p class="mt-0.5 truncate text-xs text-slate-400" x-text="c.sub" :title="c.sub"></p>
                    </article>
                </template>
            </section>

            <!-- Calendário -->
            <section class="mt-6 panel p-4 sm:p-6" aria-label="Calendário do ano">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="t-section" x-text="layout === 'strip' ? 'O ano em uma faixa' : 'Doze calendários'"></h2>
                    <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-sm font-semibold" role="group" aria-label="Forma do calendário">
                        <button type="button" @click="setLayout('strip')" :aria-pressed="layout === 'strip'" class="seg-btn" :class="layout === 'strip' ? 'bg-slate-50/15 text-slate-50' : 'text-slate-400 hover:text-slate-50'">Faixa</button>
                        <button type="button" @click="setLayout('months')" :aria-pressed="layout === 'months'" class="seg-btn" :class="layout === 'months' ? 'bg-slate-50/15 text-slate-50' : 'text-slate-400 hover:text-slate-50'">12 calendários</button>
                    </div>
                </div>

                <!-- doze calendários de parede -->
                <div x-show="layout === 'months'" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <template x-for="mo in monthGrids" :key="mo.m">
                        <div class="rounded-xl border border-slate-50/10 p-3" :class="mo.total ? '' : 'opacity-70'">
                            <p class="flex items-baseline justify-between text-sm"><span class="font-bold capitalize text-slate-100" x-text="mo.label"></span><span class="text-xs tabular-nums text-slate-500" x-text="mo.total ? mo.total + (mo.total === 1 ? ' jogo' : ' jogos') : ''"></span></p>
                            <div class="mt-2 grid grid-cols-7 gap-[3px] text-center text-[9px] text-slate-500" aria-hidden="true">
                                <template x-for="(d, i) in ['D', 'S', 'T', 'Q', 'Q', 'S', 'S']" :key="i"><span x-text="d"></span></template>
                            </div>
                            <div class="mt-1 grid grid-cols-7 gap-[3px]">
                                <template x-for="(c, i) in mo.cells" :key="i">
                                    <button type="button" :disabled="!c" :tabindex="c ? 0 : -1" @click="c && select(c)" @mouseenter="c && (hover = c)" @mouseleave="hover = null"
                                            class="grid aspect-square place-items-center rounded-[3px] text-[9px] font-semibold tabular-nums transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                            :class="[c && c.count ? 'text-white' : 'text-slate-500', selected && c && selected.key === c.key ? 'ring-2 ring-slate-50' : '']"
                                            :style="c ? cellStyle(c) : 'visibility:hidden'" :title="c ? tip(c) : null" x-text="c ? c.date.getDate() : ''"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="layout === 'strip'" class="overflow-x-auto pb-2 [scrollbar-width:thin]">
                    <div class="inline-block min-w-full" :style="`--cell:${cell}rem`">
                        <div class="flex">
                            <!-- dias da semana -->
                            <div class="mr-2 grid shrink-0 text-2xs font-medium uppercase text-slate-500" style="grid-template-rows:repeat(7,var(--cell));gap:3px;margin-top:1.4rem" aria-hidden="true">
                                <template x-for="(d, i) in ['', 'Seg', '', 'Qua', '', 'Sex', '']" :key="i"><span class="flex items-center" x-text="d"></span></template>
                            </div>
                            <div>
                                <!-- meses -->
                                <div class="mb-1.5 grid h-[0.9rem] text-2xs font-medium text-slate-400" :style="`grid-template-columns:repeat(${weeks},var(--cell));column-gap:3px`" aria-hidden="true">
                                    <template x-for="m in monthLabels" :key="m.label">
                                        <span class="whitespace-nowrap" :style="`grid-column:${m.col + 1}`" x-text="m.label"></span>
                                    </template>
                                </div>
                                <!-- dias -->
                                <div class="grid" :style="`grid-auto-flow:column;grid-template-rows:repeat(7,var(--cell));grid-auto-columns:var(--cell);gap:3px`" role="grid" aria-label="Dias do ano">
                                    <template x-for="(c, i) in cells" :key="i">
                                        <button type="button" :disabled="c.pad" :tabindex="c.pad ? -1 : 0" :aria-hidden="c.pad ? 'true' : null"
                                                @mouseenter="!c.pad && (hover = c)" @mouseleave="hover = null" @focus="!c.pad && (hover = c)" @blur="hover = null" @click="!c.pad && select(c)"
                                                class="rounded-[3px] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                                :class="selected && selected.key === c.key ? 'ring-2 ring-slate-50' : ''"
                                                :style="c.pad ? 'visibility:hidden' : cellStyle(c)" :title="c.pad ? null : tip(c)" :aria-label="c.pad ? null : tip(c)"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="min-h-[1.25rem] text-sm text-slate-300" aria-live="polite" x-text="hover ? tip(hover) : 'Passe o mouse em um dia, ou clique para ver os jogos.'"></p>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500" aria-hidden="true">
                        <span>Menos</span>
                        <template x-for="l in [0, 1, 2, 3, 4]" :key="l"><span class="h-3.5 w-3.5 rounded-[3px]" :style="levelStyle(l)"></span></template>
                        <span>Mais</span>
                    </div>
                </div>
            </section>

            <!-- Dia selecionado -->
            <section x-show="selected" x-cloak class="mt-4 rounded-2xl border border-brand-400/40 bg-brand-500/10 p-5" aria-live="polite">
                <template x-if="selected">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <p class="t-section" x-text="longDate(selected.date)"></p>
                            <button type="button" @click="selected = null" class="rounded-lg px-2 py-1 text-sm text-slate-400 hover:bg-slate-50/10 hover:text-slate-50" aria-label="Fechar">✕</button>
                        </div>
                        <p x-show="!selected.games.length" class="mt-1 text-sm text-slate-400">Nenhum jogo zerado neste dia.</p>
                        <ul class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="g in selected.games" :key="g.id">
                                <li>
                                    <a :href="isDemo ? null : base + '/jogos/' + g.id" :class="isDemo ? 'cursor-default' : 'hover:border-brand-400/60'" class="flex items-center gap-3 rounded-xl border border-slate-50/10 bg-slate-900/70 p-3 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                        <span class="grid h-8 min-w-8 shrink-0 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow ring-1 ring-black/30" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-bold text-slate-50" x-text="g.title"></span>
                                            <span class="block truncate text-xs text-slate-400"><span x-text="g.platform"></span> · <span x-text="UI.fmtTime(g.playtime_minutes)"></span> · +<span x-text="g.xp"></span> XP</span>
                                        </span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </section>

            <!-- Termômetro dos meses + estações -->
            <section class="mt-6 grid gap-4 lg:grid-cols-[1.4fr_1fr]" aria-label="Meses e estações">
                <div class="panel p-5">
                    <h2 class="t-section">Termômetro dos meses</h2>
                    <p class="text-sm text-slate-400" x-text="hottestMonth ? 'O mês mais quente foi ' + hottestMonth + '.' : 'Nenhum jogo neste ano ainda.'"></p>
                    <ol class="mt-4 grid grid-cols-6 gap-1.5 sm:grid-cols-12">
                        <template x-for="(n, i) in stats.months" :key="i">
                            <li class="flex flex-col items-center gap-1">
                                <span class="grid h-14 w-full place-items-center rounded-lg text-sm font-black tabular-nums" :style="monthHeat(n)" x-text="n || ''"></span>
                                <span class="text-2xs font-semibold text-slate-400" x-text="monthNames[i]"></span>
                            </li>
                        </template>
                    </ol>
                </div>
                <div class="panel p-5">
                    <h2 class="t-section">Sua estação gamer</h2>
                    <p class="text-sm text-slate-400" x-text="topSeason && topSeason.n ? 'Você joga mais no ' + topSeason.name.toLowerCase() + '.' : 'Sem jogos para comparar.'"></p>
                    <ul class="mt-4 grid grid-cols-2 gap-2">
                        <template x-for="se in seasons" :key="se.name">
                            <li class="rounded-xl border p-3" :class="topSeason && se.name === topSeason.name && se.n ? 'border-gold-400/60 bg-gold-400/10' : 'border-slate-50/10'">
                                <p class="text-sm font-bold text-slate-100" x-text="se.icon + ' ' + se.name"></p>
                                <p class="text-2xl font-black tabular-nums text-slate-50" x-text="se.n"></p>
                                <p class="text-2xs text-slate-500" x-text="se.range"></p>
                            </li>
                        </template>
                    </ul>
                </div>
            </section>

            <!-- Dia da semana -->
            <section class="mt-6 panel p-5" aria-labelledby="h-dow">
                <h2 id="h-dow" class="t-section">Em que dia da semana você zera mais</h2>
                <ol class="mt-4 flex h-32 items-end gap-2 sm:gap-4">
                    <template x-for="(w, i) in stats.weekdays" :key="i">
                        <li class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1" :title="weekdayNames[i] + ': ' + w + (w === 1 ? ' jogo' : ' jogos')">
                            <span class="text-xs font-bold tabular-nums" :class="i === stats.topWeekdayIndex && w > 0 ? 'text-gold-500' : 'text-slate-300'" x-text="w || ''"></span>
                            <span class="w-full rounded-t-md" :class="i === stats.topWeekdayIndex && w > 0 ? 'rating-gold' : 'bg-gradient-to-t from-brand-600 to-accent'" :style="`height:${w ? Math.max(5, w / Math.max(...stats.weekdays) * 100) : 0}%`"></span>
                            <span class="text-2xs font-medium text-slate-400" x-text="weekdayNames[i].slice(0, 3)"></span>
                        </li>
                    </template>
                </ol>
            </section>
        </div>
    </template>
</main>

<script>
function heat(demo) {
    const base = window.APP.base;
    const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const MONTHS_LONG = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    const WEEKDAYS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    const pad = (n) => String(n).padStart(2, '0');
    const keyOf = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const parse = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const nf = (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);

    return {
        isDemo: !!demo, base,
        years: [], year: new Date().getFullYear(), days: {}, today: keyOf(new Date()),
        metric: 'games', metrics: [{ v: 'games', l: 'Jogos' }, { v: 'hours', l: 'Horas' }, { v: 'xp', l: 'XP' }].filter((m) => !(m.v === 'xp' && document.documentElement.dataset.calm)),
        ready: false, loading: false, error: '',
        cell: 0.95,                      // lado do quadradinho (rem)
        cells: [], weeks: 0, monthLabels: [], stats: null, cards: [],
        hover: null, selected: null,
        weekdayNames: WEEKDAYS,

        // ---------- navegação ----------
        neighbor(dir) { const i = this.years.indexOf(this.year); return this.years[i - dir] ?? null; },
        step(dir) { const n = this.neighbor(dir); if (n) this.go(n); },
        async go(y) {
            if (y === this.year && this.ready) return;
            this.selected = null;
            history.replaceState(null, '', location.pathname + '#' + y);
            await this.loadYear(y);
        },
        async loadYear(y) {
            if (demo) { this.year = y; this.days = demo.byYear[y].days; this.rebuild(); return; }
            this.loading = true; this.error = '';
            try {
                const r = await api.get('/api/heatmap?year=' + y);
                this.year = r.year; this.years = r.years; this.days = r.days; this.rebuild();
            } catch (e) { this.error = e.message; } finally { this.loading = false; }
        },
        async init() {
            this.error = '';
            try {
                if (demo) { this.years = demo.years; this.today = demo.today; }
                const h = Number(location.hash.slice(1));
                const start = h >= 1970 ? h : (demo ? demo.years[0] : new Date().getFullYear());
                await this.loadYear(start);
                this.ready = true;
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },
        setMetric(m) { this.metric = m; this.rebuild(); },
        layout: (() => { try { return localStorage.getItem('mgl.heat.layout') || 'strip'; } catch (e) { return 'strip'; } })(),
        setLayout(v) { this.layout = v; try { localStorage.setItem('mgl.heat.layout', v); } catch (e) { /* sem storage */ } },
        monthNames: MONTHS,
        /** os 12 meses como calendários (com espaços antes do dia 1) */
        get monthGrids() {
            const real = this.cells.filter((c) => !c.pad);
            return MONTHS_LONG.map((label, m) => {
                const days = real.filter((c) => c.month === m);
                const lead = days.length ? days[0].dow : 0;
                return { m, label, total: days.reduce((s, c) => s + c.count, 0), cells: [...Array(lead).fill(null), ...days] };
            });
        },
        monthHeat(n) {
            const max = Math.max(1, ...this.stats.months);
            if (!n) return 'background:rgb(var(--s-50) / .05);color:rgb(var(--s-500))';
            const t = n / max;   // frio (azul) → quente (laranja/vermelho)
            return `background:hsl(${Math.round(220 - t * 205)} 80% ${Math.round(48 - t * 6)}%);color:#fff`;
        },
        get hottestMonth() {
            const m = this.stats.months, max = Math.max(...m);
            return max ? MONTHS_LONG[m.indexOf(max)] + ' (' + max + (max === 1 ? ' jogo' : ' jogos') + ')' : null;
        },
        /** estações do hemisfério sul */
        get seasons() {
            const m = this.stats.months, sum = (...i) => i.reduce((s, k) => s + m[k], 0);
            return [
                { name: 'Verão', icon: '☀️', range: 'dez · jan · fev', n: sum(11, 0, 1) },
                { name: 'Outono', icon: '🍂', range: 'mar · abr · mai', n: sum(2, 3, 4) },
                { name: 'Inverno', icon: '❄️', range: 'jun · jul · ago', n: sum(5, 6, 7) },
                { name: 'Primavera', icon: '🌸', range: 'set · out · nov', n: sum(8, 9, 10) },
            ];
        },
        get topSeason() { return [...this.seasons].sort((a, b) => b.n - a.n)[0]; },
        select(c) { this.selected = this.selected && this.selected.key === c.key ? null : c; },

        // ---------- textos ----------
        longDate: (d) => `${d.getDate()} de ${MONTHS_LONG[d.getMonth()]} de ${d.getFullYear()}`,
        tip(c) {
            if (c.future) return this.longDate(c.date) + ' (ainda não chegou)';
            if (!c.count) return 'Nenhum jogo em ' + this.longDate(c.date);
            return `${c.count} ${c.count === 1 ? 'jogo' : 'jogos'} · ${nf(c.hours, 1)} h · ${nf(c.xp)} XP em ${this.longDate(c.date)}`;
        },

        // ---------- cores ----------
        levelStyle(l) {
            return ['background:rgb(var(--s-50) / .07)', 'background:rgb(var(--brand-500) / .32)', 'background:rgb(var(--brand-500) / .55)',
                    'background:rgb(var(--brand-500) / .8)', 'background:rgb(var(--brand-500));box-shadow:0 0 6px rgb(var(--brand-500) / .6)'][l];
        },
        cellStyle(c) {
            if (c.pad) return '';
            if (c.future) return 'background:transparent;box-shadow:inset 0 0 0 1px rgb(var(--s-50) / .08)';
            return this.levelStyle(c.level);
        },

        // ---------- cálculo ----------
        rebuild() {
            const y = this.year, first = new Date(y, 0, 1), start = first.getDay();
            const leap = (y % 4 === 0 && y % 100 !== 0) || y % 400 === 0;
            const total = leap ? 366 : 365;
            const todayD = parse(this.today);
            const cells = [];
            for (let i = 0; i < start; i++) cells.push({ pad: true });
            for (let i = 0; i < total; i++) {
                const date = new Date(y, 0, 1 + i), key = keyOf(date), games = this.days[key] ?? [];
                cells.push({
                    key, date, games, idx: i, week: Math.floor((start + i) / 7), dow: date.getDay(), month: date.getMonth(),
                    count: games.length, hours: games.reduce((s, g) => s + g.playtime_minutes, 0) / 60, xp: games.reduce((s, g) => s + g.xp, 0),
                    future: date > todayD, level: 0,
                });
            }
            while (cells.length % 7) cells.push({ pad: true });
            this.cells = cells;
            this.weeks = cells.length / 7;

            // níveis de cor conforme a métrica escolhida
            const val = (c) => (this.metric === 'games' ? c.count : this.metric === 'hours' ? c.hours : c.xp);
            const real = cells.filter((c) => !c.pad);
            const max = Math.max(0, ...real.map(val));
            for (const c of real) {
                const v = val(c);
                c.level = v <= 0 ? 0 : this.metric === 'games' ? Math.min(4, c.count) : Math.min(4, Math.max(1, Math.ceil(v / max * 4)));
            }

            // rótulos dos meses (na coluna da semana em que o mês começa)
            this.monthLabels = MONTHS.map((label, m) => ({ label, col: Math.floor((start + Math.round((new Date(y, m, 1) - first) / 86400000)) / 7) }));

            this.stats = this.computeStats(real, start, todayD);
            const s = this.stats;
            this.cards = [
                { label: 'Jogos zerados', value: nf(s.total), sub: `${nf(s.hours, 0)} h · +${nf(s.xp)} XP` },
                { label: 'Dias com jogo', value: nf(s.activeDays), sub: s.range ? `de ${nf(s.range)} dias` : '' },
                { label: 'Maior pausa', value: nf(s.gap.days) + (s.gap.days === 1 ? ' dia' : ' dias'), sub: s.gap.days ? `${s.gap.from} → ${s.gap.to}` : 'sem pausas' },
                { label: 'Dia mais forte', value: s.peak ? s.peak.count + (s.peak.count === 1 ? ' jogo' : ' jogos') : '—', sub: s.peak ? this.longDate(s.peak.date) : '' },
            ];
        },

        computeStats(real, start, todayD) {
            // só conta até hoje (o futuro não entra em pausas nem em semanas)
            const past = real.filter((c) => !c.future);
            const active = past.filter((c) => c.count > 0);
            const weeksSet = new Set(active.map((c) => c.week));
            const lastWeek = past.length ? past[past.length - 1].week : -1;

            let streak = 0, best = 0;
            for (let w = 0; w <= lastWeek; w++) { streak = weeksSet.has(w) ? streak + 1 : 0; best = Math.max(best, streak); }
            // sequência atual: conta para trás a partir desta semana (se ela ainda está vazia, a partir da anterior)
            let cur = 0;
            for (let w = weeksSet.has(lastWeek) ? lastWeek : lastWeek - 1; w >= 0 && weeksSet.has(w); w--) cur++;
            const recentWeeks = [];
            for (let w = Math.max(0, lastWeek - 11); w <= lastWeek; w++) recentWeeks.push(weeksSet.has(w));

            // maior pausa: sequência de dias sem nenhum jogo (inclui o começo do ano e o trecho até hoje)
            let run = 0, runStart = null, gap = { days: 0, from: '', to: '' };
            const short = (d) => `${d.getDate()}/${pad(d.getMonth() + 1)}`;
            for (const c of past) {
                if (c.count === 0) { if (!run) runStart = c.date; run++; if (run > gap.days) gap = { days: run, from: short(runStart), to: short(c.date) }; }
                else run = 0;
            }

            const weekdays = Array(7).fill(0), months = Array(12).fill(0);
            for (const c of past) { weekdays[c.dow] += c.count; months[c.month] += c.count; }
            const peak = active.length ? active.reduce((a, c) => (c.count >= a.count ? c : a)) : null;
            return {
                total: past.reduce((s, c) => s + c.count, 0),
                hours: past.reduce((s, c) => s + c.hours, 0),
                xp: past.reduce((s, c) => s + c.xp, 0),
                activeDays: active.length, range: past.length,
                activeWeeks: weeksSet.size, weekCount: lastWeek + 1, weekStreak: best, currentStreak: cur, recentWeeks, gap,
                weekdays, months, peak: peak && { count: peak.count, date: peak.date },
                topWeekdayIndex: weekdays.indexOf(Math.max(...weekdays)),
            };
        },
    };
}
</script>
