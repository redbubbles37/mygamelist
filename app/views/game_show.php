<?php
/** @var array $me */ /** @var array $game */ /** @var array $events */ /** @var array $franchises */
/** @var array $context  honras, curiosidades, anterior/próximo, relacionados, história, comparação e dados faltando (GameContextService) */
$editUrl = url('/jogos/' . (int) $game['id'] . '/editar');
?>
<main class="page page-normal"
      x-data='gameShow(<?= e(json_encode(['game' => $game, 'events' => $events, 'franchises' => $franchises, 'context' => $context], JSON_UNESCAPED_UNICODE)) ?>)'
      @keydown.window="navKey($event)">
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <!-- Navegação: catálogo e jogo anterior/seguinte na ordem em que foram zerados -->
    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Navegação entre jogos">
        <a href="<?= e(url('/jogos')) ?>" class="back-link">← Catálogo</a>
        <div class="flex items-center gap-2 text-sm">
            <span class="mr-1 hidden text-xs text-slate-500 lg:inline">← → trocam de jogo · <kbd class="rounded border border-slate-50/15 px-1">E</kbd> edita · <kbd class="rounded border border-slate-50/15 px-1">N</kbd> nova nota</span>
            <a x-show="context.prev" x-cloak :href="context.prev && base + '/jogos/' + context.prev.id" class="rounded-lg border border-slate-50/10 px-3 py-1.5 text-slate-300 transition hover:bg-slate-50/5 hover:text-slate-50" :title="context.prev ? 'Zerado antes: ' + context.prev.title + ' (tecla ←)' : ''">← Anterior</a>
            <a x-show="context.next" x-cloak :href="context.next && base + '/jogos/' + context.next.id" class="rounded-lg border border-slate-50/10 px-3 py-1.5 text-slate-300 transition hover:bg-slate-50/5 hover:text-slate-50" :title="context.next ? 'Zerado depois: ' + context.next.title + ' (tecla →)' : ''">Próximo →</a>
        </div>
    </nav>

    <!-- ============ Destaque: capa, título, honras, os números principais e as ações ============ -->
    <header class="relative isolate mt-4 overflow-hidden rounded-3xl border bg-hero p-5 sm:p-8" :class="honored ? 'border-gold-400/40' : 'border-slate-50/10'">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="game.cover_url" :src="game.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-35 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/75 via-black/60 to-black/30"></div>
            <div x-show="honored" class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gold-400/20 blur-3xl"></div>
        </div>

        <!-- no celular a capa fica pequena ao lado do título; no computador, grande à esquerda -->
        <div class="grid grid-cols-[6.5rem_minmax(0,1fr)] gap-4 sm:grid-cols-[11rem_minmax(0,1fr)] sm:gap-6 md:grid-cols-[14rem_minmax(0,1fr)] md:gap-8">
            <div class="w-full sm:row-span-2">
                <div class="case" :class="honored && 'case-honor'">
                    <div class="case-band" :style="UI.bandStyle(game.platform)"><span x-text="game.platform"></span><span class="case-date hidden sm:inline" x-text="UI.fmtDate(game.finished_at)"></span></div>
                    <div class="case-art">
                        <img x-show="game.cover_url" :src="game.cover_url" :alt="'Capa de ' + game.title" class="absolute inset-0 h-full w-full object-cover">
                        <div x-show="!game.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-4 text-center">
                            <span class="text-3xl font-bold text-white/80 sm:text-5xl" x-text="UI.initials(game.title)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="min-w-0 self-center text-white sm:self-start">
                <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wider">
                    <a :href="filter('platform', game.platform)" class="rounded-full px-2.5 py-1 transition hover:brightness-110" :style="'background:var(--band-bg);color:var(--band-fg);' + UI.bandStyle(game.platform)" x-text="game.platform"></a>
                    <a x-show="game.genre" :href="filter('genre', game.genre)" class="rounded-full bg-white/10 px-2.5 py-1 normal-case tracking-normal text-white/85 transition hover:bg-white/20" x-text="game.genre"></a>
                    <span x-show="game.release_year" class="rounded-full bg-white/10 px-2.5 py-1 normal-case tracking-normal text-white/70" x-text="game.release_year"></span>
                </div>

                <h1 class="mt-3 text-2xl font-black leading-tight tracking-tight drop-shadow-lg sm:text-4xl lg:text-5xl" x-text="game.title"></h1>

                <!-- honras: GOTY e best of -->
                <ul x-show="context.honors.length" x-cloak class="mt-3 flex flex-wrap gap-2" aria-label="Hall da fama">
                    <template x-for="h in context.honors" :key="h.label">
                        <li><a :href="base + h.href" class="best-badge !py-1 !text-2xs transition hover:brightness-110"><span aria-hidden="true" x-text="h.kind === 'goty' ? '⭐' : '🏆'"></span> <span class="normal-case tracking-normal" x-text="h.label"></span></a></li>
                    </template>
                </ul>

                </div>
            <div class="col-span-2 flex min-w-0 flex-col text-white sm:col-span-1 sm:col-start-2">
                <!-- números principais -->
                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 lg:grid-cols-4">
                    <div>
                        <dt class="kicker-hero">Nota</dt>
                        <dd class="mt-1 flex items-baseline gap-1.5"><span class="grid h-11 min-w-11 place-items-center rounded-xl px-2 text-2xl font-black leading-none shadow-lg ring-1 ring-black/30" :class="UI.ratingClass(game.rating)" :style="UI.ratingStyle(game.rating)" x-text="game.rating"></span><span class="text-xs text-white/50">/ 11</span></dd>
                    </div>
                    <div>
                        <dt class="kicker-hero">Dificuldade</dt>
                        <dd class="mt-1.5"><span class="inline-block rounded-lg px-3 py-1.5 text-base font-black tracking-wide shadow ring-1 ring-black/30" :style="UI.diffStyle(game.difficulty)" x-text="game.difficulty"></span></dd>
                    </div>
                    <div>
                        <dt class="kicker-hero">Tempo de jogo</dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums" x-text="UI.fmtTime(game.playtime_minutes)"></dd>
                    </div>
                    <div>
                        <dt class="kicker-hero">Zerado em</dt>
                        <dd class="mt-1 text-lg font-bold" x-text="UI.fmtDate(game.finished_at)"></dd>
                        <dd x-show="game.started_at" x-cloak class="text-xs text-white/60" x-text="playedLabel()"></dd>
                    </div>
                </dl>

                <!-- o comentário escrito ao escolher o GOTY -->
                <blockquote x-show="gotyNote" x-cloak class="mt-5 border-l-2 border-gold-400/70 pl-4 text-sm italic leading-relaxed text-white/85" x-text="'“' + gotyNote + '”'"></blockquote>

                <!-- as duas curiosidades mais marcantes -->
                <ul x-show="context.facts.length" x-cloak class="mt-5 flex flex-wrap gap-2 text-sm">
                    <template x-for="f in context.facts.slice(0, 2)" :key="f.text">
                        <li class="flex items-center gap-2 rounded-full bg-white/10 py-1 pl-2 pr-3 text-white/85"><span aria-hidden="true" x-text="f.icon"></span><span x-text="f.text"></span></li>
                    </template>
                </ul>

                <!-- ações -->
                <div class="mt-auto flex flex-wrap items-center gap-2 pt-6">
                    <a href="<?= e($editUrl) ?>" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black transition hover:bg-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">✎ Editar</a>
                    <button type="button" @click="newNote()" class="rounded-xl border border-white/25 bg-black/30 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10">📝 Nova nota</button>
                    <button type="button" @click="replay()" :disabled="replaying" class="rounded-xl border border-white/25 bg-black/30 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10 disabled:opacity-60" x-text="replaying ? '…' : '🔁 Jogar de novo'"></button>
                    <div class="relative" @click.outside="menuOpen = false" @keydown.escape="menuOpen && (menuOpen = false, $refs.menuBtn.focus())">
                        <button type="button" x-ref="menuBtn" @click="menuOpen = !menuOpen; menuOpen && $nextTick(() => $refs.menu.querySelector('button')?.focus())" :aria-expanded="menuOpen" aria-haspopup="menu" aria-label="Mais ações" class="grid h-9 w-9 place-items-center rounded-xl border border-white/25 bg-black/30 text-white backdrop-blur transition hover:bg-white/10">⋯</button>
                        <div x-show="menuOpen" x-cloak x-ref="menu" role="menu" @keydown="menuKeys($event)" class="absolute bottom-11 left-0 z-pop w-56 overflow-hidden rounded-xl border border-slate-50/15 bg-slate-900 py-1 text-sm shadow-2xl sm:left-auto sm:right-0">
                            <button type="button" role="menuitem" @click="downloadCard(); menuOpen = false" class="block w-full px-4 py-2 text-left text-slate-200 hover:bg-slate-50/10 focus:bg-slate-50/10 focus:outline-none">🖼 Baixar o cartão do jogo</button>
                            <button type="button" role="menuitem" @click="copyLink()" class="block w-full px-4 py-2 text-left text-slate-200 hover:bg-slate-50/10 focus:bg-slate-50/10 focus:outline-none">🔗 Copiar link</button>
                            <button type="button" role="menuitem" @click="menuOpen = false; confirming = true" class="block w-full border-t border-slate-50/10 px-4 py-2 text-left text-danger hover:bg-danger/10 focus:bg-danger/10 focus:outline-none">🗑 Excluir…</button>
                        </div>
                    </div>
                </div>
                <p x-show="notice" x-cloak role="status" class="mt-3 text-sm text-ok" x-html="notice"></p>
            </div>
        </div>
    </header>

    <!-- ============ Linha do tempo do jogo: começo, notas do diário e o fim ============ -->
    <section x-show="timeline.points.length > 2 || (timeline.points.length === 2 && timeline.span > 0)" x-cloak class="mt-6 panel p-5" aria-labelledby="h-tl">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="h-tl" class="text-sm font-semibold uppercase tracking-wider text-slate-400">A jornada</h2>
            <p class="text-xs text-slate-500" x-text="timeline.label"></p>
        </div>
        <div class="relative mx-3 mt-8 h-10" role="list" aria-label="Momentos da jornada">
            <div class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-gradient-to-r from-brand-600/50 via-brand-500/60 to-ok/70" aria-hidden="true"></div>
            <template x-for="(p, i) in timeline.points" :key="i">
                <button type="button" role="listitem" @click="openPoint(p)" @mouseenter="tlHover = p" @mouseleave="tlHover = null" @focus="tlHover = p" @blur="tlHover = null"
                        class="absolute top-1/2 grid -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full text-sm shadow-lg ring-2 ring-slate-900 transition hover:scale-125 focus:scale-125 focus:outline-none focus-visible:ring-brand-400"
                        :class="p.kind === 'note' ? 'h-7 w-7 bg-brand-500 text-white' : p.kind === 'end' ? 'h-9 w-9 bg-ok text-black' : 'h-8 w-8 bg-slate-200 text-black'"
                        :style="'left:' + p.pos + '%'" :aria-label="p.title + ', ' + UI.fmtDate(p.date)" x-text="p.icon"></button>
            </template>
        </div>
        <div class="mt-4 min-h-[3rem] rounded-xl bg-slate-950/40 px-4 py-3 text-sm" aria-live="polite">
            <template x-if="tlHover || tlSel">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-brand-400" x-text="(tlHover ?? tlSel).title + ' · ' + UI.fmtDate((tlHover ?? tlSel).date)"></p>
                    <p x-show="(tlHover ?? tlSel).body" class="mt-1 line-clamp-3 text-slate-200" x-text="(tlHover ?? tlSel).body"></p>
                    <p x-show="(tlHover ?? tlSel).shots" class="mt-1 text-xs text-slate-400" x-text="(tlHover ?? tlSel).shots ? '🖼 ' + (tlHover ?? tlSel).shots + ((tlHover ?? tlSel).shots === 1 ? ' imagem (clique para ver)' : ' imagens (clique para ver)') : ''"></p>
                </div>
            </template>
            <p x-show="!tlHover && !tlSel" class="text-slate-500">Passe o mouse (ou toque) nos pontos para ver cada momento.</p>
        </div>
    </section>

    <!-- ============ Corpo: ficha à esquerda, histórias à direita (no celular, as histórias vêm primeiro) ============ -->
    <div class="mt-6 grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">

        <!-- coluna da ficha (fixa ao rolar no computador) -->
        <aside class="order-2 space-y-4 lg:sticky lg:top-6 lg:order-1 lg:self-start">
            <!-- na sua história -->
            <section class="panel p-5" aria-labelledby="h-story">
                <h2 id="h-story" class="kicker">Na sua história</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a :href="base + '/retrospectiva#' + context.story.year" class="group flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50/5">
                        <span class="text-slate-200"><span aria-hidden="true">📅 </span><span x-text="context.story.order + 'º de ' + context.story.count + ' jogos zerados em ' + context.story.year"></span></span><span class="shrink-0 text-brand-400 opacity-0 transition group-hover:opacity-100" aria-hidden="true">→</span></a></li>
                    <li x-show="context.story.count > 1"><a :href="base + '/retrospectiva#' + context.story.year" class="group flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50/5">
                        <span class="text-slate-200"><span aria-hidden="true">⏳ </span><span x-text="(context.story.longest_rank === 1 ? 'O mais longo' : 'O ' + context.story.longest_rank + 'º mais longo') + ' do ano'"></span></span></a></li>
                    <li><a :href="base + '/calor#' + context.story.year" class="group flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50/5">
                        <span class="text-slate-200"><span aria-hidden="true">🔥 </span><span x-text="context.story.month_games > 1 ? 'Um dos ' + context.story.month_games + ' zerados em ' + monthName : 'O único zerado em ' + monthName"></span></span><span class="shrink-0 text-brand-400 opacity-0 transition group-hover:opacity-100" aria-hidden="true">→</span></a></li>
                    <li x-show="game.release_year"><a :href="base + '/goty#' + game.release_year" class="group flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50/5">
                        <span class="text-slate-200"><span aria-hidden="true">⭐ </span><span x-text="gotyHonor ? 'O seu GOTY de ' + game.release_year : 'Concorre ao GOTY de ' + game.release_year"></span></span><span class="shrink-0 text-brand-400 opacity-0 transition group-hover:opacity-100" aria-hidden="true">→</span></a></li>
                    <li x-show="context.story.milestone" x-cloak class="flex items-center gap-2 rounded-lg bg-gold-400/10 px-2 py-1.5 text-gold-300"><span aria-hidden="true">🎖</span><span class="font-semibold" x-text="context.story.milestone === 1 ? 'O primeiro jogo que você zerou no catálogo' : 'O seu ' + context.story.milestone + 'º jogo zerado'"></span></li>
                    <li><a :href="base + '/timeline'" class="group flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50/5">
                        <span class="text-slate-200"><span aria-hidden="true">🧭 </span>Ver na timeline</span></a></li>
                </ul>
            </section>

            <!-- comparação com o catálogo -->
            <section x-show="context.compare.length" x-cloak class="panel p-5" aria-labelledby="h-cmp">
                <h2 id="h-cmp" class="kicker">Comparado aos outros</h2>
                <div class="mt-3 space-y-4">
                    <template x-for="c in context.compare" :key="c.kind">
                        <div>
                            <p class="text-sm font-semibold text-slate-200" x-text="c.kind + ': ' + c.name"></p>
                            <p class="text-2xs text-slate-500" x-text="c.n + ' jogos no seu catálogo'"></p>
                            <!-- tempo: este jogo × a média -->
                            <div class="mt-2 text-xs text-slate-400">
                                <div class="flex justify-between"><span>Tempo</span><span x-text="timeVs(c)"></span></div>
                                <div class="relative mt-1 h-2 rounded-full bg-slate-50/10">
                                    <span class="absolute inset-y-0 left-0 rounded-full bg-brand-500" :style="'width:' + Math.min(100, game.playtime_minutes / Math.max(1, c.max_minutes) * 100) + '%'"></span>
                                    <span class="absolute -top-1 h-4 w-0.5 bg-gold-400" :style="'left:' + Math.min(100, c.avg_minutes / Math.max(1, c.max_minutes) * 100) + '%'" title="média"></span>
                                </div>
                            </div>
                            <!-- nota: este jogo × a média (escala de 1 a 11) -->
                            <div class="mt-2 text-xs text-slate-400">
                                <div class="flex justify-between"><span>Nota</span><span x-text="ratingVs(c)"></span></div>
                                <div class="relative mt-1 h-2 rounded-full bg-slate-50/10">
                                    <span class="absolute inset-y-0 left-0 rounded-full" :class="game.rating >= c.avg_rating ? 'bg-ok' : 'bg-gold-500'" :style="'width:' + (game.rating / 11 * 100) + '%'"></span>
                                    <span class="absolute -top-1 h-4 w-0.5 bg-gold-400" :style="'left:' + (c.avg_rating / 11 * 100) + '%'" title="média"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <p class="text-2xs text-slate-500"><span class="mr-1 inline-block h-3 w-0.5 bg-gold-400 align-middle"></span>a marca é a média</p>
                </div>
            </section>

            <!-- no seu catálogo (as demais curiosidades) -->
            <section x-show="context.facts.length > 2" x-cloak class="panel p-5" aria-labelledby="h-facts">
                <h2 id="h-facts" class="kicker">Curiosidades</h2>
                <ul class="mt-3 space-y-2.5">
                    <template x-for="f in context.facts.slice(2)" :key="f.text">
                        <li class="flex gap-3 text-sm leading-snug text-slate-200"><span class="shrink-0 text-base leading-none" aria-hidden="true" x-text="f.icon"></span><span x-text="f.text"></span></li>
                    </template>
                </ul>
            </section>

            <!-- ficha técnica -->
            <section class="panel p-5" aria-labelledby="h-sheet">
                <h2 id="h-sheet" class="kicker">Ficha</h2>
                <dl class="mt-3 divide-y divide-slate-50/10 text-sm">
                    <template x-for="r in sheet" :key="r.k">
                        <div class="flex items-baseline justify-between gap-4 py-2">
                            <dt class="shrink-0 text-slate-400" x-text="r.k"></dt>
                            <dd class="min-w-0 text-right">
                                <template x-if="r.v"><a :href="r.href" class="font-medium text-slate-100 hover:text-brand-400 hover:underline" x-text="r.v"></a></template>
                                <template x-if="!r.v"><a href="<?= e($editUrl) ?>" class="text-xs font-semibold text-gold-500 hover:underline">+ adicionar</a></template>
                            </dd>
                        </div>
                    </template>
                    <div x-show="game.save_states" x-cloak class="flex items-baseline justify-between gap-4 py-2">
                        <dt class="text-slate-400">Save state</dt><dd class="text-right text-slate-200">Usou<span class="xp-ui text-xs text-slate-500"> · XP pela metade</span></dd>
                    </div>
                </dl>
                <p x-show="context.missing.length" x-cloak class="mt-3 rounded-lg border border-gold-400/30 bg-gold-400/10 px-3 py-2 text-xs text-slate-200">
                    Faltam: <strong x-text="context.missing.join(', ')"></strong>.
                    <a href="<?= e($editUrl) ?>" class="font-semibold text-gold-500 hover:underline">Completar →</a>
                </p>
            </section>

            <!-- franquias -->
            <section x-show="franchises.length" x-cloak class="panel p-5" aria-labelledby="h-fr">
                <h2 id="h-fr" class="kicker">Faz parte de</h2>
                <ul class="mt-3 space-y-2.5">
                    <template x-for="f in franchises" :key="f.id">
                        <li>
                            <a :href="base + '/franquias/' + f.id" class="group block rounded-xl border border-slate-50/10 bg-slate-950/40 p-3 transition hover:border-brand-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate font-bold text-slate-50 group-hover:text-brand-400" x-text="f.name"></p>
                                    <span class="shrink-0 text-xs tabular-nums" :class="f.total && f.done === f.total ? 'font-bold text-ok' : 'text-slate-400'" x-text="f.done + '/' + f.total"></span>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-50/10" aria-hidden="true">
                                    <div class="h-full rounded-full" :class="f.total && f.done === f.total ? 'bg-ok' : 'bg-gradient-to-r from-brand-600 to-accent'" :style="`width:${f.total ? f.done / f.total * 100 : 0}%`"></div>
                                </div>
                                <p class="mt-1.5 truncate text-2xs text-slate-500" x-text="f.sections.join(' · ')"></p>
                            </a>
                        </li>
                    </template>
                </ul>
            </section>

            <!-- XP (recolhido: é detalhe) -->
            <details class="xp-ui panel p-5">
                <summary class="flex cursor-pointer list-none items-center justify-between text-sm">
                    <span class="font-semibold text-slate-300">XP desta finalização</span>
                    <span class="text-lg font-bold text-brand-400">+<span x-text="game.xp"></span> XP</span>
                </summary>
                <ul x-show="events.length" class="mt-3 space-y-1 border-t border-slate-50/10 pt-3 text-sm">
                    <template x-for="(ev, i) in events" :key="i">
                        <li class="flex justify-between gap-3 text-slate-400"><span x-text="ev.description"></span><span class="tabular-nums text-slate-200" x-text="'+' + ev.amount"></span></li>
                    </template>
                </ul>
                <p x-show="!events.length" class="mt-3 border-t border-slate-50/10 pt-3 text-xs text-slate-500">O detalhamento aparece para os jogos registrados com o XP calculado na hora (base, dificuldade e tarefas). Edite e salve o jogo para recalcular.</p>
            </details>
        </aside>

        <!-- coluna das histórias -->
        <div class="order-1 min-w-0 lg:order-2">
            <!-- memórias -->
            <section x-show="hasMemories" x-cloak class="panel p-5" aria-labelledby="h-mem">
                <h2 id="h-mem" class="t-section">Memórias</h2>
                <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                    <template x-for="m in memoryItems" :key="m.k">
                        <div class="rounded-xl bg-slate-950/40 p-3.5" :class="m.wide && 'sm:col-span-2'">
                            <dt class="kicker flex items-center gap-1.5"><span aria-hidden="true" x-text="m.icon"></span><span x-text="m.k"></span></dt>
                            <dd class="mt-1 text-slate-100">
                                <template x-if="m.k === 'Música do momento' && game.memory.music_url"><a :href="game.memory.music_url" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-400 hover:underline" x-text="'♪ ' + m.v"></a></template>
                                <template x-if="m.k === 'Com quem jogou'"><span class="flex flex-wrap gap-2"><template x-for="p in game.memory.people" :key="p"><a :href="base + '/pessoas'" class="rounded-full bg-brand-500/20 px-3 py-0.5 text-sm hover:bg-brand-500/30" x-text="p"></a></template></span></template>
                                <template x-if="!(m.k === 'Música do momento' && game.memory.music_url) && m.k !== 'Com quem jogou'"><span x-text="m.v"></span></template>
                            </dd>
                        </div>
                    </template>
                </dl>
            </section>
            <a x-show="!hasMemories" x-cloak href="<?= e($editUrl) ?>" class="flex items-center justify-between gap-3 rounded-2xl border border-dashed border-slate-50/15 px-5 py-4 text-sm text-slate-400 transition hover:border-brand-400/50 hover:text-slate-200">
                <span>💭 Quem te apresentou este jogo? Onde jogou, com quem, que música tocava? Guarde as memórias.</span>
                <span class="shrink-0 font-semibold text-brand-400">Adicionar →</span>
            </a>

            <!-- tarefas extras -->
            <section x-show="game.tasks.length" x-cloak class="mt-4 panel p-5" aria-labelledby="h-tasks">
                <h2 id="h-tasks" class="t-section">Tarefas extras</h2>
                <ul class="mt-3 space-y-2">
                    <template x-for="t in game.tasks" :key="t.id">
                        <li class="flex items-center gap-3 text-sm">
                            <span class="shrink-0 rounded-md bg-brand-500/20 px-2 py-0.5 text-xs font-bold text-brand-400" x-text="'Tier ' + t.tier"></span>
                            <span class="text-slate-200" x-text="t.description"></span>
                        </li>
                    </template>
                </ul>
            </section>

            <!-- diário de bordo -->
            <?php $diaryKind = 'games'; $diaryId = (int) $game['id']; $diaryHint = 'Notas datadas sobre a sua jornada neste jogo. Fica só com você.'; require __DIR__ . '/partials/diary.php'; ?>

            <!-- screenshots -->
            <?php $shotsKind = 'games'; $shotsId = (int) $game['id']; require __DIR__ . '/partials/shots.php'; require __DIR__ . '/partials/lightbox.php'; ?>
        </div>
    </div>

    <!-- ============ Relacionados: da franquia, da desenvolvedora e do sistema ============ -->
    <section x-show="context.related.length" x-cloak class="mt-10" aria-labelledby="h-rel">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="h-rel" class="t-section"><span>Você também zerou</span><span x-show="context.related.length === 1" class="font-normal text-slate-400" x-text="context.related[0] ? ' · ' + relLabel(context.related[0]) : ''"></span></h2>
            <!-- só vira abas quando há mais de um grupo -->
            <div x-show="context.related.length > 1" class="flex flex-wrap gap-1.5" role="tablist" aria-label="Relacionados por" @keydown="tabKeys($event)">
                <template x-for="r in context.related" :key="r.key">
                    <button type="button" role="tab" :id="'reltab-' + r.key" :aria-controls="'relpanel-' + r.key" :aria-selected="relTab === r.key" :tabindex="relTab === r.key ? 0 : -1" @click="relTab = r.key" class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                            :class="relTab === r.key ? 'bg-brand-600 text-on-brand' : 'bg-slate-50/5 text-slate-300 hover:bg-slate-50/10'" x-text="relLabel(r)"></button>
                </template>
            </div>
        </div>
        <template x-for="r in context.related" :key="'list-' + r.key">
            <div x-show="relTab === r.key" class="mt-4" :id="'relpanel-' + r.key" :role="context.related.length > 1 ? 'tabpanel' : null" :aria-labelledby="context.related.length > 1 ? 'reltab-' + r.key : null">
                <ul class="flex gap-4 overflow-x-auto pb-3">
                    <template x-for="g in r.games" :key="g.id">
                        <li class="w-28 shrink-0">
                            <a :href="base + '/jogos/' + g.id" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                <span class="relative block aspect-[16/25] overflow-hidden rounded-lg bg-slate-950 shadow-lg ring-1 ring-slate-50/10 transition group-hover:-translate-y-1 group-hover:ring-brand-400/60 motion-reduce:group-hover:translate-y-0">
                                    <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-xl font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                    <span class="absolute left-1.5 top-1.5 grid h-6 min-w-6 place-items-center rounded-md px-1 text-xs font-black leading-none shadow ring-1 ring-black/40" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                                </span>
                                <span class="mt-1.5 block truncate text-xs font-semibold text-slate-200 group-hover:text-brand-400" :title="g.title" x-text="g.title"></span>
                            </a>
                        </li>
                    </template>
                </ul>
                <a :href="base + r.href" class="text-sm font-semibold text-brand-400 hover:underline" x-text="'Ver todos de ' + r.label + ' →'"></a>
            </div>
        </template>
    </section>

    <!-- ============ Anterior / próximo, com capa ============ -->
    <nav x-show="context.prev || context.next" x-cloak class="mt-10 grid gap-3 sm:grid-cols-2" aria-label="Jogo anterior e próximo">
        <template x-for="n in [{ d: 'prev', g: context.prev }, { d: 'next', g: context.next }]" :key="n.d">
            <div>
                <a x-show="n.g" :href="n.g && base + '/jogos/' + n.g.id" class="group flex items-center gap-3 panel p-3 transition hover:border-brand-400/60" :class="n.d === 'next' && 'sm:flex-row-reverse sm:text-right'">
                    <span class="relative block aspect-[16/25] w-12 shrink-0 overflow-hidden rounded bg-slate-950 ring-1 ring-black/40">
                        <img x-show="n.g?.cover_url" :src="n.g?.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs text-slate-500" x-text="n.d === 'prev' ? '← Zerado antes' : 'Zerado depois →'"></span>
                        <span class="block truncate font-semibold text-slate-100 group-hover:text-brand-400" x-text="n.g?.title"></span>
                        <span class="block text-xs text-slate-500" x-text="n.g ? UI.fmtDate(n.g.finished_at) : ''"></span>
                    </span>
                </a>
            </div>
        </template>
    </nav>

    <!-- Confirmação de exclusão -->
    <div x-show="confirming" x-cloak @keydown.escape.window="confirming = false" role="alertdialog" aria-modal="true" aria-labelledby="del-title"
         class="modal-back" @click.self="confirming = false">
        <div class="modal-box">
            <h2 id="del-title" class="t-section">Excluir este jogo?</h2>
            <p class="mt-2 text-sm text-slate-400">
                <strong class="text-slate-200" x-text="game.title"></strong> será removido do catálogo, junto com a capa, as notas do diário e as screenshots.
                Você perde os <strong class="text-slate-200" x-text="game.xp"></strong> XP que ele rendeu. Isso não pode ser desfeito.
            </p>
            <p x-show="error" x-cloak role="alert" class="mt-3 text-sm text-danger" x-text="error"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-ref="cancel" @click="confirming = false" class="btn-ghost">Cancelar</button>
                <button type="button" @click="remove()" :disabled="deleting" class="rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60" x-text="deleting ? 'Excluindo…' : 'Sim, excluir'"></button>
            </div>
        </div>
    </div>
</main>

<script>
function gameShow({ game, events, franchises, context }) {
    const MONTHS = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    const day = (s) => Date.UTC(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)) / 864e5;
    return {
        game, events, franchises, context,
        base: window.APP.base,
        relTab: context.related[0]?.key ?? null,
        confirming: false,
        deleting: false,
        error: '',
        menuOpen: false, notice: '', replaying: false,
        notes: [], shots: [], tlHover: null, tlSel: null,

        get honored() { return this.context.honors.length > 0; },
        get gotyNote() { return this.context.honors.find((h) => h.kind === 'goty' && h.note)?.note ?? ''; },
        get gotyHonor() { return this.context.honors.some((h) => h.kind === 'goty'); },
        get monthName() { return MONTHS[+this.game.finished_at.slice(5, 7) - 1] + ' de ' + this.game.finished_at.slice(0, 4); },

        /** Link para o catálogo já filtrado (ex.: todos os jogos do mesmo sistema). */
        filter(param, value) { return `${this.base}/jogos?${param}=${encodeURIComponent(value)}`; },

        /** Linhas da ficha; as vazias viram "+ adicionar". */
        get sheet() {
            const g = this.game;
            return [
                { k: 'Sistema', v: g.platform, href: this.filter('platform', g.platform) },
                { k: 'Gênero', v: g.genre, href: g.genre && this.filter('genre', g.genre) },
                { k: 'Lançamento', v: g.release_year, href: g.release_year && this.filter('release_year', g.release_year) },
                { k: 'Desenvolvedora', v: g.developer, href: g.developer && this.filter('developer', g.developer) },
                { k: 'Publisher', v: g.publisher, href: g.publisher && this.filter('publisher', g.publisher) },
            ];
        },

        get hasMemories() { const m = this.game.memory; return !!m && !!(m.intro_by || m.intro_where || m.intro_age !== null || m.played_where || m.music_title || m.people.length); },
        get memoryItems() {
            const m = this.game.memory;
            return [
                m.intro_by && { k: 'Quem apresentou', v: m.intro_by, icon: '🤝' },
                m.intro_where && { k: 'Onde conseguiu', v: m.intro_where, icon: '🛍' },
                m.intro_age !== null && { k: 'Idade na época', v: m.intro_age + ' anos', icon: '🎂' },
                m.played_where && { k: 'Onde jogou', v: m.played_where, icon: '🏠' },
                m.music_title && { k: 'Música do momento', v: m.music_title, icon: '🎵', wide: true },
                m.people.length && { k: 'Com quem jogou', v: '', icon: '👥', wide: true },
            ].filter(Boolean);
        },

        relLabel(r) { return { franchise: '🧩 ', developer: '🏢 ', platform: '🎮 ' }[r.key] + r.label; },
        /** setas entre as abas dos relacionados */
        tabKeys(e) {
            const keys = this.context.related.map((r) => r.key), i = keys.indexOf(this.relTab);
            const d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
            if (!d) return;
            e.preventDefault();
            this.relTab = keys[(i + d + keys.length) % keys.length];
            this.$nextTick(() => document.getElementById('reltab-' + this.relTab)?.focus());
        },

        /** "Começou e zerou no mesmo dia" ou "em 39 dias (de 03/10 a 12/11/2026)". */
        playedLabel() {
            const a = this.game.started_at, b = this.game.finished_at;
            if (!a) return '';
            const d = Math.round(day(b) - day(a));
            if (d <= 0) return 'Começou e zerou no mesmo dia';
            return 'em ' + d + (d === 1 ? ' dia' : ' dias') + ' (de ' + UI.fmtDate(a).slice(0, 5) + ' a ' + UI.fmtDate(b) + ')';
        },

        // ---------- comparação ----------
        timeVs(c) {
            const m = this.game.playtime_minutes, avg = c.avg_minutes;
            if (!avg) return '';
            const r = m / avg;
            return UI.fmtTime(m) + ' · ' + (Math.abs(r - 1) < 0.1 ? 'na média' : r > 1 ? (r).toFixed(1).replace('.', ',') + '× a média' : 'abaixo da média (' + UI.fmtTime(avg) + ')');
        },
        ratingVs(c) {
            const d = this.game.rating - c.avg_rating;
            return this.game.rating + ' · ' + (Math.abs(d) < 0.5 ? 'na média (' + String(c.avg_rating).replace('.', ',') + ')' : (d > 0 ? '+' : '−') + Math.abs(d).toFixed(1).replace('.', ',') + ' da média');
        },

        // ---------- linha do tempo (começo → notas do diário → fim) ----------
        get timeline() {
            const g = this.game, pts = [];
            if (g.started_at) pts.push({ kind: 'start', icon: '▶', title: 'Começou a jogar', date: g.started_at });
            const shotsByNote = {};
            for (const s of this.shots) if (s.note_id) (shotsByNote[s.note_id] ??= []).push(s);
            for (const n of this.notes) pts.push({ kind: 'note', icon: shotsByNote[n.id]?.length ? '🖼' : '📝', title: 'Nota do diário', date: n.note_date, body: n.body, note: n, shots: shotsByNote[n.id]?.length ?? 0, shotList: shotsByNote[n.id] ?? [] });
            pts.push({ kind: 'end', icon: '🏁', title: 'Zerou', date: g.finished_at });
            pts.sort((a, b) => a.date.localeCompare(b.date) || (a.kind === 'end') - (b.kind === 'end'));
            const first = day(pts[0].date), last = day(pts[pts.length - 1].date), span = last - first;
            for (const p of pts) p.pos = span > 0 ? (day(p.date) - first) / span * 100 : (p.kind === 'end' ? 100 : 0);
            const label = span > 0 ? span + (span === 1 ? ' dia' : ' dias') + ' · ' + this.notes.length + (this.notes.length === 1 ? ' nota' : ' notas') : '';
            return { points: pts, span, label };
        },
        openPoint(p) {
            this.tlSel = p;
            if (p.shotList?.length) {
                window.dispatchEvent(new CustomEvent('lightbox', { detail: { index: 0, items: p.shotList.map((s) => ({ url: s.url, caption: s.caption, meta: 'Diário · ' + UI.fmtDate(p.date) })) } }));
            }
        },

        // ---------- ações ----------
        newNote() { window.dispatchEvent(new CustomEvent('diary-new')); },
        /** "Jogar de novo": manda para a fila do backlog. */
        async replay() {
            this.replaying = true; this.notice = '';
            try {
                await api.post('/api/backlog', { title: this.game.title, platform: this.game.platform, genre: this.game.genre ?? '', release_year: this.game.release_year ?? '', status: 'want', note: 'Rejogar' });
                this.notice = `🔁 Foi para a fila do backlog. <a class="font-semibold underline" href="${this.base}/backlog">Ver o backlog →</a>`;
            } catch (e) {
                this.notice = e.status === 409
                    ? `Já está no seu backlog. <a class="font-semibold underline" href="${this.base}/backlog">Ver o backlog →</a>`
                    : '<span class="text-danger">' + (Object.values(e.errors || {})[0] || e.message).replace(/</g, '&lt;') + '</span>';
            }
            this.replaying = false;
        },
        async copyLink() {
            this.menuOpen = false;
            try { await navigator.clipboard.writeText(location.href); this.notice = '🔗 Link copiado.'; } catch { this.notice = 'Não deu para copiar: ' + location.href; }
        },
        menuKeys(e) {
            const items = [...e.currentTarget.querySelectorAll('[role=menuitem]')], i = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
            if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
            if (e.key === 'Tab') this.menuOpen = false;
        },

        /** Cartão do jogo: imagem gerada aqui no navegador (capa, nota, dificuldade, tempo, data e honras). */
        async downloadCard() {
            const g = this.game, p = Theme.palette(), W = 1080, H = 1350;
            const cv = document.createElement('canvas'); cv.width = W; cv.height = H;
            const c = cv.getContext('2d');
            const font = (w, s) => `${w} ${s}px ${p.font}`;
            const cover = await new Promise((res) => { if (!g.cover_url) return res(null); const im = new Image(); im.onload = () => res(im); im.onerror = () => res(null); im.src = g.cover_url; });
            c.fillStyle = '#0c0c12'; c.fillRect(0, 0, W, H);
            if (cover) { c.save(); c.filter = 'blur(60px) brightness(.45)'; c.drawImage(cover, -200, -200, W + 400, H + 400); c.restore(); }
            const grad = c.createLinearGradient(0, 0, 0, H); grad.addColorStop(0, 'rgba(0,0,0,.2)'); grad.addColorStop(1, 'rgba(0,0,0,.85)');
            c.fillStyle = grad; c.fillRect(0, 0, W, H);
            // capa
            const cw = 520, ch = 740, cx = (W - cw) / 2, cy = 110;
            c.save(); c.shadowColor = 'rgba(0,0,0,.6)'; c.shadowBlur = 50; c.fillStyle = '#1e1e28'; c.fillRect(cx, cy, cw, ch); c.restore();
            if (cover) { const s = Math.max(cw / cover.width, ch / cover.height), sw = cw / s, sh = ch / s; c.drawImage(cover, (cover.width - sw) / 2, (cover.height - sh) / 2, sw, sh, cx, cy, cw, ch); }
            else { c.fillStyle = '#fff'; c.font = font('800', 120); c.textAlign = 'center'; c.fillText(UI.initials(g.title), W / 2, cy + ch / 2 + 40); }
            if (this.honored) { c.strokeStyle = '#fbbf24'; c.lineWidth = 10; c.strokeRect(cx - 5, cy - 5, cw + 10, ch + 10); }
            c.textAlign = 'center';
            c.fillStyle = 'rgba(255,255,255,.6)'; c.font = font('700', 28); c.fillText((g.platform + (g.release_year ? ' · ' + g.release_year : '')).toUpperCase(), W / 2, 920);
            // título (até 2 linhas)
            c.fillStyle = '#fff'; c.font = font('800', 64);
            const words = g.title.split(' '), lines = []; let line = '';
            for (const w of words) { if (c.measureText(line + w).width > W - 140 && line) { lines.push(line.trim()); line = ''; } line += w + ' '; }
            lines.push(line.trim());
            lines.slice(0, 2).forEach((l, i) => c.fillText(l, W / 2, 1000 + i * 72));
            // números
            const y = lines.length > 1 ? 1160 : 1100;
            const stats = [['Nota', g.rating + '/11'], ['Dificuldade', g.difficulty], ['Tempo', UI.fmtTime(g.playtime_minutes)], ['Zerado', UI.fmtDate(g.finished_at)]];
            stats.forEach(([k, v], i) => {
                const x = 135 + i * 270;
                c.fillStyle = i === 0 ? (UI.RATING_COLORS[g.rating] ?? '#fbbf24') : '#fff'; c.font = font('800', 44); c.fillText(v, x, y + 50);
                c.fillStyle = 'rgba(255,255,255,.55)'; c.font = font('600', 24); c.fillText(k, x, y);
            });
            if (this.context.honors.length) { c.fillStyle = '#fbbf24'; c.font = font('700', 30); c.fillText('🏆 ' + this.context.honors.map((h) => h.label).join(' · '), W / 2, y + 120); }
            c.fillStyle = 'rgba(255,255,255,.4)'; c.font = font('700', 22); c.fillText('MYGAMELIST', W / 2, H - 40);
            cv.toBlob((blob) => {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = g.title.replace(/[^\p{L}\p{N}]+/gu, '-').toLowerCase() + '.png';
                a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 2000);
            }, 'image/png');
        },

        /** Setas ← → trocam de jogo; E edita; N abre uma nova nota (fora de campos de texto e janelas abertas). */
        navKey(e) {
            if (this.confirming || e.altKey || e.ctrlKey || e.metaKey || /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName) || e.target.isContentEditable) return;
            if (document.querySelector('[role=dialog]:not([style*="display: none"])')) return;
            if (e.key === 'e' || e.key === 'E') { location.href = this.base + '/jogos/' + this.game.id + '/editar'; return; }
            if (e.key === 'n' || e.key === 'N') { e.preventDefault(); this.newNote(); return; }
            const to = e.key === 'ArrowLeft' ? this.context.prev : e.key === 'ArrowRight' ? this.context.next : null;
            if (to) location.href = this.base + '/jogos/' + to.id;
        },

        init() {
            this.$watch('confirming', (v) => { if (v) this.$nextTick(() => this.$refs.cancel?.focus()); });
            // o diário e a galeria avisam quando carregam/mudam: a linha do tempo usa os mesmos dados
            window.addEventListener('notes-changed', (e) => { if (e.detail?.kind === 'games') this.notes = e.detail.notes; });
            window.addEventListener('shots-loaded', (e) => { if (e.detail?.kind === 'games') this.shots = e.detail.shots; });
        },

        async remove() {
            this.deleting = true;
            this.error = '';
            try {
                await api.del('/api/games/' + this.game.id);
                location.href = this.base + '/jogos';
            } catch (e) {
                this.error = e.message;
                this.deleting = false;
            }
        },
    };
}
</script>
