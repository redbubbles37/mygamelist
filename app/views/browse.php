<?php
/** @var array|null $me */
/** @var array $cfg  dim, param, title, unit, units, sort */
/** @var array|null $demo  agrupamentos fictícios da página de exemplo */
$demo = $demo ?? null;
?>
<main class="page page-wide"
      x-data='browse(<?= e(json_encode($cfg, JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): $note = 'Clique em um cartão para abrir o catálogo de exemplo já filtrado.'; ?>
    <?php ob_start(); ?> com dados fictícios. <?= $note ?><?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="t-page"><?= e($cfg['title']) ?></h1>
            <p class="mt-1 text-sm text-slate-400" aria-live="polite">
                <template x-if="ready"><span>
                    <span x-text="groupsWithGames"></span> <span x-text="groupsWithGames === 1 ? cfg.unit : cfg.units"></span>
                    · <span x-text="total"></span> <span x-text="total === 1 ? 'jogo' : 'jogos'"></span>
                </span></template>
            </p>
        </div>
        <div x-show="!fixedOrder" x-cloak class="flex flex-wrap items-center gap-3">
            <label class="relative">
                <span class="sr-only">Buscar</span>
                <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                <input type="search" x-model="q" :placeholder="'Buscar ' + cfg.unit + '…'"
                       class="w-48 rounded-lg border border-slate-50/10 bg-slate-900/70 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-brand-500">
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-400">Ordenar
                <select x-model="sortKey" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                    <template x-for="s in sorts" :key="s.v"><option :value="s.v" x-text="s.l"></option></template>
                </select>
            </label>
        </div>
    </header>

    <?php require __DIR__ . '/partials/browse_hero.php'; ?>

    <!-- notas: a regra das notas altas e quanto falta para a próxima -->
    <section x-show="ready && quota" x-cloak class="mt-6 panel p-5" aria-labelledby="h-quota">
        <h2 id="h-quota" class="text-base font-bold text-slate-50">Notas altas são raras</h2>
        <p class="mt-1 text-sm text-slate-400">Cada <strong class="text-slate-200">10 jogos zerados</strong> liberam <strong class="text-slate-200">1 nota 10</strong>, e cada <strong class="text-slate-200">50</strong> liberam <strong class="text-slate-200">1 nota 11</strong>. As notas que você já deu nunca mudam.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <template x-for="n in [11, 10]" :key="n">
                <div class="flex items-center gap-4 rounded-xl border border-slate-50/10 bg-slate-950/40 p-3.5">
                    <span class="grid h-12 min-w-12 shrink-0 place-items-center rounded-xl px-2 text-xl font-black shadow ring-1 ring-black/30" :class="UI.ratingClass(n)" :style="UI.ratingStyle(n)" x-text="n"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-100" x-text="quotaTitle(n)"></span>
                        <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-slate-50/10" role="progressbar" :aria-valuenow="quotaPct(n)" aria-valuemin="0" aria-valuemax="100" :aria-label="'Progresso até a próxima nota ' + n">
                            <span class="block h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="'width:' + quotaPct(n) + '%'"></span>
                        </span>
                        <span class="mt-1 block text-xs text-slate-400" x-text="quotaHint(n)"></span>
                    </span>
                </div>
            </template>
        </div>
    </section>

    <p x-show="ready && missing > 0" x-cloak class="mt-4 rounded-lg border border-slate-50/10 bg-slate-900/60 px-4 py-2.5 text-sm text-slate-400">
        <span x-text="missing"></span> <span x-text="missing === 1 ? 'jogo está' : 'jogos estão'"></span> sem este dado e <span x-text="missing === 1 ? 'não aparece' : 'não aparecem'"></span> aqui. Edite-<span x-text="missing === 1 ? 'o' : 'os'"></span> no catálogo para incluir.
    </p>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <div x-show="!ready && !error" class="mt-6 grid animate-pulse gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-label="Carregando">
        <template x-for="i in 6"><div class="h-64 rounded-2xl bg-slate-50/5"></div></template>
    </div>

    <section x-show="ready && groups.length === 0" x-cloak class="mt-6 empty">
        <p class="t-section">Nada por aqui ainda</p>
        <p class="mt-1 text-sm text-slate-400">Cadastre jogos informando o(a) <?= e($cfg['unit']) ?> e eles aparecem agrupados nesta página.</p>
        <a href="<?= e(url('/jogos/novo')) ?>" class="btn mt-4 inline-block">Adicionar jogo</a>
    </section>

    <p x-show="ready && groups.length > 0 && shown.length === 0" x-cloak class="mt-8 text-center text-slate-400">Nenhum resultado para "<span x-text="q"></span>".</p>

    <template x-for="sec in sections" :key="sec.key">
    <div x-show="mode === 'cards' && sec.groups.length" x-cloak class="mt-8">
    <h2 x-show="sec.label" class="mb-4 flex items-baseline gap-3 border-b border-slate-50/10 pb-2 text-xl font-bold text-slate-50"><span class="h-3 w-3 shrink-0 self-center rounded-full" :style="'background:' + sec.color"></span><span x-text="sec.label"></span><span class="text-sm font-normal text-slate-400" x-text="sec.games + (sec.games === 1 ? ' jogo' : ' jogos') + ' · ' + sec.groups.length + (sec.groups.length === 1 ? ' sistema' : ' sistemas')"></span></h2>
    <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <template x-for="g in sec.groups" :key="g.value">
            <li class="flex flex-col overflow-hidden rounded-2xl border bg-slate-900/70 transition duration-200 motion-reduce:transition-none"
                :class="[g.games ? 'hover:-translate-y-1 hover:shadow-xl motion-reduce:hover:translate-y-0' : 'opacity-60', g.best ? 'border-gold-400/40 hover:border-gold-400/70' : 'border-slate-50/10 hover:border-brand-400/60']">
                <a :href="g.games ? href(g) : null" :aria-disabled="g.games ? null : 'true'"
                   class="group block flex-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-400"
                   :class="g.games ? '' : 'cursor-default'">

                    <div x-show="cfg.dim === 'platform'" class="case-band !h-8 !text-xs" :style="UI.bandStyle(g.value)"><span x-text="g.value"></span><span class="case-date" x-text="g.games ? period(g) : ''"></span></div>
                    <!-- mosaico das capas do grupo (com best of: ele abre o mosaico, com moldura dourada) -->
                    <div class="relative grid h-44 grid-cols-4 bg-slate-950">
                        <template x-for="(c, i) in mosaic(g)" :key="i">
                            <div class="relative overflow-hidden border-r border-black/40 last:border-r-0" :class="c.best ? 'col-span-2' : ''">
                                <img x-show="c.url" :src="c.url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                <div x-show="!c.url" class="absolute inset-0 bg-gradient-to-br from-neutral to-brand-600/30" :style="'opacity:' + (1 - i * 0.18)"></div>
                                <div x-show="c.best" class="best-shine pointer-events-none absolute inset-0 ring-[3px] ring-inset ring-gold-400/90"></div>
                            </div>
                        </template>
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                        <span x-show="g.best" x-cloak class="best-badge absolute left-3 top-3">🏆 Best of</span>
                        <!-- dificuldade: letra grande com a cor do selo -->
                        <span x-show="cfg.dim === 'difficulty'" x-cloak class="absolute left-3 top-3 grid h-12 min-w-12 place-items-center rounded-xl px-2 text-xl font-black shadow-lg ring-1 ring-black/40" :style="UI.diffStyle(g.value)" x-text="g.value"></span>
                        <span x-show="cfg.dim === 'rating'" x-cloak class="absolute left-3 top-3 grid h-12 min-w-12 place-items-center rounded-xl px-2 text-xl font-black shadow-lg ring-1 ring-black/40" :class="UI.ratingClass(Number(g.value))" :style="UI.ratingStyle(Number(g.value))" x-text="g.value"></span>
                        <span x-show="cfg.dim === 'rating' && quota && quota.per[g.value]" x-cloak class="absolute right-3 top-3 rounded-full bg-black/70 px-2.5 py-1 text-xs font-bold text-white" x-text="quota && quota.per[g.value] ? usedText(Number(g.value)) : ''"></span>
                        <span x-show="cfg.dim === 'release_year'" x-cloak class="absolute bottom-3 left-4 text-4xl font-black tracking-tight text-white [text-shadow:0_2px_6px_rgb(0_0_0/.7)]" x-text="g.value"></span>
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="min-w-0 truncate text-lg font-bold text-slate-50" :title="label(g)" x-text="label(g)"></h3>
                            <span x-show="g.games && cfg.dim !== 'rating'" class="shrink-0 rounded-lg px-2 py-1 text-sm font-black leading-none shadow ring-1 ring-black/30"
                                  :class="UI.ratingClass(Math.round(g.avg_rating))" :style="UI.ratingStyle(Math.round(g.avg_rating))"
                                  :title="'Nota média ' + g.avg_rating.toLocaleString('pt-BR')" x-text="g.avg_rating.toLocaleString('pt-BR', { minimumFractionDigits: 1 })"></span>
                        </div>
                        <p x-show="hint(g)" class="text-xs text-slate-500" x-text="hint(g)"></p>

                        <template x-if="g.games">
                            <div>
                                <p class="mt-2 flex items-center gap-4 text-sm text-slate-300">
                                    <span><strong class="text-slate-50" x-text="g.games"></strong> <span x-text="g.games === 1 ? 'jogo' : 'jogos'"></span></span>
                                    <span class="text-slate-400"><strong class="font-semibold text-slate-200" x-text="Math.round(g.hours).toLocaleString('pt-BR')"></strong> h jogadas</span>
                                </p>
                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-50/10" role="presentation">
                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="`width:${Math.max(3, g.games / total * 100)}%`"></div>
                                </div>
                                <p class="mt-1 flex justify-between gap-2 text-2xs text-slate-500"><span x-text="extraLine(g)"></span><span><span x-text="Math.round(g.games / total * 100)"></span>% do catálogo</span></p>
                            </div>
                        </template>
                        <p x-show="!g.games" class="mt-2 text-sm text-slate-500">Nenhum jogo ainda</p>
                    </div>
                </a>

                <!-- best of do grupo -->
                <template x-if="hasBest && g.games">
                    <div class="border-t px-4 py-3" :class="g.best ? 'border-gold-400/25 bg-gradient-to-r from-gold-500/10 to-transparent' : 'border-slate-50/10'">
                        <template x-if="g.best">
                            <div class="flex items-center gap-3">
                                <a :href="gameHref(g.best)" class="best-frame h-14 w-10 shrink-0" :title="g.best.title">
                                    <img x-show="g.best.cover_url" :src="g.best.cover_url" alt="" loading="lazy">
                                    <span x-show="!g.best.cover_url" class="best-frame-inner grid place-items-center bg-slate-800 text-xs font-bold text-white/70" x-text="UI.initials(g.best.title)"></span>
                                </a>
                                <div class="min-w-0 flex-1">
                                    <p class="best-kicker !text-2xs">Best of <span x-text="cfg.unit"></span></p>
                                    <a :href="gameHref(g.best)" class="block truncate text-sm font-bold text-slate-50 hover:underline" :title="g.best.title" x-text="g.best.title"></a>
                                    <p class="truncate text-2xs text-slate-500" x-text="'Nota ' + g.best.rating + ' · escolhido entre ' + g.games + ' jogos'"></p>
                                </div>
                                <button type="button" x-show="!isDemo" @click="openBest(g)" class="shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-gold-500 transition hover:bg-gold-400/10">Trocar</button>
                            </div>
                        </template>
                        <template x-if="!g.best">
                            <div>
                                <button type="button" x-show="!isDemo && g.games >= bestMin" @click="openBest(g)"
                                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-gold-400/40 px-3 py-2 text-sm font-semibold text-gold-500 transition hover:border-gold-400/80 hover:bg-gold-400/10">
                                    🏆 Escolher o best of
                                </button>
                                <p x-show="g.games < bestMin" class="text-center text-xs text-slate-500" x-text="'Best of liberado com ' + bestMin + ' jogos zerados'"></p>
                            </div>
                        </template>
                    </div>
                </template>
            </li>
        </template>
    </ul>
    </div>
    </template>

    <?php require __DIR__ . '/partials/browse_lists.php'; ?>
</main>

<?php if ($demo === null && in_array($cfg['dim'], ['platform', 'genre', 'developer', 'publisher'], true)) require __DIR__ . '/partials/best_of.php'; ?>

<script>
function browse(cfg, demo) {
    const base = window.APP.base;
    const HINTS = { C: 'A mais tranquila', AAA: 'A mais difícil' };
    const RATING_HINTS = { 11: 'Lendário', 10: 'Obra-prima', 9: 'Excelente', 8: 'Muito bom', 7: 'Bom', 6: 'Ok', 5: 'Mediano', 4: 'Fraco', 3: 'Ruim', 2: 'Muito ruim', 1: 'O fundo do poço' };
    const fixed = cfg.dim === 'difficulty' || cfg.dim === 'rating';
    const isYear = cfg.dim === 'release_year';

    return {
        cfg,
        isDemo: !!demo,
        hasBest: ['platform', 'genre', 'developer', 'publisher'].includes(cfg.dim),
        bestMin: 2,
        groups: [],
        quota: null,
        total: 0,
        fixedOrder: fixed,
        missing: 0,
        q: '',
        sortKey: cfg.sort,
        ready: false,
        error: '',
        sorts: [
            { v: 'games:desc', l: 'Mais jogos' },
            { v: 'rating:desc', l: 'Maior nota média' },
            { v: 'hours:desc', l: 'Mais horas' },
            isYear ? { v: 'name:desc', l: 'Mais recentes' } : { v: 'name:asc', l: 'Nome A–Z' },
            isYear ? { v: 'name:asc', l: 'Mais antigos' } : { v: 'name:desc', l: 'Nome Z–A' },
        ],

        get groupsWithGames() { return this.groups.filter((g) => g.games > 0).length; },
        get withGames() { return this.groups.filter((g) => g.games > 0); },
        fmt: (v) => new Intl.NumberFormat('pt-BR').format(v ?? 0),
        fmtH(h) { return h >= 10 ? Math.round(h) + ' h' : (Math.round(h * 10) / 10).toLocaleString('pt-BR') + ' h'; },
        pct(n) { return this.total ? Math.round(n / this.total * 100) : 0; },

        // ---- como cada página mostra a lista ----
        /** cards = cartões (com divisões); board = ranking (estúdios e publishers); decades = anos por década */
        get mode() { return cfg.dim === 'developer' || cfg.dim === 'publisher' ? 'board' : isYear ? 'decades' : 'cards'; },
        /** o grupo com mais jogos (o "campeão" do destaque) */
        get top() { return [...this.withGames].sort((a, b) => b.games - a.games || b.hours - a.hours)[0] ?? { value: '', games: 0, hours: 0, years: [0, 0], covers: [] }; },
        get heroCover() { return this.top.best?.cover_url ?? this.top.covers?.[0] ?? null; },
        period(g) { return !g.years?.[0] ? '' : g.years[0] === g.years[1] ? 'em ' + g.years[0] : g.years[0] + '–' + g.years[1]; },
        extraLine(g) {
            if (!g.games) return '';
            if (cfg.dim === 'platform') return g.years?.[0] ? (g.years[0] === g.years[1] ? 'Jogado em ' + g.years[0] : 'Jogado de ' + g.years[0] + ' a ' + g.years[1]) : '';
            if (cfg.dim === 'difficulty' || cfg.dim === 'rating') return 'média de ' + this.fmtH(g.hours / g.games) + ' por jogo';
            if (cfg.dim === 'genre') return g.years?.[0] ? 'desde ' + g.years[0] : '';
            return '';
        },

        // ---- sistemas: famílias ----
        family(name) {
            const n = String(name).toLowerCase();
            if (/playstation|^ps\s?\d|^ps$|psp|vita|^ps one|psone/.test(n)) return 'ps';
            if (/xbox/.test(n)) return 'xbox';
            if (/nintendo|switch|wii|^n?ds|3ds|game ?boy|gba|gbc|snes|^nes|famicom|n64|gamecube|virtual boy/.test(n)) return 'nintendo';
            if (/sega|mega ?drive|genesis|saturn|dreamcast|master system|game gear|32x/.test(n)) return 'sega';
            if (/^pc$|windows|steam|mac|linux|dos|computador/.test(n)) return 'pc';
            if (/arcade|neo ?geo|mame|cps/.test(n)) return 'arcade';
            if (/android|ios|iphone|mobile|celular/.test(n)) return 'mobile';
            return 'other';
        },
        FAMILIES: {
            ps: { label: 'PlayStation', color: '#3b5bdb' }, nintendo: { label: 'Nintendo', color: '#e03131' }, xbox: { label: 'Xbox', color: '#2f9e44' },
            sega: { label: 'Sega', color: '#1c7ed6' }, pc: { label: 'PC', color: '#868e96' }, arcade: { label: 'Arcade', color: '#f59f00' },
            mobile: { label: 'Celular', color: '#ae3ec9' }, other: { label: 'Outros', color: '#adb5bd' },
        },
        get families() {
            const by = {};
            for (const g of this.withGames) { const k = this.family(g.value); (by[k] ??= { key: k, ...this.FAMILIES[k], games: 0, groups: [] }); by[k].games += g.games; by[k].groups.push(g); }
            return Object.values(by).sort((a, b) => b.games - a.games);
        },
        /** divisões dos cartões: por família nos sistemas; uma só nas outras páginas */
        get sections() {
            if (cfg.dim !== 'platform' || this.q.trim()) return [{ key: 'all', label: '', color: '', games: 0, groups: this.shown }];
            const order = this.families.map((f) => f.key);
            const by = {};
            for (const g of this.shown) { const k = this.family(g.value); (by[k] ??= { key: k, ...this.FAMILIES[k], games: 0, groups: [] }); by[k].games += g.games; by[k].groups.push(g); }
            return Object.values(by).sort((a, b) => (order.indexOf(a.key) + 99 * (a.key === 'other')) - (order.indexOf(b.key) + 99 * (b.key === 'other')));
        },

        // ---- gêneros: nuvem ----
        get cloud() { return [...this.withGames].sort((a, b) => a.value.localeCompare(b.value, 'pt')); },
        cloudSize(g) { const max = this.top.games || 1; return (0.8 + 1.5 * Math.sqrt(g.games / max)).toFixed(2); },
        get bestRated() { return [...this.withGames].filter((g) => g.games >= 3).sort((a, b) => b.avg_rating - a.avg_rating || b.games - a.games)[0] ?? null; },

        // ---- estúdios e publishers: pódio e links das parceiras ----
        get podium() {
            const t = [...this.withGames].sort((a, b) => b.games - a.games || b.hours - a.hours).slice(0, 3);
            const looks = [
                { medal: '🥇', order: 'sm:order-2', bar: 'bg-gradient-to-t from-gold-500/60 to-gold-300/80', h: 4 },
                { medal: '🥈', order: 'sm:order-1', bar: 'bg-gradient-to-t from-slate-400/50 to-slate-200/70', h: 2.75 },
                { medal: '🥉', order: 'sm:order-3', bar: 'bg-gradient-to-t from-orange-800/60 to-orange-500/70', h: 1.75 },
            ];
            return t.map((g, i) => ({ g, ...looks[i] }));
        },
        partnerHref(p) { return `${base}/jogos${demo ? '/exemplo' : ''}?${cfg.dim === 'developer' ? 'publisher' : 'developer'}=${encodeURIComponent(p.name)}`; },

        // ---- lançamento: linha do tempo e décadas ----
        decadeColor(y) { const d = Math.floor(Number(y) / 10) - 195; return `hsl(${(d * 52 + 250) % 360} 70% 58%)`; },
        get timeline() {
            const years = this.withGames.map((g) => Number(g.value)).filter(Boolean);
            if (!years.length) return { from: 0, to: 0, max: 1, years: [] };
            const from = Math.min(...years), to = Math.max(...years), by = Object.fromEntries(this.withGames.map((g) => [Number(g.value), g.games]));
            const list = [];
            for (let y = from; y <= to; y++) list.push({ year: y, games: by[y] ?? 0 });
            return { from, to, max: Math.max(...list.map((y) => y.games)), years: list };
        },
        decadeOf(groups) {
            const by = {};
            for (const g of groups) {
                const start = Math.floor(Number(g.value) / 10) * 10;
                (by[start] ??= { key: 'd' + start, start, label: start >= 2000 ? 'Anos ' + start : 'Anos ' + String(start).slice(2), games: 0, hours: 0, groups: [] });
                by[start].games += g.games; by[start].hours += g.hours; by[start].groups.push(g);
            }
            return Object.values(by);
        },
        /** décadas do destaque: a com mais jogos primeiro */
        get decades() { return this.decadeOf(this.withGames).sort((a, b) => b.games - a.games); },
        /** décadas da lista: na ordem escolhida (os anos de cada uma seguem a ordenação) */
        get decadesShown() {
            const list = this.decadeOf(this.shown.filter((g) => g.games));
            const [key, dir] = this.sortKey.split(':');
            return key === 'name' ? list.sort((a, b) => (dir === 'asc' ? 1 : -1) * (a.start - b.start)) : list.sort((a, b) => b.games - a.games);
        },

        // ---- notas: distribuição ----
        get ratingBars() { return [...this.groups].map((g) => ({ n: Number(g.value), games: g.games })).sort((a, b) => a.n - b.n); },
        get ratingMax() { return Math.max(1, ...this.ratingBars.map((r) => r.games)); },
        get ratingStats() {
            const all = []; for (const r of this.ratingBars) for (let i = 0; i < r.games; i++) all.push(r.n);
            if (!all.length) return { avg: '—', median: '—', mode: '—' };
            const avg = all.reduce((s, n) => s + n, 0) / all.length, mid = all.length >> 1;
            const median = all.length % 2 ? all[mid] : (all[mid - 1] + all[mid]) / 2;
            const mode = [...this.ratingBars].sort((a, b) => b.games - a.games || b.n - a.n)[0].n;
            return { avg: avg.toFixed(1).replace('.', ','), median: String(median).replace('.', ','), mode };
        },

        // ---- dificuldades: quanto foi difícil ----
        get hardPct() { const hard = this.groups.filter((g) => ['A', 'AA', 'AAA'].includes(g.value)).reduce((s, g) => s + g.games, 0); return this.pct(hard); },

        get shown() {
            const q = this.q.trim().toLowerCase();
            const list = this.groups.filter((g) => !q || g.value.toLowerCase().includes(q));
            if (fixed) return list; // ordem fixa: C -> AAA, 11 -> 1

            const [key, dir] = this.sortKey.split(':');
            const mul = dir === 'asc' ? 1 : -1;
            const cmp = {
                games: (a, b) => a.games - b.games,
                rating: (a, b) => a.avg_rating - b.avg_rating,
                hours: (a, b) => a.hours - b.hours,
                name: (a, b) => isYear ? Number(a.value) - Number(b.value) : a.value.localeCompare(b.value, 'pt', { sensitivity: 'base' }),
            }[key];
            return [...list].sort((a, b) => mul * cmp(a, b) || b.hours - a.hours || a.value.localeCompare(b.value, 'pt')); // empate: mais horas primeiro (igual ao pódio)
        },

        label(g) { return cfg.dim === 'difficulty' ? 'Dificuldade ' + g.value : cfg.dim === 'rating' ? 'Nota ' + g.value : g.value; },
        hint(g) { return cfg.dim === 'difficulty' ? HINTS[g.value] ?? '' : cfg.dim === 'rating' ? RATING_HINTS[g.value] ?? '' : ''; },

        // ---- cota das notas altas (1 nota 10 a cada 10 jogos, 1 nota 11 a cada 50) ----
        allowed(n) { return Math.floor(this.quota.games / this.quota.per[n]); },
        /** jogos que ainda faltam zerar para liberar a próxima (o próximo jogo cadastrado conta) */
        missingFor(n) { return Math.max(1, (this.quota.used[n] + 1) * this.quota.per[n] - this.quota.games); },
        usedText(n) { const a = this.allowed(n); return this.quota.used[n] + ' de ' + a + (a === 1 ? ' vaga' : ' vagas'); },
        quotaTitle(n) {
            if (!this.quota) return '';
            const free = this.allowed(n) - this.quota.used[n];
            if (free > 0) return `Nota ${n}: ${free} ${free === 1 ? 'vaga livre' : 'vagas livres'} (${this.quota.used[n]} de ${this.allowed(n)} usadas)`;
            if (free < 0) return `Nota ${n}: ${-free} acima do limite (${this.quota.used[n]} de ${this.allowed(n)})`;
            return `Nota ${n}: todas as vagas usadas (${this.quota.used[n]} de ${this.allowed(n)})`;
        },
        quotaHint(n) {
            if (!this.quota) return '';
            const m = this.missingFor(n), free = this.allowed(n) - this.quota.used[n];
            return free > 0 ? `Você pode dar ${free === 1 ? 'mais uma' : 'mais ' + free} já no próximo jogo.` : `Faltam ${m} ${m === 1 ? 'jogo' : 'jogos'} para liberar a próxima.`;
        },
        /** quanto do caminho até a próxima vaga já foi percorrido */
        quotaPct(n) {
            if (!this.quota) return 0;
            const per = this.quota.per[n], free = this.allowed(n) - this.quota.used[n];
            if (free > 0) return 100;
            const target = (this.quota.used[n] + 1) * per, start = target - per;
            return Math.max(0, Math.min(100, Math.round((this.quota.games - start) / per * 100)));
        },
        href(g) { return `${base}/jogos${demo ? '/exemplo' : ''}?${cfg.param}=${encodeURIComponent(g.value)}`; },
        gameHref(game) { return game ? (demo ? `${base}/jogos/exemplo` : `${base}/jogos/${game.id}`) : null; },

        // ---- best of ----
        /** Capas do mosaico: com best of, ele vem primeiro (ocupando 2 colunas) e as outras completam. */
        mosaic(g) {
            if (!g.best) return [0, 1, 2, 3].map((i) => ({ url: g.covers[i] ?? null, best: false }));
            const rest = g.covers.filter((u) => u !== g.best.cover_url).slice(0, 2);
            return [{ url: g.best.cover_url, best: true }, { url: rest[0] ?? null, best: false }, { url: rest[1] ?? null, best: false }];
        },
        openBest(g) { this.$dispatch('best-open', { kind: cfg.dim, value: g.value, label: g.value, current: g.best }); },
        onBestSaved(d) {
            if (d.kind !== cfg.dim) return;
            const g = this.groups.find((x) => x.value === d.value);
            if (g) g.best = d.best;
        },

        async init() {
            window.addEventListener('best-saved', (e) => this.onBestSaved(e.detail));
            await this.load();
        },

        async load() {
            this.error = '';
            try {
                const r = demo ?? await api.get('/api/me/groups/' + cfg.dim);
                this.groups = r.data;
                this.total = r.total;
                this.missing = r.missing;
                this.quota = r.quota ?? null;
                this.bestMin = r.best_min ?? 2;
                this.ready = true;
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },
    };
}
</script>
