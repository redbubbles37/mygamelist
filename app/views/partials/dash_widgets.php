<?php
/** Blocos de história do painel. Usam o estado Alpine de views/home.php (mesclado com public/assets/js/dashboard-extra.js). */

/** Cartão de um jogo de "Hoje na sua história" (usado na lista e na janela com todos). */
$todayCard = function (): void { ?>
                        <a :href="isDemo ? null : base + '/jogos/' + g.id" class="group flex items-center gap-3 rounded-xl border border-slate-50/10 bg-slate-950/40 p-2.5 transition hover:border-brand-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <span class="relative block aspect-[16/25] w-16 shrink-0 overflow-hidden rounded bg-slate-950 ring-1 ring-black/40">
                                <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-lg font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                <span class="absolute left-1 top-1 grid h-5 min-w-5 place-items-center rounded px-1 text-2xs font-black leading-none ring-1 ring-black/40" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" :title="'Nota ' + g.rating" x-text="g.rating"></span>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-bold leading-snug text-slate-50 group-hover:text-brand-400" x-text="g.title"></span>
                                <span class="block text-xs text-slate-400" x-text="g.platform"></span>
                                <span class="mt-1.5 block text-xs font-semibold text-brand-400" x-text="yearsAgo(g.years_ago) + ' · ' + UI.fmtDate(g.finished_at)"></span>
                                <span class="block text-xs text-slate-400" x-text="seasonLabel(g)"></span>
                            </span>
                        </a>
<?php };
?>

<!-- ============ Estante: jogando agora (em cima da mesa) e últimos zerados ============ -->
<section :style="'order:' + pos('shelf')" x-show="vis('shelf')" x-cloak :class="cell('shelf')" aria-labelledby="h-shelf">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="h-shelf" class="t-section">Na sua estante</h2>
        <span class="flex gap-4 text-xs font-semibold">
            <a :href="base + (isDemo ? '/backlog/exemplo' : '/backlog')" class="text-brand-400 hover:underline">Backlog →</a>
            <a :href="base + (isDemo ? '/jogos/exemplo' : '/jogos')" class="text-brand-400 hover:underline">Catálogo →</a>
        </span>
    </div>
    <div class="mt-3 grid gap-4">
        <!-- em cima da mesa: até 4 jogos ao mesmo tempo -->
        <div x-show="extra" x-cloak class="dtable panel p-4">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Jogando agora</p>
                <span class="flex items-center gap-2 text-xs font-semibold text-slate-300" :aria-label="(extra?.playing.length ?? 0) + ' de ' + (extra?.playing_max ?? 4) + ' vagas ocupadas'">
                    <span class="xp-ui tabular-nums" x-text="(extra?.playing.length ?? 0) + '/' + (extra?.playing_max ?? 4)"></span>
                </span>
            </div>
            <ul class="dtable-grid mt-3" aria-label="Jogos em andamento">
                <template x-for="g in (extra?.playing ?? [])" :key="g.id">
                    <li class="relative">
                        <a :href="base + (isDemo ? '/backlog/exemplo' : '/backlog/' + g.id + '/editar')" class="dtable-tile group focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <span class="dtable-cover is-tile">
                                <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                <span x-show="!g.cover_url" class="grid h-full w-full place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-lg font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                            </span>
                            <span class="dtable-text">
                                <span class="dtable-title group-hover:text-brand-400" x-text="g.title"></span>
                                <span class="dtable-sub" x-text="g.platform || 'Sem sistema'"></span>
                                <span class="dtable-days" x-text="g.days_playing == null ? '' : (g.days_playing === 0 ? 'começou hoje' : 'há ' + g.days_playing + (g.days_playing === 1 ? ' dia' : ' dias'))"></span>
                            </span>
                        </a>
                        <!-- atalho: terminou? registra como zerado (o jogo sai do backlog ao salvar) -->
                        <a x-show="!isDemo" :href="doneUrl(g)" class="absolute bottom-2 right-2 rounded-lg bg-ok px-2 py-1 text-2xs font-black text-black shadow transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" :aria-label="'Zerei ' + g.title">✓ Zerei</a>
                    </li>
                </template>
                <!-- vagas livres (no celular viram uma linha só, logo abaixo) -->
                <template x-for="i in freeSlots" :key="'free-' + i">
                    <li class="hidden sm:block">
                        <template x-if="isDemo">
                            <div class="dtable-tile dtable-free">
                                <span class="dtable-cover is-tile is-empty" aria-hidden="true"><?= icon('plus', 'h-5 w-5') ?></span>
                                <span class="dtable-text"><span class="dtable-title">Vaga livre</span><span class="dtable-sub">Escolha no backlog</span></span>
                            </div>
                        </template>
                        <template x-if="!isDemo">
                            <a :href="base + '/backlog'" class="dtable-tile dtable-free focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                <span class="dtable-cover is-tile is-empty" aria-hidden="true"><?= icon('plus', 'h-5 w-5') ?></span>
                                <span class="dtable-text"><span class="dtable-title">Vaga livre</span><span class="dtable-sub">Escolha no backlog</span></span>
                            </a>
                        </template>
                    </li>
                </template>
            </ul>
            <a x-show="freeSlots > 0" :href="isDemo ? null : base + '/backlog'" class="mt-3 flex items-center gap-3 rounded-xl border border-dashed border-slate-50/20 px-3 py-2.5 text-sm text-slate-300 transition hover:border-brand-400/60 hover:text-brand-400 sm:hidden">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-50/5 text-lg" aria-hidden="true">+</span>
                <span x-text="freeSlots + (freeSlots === 1 ? ' vaga livre' : ' vagas livres') + ' · escolher no backlog'"></span>
            </a>
            <p x-show="extra?.playing[0]?.last_note" x-cloak class="mt-3 rounded-lg border-l-2 border-brand-400 bg-slate-950/40 px-2.5 py-1.5 text-xs italic text-slate-300">
                <span class="not-italic font-semibold text-brand-400" x-text="extra?.playing[0]?.title"></span>
                <span class="not-italic text-slate-500"> · </span><span class="not-italic font-semibold text-brand-400" x-text="extra?.playing[0]?.last_note && UI.fmtDate(extra.playing[0].last_note.date)"></span>
                <span x-text="extra?.playing[0]?.last_note?.body"></span>
            </p>
        </div>
        <!-- prateleira dos últimos zerados -->
        <div class="min-w-0">
            <ul class="dshelf" aria-label="Últimos jogos zerados">
                <template x-for="g in (extra?.recent ?? [])" :key="g.id">
                    <li class="dshelf-item">
                        <a :href="isDemo ? null : base + '/jogos/' + g.id" class="shelf-link block focus:outline-none" :aria-label="g.title + ', ' + g.platform">
                            <div class="shelf-stage">
                                <div class="shelf-box">
                                    <div class="shelf-front">
                                        <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy">
                                        <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-2 text-center"><span class="text-3xl font-bold text-white/80" x-text="UI.initials(g.title)"></span></div>
                                    </div>
                                    <div class="shelf-side" :style="UI.spineStyle(g.platform)"><span x-text="g.platform"></span></div>
                                </div>
                            </div>
                            <div class="shelf-info"><p class="shelf-title !text-xs" x-text="g.title"></p><p class="shelf-sub" x-text="UI.fmtDate(g.finished_at)"></p></div>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</section>

<!-- ============ Resumo da semana ============ -->
<section :style="'order:' + pos('week')" x-show="vis('week')" x-cloak :class="cell('week')" class="panel p-5" aria-labelledby="h-week">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="h-week" class="t-section">Sua semana</h2>
        <p class="text-xs text-slate-500">Últimos 7 dias</p>
    </div>
    <div class="mt-4 grid gap-5 md:grid-cols-[auto_1fr] md:items-center">
        <dl class="grid grid-cols-2 gap-x-8 gap-y-3 sm:grid-cols-4 md:grid-cols-2 xl:grid-cols-4">
            <div><dt class="text-xs text-slate-500">Zerados</dt><dd class="text-2xl font-black tabular-nums text-slate-50" x-text="extra?.week.games ?? 0"></dd><dd class="text-2xs text-slate-500" x-text="weekDelta(extra?.week.games ?? 0, extra?.week.prev_games ?? 0, '')"></dd></div>
            <div><dt class="text-xs text-slate-500">Horas</dt><dd class="text-2xl font-black tabular-nums text-slate-50" x-text="fmt((extra?.week.minutes ?? 0) / 60, 0) + 'h'"></dd><dd class="text-2xs text-slate-500" x-text="weekDelta(Math.round((extra?.week.minutes ?? 0) / 60), Math.round((extra?.week.prev_minutes ?? 0) / 60), 'h')"></dd></div>
            <div><dt class="text-xs text-slate-500">Notas no diário</dt><dd class="text-2xl font-black tabular-nums text-slate-50" x-text="extra?.week.notes ?? 0"></dd></div>
            <div><dt class="text-xs text-slate-500">Sequência</dt>
                <dd class="flex items-center gap-1 text-2xl font-black tabular-nums" :class="extra?.week.streak ? 'text-orange-400' : 'text-slate-500'"><span aria-hidden="true" :class="extra?.week.streak ? '' : 'grayscale'">🔥</span><span x-text="extra?.week.streak ?? 0"></span></dd>
                <dd class="text-2xs text-slate-500" x-text="(extra?.week.streak ?? 0) === 1 ? 'semana zerando algo' : 'semanas seguidas zerando algo'"></dd></div>
        </dl>
        <div class="min-w-0">
            <ul x-show="extra?.week.list.length" class="flex gap-3 overflow-x-auto pb-1">
                <template x-for="g in (extra?.week.list ?? [])" :key="g.id">
                    <li class="w-20 shrink-0">
                        <a :href="isDemo ? null : base + '/jogos/' + g.id" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <span class="relative block aspect-[16/25] overflow-hidden rounded-md bg-slate-950 ring-1 ring-slate-50/10">
                                <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-lg font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                <span class="absolute left-1 top-1 rounded px-1 text-2xs font-black" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                            </span>
                            <span class="mt-1 block truncate text-2xs font-semibold text-slate-200" x-text="g.title"></span>
                        </a>
                    </li>
                </template>
            </ul>
            <p x-show="!extra?.week.list.length" class="rounded-xl border border-dashed border-slate-50/15 px-4 py-5 text-center text-sm text-slate-400"
               x-text="(extra?.playing.length ?? 0) ? 'Nenhum jogo zerado nesta semana ainda. Que tal avançar em ' + extra.playing[0].title + '?' : 'Nenhum jogo zerado nesta semana ainda.'"></p>
        </div>
    </div>
</section>

<!-- ============ Próximos passos ============ -->
<section :style="'order:' + pos('next')" x-show="vis('next')" x-cloak :class="cell('next')" aria-labelledby="h-next">
    <h2 id="h-next" class="t-section">Próximos passos</h2>
    <ul class="mt-3 grid gap-3 sm:grid-cols-2" :class="{ 'lg:grid-cols-3': extra?.next.length === 3, 'lg:grid-cols-4': (extra?.next.length ?? 0) >= 4 }">
        <template x-for="n in (extra?.next ?? [])" :key="n.kind">
            <li>
                <a :href="n.href" class="group flex h-full flex-col rounded-2xl border bg-slate-900/70 p-4 transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:hover:translate-y-0"
                   :class="n.kind === 'best' || n.kind === 'goty' ? 'border-gold-400/30 hover:border-gold-400/70' : 'border-slate-50/10 hover:border-brand-400/60'">
                    <span class="text-2xl leading-none" aria-hidden="true" x-text="n.icon"></span>
                    <span class="mt-2 block font-bold leading-snug text-slate-50" x-text="n.title"></span>
                    <span class="mt-1 block flex-1 text-sm text-slate-400" x-text="n.text"></span>
                    <span class="mt-3 text-sm font-semibold" :class="n.kind === 'best' || n.kind === 'goty' ? 'text-gold-500' : 'text-brand-400'" x-text="n.cta + ' →'"></span>
                </a>
            </li>
        </template>
    </ul>
</section>

<!-- ============ Hoje na sua história ============ -->
<section :style="'order:' + pos('today')" x-show="vis('today')" x-cloak :class="cell('today')" aria-labelledby="h-today">
    <h2 id="h-today" class="t-section">Hoje na sua história</h2>
    <!-- os dois lados dividem a linha só quando ambos têm conteúdo; com poucos jogos, o hall da fama ganha mais espaço -->
    <div class="mt-3 grid gap-4" :class="extra?.on_this_day.length && extra?.memory_pick ? (extra.on_this_day.length <= 2 ? 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]' : 'lg:grid-cols-2') : ''">
        <div x-show="extra?.on_this_day.length" x-cloak class="panel p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Em outros anos</p>
            <p class="mt-1 text-xs text-slate-400" x-text="seasonNote()"></p>
            <ul class="mt-4 grid gap-3" :class="extra?.memory_pick ? (extra.on_this_day.length > 2 ? 'sm:grid-cols-2' : '') : 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'">
                <template x-for="g in todayTop" :key="g.id">
                    <li><?php $todayCard(); ?></li>
                </template>
            </ul>
            <button type="button" x-show="(extra?.on_this_day.length ?? 0) > 4" x-cloak @click="openToday()"
                    class="mt-4 text-sm font-semibold text-brand-400 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    x-text="'Ver os ' + (extra?.on_this_day.length ?? 0) + ' jogos mais perto de hoje →'"></button>
        </div>
        <!-- Do seu hall da fama: o GOTY ou best of zerado mais perto da data de hoje -->
        <template x-if="extra?.memory_pick">
            <figure class="relative isolate overflow-hidden rounded-2xl border border-gold-400/35 bg-hero p-5">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    <img x-show="extra.memory_pick.cover_url" :src="extra.memory_pick.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-2xl">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/55 to-gold-500/15"></div>
                </div>
                <p class="best-kicker">🏆 Do seu hall da fama</p>
                <div class="mt-4 flex items-start gap-4">
                    <a :href="isDemo ? null : base + '/jogos/' + extra.memory_pick.id" class="best-frame best-shine aspect-[3/4] w-24 shrink-0 -rotate-2 transition duration-300 hover:rotate-0" :title="extra.memory_pick.title">
                        <img x-show="extra.memory_pick.cover_url" :src="extra.memory_pick.cover_url" :alt="'Capa de ' + extra.memory_pick.title">
                        <span x-show="!extra.memory_pick.cover_url" class="best-frame-inner grid place-items-center bg-slate-800 text-2xl font-bold text-white/70" x-text="UI.initials(extra.memory_pick.title)"></span>
                    </a>
                    <div class="min-w-0 flex-1">
                        <a :href="isDemo ? null : base + '/jogos/' + extra.memory_pick.id" class="block text-xl font-bold leading-tight text-white hover:underline" x-text="extra.memory_pick.title"></a>
                        <p class="mt-0.5 text-xs text-white/60" x-text="extra.memory_pick.platform"></p>
                        <ul class="mt-2 flex flex-wrap gap-1.5" aria-label="Honras">
                            <template x-for="h in extra.memory_pick.honors" :key="h.label">
                                <li class="best-badge !px-2 !py-0.5 !text-2xs" x-text="(h.kind === 'goty' ? '⭐ ' : '🏆 ') + h.label"></li>
                            </template>
                        </ul>
                        <p class="mt-3 text-sm font-semibold text-gold-300" x-text="memoryWhen(extra.memory_pick)"></p>
                        <p class="text-xs text-white/60" x-text="'Zerado em ' + longDate(extra.memory_pick.finished_at)"></p>
                    </div>
                </div>
                <blockquote x-show="extra.memory_pick.note" class="mt-4 border-l-2 border-gold-400/70 pl-4 text-sm italic leading-relaxed text-white/85" x-text="'“' + (extra.memory_pick.note ?? '') + '”'"></blockquote>
            </figure>
        </template>
    </div>

    <!-- Todos os jogos desta época, por ano (o foco fica dentro da janela e volta ao botão ao fechar) -->
    <div x-show="todayModal" x-cloak class="modal-back" @click.self="closeToday()" @keydown.escape.window="todayModal && closeToday()"
         role="dialog" aria-modal="true" aria-labelledby="today-modal-title">
        <div x-ref="todayBox" @keydown.tab="trapFocus($event, $refs.todayBox)" class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-50/15 bg-slate-900 shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-50/10 p-5">
                <div>
                    <h2 id="today-modal-title" class="t-section">Mais perto de hoje, em outros anos</h2>
                    <p class="text-sm text-slate-400" x-text="seasonNote()"></p>
                </div>
                <button type="button" x-ref="todayClose" @click="closeToday()" aria-label="Fechar" class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-slate-50/10 text-slate-300 hover:bg-slate-50/10 hover:text-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                    <?= icon('close', 'h-5 w-5') ?>
                </button>
            </div>
            <div class="overflow-y-auto p-5">
                <template x-for="grp in todayGroups" :key="grp.years">
                    <section class="mb-6 last:mb-0">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-brand-400" x-text="yearsAgo(grp.years) + ' · ' + grp.year"></h3>
                        <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="g in grp.games" :key="g.id">
                                <li><?php $todayCard(); ?></li>
                            </template>
                        </ul>
                    </section>
                </template>
            </div>
        </div>
    </div>
</section>

<!-- ============ Carta de jogador (linha inteira: a carta ao lado da explicação) ============ -->
<section :style="'order:' + pos('card')" x-show="vis('card')" x-cloak :class="cell('card')" class="panel p-5" aria-labelledby="h-card">
    <h2 id="h-card" class="t-section">Carta de jogador</h2>
    <div class="mt-4 flex flex-col items-center gap-6 lg:flex-row lg:items-center lg:justify-center lg:gap-10">
        <article class="pcard w-full max-w-[22rem] shrink-0" x-show="extra && !extra.card.empty" aria-label="Carta de jogador">
            <div class="pcard-inner">
                <p class="text-2xs font-bold uppercase tracking-[.25em] text-brand-400">MyGameList</p>
                <p class="mt-1 text-2xl font-black leading-tight text-slate-50" x-text="extra?.card.username"></p>
                <p class="text-sm font-bold text-accent" x-text="extra?.card.title"></p>
                <div class="pcard-covers mt-4" aria-hidden="true">
                    <template x-for="(f, i) in (extra?.card.favorites ?? [])" :key="i">
                        <span class="pcard-cover" :style="'--i:' + i">
                            <img x-show="f.cover_url" :src="f.cover_url" alt="" loading="lazy">
                            <span x-show="!f.cover_url" class="grid h-full w-full place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-lg font-bold text-white/80" x-text="UI.initials(f.title)"></span>
                        </span>
                    </template>
                </div>
                <ul class="mt-5 space-y-2.5">
                    <template x-for="a in (extra?.card.attributes ?? [])" :key="a.k">
                        <li class="flex items-center gap-3 text-sm">
                            <span class="w-24 shrink-0 font-semibold text-slate-200" x-text="a.k"></span>
                            <span class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-50/10"><span class="block h-full rounded-full" :class="attrColor(a.v)" :style="'width:' + a.v + '%'"></span></span>
                        </li>
                    </template>
                </ul>
                <dl class="mt-5 grid grid-cols-4 gap-2 border-t border-slate-50/10 pt-4 text-center">
                    <div><dd class="text-xl font-black text-slate-50" x-text="extra?.card.games"></dd><dt class="text-2xs text-slate-400">jogos</dt></div>
                    <div><dd class="text-xl font-black text-slate-50" x-text="extra?.card.hours"></dd><dt class="text-2xs text-slate-400">horas</dt></div>
                    <div><dd class="text-xl font-black text-slate-50" x-text="String(extra?.card?.avg_rating ?? '').replace('.', ',')"></dd><dt class="text-2xs text-slate-400">nota média</dt></div>
                    <div class="xp-ui"><dd class="text-xl font-black text-brand-400" x-text="extra?.card.level"></dd><dt class="text-2xs text-slate-400">nível</dt></div>
                </dl>
            </div>
        </article>
        <div class="w-full max-w-md">
            <p class="kicker">Quem é você como jogador</p>
            <p class="mt-1 text-base leading-relaxed text-slate-200" x-text="extra?.card.phrase"></p>
            <ul class="mt-4 space-y-2 text-sm text-slate-400">
                <li><strong class="text-slate-200">Nostalgia</strong>: quantos anos depois do lançamento você costuma zerar.</li>
                <li><strong class="text-slate-200">Fôlego</strong>: o tamanho médio dos jogos que você termina.</li>
                <li><strong class="text-slate-200">Exploração</strong>: a variedade de sistemas e gêneros.</li>
                <li><strong class="text-slate-200">Desafio</strong>: quanto dos seus jogos foram difíceis (A ou mais).</li>
            </ul>
            <button type="button" @click="downloadCard()" class="mt-5 w-full rounded-lg border border-slate-50/15 px-4 py-2.5 text-sm font-semibold text-slate-100 hover:bg-slate-50/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 sm:w-auto">⬇ Baixar a carta (imagem)</button>
        </div>
    </div>
</section>

<!-- ============ Constelação ============ -->
<section :style="'order:' + pos('constellation')" x-show="vis('constellation')" x-cloak :class="cell('constellation')" x-ref="constSection" class="panel p-5" aria-labelledby="h-const">
    <h2 id="h-const" class="t-section">Constelação</h2>
    <p class="text-xs text-slate-500">Cada ponto é um jogo: lançamento × quando você zerou. Acima da linha, você chegou nele mais tarde. Tamanho = tempo de jogo, cor = nota.</p>
    <div class="relative mt-3 h-72">
        <!-- os pontos chegam numa requisição própria, quando o bloco aparece na tela -->
        <p x-show="!constPts" class="absolute inset-0 grid animate-pulse place-items-center rounded-xl bg-slate-50/5 text-sm text-slate-500" aria-live="polite">Carregando a constelação…</p>
        <canvas id="chart-constellation" role="img" aria-label="Constelação dos jogos: ano de lançamento contra ano em que foram zerados. A lista abaixo traz os destaques em texto."></canvas>
        <div id="const-tip" class="pointer-events-none absolute z-10 flex w-60 gap-2 rounded-lg border border-slate-50/15 bg-slate-900 p-2 shadow-xl transition-opacity" style="opacity:0"></div>
    </div>
    <!-- alternativa em texto (teclado e leitor de tela) -->
    <details x-show="lateList.length" class="mt-3 text-sm">
        <summary class="cursor-pointer font-semibold text-slate-300 hover:text-slate-50">Ver em lista: os que você zerou mais tempo depois do lançamento</summary>
        <ol class="mt-2 space-y-1.5">
            <template x-for="p in lateList" :key="p.id">
                <li class="flex justify-between gap-3 text-slate-300">
                    <a :href="isDemo ? null : base + '/jogos/' + p.id" class="truncate hover:text-brand-400" x-text="p.title"></a>
                    <span class="shrink-0 text-xs tabular-nums text-slate-500" x-text="p.release_year + ' → ' + Math.floor(p.finish) + (p.lag ? ' (' + p.lag + (p.lag === 1 ? ' ano depois)' : ' anos depois)') : ' (no mesmo ano)')"></span>
                </li>
            </template>
        </ol>
    </details>
</section>

<!-- ============ Rio de gêneros ============ -->
<section :style="'order:' + pos('river')" x-show="vis('river')" x-cloak :class="cell('river')" class="panel p-5" aria-labelledby="h-river">
    <h2 id="h-river" class="t-section">Rio de gêneros</h2>
    <p class="text-xs text-slate-500">Como o seu gosto por gêneros cresce e diminui ao longo do tempo.</p>
    <p class="sr-only" x-text="riverSummary"></p>
    <div class="relative mt-3 h-72"><canvas id="chart-river" role="img" aria-label="Gêneros zerados ao longo do tempo, em áreas empilhadas"></canvas></div>
</section>

<!-- ============ Trilha do ano ============ -->
<section :style="'order:' + pos('soundtrack')" x-show="vis('soundtrack')" x-cloak :class="cell('soundtrack')" class="flex flex-col panel p-5" aria-labelledby="h-music">
    <h2 id="h-music" class="t-section">Trilha de <span x-text="year"></span></h2>
    <p class="text-xs text-slate-500">As músicas que estavam tocando na sua vida enquanto você jogava.</p>
    <p x-show="!(extra?.soundtrack ?? []).length" class="mt-4 text-sm text-slate-500" x-text="'Nenhuma música registrada nos jogos de ' + year + '.'"></p>
    <ol class="mt-3 max-h-80 flex-1 divide-y divide-slate-50/10 overflow-y-auto pr-1">
        <template x-for="(m, i) in (extra?.soundtrack ?? [])" :key="m.game_id">
            <li class="flex items-center gap-3 py-2.5">
                <span class="w-6 shrink-0 text-right text-sm tabular-nums text-slate-500" x-text="i + 1"></span>
                <span class="min-w-0 flex-1">
                    <template x-if="m.music_url"><a :href="m.music_url" target="_blank" rel="noopener noreferrer" class="block truncate font-semibold text-brand-400 hover:underline"><span aria-hidden="true">♪ </span><span x-text="m.music_title"></span></a></template>
                    <template x-if="!m.music_url"><span class="block truncate font-semibold text-slate-100"><span aria-hidden="true" class="text-brand-400">♪ </span><span x-text="m.music_title"></span></span></template>
                    <a :href="isDemo ? null : base + '/jogos/' + m.game_id" class="block truncate text-xs text-slate-400 hover:text-slate-200" x-text="m.game + ' · ' + monthOf(m.finished_at)"></a>
                </span>
            </li>
        </template>
    </ol>
</section>

<!-- ============ Com quem você jogou ============ -->
<section :style="'order:' + pos('people')" x-show="vis('people')" x-cloak :class="cell('people')" class="panel p-5" aria-labelledby="h-people">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="h-people" class="t-section">Com quem você jogou</h2>
        <a :href="base + (isDemo ? '/pessoas/exemplo' : '/pessoas')" class="text-xs font-semibold text-brand-400 hover:underline">Pessoas e lugares →</a>
    </div>
    <div class="mt-4">
        <ul class="flex flex-wrap items-center gap-3">
            <template x-for="p in (extra?.people ?? [])" :key="p.name">
                <li>
                    <button type="button" @click="openPerson = openPerson === p.name ? null : p.name" :aria-expanded="openPerson === p.name" :title="p.name"
                            class="pbubble focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" :class="openPerson === p.name && 'is-open'"
                            :style="'--s:' + (3.6 + Math.min(p.n, 20) * 0.16) + 'rem'">
                        <span class="pbubble-name !whitespace-normal line-clamp-2 break-words !text-[.68rem] !leading-tight" x-text="p.name"></span><span class="pbubble-n" x-text="p.n"></span>
                    </button>
                </li>
            </template>
        </ul>
        <!-- a pessoa com quem você mais jogou já vem aberta -->
        <template x-for="p in (extra?.people ?? [])" :key="'g' + p.name">
            <div x-show="openPerson === p.name" x-cloak class="mt-4 border-t border-slate-50/10 pt-4">
                <p class="text-sm text-slate-300">Jogos com <strong class="text-slate-50" x-text="p.name"></strong> <span class="text-slate-500" x-text="'· ' + p.n + (p.n === 1 ? ' jogo' : ' jogos')"></span></p>
                <ul class="mt-2 flex gap-3 overflow-x-auto pb-1">
                    <template x-for="g in p.games" :key="g.id">
                        <li class="w-20 shrink-0">
                            <a :href="isDemo ? null : base + '/jogos/' + g.id" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                <span class="relative block aspect-[16/25] overflow-hidden rounded bg-slate-950 ring-1 ring-slate-50/10">
                                    <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-xl font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                </span>
                                <span class="mt-1 block truncate text-2xs font-semibold text-slate-200" x-text="g.title"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>
</section>
