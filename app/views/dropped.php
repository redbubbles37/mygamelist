<?php
/** @var array|null $me */
/** @var array|null $demo  {data, counts} fictícios da página de exemplo (nulo na lista real) */
$demo = $demo ?? null;
?>
<main class="page page-wide" x-data='dropped(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com <?= count($demo['data']) ?> jogos fictícios. Dá para filtrar, ordenar e mover os jogos entre as chances de voltar; nada é salvo.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <!-- ========== Destaque: a sala de espera ========== -->
    <section class="hero-box text-white" aria-label="Resumo dos dropados">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="spot?.cover_url" :src="spot?.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl grayscale-[.4]">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/70 to-rose-900/25"></div>
        </div>
        <div class="grid lg:grid-cols-[1.1fr_1fr]">
            <div class="p-5 sm:p-8">
                <p class="kicker-hero">Largados no meio do caminho</p>
                <h1 class="mt-1 t-hero">Jogos dropados</h1>
                <p class="mt-2 max-w-md text-sm text-white/65">A sala de espera dos jogos que você começou e deixou de lado. Diga a chance de voltar e decida quem sai daqui primeiro.</p>

                <div x-show="!ready" class="mt-6 h-24 animate-pulse rounded-2xl bg-white/5"></div>
                <dl x-show="ready && list.length" x-cloak class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4">
                    <div><dt class="text-xs text-white/55">Na espera</dt><dd class="text-3xl font-black tabular-nums" x-text="list.length"></dd></div>
                    <div><dt class="text-xs text-white/55">Horas investidas</dt><dd class="text-3xl font-black tabular-nums" x-text="fmt(totalHours, 0) + 'h'"></dd></div>
                    <div><dt class="text-xs text-white/55">Média antes de largar</dt><dd class="text-3xl font-black tabular-nums" x-text="avgLabel"></dd></div>
                    <div><dt class="text-xs text-white/55">Esperando há mais tempo</dt><dd class="truncate text-base font-bold" :title="oldest?.title" x-text="oldest ? ago(oldest.dropped_at) : '—'"></dd><dd class="truncate text-xs text-white/55" x-text="oldest?.title"></dd></div>
                </dl>

                <!-- chance de voltar: barra + filtro -->
                <div x-show="ready && list.length" x-cloak class="mt-6">
                    <div class="flex h-3 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                        <template x-for="c in chances" :key="c.v">
                            <span :style="UI.chanceStyle(c.v) + ';width:' + (list.length ? counts[c.v] / list.length * 100 : 0) + '%'"></span>
                        </template>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="Filtrar pela chance de voltar">
                        <template x-for="c in chances" :key="c.v">
                            <button type="button" @click="chance = chance === c.v ? '' : c.v" :aria-pressed="chance === c.v" class="flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold transition"
                                    :class="chance === c.v ? 'border-white bg-white/15' : 'border-white/15 hover:border-white/40'">
                                <span class="h-2.5 w-2.5 rounded-full" :style="UI.chanceStyle(c.v)"></span>
                                <span x-text="'Chance ' + c.l.toLowerCase() + ' · ' + counts[c.v]"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <?php if ($demo === null): ?>
                <p class="mt-5 text-xs text-white/50">Para dropar um jogo, abra o <a href="<?= e(url('/backlog')) ?>" class="font-semibold text-white/80 underline hover:text-white">backlog</a> e escolha <em>Dropei</em> nele.</p>
                <?php endif; ?>
            </div>

            <!-- o mais perto de voltar -->
            <div x-show="spot" x-cloak class="relative flex min-h-[16rem] items-end overflow-hidden border-t border-white/10 lg:border-l lg:border-t-0">
                <img x-show="spot?.cover_url" :src="spot?.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover opacity-60">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/70 to-black/20"></div>
                <div class="relative w-full p-5 sm:p-7">
                    <span class="rounded-full px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider" :style="UI.chanceStyle(spot?.return_chance)" x-text="spot?.return_chance === 'high' ? 'Mais perto de voltar' : 'Na fila de espera'"></span>
                    <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl" x-text="spot?.title"></h2>
                    <p class="mt-1 text-sm text-white/70" x-text="spot ? spot.platform + ' · parou com ' + (spot.playtime_minutes ? UI.fmtTime(spot.playtime_minutes) : 'pouco tempo') + ' · ' + ago(spot.dropped_at) : ''"></p>
                    <p x-show="spot?.reason" class="mt-3 line-clamp-2 border-l-2 border-white/30 pl-3 text-sm italic text-white/80" x-text="spot?.reason ? '“' + spot.reason + '”' : ''"></p>
                    <?php if ($demo === null): ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" @click="spot && resume(spot)" :disabled="spot?.busy" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black transition hover:bg-white/85 disabled:opacity-60">▶ Voltei a jogar</button>
                        <a :href="spot ? resumeUrl(spot) : null" class="rounded-xl border border-white/30 bg-black/40 px-4 py-2 text-sm font-semibold backdrop-blur transition hover:bg-white/10">Zerei</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <p x-show="notice" x-cloak role="status" class="mt-6 rounded-lg border border-ok/30 bg-ok/10 px-4 py-3 text-sm text-ok"><span x-text="notice"></span> <a href="<?= e(url('/backlog')) ?>" class="ml-1 font-semibold underline">Ver o backlog</a></p>
    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>
    <div x-show="!ready && !error" class="mt-6 grid animate-pulse gap-5 md:grid-cols-3" aria-label="Carregando"><template x-for="i in 3"><div class="h-72 rounded-2xl bg-slate-50/5"></div></template></div>

    <section x-show="ready && list.length === 0 && !error" x-cloak class="mt-6 empty">
        <p class="t-section">Ninguém na sala de espera</p>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Começou um jogo e largou no meio? No backlog, escolha <em>Dropei</em> nele: o jogo vem para cá com a capa, você diz a chance de voltar e ele fica esperando.</p>
        <?php if ($demo === null): ?><a href="<?= e(url('/backlog')) ?>" class="btn mt-4 inline-block">Abrir o backlog</a><?php endif; ?>
    </section>

    <template x-if="ready && list.length">
        <div>
            <!-- padrões: onde você mais larga -->
            <section x-show="list.length >= 3" class="mt-6 grid gap-3 sm:grid-cols-3" aria-label="Padrões">
                <div class="panel p-4">
                    <p class="kicker">Gênero que você mais larga</p>
                    <p class="mt-1 truncate text-lg font-bold text-slate-50" x-text="topGenre ? topGenre.k : '—'"></p>
                    <p class="text-xs text-slate-400" x-text="topGenre ? topGenre.n + ' de ' + list.length + ' dropados' : ''"></p>
                </div>
                <div class="panel p-4">
                    <p class="kicker">Sistema com mais dropados</p>
                    <p class="mt-1 truncate text-lg font-bold text-slate-50" x-text="topPlatform ? topPlatform.k : '—'"></p>
                    <p class="text-xs text-slate-400" x-text="topPlatform ? topPlatform.n + ' de ' + list.length + ' dropados' : ''"></p>
                </div>
                <div class="panel p-4">
                    <p class="kicker">Mais horas antes de largar</p>
                    <p class="mt-1 truncate text-lg font-bold text-slate-50" x-text="longest ? longest.title : '—'"></p>
                    <p class="text-xs text-slate-400" x-text="longest ? UI.fmtTime(longest.playtime_minutes) + ' jogadas' : ''"></p>
                </div>
            </section>

            <!-- barra de ferramentas -->
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <label class="relative min-w-0 flex-1 sm:max-w-xs">
                    <span class="sr-only">Buscar</span>
                    <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                    <input type="search" x-model="q" placeholder="Buscar por título…" class="w-full rounded-lg border border-slate-50/10 bg-slate-900/70 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-brand-500">
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-400">Ordenar
                    <select x-model="sort" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="recent">Dropados recentemente</option><option value="old">Esperando há mais tempo</option>
                        <option value="chance">Maior chance de voltar</option><option value="hours">Mais horas jogadas</option><option value="title">Título A–Z</option>
                    </select>
                </label>
                <button type="button" x-show="chance || q" x-cloak @click="chance = ''; q = ''" class="text-sm font-medium text-brand-400 hover:text-brand-500">Limpar filtros</button>
                <div class="ml-auto seg" role="group" aria-label="Exibição">
                    <button type="button" @click="setView('board')" :aria-pressed="view === 'board'" class="seg-btn" :class="view === 'board' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Quadro</button>
                    <button type="button" @click="setView('shelf')" :aria-pressed="view === 'shelf'" class="seg-btn" :class="view === 'shelf' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Estante</button>
                </div>
            </div>

            <p x-show="shown.length === 0" x-cloak class="mt-10 text-center text-slate-400">Nenhum jogo com esses filtros.</p>

            <!-- ===== Quadro: uma coluna por chance de voltar ===== -->
            <div x-show="view === 'board' && shown.length" class="mt-6 grid gap-5 md:grid-cols-3">
                <template x-for="c in chances" :key="c.v">
                    <section x-show="!chance || chance === c.v" class="min-w-0 rounded-2xl border border-slate-50/10 bg-slate-900/40 p-3" :aria-label="'Chance ' + c.l.toLowerCase()">
                        <header class="flex items-center justify-between gap-2 px-1 pb-3">
                            <h2 class="flex items-center gap-2 font-bold text-slate-50"><span class="h-3 w-3 rounded-full" :style="UI.chanceStyle(c.v)"></span><span x-text="'Chance ' + c.l.toLowerCase()"></span></h2>
                            <span class="text-xs text-slate-500" x-text="c.hint + ' · ' + column(c.v).length"></span>
                        </header>
                        <p x-show="!column(c.v).length" class="rounded-xl border border-dashed border-slate-50/10 px-3 py-6 text-center text-xs text-slate-500">Nenhum jogo aqui.</p>
                        <ul class="space-y-3">
                            <template x-for="g in column(c.v)" :key="g.id">
                                <li class="rounded-xl border border-slate-50/10 bg-slate-900/80 p-3 transition" :class="g.busy ? 'opacity-60' : ''">
                                    <div class="flex gap-3">
                                        <span class="relative h-20 w-14 shrink-0 overflow-hidden rounded-md bg-slate-950 shadow">
                                            <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover saturate-[.8]">
                                            <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center text-lg font-bold text-slate-500" x-text="UI.initials(g.title)"></span>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-bold text-slate-50" :title="g.title" x-text="g.title"></p>
                                            <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-slate-400">
                                                <span class="rounded px-1.5 py-0.5 text-2xs font-bold" :style="bandChip(g.platform)" x-text="g.platform"></span>
                                                <span x-show="g.playtime_minutes" x-text="'⏱ ' + UI.fmtTime(g.playtime_minutes)"></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500" :title="'Dropado em ' + UI.fmtDate(g.dropped_at)" x-text="'parado ' + ago(g.dropped_at)"></p>
                                        </div>
                                    </div>
                                    <p x-show="g.reason" class="mt-2 line-clamp-2 text-sm italic text-slate-300" :title="g.reason" x-text="'“' + g.reason + '”'"></p>
                                    <div class="mt-3 flex items-center gap-1.5">
                                        <?php if ($demo === null): ?>
                                        <button type="button" @click="resume(g)" :disabled="g.busy" class="rounded-lg bg-ok/15 px-2.5 py-1.5 text-xs font-bold text-ok transition hover:bg-ok/25 disabled:opacity-60">▶ Voltei</button>
                                        <?php endif; ?>
                                        <span class="flex-1"></span>
                                        <button type="button" @click="shift(g, 1)" x-show="c.v !== 'high'" :disabled="g.busy" class="icon-btn !h-8 !w-8" :aria-label="'Subir a chance de ' + g.title" title="Subir a chance">↑</button>
                                        <button type="button" @click="shift(g, -1)" x-show="c.v !== 'low'" :disabled="g.busy" class="icon-btn !h-8 !w-8" :aria-label="'Baixar a chance de ' + g.title" title="Baixar a chance">↓</button>
                                        <?php if ($demo === null): ?>
                                        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                            <button type="button" @click="open = !open" :aria-expanded="open" class="icon-btn !h-8 !w-8" :aria-label="'Mais ações para ' + g.title">⋯</button>
                                            <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 z-20 mt-1 w-48 overflow-hidden rounded-xl border border-slate-50/10 bg-slate-900 py-1 text-sm shadow-2xl">
                                                <a :href="resumeUrl(g)" class="block px-3 py-2 text-slate-200 hover:bg-slate-50/10">✓ Registrar como zerado</a>
                                                <a :href="base + '/dropados/' + g.id + '/editar'" class="block px-3 py-2 text-slate-200 hover:bg-slate-50/10">✎ Editar</a>
                                                <button type="button" x-show="confirmId !== g.id" @click="confirmId = g.id" class="block w-full px-3 py-2 text-left text-danger hover:bg-danger/10">Excluir…</button>
                                                <div x-show="confirmId === g.id" class="flex items-center gap-2 px-3 py-2">
                                                    <span class="text-slate-300">Excluir?</span>
                                                    <button type="button" @click="remove(g)" class="font-semibold text-danger hover:underline">Sim</button>
                                                    <button type="button" @click="confirmId = null" class="text-slate-400 hover:text-slate-50">Não</button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>
            </div>

            <!-- ===== Estante: as capas ===== -->
            <ul x-show="view === 'shelf' && shown.length" class="mt-6 grid grid-cols-2 gap-x-5 gap-y-9 sm:grid-cols-3 lg:grid-cols-5">
                <template x-for="g in shown" :key="g.id">
                    <li class="flex flex-col">
                        <div class="case">
                            <div class="case-band" :style="UI.bandStyle(g.platform)"><span x-text="g.platform"></span><span class="case-date" :title="'Dropado em ' + UI.fmtDate(g.dropped_at)" x-text="UI.fmtDate(g.dropped_at)"></span></div>
                            <div class="case-art">
                                <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover saturate-[.8]">
                                <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center"><span class="text-5xl font-bold text-white/80" x-text="UI.initials(g.title)"></span></div>
                                <span class="absolute left-2.5 top-2.5 rounded-lg px-2.5 py-1.5 text-xs font-black uppercase leading-none tracking-wide shadow-lg ring-1 ring-black/40" :style="UI.chanceStyle(g.return_chance)" x-text="'Volto: ' + UI.chanceLabel(g.return_chance)"></span>
                                <span class="absolute right-2.5 top-2.5 grid h-8 min-w-8 place-items-center rounded-lg bg-black/65 px-2 text-xs font-bold leading-none text-white shadow-lg ring-1 ring-black/40" x-text="g.playtime_minutes ? UI.fmtTime(g.playtime_minutes) : '—'"></span>
                                <div x-show="!g.cover_url" class="absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 px-3 pb-2.5 pt-2 backdrop-blur-md">
                                    <p class="truncate text-center text-sm font-bold leading-tight text-white" :title="g.title" x-text="g.title"></p>
                                </div>
                            </div>
                        </div>
                        <p x-show="g.reason" class="mt-2.5 line-clamp-2 text-sm italic text-slate-300" :title="g.reason" x-text="'“' + g.reason + '”'"></p>
                        <div class="mt-2.5 inline-flex rounded-lg bg-slate-50/5 p-0.5 text-xs font-bold" role="group" :aria-label="'Chance de voltar a jogar ' + g.title">
                            <template x-for="c in chances" :key="c.v">
                                <button type="button" @click="setChance(g, c.v)" :aria-pressed="g.return_chance === c.v" :disabled="g.busy" class="flex-1 rounded-md px-2 py-1.5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 disabled:opacity-60"
                                        :style="g.return_chance === c.v ? UI.chanceStyle(c.v) : ''" :class="g.return_chance === c.v ? '' : 'text-slate-400 hover:text-slate-50'" x-text="c.l"></button>
                            </template>
                        </div>
                        <?php if ($demo === null): ?>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <button type="button" @click="resume(g)" :disabled="g.busy" class="font-semibold text-ok hover:underline disabled:opacity-60">▶ Voltei</button>
                            <a :href="resumeUrl(g)" class="font-semibold text-brand-400 hover:underline">Zerei</a>
                            <a :href="base + '/dropados/' + g.id + '/editar'" class="text-slate-400 hover:text-slate-50">Editar</a>
                        </div>
                        <?php endif; ?>
                    </li>
                </template>
            </ul>
        </div>
    </template>
</main>

<script>
function dropped(demo) {
    const base = window.APP.base;
    const ORDER = { high: 3, medium: 2, low: 1 };
    const LEVELS = ['low', 'medium', 'high'];
    let savedView = 'board';
    try { savedView = localStorage.getItem('mgl.dropped.view') || 'board'; } catch (e) { /* sem storage */ }
    return {
        isDemo: !!demo, base,
        list: [], counts: { low: 0, medium: 0, high: 0 },
        ready: false, error: '', notice: '', q: '', chance: '', sort: 'recent', confirmId: null, view: savedView,
        chances: [
            { v: 'high', l: 'Alta', hint: 'Quero muito voltar' },
            { v: 'medium', l: 'Média', hint: 'Talvez, um dia' },
            { v: 'low', l: 'Baixa', hint: 'Dificilmente volto' },
        ],

        fmt: (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0),
        setView(v) { this.view = v; try { localStorage.setItem('mgl.dropped.view', v); } catch (e) { /* sem storage */ } },
        bandChip(p) { return UI.bandStyle(p) + ';background:var(--band-bg);color:var(--band-fg)'; },
        /** "há 3 meses", "há 2 anos"… a partir de uma data AAAA-MM-DD */
        ago(d) {
            if (!d) return '';
            const [y, m, dd] = d.slice(0, 10).split('-').map(Number);
            const days = Math.max(0, Math.floor((Date.now() - new Date(y, m - 1, dd)) / 864e5));
            if (days < 1) return 'hoje';
            if (days < 30) return days === 1 ? 'há 1 dia' : `há ${days} dias`;
            const months = Math.floor(days / 30.44);
            if (months < 12) return months === 1 ? 'há 1 mês' : `há ${months} meses`;
            const years = Math.floor(months / 12);
            return years === 1 ? 'há 1 ano' : `há ${years} anos`;
        },

        get totalHours() { return this.list.reduce((s, g) => s + g.playtime_minutes, 0) / 60; },
        get avgLabel() {
            const played = this.list.filter((g) => g.playtime_minutes > 0);
            if (!played.length) return '—';
            const m = Math.round(played.reduce((s, g) => s + g.playtime_minutes, 0) / played.length);
            return m >= 60 ? Math.round(m / 60) + 'h' : m + 'min';
        },
        get oldest() { return [...this.list].sort((a, b) => a.dropped_at.localeCompare(b.dropped_at))[0] ?? null; },
        get longest() { return [...this.list].filter((g) => g.playtime_minutes).sort((a, b) => b.playtime_minutes - a.playtime_minutes)[0] ?? null; },
        /** em destaque: a maior chance; empate → o dropado mais recente */
        get spot() {
            return [...this.list].sort((a, b) => ORDER[b.return_chance] - ORDER[a.return_chance] || b.dropped_at.localeCompare(a.dropped_at))[0] ?? null;
        },
        top(key) {
            const c = {};
            for (const g of this.list) if (g[key]) c[g[key]] = (c[g[key]] ?? 0) + 1;
            const best = Object.entries(c).sort((a, b) => b[1] - a[1])[0];
            return best && best[1] > 1 ? { k: best[0], n: best[1] } : null;
        },
        get topGenre() { return this.top('genre'); },
        get topPlatform() { return this.top('platform'); },

        get shown() {
            const q = this.q.trim().toLowerCase();
            const rows = this.list.filter((g) => (!this.chance || g.return_chance === this.chance) && (!q || g.title.toLowerCase().includes(q)));
            const cmp = {
                recent: (a, b) => b.dropped_at.localeCompare(a.dropped_at) || b.id - a.id,
                old: (a, b) => a.dropped_at.localeCompare(b.dropped_at) || a.id - b.id,
                chance: (a, b) => ORDER[b.return_chance] - ORDER[a.return_chance] || b.dropped_at.localeCompare(a.dropped_at),
                hours: (a, b) => b.playtime_minutes - a.playtime_minutes,
                title: (a, b) => a.title.localeCompare(b.title, 'pt', { sensitivity: 'base' }),
            }[this.sort];
            return [...rows].sort(cmp);
        },
        column(v) { return this.shown.filter((g) => g.return_chance === v); },

        /** Abre o cadastro de jogo já preenchido; ao salvar, o jogo sai desta lista. */
        resumeUrl(g) {
            const p = new URLSearchParams({ title: g.title, platform: g.platform, from_dropped: g.id });
            if (g.genre) p.set('genre', g.genre);
            if (g.release_year) p.set('release_year', g.release_year);
            return `${base}/jogos/novo?${p}`;
        },

        recount() { const c = { low: 0, medium: 0, high: 0 }; for (const g of this.list) c[g.return_chance]++; this.counts = c; },

        async init() { await this.load(); },
        async load() {
            this.error = '';
            try {
                const r = demo ?? await api.get('/api/dropped');
                this.list = r.data.map((g) => ({ ...g, busy: false }));
                this.recount();
                this.ready = true;
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },
        /** sobe (+1) ou desce (-1) a chance de voltar: move o jogo de coluna */
        shift(g, d) {
            const i = LEVELS.indexOf(g.return_chance) + d;
            if (i >= 0 && i < LEVELS.length) this.setChance(g, LEVELS[i]);
        },
        async setChance(g, v) {
            if (g.return_chance === v) return;
            const old = g.return_chance;
            g.return_chance = v; this.recount();
            if (demo) return;
            g.busy = true;
            try { await api.put(`/api/dropped/${g.id}/chance`, { return_chance: v }); }
            catch (e) { g.return_chance = old; this.recount(); this.error = e.message; }
            finally { g.busy = false; }
        },
        /** "Voltei a jogar": o jogo sai dos dropados e vai para "jogando agora" no backlog. */
        async resume(g) {
            this.error = ''; this.notice = '';
            g.busy = true;
            try {
                await api.post(`/api/dropped/${g.id}/resume`, {});
                this.list = this.list.filter((x) => x.id !== g.id); this.recount();
                this.notice = `"${g.title}" voltou para "jogando agora".`;
            } catch (e) { this.error = e.message; g.busy = false; }
        },
        async remove(g) {
            try { await api.del('/api/dropped/' + g.id); this.list = this.list.filter((x) => x.id !== g.id); this.recount(); this.confirmId = null; }
            catch (e) { this.error = e.message; }
        },
    };
}
</script>
