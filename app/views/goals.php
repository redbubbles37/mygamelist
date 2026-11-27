<?php
/** @var array|null $me */
/** @var array|null $demo  {byYear, years, today} fictícios da página de exemplo (nulo nas metas reais) */
$demo = $demo ?? null;
?>
<main class="page page-narrow" x-data='goals(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com metas e jogos fictícios. Dá para editar a meta e trocar de ano; nada é salvo.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="t-page">Metas do ano</h1>
            <p class="page-sub ">Defina quantos jogos quer zerar (e quantas horas quer jogar) e acompanhe o ritmo.</p>
        </div>
        <div class="flex items-center gap-2" x-show="ready" x-cloak>
            <button type="button" @click="step(-1)" :disabled="!neighbor(-1) || loading" class="btn-ghost" aria-label="Ano anterior">←</button>
            <label class="sr-only" for="goal-year">Ano</label>
            <select id="goal-year" :value="d?.year" @change="go(Number($event.target.value))" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 font-semibold text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                <template x-for="y in years" :key="y"><option :value="y" x-text="y"></option></template>
            </select>
            <button type="button" @click="step(1)" :disabled="!neighbor(1) || loading" class="btn-ghost" aria-label="Próximo ano">→</button>
        </div>
    </header>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="init()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>
    <div x-show="!ready && !error" class="mt-8 animate-pulse space-y-4" aria-label="Carregando"><div class="h-48 rounded-2xl bg-slate-50/5"></div></div>

    <template x-if="ready && d">
        <div :class="loading && 'opacity-60 transition-opacity'">

            <!-- Sem meta ainda / editor -->
            <section x-show="editing || !hasGoals" x-cloak class="mt-6 panel p-5 sm:p-6" aria-label="Definir meta">
                <h2 class="t-section" x-text="hasGoals ? 'Editar a meta de ' + d.year : 'Qual é a sua meta para ' + d.year + '?'"></h2>
                <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="save" novalidate>
                    <div>
                        <label for="g-games" class="mb-1 block text-sm font-medium text-slate-300">Jogos para zerar</label>
                        <input id="g-games" type="number" min="1" inputmode="numeric" x-model="form.games" placeholder="Ex.: 36" class="field" :class="errs.games && 'border-danger/60'">
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <template x-for="n in [12, 24, 36, 52, 100]" :key="n"><button type="button" @click="form.games = n" class="rounded-full border border-slate-50/10 px-3 py-1 text-xs font-semibold text-slate-300 hover:border-brand-400/60 hover:text-brand-400" x-text="n"></button></template>
                        </div>
                        <p x-show="errs.games" x-cloak class="err" x-text="errs.games"></p>
                    </div>
                    <div>
                        <label for="g-hours" class="mb-1 block text-sm font-medium text-slate-300">Horas para jogar <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="g-hours" type="number" min="1" inputmode="numeric" x-model="form.hours" placeholder="Ex.: 500" class="field" :class="errs.hours && 'border-danger/60'">
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <template x-for="n in [250, 500, 750, 1000, 1500]" :key="n"><button type="button" @click="form.hours = n" class="rounded-full border border-slate-50/10 px-3 py-1 text-xs font-semibold text-slate-300 hover:border-brand-400/60 hover:text-brand-400" x-text="n"></button></template>
                        </div>
                        <p x-show="errs.hours" x-cloak class="err" x-text="errs.hours"></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                        <button type="submit" :disabled="saving" class="btn" x-text="saving ? 'Salvando…' : 'Salvar meta'"></button>
                        <button type="button" x-show="hasGoals" x-cloak @click="editing = false" class="btn-ghost">Cancelar</button>
                        <button type="button" x-show="hasGoals" x-cloak @click="form.games = ''; form.hours = ''; save()" class="ml-auto text-sm text-slate-400 hover:text-danger">Remover a meta</button>
                        <p x-show="errs.year" x-cloak class="err" x-text="errs.year"></p>
                    </div>
                </form>
            </section>

            <!-- Destaque: a previsão, numa pista de janeiro a dezembro -->
            <section x-show="hasGoals && !editing && forecast" x-cloak class="relative isolate mt-6 overflow-hidden rounded-3xl border border-slate-50/10 bg-hero p-5 text-white sm:p-7" aria-labelledby="h-forecast">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    <div class="absolute -left-16 -top-20 h-64 w-64 rounded-full blur-3xl" :class="forecast?.good ? 'bg-ok/25' : 'bg-gold-500/20'"></div>
                    <div class="absolute -bottom-24 right-0 h-64 w-80 rounded-full bg-brand-500/20 blur-3xl"></div>
                </div>
                <p class="kicker-hero" x-text="'Meta de ' + d.year + ' · ' + (forecast?.k === 'hours' ? 'horas' : 'jogos')"></p>
                <h2 id="h-forecast" class="mt-2 max-w-3xl text-2xl font-black leading-tight sm:text-3xl" x-text="forecast?.headline"></h2>
                <!-- a pista: posição = quanto da meta já foi feito; o tracejado = onde você deveria estar hoje -->
                <div class="relative mt-8 h-3 rounded-full bg-white/10" role="img" :aria-label="forecast ? forecast.done + ' de ' + forecast.target + '; o esperado para hoje é ' + forecast.expected : ''">
                    <div class="absolute inset-y-0 left-0 rounded-full" :class="forecast?.good ? 'bg-gradient-to-r from-ok/70 to-ok' : 'bg-gradient-to-r from-brand-600 to-accent'" :style="'width:' + Math.min(100, forecast?.pctDone ?? 0) + '%'"></div>
                    <!-- onde você deveria estar hoje -->
                    <div x-show="forecast?.showExpected" class="absolute -top-2 h-7 border-l-2 border-dashed border-white/70" :style="'left:' + Math.min(100, (forecast?.pctExpected ?? 0)) + '%'">
                        <span class="absolute -top-5 -translate-x-1/2 whitespace-nowrap text-2xs font-semibold text-white/70">hoje</span>
                    </div>
                    <!-- você -->
                    <div class="absolute top-1/2 -translate-x-1/2 -translate-y-1/2" :style="'left:' + Math.min(100, forecast?.pctDone ?? 0) + '%'">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-white text-sm shadow-lg ring-4 ring-black/40" aria-hidden="true">🎮</span>
                    </div>
                    <!-- chegada prevista (pode passar da meta) -->
                    <div x-show="forecast?.showProjection" class="absolute top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-80" :style="'left:' + Math.min(100, forecast?.pctProjected ?? 0) + '%'">
                        <span class="block h-4 w-4 rotate-45 border-2 border-white/80 bg-transparent" aria-hidden="true"></span>
                    </div>
                    <span class="absolute -right-1 -top-7 text-lg" aria-hidden="true">🏁</span>
                </div>
                <div class="mt-3 flex justify-between text-xs text-white/55"><span>Jan</span><span x-text="forecast ? forecast.targetLabel + ' (a meta)' : ''"></span></div>
                <p class="mt-4 text-sm text-white/75" x-text="forecast?.detail"></p>
            </section>

            <!-- Cartões de progresso -->
            <section x-show="hasGoals && !editing" x-cloak class="mt-6 grid gap-4 md:grid-cols-2" aria-label="Progresso das metas">
                <template x-for="m in activeMetrics" :key="m.k">
                    <article class="panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500" x-text="m.title"></p>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="m.statusClass" x-text="m.statusLabel"></span>
                        </div>
                        <div class="mt-4 flex items-center gap-5">
                            <!-- anel de progresso, com o ponto de "onde você deveria estar" -->
                            <div class="relative h-28 w-28 shrink-0">
                                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img" :aria-label="m.title + ': ' + m.pct + '%'">
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="rgb(var(--s-50) / .1)" stroke-width="10"/>
                                    <circle cx="60" cy="60" r="52" fill="none" :stroke="m.done >= m.target ? 'rgb(var(--ok))' : 'rgb(var(--brand-500))'" stroke-width="10" stroke-linecap="round"
                                            :stroke-dasharray="326.7" :stroke-dashoffset="326.7 * (1 - Math.min(1, m.done / m.target))" class="transition-all duration-700 motion-reduce:transition-none"/>
                                    <circle x-show="m.markerFrac > 0 && m.markerFrac < 1" :cx="60 + 52 * Math.cos(m.markerFrac * 2 * Math.PI)" :cy="60 + 52 * Math.sin(m.markerFrac * 2 * Math.PI)" r="4.5" fill="rgb(var(--s-50))" stroke="rgb(var(--s-950))" stroke-width="2"/>
                                </svg>
                                <div class="absolute inset-0 grid place-items-center text-center"><div><p class="text-2xl font-black tabular-nums leading-none text-slate-50" x-text="m.pct + '%'"></p></div></div>
                            </div>
                            <div class="min-w-0">
                                <p class="text-3xl font-black tabular-nums text-slate-50 sm:text-4xl"><span x-text="m.doneLabel"></span><span class="text-xl text-slate-500"> / <span x-text="m.targetLabel"></span></span></p>
                                <p class="text-sm text-slate-400" x-text="m.unit"></p>
                                <p class="mt-1 text-xs text-slate-500" x-show="m.markerLabel" x-text="m.markerLabel"></p>
                            </div>
                        </div>
                        <ul class="mt-4 space-y-1 border-t border-slate-50/10 pt-3 text-sm text-slate-300">
                            <template x-for="l in m.lines" :key="l"><li x-text="l"></li></template>
                        </ul>
                    </article>
                </template>
                <div class="md:col-span-2 text-right"><button type="button" @click="openEditor()" class="text-sm font-semibold text-brand-400 hover:underline">Editar a meta</button></div>
            </section>

            <!-- Checkpoints + plano mês a mês -->
            <section x-show="hasGoals && !editing && plan" x-cloak class="mt-2 panel p-5 sm:p-6" aria-labelledby="h-plan">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="h-plan" class="t-section">Checkpoints e plano mês a mês</h2>
                    <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-sm font-semibold" role="group" aria-label="Métrica do plano" x-show="activeMetrics.length > 1">
                        <template x-for="m in activeMetrics" :key="m.k">
                            <button type="button" @click="metric = m.k; draw()" :aria-pressed="metric === m.k" class="seg-btn" :class="metric === m.k ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'" x-text="m.k === 'games' ? 'Jogos' : 'Horas'"></button>
                        </template>
                    </div>
                </div>
                <!-- os quatro checkpoints da meta -->
                <ol class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <template x-for="c in plan?.checkpoints ?? []" :key="c.pct">
                        <li class="relative rounded-2xl border p-4 text-center" :class="c.month !== null ? 'border-ok/40 bg-ok/10' : 'border-dashed border-slate-50/15'">
                            <span class="mx-auto grid h-11 w-11 place-items-center rounded-full text-lg" :class="c.month !== null ? 'bg-ok text-black' : 'bg-slate-50/10 text-slate-500'" x-text="c.month !== null ? (c.pct === 100 ? '🏆' : '✓') : c.icon"></span>
                            <p class="mt-2 text-sm font-bold text-slate-50" x-text="c.label"></p>
                            <p class="text-xs tabular-nums text-slate-400" x-text="fmt(c.value) + (plan.k === 'hours' ? 'h' : ' jogos')"></p>
                            <p class="mt-1 text-xs font-semibold" :class="c.month !== null ? 'text-ok' : 'text-slate-500'" x-text="c.month !== null ? 'alcançado em ' + c.monthName : 'ainda não'"></p>
                        </li>
                    </template>
                </ol>
                <!-- doze meses contra o ritmo necessário -->
                <p class="mt-6 text-sm text-slate-400" x-text="'Ritmo necessário: ~' + fmt(plan?.pace ?? 0, plan?.k === 'hours' ? 0 : 1) + (plan?.k === 'hours' ? 'h' : ' jogos') + ' por mês' + (plan?.needLeft ? ' · a partir de agora: ~' + fmt(plan.needLeft, plan.k === 'hours' ? 0 : 1) + ' por mês' : '')"></p>
                <ol class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6 lg:grid-cols-12">
                    <template x-for="mo in plan?.months ?? []" :key="mo.i">
                        <li class="flex flex-col rounded-xl border p-2 text-center" :class="mo.state === 'hit' ? 'border-ok/40 bg-ok/10' : mo.state === 'low' ? 'border-gold-400/40 bg-gold-400/10' : mo.state === 'now' ? 'border-brand-400/60 bg-brand-500/10' : 'border-dashed border-slate-50/15'">
                            <span class="text-2xs font-bold uppercase text-slate-400" x-text="mo.label"></span>
                            <span class="text-xl font-black tabular-nums text-slate-50" x-text="mo.state === 'future' ? '—' : fmt(mo.v, 0)"></span>
                            <span class="mt-1 h-1 overflow-hidden rounded-full bg-slate-50/10"><span class="block h-full rounded-full" :class="mo.state === 'hit' ? 'bg-ok' : mo.state === 'low' ? 'bg-gold-400' : 'bg-brand-500'" :style="'width:' + Math.min(100, mo.v / Math.max(0.1, plan.pace) * 100) + '%'"></span></span>
                            <span class="mt-1 text-2xs text-slate-500" x-text="mo.state === 'future' ? 'meta ~' + fmt(plan.needLeft || plan.pace, 0) : mo.state === 'hit' ? 'no ritmo' : mo.state === 'now' ? 'mês atual' : 'abaixo'"></span>
                        </li>
                    </template>
                </ol>
            </section>

            <!-- Gráfico acumulado -->
            <section x-show="hasGoals && !editing" x-cloak class="mt-2 panel p-5 sm:p-6" aria-labelledby="h-chart">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="h-chart" class="t-section">Mês a mês contra a meta</h2>
                    <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-sm font-semibold" role="group" aria-label="Métrica do gráfico" x-show="activeMetrics.length > 1">
                        <template x-for="m in activeMetrics" :key="m.k">
                            <button type="button" @click="metric = m.k; draw()" :aria-pressed="metric === m.k" class="seg-btn" :class="metric === m.k ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'" x-text="m.k === 'games' ? 'Jogos' : 'Horas'"></button>
                        </template>
                    </div>
                </div>
                <div class="relative mt-4 h-64"><canvas id="goal-chart" role="img" aria-label="Gráfico acumulado do ano contra a meta"></canvas></div>
                <p class="mt-2 text-xs text-slate-500">A linha tracejada é o ritmo ideal para chegar à meta em 31/12.</p>
            </section>

            <!-- Histórico -->
            <section x-show="d.history.length" x-cloak class="mt-8" aria-labelledby="h-hist">
                <h2 id="h-hist" class="t-section">Metas de outros anos</h2>
                <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                    <template x-for="h in d.history" :key="h.year">
                        <li class="panel p-4">
                            <div class="flex items-center justify-between">
                                <button type="button" @click="go(h.year)" class="text-2xl font-black tabular-nums text-slate-50 hover:text-brand-400" x-text="h.year"></button>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="hit(h) ? 'bg-ok/15 text-ok' : 'bg-slate-50/10 text-slate-400'" x-text="hit(h) ? '✓ Cumprida' : 'Não cumprida'"></span>
                            </div>
                            <ul class="mt-2 space-y-1.5 text-sm">
                                <template x-for="k in ['games', 'hours']" :key="k">
                                    <li x-show="h.goals[k]" class="flex items-center justify-between gap-3">
                                        <span class="text-slate-300" x-text="(k === 'games' ? 'Jogos' : 'Horas') + ': ' + fmt(h.done[k], k === 'hours' ? 0 : 0) + ' de ' + fmt(h.goals[k])"></span>
                                        <span class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-50/10"><span class="block h-full rounded-full" :class="h.done[k] >= h.goals[k] ? 'bg-ok' : 'bg-brand-500'" :style="`width:${Math.min(100, h.done[k] / h.goals[k] * 100)}%`"></span></span>
                                    </li>
                                </template>
                            </ul>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </template>
</main>

<script>
function goals(demo) {
    const base = window.APP.base;
    const MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const nf = (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
    const parse = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    let chart = null; // fora do estado reativo do Alpine

    return {
        years: [], d: null, ready: false, loading: false, error: '',
        metric: 'games', editing: false, saving: false, form: { games: '', hours: '' }, errs: {},
        fmt: nf,

        // ---------- carga ----------
        async init() {
            this.error = '';
            try {
                if (demo) { this.years = demo.years; }
                const h = Number(location.hash.slice(1));
                const first = h >= 2000 ? h : (demo ? demo.years[0] : new Date().getFullYear());
                await this.load(first);
                this.ready = true;
                window.addEventListener('themechange', () => this.draw());
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },
        async load(year) {
            this.loading = true;
            try {
                this.d = demo ? structuredClone(demo.byYear[year]) : await api.get('/api/goals?year=' + year);
                if (!demo) this.years = this.d.years;
                this.editing = false; this.errs = {};
                if (!this.activeMetrics.some((m) => m.k === this.metric)) this.metric = this.activeMetrics[0]?.k ?? 'games';
                this.form = { games: this.d.goals.games ?? '', hours: this.d.goals.hours ?? '' };
                this.$nextTick(() => this.draw());
            } finally { this.loading = false; }
        },
        neighbor(dir) { const i = this.years.indexOf(this.d?.year); return this.years[i - dir] ?? null; },
        step(dir) { const n = this.neighbor(dir); if (n) this.go(n); },
        async go(y) { if (y === this.d.year) return; history.replaceState(null, '', location.pathname + '#' + y); await this.load(y); },

        // ---------- cálculo do ritmo ----------
        get today() { return demo ? parse(demo.today) : new Date(); },
        get hasGoals() { return !!(this.d.goals.games || this.d.goals.hours); },
        /** Fração do ano já decorrida (1 para anos passados, 0 para futuros). */
        get frac() {
            const y = this.d.year, ty = this.today.getFullYear();
            if (y < ty) return 1;
            if (y > ty) return 0;
            const start = new Date(y, 0, 1), days = (new Date(y + 1, 0, 1) - start) / 86400000;
            return Math.min(1, (Math.floor((this.today - start) / 86400000) + 1) / days);
        },
        /** a previsão do destaque: a primeira meta definida (jogos, senão horas) */
        shareCard() {
            const f = this.forecast;
            if (!f) { this.error = 'Defina uma meta para gerar o cartão.'; return; }
            ShareCard.download({ kicker: 'Metas', title: 'Minha meta de ' + this.d.year, sub: f.headline, file: 'meta-' + this.d.year,
                stats: this.activeMetrics.map((m) => [m.doneLabel ?? nf(m.done), (m.k === 'hours' ? 'horas' : 'jogos') + ' de ' + nf(m.target)]) });
        },
        get forecast() {
            const m = this.activeMetrics[0];
            if (!m) return null;
            const frac = this.frac, year = this.d.year, ty = this.today.getFullYear(), hours = m.k === 'hours';
            const unit = (n) => hours ? 'horas' : (Math.round(n) === 1 ? 'jogo' : 'jogos');
            const proj = frac > 0 ? m.done / frac : 0, expected = m.target * frac;
            const fmtN = (v) => nf(hours ? Math.round(v) : Math.round(v * 10) / 10, hours ? 0 : 1).replace(/,0$/, '');
            let headline, detail = '', good = m.done >= m.target;
            if (m.done >= m.target) {
                headline = year < ty ? `Você fechou ${year} com ${fmtN(m.done)} de ${fmtN(m.target)} ${unit(m.target)}. Meta batida! 🎉` : `Meta batida: ${fmtN(m.done)} de ${fmtN(m.target)} ${unit(m.target)}! 🎉`;
                if (year === ty) detail = `No ritmo atual, você fecha o ano com ~${fmtN(proj)} ${unit(proj)}.`;
            } else if (year < ty) {
                headline = `Você fechou ${year} com ${fmtN(m.done)} de ${fmtN(m.target)} ${unit(m.target)}.`;
                detail = `Faltaram ${fmtN(m.target - m.done)}. Fica para o próximo ano!`;
            } else if (year > ty) {
                headline = `A meta de ${year} começa em 1º de janeiro.`;
            } else {
                good = proj >= m.target;
                headline = good ? `No ritmo atual, você termina ${year} com ~${fmtN(proj)} ${unit(proj)}: bate a meta.` : `No ritmo atual, você termina ${year} com ~${fmtN(proj)} ${unit(proj)}: faltariam ${fmtN(m.target - proj)}.`;
                detail = m.lines.slice(0, 2).join(' ');
            }
            const pct = (v) => m.target ? v / m.target * 100 : 0;
            return {
                k: m.k, done: fmtN(m.done), target: fmtN(m.target), expected: fmtN(expected), targetLabel: fmtN(m.target) + ' ' + unit(m.target),
                pctDone: pct(m.done), pctExpected: pct(expected), pctProjected: pct(proj),
                showExpected: year === ty && m.done < m.target, showProjection: year === ty && frac > 0,
                headline, detail, good,
            };
        },
        get activeMetrics() {
            if (!this.d) return [];
            const out = [];
            for (const k of ['games', 'hours']) {
                const target = this.d.goals[k];
                if (!target) continue;
                const done = this.d.done[k], frac = this.frac, year = this.d.year, ty = this.today.getFullYear();
                const expected = target * frac, delta = done - expected;
                const hours = k === 'hours', unit = hours ? 'horas jogadas' : (done === 1 ? 'jogo zerado' : 'jogos zerados');
                const r = (v) => nf(hours ? Math.round(v) : Math.round(v * 10) / 10, hours ? 0 : (Number.isInteger(Math.round(v * 10) / 10) ? 0 : 1));
                let statusLabel, statusClass;
                if (done >= target) { statusLabel = '✓ Meta cumprida!'; statusClass = 'bg-ok/15 text-ok'; }
                else if (year < ty) { statusLabel = 'Não cumprida'; statusClass = 'bg-slate-50/10 text-slate-400'; }
                else if (year > ty) { statusLabel = 'Ainda não começou'; statusClass = 'bg-slate-50/10 text-slate-400'; }
                else if (delta >= 0.5) { statusLabel = 'Adiantado'; statusClass = 'bg-ok/15 text-ok'; }
                else if (delta > -1) { statusLabel = 'No ritmo'; statusClass = 'bg-brand-500/20 text-brand-400'; }
                else { statusLabel = 'Atrasado'; statusClass = 'bg-danger/15 text-danger'; }

                const lines = [];
                if (done >= target) {
                    lines.push(done > target ? `Você passou da meta em ${r(done - target)} ${hours ? 'h' : (done - target === 1 ? 'jogo' : 'jogos')}.` : 'Você bateu a meta exatamente.');
                } else if (year === ty) {
                    const left = target - done, daysTotal = (new Date(year + 1, 0, 1) - new Date(year, 0, 1)) / 86400000, daysLeft = Math.max(1, Math.round(daysTotal * (1 - frac)));
                    const proj = frac > 0 ? done / frac : 0;
                    lines.push(`Faltam ${r(left)} ${hours ? 'horas' : (left === 1 ? 'jogo' : 'jogos')} em ${daysLeft} ${daysLeft === 1 ? 'dia' : 'dias'}.`);
                    lines.push(`Para chegar lá: ~${r(left / (daysLeft / 30.4375))} por mês (~${r(left / (daysLeft / 7))} por semana).`);
                    lines.push(`No ritmo atual você termina o ano com ~${r(proj)}${proj >= target ? ' (bate a meta)' : ' (fica abaixo da meta)'}.`);
                } else if (year < ty) {
                    lines.push(`Faltaram ${r(target - done)} ${hours ? 'horas' : 'jogos'} para a meta.`);
                } else {
                    lines.push('A meta vale a partir de 1º de janeiro.');
                }
                out.push({
                    k, target, done, title: hours ? 'Meta de horas' : 'Meta de jogos', unit, statusLabel, statusClass, lines,
                    pct: Math.min(999, Math.round(done / target * 100)),
                    doneLabel: nf(hours ? Math.round(done) : done), targetLabel: nf(target),
                    markerFrac: year === ty && done < target ? frac : 0,
                    markerLabel: year === ty && done < target ? `Ponto branco: onde você deveria estar hoje (~${r(expected)})` : '',
                });
            }
            return out;
        },
        /** checkpoints (25/50/75/100%) e os 12 meses contra o ritmo necessário, na métrica escolhida */
        get plan() {
            if (!this.d || !this.hasGoals) return null;
            const k = this.activeMetrics.some((m) => m.k === this.metric) ? this.metric : this.activeMetrics[0]?.k;
            if (!k) return null;
            const target = this.d.goals[k], months = this.d.months[k], y = this.d.year, ty = this.today.getFullYear();
            const cur = y < ty ? 12 : y > ty ? -1 : this.today.getMonth();
            const pace = target / 12;
            let acc = 0;
            const cum = months.map((v) => (acc += v));
            const done = this.d.done[k];
            const left = Math.max(0, target - done), monthsLeft = y === ty ? 12 - cur : 0;
            const checkpoints = [25, 50, 75, 100].map((pct, i) => {
                const value = target * pct / 100, m = cum.findIndex((c, idx) => c >= value && idx <= Math.min(cur, 11));
                return { pct, value: Math.ceil(value), label: pct === 100 ? 'A chegada' : pct + '% da meta', icon: ['🥉', '🥈', '🥇', '🏁'][i], month: m === -1 ? null : m, monthName: m === -1 ? '' : MONTHS[m].toLowerCase() };
            });
            return {
                k, pace, checkpoints,
                needLeft: y === ty && left > 0 && monthsLeft > 0 ? left / monthsLeft : 0,
                months: months.map((v, i) => ({ i, label: MONTHS[i], v, state: i > cur ? 'future' : i === cur && y === ty ? 'now' : v >= pace ? 'hit' : 'low' })),
            };
        },
        hit(h) { return (h.goals.games ? h.done.games >= h.goals.games : true) && (h.goals.hours ? h.done.hours >= h.goals.hours : true); },

        // ---------- edição ----------
        openEditor() { this.form = { games: this.d.goals.games ?? '', hours: this.d.goals.hours ?? '' }; this.errs = {}; this.editing = true; },
        async save() {
            this.saving = true; this.errs = {};
            const body = { games: this.form.games === '' ? null : Number(this.form.games), hours: this.form.hours === '' ? null : Number(this.form.hours) };
            try {
                if (demo) {
                    this.d.goals = { games: body.games, hours: body.hours };
                    this.editing = false;
                } else {
                    this.d = await api.put('/api/goals/' + this.d.year, body);
                    this.editing = false;
                }
                if (!this.activeMetrics.some((m) => m.k === this.metric)) this.metric = this.activeMetrics[0]?.k ?? 'games';
                this.$nextTick(() => this.draw());
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.errs = e.errors || {};
                if (!Object.keys(this.errs).length) this.errs = { year: e.message };
            } finally { this.saving = false; }
        },

        // ---------- gráfico acumulado ----------
        draw() {
            chart?.destroy(); chart = null;
            const el = document.getElementById('goal-chart');
            if (!el || !window.Chart || !this.hasGoals || this.editing) return;
            const k = this.activeMetrics.some((m) => m.k === this.metric) ? this.metric : this.activeMetrics[0].k;
            const target = this.d.goals[k], y = this.d.year, ty = this.today.getFullYear();
            const lastMonth = y < ty ? 11 : y > ty ? -1 : this.today.getMonth();
            let acc = 0;
            const mine = this.d.months[k].map((v, i) => { acc += v; return i <= lastMonth ? Math.round(acc * 10) / 10 : null; });
            const ideal = MONTHS.map((_, i) => Math.round(target * (i + 1) / 12 * 10) / 10);
            const p = Theme.palette();
            Chart.defaults.color = p.text; Chart.defaults.borderColor = p.grid; Chart.defaults.font.family = p.font;
            chart = new Chart(el, {
                type: 'line',
                data: { labels: MONTHS, datasets: [
                    { label: k === 'games' ? 'Jogos zerados (acumulado)' : 'Horas jogadas (acumulado)', data: mine, borderColor: p.brand, backgroundColor: p.brand + '33', fill: true, tension: 0.25, pointRadius: 3, borderWidth: 3 },
                    { label: 'Ritmo ideal', data: ideal, borderColor: p.accent, borderDash: [6, 6], pointRadius: 0, borderWidth: 2, fill: false },
                ] },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12 } } },
                    scales: { y: { beginAtZero: true, suggestedMax: target, ticks: { precision: 0 } }, x: { grid: { display: false } } },
                },
            });
        },
    };
}
</script>
