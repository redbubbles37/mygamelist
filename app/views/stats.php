<?php
/** @var array|null $me */
/** @var bool $demo */
/** @var array $stats */
$demo = $demo ?? false;
?>
<main class="page page-normal" x-data='stats(<?= e(json_encode($stats, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo): ?>
    <?php ob_start(); ?>: estas estatísticas são calculadas sobre os jogos fictícios do catálogo de exemplo.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <!-- ========== Destaque: a ficha do jogador ========== -->
    <header class="hero-box p-5 text-white sm:p-8">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 opacity-[.07] [background-image:linear-gradient(rgb(255_255_255)_1px,transparent_1px),linear-gradient(90deg,rgb(255_255_255)_1px,transparent_1px)] [background-size:28px_28px]"></div>
            <div class="absolute -left-20 -top-24 h-72 w-72 rounded-full bg-brand-500/25 blur-3xl"></div>
            <div class="absolute -bottom-24 right-0 h-72 w-72 rounded-full bg-accent/20 blur-3xl"></div>
        </div>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="kicker-hero">Estatísticas</p>
                <h1 class="mt-1 t-hero">Sua ficha de jogador</h1>
                <p class="mt-1 text-sm text-white/65">Recordes, hábitos e gostos de todos os jogos que você zerou.</p>
            </div>
            <p x-show="!s.empty && archetype" class="rounded-2xl border border-white/15 bg-white/5 px-4 py-2 text-right">
                <span class="block text-2xs uppercase tracking-wider text-white/55">Seu arquétipo</span>
                <strong class="text-lg" x-text="archetype"></strong>
            </p>
        </div>
        <template x-if="!s.empty">
            <div>
                <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
                    <template x-for="c in headline" :key="c.l">
                        <div class="border-l-2 border-white/15 pl-4">
                            <dt class="kicker-hero" x-text="c.l"></dt>
                            <dd class="mt-1 font-mono text-3xl font-bold tabular-nums sm:text-4xl" x-text="c.v"></dd>
                            <dd class="text-xs text-white/55" x-text="c.h"></dd>
                        </div>
                    </template>
                </dl>
                <ul class="mt-6 flex flex-wrap gap-2 border-t border-white/10 pt-5 text-sm">
                    <template x-for="t in profile" :key="t.k">
                        <li class="rounded-full bg-white/10 px-3 py-1"><span class="text-white/55" x-text="t.k + ': '"></span><strong x-text="t.v"></strong></li>
                    </template>
                </ul>
            </div>
        </template>
    </header>

    <section x-show="s.empty" x-cloak class="mt-6 empty">
        <p class="t-section">Ainda não há o que analisar</p>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Registre alguns jogos e as estatísticas aparecem aqui.</p>
        <a href="<?= e(url('/jogos/novo')) ?>" class="btn mt-4 inline-block">Adicionar um jogo</a>
    </section>

    <template x-if="!s.empty">
        <div>
            <!-- abas do painel -->
            <nav class="sticky top-2 z-20 mt-6 flex justify-center" aria-label="Seções das estatísticas">
                <div class="flex max-w-full gap-1 overflow-x-auto rounded-full border border-slate-50/10 bg-slate-900/90 p-1 text-sm font-semibold shadow-lg backdrop-blur" role="tablist">
                    <template x-for="t in tabs" :key="t.v">
                        <button type="button" role="tab" :aria-selected="tab === t.v" @click="setTab(t.v)" class="whitespace-nowrap rounded-full px-4 py-1.5 transition"
                                :class="tab === t.v ? 'bg-brand-600 text-on-brand' : 'text-slate-300 hover:text-slate-50'" x-text="t.icon + ' ' + t.l"></button>
                    </template>
                </div>
            </nav>

            <!-- ===== Visão geral ===== -->
            <div x-show="tab === 'geral'" class="mt-6 space-y-6">
                <section class="grid grid-cols-2 gap-3 md:grid-cols-4" aria-label="Outros números">
                    <template x-for="c in cards" :key="c.l">
                        <div class="panel p-4">
                            <p class="text-2xl font-black tabular-nums text-slate-50" x-text="c.v"></p>
                            <p class="text-sm font-semibold text-slate-200" x-text="c.l"></p>
                            <p class="text-xs text-slate-400" x-text="c.h"></p>
                        </div>
                    </template>
                </section>

                <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
                    <!-- curiosidades em cartões numerados -->
                    <section class="panel p-5" aria-labelledby="h-insights">
                        <h2 id="h-insights" class="t-section">Você sabia?</h2>
                        <ol class="mt-4 space-y-3">
                            <template x-for="(i, k) in s.insights" :key="i.text">
                                <li class="flex gap-3 text-sm text-slate-300">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-500/15 text-xs font-black text-brand-400" x-text="k + 1"></span>
                                    <span class="pt-1" x-text="i.text"></span>
                                </li>
                            </template>
                        </ol>
                    </section>
                    <!-- mosaico dos gêneros: área proporcional à quantidade -->
                    <section class="panel p-5" aria-labelledby="h-mosaic">
                        <h2 id="h-mosaic" class="t-section">O mosaico dos seus gêneros</h2>
                        <p class="text-xs text-slate-500">Cada bloco cresce com a quantidade de jogos zerados.</p>
                        <div class="mt-4 flex h-64 flex-wrap gap-1.5 overflow-hidden">
                            <template x-for="(g, i) in s.genres" :key="g.name">
                                <div class="flex min-w-[4.5rem] flex-col justify-end overflow-hidden rounded-lg p-2 text-white" :style="'flex-grow:' + g.n + ';flex-basis:' + Math.max(18, g.n / s.genres[0].n * 45) + '%;background:hsl(' + (250 + i * 23) % 360 + ' 55% ' + (42 - i * 2) + '%)'" :title="g.name + ': ' + g.n + ' jogos'">
                                    <span class="truncate text-xs font-bold" x-text="g.name"></span>
                                    <span class="text-2xs tabular-nums text-white/75" x-text="g.n"></span>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>

                <!-- recordes: parede de troféus -->
                <section aria-labelledby="h-records">
                    <h2 id="h-records" class="t-section">Parede de recordes</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="(r, i) in s.records" :key="r.label">
                            <div class="relative overflow-hidden panel p-4 pl-16">
                                <span class="absolute left-4 top-4 grid h-9 w-9 place-items-center rounded-xl bg-gold-400/15 text-lg" x-text="['⏳', '⚡', '⭐', '🧊', '🔥', '🏁', '🎯', '📅', '🧗'][i % 9]"></span>
                                <p class="kicker" x-text="r.label"></p>
                                <p class="mt-1 truncate text-base font-bold text-slate-50" :title="r.title" x-text="r.title"></p>
                                <p class="text-sm text-slate-400"><span x-text="r.platform"></span> · <span class="font-semibold text-brand-400" x-text="r.detail"></span></p>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <!-- ===== Hábitos ===== -->
            <div x-show="tab === 'habitos'" x-cloak class="mt-6 grid gap-6 lg:grid-cols-2">
                <!-- relógio da semana -->
                <section class="panel p-5" aria-labelledby="h-week">
                    <h2 id="h-week" class="t-section">O relógio da semana</h2>
                    <p class="text-sm text-slate-400" x-text="'Você termina mais jogos ' + bestWeekday.prep + ' ' + bestWeekday.label.toLowerCase() + '.'"></p>
                    <div class="relative mx-auto mt-4 h-72 w-72" role="img" :aria-label="'Jogos zerados por dia da semana. Mais: ' + bestWeekday.label">
                        <span class="absolute inset-[6%] rounded-full border border-dashed border-slate-50/10"></span>
                        <template x-for="(w, i) in s.weekdays" :key="w.label">
                            <span class="absolute bottom-1/2 left-1/2 flex h-[45%] w-6 -translate-x-1/2 flex-col items-center justify-start" :style="'transform-origin:50% 100%;transform:translateX(-50%) rotate(' + (i * 360 / 7) + 'deg)'">
                                <span class="text-2xs font-bold text-slate-400" :style="'transform:rotate(' + (-i * 360 / 7) + 'deg)'" x-text="w.label.slice(0, 3)"></span>
                                <span class="mt-1 flex w-full flex-1 flex-col justify-end"><span class="w-full rounded-md" :class="w.n === weekMax ? 'bg-brand-400' : 'bg-brand-600/60'" :style="'height:' + Math.max(4, w.n / weekMax * 100) + '%'" :title="w.label + ': ' + w.n"></span></span>
                                <span class="h-[34%] shrink-0" aria-hidden="true"></span>
                            </span>
                        </template>
                        <span class="absolute left-1/2 top-1/2 grid h-20 w-20 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-slate-50/5 text-center text-xs font-bold leading-tight text-slate-300" x-text="weekendShare + '% no fim de semana'"></span>
                    </div>
                    <p class="text-center text-xs text-slate-500" x-text="weekendShare + '% dos jogos foram zerados no sábado ou no domingo.'"></p>
                </section>

                <!-- ritmo por ano -->
                <section class="panel p-5" aria-labelledby="h-years">
                    <h2 id="h-years" class="t-section">Ritmo ano a ano</h2>
                    <p class="text-sm text-slate-400" x-text="bestYear ? 'Seu ano mais forte foi ' + bestYear.year + ', com ' + bestYear.n + ' jogos e ' + nf(bestYear.minutes / 60) + 'h.' : ''"></p>
                    <ol class="mt-5 space-y-2">
                        <template x-for="y in [...s.finish_years].reverse()" :key="y.year">
                            <li class="grid grid-cols-[3rem_1fr_auto] items-center gap-3 text-sm">
                                <span class="font-bold tabular-nums text-slate-300" x-text="y.year"></span>
                                <span class="relative h-5 overflow-hidden rounded-md bg-slate-50/5">
                                    <span class="absolute inset-y-0 left-0 rounded-md bg-gradient-to-r from-brand-600 to-accent" :style="'width:' + (y.minutes / yearMax * 100) + '%'"></span>
                                </span>
                                <span class="w-28 text-right text-xs tabular-nums text-slate-400" x-text="y.n + ' jogos · ' + nf(y.minutes / 60) + 'h'"></span>
                            </li>
                        </template>
                    </ol>
                </section>

                <!-- linha das décadas de lançamento -->
                <section class="panel p-5 lg:col-span-2" aria-labelledby="h-dec">
                    <h2 id="h-dec" class="t-section">De que época são os seus jogos</h2>
                    <p class="text-sm text-slate-400">Década de lançamento dos jogos zerados.</p>
                    <div class="mt-5 flex h-44 items-end gap-2 sm:gap-4">
                        <template x-for="d in s.decades" :key="d.name">
                            <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1">
                                <span class="text-xs font-bold tabular-nums text-slate-300" x-text="d.n"></span>
                                <span class="w-full rounded-t-lg" :style="'height:' + Math.max(3, d.n / decadeMax * 100) + '%;background:linear-gradient(to top,hsl(' + decadeHue(d.name) + ' 60% 35%),hsl(' + decadeHue(d.name) + ' 70% 55%))'"></span>
                                <span class="truncate text-xs font-semibold text-slate-400" x-text="d.name"></span>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <!-- ===== Gostos ===== -->
            <div x-show="tab === 'gostos'" x-cloak class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="panel p-5" aria-labelledby="h-rates">
                    <h2 id="h-rates" class="t-section">Como você avalia</h2>
                    <p class="text-sm text-slate-400" x-text="'Nota mais dada: ' + modeRating + ' · ' + generosity"></p>
                    <div class="mt-5 flex h-48 items-end gap-1.5">
                        <template x-for="r in s.rating_dist" :key="r.rating">
                            <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1">
                                <span class="text-2xs font-bold tabular-nums text-slate-400" x-text="r.n || ''"></span>
                                <span class="w-full rounded-t-md" :class="r.rating === 11 ? 'rating-gold' : ''" :style="(r.rating === 11 ? '' : 'background:' + UI.RATING_COLORS[r.rating] + ';') + 'height:' + (r.n ? Math.max(3, r.n / ratingMax * 100) : 0) + '%'"></span>
                                <span class="text-xs font-bold text-slate-400" x-text="r.rating"></span>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- a escada da dificuldade -->
                <section class="panel p-5" aria-labelledby="h-diff">
                    <h2 id="h-diff" class="t-section">A escada da dificuldade</h2>
                    <p class="text-sm text-slate-400">Quanto mais alto o degrau, mais difícil. A largura é a quantidade de jogos.</p>
                    <ol class="mt-5 space-y-2">
                        <template x-for="(d, i) in [...s.difficulty].reverse()" :key="d.name">
                            <li class="flex items-center gap-3" :style="'padding-left:' + (s.difficulty.length - 1 - i) * 0 + 'rem'">
                                <span class="grid h-9 w-12 shrink-0 place-items-center rounded-md text-xs font-black" :style="UI.diffStyle(d.name)" x-text="d.name"></span>
                                <span class="relative h-9 flex-1 overflow-hidden rounded-md bg-slate-50/5">
                                    <span class="absolute inset-y-0 left-0 rounded-md opacity-40" :style="UI.diffStyle(d.name) + ';width:' + (d.n / diffMax * 100) + '%'"></span>
                                    <span class="relative flex h-full items-center px-3 text-xs text-slate-200">
                                        <strong class="mr-2 tabular-nums" x-text="d.n + ' jogos'"></strong>
                                        <span class="truncate text-slate-400" x-text="(d.avg_rating === null ? '' : 'nota ' + nf(d.avg_rating, 1)) + (d.avg_minutes === null ? '' : ' · ' + UI.fmtTime(d.avg_minutes) + ' em média')"></span>
                                    </span>
                                </span>
                            </li>
                        </template>
                    </ol>
                </section>

                <!-- o que mais joga × o que melhor avalia -->
                <section class="panel p-5 lg:col-span-2" aria-labelledby="h-love">
                    <h2 id="h-love" class="t-section">O que você mais joga × o que você mais ama</h2>
                    <p class="text-sm text-slate-400">Entre os gêneros com 3 jogos ou mais: quantidade contra nota média.</p>
                    <div class="mt-5 grid gap-6 sm:grid-cols-2">
                        <template x-for="col in loveHate" :key="col.t">
                            <div>
                                <p class="kicker" x-text="col.t"></p>
                                <ol class="mt-3 space-y-2">
                                    <template x-for="(g, i) in col.rows" :key="g.name">
                                        <li class="flex items-center gap-3 text-sm">
                                            <span class="w-5 text-right text-xs font-bold text-slate-500" x-text="i + 1"></span>
                                            <span class="min-w-0 flex-1 truncate font-semibold text-slate-100" x-text="g.name"></span>
                                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-black" :style="UI.ratingStyle(Math.min(10, Math.max(1, Math.round(g.avg_rating))))" x-text="nf(g.avg_rating, 1)"></span>
                                            <span class="w-14 shrink-0 text-right text-xs tabular-nums text-slate-400" x-text="g.n + ' jogos'"></span>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <!-- ===== Rankings ===== -->
            <div x-show="tab === 'rankings'" x-cloak class="mt-6 grid gap-4 lg:grid-cols-2">
                <template x-for="t in tops" :key="t.key">
                    <section x-show="s[t.key].length" class="panel p-5">
                        <h2 class="text-base font-bold text-slate-50" x-text="t.title"></h2>
                        <ol class="mt-3 space-y-2.5">
                            <template x-for="(row, i) in s[t.key]" :key="row.name">
                                <li class="text-sm">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <span class="truncate font-semibold text-slate-100" :title="row.name"><span class="mr-1.5 text-xs text-slate-500" x-text="(i + 1) + '.'"></span><span x-text="row.name"></span></span>
                                        <span class="shrink-0 text-xs tabular-nums text-slate-400"><span x-text="row.n"></span> · nota <span class="font-semibold text-slate-200" x-text="nf(row.avg_rating, 1)"></span> · <span x-text="nf(row.minutes / 60) + 'h'"></span></span>
                                    </div>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-50/10"><div class="h-full rounded-full" :class="t.bar" :style="'width:' + (row.n / s[t.key][0].n * 100) + '%'"></div></div>
                                </li>
                            </template>
                        </ol>
                    </section>
                </template>
            </div>
        </div>
    </template>
</main>

<script>
function stats(s) {
    const nf = (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0);
    const TABS = ['geral', 'habitos', 'gostos', 'rankings'];
    return {
        s, nf,
        tab: TABS.includes(location.hash.slice(1)) ? location.hash.slice(1) : 'geral',
        tabs: [{ v: 'geral', l: 'Visão geral', icon: '📋' }, { v: 'habitos', l: 'Hábitos', icon: '🕹' }, { v: 'gostos', l: 'Gostos', icon: '❤' }, { v: 'rankings', l: 'Rankings', icon: '🏅' }],
        setTab(v) { this.tab = v; history.replaceState(null, '', '#' + v); },
        tops: [
            { key: 'genres', title: 'Gêneros mais zerados', bar: 'bg-brand-500' },
            { key: 'platforms', title: 'Sistemas mais usados', bar: 'bg-accent' },
            { key: 'developers', title: 'Desenvolvedoras mais frequentes', bar: 'bg-ok' },
            { key: 'publishers', title: 'Publishers mais frequentes', bar: 'bg-gold-500' },
        ],
        get headline() {
            const t = this.s.totals;
            const span = Math.max(1, Math.round((Date.parse(t.last) - Date.parse(t.first)) / 86400000));
            const every = t.games > 1 ? span / (t.games - 1) : null;
            return [
                { l: 'Jogos zerados', v: nf(t.games), h: 'de ' + UI.fmtDate(t.first) + ' a ' + UI.fmtDate(t.last) },
                { l: 'Horas jogadas', v: nf(Math.round(t.minutes / 60)), h: nf(t.days, 1) + ' dias inteiros' },
                { l: 'Nota média', v: nf(t.avg_rating, 1), h: 'de 1 a 11 · ' + UI.fmtTime(t.avg_minutes) + ' por jogo' },
                { l: 'Ritmo', v: every === null ? '—' : every < 1.5 ? nf(t.games / span, 1) + '/dia' : nf(every, 0) + ' dias', h: every === null ? 'zere mais um para medir' : every < 1.5 ? 'jogos zerados por dia' : 'entre um jogo zerado e o próximo' },
            ];
        },
        get profile() {
            const top = (k) => this.s[k]?.[0]?.name;
            return [
                { k: 'Gênero', v: top('genres') }, { k: 'Sistema', v: top('platforms') },
                { k: 'Estúdio', v: top('developers') }, { k: 'Publisher', v: top('publishers') },
            ].filter((x) => x.v);
        },
        /** um "título" para o jogador, a partir dos hábitos */
        get archetype() {
            const t = this.s.totals, hard = this.s.difficulty.filter((d) => ['AA', 'AAA'].includes(d.name)).reduce((a, d) => a + d.n, 0);
            if (t.games && hard / t.games >= 0.3) return '🧗 Caçador de desafios';
            if (t.avg_minutes >= 40 * 60) return '🏔 Maratonista de épicos';
            if (t.avg_minutes && t.avg_minutes <= 8 * 60) return '⚡ Velocista de campanhas';
            if (this.s.platforms.length >= 8) return '🕹 Colecionador de consoles';
            if (t.avg_rating >= 8) return '💛 Coração generoso';
            return '🎮 Explorador versátil';
        },
        shareCard() {
            const t = this.s.totals;
            ShareCard.download({ kicker: 'Estatísticas', title: 'Minha ficha de jogador', sub: this.archetype, file: 'estatisticas',
                stats: [[nf(t.games), 'jogos zerados'], [nf(t.avg_rating, 1), 'nota média'], [nf(t.platforms), 'sistemas'], [nf(t.genres), 'gêneros']] });
        },
        get cards() {
            const t = this.s.totals;
            return [
                { l: 'XP total', v: nf(t.xp), h: nf(t.tasks) + ' tarefas extras' },
                { l: 'Sistemas', v: nf(t.platforms), h: 'diferentes' },
                { l: 'Gêneros', v: nf(t.genres), h: 'diferentes' },
                { l: 'Jogos por ano', v: nf(t.games / Math.max(1, this.s.finish_years.length), 1), h: 'em média, nos ' + this.s.finish_years.length + ' anos com registro' },
            ];
        },
        // hábitos
        get weekMax() { return Math.max(1, ...this.s.weekdays.map((w) => w.n)); },
        get bestWeekday() {
            const w = [...this.s.weekdays].sort((a, b) => b.n - a.n)[0];
            return { label: w.label, prep: /^(sábado|domingo)/i.test(w.label) ? 'no' : 'na' };
        },
        get weekendShare() {
            const total = this.s.weekdays.reduce((a, w) => a + w.n, 0);
            const we = this.s.weekdays.filter((w) => /^(sábado|domingo)/i.test(w.label)).reduce((a, w) => a + w.n, 0);
            return total ? Math.round(we / total * 100) : 0;
        },
        get yearMax() { return Math.max(1, ...this.s.finish_years.map((y) => y.minutes)); },
        get bestYear() { return [...this.s.finish_years].sort((a, b) => b.n - a.n)[0] ?? null; },
        get decadeMax() { return Math.max(1, ...this.s.decades.map((d) => d.n)); },
        decadeHue(name) { const y = parseInt(name, 10) || 2000; return (200 + (y - 1970) * 3) % 360; },
        // gostos
        get ratingMax() { return Math.max(1, ...this.s.rating_dist.map((r) => r.n)); },
        get modeRating() { return [...this.s.rating_dist].sort((a, b) => b.n - a.n)[0]?.rating ?? '—'; },
        get generosity() {
            const r = this.s.totals.avg_rating;
            return r >= 8.5 ? 'você é generoso nas notas' : r >= 6.5 ? 'notas equilibradas' : 'um crítico exigente';
        },
        get diffMax() { return Math.max(1, ...this.s.difficulty.map((d) => d.n)); },
        get loveHate() {
            const pool = this.s.genres.filter((g) => g.n >= 3);
            return [
                { t: 'Mais jogados', rows: [...pool].sort((a, b) => b.n - a.n).slice(0, 5) },
                { t: 'Mais bem avaliados', rows: [...pool].sort((a, b) => b.avg_rating - a.avg_rating).slice(0, 5) },
            ];
        },
    };
}
</script>
