<?php /** @var array $me */ ?>
<main class="page page-normal" x-data='ranking(<?= e(json_encode($me['username'])) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <!-- ========== Arena: controles, pódio e a sua posição ========== -->
    <section class="hero-box text-white" aria-label="Pódio">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="absolute left-1/2 top-[-30%] h-[28rem] w-[28rem] -translate-x-1/2 rounded-full bg-gold-400/20 blur-3xl"></div>
            <div class="absolute inset-0 bg-[repeating-linear-gradient(90deg,transparent_0_79px,rgb(255_255_255/.03)_79px_80px)]"></div>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-4 p-5 sm:p-8">
            <div>
                <p class="kicker-hero">Quem está no topo</p>
                <h1 class="mt-1 t-hero">Ranking</h1>
                <p class="mt-1 text-sm text-white/60">Entre os amigos com perfil público<span x-show="period !== 'all'" x-text="' · só ' + period"></span>.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl bg-white/10 p-1 text-sm font-semibold" role="group" aria-label="Classificar por">
                    <template x-for="m in metrics" :key="m.v">
                        <button type="button" @click="metric = m.v; load()" :aria-pressed="metric === m.v" class="rounded-lg px-3 py-1.5 transition"
                                :class="metric === m.v ? 'bg-white text-black' : 'text-white/70 hover:text-white'" x-text="m.l"></button>
                    </template>
                </div>
                <label class="sr-only" for="rk-period">Período</label>
                <select id="rk-period" x-model="period" @change="load()" class="rounded-xl border border-white/15 bg-black/40 px-3 py-2 text-sm text-white outline-none focus:ring-2 focus:ring-white/60">
                    <option value="all">De sempre</option>
                    <template x-for="y in years" :key="y"><option :value="String(y)" x-text="'Só ' + y"></option></template>
                </select>
            </div>
        </div>

        <div x-show="!ready && !error" class="mx-5 mb-8 h-56 animate-pulse rounded-2xl bg-white/5 sm:mx-8"></div>

        <!-- pódio: 2º, 1º, 3º -->
        <div x-show="ready && rows.length" x-cloak class="mx-auto grid max-w-3xl grid-cols-3 items-end gap-2 px-3 sm:gap-4 sm:px-8">
            <template x-for="slot in podium" :key="slot.place">
                <div class="flex min-w-0 flex-col items-center text-center">
                    <template x-if="slot.r">
                        <a :href="profileUrl(slot.r)" class="group flex w-full min-w-0 flex-col items-center focus:outline-none">
                            <span x-show="slot.place === 1" class="text-2xl sm:text-3xl" aria-hidden="true">👑</span>
                            <span class="relative grid place-items-center rounded-full font-black shadow-2xl ring-4 transition group-hover:scale-105 motion-reduce:transition-none"
                                  :class="[slot.place === 1 ? 'h-20 w-20 text-3xl sm:h-24 sm:w-24' : 'h-14 w-14 text-xl sm:h-[4.5rem] sm:w-[4.5rem] sm:text-2xl', slot.ring, slot.r.is_me ? 'outline outline-2 outline-offset-4 outline-white/70' : '']"
                                  :style="UI.avatarStyle(slot.r.username)" x-html="UI.avatarContent(slot.r.username)"></span>
                            <span class="mt-2 w-full truncate text-sm font-bold group-hover:underline sm:text-base" x-text="slot.r.username + (slot.r.is_me ? ' (você)' : '')"></span>
                            <span class="text-lg font-black tabular-nums sm:text-2xl" :class="slot.text" x-text="metricLabel(slot.r)"></span>
                            <span class="text-2xs text-white/55" x-text="'Nível ' + slot.r.level"></span>
                        </a>
                    </template>
                    <div class="mt-3 grid w-full place-items-start justify-center rounded-t-xl pt-2 text-3xl font-black shadow-inner sm:text-4xl" :class="slot.base" :style="'height:' + slot.h"
                         x-text="slot.r ? slot.place : ''"></div>
                </div>
            </template>
        </div>

        <!-- a sua posição -->
        <div x-show="ready && mine" x-cloak class="border-t border-white/10 bg-black/40 px-5 py-4 sm:px-8" aria-live="polite">
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                <p class="flex items-baseline gap-3">
                    <span class="text-xs uppercase tracking-wider text-white/55">Sua posição</span>
                    <span class="text-3xl font-black tabular-nums" x-text="mine ? '#' + mine.rank : ''"></span>
                    <span class="text-sm text-white/60" x-text="'de ' + rows.length + (rows.length === 1 ? ' jogador' : ' jogadores') + ' · ' + (mine ? metricLabel(mine) : '')"></span>
                </p>
                <p class="text-sm text-white/80" x-text="chaseText"></p>
            </div>
            <div x-show="above" class="mt-3">
                <div class="flex justify-between text-2xs text-white/50"><span>Você</span><span x-text="above ? above.username + ' (#' + above.rank + ')' : ''"></span></div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-gold-300" :style="'width:' + chasePct + '%'"></div></div>
            </div>
            <p x-show="mine?.hidden" class="mt-2 text-xs text-white/55">Seu perfil está oculto: só você vê a sua linha. <a href="<?= e(url('/u/' . rawurlencode($me['username']))) ?>" class="font-semibold underline">Mudar no perfil</a></p>
        </div>
    </section>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <p x-show="ready && rows.length === 0" x-cloak class="mt-8 text-center text-slate-400">Ainda não há jogadores no ranking.</p>

    <!-- ========== Destaques de cada categoria + em alta ========== -->
    <section x-show="ready && rows.length > 1" x-cloak class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Destaques">
        <template x-for="h in highlights" :key="h.l">
            <a :href="h.r ? profileUrl(h.r) : null" class="flex items-center gap-3 panel p-4 transition hover:border-brand-400/50">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-lg font-black" :style="h.r ? UI.avatarStyle(h.r.username) : ''" x-html="h.r ? UI.avatarContent(h.r.username) : '—'"></span>
                <span class="min-w-0">
                    <span class="kicker block" x-text="h.icon + ' ' + h.l"></span>
                    <span class="block truncate font-bold text-slate-50" x-text="h.r ? h.r.username : 'Ninguém ainda'"></span>
                    <span class="block text-xs text-slate-400" x-text="h.r ? h.v(h.r) : ''"></span>
                </span>
            </a>
        </template>
    </section>

    <!-- ========== A lista ========== -->
    <section x-show="ready && rows.length" x-cloak class="mt-8" aria-labelledby="h-list">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="h-list" class="t-section" x-text="q.trim() ? 'Resultado da busca' : (rows.length > 3 ? 'Do 4º lugar em diante' : 'Todos os jogadores')"></h2>
            <label class="relative">
                <span class="sr-only">Buscar jogador</span>
                <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                <input type="search" x-model="q" placeholder="Buscar jogador…" class="w-48 rounded-lg border border-slate-50/10 bg-slate-900/70 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-brand-500">
            </label>
        </div>
        <p x-show="shown.length === 0" class="mt-6 text-center text-sm text-slate-400" x-text="q.trim() ? 'Nenhum jogador com esse nome.' : 'Todo mundo já está no pódio. 🏆'"></p>

        <ol x-show="shown.length" class="mt-4 space-y-2.5">
            <template x-for="r in shown" :key="r.username">
                <li>
                    <a :href="profileUrl(r)"
                       class="group relative flex items-center gap-3 overflow-hidden rounded-2xl border px-3 py-3 transition hover:-translate-y-0.5 hover:border-brand-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0 sm:gap-4 sm:px-4"
                       :class="r.is_me ? 'border-brand-400/50 bg-brand-500/10' : 'border-slate-50/10 bg-slate-900/70'">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-base font-black tabular-nums"
                              :style="medal(r.rank)" :class="r.rank > 3 ? 'text-slate-300' : 'shadow ring-1 ring-black/30'" x-text="r.rank"></span>
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-lg font-bold" aria-hidden="true" :style="UI.avatarStyle(r.username)" x-html="UI.avatarContent(r.username)"></span>

                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2">
                                <span class="truncate text-base font-bold text-slate-50" x-text="r.username"></span>
                                <span x-show="r.is_me" class="shrink-0 rounded bg-brand-600 px-1.5 py-0.5 text-2xs font-bold uppercase tracking-wider text-on-brand">Você</span>
                                <span x-show="r.hidden" class="shrink-0 rounded bg-slate-50/10 px-1.5 py-0.5 text-2xs font-bold uppercase tracking-wider text-slate-400">Oculto</span>
                                <span x-show="r.month >= 2" class="shrink-0 rounded bg-orange-500/15 px-1.5 py-0.5 text-2xs font-bold uppercase tracking-wider text-orange-400" :title="r.month + ' jogos zerados nos últimos 30 dias'">🔥 em alta</span>
                            </p>
                            <p class="truncate text-xs text-slate-400">
                                <span x-text="'Nível ' + r.level"></span>
                                <span x-show="r.last" x-text="r.last ? ' · último: ' + r.last.title : ''"></span>
                            </p>
                            <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-slate-50/10" aria-hidden="true">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="`width:${leader ? Math.max(2, value(r) / leader * 100) : 0}%`"></div>
                            </div>
                        </div>

                        <span x-show="r.last?.cover_url" class="relative hidden h-12 w-9 shrink-0 overflow-hidden rounded ring-1 ring-black/40 sm:block" :title="r.last ? 'Último zerado: ' + r.last.title : ''">
                            <img :src="r.last?.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                        </span>

                        <dl class="flex shrink-0 items-center gap-4 text-right sm:gap-6">
                            <template x-for="m in metrics" :key="m.v">
                                <div :class="metric === m.v ? '' : 'hidden md:block'">
                                    <dt class="text-2xs uppercase tracking-wider text-slate-500" x-text="m.short"></dt>
                                    <dd class="tabular-nums" :class="metric === m.v ? 'text-lg font-black text-brand-400' : 'text-sm text-slate-300'" x-text="m.v === 'hours' ? fmt(r.hours, 0) : fmt(r[m.v])"></dd>
                                </div>
                            </template>
                            <div class="hidden lg:block">
                                <dt class="text-2xs uppercase tracking-wider text-slate-500">Nota</dt>
                                <dd class="mt-0.5"><span x-show="r.avg_rating !== null" class="rounded-md px-1.5 py-0.5 text-sm font-black ring-1 ring-black/30"
                                      :class="r.avg_rating !== null ? UI.ratingClass(Math.round(r.avg_rating)) : ''" :style="r.avg_rating !== null ? UI.ratingStyle(Math.round(r.avg_rating)) : ''"
                                      x-text="r.avg_rating !== null ? fmt(r.avg_rating, 1) : ''"></span>
                                    <span x-show="r.avg_rating === null" class="text-sm text-slate-500">—</span></dd>
                            </div>
                        </dl>
                    </a>
                </li>
            </template>
        </ol>
    </section>
</main>

<script>
function ranking(myName) {
    return {
        base: window.APP.base,
        metric: 'xp',
        period: 'all',
        q: '',
        rows: [],
        years: [],
        ready: false,
        error: '',
        metrics: [{ v: 'xp', l: 'XP', short: 'XP' }, { v: 'games', l: 'Jogos zerados', short: 'Jogos' }, { v: 'hours', l: 'Horas jogadas', short: 'Horas' }],

        fmt: (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0),
        value(r) { return r[this.metric]; },
        metricLabel(r) {
            const v = this.value(r);
            return this.metric === 'xp' ? this.fmt(v) + ' XP' : this.metric === 'games' ? this.fmt(v) + (v === 1 ? ' jogo' : ' jogos') : this.fmt(v, 0) + 'h';
        },
        profileUrl(r) { return this.base + '/u/' + encodeURIComponent(r.username); },
        get leader() { return this.rows.length ? Math.max(...this.rows.map((r) => this.value(r))) : 0; },
        get mine() { return this.rows.find((r) => r.is_me) ?? null; },
        /** quem está logo à frente (posição melhor, valor maior) */
        get above() {
            const me = this.mine;
            if (!me) return null;
            return [...this.rows].filter((r) => this.value(r) > this.value(me)).sort((a, b) => this.value(a) - this.value(b))[0] ?? null;
        },
        get below() {
            const me = this.mine;
            if (!me) return null;
            return [...this.rows].filter((r) => this.value(r) < this.value(me)).sort((a, b) => this.value(b) - this.value(a))[0] ?? null;
        },
        get chaseText() {
            const me = this.mine;
            if (!me) return '';
            const unit = { xp: ' XP', games: ' jogo(s)', hours: 'h' }[this.metric];
            const gap = (x) => (this.metric === 'hours' ? this.fmt(Math.ceil(x), 0) : this.fmt(x)) + unit;
            if (!this.above) return this.below ? `👑 Você lidera, ${gap(this.value(me) - this.value(this.below))} à frente de ${this.below.username}.` : '👑 Você lidera.';
            return `Faltam ${gap(this.value(this.above) - this.value(me) + (this.metric === 'hours' ? 0 : 1))} para passar ${this.above.username}.`;
        },
        get chasePct() {
            const me = this.mine, a = this.above;
            if (!me || !a) return 100;
            const floor = this.below ? this.value(this.below) : 0;
            const span = this.value(a) - floor;
            return span > 0 ? Math.max(4, Math.min(100, (this.value(me) - floor) / span * 100)) : 50;
        },
        /** pódio na ordem visual 2º, 1º, 3º */
        get podium() {
            const at = (i) => this.rows[i] ?? null;
            return [
                { place: 2, r: at(1), h: '5.5rem', ring: 'ring-[#d1d5db]', text: 'text-[#e5e7eb]', base: 'bg-gradient-to-b from-[#d1d5db]/80 to-[#6b7280]/40 text-[#111827]/70' },
                { place: 1, r: at(0), h: '8rem', ring: 'ring-gold-300', text: 'text-gold-300', base: 'bg-gradient-to-b from-gold-300 to-gold-600/50 text-gold-950/70' },
                { place: 3, r: at(2), h: '4rem', ring: 'ring-orange-400', text: 'text-orange-300', base: 'bg-gradient-to-b from-orange-400/80 to-orange-800/40 text-orange-950/70' },
            ];
        },
        /** os campeões de cada categoria, independentemente da métrica escolhida */
        get highlights() {
            const best = (fn) => [...this.rows].filter((r) => fn(r) !== null && fn(r) > 0).sort((a, b) => fn(b) - fn(a))[0] ?? null;
            return [
                { icon: '🎮', l: 'Mais jogos', r: best((r) => r.games), v: (r) => this.fmt(r.games) + ' zerados' },
                { icon: '⏱', l: 'Mais horas', r: best((r) => r.hours), v: (r) => this.fmt(r.hours, 0) + 'h jogadas' },
                { icon: '⭐', l: 'Nota mais alta', r: best((r) => (r.games >= 3 ? r.avg_rating : null)), v: (r) => 'média ' + this.fmt(r.avg_rating, 1) },
                { icon: '🔥', l: 'Em alta (30 dias)', r: best((r) => r.month), v: (r) => r.month + (r.month === 1 ? ' zerado' : ' zerados') + ' no último mês' },
            ];
        },
        get shown() {
            const q = this.q.trim().toLowerCase();
            return q ? this.rows.filter((r) => r.username.toLowerCase().includes(q)) : this.rows.slice(3);
        },

        /** Ouro, prata e bronze para o pódio. */
        medal(rank) {
            return {
                1: 'background:linear-gradient(135deg,#fde047,#f59e0b);color:#3b1d00',
                2: 'background:linear-gradient(135deg,#f3f4f6,#9ca3af);color:#1f2937',
                3: 'background:linear-gradient(135deg,#fdba74,#c2410c);color:#2a1100',
            }[rank] ?? 'background:rgb(var(--s-50) / .08)';
        },

        async init() {
            const p = new URLSearchParams(location.search);
            if (['xp', 'games', 'hours'].includes(p.get('metric'))) this.metric = p.get('metric');
            if (/^\d{4}$/.test(p.get('period') || '')) this.period = p.get('period');
            await this.load();
        },

        async load() {
            this.error = '';
            try {
                const r = await api.get(`/api/ranking?metric=${this.metric}&period=${this.period}`);
                this.rows = r.data;
                this.years = r.years;
                this.ready = true;
                history.replaceState(null, '', location.pathname + (this.metric !== 'xp' || this.period !== 'all' ? `?metric=${this.metric}&period=${this.period}` : ''));
            } catch (e) {
                if (e.status === 401) return (location.href = this.base + '/login');
                this.error = e.message;
            }
        },
    };
}
</script>
