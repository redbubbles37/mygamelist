<?php
/** @var array|null $me */
/** @var array $franchise  com queue (situação dos que faltam no backlog/dropados) e nav (franquia anterior/seguinte) */
$demo = $demo ?? false;
$editUrl = url('/franquias/' . (int) $franchise['id'] . '/editar');
?>
<main class="page page-wide" x-data='franchiseShow(<?= e(json_encode($franchise, JSON_UNESCAPED_UNICODE)) ?>, <?= $demo ? 'true' : 'false' ?>)'
      @keydown.window="pageKeys($event)">
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo): $note = 'Os jogos marcados como feitos vêm do catálogo de exemplo.'; ?>
    <?php ob_start(); ?> com dados fictícios. <?= $note ?><?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <nav class="flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="Navegação entre franquias">
        <a href="<?= e(url($demo ? '/exemplo/franquias' : '/franquias')) ?>" class="back-link">← Franquias</a>
        <span class="hidden text-xs text-slate-500 lg:inline">← → trocam de franquia · <kbd class="rounded border border-slate-50/15 px-1">/</kbd> busca</span>
    </nav>

    <!-- ========== Banner: a franquia, a trilha completa e o próximo jogo ========== -->
    <header class="franchise-hero relative isolate mt-4 rounded-3xl border border-slate-50/10 bg-hero text-white">
        <div class="absolute inset-0 -z-10 overflow-hidden rounded-3xl" aria-hidden="true">
            <img x-show="banner" :src="banner" alt="" class="h-full w-full object-cover" :class="(f.hero_url || f.cover_url) ? '' : 'scale-150 opacity-40 blur-3xl'" :style="(f.hero_url || f.cover_url) ? UI.focusStyle(f.cover_focus?.hero) : ''">
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/55 to-black/15"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-transparent"></div>
        </div>
        <div class="flex min-h-[16rem] flex-col justify-end gap-5 p-5 sm:min-h-[20rem] sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0 max-w-3xl">
                    <p class="kicker-hero" x-text="'Franquia · ' + f.sections.length + (f.sections.length === 1 ? ' seção' : ' seções')"></p>
                    <h1 class="mt-1 t-hero-xl drop-shadow-lg" x-text="f.name"></h1>
                    <p x-show="f.description" class="mt-2 text-white/80 drop-shadow" x-text="f.description"></p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <?php if (!$demo): ?>
                    <a href="<?= e($editUrl) ?>" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black transition hover:bg-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">✎ Editar</a>
                    <?php endif; ?>
                    <div class="relative" @click.outside="menuOpen = false" @keydown.escape="menuOpen && (menuOpen = false, $refs.menuBtn.focus())">
                        <button type="button" x-ref="menuBtn" @click="menuOpen = !menuOpen; menuOpen && $nextTick(() => $refs.menu.querySelector('button')?.focus())" :aria-expanded="menuOpen" aria-haspopup="menu" aria-label="Mais ações" class="grid h-9 w-9 place-items-center rounded-xl border border-white/30 bg-black/40 text-white backdrop-blur transition hover:bg-white/10">⋯</button>
                        <div x-show="menuOpen" x-cloak x-ref="menu" role="menu" @keydown="menuKeys($event)" class="absolute right-0 top-11 z-pop w-64 overflow-hidden rounded-xl border border-slate-50/15 bg-slate-900 py-1 text-sm shadow-2xl">
                            <button type="button" role="menuitem" @click="downloadCard(); menuOpen = false" class="block w-full px-4 py-2 text-left text-slate-200 hover:bg-slate-50/10 focus:bg-slate-50/10 focus:outline-none">🖼 Baixar o cartão da franquia</button>
                            <button type="button" role="menuitem" @click="copyLink()" class="block w-full px-4 py-2 text-left text-slate-200 hover:bg-slate-50/10 focus:bg-slate-50/10 focus:outline-none">🔗 Copiar link</button>
                            <?php if (!$demo): ?>
                            <button type="button" role="menuitem" @click="menuOpen = false; confirming = true" class="block w-full border-t border-slate-50/10 px-4 py-2 text-left text-danger hover:bg-danger/10 focus:bg-danger/10 focus:outline-none">🗑 Excluir…</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <p x-show="notice" x-cloak role="status" class="-mt-2 text-sm text-ok" x-text="notice"></p>

            <!-- trilha: um segmento por jogo, na ordem das seções; o espaço maior marca a troca de seção.
                 Uma parada só no Tab; as setas andam pelos jogos e Enter vai até ele. -->
            <div x-show="f.total" class="rounded-2xl border border-white/10 bg-black/45 p-4 backdrop-blur-md">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <p class="text-sm"><strong class="text-2xl font-black tabular-nums" x-text="f.done"></strong><span class="text-white/60" x-text="' de ' + f.total + ' ' + unit"></span>
                        <span x-show="complete" class="ml-2 rounded-full bg-ok px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider text-black">✓ Completa</span></p>
                    <p class="text-sm text-white/70">
                        <span x-show="f.minutes" x-text="'⏱ ' + hoursLabel(f.minutes)"></span>
                        <span x-show="f.games > 1" x-text="' · média de ' + hoursLabel(Math.round(f.minutes / f.games)) + ' por jogo'"></span>
                        <strong class="ml-2 text-lg tabular-nums" :class="complete ? 'text-ok' : 'text-white'" x-text="pct(f) + '%'"></strong>
                    </p>
                </div>
                <div class="mt-3 flex gap-1.5 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 focus-visible:ring-offset-4 focus-visible:ring-offset-black"
                     tabindex="0" role="group" :aria-label="'Trilha da franquia, ' + pct(f) + '% completa. Use as setas para passar pelos jogos e Enter para ir até ele.'"
                     @keydown="trackKeys($event)" @focus="trackIdx < 0 && (trackIdx = 0)" @blur="trackIdx = -1" @mouseleave="trackIdx = -1">
                    <template x-for="s in f.sections" :key="s.id">
                        <div x-show="s.entries.length" class="flex min-w-0 gap-[3px]" :style="'flex:' + Math.max(s.entries.length, 1)">
                            <template x-for="e in s.entries" :key="e.id">
                                <span @click="goTo(e)" @mouseenter="trackIdx = flat.indexOf(e)" class="block h-3 min-w-[3px] flex-1 cursor-pointer rounded-sm transition motion-reduce:transition-none"
                                      :class="[e.done ? 'bg-ok' : 'bg-white/20', flat[trackIdx] === e ? 'scale-y-[1.8] ring-1 ring-white' : 'hover:scale-y-150']"></span>
                            </template>
                        </div>
                    </template>
                </div>
                <p class="mt-2 h-4 truncate text-xs text-white/70" aria-live="polite" x-text="trackLabel"></p>
                <!-- previsão -->
                <p x-show="forecast" x-cloak class="mt-1 text-xs text-white/60" x-text="forecast"></p>
            </div>

            <!-- o próximo da franquia -->
            <div x-show="nextUp" x-cloak class="flex w-fit max-w-full flex-wrap items-center gap-2 rounded-2xl border border-white/15 bg-black/50 px-3 py-2 text-sm backdrop-blur-md">
                <span class="text-white/60">▶ Próximo da franquia:</span>
                <strong class="min-w-0" x-text="nextUp ? nextUp.title + (nextUp.release_year ? ' (' + nextUp.release_year + ')' : '') : ''"></strong>
                <template x-if="nextUp && !isDemo">
                    <span class="flex flex-wrap gap-2">
                        <a :href="registerUrl(nextUp)" class="rounded-lg bg-ok px-2.5 py-1 text-xs font-black text-black hover:opacity-90">Zerei</a>
                        <a x-show="qs(nextUp)" :href="qs(nextUp)?.href" class="rounded-lg border border-white/30 px-2.5 py-1 text-xs font-semibold hover:bg-white/10" x-text="stateLabel(qs(nextUp))"></a>
                        <button type="button" x-show="!qs(nextUp)" @click="wantToPlay(nextUp)" :disabled="nextUp.queuing" class="rounded-lg border border-white/30 px-2.5 py-1 text-xs font-semibold hover:bg-white/10 disabled:opacity-60" x-text="nextUp.queuing ? '…' : '+ Quero jogar'"></button>
                    </span>
                </template>
            </div>
            <p x-show="!f.total && !isDemo" x-cloak class="text-sm text-white/70">Esta franquia ainda não tem jogos. <a href="<?= e($editUrl) ?>" class="font-semibold text-white underline">Adicionar jogos</a></p>
        </div>
    </header>

    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_20rem]">
        <!-- ========== Conteúdo: os jogos ========== -->
        <div class="min-w-0">
            <div x-show="f.total" class="flex flex-wrap items-center gap-3">
                <div class="seg" role="group" aria-label="Mostrar">
                    <template x-for="o in [{ v: 'all', l: 'Todos' }, { v: 'done', l: 'Zerados' }, { v: 'todo', l: 'Faltando' }]" :key="o.v">
                        <button type="button" @click="show = o.v" :aria-pressed="show === o.v" class="seg-btn" :class="show === o.v ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'"
                                x-text="o.l + ' (' + (o.v === 'all' ? f.total : o.v === 'done' ? f.done : f.total - f.done) + ')'"></button>
                    </template>
                </div>
                <label class="relative min-w-[10rem] flex-1">
                    <span class="sr-only">Buscar nesta franquia</span>
                    <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                    <input type="search" x-ref="search" x-model="q" @keydown.escape="q = ''; $el.blur()" placeholder="Buscar jogo…" class="w-full rounded-lg border border-slate-50/10 bg-slate-900/70 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-brand-500">
                </label>
                <div class="seg" role="group" aria-label="Exibição">
                    <button type="button" @click="mode = 'sections'" :aria-pressed="mode === 'sections'" class="seg-btn" :class="mode === 'sections' ? 'bg-slate-50/15 text-slate-50' : 'text-slate-400 hover:text-slate-50'">Por seção</button>
                    <button type="button" @click="mode = 'years'" :aria-pressed="mode === 'years'" class="seg-btn" :class="mode === 'years' ? 'bg-slate-50/15 text-slate-50' : 'text-slate-400 hover:text-slate-50'">Linha do tempo</button>
                </div>
            </div>
            <p x-show="q && !matches" x-cloak class="mt-6 text-sm text-slate-400">Nenhum jogo com “<span x-text="q"></span>” nesta franquia.</p>

            <!-- por seção: os zerados em estojo, os que faltam em cartões baixos -->
            <div x-show="mode === 'sections'">
                <template x-for="(s, si) in f.sections" :key="s.id">
                    <section x-show="!q || visible(s.entries).length" class="mt-8 first:mt-6 scroll-mt-24" :id="'s' + s.id" :aria-label="s.name">
                        <h2>
                            <button type="button" @click="toggle(s)" :aria-expanded="isOpen(s)" :aria-controls="'sb' + s.id" class="group flex w-full items-center gap-3 rounded-lg text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-black" :class="s.total && s.done === s.total ? 'bg-ok text-black' : 'bg-slate-50/10 text-slate-300'" x-text="s.total && s.done === s.total ? '✓' : si + 1"></span>
                                <span class="min-w-0 truncate text-xl font-bold text-slate-50 group-hover:underline" x-text="s.name"></span>
                                <span class="shrink-0 text-sm font-semibold tabular-nums" :class="s.total && s.done === s.total ? 'text-ok' : 'text-slate-400'" x-text="s.done + '/' + s.total"></span>
                                <span class="h-px flex-1 bg-slate-50/10"></span>
                                <span class="shrink-0 text-slate-400 transition" :class="isOpen(s) ? 'rotate-180' : ''" aria-hidden="true">▾</span>
                            </button>
                        </h2>
                        <div x-show="isOpen(s)" :id="'sb' + s.id">
                            <p x-show="!s.entries.length" class="mt-3 text-sm text-slate-500">Nenhum jogo nesta seção.</p>
                            <!-- zerados -->
                            <ul x-show="doneOf(s).length" class="mt-4 grid grid-cols-2 gap-x-5 gap-y-7 sm:grid-cols-3 xl:grid-cols-4">
                                <template x-for="e in doneOf(s)" :key="e.id">
                                    <li :id="'e' + e.id" class="scroll-mt-24 rounded-xl transition" :class="flash === e.id && 'ring-4 ring-brand-400 ring-offset-4 ring-offset-slate-950'">
                                        <a :href="isDemo ? null : base + '/jogos/' + e.game.id" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">
                                            <div class="case case-tall">
                                                <div class="case-band" :style="UI.bandStyle(e.game.platform)"><span x-text="e.game.platform"></span><span class="case-date" x-text="e.release_year || ''"></span></div>
                                                <div class="case-art">
                                                    <img x-show="e.game.cover_url" :src="e.game.cover_url" :alt="'Capa de ' + e.game.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                                    <div x-show="!e.game.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/50 p-3 text-center">
                                                        <span class="text-5xl font-bold text-white/80" x-text="UI.initials(e.title)"></span>
                                                    </div>
                                                    <div x-show="!e.game.cover_url" class="absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 px-3 pb-2.5 pt-2 backdrop-blur-md">
                                                        <p class="truncate text-center text-sm font-bold leading-tight text-white" :title="e.title" x-text="e.title"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                            <!-- faltando -->
                            <p x-show="doneOf(s).length && todoOf(s).length" class="kicker mt-6" x-text="'Faltam ' + todoOf(s).length"></p>
                            <ul x-show="todoOf(s).length" class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3 [&>li]:min-w-0">
                                <template x-for="e in todoOf(s)" :key="e.id">
                                    <li :id="'e' + e.id" class="scroll-mt-24 flex items-center gap-3 rounded-xl border border-dashed border-slate-50/15 bg-slate-900/40 p-3 transition" :class="flash === e.id && 'ring-4 ring-brand-400'">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-50/5 text-xs font-bold tabular-nums text-slate-400" x-text="'#' + (s.entries.indexOf(e) + 1)"></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="line-clamp-2 break-words text-sm font-semibold leading-snug text-slate-100" :title="e.title" x-text="e.title"></span>
                                            <span class="flex flex-wrap items-center gap-x-2 text-xs text-slate-500">
                                                <span x-show="e.release_year" x-text="e.release_year"></span>
                                                <a x-show="qs(e)" :href="isDemo ? null : qs(e)?.href" class="font-semibold hover:underline" :class="stateClass(qs(e))" x-text="stateLabel(qs(e))"></a>
                                            </span>
                                        </span>
                                        <span x-show="!isDemo" class="flex shrink-0 gap-1">
                                            <a :href="registerUrl(e)" class="rounded-lg border border-slate-50/15 px-2 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-brand-400/60 hover:bg-brand-500/10 hover:text-brand-400" :aria-label="'Registrar ' + e.title + ' como zerado'" title="Já zerei: registrar">✓</a>
                                            <button type="button" x-show="!qs(e)" @click="wantToPlay(e)" :disabled="e.queuing" class="rounded-lg border border-slate-50/15 px-2 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-brand-400/60 hover:bg-brand-500/10 hover:text-brand-400 disabled:opacity-60"
                                                    :title="e.queueError || 'Adicionar ao backlog'" :aria-label="'Quero jogar ' + e.title" x-text="e.queuing ? '…' : '+ Fila'"></button>
                                        </span>
                                    </li>
                                </template>
                            </ul>
                            <p x-show="s.entries.length && !visible(s.entries).length" class="mt-3 text-sm text-slate-500" x-text="show === 'done' ? 'Nenhum jogo zerado nesta seção ainda.' : 'Seção completa. 🎉'"></p>
                        </div>
                    </section>
                </template>
            </div>

            <!-- linha do tempo: por ano de lançamento, de todas as seções -->
            <ol x-show="mode === 'years'" x-cloak class="relative ml-2 mt-6 border-l-2 border-slate-50/10 pl-6">
                <template x-for="y in years" :key="y.year">
                    <li class="relative mb-8 last:mb-0">
                        <span class="absolute -left-[33px] top-1.5 h-4 w-4 rounded-full border-2 border-slate-950" :class="y.items.every((x) => x.e.done) ? 'bg-ok' : y.items.some((x) => x.e.done) ? 'bg-gold-400' : 'bg-slate-600'"></span>
                        <p class="text-2xl font-black tabular-nums text-slate-50" x-text="y.year === 9999 ? 'Sem ano' : y.year"></p>
                        <ul class="mt-3 space-y-2">
                            <template x-for="x in y.items" :key="x.e.id">
                                <li class="flex items-center gap-3 rounded-xl border border-slate-50/10 bg-slate-900/70 p-2 pr-3" :class="x.e.done ? '' : 'border-dashed opacity-80'">
                                    <span class="relative h-14 w-10 shrink-0 overflow-hidden rounded-md bg-slate-950">
                                        <img x-show="x.e.done && x.e.game.cover_url" :src="x.e.game?.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                        <span x-show="!(x.e.done && x.e.game.cover_url)" class="absolute inset-0 grid place-items-center text-sm font-bold text-slate-500" x-text="x.e.done ? UI.initials(x.e.title) : '?'"></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <a :href="x.e.done && !isDemo ? base + '/jogos/' + x.e.game.id : null" class="block truncate font-semibold text-slate-50" :class="x.e.done && !isDemo ? 'hover:underline' : ''" x-text="x.e.title"></a>
                                        <span class="block truncate text-xs text-slate-500" x-text="x.section + (x.e.done ? ' · ' + x.e.game.platform + (x.e.game.finished_at ? ' · zerado em ' + UI.fmtDate(x.e.game.finished_at.slice(0, 10)) : '') : (qs(x.e) ? ' · ' + stateLabel(qs(x.e)) : ' · pendente'))"></span>
                                    </span>
                                    <span x-show="x.e.done && x.e.game?.rating" class="shrink-0 rounded-md px-2 py-0.5 text-sm font-black" :class="UI.ratingClass(x.e.game?.rating)" :style="x.e.game?.rating ? UI.ratingStyle(x.e.game.rating) : ''" x-text="x.e.game?.rating"></span>
                                    <a x-show="!x.e.done && !isDemo" :href="registerUrl(x.e)" class="shrink-0 rounded-lg border border-slate-50/15 px-2.5 py-1 text-xs font-semibold text-slate-200 hover:border-brand-400/60 hover:text-brand-400">+ Registrar</a>
                                </li>
                            </template>
                        </ul>
                    </li>
                </template>
                <li x-show="!years.length" class="text-sm text-slate-500">Nada para mostrar nesse filtro.</li>
            </ol>
        </div>

        <!-- ========== Lateral: best of, números, jornada e a sua ordem ========== -->
        <aside class="min-w-0 space-y-5 lg:sticky lg:top-24 lg:self-start" aria-label="Resumo da franquia">
            <!-- best of -->
            <section x-show="f.best || (doneGames >= bestMin && !isDemo)" x-cloak aria-labelledby="h-best">
                <template x-if="f.best">
                    <div class="relative isolate overflow-hidden rounded-2xl border border-gold-400/35 bg-hero p-5 text-white">
                        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                            <img x-show="f.best.cover_url" :src="f.best.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-2xl">
                            <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/70 to-gold-500/10"></div>
                        </div>
                        <p id="h-best" class="best-kicker">🏆 Best of da franquia</p>
                        <div class="mt-3 flex gap-4">
                            <a :href="isDemo ? null : base + '/jogos/' + f.best.id" class="best-frame best-shine aspect-[3/4] w-20 shrink-0 -rotate-2 transition hover:rotate-0" :title="f.best.title">
                                <img x-show="f.best.cover_url" :src="f.best.cover_url" :alt="'Capa de ' + f.best.title">
                                <span x-show="!f.best.cover_url" class="best-frame-inner grid place-items-center bg-slate-800 text-2xl font-bold text-white/70" x-text="UI.initials(f.best.title)"></span>
                            </a>
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold leading-tight" x-text="f.best.title"></h2>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-white/80">
                                    <span class="rounded-md px-1.5 py-0.5 text-sm font-black" :class="UI.ratingClass(f.best.rating)" :style="UI.ratingStyle(f.best.rating)" x-text="f.best.rating"></span>
                                    <span class="rounded-full bg-white/10 px-2 py-0.5" x-text="f.best.platform"></span>
                                </div>
                                <p class="mt-2 text-xs text-white/55" x-text="'Entre ' + doneGames + ' zerados' + (isDemo ? ' (exemplo)' : '')"></p>
                            </div>
                        </div>
                        <button type="button" x-show="!isDemo" @click="openBest()" class="mt-4 w-full rounded-lg border border-gold-400/50 bg-black/30 px-4 py-2 text-sm font-semibold text-gold-300 transition hover:bg-gold-400/15">Trocar</button>
                    </div>
                </template>
                <template x-if="!f.best">
                    <div class="rounded-2xl border border-dashed border-gold-400/40 bg-gradient-to-b from-gold-500/10 to-transparent p-5">
                        <p class="best-kicker">🏆 Best of da franquia</p>
                        <p class="mt-1 font-bold text-slate-50" x-text="'Qual é o melhor de ' + f.name + '?'"></p>
                        <p class="text-sm text-slate-400" x-text="'Escolha entre os que você zerou (só nota 9, 10 ou 11 podem ser eleitos).'"></p>
                        <button type="button" @click="openBest()" class="mt-3 w-full rounded-lg bg-gradient-to-r from-gold-400 to-gold-500 px-4 py-2 text-sm font-bold text-black shadow transition hover:brightness-110">Escolher o best of</button>
                    </div>
                </template>
            </section>
            <p x-show="!f.best && doneGames > 0 && doneGames < bestMin" x-cloak class="rounded-2xl border border-slate-50/10 p-4 text-sm text-slate-400" x-text="'🏆 Zere mais ' + (bestMin - doneGames) + (bestMin - doneGames === 1 ? ' jogo' : ' jogos') + ' da franquia para escolher o best of.'"></p>

            <!-- números -->
            <section x-show="doneList.length" x-cloak class="panel p-5" aria-labelledby="h-nums">
                <h2 id="h-nums" class="kicker">Você e a franquia</h2>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-500">Nota média</dt><dd class="text-xl font-black tabular-nums text-slate-50" x-text="avgRating ?? '—'"></dd></div>
                    <div><dt class="text-slate-500">Horas</dt><dd class="text-xl font-black tabular-nums text-slate-50" x-text="hoursLabel(f.minutes)"></dd></div>
                    <div><dt class="text-slate-500">Primeiro zerado</dt><dd class="font-semibold text-slate-200" x-text="doneList[0]?.finished_at ? UI.fmtDate(doneList[0].finished_at.slice(0, 10)) : '—'"></dd></div>
                    <div><dt class="text-slate-500">Último</dt><dd class="font-semibold text-slate-200" x-text="doneList.at(-1)?.finished_at ? UI.fmtDate(doneList.at(-1).finished_at.slice(0, 10)) : '—'"></dd></div>
                </dl>
                <div x-show="platforms.length" class="mt-4">
                    <p class="text-xs text-slate-500">Onde você jogou</p>
                    <ul class="mt-2 flex flex-wrap gap-1.5">
                        <template x-for="p in platforms" :key="p.name">
                            <li class="rounded-md px-2 py-0.5 text-2xs font-bold" :style="platformChip(p.name)" x-text="p.name + ' · ' + p.n"></li>
                        </template>
                    </ul>
                </div>
            </section>

            <!-- sua jornada na franquia: ano de lançamento × ano em que zerou -->
            <section x-show="journey.points.length >= 2" x-cloak class="panel p-5" aria-labelledby="h-journey">
                <h2 id="h-journey" class="kicker">Sua jornada na franquia</h2>
                <p class="mt-1 text-xs text-slate-500">Lançamento (→) × quando você zerou (↑). Na diagonal: jogou na época.</p>
                <div class="relative mb-6 ml-8 mt-4 aspect-[4/3]">
                    <div class="absolute inset-0 rounded-md border-b border-l border-slate-50/20"></div>
                    <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><line x1="5" y1="95" x2="95" y2="5" stroke="currentColor" class="text-slate-50/15" stroke-dasharray="3 3" vector-effect="non-scaling-stroke"/></svg>
                    <template x-for="p in journey.points" :key="p.id">
                        <a :href="isDemo ? null : base + '/jogos/' + p.id" class="absolute grid h-3.5 w-3.5 -translate-x-1/2 translate-y-1/2 rounded-full ring-2 ring-slate-900 transition hover:scale-150 focus:scale-150 focus:outline-none"
                           :class="p.gap <= 1 ? 'bg-ok' : p.gap <= 5 ? 'bg-gold-400' : 'bg-brand-500'"
                           :style="'left:' + p.x + '%;bottom:' + p.y + '%'" :title="p.label" :aria-label="p.label"></a>
                    </template>
                    <span class="absolute -bottom-5 left-0 text-2xs tabular-nums text-slate-500" x-text="journey.lo"></span>
                    <span class="absolute -bottom-5 right-0 text-2xs tabular-nums text-slate-500" x-text="journey.hi"></span>
                    <span class="absolute -left-8 bottom-0 text-2xs tabular-nums text-slate-500" x-text="journey.lo"></span>
                    <span class="absolute -left-8 top-0 text-2xs tabular-nums text-slate-500" x-text="journey.hi"></span>
                </div>
                <p class="text-xs text-slate-300" x-text="journey.summary"></p>
                <p class="mt-2 flex flex-wrap gap-x-3 text-2xs text-slate-500">
                    <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-ok"></span>na época</span>
                    <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-gold-400"></span>até 5 anos</span>
                    <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-brand-500"></span>mais tarde</span>
                </p>
            </section>

            <!-- a sua ordem: como você zerou a franquia -->
            <section x-show="doneList.length > 1" x-cloak class="panel p-5" aria-labelledby="h-order">
                <h2 id="h-order" class="kicker">A ordem em que você zerou</h2>
                <ol class="mt-3 space-y-2">
                    <template x-for="(g, i) in doneList" :key="g.id">
                        <li class="flex items-center gap-2 text-sm">
                            <span class="w-5 shrink-0 text-right text-xs font-bold tabular-nums text-slate-500" x-text="i + 1"></span>
                            <a :href="isDemo ? null : base + '/jogos/' + g.id" class="min-w-0 flex-1 truncate text-slate-200" :class="isDemo ? '' : 'hover:underline'" x-text="g.title"></a>
                            <span class="shrink-0 text-xs tabular-nums text-slate-500" x-text="g.finished_at ? g.finished_at.slice(0, 4) : ''"></span>
                        </li>
                    </template>
                </ol>
            </section>

            <!-- atalho para as seções -->
            <nav x-show="f.sections.length > 1" class="rounded-2xl border border-slate-50/10 p-5" aria-label="Seções">
                <h2 class="kicker">Seções</h2>
                <ul class="mt-3 space-y-2">
                    <template x-for="s in f.sections" :key="s.id">
                        <li><a :href="'#s' + s.id" @click="mode = 'sections'; open(s)" class="group block text-sm">
                            <span class="flex justify-between gap-2"><span class="truncate text-slate-200 group-hover:underline" x-text="s.name"></span><span class="shrink-0 text-xs tabular-nums text-slate-500" x-text="s.done + '/' + s.total"></span></span>
                            <span class="mt-1 block h-1 overflow-hidden rounded-full bg-slate-50/10"><span class="block h-full rounded-full" :class="s.total && s.done === s.total ? 'bg-ok' : 'bg-brand-500'" :style="'width:' + pct(s) + '%'"></span></span>
                        </a></li>
                    </template>
                </ul>
            </nav>
        </aside>
    </div>

    <!-- ========== Franquia anterior / seguinte ========== -->
    <nav x-show="f.nav && (f.nav.prev || f.nav.next)" x-cloak class="mt-12 grid gap-3 sm:grid-cols-2" aria-label="Franquia anterior e seguinte">
        <div><a x-show="f.nav?.prev" :href="f.nav?.prev && link(f.nav.prev)" class="group block panel p-4 transition hover:border-brand-400/60">
            <span class="block text-xs text-slate-500">← Franquia anterior</span>
            <span class="block truncate font-semibold text-slate-100 group-hover:text-brand-400" x-text="f.nav?.prev?.name"></span>
        </a></div>
        <div><a x-show="f.nav?.next" :href="f.nav?.next && link(f.nav.next)" class="group block panel p-4 text-right transition hover:border-brand-400/60">
            <span class="block text-xs text-slate-500">Próxima franquia →</span>
            <span class="block truncate font-semibold text-slate-100 group-hover:text-brand-400" x-text="f.nav?.next?.name"></span>
        </a></div>
    </nav>

    <!-- Confirmação de exclusão -->
    <div x-show="confirming" x-cloak @keydown.escape.window="confirming = false" role="alertdialog" aria-modal="true" aria-labelledby="del-title"
         class="modal-back" @click.self="confirming = false">
        <div class="modal-box">
            <h2 id="del-title" class="t-section">Excluir esta franquia?</h2>
            <p class="mt-2 text-sm text-slate-400">
                <strong class="text-slate-200" x-text="f.name"></strong> e a lista de <span x-text="f.total + ' ' + unit"></span> serão removidas.
                <strong class="text-slate-200">Seus jogos registrados no catálogo não são afetados.</strong>
            </p>
            <p x-show="error" x-cloak role="alert" class="mt-3 text-sm text-danger" x-text="error"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-ref="cancel" @click="confirming = false" class="btn-ghost">Cancelar</button>
                <button type="button" @click="remove()" :disabled="deleting" class="rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60" x-text="deleting ? 'Excluindo…' : 'Sim, excluir'"></button>
            </div>
        </div>
    </div>
</main>

<?php if (!$demo) require __DIR__ . '/partials/best_of.php'; ?>

<script>
function franchiseShow(f, demo = false) {
    const MONTHS = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    const norm = (s) => String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const storeKey = 'mgl.franchise.closed.' + f.id;
    f.queue ??= {};
    return {
        f,
        isDemo: demo,
        base: window.APP.base,
        confirming: false,
        deleting: false,
        error: '',
        show: 'all',
        mode: 'sections',
        q: '',
        closed: {},           // seções recolhidas (id → true)
        menuOpen: false, notice: '',
        trackIdx: -1, flash: null,
        pace: null,           // jogos zerados por mês (últimos 12 meses), para a previsão

        /** Tempo legível: a partir de 10h só as horas ("124h"), abaixo disso com os minutos ("3h 20min"). */
        hoursLabel(min) {
            min = Number(min) || 0;
            return min >= 600 ? Math.round(min / 60).toLocaleString('pt-BR') + 'h' : UI.fmtTime(min);
        },
        link(x) { return this.base + (this.isDemo ? '/exemplo/franquias/' : '/franquias/') + x.id; },

        pct: (x) => (x.total ? Math.round(x.done / x.total * 100) : 0),
        get complete() { return this.f.total > 0 && this.f.done === this.f.total; },
        /** todos os itens em ordem (a trilha) */
        get flat() { return this.f.sections.flatMap((s) => s.entries); },
        /** "jogos" ou "itens" (quando o mesmo jogo aparece em mais de uma seção) */
        get unit() {
            const n = new Set(this.flat.map((e) => norm(e.title) + '|' + (e.release_year ?? ''))).size;
            return n < this.f.total ? 'itens' : this.f.total === 1 ? 'jogo' : 'jogos';
        },
        visible(entries) {
            const q = norm(this.q.trim());
            return entries.filter((e) => (this.show === 'all' || (this.show === 'done') === e.done) && (!q || norm(e.title).includes(q)));
        },
        doneOf(s) { return this.visible(s.entries).filter((e) => e.done); },
        todoOf(s) { return this.visible(s.entries).filter((e) => !e.done); },
        get matches() { return this.f.sections.reduce((n, s) => n + this.visible(s.entries).length, 0); },

        // ---------- seções recolhíveis ----------
        isOpen(s) { return !!this.q || !this.closed[s.id]; },
        toggle(s) { this.closed[s.id] = !this.closed[s.id] || undefined; this.saveClosed(); },
        open(s) { if (this.closed[s.id]) { delete this.closed[s.id]; this.saveClosed(); } },
        saveClosed() { try { localStorage.setItem(storeKey, JSON.stringify(Object.keys(this.closed).filter((k) => this.closed[k]))); } catch { /* sem storage */ } },

        // ---------- situação fora do catálogo ----------
        qs(e) { return this.f.queue[e.id] ?? null; },
        stateLabel(st) { return st ? { want: '✓ Na fila', playing: '▶ Jogando', dropped: '✕ Dropado' }[st.state] : ''; },
        stateClass(st) { return st ? { want: 'text-brand-400', playing: 'text-ok', dropped: 'text-danger' }[st.state] : ''; },

        /** etiqueta com a cor do sistema (as mesmas variáveis da faixa do estojo) */
        platformChip(name) { return UI.bandStyle(name) + ';background:var(--band-bg);color:var(--band-fg)'; },
        /** o primeiro item que ainda falta, seguindo a ordem das seções */
        get nextUp() { return this.flat.find((e) => !e.done) ?? null; },
        /** banner: imagem própria; sem ela, a capa do best of ou de algum zerado (desfocada) */
        get banner() {
            if (this.f.hero_url || this.f.cover_url) return this.f.hero_url || this.f.cover_url;
            return this.f.best?.cover_url || this.doneList.find((g) => g.cover_url)?.cover_url || null;
        },
        /** jogos zerados distintos, na ordem em que foram zerados */
        get doneList() {
            const seen = new Map();
            for (const e of this.flat) if (e.done && !seen.has(e.game.id)) seen.set(e.game.id, e.game);
            return [...seen.values()].sort((a, b) => String(a.finished_at ?? '9999').localeCompare(String(b.finished_at ?? '9999')));
        },
        get avgRating() {
            const r = this.doneList.map((g) => Number(g.rating)).filter((n) => n > 0);
            return r.length ? (r.reduce((a, b) => a + b, 0) / r.length).toFixed(1).replace('.', ',') : null;
        },
        get platforms() {
            const c = {};
            for (const g of this.doneList) if (g.platform) c[g.platform] = (c[g.platform] ?? 0) + 1;
            return Object.entries(c).map(([name, n]) => ({ name, n })).sort((a, b) => b.n - a.n);
        },
        /** todos os itens agrupados pelo ano de lançamento (filtro e busca aplicados) */
        get years() {
            const by = {};
            for (const s of this.f.sections) for (const e of this.visible(s.entries)) (by[e.release_year ?? 9999] ??= []).push({ e, section: s.name });
            return Object.keys(by).map(Number).sort((a, b) => a - b).map((year) => ({ year, items: by[year] }));
        },

        // ---------- trilha pelo teclado ----------
        get trackLabel() {
            const e = this.flat[this.trackIdx];
            if (!e) return '';
            const s = this.f.sections.find((x) => x.entries.includes(e));
            return (e.done ? '✓ ' : '· ') + e.title + (e.release_year ? ' (' + e.release_year + ')' : '') + ' — ' + s.name;
        },
        trackKeys(ev) {
            const n = this.flat.length;
            const d = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[ev.key];
            if (d) { ev.preventDefault(); ev.stopPropagation(); this.trackIdx = (Math.max(0, this.trackIdx) + d + n) % n; }
            else if (ev.key === 'Home') { ev.preventDefault(); this.trackIdx = 0; }
            else if (ev.key === 'End') { ev.preventDefault(); this.trackIdx = n - 1; }
            else if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); if (this.flat[this.trackIdx]) this.goTo(this.flat[this.trackIdx]); }
        },
        /** rola até o item (abrindo a seção e limpando filtros) e pisca o cartão */
        goTo(e) {
            this.mode = 'sections'; this.show = 'all'; this.q = '';
            const s = this.f.sections.find((x) => x.entries.includes(e));
            if (s) this.open(s);
            this.$nextTick(() => {
                document.getElementById('e' + e.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                this.flash = e.id; setTimeout(() => (this.flash = null), 1600);
            });
        },

        // ---------- previsão ----------
        get forecast() {
            const left = this.f.total - this.f.done;
            if (!left || !this.f.games) return '';
            const h = Math.round(left * this.f.minutes / this.f.games / 60);
            let txt = 'Faltam ' + left + ' ' + (this.unit === 'itens' ? 'itens' : left === 1 ? 'jogo' : 'jogos') + ' ≈ ' + h.toLocaleString('pt-BR') + 'h (pela sua média por jogo)';
            if (this.pace) {
                const months = Math.ceil(left / this.pace);
                if (months > 120) txt += ' · mais de 10 anos se você só jogasse a franquia';
                else { const d = new Date(); d.setMonth(d.getMonth() + months); txt += ' · jogando só ela, termina por volta de ' + MONTHS[d.getMonth()] + ' de ' + d.getFullYear(); }
            }
            return txt + '.';
        },
        async loadPace() {
            if (this.isDemo) { this.pace = 2.5; return; }
            try {
                const now = new Date(), y = now.getFullYear(), m = now.getMonth();
                const [a, b] = await Promise.all([api.get('/api/me/stats?year=' + y), api.get('/api/me/stats?year=' + (y - 1))]);
                this.pace = (a.months.slice(0, m + 1).reduce((s, x) => s + x.games, 0) + b.months.slice(m + 1).reduce((s, x) => s + x.games, 0)) / 12;
            } catch { this.pace = 0; }
        },

        // ---------- jornada: lançamento × quando zerou ----------
        get journey() {
            const seen = new Set(), raw = [];
            for (const e of this.flat) {
                if (!e.done || !e.release_year || !e.game.finished_at || seen.has(e.game.id)) continue;
                seen.add(e.game.id);
                raw.push({ id: e.game.id, title: e.game.title, r: +e.release_year, d: +e.game.finished_at.slice(0, 4) });
            }
            if (!raw.length) return { points: [], lo: 0, hi: 0, summary: '' };
            let lo = Math.min(...raw.map((p) => Math.min(p.r, p.d))), hi = Math.max(...raw.map((p) => Math.max(p.r, p.d)));
            if (hi === lo) { lo--; hi++; }
            const pos = (v) => 5 + (v - lo) / (hi - lo) * 90;
            const points = raw.map((p) => ({ ...p, gap: p.d - p.r, x: pos(p.r), y: pos(p.d), label: p.title + ': lançado em ' + p.r + ', zerado em ' + p.d }));
            const onTime = points.filter((p) => p.gap <= 1).length, late = [...points].sort((a, b) => b.gap - a.gap)[0];
            const summary = onTime + ' de ' + points.length + ' zerados no lançamento (ou no ano seguinte).' + (late.gap > 1 ? ' A maior espera: ' + late.title + ', ' + late.gap + ' anos.' : '');
            return { points, lo, hi, summary };
        },

        /** Abre o cadastro de jogo já preenchido com os dados do item e um atalho de volta para a franquia. */
        registerUrl(e) {
            const p = new URLSearchParams({ title: e.title, from: this.f.id });
            if (e.release_year) p.set('release_year', e.release_year);
            return `${this.base}/jogos/novo?${p}`;
        },

        /** Adiciona o item ao backlog ("quero jogar"). Se já estiver lá, conta como feito. */
        async wantToPlay(e) {
            e.queuing = true; e.queueError = '';
            try {
                await api.post('/api/backlog', { title: e.title, release_year: e.release_year ?? '', status: 'want' });
                this.f.queue[e.id] = { state: 'want', href: this.base + '/backlog' };
            } catch (err) {
                if (err.status === 409) this.f.queue[e.id] = { state: 'want', href: this.base + '/backlog' }; else e.queueError = err.message;
            } finally { e.queuing = false; }
        },

        // ---------- menu ⋯ ----------
        async copyLink() {
            this.menuOpen = false;
            try { await navigator.clipboard.writeText(location.href); this.notice = '🔗 Link copiado.'; } catch { this.notice = location.href; }
            setTimeout(() => (this.notice = ''), 3000);
        },
        menuKeys(e) {
            const items = [...e.currentTarget.querySelectorAll('[role=menuitem]')], i = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
            if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
            if (e.key === 'Tab') this.menuOpen = false;
        },

        /** Cartão da franquia: imagem gerada no navegador (nome, trilha, progresso, horas e best of). */
        async downloadCard() {
            const f = this.f, p = Theme.palette(), W = 1080, H = 1350;
            const cv = document.createElement('canvas'); cv.width = W; cv.height = H;
            const c = cv.getContext('2d');
            const font = (w, s) => `${w} ${s}px ${p.font}`;
            const load = (src) => new Promise((res) => { if (!src) return res(null); const im = new Image(); im.onload = () => res(im); im.onerror = () => res(null); im.src = src; });
            const [bg, best] = await Promise.all([load(this.banner), load(f.best?.cover_url)]);
            const cover = (im, x, y, w, h) => { const s = Math.max(w / im.width, h / im.height), sw = w / s, sh = h / s; c.drawImage(im, (im.width - sw) / 2, (im.height - sh) / 2, sw, sh, x, y, w, h); };
            c.fillStyle = '#0c0c12'; c.fillRect(0, 0, W, H);
            if (bg) { c.save(); c.globalAlpha = .55; cover(bg, 0, 0, W, 620); c.restore(); }
            const g = c.createLinearGradient(0, 0, 0, 700); g.addColorStop(0, 'rgba(12,12,18,.1)'); g.addColorStop(1, '#0c0c12');
            c.fillStyle = g; c.fillRect(0, 0, W, 700);
            c.fillStyle = 'rgba(255,255,255,.65)'; c.font = font('700', 26); c.fillText('FRANQUIA · ' + f.sections.length + (f.sections.length === 1 ? ' SEÇÃO' : ' SEÇÕES'), 70, 470);
            c.fillStyle = '#fff'; c.font = font('900', 84);
            const words = f.name.split(' '), lines = []; let line = '';
            for (const w of words) { if (c.measureText(line + w).width > W - 140 && line) { lines.push(line.trim()); line = ''; } line += w + ' '; }
            lines.push(line.trim());
            lines.slice(0, 2).forEach((l, i) => c.fillText(l, 70, 560 + i * 90));
            let y = lines.length > 1 ? 760 : 680;
            // trilha
            const flat = this.flat, gap = 4, tw = (W - 140 - gap * (flat.length - 1)) / Math.max(1, flat.length);
            flat.forEach((e, i) => { c.fillStyle = e.done ? '#22c55e' : 'rgba(255,255,255,.18)'; c.fillRect(70 + i * (tw + gap), y, Math.max(2, tw), 26); });
            y += 110;
            const stats = [[this.pct(f) + '%', 'completa'], [f.done + '/' + f.total, this.unit], [this.hoursLabel(f.minutes), 'jogadas'], [this.avgRating ?? '—', 'nota média']];
            stats.forEach(([v, k], i) => {
                const x = 70 + i * 250;
                c.fillStyle = i === 0 ? '#22c55e' : '#fff'; c.font = font('800', 56); c.fillText(v, x, y);
                c.fillStyle = 'rgba(255,255,255,.55)'; c.font = font('600', 24); c.fillText(k, x, y + 38);
            });
            if (f.best) {
                y += 110;
                if (best) { c.save(); c.shadowColor = 'rgba(0,0,0,.6)'; c.shadowBlur = 30; cover(best, 70, y, 150, 200); c.restore(); c.strokeStyle = '#fbbf24'; c.lineWidth = 6; c.strokeRect(70, y, 150, 200); }
                c.fillStyle = '#fbbf24'; c.font = font('800', 26); c.fillText('🏆 BEST OF DA FRANQUIA', 250, y + 60);
                c.fillStyle = '#fff'; c.font = font('800', 44); c.fillText(f.best.title.length > 34 ? f.best.title.slice(0, 33) + '…' : f.best.title, 250, y + 120);
            }
            c.fillStyle = 'rgba(255,255,255,.4)'; c.font = font('700', 22); c.textAlign = 'center'; c.fillText('MYGAMELIST', W / 2, H - 40);
            cv.toBlob((blob) => {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'franquia-' + f.name.replace(/[^\p{L}\p{N}]+/gu, '-').toLowerCase() + '.png';
                a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 2000);
            }, 'image/png');
        },

        /** "/" busca; ← → trocam de franquia (fora de campos, da trilha e de janelas abertas). */
        pageKeys(e) {
            if (this.confirming || e.altKey || e.ctrlKey || e.metaKey || /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName) || e.target.isContentEditable) return;
            if (document.querySelector('[role=dialog]:not([style*="display: none"])')) return;
            if (e.key === '/') { e.preventDefault(); this.$refs.search?.focus(); return; }
            const to = e.key === 'ArrowLeft' ? this.f.nav?.prev : e.key === 'ArrowRight' ? this.f.nav?.next : null;
            if (to) location.href = this.link(to);
        },

        // ---- best of ----
        bestMin: f.best_min ?? 2,
        /** jogos zerados distintos da franquia (os candidatos ao best of) */
        get doneGames() { return this.f.games ?? 0; },
        openBest() { this.$dispatch('best-open', { kind: 'franchise', value: this.f.id, label: this.f.name, current: this.f.best }); },

        init() {
            // seções recolhidas: o que você escolheu; na primeira visita pelo celular, as completas começam fechadas
            let saved = null;
            try { saved = JSON.parse(localStorage.getItem(storeKey) ?? 'null'); } catch { /* sem storage */ }
            if (Array.isArray(saved)) saved.forEach((id) => (this.closed[id] = true));
            else if (window.innerWidth < 640) this.f.sections.forEach((s) => { if (s.total && s.done === s.total) this.closed[s.id] = true; });
            if (!this.complete && this.f.games) this.loadPace();
            this.$watch('confirming', (v) => { if (v) this.$nextTick(() => this.$refs.cancel?.focus()); });
            window.addEventListener('best-saved', (e) => {
                if (e.detail.kind === 'franchise' && String(e.detail.value) === String(this.f.id)) this.f.best = e.detail.best;
            });
        },

        async remove() {
            this.deleting = true;
            this.error = '';
            try {
                await api.del('/api/franchises/' + this.f.id);
                location.href = this.base + '/franquias';
            } catch (e) {
                this.error = e.message;
                this.deleting = false;
            }
        },
    };
}
</script>
