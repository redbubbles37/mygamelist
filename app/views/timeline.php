<?php
/** @var array|null $me */
/** @var array|null $demo  jogos fictícios da página de exemplo (nulo na timeline real) */
$demo = $demo ?? null;
?>
<main class="page page-wide" x-data='timeline(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com <?= count($demo) ?> jogos fictícios. Alterne entre vertical e horizontal, filtre por ano ou nota e procure os marcos dourados.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-400">Timeline</p>
            <h1 class="mt-1 t-page">Toda a sua história, em ordem</h1>
            <p class="mt-1 text-sm text-slate-400" aria-live="polite">
                <template x-if="ready && all.length"><span>
                    <span x-text="count"></span> <span x-text="count === 1 ? 'jogo' : 'jogos'"></span> · <span x-text="fmt(hours, 0)"></span> h
                    <span x-show="count !== all.length"> (de <span x-text="all.length"></span>)</span>
                    · de <span x-text="rangeLabel"></span>
                </span></template>
            </p>
        </div>

        <!-- Modo de visualização -->
        <div class="inline-flex rounded-xl bg-slate-50/5 p-1 text-sm font-semibold" role="group" aria-label="Modo de visualização">
            <button type="button" @click="setMode('vertical')" :aria-pressed="mode === 'vertical'" class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    :class="mode === 'vertical' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 3v18M12 7h6M12 12H6M12 17h6"/></svg>
                Vertical
            </button>
            <button type="button" @click="setMode('horizontal')" :aria-pressed="mode === 'horizontal'" class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    :class="mode === 'horizontal' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 12h18M7 12V6M12 12v6M17 12V6"/></svg>
                Horizontal
            </button>
            <button type="button" @click="setMode('mural')" :aria-pressed="mode === 'mural'" class="flex items-center gap-2 rounded-lg px-3.5 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    :class="mode === 'mural' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 4h5v7H4zM10 4h5v7h-5zM16 4h4v7h-4zM4 13h5v7H4zM10 13h5v7h-5zM16 13h4v7h-4z"/></svg>
                Mural
            </button>
        </div>
    </header>

    <!-- Destaque: o minimapa (cada barra é um mês; clique para ir até ele) -->
    <section x-show="ready && all.length" x-cloak class="relative isolate mt-6 overflow-hidden rounded-3xl border border-slate-50/10 bg-hero p-5 text-white sm:p-7" aria-labelledby="h-map">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="all[all.length - 1]?.cover_url" :src="all[all.length - 1]?.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/65 to-brand-700/25"></div>
        </div>
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 id="h-map" class="kicker-hero">Mapa da sua história</h2>
            <p class="text-xs text-white/55" x-text="minimap.months.length + ' meses · mês mais cheio: ' + minimap.peakLabel"></p>
        </div>
        <div class="mt-4 flex h-24 items-end gap-px" role="img" :aria-label="'Jogos por mês, de ' + rangeLabel">
            <template x-for="m in minimap.months" :key="m.key">
                <button type="button" @click="jumpMonth(m)" :disabled="!m.games" class="group relative flex h-full flex-1 items-end focus:outline-none disabled:cursor-default" :title="m.label + ': ' + m.games + (m.games === 1 ? ' jogo' : ' jogos')">
                    <span class="block w-full rounded-t-sm transition group-hover:bg-brand-300 group-focus-visible:bg-brand-300"
                          :class="m.games ? (m.start ? 'bg-brand-400' : 'bg-brand-500/80') : 'bg-white/[.07]'" :style="'height:' + (m.games ? Math.max(8, m.games / minimap.max * 100) : 3) + '%'"></span>
                </button>
            </template>
        </div>
        <!-- anos embaixo do minimapa -->
        <div class="mt-1 flex text-2xs font-bold tabular-nums text-white/50">
            <template x-for="y in minimap.years" :key="y.year">
                <span class="truncate border-l border-white/15 pl-1" :style="'flex:' + y.months" x-text="y.year"></span>
            </template>
        </div>
        <!-- as eras: a história dividida pelas pausas longas -->
        <div x-show="eras.length > 1" class="mt-5">
            <div class="flex items-baseline justify-between gap-2">
                <p class="kicker-hero">Suas eras</p>
                <button type="button" x-show="era !== null" @click="era = null" class="text-xs font-semibold text-white/70 underline hover:text-white">Ver todas</button>
            </div>
            <div class="mt-2 flex gap-1 overflow-x-auto pb-1">
                <template x-for="(e, i) in eras" :key="e.start">
                    <button type="button" @click="era = era === i ? null : i" :aria-pressed="era === i" class="min-w-[7.5rem] rounded-xl border px-3 py-2 text-left transition"
                            :class="era === i ? 'border-white bg-white/15' : 'border-white/10 hover:border-white/40'" :style="'flex:' + e.games.length + ' 1 0;background-image:linear-gradient(135deg,hsl(' + e.hue + ' 70% 45% / .35),transparent)'">
                        <span class="block truncate text-sm font-bold" x-text="e.name"></span>
                        <span class="block truncate text-2xs text-white/60" x-text="e.label + ' · ' + e.games.length + (e.games.length === 1 ? ' jogo' : ' jogos')"></span>
                    </button>
                </template>
            </div>
        </div>
        <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-white/10 pt-4 text-sm sm:grid-cols-4">
            <div><dt class="text-xs text-white/55">Primeiro registro</dt><dd class="truncate font-bold" x-text="all[0]?.title"></dd><dd class="text-xs text-white/55" x-text="all[0] ? UI.fmtDate(all[0].finished_at) : ''"></dd></div>
            <div><dt class="text-xs text-white/55">Mais recente</dt><dd class="truncate font-bold" x-text="all[all.length - 1]?.title"></dd><dd class="text-xs text-white/55" x-text="all.length ? UI.fmtDate(all[all.length - 1].finished_at) : ''"></dd></div>
            <div><dt class="text-xs text-white/55">Meses com jogo</dt><dd class="font-bold" x-text="minimap.active + ' de ' + minimap.months.length"></dd></div>
            <div><dt class="text-xs text-white/55">Maior jejum</dt><dd class="font-bold" x-text="minimap.gap ? minimap.gap + (minimap.gap === 1 ? ' mês' : ' meses') + ' sem zerar' : 'nenhum'"></dd></div>
        </dl>
    </section>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="init()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <div x-show="!ready && !error" class="mt-8 animate-pulse space-y-3" aria-label="Carregando">
        <template x-for="i in 5"><div class="h-24 rounded-2xl bg-slate-50/5"></div></template>
    </div>

    <section x-show="ready && all.length === 0" x-cloak class="mt-8 empty">
        <p class="t-section">Sua timeline está vazia</p>
        <p class="mt-1 text-sm text-slate-400">Cada jogo que você zerar entra aqui, em ordem de data.</p>
        <a href="<?= e(url('/jogos/novo')) ?>" class="btn mt-4 inline-block">Registrar um jogo</a>
    </section>

    <template x-if="ready && all.length">
        <div>
            <!-- Filtros -->
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <label class="relative min-w-[12rem] flex-1 sm:max-w-xs">
                    <span class="sr-only">Buscar na timeline</span>
                    <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                    <input type="search" x-model="q" placeholder="Buscar por título…" class="w-full rounded-lg border border-slate-50/10 bg-slate-900/70 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-brand-500">
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-400">Nota
                    <select x-model.number="minRating" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="0">Todas</option><option value="8">8 ou mais</option><option value="9">9 ou mais</option><option value="10">10 ou mais</option><option value="11">Só 11</option>
                    </select>
                </label>
                <button type="button" @click="toggleOrder()" class="btn-ghost flex items-center gap-2">
                    <svg class="h-4 w-4 transition" :class="order === 'asc' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"/></svg>
                    <span x-text="order === 'desc' ? 'Mais recentes primeiro' : 'Mais antigos primeiro'"></span>
                </button>
                <button type="button" x-show="hasFilters" x-cloak @click="clear()" class="text-sm font-medium text-brand-400 hover:text-brand-500">Limpar filtros</button>
            </div>

            <!-- Anos (filtro rápido) -->
            <div class="mt-3 flex items-center gap-1.5 overflow-x-auto py-1 [scrollbar-width:thin]" role="group" aria-label="Filtrar por ano">
                <button type="button" @click="pickYear('')" :aria-pressed="year === ''" class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                        :class="year === '' ? 'bg-brand-600 text-on-brand' : 'bg-slate-50/5 text-slate-300 hover:text-slate-50'">Todos</button>
                <template x-for="y in allYears" :key="y">
                    <button type="button" @click="pickYear(y)" :aria-pressed="year === y" class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-bold tabular-nums transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                            :class="year === y ? 'bg-brand-600 text-on-brand' : 'bg-slate-50/5 text-slate-300 hover:text-slate-50'" x-text="y"></button>
                </template>
            </div>

            <p x-show="count === 0" x-cloak class="mt-10 text-center text-slate-400">Nenhum jogo com esses filtros.</p>

            <!-- ============ VERTICAL ============ -->
            <div x-show="mode === 'vertical' && count" x-cloak class="mt-8">
                <ol class="relative border-l-2 border-slate-50/15 pl-5 sm:pl-8">
                    <template x-for="yg in shown" :key="yg.year">
                        <li :id="'y-' + yg.year" class="pb-8">
                            <!-- ano -->
                            <div class="sticky top-0 z-10 -mb-1 flex items-center gap-3 bg-slate-950/85 py-2.5 backdrop-blur">
                                <span class="absolute -left-[1.92rem] grid h-5 w-5 place-items-center rounded-full bg-brand-600 ring-4 ring-slate-950 sm:-left-[2.67rem]" aria-hidden="true"></span>
                                <h2 class="text-3xl font-black tabular-nums tracking-tight text-slate-50 sm:text-4xl" x-text="yg.year"></h2>
                                <span class="text-sm text-slate-400"><span x-text="yg.count"></span> <span x-text="yg.count === 1 ? 'jogo' : 'jogos'"></span> · <span x-text="fmt(yg.hours, 0)"></span> h</span>
                            </div>

                            <template x-for="mg in yg.months" :key="mg.key">
                                <div class="mt-5 scroll-mt-20" :id="'m-' + mg.key">
                                    <h3 class="flex items-baseline gap-2 text-sm font-bold uppercase tracking-wider text-slate-400">
                                        <span x-text="mg.label"></span>
                                        <span class="text-xs font-medium normal-case tracking-normal text-slate-500"><span x-text="mg.games.length"></span> <span x-text="mg.games.length === 1 ? 'jogo' : 'jogos'"></span> · <span x-text="fmt(mg.hours, 0)"></span> h</span>
                                    </h3>
                                    <ul class="mt-3 space-y-3">
                                        <template x-for="g in mg.games" :key="g.id">
                                            <li class="relative grid grid-cols-1 items-start gap-3 sm:grid-cols-[3.75rem_minmax(0,1fr)]">
                                                <span class="absolute -left-[1.66rem] top-7 h-3 w-3 rounded-full ring-4 ring-slate-950 sm:-left-[2.41rem]" :class="g.milestone ? 'rating-gold !h-4 !w-4 -translate-x-[2px] -translate-y-[2px]' : 'bg-brand-500'" aria-hidden="true"></span>
                                                <!-- data -->
                                                <div class="hidden pt-3 text-center sm:block">
                                                    <p class="text-2xl font-black tabular-nums leading-none text-slate-50" x-text="g.finished_at.slice(8)"></p>
                                                    <p class="kicker mt-0.5" x-text="monthShort(g.finished_at)"></p>
                                                </div>
                                                <!-- cartão -->
                                                <a :href="isDemo ? null : base + '/jogos/' + g.id"
                                                   class="group flex min-w-0 gap-3 rounded-2xl border bg-slate-900/70 p-3 transition hover:border-brand-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                                   :style="g.milestone ? 'border-color:#fbbf24' : ''" :class="[isDemo ? 'cursor-default' : '', g.milestone ? '' : 'border-slate-50/10']">
                                                    <div class="relative h-24 w-[3.75rem] shrink-0 overflow-hidden rounded-lg ring-1 ring-black/40">
                                                        <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                                        <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60"><span class="text-lg font-bold text-white/85" x-text="UI.initials(g.title)"></span></div>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p x-show="g.milestone" class="mb-1 inline-block rounded-md px-2 py-0.5 text-2xs font-black uppercase tracking-wider rating-gold text-[#3b1d00]" x-text="'★ ' + g.milestone"></p>
                                                        <p class="truncate text-base font-bold text-slate-50 group-hover:text-brand-400" :title="g.title" x-text="g.title"></p>
                                                        <p class="truncate text-sm text-slate-400"><span x-text="g.platform"></span><span x-show="g.genre"> · <span x-text="g.genre"></span></span></p>
                                                        <p class="mt-1 text-xs text-slate-500"><span class="sm:hidden" x-text="UI.fmtDate(g.finished_at) + ' · '"></span><span x-text="UI.fmtTime(g.playtime_minutes)"></span><span x-show="g.release_year"> · lançado em <span x-text="g.release_year"></span></span></p>
                                                    </div>
                                                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                                                        <span class="grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow ring-1 ring-black/30" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" :title="'Nota ' + g.rating + ' de 11'" x-text="g.rating"></span>
                                                        <span class="grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow ring-1 ring-black/30" :style="UI.diffStyle(g.difficulty)" :title="'Dificuldade ' + g.difficulty" x-text="g.difficulty"></span>
                                                    </div>
                                                </a>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </li>
                    </template>
                </ol>
                <div x-show="hiddenOlder > 0" x-cloak class="pl-5 sm:pl-8">
                    <button type="button" @click="loadOlder()" class="btn-ghost">
                        Mostrar <span x-text="order === 'desc' ? 'anos mais antigos' : 'anos mais recentes'"></span> (<span x-text="hiddenOlder"></span> <span x-text="hiddenOlder === 1 ? 'ano' : 'anos'"></span>)
                    </button>
                </div>
            </div>

            <!-- ============ MURAL: a parede de capas, ano a ano ============ -->
            <div x-show="mode === 'mural' && count" x-cloak class="mt-8 space-y-8">
                <template x-for="yg in groups" :key="yg.year">
                    <section :aria-label="'Ano ' + yg.year">
                        <div class="flex items-baseline gap-3">
                            <h2 class="text-3xl font-black tabular-nums text-slate-50" x-text="yg.year"></h2>
                            <span class="text-sm text-slate-400" x-text="yg.count + (yg.count === 1 ? ' jogo' : ' jogos') + ' · ' + fmt(yg.hours, 0) + ' h'"></span>
                            <span class="h-px flex-1 bg-slate-50/10"></span>
                        </div>
                        <ul class="mt-3 grid grid-cols-6 gap-1.5 sm:grid-cols-10 lg:grid-cols-[repeat(16,minmax(0,1fr))]">
                            <template x-for="g in yg.months.flatMap((m) => m.games)" :key="g.id">
                                <li>
                                    <a :href="isDemo ? null : base + '/jogos/' + g.id" class="group relative block aspect-[3/4] overflow-hidden rounded-md bg-slate-900 ring-1 transition hover:z-10 hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:hover:scale-100"
                                       :class="g.milestone ? 'ring-2 ring-gold-400' : 'ring-black/30'" :title="g.title + ' · ' + UI.fmtDate(g.finished_at) + ' · nota ' + g.rating">
                                        <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                        <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-2xs font-bold text-white/85" x-text="UI.initials(g.title)"></span>
                                        <span class="absolute bottom-0.5 right-0.5 rounded px-1 text-[9px] font-black leading-tight opacity-0 transition group-hover:opacity-100" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>
            </div>

            <!-- ============ HORIZONTAL ============ -->
            <div x-show="mode === 'horizontal' && count" x-cloak class="mt-6">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <button type="button" x-show="hiddenOlder > 0" x-cloak @click="loadOlder()" class="btn-ghost">
                        + <span x-text="order === 'desc' ? 'Anos mais antigos' : 'Anos mais recentes'"></span> (<span x-text="hiddenOlder"></span>)
                    </button>
                    <span x-show="!(hiddenOlder > 0)" class="text-xs text-slate-500">Arraste, use a rolagem ou as setas ← →</span>
                    <div class="flex gap-1.5">
                        <button type="button" @click="scrollH(-1)" class="btn-ghost" aria-label="Rolar para a esquerda">←</button>
                        <button type="button" @click="scrollH(1)" class="btn-ghost" aria-label="Rolar para a direita">→</button>
                    </div>
                </div>

                <div x-ref="hs" class="overflow-x-auto rounded-2xl border border-slate-50/10 bg-slate-900/40 pb-4 [scrollbar-width:thin]" tabindex="0" aria-label="Linha do tempo horizontal">
                    <div class="relative flex w-max items-start px-3 pt-3">
                        <div class="pointer-events-none absolute left-0 right-0 top-[3.6rem] h-0.5 bg-slate-50/15" aria-hidden="true"></div>
                        <template x-for="col in columns" :key="col.key">
                            <section class="relative w-[17rem] shrink-0 px-2" :aria-label="col.label">
                                <p class="h-9 text-3xl font-black tabular-nums leading-9 text-slate-50" x-text="col.showYear ? col.year : ''"></p>
                                <div class="relative flex h-7 items-center gap-2">
                                    <span class="h-3.5 w-3.5 shrink-0 rounded-full bg-brand-500 ring-4 ring-slate-900" aria-hidden="true"></span>
                                    <span class="truncate text-xs font-bold uppercase tracking-wider text-slate-400"><span x-text="col.label"></span> <span class="font-medium normal-case tracking-normal text-slate-500" x-text="'· ' + col.games.length"></span></span>
                                </div>
                                <ul class="mt-3 space-y-2.5">
                                    <template x-for="g in col.games" :key="g.id">
                                        <li>
                                            <a :href="isDemo ? null : base + '/jogos/' + g.id"
                                               class="group flex gap-2.5 rounded-xl border bg-slate-900/80 p-2.5 transition hover:border-brand-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                               :style="g.milestone ? 'border-color:#fbbf24' : ''" :class="[isDemo ? 'cursor-default' : '', g.milestone ? '' : 'border-slate-50/10']">
                                                <div class="relative h-[4.5rem] w-12 shrink-0 overflow-hidden rounded-md ring-1 ring-black/40">
                                                    <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                                    <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60"><span class="text-sm font-bold text-white/85" x-text="UI.initials(g.title)"></span></div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p x-show="g.milestone" class="mb-0.5 inline-block rounded px-1.5 py-px text-2xs font-black uppercase tracking-wider rating-gold text-[#3b1d00]" x-text="'★ ' + g.milestone"></p>
                                                    <p class="truncate text-sm font-bold leading-tight text-slate-50 group-hover:text-brand-400" :title="g.title" x-text="g.title"></p>
                                                    <p class="truncate text-xs text-slate-400" x-text="g.platform"></p>
                                                    <p class="mt-0.5 text-2xs text-slate-500"><span x-text="g.finished_at.slice(8) + ' ' + monthShort(g.finished_at)"></span> · <span x-text="UI.fmtTime(g.playtime_minutes)"></span></p>
                                                </div>
                                                <div class="flex shrink-0 flex-col items-end gap-1">
                                                    <span class="grid h-7 min-w-7 place-items-center rounded-md px-1 text-xs font-black leading-none shadow ring-1 ring-black/30" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                                                    <span class="grid h-7 min-w-7 place-items-center rounded-md px-1 text-xs font-black leading-none shadow ring-1 ring-black/30" :style="UI.diffStyle(g.difficulty)" x-text="g.difficulty"></span>
                                                </div>
                                            </a>
                                        </li>
                                    </template>
                                </ul>
                            </section>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</main>

<script>
function timeline(demo) {
    const base = window.APP.base;
    const MONTH_SHORT = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    const MONTH_LONG = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const key = (s) => String(s ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
    const get = (k) => { try { return localStorage.getItem(k); } catch { return null; } };
    const put = (k, v) => { try { localStorage.setItem(k, v); } catch { /* sem storage */ } };

    return {
        isDemo: !!demo,
        base,
        all: [],                 // todos os jogos, em ordem cronológica (do mais antigo ao mais novo)
        ready: false,
        error: '',
        mode: 'vertical',
        orders: { vertical: 'desc', horizontal: 'asc', mural: 'desc' },
        year: '', minRating: 0, q: '', era: null,
        visible: 3,              // quantos anos renderizar quando "Todos" está selecionado
        // resultado do filtro (recalculado em rebuild)
        groups: [], shown: [], columns: [], hiddenOlder: 0, count: 0, hours: 0,

        fmt: (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0),
        monthShort: (d) => MONTH_SHORT[Number(d.slice(5, 7)) - 1],
        get order() { return this.orders[this.mode]; },
        /** todos os meses do primeiro ao último registro (com os vazios), para o minimapa */
        get minimap() {
            if (!this.all.length) return { months: [], years: [], max: 1, active: 0, gap: 0, peakLabel: '' };
            const count = {};
            for (const g of this.all) { const k = g.finished_at.slice(0, 7); count[k] = (count[k] ?? 0) + 1; }
            const first = this.all[0].finished_at, last = this.all[this.all.length - 1].finished_at;
            let y = +first.slice(0, 4), m = +first.slice(5, 7);
            const ly = +last.slice(0, 4), lm = +last.slice(5, 7), months = [], years = [];
            let gap = 0, run = 0;
            while (y < ly || (y === ly && m <= lm)) {
                const k = y + '-' + String(m).padStart(2, '0'), n = count[k] ?? 0;
                months.push({ key: k, games: n, start: m === 1, label: MONTH_SHORT[m - 1] + ' ' + y });
                if (!years.length || years[years.length - 1].year !== y) years.push({ year: y, months: 0 });
                years[years.length - 1].months++;
                run = n ? 0 : run + 1; gap = Math.max(gap, run);
                if (++m > 12) { m = 1; y++; }
            }
            const peak = [...months].sort((a, b) => b.games - a.games)[0];
            return { months, years, max: peak.games || 1, active: months.filter((x) => x.games).length, gap, peakLabel: peak.label + ' (' + peak.games + ')' };
        },
        /** clique no minimapa: mostra o ano daquele mês (se estava escondido) e rola até ele */
        jumpMonth(m) {
            if (this.year && this.year !== m.key.slice(0, 4)) this.year = '';
            if (this.mode !== 'vertical') this.setMode('vertical');
            if (!this.shown.some((g) => g.year === m.key.slice(0, 4))) { this.visible = this.groups.length; this.rebuild(); }
            this.$nextTick(() => setTimeout(() => document.getElementById('m-' + m.key)?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }), 60));
        },
        get hasFilters() { return !!(this.year || this.minRating || this.q.trim() || this.era !== null); },
        /**
         * Eras: a história cortada nas pausas de mais de 60 dias sem zerar nada.
         * Cada era leva o nome do sistema mais jogado nela (e o gênero, se houver um dominante).
         */
        get eras() {
            if (!this.all.length) return [];
            const day = (s) => Date.UTC(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)) / 864e5;
            const out = [];
            let cur = [this.all[0]];
            for (let i = 1; i < this.all.length; i++) {
                const g = this.all[i];
                if (day(g.finished_at) - day(cur[cur.length - 1].finished_at) > 60) { out.push(cur); cur = []; }
                cur.push(g);
            }
            out.push(cur);
            // sem pausas longas: cada ano vira uma era
            if (out.length < 2) {
                out.length = 0;
                for (const g of this.all) { const last = out[out.length - 1]; if (last && last[0].finished_at.slice(0, 4) === g.finished_at.slice(0, 4)) last.push(g); else out.push([g]); }
            }
            const top = (list, k, min = 0.34) => { const c = {}; for (const g of list) if (g[k]) c[g[k]] = (c[g[k]] ?? 0) + 1; const b = Object.entries(c).sort((a, z) => z[1] - a[1])[0]; return b && b[1] / list.length >= min ? b[0] : null; };
            const ml = (s) => MONTH_SHORT[+s.slice(5, 7) - 1] + '/' + s.slice(2, 4);
            return out.map((games, i) => {
                const p = top(games, 'platform', 0), gn = top(games, 'genre');
                const start = games[0].finished_at, end = games[games.length - 1].finished_at;
                return {
                    games, start, end, hue: (i * 67 + 250) % 360,
                    name: p ? 'Era ' + p + (gn ? ' · ' + gn : '') : (gn ? 'Era ' + gn : 'Era ' + (i + 1)),
                    label: ml(start) === ml(end) ? ml(start) : ml(start) + ' – ' + ml(end),
                };
            });
        },
        get allYears() { return [...new Set(this.all.map((g) => g.finished_at.slice(0, 4)))].sort().reverse(); },
        get rangeLabel() {
            if (!this.all.length) return '';
            const f = this.all[0].finished_at, l = this.all[this.all.length - 1].finished_at;
            return `${UI.fmtDate(f)} até ${UI.fmtDate(l)}`;
        },

        async init() {
            this.error = '';
            const view = new URLSearchParams(location.search).get('view') || get('mgl.timeline.view');
            if (['horizontal', 'vertical', 'mural'].includes(view)) this.mode = view;
            try {
                const rows = demo ?? (await api.get('/api/timeline')).data;
                this.all = [...rows].sort((a, b) => a.finished_at.localeCompare(b.finished_at) || a.id - b.id)
                    .map((g, i) => {
                        const n = i + 1;
                        return { ...g, n, milestone: n === 1 ? 'Primeiro jogo' : n % 25 === 0 ? `${n}º jogo` : null };
                    });
                this.rebuild();
                this.ready = true;
                for (const p of ['year', 'minRating', 'q', 'visible', 'era']) this.$watch(p, () => this.rebuild());
                this.$nextTick(() => this.positionScroll());
                window.addEventListener('keydown', (e) => this.onKey(e));
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },

        // ---------- controles ----------
        setMode(m) {
            this.mode = m;
            put('mgl.timeline.view', m);
            const p = new URLSearchParams(location.search);
            p.set('view', m);
            history.replaceState(null, '', location.pathname + '?' + p);
            this.rebuild();
            this.$nextTick(() => this.positionScroll());
        },
        toggleOrder() {
            this.orders[this.mode] = this.order === 'desc' ? 'asc' : 'desc';
            this.rebuild();
            this.$nextTick(() => this.positionScroll());
        },
        pickYear(y) { this.year = y; this.visible = 3; this.$nextTick(() => this.positionScroll()); },
        clear() { this.q = ''; this.minRating = 0; this.year = ''; this.visible = 3; this.era = null; },
        loadOlder() {
            const hs = this.$refs.hs, before = hs ? hs.scrollWidth : 0;
            this.visible += 3;
            this.rebuild();
            // no modo horizontal "mais antigos" entram à esquerda (ordem crescente): mantém o que você estava vendo
            this.$nextTick(() => { if (hs && this.mode === 'horizontal' && this.order === 'asc') hs.scrollLeft += hs.scrollWidth - before; });
        },
        /** Horizontal: abre no lado mais recente (crescente => fim; decrescente => começo). */
        positionScroll() {
            const hs = this.$refs.hs;
            if (this.mode === 'horizontal' && hs) hs.scrollLeft = this.order === 'asc' ? hs.scrollWidth : 0;
        },
        scrollH(dir) { const hs = this.$refs.hs; hs?.scrollBy({ left: dir * hs.clientWidth * 0.8, behavior: 'smooth' }); },
        onKey(e) {
            const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName);
            if (typing || e.ctrlKey || e.metaKey || e.altKey || this.mode !== 'horizontal' || document.body.classList.contains('overflow-hidden')) return;
            if (e.key === 'ArrowLeft') { e.preventDefault(); this.scrollH(-1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); this.scrollH(1); }
        },

        // ---------- filtro + agrupamento (ano > mês > jogos) ----------
        rebuild() {
            const q = key(this.q), min = Number(this.minRating) || 0;
            const er = this.era !== null ? this.eras[this.era] : null;
            const list = this.all.filter((g) => (!this.year || g.finished_at.startsWith(this.year)) && g.rating >= min && (!q || key(g.title).includes(q))
                && (!er || (g.finished_at >= er.start && g.finished_at <= er.end)));
            this.count = list.length;
            this.hours = list.reduce((s, g) => s + g.playtime_minutes, 0) / 60;

            const desc = this.order === 'desc';
            const sign = desc ? -1 : 1;
            const years = new Map();
            for (const g of list) { // list já está em ordem crescente
                const y = g.finished_at.slice(0, 4), m = g.finished_at.slice(0, 7);
                if (!years.has(y)) years.set(y, new Map());
                const months = years.get(y);
                if (!months.has(m)) months.set(m, []);
                months.get(m).push(g);
            }
            const groups = [...years].map(([year, months]) => {
                const ms = [...months].map(([k, games]) => ({
                    key: k,
                    label: MONTH_LONG[Number(k.slice(5)) - 1] + ' ' + year,
                    hours: games.reduce((s, g) => s + g.playtime_minutes, 0) / 60,
                    games: desc ? [...games].reverse() : games,
                })).sort((a, b) => sign * a.key.localeCompare(b.key));
                const all = ms.flatMap((m) => m.games);
                return { year, months: ms, count: all.length, hours: all.reduce((s, g) => s + g.playtime_minutes, 0) / 60 };
            }).sort((a, b) => sign * a.year.localeCompare(b.year));

            this.groups = groups;
            // com "Todos", renderiza só os anos mais novos (o resto vem sob demanda)
            const n = this.year ? groups.length : this.visible;
            this.shown = desc ? groups.slice(0, n) : groups.slice(Math.max(0, groups.length - n));
            this.hiddenOlder = groups.length - this.shown.length;
            this.columns = this.shown.flatMap((yg) => yg.months.map((mg, i) => ({ ...mg, year: yg.year, showYear: i === 0 })));
        },
    };
}
</script>
