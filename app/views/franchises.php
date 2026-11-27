<?php
/** @var array|null $me */
/** @var array|null $demo  franquias fictícias da página de exemplo */
$demo = $demo ?? null;
?>
<main class="page page-wide" x-data='franchises(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'
      @keydown.window="if ($event.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test($event.target.tagName)) { $event.preventDefault(); $refs.search.focus(); }">
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): $note = 'Clique em uma franquia para ver as seções e o que já foi zerado.'; ?>
    <?php ob_start(); ?> com dados fictícios. <?= $note ?><?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <!-- ========== Destaque: o painel das franquias ========== -->
    <section class="hero-box text-white" aria-label="Resumo das franquias">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="focus && img(focus)" :src="focus ? img(focus) : ''" alt="" class="h-full w-full scale-150 object-cover opacity-30 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/70 to-ok/15"></div>
        </div>
        <div class="grid lg:grid-cols-[1fr_1.05fr]">
            <!-- números -->
            <div class="p-5 sm:p-8">
                <p class="kicker-hero">Suas franquias</p>
                <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                    <h1 class="t-hero">Franquias</h1>
                    <?php if ($demo === null): ?>
                    <a href="<?= e(url('/franquias/nova')) ?>" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black transition hover:bg-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">+ Nova franquia</a>
                    <?php endif; ?>
                </div>

                <div x-show="!ready" class="mt-6 h-28 animate-pulse rounded-2xl bg-white/5"></div>
                <div x-show="ready && list.length" x-cloak class="mt-6 flex flex-wrap items-center gap-6">
                    <div class="relative h-28 w-28 shrink-0">
                        <svg class="h-full w-full -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="rgb(255 255 255 / .12)" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="rgb(var(--ok))" stroke-width="3" stroke-linecap="round" :stroke-dasharray="(overall.pct * 0.974) + ' 100'"/>
                        </svg>
                        <div class="absolute inset-0 grid place-items-center text-center leading-none">
                            <span><span class="block text-3xl font-black tabular-nums" x-text="overall.pct + '%'"></span><span class="text-2xs uppercase tracking-wider text-white/55">geral</span></span>
                        </div>
                    </div>
                    <dl class="grid min-w-[14rem] flex-1 grid-cols-3 gap-x-4 gap-y-3 text-sm [&_dd]:whitespace-nowrap">
                        <div><dt class="text-white/55">Jogos zerados</dt><dd class="text-xl font-black tabular-nums" x-text="overall.done + ' / ' + overall.total"></dd></div>
                        <div><dt class="text-white/55">Horas nas franquias</dt><dd class="text-xl font-black tabular-nums" x-text="hours(overall.minutes)"></dd></div>
                        <div><dt class="text-white/55">Completas</dt><dd class="text-xl font-black tabular-nums text-ok" x-text="count('done') + ' de ' + list.length"></dd></div>
                    </dl>
                </div>
                <!-- a distribuição das franquias -->
                <div x-show="ready && list.length" x-cloak class="mt-6">
                    <div class="flex h-2.5 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                        <span class="bg-ok" :style="'width:' + share('done') + '%'"></span>
                        <span class="bg-gold-400" :style="'width:' + share('going') + '%'"></span>
                        <span class="bg-white/25" :style="'width:' + share('new') + '%'"></span>
                    </div>
                    <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/70">
                        <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-ok"></span><span x-text="count('done') + (count('done') === 1 ? ' completa' : ' completas')"></span></span>
                        <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-gold-400"></span><span x-text="count('going') + ' em andamento'"></span></span>
                        <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-white/40"></span><span x-text="count('new') + (count('new') === 1 ? ' não iniciada' : ' não iniciadas')"></span></span>
                    </p>
                </div>
            </div>

            <!-- em foco: a franquia mais perto do fim (ou a última que andou) -->
            <a x-show="focus" x-cloak :href="focus ? link(focus) : null" class="group relative block min-h-[15rem] overflow-hidden border-t border-white/10 lg:border-l lg:border-t-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white">
                <img x-show="focus && img(focus)" :src="focus ? img(focus) : ''" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100" :style="focus?.cover_url ? UI.focusStyle(focus.cover_focus?.card) : ''">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-black/10"></div>
                <div class="relative flex h-full flex-col justify-end p-5 sm:p-7">
                    <span class="w-fit rounded-full bg-gold-400 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider text-black" x-text="focusLabel"></span>
                    <h2 class="mt-2 text-2xl font-black leading-tight sm:text-3xl" x-text="focus?.name"></h2>
                    <div class="mt-3 flex gap-[2px] overflow-hidden" aria-hidden="true">
                        <template x-for="(t, i) in focus?.track ?? []" :key="i">
                            <span class="h-2 min-w-0 flex-1 rounded-sm" :class="t.d ? 'bg-ok' : 'bg-white/20'"></span>
                        </template>
                    </div>
                    <p class="mt-2 text-sm text-white/80" x-text="focus ? focus.done + ' de ' + focus.total + ' · ' + (focus.total - focus.done === 1 ? 'falta 1 jogo' : 'faltam ' + (focus.total - focus.done) + ' jogos') : ''"></p>
                    <p x-show="focus?.next" class="mt-3 w-fit rounded-xl border border-white/15 bg-black/50 px-3 py-2 text-sm backdrop-blur">
                        <span class="text-white/60">▶ Próximo:</span> <strong x-text="focus?.next ? focus.next.title + (focus.next.release_year ? ' (' + focus.next.release_year + ')' : '') : ''"></strong>
                    </p>
                </div>
            </a>
            <div x-show="ready && !focus" x-cloak class="grid min-h-[12rem] place-items-center border-t border-white/10 p-8 text-center text-sm text-white/60 lg:border-l lg:border-t-0">
                <p x-text="list.length ? 'Nenhuma franquia em andamento agora. Escolha uma da lista abaixo para começar.' : 'Monte a lista de uma franquia (ex.: Resident Evil), separe em seções e acompanhe o que já zerou.'"></p>
            </div>
        </div>
    </section>

    <!-- ========== Barra: busca e modo de exibição ========== -->
    <div x-show="ready && list.length" x-cloak class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <label class="relative min-w-0 flex-1 sm:max-w-xs">
            <span class="sr-only">Buscar franquia</span>
            <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
            <input type="search" x-ref="search" x-model="q" @keydown.escape="q = ''; $el.blur()" placeholder="Franquia ou jogo…" title="Atalho: /" class="w-full rounded-lg border border-slate-50/10 bg-slate-900/70 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-brand-500">
        </label>
        <div class="flex flex-wrap items-center gap-2">
            <label class="flex items-center gap-2 text-sm text-slate-400">Ordenar
                <select x-model="sortKey" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="auto">Padrão</option>
                    <option value="name">Nome A–Z</option>
                    <option value="pct">Maior progresso</option>
                    <option value="missing">Mais jogos faltando</option>
                    <option value="total">Mais jogos na franquia</option>
                    <option value="hours">Mais horas</option>
                </select>
            </label>
            <div class="seg" role="group" aria-label="Exibição">
                <button type="button" @click="setView('shelves')" :aria-pressed="view === 'shelves'" class="seg-btn" :class="view === 'shelves' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Estantes</button>
                <button type="button" @click="setView('grid')" :aria-pressed="view === 'grid'" class="seg-btn" :class="view === 'grid' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Grade</button>
            </div>
        </div>
    </div>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <div x-show="!ready && !error" class="mt-6 grid animate-pulse gap-5 sm:grid-cols-2 lg:grid-cols-3" aria-label="Carregando">
        <template x-for="i in 6"><div class="h-48 rounded-2xl bg-slate-50/5"></div></template>
    </div>

    <section x-show="ready && list.length === 0 && !error" x-cloak class="mt-6 empty">
        <p class="t-section">Nenhuma franquia ainda</p>
        <p class="mt-1 text-sm text-slate-400">Crie a primeira e veja aqui a trilha de cada franquia, o próximo jogo e a sua galeria de troféus.</p>
        <?php if ($demo === null): ?>
        <a href="<?= e(url('/franquias/nova')) ?>" class="btn mt-4 inline-block">Criar a primeira franquia</a>
        <?php endif; ?>
    </section>

    <p x-show="ready && list.length && filtered.length === 0" x-cloak class="mt-8 text-center text-slate-400">Nenhuma franquia encontrada.</p>

    <!-- ========== Modo estantes ========== -->
    <div x-show="ready && view === 'shelves' && filtered.length" x-cloak class="mt-8 space-y-12">

        <!-- Maratona: o próximo jogo de cada franquia em andamento -->
        <section x-show="!q && upNext.length > 1" aria-labelledby="fr-next">
            <h2 id="fr-next" class="t-section">▶ Próximos das suas franquias</h2>
            <p class="text-sm text-slate-500">O jogo que vem a seguir em cada franquia em andamento. Mande direto para a fila do backlog.</p>
            <ul class="mt-4 flex snap-x gap-3 overflow-x-auto pb-3">
                <template x-for="f in upNext" :key="f.id">
                    <li class="w-56 shrink-0 snap-start panel p-4">
                        <a :href="link(f)" class="kicker block truncate hover:text-brand-400" x-text="f.name"></a>
                        <p class="mt-1 line-clamp-2 min-h-[2.5rem] font-bold leading-tight text-slate-50" :title="f.next.title" x-text="f.next.title"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="(f.next.release_year ? f.next.release_year + ' · ' : '') + ((f.total - f.done) === 1 ? 'o último que falta' : 'faltam ' + (f.total - f.done))"></p>
                        <button type="button" x-show="!isDemo" @click="queue(f)" :disabled="nextState[f.id] === 'busy' || nextState[f.id] === 'ok'"
                                class="mt-3 w-full rounded-lg border border-slate-50/15 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-brand-400/60 hover:text-brand-400 disabled:opacity-70"
                                x-text="{ busy: '…', ok: '✓ Na fila', err: 'Não deu. Tentar de novo' }[nextState[f.id]] ?? '+ Quero jogar'"></button>
                    </li>
                </template>
            </ul>
        </section>

        <!-- Em andamento: a trilha de cada franquia -->
        <section x-show="group('going').length" aria-labelledby="fr-going">
            <div class="flex items-baseline justify-between gap-3">
                <h2 id="fr-going" class="t-section">Continue de onde parou <span class="ml-1 text-sm font-normal text-slate-500" x-text="group('going').length"></span></h2>
                <p class="hidden text-xs text-slate-500 sm:block">Cada risco é um jogo da franquia, na ordem das seções.</p>
            </div>
            <ul class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2 [&>li]:min-w-0">
                <template x-for="f in group('going')" :key="f.id">
                    <li>
                        <a :href="link(f)" class="group flex h-full overflow-hidden panel transition hover:border-brand-400/60 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <div class="relative w-28 shrink-0 overflow-hidden bg-slate-950 sm:w-36">
                                <img x-show="img(f)" :src="img(f)" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:transition-none" :style="f.cover_url ? UI.focusStyle(f.cover_focus?.card) : ''">
                                <div x-show="!img(f)" class="absolute inset-0 bg-gradient-to-br from-neutral to-brand-600/40"></div>
                                <span class="absolute bottom-2 left-2 rounded-md bg-black/70 px-1.5 py-0.5 text-xs font-black tabular-nums text-white" x-text="pct(f) + '%'"></span>
                            </div>
                            <div class="min-w-0 flex-1 p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="truncate text-base font-bold text-slate-50 group-hover:underline" :title="f.name" x-text="f.name"></h3>
                                    <span class="shrink-0 text-xs font-semibold text-gold-500" x-text="(f.total - f.done) === 1 ? 'falta 1' : 'faltam ' + (f.total - f.done)"></span>
                                </div>
                                <div class="mt-2 flex gap-[2px] overflow-hidden">
                                    <template x-for="(t, i) in f.track ?? []" :key="i">
                                        <span class="h-2.5 min-w-0 flex-1 rounded-sm" :class="t.d ? 'bg-ok' : 'bg-slate-50/15'" :title="(t.d ? '✓ ' : '· ') + t.t"></span>
                                    </template>
                                </div>
                                <p x-show="f.next" class="mt-3 truncate text-sm text-slate-300"><span class="text-slate-500">▶ Próximo:</span> <strong class="text-slate-50" x-text="f.next ? f.next.title + (f.next.release_year ? ' (' + f.next.release_year + ')' : '') : ''"></strong></p>
                                <p class="mt-1 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                    <span x-text="f.done + ' de ' + f.total + ' zerados'"></span>
                                    <span x-show="f.minutes" x-text="'⏱ ' + hours(f.minutes)"></span>
                                    <span x-show="f.last_done" x-text="f.last_done ? 'último em ' + date(f.last_done.finished_at) : ''"></span>
                                </p>
                            </div>
                        </a>
                    </li>
                </template>
            </ul>
        </section>

        <!-- Completas: a galeria de troféus -->
        <section x-show="group('done').length" aria-labelledby="fr-done">
            <h2 id="fr-done" class="t-section">🏆 Galeria de troféus <span class="ml-1 text-sm font-normal text-slate-500" x-text="group('done').length"></span></h2>
            <ul class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="f in group('done')" :key="f.id">
                    <li>
                        <a :href="link(f)" class="group block rounded-2xl bg-gradient-to-b from-gold-300 via-gold-500 to-gold-700 p-[3px] shadow-lg transition hover:-translate-y-1 hover:shadow-gold-500/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300 motion-reduce:hover:translate-y-0">
                            <div class="relative aspect-[4/5] overflow-hidden rounded-[13px] bg-hero text-white">
                                <img x-show="img(f)" :src="img(f)" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" :style="f.cover_url ? UI.focusStyle(f.cover_focus?.card) : ''">
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-3">
                                    <p class="text-2xs font-black uppercase tracking-[.2em] text-gold-300">Completa</p>
                                    <h3 class="line-clamp-2 text-base font-black leading-tight" x-text="f.name"></h3>
                                    <p class="mt-1 text-xs text-white/70" x-text="f.total + (f.total === 1 ? ' jogo' : ' jogos') + (f.minutes ? ' · ' + hours(f.minutes) : '')"></p>
                                    <p x-show="f.best" class="mt-1 truncate text-xs text-gold-200" x-text="f.best ? '★ ' + f.best.title : ''"></p>
                                </div>
                            </div>
                        </a>
                    </li>
                </template>
            </ul>
        </section>

        <!-- Não iniciadas: a fila de franquias -->
        <section x-show="group('new').length" aria-labelledby="fr-new">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h2 id="fr-new" class="t-section">Ainda não começou <span class="ml-1 text-sm font-normal text-slate-500" x-text="group('new').length"></span></h2>
                <button type="button" x-show="group('new').length > 1" @click="draw()" class="rounded-lg border border-slate-50/15 px-3 py-1.5 text-sm font-semibold text-slate-200 transition hover:border-brand-400/60 hover:text-brand-400">🎲 Sortear a próxima franquia</button>
            </div>
            <p x-show="picked" x-cloak role="status" class="mt-2 text-sm text-slate-300">A sorte escolheu: <a :href="picked && link(picked)" class="font-bold text-brand-400 hover:underline" x-text="picked?.name"></a></p>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 [&>li]:min-w-0">
                <template x-for="f in group('new')" :key="f.id">
                    <li>
                        <a :href="link(f)" :id="'fr-' + f.id" class="group flex items-center gap-3 rounded-xl border border-dashed p-3 transition hover:border-brand-400/60 hover:bg-slate-50/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" :class="picked?.id === f.id ? 'border-brand-400 bg-brand-500/10 ring-2 ring-brand-400' : 'border-slate-50/15'">
                            <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-50/5 text-lg font-black text-slate-400">
                                <img x-show="img(f)" :src="img(f)" alt="" loading="lazy" class="h-full w-full object-cover opacity-60 grayscale" :style="f.cover_url ? UI.focusStyle(f.cover_focus?.card) : ''">
                                <span x-show="!img(f)" x-text="UI.initials(f.name)"></span>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-slate-50 group-hover:underline" x-text="f.name"></span>
                                <span class="block truncate text-xs text-slate-500" x-text="f.total + (f.total === 1 ? ' jogo' : ' jogos') + (f.next ? ' · começa por ' + f.next.title : '')"></span>
                            </span>
                        </a>
                    </li>
                </template>
            </ul>
        </section>
    </div>

    <!-- ========== Modo grade ========== -->
    <div x-show="ready && view === 'grid' && filtered.length" x-cloak class="mt-6">
        <div class="seg flex-wrap" role="group" aria-label="Situação">
            <template x-for="t in tabs" :key="t.v">
                <button type="button" @click="tab = t.v" :aria-pressed="tab === t.v" class="seg-btn"
                        :class="tab === t.v ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'" x-text="t.l + ' (' + count(t.v) + ')'"></button>
            </template>
        </div>
        <p x-show="!shown.length" class="mt-8 text-center text-slate-400">Nenhuma franquia nesse filtro.</p>
        <ul class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="f in shown" :key="f.id">
                <li>
                    <a :href="link(f)" class="group block overflow-hidden panel transition duration-200 hover:-translate-y-1 hover:border-brand-400/60 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        <div class="relative grid h-40 grid-cols-4 overflow-hidden bg-slate-950">
                            <div x-show="f.cover_url" class="absolute inset-0 transition duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                                <img :src="f.cover_url" alt="" loading="lazy" class="h-full w-full object-cover" :style="UI.focusStyle(f.cover_focus?.card)">
                            </div>
                            <template x-for="i in (f.cover_url ? 0 : 4)" :key="i">
                                <div class="relative overflow-hidden border-r border-black/40 last:border-r-0">
                                    <img x-show="f.covers[i - 1]" :src="f.covers[i - 1]" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    <div x-show="!f.covers[i - 1]" class="absolute inset-0 bg-gradient-to-br from-neutral to-brand-600/30" :style="'opacity:' + (1 - (i - 1) * 0.18)"></div>
                                </div>
                            </template>
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                            <span x-show="status(f) === 'done'" class="absolute right-3 top-3 rounded-full bg-ok px-2.5 py-1 text-2xs font-black uppercase tracking-wider text-black shadow">✓ Completa</span>
                        </div>
                        <div class="p-4">
                            <h3 class="truncate text-lg font-bold text-slate-50" :title="f.name" x-text="f.name"></h3>
                            <p x-show="f.description" class="mt-0.5 line-clamp-1 text-xs text-slate-500" x-text="f.description"></p>
                            <div class="mt-3 flex items-baseline justify-between text-sm">
                                <span class="text-slate-300"><strong class="text-lg text-slate-50" x-text="f.done"></strong> de <span x-text="f.total"></span> <span x-text="f.total === 1 ? 'jogo' : 'jogos'"></span></span>
                                <span class="font-bold tabular-nums" :class="status(f) === 'done' ? 'text-ok' : 'text-brand-400'" x-text="pct(f) + '%'"></span>
                            </div>
                            <div class="mt-2 flex gap-px overflow-hidden" role="progressbar" :aria-valuenow="pct(f)" aria-valuemin="0" aria-valuemax="100" :aria-label="'Progresso em ' + f.name">
                                <template x-for="(t, i) in f.track ?? []" :key="i">
                                    <span class="h-2 min-w-0 flex-1 rounded-sm" :class="t.d ? (status(f) === 'done' ? 'bg-ok' : 'bg-brand-500') : 'bg-slate-50/10'"></span>
                                </template>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500">
                                <span class="shrink-0" x-text="f.sections + (f.sections === 1 ? ' seção' : ' seções') + (f.minutes ? ' · ' + hours(f.minutes) : '')"></span>
                                <span x-show="f.best" class="truncate" :title="f.best ? 'Best of: ' + f.best.title : ''"><span class="font-bold text-gold-500">🏆</span> <span class="font-semibold text-slate-200" x-text="f.best?.title"></span></span>
                            </div>
                        </div>
                    </a>
                </li>
            </template>
        </ul>
    </div>
</main>

<script>
function franchises(demo) {
    let savedView = 'shelves';
    try { savedView = localStorage.getItem('mgl.franchises.view') || 'shelves'; } catch (e) { /* sem storage */ }
    return {
        isDemo: !!demo,
        base: window.APP.base,
        list: [],
        ready: false,
        error: '',
        q: '',
        tab: 'all',
        sortKey: 'auto',
        picked: null, nextState: {},
        view: savedView,
        tabs: [{ v: 'all', l: 'Todas' }, { v: 'going', l: 'Em andamento' }, { v: 'done', l: 'Completas' }, { v: 'new', l: 'Não iniciadas' }],

        pct: (f) => (f.total ? Math.round(f.done / f.total * 100) : 0),
        status: (f) => (f.total && f.done === f.total ? 'done' : f.done > 0 ? 'going' : 'new'),
        hours: (m) => (m >= 60 ? Math.round(m / 60) + 'h' : (m || 0) + 'min'),
        date: (s) => UI.fmtDate(String(s).slice(0, 10)),
        img: (f) => f.cover_url || f.covers?.[0] || null,
        link(f) { return this.base + (this.isDemo ? '/exemplo/franquias/' : '/franquias/') + f.id; },
        setView(v) { this.view = v; try { localStorage.setItem('mgl.franchises.view', v); } catch (e) { /* sem storage */ } },
        count(tab) { return tab === 'all' ? this.list.length : this.list.filter((f) => this.status(f) === tab).length; },
        share(tab) { return this.list.length ? this.count(tab) / this.list.length * 100 : 0; },

        get overall() {
            const sum = (k) => this.list.reduce((s, f) => s + (f[k] || 0), 0);
            const done = sum('done'), total = sum('total');
            return { done, total, minutes: sum('minutes'), pct: total ? Math.round(done / total * 100) : 0 };
        },
        /** em foco: a mais perto de completar; empate → a que andou por último */
        get focus() {
            const going = this.list.filter((f) => this.status(f) === 'going');
            if (!going.length) return null;
            return [...going].sort((a, b) => (a.total - a.done) - (b.total - b.done)
                || String(b.last_done?.finished_at ?? '').localeCompare(String(a.last_done?.finished_at ?? '')))[0];
        },
        get focusLabel() {
            const f = this.focus;
            if (!f) return '';
            const left = f.total - f.done;
            return left === 1 ? 'Falta só 1 jogo' : left <= 3 ? 'Quase lá' : 'Em andamento';
        },

        /** filtro da busca (nome da franquia ou de qualquer jogo da trilha) */
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.list;
            return this.list.filter((f) => f.name.toLowerCase().includes(q) || (f.track ?? []).some((t) => t.t.toLowerCase().includes(q)));
        },
        group(st) {
            const rows = this.filtered.filter((f) => this.status(f) === st);
            if (this.sortKey !== 'auto') return rows.sort(this.sorter);
            if (st === 'going') return rows.sort((a, b) => String(b.last_done?.finished_at ?? '').localeCompare(String(a.last_done?.finished_at ?? '')) || this.pct(b) - this.pct(a));
            if (st === 'done') return rows.sort((a, b) => b.total - a.total || a.name.localeCompare(b.name, 'pt'));
            return rows.sort((a, b) => a.name.localeCompare(b.name, 'pt', { sensitivity: 'base' }));
        },
        get shown() {
            return this.filtered.filter((f) => this.tab === 'all' || this.status(f) === this.tab).sort(this.sorter);
        },
        get sorter() {
            return {
                name: (a, b) => a.name.localeCompare(b.name, 'pt', { sensitivity: 'base' }),
                pct: (a, b) => this.pct(b) - this.pct(a) || a.name.localeCompare(b.name, 'pt'),
                missing: (a, b) => (b.total - b.done) - (a.total - a.done) || a.name.localeCompare(b.name, 'pt'),
                total: (a, b) => b.total - a.total || a.name.localeCompare(b.name, 'pt'),
                hours: (a, b) => (b.minutes || 0) - (a.minutes || 0) || a.name.localeCompare(b.name, 'pt'),
            }[this.sortKey === 'auto' ? 'name' : this.sortKey];
        },
        /** os próximos jogos de cada franquia em andamento (as mais perto do fim primeiro) */
        get upNext() {
            return this.list.filter((f) => this.status(f) === 'going' && f.next)
                .sort((a, b) => (a.total - a.done) - (b.total - b.done) || a.name.localeCompare(b.name, 'pt')).slice(0, 12);
        },
        /** sorteia uma franquia ainda não iniciada e a destaca */
        draw() {
            const pool = this.group('new').filter((f) => f.id !== this.picked?.id);
            if (!pool.length) return;
            this.picked = pool[Math.floor(Math.random() * pool.length)];
            this.$nextTick(() => document.getElementById('fr-' + this.picked.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        },
        async queue(f) {
            this.nextState[f.id] = 'busy';
            try {
                await api.post('/api/backlog', { title: f.next.title, release_year: f.next.release_year ?? '', status: 'want' });
                this.nextState[f.id] = 'ok';
            } catch (e) { this.nextState[f.id] = e.status === 409 ? 'ok' : 'err'; }
        },

        async init() { await this.load(); },
        async load() {
            this.error = '';
            try {
                this.list = demo ?? (await api.get('/api/franchises')).data;
                this.ready = true;
            } catch (e) {
                if (e.status === 401) return (location.href = this.base + '/login');
                this.error = e.message;
            }
        },
    };
}
</script>
