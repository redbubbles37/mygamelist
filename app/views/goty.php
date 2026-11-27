<?php /** @var array|null $me */ /** @var array|null $demo  dados fictícios da página de exemplo (nulo na página real) */ ?>
<main class="page page-normal" x-data='goty(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <div class="demo-note" role="note">
        <p><strong class="text-slate-50">Página de exemplo</strong> com jogos fictícios. Dá para testar a escolha, mas nada é salvo.</p>
        <a href="<?= e(url($me ? '/goty' : '/login')) ?>" class="font-semibold text-brand-400 hover:underline"><?= $me ? 'Ir para o meu GOTY →' : 'Entrar →' ?></a>
    </div>
    <?php endif; ?>

    <!-- Destaque: o hall da fama como uma prateleira de capas douradas, do ano mais antigo ao mais novo -->
    <header class="hero-box border-gold-400/35 p-5 text-white sm:p-8">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="picks[0]?.pick.game.cover_url" :src="picks[0]?.pick.game.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/65 to-gold-500/15"></div>
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-gold-400/20 blur-3xl"></div>
        </div>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-2xl">
                <span class="rating-gold inline-block rounded-md px-2.5 py-1 text-xs font-black uppercase tracking-[0.2em] text-[#3b1d00]">Game of the Year</span>
                <h1 class="mt-3 t-hero-xl">Seu hall da fama</h1>
                <p class="mt-2 text-sm text-white/70">
                    O melhor jogo <strong class="text-white">lançado</strong> em cada ano. Um ano aparece aqui depois que você zera
                    <strong class="text-white" x-text="min"></strong> jogos ou mais lançados nele. Só jogos com <strong class="text-white">nota 9, 10 ou 11</strong> podem ser eleitos.
                </p>
            </div>
            <div x-show="ready && years.length" x-cloak class="text-right">
                <p class="text-4xl font-black tabular-nums"><span x-text="picks.length"></span><span class="text-white/40" x-text="' / ' + years.length"></span></p>
                <p class="text-xs text-white/60" x-text="years.length === 1 ? 'ano com GOTY' : 'anos com GOTY'"></p>
                <button type="button" x-show="nextPending()" @click="go(nextPending().year)" class="mt-3 rounded-lg border border-gold-400/60 bg-black/30 px-3 py-1.5 text-xs font-semibold text-gold-200 backdrop-blur transition hover:bg-gold-400/15">Escolher o próximo: <span x-text="nextPending()?.year"></span> →</button>
            </div>
        </div>
        <div x-show="ready && years.length" x-cloak class="mt-5 h-1.5 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-label="Anos com GOTY" :aria-valuenow="picks.length" aria-valuemin="0" :aria-valuemax="years.length">
            <div class="rating-gold h-full rounded-full" :style="`width:${years.length ? picks.length / years.length * 100 : 0}%`"></div>
        </div>
        <!-- a prateleira -->
        <ul x-show="ready && picks.length" x-cloak class="mt-6 flex items-end gap-3 overflow-x-auto pb-2 [scrollbar-color:rgb(255_255_255/.25)_transparent] [scrollbar-width:thin]" aria-label="GOTYs escolhidos, do mais antigo ao mais novo">
            <template x-for="yr in shelf" :key="yr.year">
                <li class="w-16 shrink-0 sm:w-20">
                    <button type="button" @click="go(yr.year)" class="group block w-full text-center focus:outline-none" :title="yr.year + ': ' + yr.pick.game.title">
                        <span class="best-frame block aspect-[3/4] w-full transition group-hover:-translate-y-1 group-focus-visible:ring-2 group-focus-visible:ring-gold-300 motion-reduce:group-hover:translate-y-0">
                            <img x-show="yr.pick.game.cover_url" :src="yr.pick.game.cover_url" :alt="'Capa de ' + yr.pick.game.title" loading="lazy">
                            <span x-show="!yr.pick.game.cover_url" class="best-frame-inner grid place-items-center bg-[#2a2236] text-sm font-bold text-white/70" x-text="UI.initials(yr.pick.game.title)"></span>
                        </span>
                        <span class="mt-1.5 block text-xs font-black tabular-nums text-gold-200" x-text="yr.year"></span>
                    </button>
                </li>
            </template>
        </ul>
        <!-- a "tábua" da prateleira -->
        <div x-show="ready && picks.length" x-cloak class="h-2 rounded-full bg-gradient-to-r from-gold-900/60 via-gold-700/60 to-gold-900/60 shadow-[0_6px_14px_rgba(0,0,0,.5)]" aria-hidden="true"></div>
        <!-- o que os premiados têm em comum -->
        <dl x-show="ready && picks.length >= 2" x-cloak class="mt-6 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-4">
            <div><dt class="text-xs text-white/55">Década de ouro</dt><dd class="font-bold" x-text="goldenDecade ? goldenDecade.label.replace('Anos ', '') + 's · ' + goldenDecade.n + ' GOTYs' : '—'"></dd></div>
            <div><dt class="text-xs text-white/55">Gênero mais premiado</dt><dd class="truncate font-bold" x-text="topOf('genre') ?? '—'"></dd></div>
            <div><dt class="text-xs text-white/55">Sistema mais premiado</dt><dd class="truncate font-bold" x-text="topOf('platform') ?? '—'"></dd></div>
            <div><dt class="text-xs text-white/55">Nota média dos GOTYs</dt><dd class="font-bold tabular-nums" x-text="fmt(picks.reduce((s, y) => s + y.pick.game.rating, 0) / picks.length, 1)"></dd></div>
        </dl>
    </header>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <div x-show="!ready && !error" class="mt-8 animate-pulse space-y-4" aria-label="Carregando">
        <div class="h-12 w-48 rounded-xl bg-slate-50/5"></div><div class="h-72 rounded-2xl bg-slate-50/5"></div>
    </div>

    <!-- Nenhum ano liberado ainda -->
    <section x-show="ready && years.length === 0" x-cloak class="mt-8 empty">
        <p class="t-section">Nenhum ano liberado ainda</p>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Zere <strong class="text-slate-200" x-text="min"></strong> jogos ou mais lançados em um mesmo ano e o GOTY dele aparece aqui para você escolher.</p>
        <a href="<?= e(url('/jogos/novo')) ?>" class="btn mt-4 inline-block">Registrar um jogo</a>
    </section>

    <!-- Navegador de anos: fixo no topo, rolável e agrupado por década -->
    <nav x-show="ready && years.length" x-cloak class="sticky top-0 z-20 -mx-4 mt-6 border-b border-slate-50/10 bg-slate-950/90 px-4 py-2.5 backdrop-blur sm:-mx-6 sm:px-6" aria-label="Anos do GOTY">
        <div class="flex items-center gap-2">
            <button type="button" @click="go('geral')" :aria-pressed="view === 'geral'"
                    class="flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    :class="view === 'geral' ? 'bg-brand-600 text-on-brand' : 'bg-slate-50/5 text-slate-300 hover:text-slate-50'">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
                Visão geral
            </button>
            <div x-ref="rail" class="flex min-w-0 flex-1 items-center gap-1.5 overflow-x-auto scroll-smooth py-1 [scrollbar-width:thin]" role="tablist" aria-label="Escolher ano">
                <template x-for="it in rail" :key="it.key">
                    <span class="contents">
                        <span x-show="it.decade" class="kicker shrink-0 pl-2 pr-0.5" x-text="it.decade"></span>
                        <button x-show="it.yr" type="button" role="tab" :data-year="it.yr?.year" :aria-selected="view === it.yr?.year" @click="go(it.yr.year)"
                                class="relative flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-bold tabular-nums transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                :class="it.yr && view === it.yr.year ? 'bg-brand-600 text-on-brand' : 'bg-slate-50/5 text-slate-300 hover:text-slate-50'">
                            <span x-text="it.yr?.year"></span>
                            <span class="h-2 w-2 rounded-full" :class="it.yr?.pick ? 'rating-gold' : 'border border-current opacity-50'" :title="it.yr?.pick ? 'GOTY escolhido' : 'Sem escolha ainda'"></span>
                        </button>
                    </span>
                </template>
            </div>
        </div>
    </nav>

    <!-- Visão geral: a linha dourada, ano a ano, agrupada por década -->
    <section x-show="ready && view === 'geral' && years.length" x-cloak class="mt-8" aria-label="Visão geral">
        <template x-for="d in decades" :key="d.label">
            <div class="mt-10 first:mt-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-black uppercase tracking-[0.25em] text-gold-500" x-text="d.label"></h2>
                    <span class="h-px flex-1 bg-gradient-to-r from-gold-500/50 to-transparent"></span>
                    <span class="text-xs text-slate-500" x-text="d.years.filter((y) => y.pick).length + ' de ' + d.years.length + ' escolhidos'"></span>
                </div>
                <ol class="relative mt-5 space-y-4 before:absolute before:bottom-0 before:left-[1.1rem] before:top-0 before:w-0.5 before:bg-gradient-to-b before:from-gold-400/70 before:to-gold-700/20 lg:before:left-1/2 lg:before:-translate-x-1/2">
                    <template x-for="(yr, i) in d.years" :key="yr.year">
                        <li class="relative pl-12 lg:grid lg:grid-cols-2 lg:gap-12 lg:pl-0">
                            <span class="absolute left-[1.1rem] top-6 z-10 h-4 w-4 -translate-x-1/2 rounded-full border-2 border-slate-950 lg:left-1/2" :class="yr.pick ? 'rating-gold' : 'bg-slate-600'"></span>
                            <button type="button" @click="go(yr.year)" class="group flex w-full items-center gap-4 rounded-2xl border p-3 text-left transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0"
                                    :class="[yr.pick ? 'border-gold-400/40 bg-slate-900/70 hover:border-gold-400' : 'border-dashed border-slate-50/20 bg-slate-900/40 hover:border-brand-400/60', i % 2 ? 'lg:col-start-2' : 'lg:flex-row-reverse lg:text-right']">
                                <div class="relative h-28 w-20 shrink-0 overflow-hidden rounded-lg ring-1 ring-black/40" :class="yr.pick ? 'rating-gold !p-[2px]' : 'bg-slate-950/60'">
                                    <template x-if="yr.pick">
                                        <div class="relative h-full w-full overflow-hidden rounded-md bg-slate-900">
                                            <img x-show="yr.pick.game.cover_url" :src="yr.pick.game.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                            <div x-show="!yr.pick.game.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60"><span class="text-xl font-bold text-white/85" x-text="UI.initials(yr.pick.game.title)"></span></div>
                                        </div>
                                    </template>
                                    <template x-if="!yr.pick"><div class="grid h-full w-full place-items-center text-2xl text-slate-500">?</div></template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-4xl font-black tabular-nums leading-none text-slate-50" x-text="yr.year"></p>
                                    <p class="mt-1.5 truncate text-base font-bold" :class="yr.pick ? 'text-slate-100' : 'text-brand-400'" x-text="yr.pick ? yr.pick.game.title : 'Escolher o GOTY →'"></p>
                                    <p class="truncate text-xs text-slate-500" x-text="(yr.pick ? (yr.pick.game.genre || yr.pick.game.platform) + ' · ' : '') + yr.games + ' indicados'"></p>
                                    <p x-show="yr.pick?.note" class="mt-1 line-clamp-1 text-xs italic text-slate-400" x-text="yr.pick?.note ? '“' + yr.pick.note + '”' : ''"></p>
                                </div>
                            </button>
                        </li>
                    </template>
                </ol>
            </div>
        </template>
    </section>

    <!-- Um ano por vez -->
    <template x-for="yr in shownYears" :key="yr.year">
        <section :id="'goty-' + yr.year" class="mt-8" :aria-label="'GOTY ' + yr.year">
            <header class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-50/10 pb-3">
                <h2 class="text-5xl font-black tracking-tight text-slate-50 sm:text-6xl"><span class="text-slate-500">GOTY</span> <span x-text="yr.year"></span></h2>
                <div class="flex items-center gap-3 pb-1">
                    <p class="hidden text-sm text-slate-400 sm:block"><strong class="text-slate-200" x-text="yr.games"></strong> jogos zerados · <strong class="text-slate-200" x-text="fmt(yr.hours, 1)"></strong> h</p>
                    <div class="flex gap-1.5">
                        <button type="button" @click="step(-1)" :disabled="!neighbor(-1)" class="btn-ghost btn-sm" :aria-label="'Ano anterior' + (neighbor(-1) ? ': ' + neighbor(-1).year : '')">
                            ← <span x-text="neighbor(-1)?.year"></span>
                        </button>
                        <button type="button" @click="step(1)" :disabled="!neighbor(1)" class="btn-ghost btn-sm" :aria-label="'Próximo ano' + (neighbor(1) ? ': ' + neighbor(1).year : '')">
                            <span x-text="neighbor(1)?.year"></span> →
                        </button>
                    </div>
                </div>
            </header>

            <!-- Escolhido -->
            <article x-show="yr.pick && !yr.picking" x-cloak class="mt-6 grid gap-6 md:grid-cols-[17rem_1fr] md:gap-8">
                <template x-if="yr.pick">
                    <div class="mx-auto w-full max-w-[17rem] md:mx-0">
                        <div class="rating-gold rounded-xl p-[3px] shadow-[0_0_44px_rgba(251,191,36,.3)]">
                            <div class="case !shadow-none">
                                <div class="case-band"><span x-text="yr.pick.game.platform"></span><span class="case-date" x-text="UI.fmtDate(yr.pick.game.finished_at)"></span></div>
                                <div class="case-art">
                                    <img x-show="yr.pick.game.cover_url" :src="yr.pick.game.cover_url" :alt="'Capa de ' + yr.pick.game.title" class="absolute inset-0 h-full w-full object-cover">
                                    <div x-show="!yr.pick.game.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center">
                                        <span class="text-6xl font-bold text-white/80" x-text="UI.initials(yr.pick.game.title)"></span>
                                    </div>
                                    <span class="rating-gold absolute right-2.5 top-2.5 grid h-9 w-9 place-items-center rounded-full text-[#3b1d00] shadow-lg ring-1 ring-black/40" title="GOTY">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zM17 5h3v2a3 3 0 01-3 3M7 5H4v2a3 3 0 003 3"/></svg>
                                    </span>
                                    <span class="absolute left-2.5 top-2.5 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40"
                                          :class="UI.ratingClass(yr.pick.game.rating)" :style="UI.ratingStyle(yr.pick.game.rating)" x-text="yr.pick.game.rating"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="yr.pick">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-gold-500">Seu GOTY de <span x-text="yr.year"></span></p>
                        <h3 class="mt-1 text-3xl font-bold leading-tight text-slate-50 sm:text-4xl" x-text="yr.pick.game.title"></h3>
                        <p class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-brand-500/20 px-2.5 py-1 text-xs font-medium text-brand-400" x-text="yr.pick.game.platform"></span>
                            <span x-show="yr.pick.game.genre" class="rounded-md bg-slate-50/10 px-2.5 py-1 text-xs font-medium text-slate-200" x-text="yr.pick.game.genre"></span>
                            <span class="rounded-md px-2.5 py-1 text-xs font-black ring-1 ring-black/30" :style="UI.diffStyle(yr.pick.game.difficulty)" x-text="'Dificuldade ' + yr.pick.game.difficulty"></span>
                            <span x-show="isTopRated(yr)" class="rounded-md bg-ok/15 px-2.5 py-1 text-xs font-bold text-ok">Também a maior nota do ano</span>
                        </p>
                        <dl class="mt-4 grid max-w-md grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-50/10 bg-slate-900/70 p-3.5">
                                <dt class="text-xs uppercase tracking-wider text-slate-500">Zerado em</dt>
                                <dd class="mt-0.5 font-semibold text-slate-50" x-text="UI.fmtDate(yr.pick.game.finished_at)"></dd>
                            </div>
                            <div class="rounded-xl border border-slate-50/10 bg-slate-900/70 p-3.5">
                                <dt class="text-xs uppercase tracking-wider text-slate-500">Tempo de jogo</dt>
                                <dd class="mt-0.5 font-semibold text-slate-50" x-text="UI.fmtTime(yr.pick.game.playtime_minutes)"></dd>
                            </div>
                        </dl>
                        <blockquote x-show="yr.pick.note" class="mt-5 max-w-xl border-l-4 border-gold-400 pl-4 text-lg italic text-slate-200" x-text="'“' + yr.pick.note + '”'"></blockquote>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button type="button" @click="startChange(yr)" class="btn">Trocar escolha</button>
                            <button type="button" x-show="!yr.confirmDel" @click="yr.confirmDel = true" class="rounded-lg border border-slate-50/10 px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-50/10">Remover</button>
                            <span x-show="yr.confirmDel" x-cloak class="flex items-center gap-2 text-sm">
                                <span class="text-slate-300">Remover o GOTY de <span x-text="yr.year"></span>?</span>
                                <button type="button" @click="remove(yr)" :disabled="yr.saving" class="rounded-lg bg-danger px-3 py-1.5 font-semibold text-white hover:opacity-90 disabled:opacity-60">Sim</button>
                                <button type="button" @click="yr.confirmDel = false" class="rounded-lg px-3 py-1.5 text-slate-300 hover:bg-slate-50/10">Não</button>
                            </span>
                            <button type="button" x-show="nextPending(yr)" x-cloak @click="go(nextPending(yr).year)" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gold-500 hover:bg-gold-400/10">
                                Próximo sem escolha: <span x-text="nextPending(yr)?.year"></span> →
                            </button>
                        </div>
                        <p x-show="yr.err" x-cloak role="alert" class="mt-3 text-sm text-danger" x-text="yr.err"></p>
                    </div>
                </template>
            </article>

            <!-- os indicados: quem concorreu e não levou -->
            <section x-show="yr.pick && !yr.picking && nominees(yr).length" x-cloak class="mt-10" :aria-label="'Indicados de ' + yr.year">
                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-black uppercase tracking-[0.2em] text-slate-400">Os outros indicados</h3>
                    <span class="h-px flex-1 bg-slate-50/10"></span>
                    <span class="text-xs text-slate-500" x-text="yr.candidates.length + ' jogos nota 9 a 11 de ' + yr.year + ' (de ' + yr.games + ' zerados)'"></span>
                </div>
                <ul class="mt-4 flex gap-4 overflow-x-auto pb-2">
                    <template x-for="g in nominees(yr)" :key="g.id">
                        <li class="w-24 shrink-0">
                            <div class="relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-900 ring-1 ring-slate-50/10 grayscale-[.35] transition hover:grayscale-0">
                                <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-xl font-bold text-white/80" x-text="UI.initials(g.title)"></div>
                                <span class="absolute left-1.5 top-1.5 rounded-md px-1.5 py-0.5 text-xs font-black shadow" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                            </div>
                            <p class="mt-1.5 truncate text-xs font-semibold text-slate-300" :title="g.title" x-text="g.title"></p>
                        </li>
                    </template>
                </ul>
            </section>

            <!-- Escolher / trocar -->
            <div x-show="yr.picking" x-cloak class="mt-6">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="t-section">Qual foi o melhor jogo lançado em <span x-text="yr.year"></span>?</p>
                    <p class="text-sm text-slate-400">Só entram jogos que você zerou <strong class="text-slate-200">com nota 9, 10 ou 11</strong>. Ordenados pela sua nota.</p>
                </div>
                <p x-show="!yr.candidates.length" x-cloak class="mt-4 empty !p-6">Nenhum dos <span x-text="yr.games"></span> jogos de <span x-text="yr.year"></span> tem nota 9, 10 ou 11, e só esses podem ser eleitos GOTY.</p>
                <button type="button" x-show="yr.candidates.length >= 3" @click="startDuel(yr)" class="mt-4 flex w-full flex-wrap items-center justify-between gap-3 rounded-2xl border border-gold-400/40 bg-gradient-to-r from-gold-500/15 to-transparent p-4 text-left transition hover:border-gold-400">
                    <span>
                        <span class="block font-bold text-slate-50">⚔ Em dúvida? Decida no duelo</span>
                        <span class="block text-sm text-slate-400" x-text="'Os ' + Math.min(8, yr.candidates.length) + ' melhores se enfrentam, dois de cada vez, até sobrar o campeão.'"></span>
                    </span>
                    <span class="rating-gold rounded-lg px-4 py-2 text-sm font-bold text-[#3b1d00]">Começar duelo</span>
                </button>

                <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5" role="radiogroup" :aria-label="'Candidatos a GOTY ' + yr.year">
                    <template x-for="g in yr.candidates" :key="g.id">
                        <li>
                            <button type="button" role="radio" :aria-checked="yr.selected === g.id" @click="yr.selected = g.id" class="group block w-full rounded-xl text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">
                                <div class="case transition" :class="yr.selected === g.id ? '!scale-[1.03] !shadow-[0_0_0_3px_#fbbf24,0_14px_30px_rgba(251,191,36,.35)]' : 'opacity-90'">
                                    <div class="case-band"><span x-text="g.platform"></span><span class="case-date" x-text="UI.fmtDate(g.finished_at)"></span></div>
                                    <div class="case-art">
                                        <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                        <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center">
                                            <span class="text-4xl font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                        </div>
                                        <span class="absolute left-2 top-2 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40"
                                              :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                                        <span x-show="yr.selected === g.id" class="rating-gold absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full text-base font-black text-[#3b1d00] shadow-lg ring-1 ring-black/40" aria-hidden="true">✓</span>
                                        <div class="absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 px-2.5 pb-2 pt-1.5 backdrop-blur-md">
                                            <p class="truncate text-center text-sm font-bold leading-tight text-white" :title="g.title" x-text="g.title"></p>
                                            <div class="mt-1 flex items-center justify-between gap-2 text-2xs font-medium text-white/85">
                                                <span class="min-w-0 truncate" x-text="g.genre || '—'"></span>
                                                <span class="shrink-0 tabular-nums" x-text="UI.fmtTime(g.playtime_minutes)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </li>
                    </template>
                </ul>

                <!-- Confirmação -->
                <div class="sticky bottom-0 z-10 -mx-4 mt-6 border-t border-slate-50/10 bg-slate-950/90 px-4 py-3 pr-24 backdrop-blur sm:-mx-6 sm:px-6 sm:pr-24">
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="min-w-[14rem] flex-1">
                            <span class="sr-only">Por que esse jogo? (opcional)</span>
                            <input type="text" maxlength="300" x-model="yr.draft" :disabled="!yr.selected" placeholder="Por que esse jogo? (opcional)"
                                   class="w-full rounded-lg border border-slate-50/10 bg-slate-900 px-3 py-2.5 text-slate-50 placeholder-slate-500 outline-none transition focus:ring-2 focus:ring-brand-500 disabled:opacity-50">
                        </label>
                        <button type="button" @click="cancel(yr)" x-show="yr.pick" x-cloak class="btn-ghost">Cancelar</button>
                        <button type="button" @click="save(yr)" :disabled="!yr.selected || yr.saving"
                                class="rating-gold rounded-lg px-5 py-2.5 font-bold text-[#3b1d00] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300 disabled:cursor-not-allowed disabled:opacity-50"
                                x-text="yr.saving ? 'Salvando…' : (yr.pick ? 'Confirmar troca' : 'Definir como GOTY ' + yr.year)"></button>
                    </div>
                    <p x-show="yr.err" x-cloak role="alert" class="mt-2 text-sm text-danger" x-text="yr.err"></p>
                </div>
            </div>
        </section>
    </template>

    <!-- Duelo: mata-mata entre os candidatos -->
    <div x-show="duel" x-cloak x-transition.opacity class="fixed inset-0 z-viewer overflow-y-auto bg-black/90 p-4 text-white backdrop-blur" role="dialog" aria-modal="true" aria-label="Duelo de candidatos" @keydown.escape.window="duel = null">
        <template x-if="duel">
            <div class="mx-auto flex min-h-full max-w-3xl flex-col justify-center py-6">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-[.3em] text-gold-300" x-text="'GOTY ' + duel.yr.year + ' · ' + (duel.done ? 'temos um campeão' : roundLabel)"></p>
                    <button type="button" @click="duel = null" class="rounded-full px-3 py-1 text-sm text-white/70 hover:bg-white/10" aria-label="Fechar duelo">✕</button>
                </div>
                <!-- confronto -->
                <template x-if="!duel.done && duelPair">
                    <div>
                        <h2 class="mt-2 text-center text-2xl font-black sm:text-3xl">Qual é melhor?</h2>
                        <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-3 sm:gap-6">
                            <template x-for="(g, k) in duelPair" :key="g.id">
                                <button type="button" @click="duelPick(g)" class="group rounded-2xl p-2 text-center transition hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300" :class="k === 1 ? 'col-start-3' : ''">
                                    <span class="relative mx-auto block aspect-[3/4] w-full max-w-[13rem] overflow-hidden rounded-xl shadow-2xl ring-2 ring-white/15 transition group-hover:scale-105 group-hover:ring-gold-300 motion-reduce:group-hover:scale-100">
                                        <img x-show="g.cover_url" :src="g.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                                        <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-4xl font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                    </span>
                                    <span class="mt-3 block text-sm font-bold sm:text-base" x-text="g.title"></span>
                                    <span class="block text-xs text-white/60" x-text="g.platform + ' · nota ' + g.rating"></span>
                                </button>
                            </template>
                            <span class="col-start-2 row-start-1 text-2xl font-black text-gold-300">VS</span>
                        </div>
                        <p class="mt-6 text-center text-xs text-white/50" x-text="'Restam ' + (duel.round.length - duel.pos + duel.next.length) + ' na disputa'"></p>
                    </div>
                </template>
                <!-- campeão -->
                <template x-if="duel.done">
                    <div class="mt-4 text-center">
                        <div class="rating-gold mx-auto w-48 rounded-xl p-[3px] shadow-[0_0_60px_rgba(251,191,36,.45)]">
                            <div class="relative aspect-[3/4] overflow-hidden rounded-[9px] bg-slate-900">
                                <img x-show="duel.done.cover_url" :src="duel.done.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                                <span x-show="!duel.done.cover_url" class="absolute inset-0 grid place-items-center text-4xl font-bold text-white/80" x-text="UI.initials(duel.done.title)"></span>
                            </div>
                        </div>
                        <p class="mt-5 text-sm font-bold uppercase tracking-[.25em] text-gold-300">🏆 Venceu o duelo</p>
                        <h2 class="mt-1 text-3xl font-black" x-text="duel.done.title"></h2>
                        <div class="mt-6 flex justify-center gap-3">
                            <button type="button" @click="startDuel(duel.yr)" class="rounded-lg border border-white/25 px-4 py-2.5 text-sm font-semibold hover:bg-white/10">Refazer</button>
                            <button type="button" @click="duelAccept()" class="rating-gold rounded-lg px-5 py-2.5 font-bold text-[#3b1d00]">Usar como GOTY</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</main>

<script>
function goty(demo) {
    const base = window.APP.base;
    return {
        demo: !!demo,
        min: 5,
        years: [],
        view: 'geral',          // 'geral' (visão geral) ou o número do ano em exibição
        ready: false,
        error: '',

        fmt: (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0),
        get picks() { return this.years.filter((y) => y.pick); },
        /** a prateleira do destaque: os escolhidos, do ano mais antigo ao mais novo */
        get shelf() { return [...this.picks].sort((a, b) => a.year - b.year); },
        /** os indicados que não levaram (os 8 de maior nota, sem o escolhido) */
        nominees(yr) { return yr.candidates.filter((c) => c.id !== yr.pick?.game.id).slice(0, 8); },
        get goldenDecade() {
            const c = {};
            for (const y of this.picks) { const k = 'Anos ' + Math.floor(y.year / 10) * 10; c[k] = (c[k] ?? 0) + 1; }
            const best = Object.entries(c).sort((a, b) => b[1] - a[1])[0];
            return best ? { label: best[0], n: best[1] } : null;
        },
        topOf(key) {
            const c = {};
            for (const y of this.picks) { const v = y.pick.game[key]; if (v) c[v] = (c[v] ?? 0) + 1; }
            const best = Object.entries(c).sort((a, b) => b[1] - a[1])[0];
            return best ? best[0] + ' (' + best[1] + ')' : null;
        },

        // ---------- duelo (mata-mata entre os melhores candidatos) ----------
        duel: null,
        startDuel(yr) {
            const pool = yr.candidates.slice(0, 8);
            if (pool.length < 2) return;
            this.duel = { yr, round: [...pool].sort(() => Math.random() - 0.5), next: [], pos: 0, done: null };
            this.duelAdvance();
        },
        get duelPair() { const d = this.duel; return d && !d.done ? [d.round[d.pos], d.round[d.pos + 1]] : null; },
        get roundLabel() {
            const n = this.duel ? this.duel.round.length : 0;
            return n <= 2 ? 'Final' : n <= 4 ? 'Semifinal' : 'Quartas de final';
        },
        /** passa adiante quem ficou sem par e abre a próxima rodada quando a atual acaba */
        duelAdvance() {
            const d = this.duel;
            for (;;) {
                if (d.pos >= d.round.length) {
                    if (d.next.length === 1) { d.done = d.next[0]; return; }
                    d.round = d.next; d.next = []; d.pos = 0; continue;
                }
                if (d.pos === d.round.length - 1) { d.next.push(d.round[d.pos]); d.pos++; continue; }
                return;
            }
        },
        duelPick(g) { this.duel.next.push(g); this.duel.pos += 2; this.duelAdvance(); },
        duelAccept() { this.duel.yr.selected = this.duel.done.id; this.duel = null; },

        isTopRated(yr) { return yr.pick && yr.pick.game.rating === Math.max(...yr.candidates.map((c) => c.rating)); },

        // ---------- navegação entre anos ----------
        get current() { return typeof this.view === 'number' ? this.years.find((y) => y.year === this.view) ?? null : null; },
        get shownYears() { return this.current ? [this.current] : []; },

        /** Anos agrupados por década (mais recente primeiro). */
        get decades() {
            const map = new Map();
            for (const y of this.years) {
                const label = 'Anos ' + Math.floor(y.year / 10) * 10;
                if (!map.has(label)) map.set(label, []);
                map.get(label).push(y);
            }
            return [...map].map(([label, years]) => ({ label, years }));
        },
        /** Itens da barra de anos: um rótulo de década antes do primeiro ano de cada uma. */
        get rail() {
            return this.decades.flatMap((d) => [
                { key: 'd' + d.label, decade: d.label.replace('Anos ', '') + 's' },
                ...d.years.map((yr) => ({ key: 'y' + yr.year, yr })),
            ]);
        },
        neighbor(dir) { // dir -1 = ano anterior (mais antigo), +1 = próximo (mais novo)
            const i = this.years.findIndex((y) => y.year === this.view);
            return this.years[i - dir] ?? null; // a lista está em ordem decrescente
        },
        step(dir) { const n = this.neighbor(dir); if (n) this.go(n.year); },
        /** Próximo ano (a partir de `from`, indo para os mais antigos e dando a volta) que ainda não tem escolha. */
        nextPending(from = null) {
            const list = this.years;
            const start = from ? list.findIndex((y) => y.year === from.year) : -1;
            for (let k = 1; k <= list.length; k++) {
                const y = list[(start + k) % list.length];
                if (!y.pick && y.year !== from?.year) return y;
            }
            return null;
        },

        go(target) {
            const t = target === 'geral' ? 'geral' : Number(target);
            this.view = t;
            history.replaceState(null, '', location.pathname + location.search + '#' + t);
            window.scrollTo({ top: 0 });
            this.$nextTick(() => {
                const chip = this.$refs.rail?.querySelector(`[data-year="${t}"]`);
                if (chip) this.$refs.rail.scrollTo({ left: chip.offsetLeft - this.$refs.rail.clientWidth / 2 + chip.clientWidth / 2, behavior: 'smooth' });
            });
        },
        readHash() {
            const h = location.hash.slice(1);
            if (/^\d{4}$/.test(h) && this.years.some((y) => y.year === Number(h))) return Number(h);
            if (h === 'geral') return 'geral';
            // padrão: poucos anos -> abre direto o mais recente; muitos -> visão geral
            return this.years.length <= 3 ? (this.years[0]?.year ?? 'geral') : 'geral';
        },
        onKey(e) {
            const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName);
            if (typing || e.ctrlKey || e.metaKey || e.altKey || document.body.classList.contains('overflow-hidden')) return;
            if (e.key === 'ArrowLeft' && this.current) this.step(-1);
            if (e.key === 'ArrowRight' && this.current) this.step(1);
        },

        // ---------- dados ----------
        /** Campos de interface de cada ano (modo de escolha, seleção, rascunho do comentário...). */
        decorate(y) {
            return { ...y, picking: !y.pick, selected: y.pick?.game.id ?? null, draft: y.pick?.note ?? '', saving: false, err: '', confirmDel: false };
        },
        replace(y) {
            const i = this.years.findIndex((x) => x.year === y.year);
            this.years[i] = this.decorate(y);
        },

        async init() {
            window.addEventListener('keydown', (e) => this.onKey(e));
            window.addEventListener('hashchange', () => { this.view = this.readHash(); });
            if (this.demo) { this.years = demo.map((y) => this.decorate(y)); this.ready = true; this.view = this.readHash(); return; }
            await this.load();
        },
        async load() {
            this.error = '';
            try {
                const r = await api.get('/api/goty');
                this.min = r.min;
                this.years = r.data.map((y) => this.decorate(y));
                this.ready = true;
                this.view = this.readHash();
                this.$nextTick(() => typeof this.view === 'number' && this.go(this.view));
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },

        startChange(yr) { yr.picking = true; yr.selected = yr.pick.game.id; yr.draft = yr.pick.note ?? ''; yr.err = ''; },
        cancel(yr) { yr.picking = false; yr.err = ''; },

        async save(yr) {
            if (!yr.selected) return;
            yr.saving = true; yr.err = '';
            try {
                if (this.demo) { // exemplo: só altera a tela
                    const game = yr.candidates.find((c) => c.id === yr.selected);
                    this.replace({ ...yr, pick: { note: yr.draft.trim() || null, game } });
                    return;
                }
                const r = await api.put('/api/goty/' + yr.year, { game_id: yr.selected, note: yr.draft.trim() });
                this.replace(r.data);
            } catch (e) {
                yr.err = Object.values(e.errors || {})[0] || e.message;
                yr.saving = false;
            }
        },

        async remove(yr) {
            yr.saving = true; yr.err = '';
            try {
                if (!this.demo) await api.del('/api/goty/' + yr.year);
                this.replace({ ...yr, pick: null });
            } catch (e) {
                yr.err = e.message;
                yr.saving = false;
            }
        },
    };
}
</script>
