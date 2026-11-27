<?php
/** @var array $me */
/** @var array|null $demo  estatísticas fictícias da página de exemplo (nulo no painel real) */
$demo = $demo ?? null;
?>
<main class="page page-normal" x-data='dashboard(<?= e(json_encode($me, JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): $note = 'Troque o ano dos números para ver como ficam; nada aqui é do seu catálogo.'; ?>
    <?php ob_start(); ?> com dados fictícios. <?= $note ?><?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <!-- ========== Destaque: saudação, nível e os números do ano ========== -->
    <header class="hero-box text-white">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="heroCover" :src="heroCover" alt="" class="h-full w-full scale-150 object-cover opacity-30 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/65 to-brand-700/30"></div>
        </div>

        <div class="flex flex-wrap items-start justify-between gap-5 p-5 sm:p-8">
            <div class="min-w-0 max-w-2xl">
                <p class="text-sm text-white/65"><span x-text="greeting"></span>, <strong class="font-semibold text-white" x-text="me.username"></strong></p>
                <h1 class="mt-1 t-hero">Seu painel</h1>
                <p x-show="extra?.hello" x-cloak class="mt-2 text-sm leading-relaxed text-white/80 sm:text-base" x-text="extra?.hello"></p>
                <button type="button" @click="editing = !editing" :aria-expanded="editing" aria-controls="dash-edit"
                        class="mt-4 rounded-xl border px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                        :class="editing ? 'border-white bg-white text-black' : 'border-white/25 bg-black/30 text-white hover:bg-white/10'">⚙ Personalizar</button>
            </div>
            <!-- nível com o anel de XP -->
            <div x-show="data && !calm" x-cloak class="flex items-center gap-3">
                <div class="relative h-20 w-20 shrink-0">
                    <svg class="h-full w-full -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="rgb(255 255 255 / .12)" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#fbbf24" stroke-width="3" stroke-linecap="round" :stroke-dasharray="((data?.xp.progress_pct ?? 0) * 0.974) + ' 100'"/>
                    </svg>
                    <span class="absolute inset-0 grid place-items-center text-center leading-none"><span><span class="block text-2xs uppercase tracking-wider text-white/55">nível</span><span class="text-2xl font-black" x-text="data?.xp.level"></span></span></span>
                </div>
                <p class="text-xs text-white/60"><span class="block text-sm font-bold text-white" x-text="fmt(data?.xp.total_xp) + ' XP'"></span><span x-show="data?.xp.next_level_xp" x-text="'faltam ' + fmt((data?.xp.next_level_xp ?? 0) - (data?.xp.total_xp ?? 0)) + ' para o próximo'"></span></p>
            </div>
        </div>

        <!-- os números (o bloco "Números e meta do ano" do painel montável) -->
        <div x-show="numbersOn" x-cloak class="border-t border-white/10 bg-black/30 px-5 py-5 sm:px-8" aria-label="Números e meta do ano">
            <div class="kicker-hero flex flex-wrap items-center gap-2">
                <span>Números de</span>
                <label class="sr-only" for="dash-year">Ano dos números</label>
                <select id="dash-year" x-model.number="year" @change="changeYear()" :disabled="loading"
                        class="rounded-lg border border-white/20 bg-black/40 px-2 py-1 text-sm font-bold normal-case tracking-normal text-white outline-none focus:ring-2 focus:ring-white/60">
                    <template x-for="y in years" :key="y"><option :value="y" x-text="y"></option></template>
                </select>
                <span class="normal-case tracking-normal text-white/40">· vale para os números, os gráficos e a trilha</span>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-white/55">Jogos zerados</dt>
                    <dd class="text-3xl font-black tabular-nums" x-text="fmt(data?.totals.year.games)"></dd>
                    <dd class="text-xs text-white/55" x-text="fmt(data?.totals.all.games) + ' no total'"></dd>
                </div>
                <div>
                    <dt class="text-xs text-white/55">Horas jogadas</dt>
                    <dd class="text-3xl font-black tabular-nums" x-text="fmt(data?.totals.year.hours, 0) + 'h'"></dd>
                    <dd class="text-xs text-white/55" x-text="fmt(data?.totals.all.hours, 0) + 'h no total'"></dd>
                </div>
                <div>
                    <dt class="text-xs text-white/55">Nota média</dt>
                    <dd class="text-3xl font-black tabular-nums" x-text="data?.totals.year.avg_rating != null ? fmt(data.totals.year.avg_rating, 1) : '—'"></dd>
                    <dd class="text-xs text-white/55" x-text="'geral: ' + (data?.totals.all.avg_rating != null ? fmt(data.totals.all.avg_rating, 1) : '—') + ' · escala 1–11'"></dd>
                </div>
            </dl>
            <!-- meta do ano -->
            <div x-show="data?.goal" class="mt-5 grid gap-x-6 gap-y-3 border-t border-white/10 pt-4 sm:grid-cols-[auto_1fr_1fr] sm:items-center">
                <a :href="base + (isDemo ? '/metas/exemplo' : '/metas')" class="kicker-hero hover:text-white">Meta de <span x-text="data?.goal?.year"></span> →</a>
                <template x-for="k in ['games', 'hours']" :key="k">
                    <div x-show="data?.goal?.[k]">
                        <div class="flex items-baseline justify-between text-xs"><span class="font-semibold text-white/85" x-text="k === 'games' ? 'Jogos' : 'Horas'"></span>
                            <span class="tabular-nums text-white/60"><strong class="text-white" x-text="fmt(data?.goal?.[k]?.done)"></strong> / <span x-text="fmt(data?.goal?.[k]?.target)"></span> · <span x-text="data?.goal?.[k] ? Math.round(data.goal[k].done / data.goal[k].target * 100) + '%' : ''"></span></span></div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full" :class="data?.goal?.[k]?.done >= data?.goal?.[k]?.target ? 'bg-ok' : 'bg-gradient-to-r from-gold-500 to-gold-300'" :style="data?.goal?.[k] ? `width:${Math.min(100, data.goal[k].done / data.goal[k].target * 100)}%` : ''"></div></div>
                    </div>
                </template>
            </div>
        </div>
    </header>

    <!-- Painel montável: escolha o que aparece e em que ordem -->
    <section id="dash-edit" x-show="editing" x-cloak class="mt-6 rounded-2xl border border-brand-400/40 bg-slate-900/80 p-5" aria-label="Personalizar o painel">
        <p class="text-sm text-slate-300">Escolha o que aparece e em que ordem: arraste pela alça ⠿ ou use as setas. Os blocos aparecem só quando há dados para eles, e o bloco que você mexe pisca no painel.</p>
        <ol class="mt-4 grid gap-2 md:grid-cols-2">
            <template x-for="(id, i) in editable" :key="id">
                <li class="flex items-center gap-2 rounded-xl border bg-slate-950/40 px-3 py-2 transition"
                    :class="dragOver === id ? 'border-brand-400' : 'border-slate-50/10'"
                    draggable="true" @dragstart="dragId = id; $event.dataTransfer.effectAllowed = 'move'" @dragend="dragId = null; dragOver = null"
                    @dragover.prevent="dragOver = id" @dragleave="dragOver === id && (dragOver = null)" @drop.prevent="dropOn(id)">
                    <span class="cursor-grab select-none px-1 text-slate-500" aria-hidden="true">⠿</span>
                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2.5 text-sm text-slate-100">
                        <input type="checkbox" :checked="!layout.hidden.includes(id)" @change="toggleWidget(id)" class="h-4 w-4 shrink-0 rounded">
                        <span class="truncate" x-text="widgets[id].label"></span>
                    </label>
                    <button type="button" @click="move(id, -1)" :disabled="i === 0" :aria-label="'Subir ' + widgets[id].label" class="grid h-8 w-8 place-items-center rounded-lg text-slate-300 hover:bg-slate-50/10 disabled:opacity-30">↑</button>
                    <button type="button" @click="move(id, 1)" :disabled="i === editable.length - 1" :aria-label="'Descer ' + widgets[id].label" class="grid h-8 w-8 place-items-center rounded-lg text-slate-300 hover:bg-slate-50/10 disabled:opacity-30">↓</button>
                </li>
            </template>
        </ol>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="button" @click="saveLayout()" :disabled="savingLayout" class="btn" x-text="savingLayout ? 'Salvando…' : 'Salvar painel'"></button>
            <button type="button" @click="resetLayout()" class="btn-ghost">Restaurar o padrão</button>
            <span x-show="layoutNotice" x-cloak role="status" class="text-sm text-ok" x-text="layoutNotice"></span>
        </div>
    </section>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span>
        <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <!-- celular: o painel dividido em abas (no computador tudo aparece em 2 colunas) -->
    <nav x-show="!wide && tabsShown.length > 1" x-cloak class="sticky top-0 z-20 -mx-4 mt-5 bg-slate-950/85 px-4 py-2 backdrop-blur" aria-label="Seções do painel">
        <div class="grid rounded-xl bg-slate-50/5 p-1 text-sm font-semibold" :style="'grid-template-columns:repeat(' + tabsShown.length + ',1fr)'" role="tablist">
            <template x-for="t in tabsShown" :key="t.v">
                <button type="button" role="tab" :aria-selected="tab === t.v" @click="setTab(t.v)" class="rounded-lg py-2 transition"
                        :class="tab === t.v ? 'bg-brand-600 text-on-brand shadow' : 'text-slate-400 hover:text-slate-50'" x-text="t.l"></button>
            </template>
        </div>
    </nav>

    <!-- grade de 2 colunas: cada bloco ocupa a linha toda ou metade (DashboardService::WIDGETS['span']) -->
    <div class="mt-6 grid grid-flow-row-dense gap-6 lg:grid-cols-2" :class="loading && 'opacity-60 transition-opacity'">

        <!-- os blocos não carregaram -->
        <div x-show="extraError" x-cloak role="alert" style="order:-3" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger lg:col-span-2">
            <span x-text="'Não deu para carregar os blocos do painel: ' + extraError"></span>
            <button type="button" @click="loadExtra()" class="rounded-lg border border-danger/40 px-3 py-1.5 font-semibold hover:bg-danger/10">Tentar de novo</button>
        </div>

        <!-- esqueleto enquanto os blocos chegam (reserva o espaço: nada pula na tela) -->
        <template x-if="!extra && !extraError">
            <div style="order:-2" class="grid gap-6 lg:col-span-2 lg:grid-cols-2" aria-label="Carregando o painel" aria-busy="true">
                <div class="h-60 skeleton lg:col-span-2"></div>
                <div class="h-32 skeleton lg:col-span-2"></div>
                <div class="h-72 skeleton"></div>
                <div class="h-72 skeleton"></div>
            </div>
        </template>

        <?php require __DIR__ . '/partials/dash_widgets.php'; ?>

        <!-- Vazio -->
        <p x-show="data && data.totals.all.games === 0" x-cloak style="order:-1" class="rounded-2xl border border-dashed border-slate-50/10 p-8 text-center text-slate-400 lg:col-span-2">
            Você ainda não registrou nenhum jogo. Assim que registrar, o seu painel ganha vida aqui.
            <a x-show="!isDemo" :href="base + '/jogos/novo'" class="mt-3 block font-semibold text-brand-400 hover:underline">Registrar o primeiro jogo →</a>
        </p>

        <!-- Por mês: barras em HTML, com o mês atual em destaque e o ritmo da meta -->
        <section :style="'order:' + pos('months')" x-show="vis('months')" x-cloak :class="cell('months')" class="panel p-5" aria-labelledby="t-months">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="t-months" class="t-section">Por mês · <span x-text="year"></span></h2>
                    <p class="text-xs text-slate-500"><span x-text="fmt(total('games'))"></span> jogos · <span x-text="fmt(total('hours'), 0)"></span> h · <span x-text="fmt(total('xp'))"></span> XP</p>
                </div>
                <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-xs font-medium" role="group" aria-label="Métrica do gráfico">
                    <template x-for="r in rows" :key="r.key">
                        <button type="button" @click="metric = r.key" :aria-pressed="metric === r.key"
                                class="seg-btn" :class="metric === r.key ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'" x-text="r.label"></button>
                    </template>
                </div>
            </div>
            <p class="sr-only" x-text="monthsSummary"></p>
            <div class="relative mt-7 h-44" aria-hidden="true">
                <!-- ritmo da meta (por mês) -->
                <div x-show="monthPace" class="absolute inset-x-0 z-10 border-t-2 border-dashed border-gold-400/80" :style="'bottom:' + (monthPace / monthScale * 100) + '%'">
                    <span class="absolute -top-5 right-0 rounded bg-gold-400 px-1.5 text-2xs font-bold text-black" x-text="'meta ~' + fmt(monthPace, metric === 'games' ? 1 : 0) + '/mês'"></span>
                </div>
                <ol class="flex h-full items-end gap-1.5 sm:gap-2">
                    <template x-for="(m, i) in (data?.months ?? [])" :key="i">
                        <li class="relative flex h-full min-w-0 flex-1 items-end" :title="monthNames[i] + ': ' + fmt(m[metric], metric === 'hours' ? 1 : 0)">
                            <span class="w-full rounded-t-md transition-all duration-500 motion-reduce:transition-none" :class="isNowMonth(i) ? 'bg-brand-400 shadow-[0_0_14px_rgb(var(--brand-400)/.6)]' : 'bg-brand-600/70'" :style="'height:' + (m[metric] ? Math.max(3, m[metric] / monthScale * 100) : 0) + '%'"></span>
                            <span x-show="m[metric]" class="absolute inset-x-0 text-center text-2xs font-bold tabular-nums" :class="isNowMonth(i) ? 'text-brand-400' : 'text-slate-400'" :style="'bottom:calc(' + Math.max(3, m[metric] / monthScale * 100) + '% + 2px)'" x-text="fmt(m[metric], 0)"></span>
                        </li>
                    </template>
                </ol>
            </div>
            <ol class="mt-1.5 flex gap-1.5 sm:gap-2" aria-hidden="true">
                <template x-for="(n, i) in monthNames" :key="n"><li class="min-w-0 flex-1 text-center text-2xs" :class="isNowMonth(i) ? 'font-bold text-brand-400' : 'font-medium text-slate-500'" x-text="n"></li></template>
            </ol>
        </section>

        <!-- Gêneros e sistemas: mosaico dos gêneros ou barras com a cor de cada sistema -->
        <section :style="'order:' + pos('breakdown')" x-show="vis('breakdown')" x-cloak :class="cell('breakdown')" class="panel p-5" aria-labelledby="t-breakdown">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="t-breakdown" class="t-section">Gêneros e sistemas</h2>
                <div class="flex flex-wrap gap-2">
                    <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-xs font-medium" role="group" aria-label="O que mostrar">
                        <button type="button" @click="breakView = 'genres'" :aria-pressed="breakView === 'genres'" class="seg-btn" :class="breakView === 'genres' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Gêneros</button>
                        <button type="button" @click="breakView = 'platforms'" :aria-pressed="breakView === 'platforms'" class="seg-btn" :class="breakView === 'platforms' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Sistemas</button>
                    </div>
                    <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-xs font-medium" role="group" aria-label="Período">
                        <button type="button" @click="scope = 'year'" :aria-pressed="scope === 'year'" class="seg-btn" :class="scope === 'year' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'" x-text="year"></button>
                        <button type="button" @click="scope = 'all'" :aria-pressed="scope === 'all'" class="seg-btn" :class="scope === 'all' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Sempre</button>
                    </div>
                </div>
            </div>
            <p x-show="!breakRows.length" class="mt-6 text-sm text-slate-500">Sem dados neste período.</p>
            <!-- gêneros: blocos que crescem com a quantidade -->
            <ul x-show="breakView === 'genres' && breakRows.length" class="mt-4 flex h-56 flex-wrap gap-1.5 overflow-hidden">
                <template x-for="(g, i) in breakRows" :key="g.name">
                    <li class="flex min-w-[4.5rem] flex-col justify-end overflow-hidden rounded-lg p-2 text-white" :style="'flex-grow:' + g.n + ';flex-basis:' + Math.max(18, g.n / breakRows[0].n * 45) + '%;background:hsl(' + (250 + i * 23) % 360 + ' 55% ' + (42 - i * 2) + '%)'" :title="g.name + ': ' + g.n + ' jogos'">
                        <span class="truncate text-xs font-bold" x-text="g.name"></span>
                        <span class="text-2xs tabular-nums text-white/75" x-text="g.n + (g.n === 1 ? ' jogo' : ' jogos')"></span>
                    </li>
                </template>
            </ul>
            <!-- sistemas: barras na cor do console -->
            <ol x-show="breakView === 'platforms' && breakRows.length" class="mt-4 space-y-2">
                <template x-for="p in breakRows.slice(0, 8)" :key="p.name">
                    <li class="grid grid-cols-[minmax(0,7rem)_1fr_auto] items-center gap-3 text-sm">
                        <span class="truncate font-semibold text-slate-200" :title="p.name" x-text="p.name"></span>
                        <span class="h-5 overflow-hidden rounded-md bg-slate-50/5"><span class="block h-full rounded-md" :style="UI.bandStyle(p.name) + ';background:var(--band-bg);width:' + (p.n / breakRows[0].n * 100) + '%'"></span></span>
                        <span class="w-8 text-right text-xs tabular-nums text-slate-400" x-text="p.n"></span>
                    </li>
                </template>
            </ol>
        </section>
    </div>
</main>

<script src="<?= e(asset('/assets/js/dashboard-extra.js')) ?>"></script>
<script>
const WIDGETS = <?= json_encode(array_map(fn($w) => ['label' => $w['label'], 'numbers' => $w['numbers'], 'span' => $w['span'], 'group' => $w['group']], \App\Services\DashboardService::WIDGETS), JSON_UNESCAPED_UNICODE) ?>;
function dashboard(me, demo) {
    const nf = (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0);

    // withDashExtra copia os blocos novos preservando os getters (um spread congelaria os valores calculados)
    return withDashExtra({
        me,
        isDemo: !!demo,
        base: window.APP.base,
        year: demo ? demo.years[0] : new Date().getFullYear(),
        years: demo ? demo.years : [new Date().getFullYear()],
        data: null,
        loading: true,
        error: '',
        metric: 'games',
        scope: 'year',
        breakView: 'genres',
        monthNames: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        rows: [
            { key: 'games', label: 'Jogos', dec: 0 },
            { key: 'hours', label: 'Horas', dec: 1 },
            { key: 'xp', label: 'XP', dec: 0 },
        ],

        fmt: nf,
        total(key) { return (this.data?.months ?? []).reduce((s, m) => s + m[key], 0); },
        /** o bloco de números fica dentro do destaque; continua respeitando o painel montável e o modo calmo */
        get numbersOn() { return !!this.data && !this.layout.hidden.includes('numbers') && !this.calm; },
        /** fundo do destaque: o jogo que está jogando agora (ou o último zerado) */
        get heroCover() { return this.extra?.playing?.find((g) => g.cover_url)?.cover_url ?? this.extra?.recent?.find((g) => g.cover_url)?.cover_url ?? null; },

        // ---------- por mês ----------
        isNowMonth(i) { const d = new Date(); return this.year === d.getFullYear() && i === d.getMonth(); },
        /** ritmo mensal da meta, quando a meta é do ano mostrado e da métrica escolhida */
        get monthPace() {
            const g = this.data?.goal;
            return g && g.year === this.year && g[this.metric]?.target ? g[this.metric].target / 12 : 0;
        },
        get monthScale() { return Math.max(1, this.monthPace * 1.15, ...(this.data?.months ?? []).map((m) => m[this.metric])); },
        get monthsSummary() {
            const ms = this.data?.months ?? [], label = this.rows.find((r) => r.key === this.metric)?.label ?? '';
            if (!ms.length) return '';
            const max = Math.max(...ms.map((m) => m[this.metric]));
            const peak = ms.findIndex((m) => m[this.metric] === max);
            return `${label} por mês em ${this.year}: total ${nf(this.total(this.metric), 0)}; pico em ${this.monthNames[peak]} com ${nf(max, 0)}.`;
        },
        get breakRows() { return this.data?.[this.breakView]?.[this.scope] ?? []; },

        async init() {
            this.applyChartTheme();
            window.addEventListener('themechange', () => { this.applyChartTheme(); this.paintExtra(); });
            this.initExtra();
            await this.load();
        },

        applyChartTheme() {
            if (!window.Chart) return setTimeout(() => this.applyChartTheme(), 50);
            const p = Theme.palette();
            Chart.defaults.color = p.text;
            Chart.defaults.borderColor = p.grid;
            Chart.defaults.font.family = p.font;
        },

        async load() {
            this.loading = true;
            this.error = '';
            try {
                const d = demo ? demo.byYear[this.year] : await api.get('/api/me/stats?year=' + this.year);
                this.data = d;
                this.years = d.years;
                this.year = d.year;
                if (!this.extra) this.loadExtra();
            } catch (e) {
                if (e.status === 401) return (location.href = window.APP.base + '/login');
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
        /** trocar o ano: só os números e a trilha mudam (os outros blocos não dependem do ano) */
        async changeYear() {
            await this.load();
            this.loadSoundtrack();
        },
        drawAll() { /* os gráficos de mês e de gêneros agora são HTML; ficou para compatibilidade com dashboard-extra.js */ },
    }, { me, isDemo: !!demo, base: window.APP.base, widgets: WIDGETS });
}
</script>
