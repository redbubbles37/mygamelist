<?php
/** @var array|null $me */
/** @var array|null $demo  {today, first} da página de exemplo (nulo na real) */
$demo = $demo ?? null;
?>
<main class="page page-narrow" x-data='timeMachine(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?>: viaje pelas datas do catálogo fictício. Nada aqui é seu.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <!-- Painel de viagem: a data em dígitos de relógio, os controles e o retrato daquele dia -->
    <section class="hero-box p-5 text-white sm:p-8" aria-labelledby="h-tm">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="snap?.recent[0]?.cover_url" :src="snap?.recent[0]?.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-30 blur-3xl transition-opacity">
            <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/65 to-cyan-500/15"></div>
        </div>
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div>
                <h1 id="h-tm" class="kicker-hero">Máquina do tempo</h1>
                <!-- a data, como num painel de viagem no tempo -->
                <div class="mt-3 flex items-end gap-2" aria-live="polite" :aria-label="'Destino: ' + longDate(date)">
                    <template x-for="part in dateParts" :key="part.l">
                        <div class="text-center">
                            <span class="block rounded-lg border border-cyan-300/30 bg-black/60 px-3 py-1.5 font-mono text-3xl font-bold tabular-nums tracking-wider text-cyan-200 shadow-[0_0_18px_rgba(103,232,249,.25)] [text-shadow:0_0_10px_rgba(103,232,249,.7)] sm:text-5xl" x-text="part.v"></span>
                            <span class="kicker-hero mt-1 block" x-text="part.l"></span>
                        </div>
                    </template>
                </div>
                <p class="mt-3 text-sm text-white/70" x-text="agoLabel"></p>
            </div>
            <!-- o que você tinha acabado de zerar -->
            <a x-show="snap?.recent[0]" x-cloak :href="isDemo || !snap?.recent[0] ? null : base + '/jogos/' + snap.recent[0].id" class="group flex max-w-xs items-center gap-3 rounded-2xl border border-white/10 bg-white/[.05] p-3 backdrop-blur">
                <span class="relative block aspect-[16/25] w-14 shrink-0 overflow-hidden rounded-md bg-black ring-1 ring-white/10">
                    <img x-show="snap?.recent[0]?.cover_url" :src="snap?.recent[0]?.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                    <span x-show="snap?.recent[0] && !snap.recent[0].cover_url" class="absolute inset-0 grid place-items-center text-sm font-bold text-white/70" x-text="snap?.recent[0] ? UI.initials(snap.recent[0].title) : ''"></span>
                </span>
                <span class="min-w-0">
                    <span class="block text-2xs uppercase tracking-wider text-white/50">Você tinha acabado de zerar</span>
                    <span class="block truncate font-bold group-hover:underline" x-text="snap?.recent[0]?.title"></span>
                    <span class="block text-xs text-white/55" x-text="snap?.recent[0] ? UI.fmtDate(snap.recent[0].finished_at) : ''"></span>
                </span>
            </a>
        </div>

        <!-- controles -->
        <div class="mt-6 flex flex-wrap items-end gap-3">
            <label class="text-xs text-white/60">Ir para
                <input type="date" x-model="date" :min="first" :max="today" @change="go()" class="mt-1 block rounded-lg border border-white/20 bg-black/40 px-3 py-2 text-sm text-white outline-none [color-scheme:dark] focus:ring-2 focus:ring-cyan-400">
            </label>
            <div class="flex flex-wrap gap-2 text-sm">
                <button type="button" x-show="first" @click="date = first; sync(); go()" class="rounded-lg border border-cyan-300/40 bg-cyan-400/10 px-3 py-2 font-semibold text-cyan-100 transition hover:bg-cyan-400/20">⏮ Primeiro zerado</button>
                <button type="button" x-show="first && span > 60" @click="pos = Math.round(span / 2); fromSlider(); go()" class="rounded-lg border border-white/20 bg-black/30 px-3 py-2 font-semibold text-white/85 transition hover:bg-white/10">◐ Metade do caminho</button>
                <template x-for="p in presets" :key="p.l">
                    <button type="button" @click="jump(p.years)" :disabled="!canJump(p.years)" class="rounded-lg border border-white/20 bg-black/30 px-3 py-2 font-semibold text-white/85 transition hover:bg-white/10 disabled:opacity-30" x-text="p.l"></button>
                </template>
            </div>
        </div>
        <div class="mt-4" x-show="first && first < today" x-cloak>
            <label class="sr-only" for="tm-range">Linha do tempo</label>
            <input id="tm-range" type="range" min="0" :max="span" step="1" x-model.number="pos" @input="fromSlider()" @change="go()" class="w-full accent-cyan-400">
            <div class="flex justify-between text-xs tabular-nums text-white/45"><span x-text="first && UI.fmtDate(first)"></span><span x-text="UI.fmtDate(today)"></span></div>
        </div>

        <!-- o retrato daquele dia -->
        <template x-if="snap">
            <div class="mt-6 border-t border-white/10 pt-5" :class="loading && 'opacity-60 transition-opacity'">
                <p class="text-base text-white/85">
                    <template x-if="snap.games > 0"><span>Naquele dia, você já tinha zerado <strong class="text-cyan-200" x-text="snap.games"></strong> <span x-text="snap.games === 1 ? 'jogo' : 'jogos'"></span>.</span></template>
                    <template x-if="snap.games === 0"><span>Naquele dia, o seu catálogo ainda estava vazio.</span></template>
                </p>
                <dl x-show="snap.games > 0" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4" aria-label="Números daquele dia">
                    <div><dd class="text-3xl font-black tabular-nums" x-text="snap.games"></dd><dt class="text-xs text-white/55">jogos zerados</dt></div>
                    <div><dd class="text-3xl font-black tabular-nums" x-text="nf(snap.minutes / 60)"></dd><dt class="text-xs text-white/55">horas jogadas</dt></div>
                    <div><dd class="text-3xl font-black tabular-nums" x-text="snap.platforms"></dd><dt class="text-xs text-white/55">sistemas</dt></div>
                    <div class="xp-ui"><dd class="text-3xl font-black tabular-nums" x-text="'Nv ' + snap.level"></dd><dt class="text-xs text-white/55"><span x-text="nf(snap.xp)"></span> XP</dt></div>
                </dl>
            </div>
        </template>
    </section>

    <div x-show="error" x-cloak role="alert" class="mt-5 alert-error" x-text="error"></div>

    <template x-if="snap">
        <div :class="loading && 'opacity-60 transition-opacity'">
            <!-- Antes × agora -->
            <section x-show="now && snap.games > 0 && date < today" x-cloak class="mt-6 panel p-5 sm:p-6" aria-labelledby="h-vs">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="h-vs" class="t-section">Naquele dia <span class="text-slate-500">×</span> hoje</h2>
                    <p class="text-sm text-slate-400" x-text="now ? 'Naquela época você tinha ' + pctOf(snap.games, now.games) + '% do catálogo de hoje.' : ''"></p>
                </div>
                <ul class="mt-5 grid gap-5 sm:grid-cols-2">
                    <template x-for="r in compare" :key="r.l">
                        <li>
                            <p class="flex items-baseline justify-between text-sm"><span class="text-slate-400" x-text="r.l"></span><span class="font-semibold tabular-nums text-ok" x-text="r.delta"></span></p>
                            <div class="relative mt-2 h-3 overflow-hidden rounded-full bg-cyan-500/20">
                                <div class="absolute inset-y-0 left-0 rounded-full bg-cyan-400" :style="'width:' + r.pct + '%'"></div>
                            </div>
                            <p class="mt-1 flex justify-between text-xs tabular-nums text-slate-500"><span x-text="'então: ' + r.then"></span><span x-text="'hoje: ' + r.now"></span></p>
                        </li>
                    </template>
                </ul>
            </section>

            <!-- a estante daquele dia -->
            <section x-show="snap.recent.length" class="mt-6" aria-labelledby="h-shelf">
                <h2 id="h-shelf" class="t-section">A sua estante naquele dia</h2>
                <p class="text-sm text-slate-400">Os últimos jogos que você tinha zerado até ali, do mais recente para trás.</p>
                <div class="mt-4 rounded-2xl bg-gradient-to-b from-transparent to-slate-50/5 px-2 pt-2">
                    <ul class="flex items-end gap-3 overflow-x-auto px-2 pb-3">
                        <template x-for="(g, i) in snap.recent" :key="g.id">
                            <li class="w-24 shrink-0 sm:w-28" :style="'opacity:' + Math.max(.45, 1 - i * .06)">
                                <a :href="isDemo ? null : base + '/jogos/' + g.id" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                    <span class="relative block aspect-[16/25] overflow-hidden rounded-md bg-slate-950 shadow-lg ring-1 ring-slate-50/10 transition group-hover:-translate-y-1 motion-reduce:transition-none">
                                        <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover" :class="i ? 'sepia-[.25]' : ''">
                                        <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-2xl font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                        <span x-show="i === 0" class="absolute inset-x-0 bottom-0 bg-cyan-400 py-0.5 text-center text-2xs font-black uppercase tracking-wider text-black">o mais recente</span>
                                    </span>
                                    <span class="mt-1 block truncate text-xs font-semibold text-slate-200 group-hover:text-brand-400" :title="g.title" x-text="g.title"></span>
                                    <span class="block truncate text-2xs text-slate-500" x-text="relDays(g.finished_at)"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                    <div class="h-2 rounded-full bg-gradient-to-r from-gold-900/50 via-gold-700/50 to-gold-900/50" aria-hidden="true"></div>
                </div>
            </section>

            <!-- ±45 dias: o eixo do tempo em volta da data -->
            <section x-show="snap.around.length" class="mt-8 panel p-5 sm:p-6" aria-labelledby="h-around">
                <h2 id="h-around" class="t-section">Por volta dessa época</h2>
                <p class="text-sm text-slate-400">Tudo o que você zerou 45 dias antes e depois da data escolhida.</p>
                <div class="relative mx-4 mt-10 h-32" aria-hidden="true">
                    <div class="absolute inset-x-0 top-1/2 h-px bg-slate-50/15"></div>
                    <div class="absolute bottom-0 left-1/2 top-0 w-0.5 -translate-x-1/2 bg-cyan-400"></div>
                    <span class="absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-cyan-400 px-2 py-0.5 text-2xs font-black text-black" x-text="UI.fmtDate(date)"></span>
                    <template x-for="(g, i) in snap.around" :key="g.id">
                        <span class="absolute h-12 w-8 -translate-x-1/2 overflow-hidden rounded shadow-lg ring-2 ring-slate-900 transition hover:z-10 hover:scale-125"
                              :style="'left:' + axisPos(g.finished_at) + '%;top:' + (i % 2 ? '60%' : '2%')" :title="g.title + ' · ' + UI.fmtDate(g.finished_at)">
                            <img x-show="g.cover_url" :src="g.cover_url" alt="" class="h-full w-full object-cover">
                            <span x-show="!g.cover_url" class="grid h-full w-full place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-[9px] font-bold text-white/85" x-text="UI.initials(g.title)"></span>
                        </span>
                    </template>
                </div>
                <div class="mt-2 flex justify-between text-2xs text-slate-500"><span>−45 dias</span><span>+45 dias</span></div>
                <ul class="mt-4 grid gap-x-6 sm:grid-cols-2">
                    <template x-for="g in snap.around" :key="g.id">
                        <li class="flex items-center justify-between gap-3 border-b border-slate-50/5 py-2 text-sm">
                            <a :href="isDemo ? null : base + '/jogos/' + g.id" class="min-w-0 truncate font-semibold text-slate-100 hover:text-brand-400" x-text="g.title"></a>
                            <span class="shrink-0 text-xs tabular-nums" :class="g.finished_at > date ? 'text-cyan-500' : 'text-slate-400'" x-text="relDays(g.finished_at)"></span>
                        </li>
                    </template>
                </ul>
            </section>
        </div>
    </template>
</main>

<script>
function timeMachine(demo) {
    const base = window.APP.base;
    const iso = (d) => new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    const today = demo ? demo.today : iso(new Date());
    const day = (s) => Date.UTC(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)) / 86400000;
    return {
        isDemo: !!demo, base, today, first: demo ? demo.first : null, date: today, pos: 0, snap: null, loading: false, error: '',
        presets: [{ l: 'Há 1 ano', years: 1 }, { l: 'Há 2 anos', years: 2 }, { l: 'Há 5 anos', years: 5 }, { l: 'Há 10 anos', years: 10 }],
        get span() { return this.first ? Math.max(1, day(this.today) - day(this.first)) : 1; },
        nf: (v) => new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 0 }).format(v ?? 0),
        /** dia, mês e ano separados, para o painel em dígitos */
        get dateParts() {
            const [y, m, d] = (this.date || this.today).split('-');
            return [{ l: 'dia', v: d }, { l: 'mês', v: ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][+m - 1] }, { l: 'ano', v: y }];
        },
        /** "Você voltou 1 ano e 3 meses no tempo" */
        get agoLabel() {
            const a = new Date(this.date + 'T12:00:00'), b = new Date(this.today + 'T12:00:00');
            let months = (b.getFullYear() - a.getFullYear()) * 12 + (b.getMonth() - a.getMonth());
            if (b.getDate() < a.getDate()) months--;
            if (months <= 0) { const days = Math.round((b - a) / 86400000); return days <= 0 ? 'Você está no presente.' : `Você voltou ${days} ${days === 1 ? 'dia' : 'dias'} no tempo.`; }
            const y = Math.floor(months / 12), m = months % 12;
            const parts = [y && `${y} ${y === 1 ? 'ano' : 'anos'}`, m && `${m} ${m === 1 ? 'mês' : 'meses'}`].filter(Boolean);
            return 'Você voltou ' + parts.join(' e ') + ' no tempo.';
        },
        longDate(s) { return new Date(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' }); },
        shift(years) { const y = +this.today.slice(0, 4) - years; return `${y}-${this.today.slice(5, 7)}-${this.today.slice(8, 10) === '29' && this.today.slice(5, 7) === '02' ? '28' : this.today.slice(8, 10)}`; },
        canJump(years) { return !this.first || this.shift(years) >= this.first; },
        jump(years) { this.date = this.shift(years); this.sync(); this.go(); },
        sync() { if (this.first) this.pos = Math.max(0, Math.min(this.span, day(this.date) - day(this.first))); },
        fromSlider() { this.date = new Date((day(this.first) + this.pos) * 86400000).toISOString().slice(0, 10); },
        now: null,   // o retrato de hoje, para comparar
        pctOf: (a, b) => (b ? Math.round(a / b * 100) : 0),
        get compare() {
            const s = this.snap, n = this.now;
            if (!s || !n) return [];
            const row = (l, a, b, f = (v) => this.nf(v)) => ({ l, then: f(a), now: f(b), pct: b ? Math.min(100, a / b * 100) : 0, delta: b - a > 0 ? '+' + f(b - a) + ' desde então' : 'igual' });
            return [
                row('Jogos zerados', s.games, n.games),
                row('Horas jogadas', s.minutes / 60, n.minutes / 60, (v) => this.nf(v) + 'h'),
                row('Sistemas', s.platforms, n.platforms),
                row('Nível', s.level, n.level),
            ];
        },
        /** "12 dias antes", "8 dias depois": em relação à data escolhida */
        relDays(d) {
            const diff = Math.round(day(d.slice(0, 10)) - day(this.date));
            if (diff === 0) return 'no mesmo dia';
            const n = Math.abs(diff), u = n === 1 ? 'dia' : 'dias';
            return diff < 0 ? `${n} ${u} antes` : `${n} ${u} depois`;
        },
        axisPos(d) { const diff = day(d.slice(0, 10)) - day(this.date); return Math.max(2, Math.min(98, (diff + 45) / 90 * 100)); },
        async init() {
            await this.go();
            try { this.now = (await api.get((this.isDemo ? '/api/demo/time?date=' : '/api/time/snapshot?date=') + encodeURIComponent(this.today))).data; } catch (e) { /* sem comparação */ }
        },
        async go(first) {
            if (!this.date) return;
            this.loading = true; this.error = '';
            try {
                const url = (this.isDemo ? '/api/demo/time?date=' : '/api/time/snapshot?date=') + encodeURIComponent(this.date);
                const { data } = await api.get(url);
                this.snap = data; this.date = data.date;
                if (data.first) this.first = data.first;
                this.sync();
            } catch (e) { this.error = e.message; }
            this.loading = false;
        },
    };
}
</script>
