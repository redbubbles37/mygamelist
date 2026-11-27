<?php /** @var array|null $me */ /** @var array|null $demo  {years, data} fictícios da página de exemplo (nulo na página real) */ ?>
<main class="page page-normal" x-data='retro(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <div class="demo-note" role="note">
        <p><strong class="text-slate-50">Página de exemplo</strong> com um ano fictício de jogos. Dá para testar a escolha do top 3, mas nada é salvo.</p>
        <a href="<?= e(url($me ? '/retrospectiva' : '/login')) ?>" class="font-semibold text-brand-400 hover:underline"><?= $me ? 'Ir para a minha retrospectiva →' : 'Entrar →' ?></a>
    </div>
    <?php endif; ?>

    <div x-show="error" x-cloak role="alert" class="alert-error">
        <span x-text="error"></span> <button type="button" @click="init()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <div x-show="!ready && !error" class="animate-pulse space-y-4" aria-label="Carregando">
        <div class="h-14 w-72 rounded-xl bg-slate-50/5"></div><div class="h-56 rounded-3xl bg-slate-50/5"></div>
    </div>

    <section x-show="ready && years.length === 0" x-cloak class="empty">
        <p class="t-section">Sua retrospectiva ainda está vazia</p>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Assim que você zerar o primeiro jogo, o resumo do ano aparece aqui, com o seu top 3.</p>
        <a href="<?= e(url('/jogos/novo')) ?>" class="btn mt-4 inline-block">Registrar um jogo</a>
    </section>

    <template x-if="ready && d">
        <div :class="loading && 'opacity-60 transition-opacity'">

            <!-- Destaque: o ano gigante sobre a capa do seu nº 1, a frase do ano e os números -->
            <header class="hero-box p-5 text-white sm:p-8">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    <img x-show="heroCover" :src="heroCover" alt="" class="h-full w-full scale-150 object-cover opacity-35 blur-3xl">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/60 to-brand-700/30"></div>
                    <!-- o ano, enorme e vazado, ao fundo -->
                    <span class="absolute -bottom-10 -right-4 select-none text-[9rem] font-black leading-none tracking-tighter text-white/[.06] sm:text-[14rem]" x-text="d.year"></span>
                </div>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="kicker-hero">Retrospectiva</p>
                        <h1 class="mt-1 text-6xl font-black tracking-tight drop-shadow-lg sm:text-8xl" x-text="d.year"></h1>
                        <p class="mt-3 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg" x-text="yearLine"></p>
                        <button type="button" @click="openStory()" class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-black text-black shadow-lg transition hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-white motion-reduce:hover:scale-100">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg> Assistir o meu ano
                        </button>
                    </div>
                    <div class="flex items-center gap-2" x-show="years.length > 1">
                        <button type="button" @click="step(-1)" :disabled="!neighbor(-1) || loading" class="rounded-lg border border-white/20 bg-black/30 px-3 py-2 text-sm font-semibold text-white/85 backdrop-blur transition hover:bg-white/10 disabled:opacity-30" :aria-label="'Ano anterior' + (neighbor(-1) ? ': ' + neighbor(-1).year : '')">← <span x-text="neighbor(-1)?.year"></span></button>
                        <label class="sr-only" for="retro-year">Escolher ano</label>
                        <select id="retro-year" :value="d.year" @change="go(Number($event.target.value))" class="rounded-lg border border-white/20 bg-black/40 px-3 py-2 font-semibold text-white outline-none backdrop-blur focus:ring-2 focus:ring-brand-500">
                            <template x-for="y in years" :key="y.year"><option :value="y.year" x-text="y.year + ' · ' + y.games + ' jogos'"></option></template>
                        </select>
                        <button type="button" @click="step(1)" :disabled="!neighbor(1) || loading" class="rounded-lg border border-white/20 bg-black/30 px-3 py-2 text-sm font-semibold text-white/85 backdrop-blur transition hover:bg-white/10 disabled:opacity-30" :aria-label="'Próximo ano' + (neighbor(1) ? ': ' + neighbor(1).year : '')"><span x-text="neighbor(1)?.year"></span> →</button>
                    </div>
                </div>

                <dl class="mt-8 grid grid-cols-2 gap-x-6 gap-y-5 border-t border-white/10 pt-6 lg:grid-cols-4" aria-label="Números do ano">
                    <div>
                        <dt class="kicker-hero">Jogos zerados</dt>
                        <dd class="mt-1 text-4xl font-black sm:text-5xl" x-text="fmt(d.games)"></dd>
                        <dd x-show="d.prev" x-cloak class="mt-1 text-xs" :class="d.prev && d.games >= d.prev.games ? 'text-ok' : 'text-white/55'" x-text="d.prev ? delta(d.games - d.prev.games) + ' que em ' + (d.year - 1) : ''"></dd>
                    </div>
                    <div>
                        <dt class="kicker-hero">Horas jogadas</dt>
                        <dd class="mt-1 text-4xl font-black sm:text-5xl" x-text="fmt(d.hours, 0)"></dd>
                        <dd class="mt-1 text-xs text-white/55" x-text="'≈ ' + fmt(d.hours / 24, 1) + ' dias seguidos'"></dd>
                    </div>
                    <div class="xp-ui">
                        <dt class="kicker-hero">XP ganho</dt>
                        <dd class="mt-1 text-4xl font-black text-brand-300 sm:text-5xl" x-text="'+' + fmt(d.xp)"></dd>
                        <dd class="mt-1 text-xs text-white/55" x-text="'≈ ' + fmt(d.xp / d.games) + ' por jogo'"></dd>
                    </div>
                    <div>
                        <dt class="kicker-hero">Nota média</dt>
                        <dd class="mt-1 flex items-center gap-2 text-4xl font-black sm:text-5xl">
                            <span x-text="fmt(d.avg_rating, 1)"></span>
                            <span class="rounded-lg px-2 py-0.5 text-sm font-black ring-1 ring-black/30" :class="UI.ratingClass(Math.round(d.avg_rating))" :style="UI.ratingStyle(Math.round(d.avg_rating))">/ 11</span>
                        </dd>
                        <dd class="mt-1 text-xs text-white/55"><span x-show="d.peak_month" x-text="d.peak_month ? 'Pico em ' + d.peak_month.label + ' (' + d.peak_month.games + ' jogos)' : ''"></span></dd>
                    </div>
                </dl>
            </header>

            <!-- capítulos do ano -->
            <nav class="sticky top-2 z-20 mt-6 flex justify-center" aria-label="Capítulos">
                <ol class="flex max-w-full gap-1 overflow-x-auto rounded-full border border-slate-50/10 bg-slate-900/90 p-1 text-sm font-semibold shadow-lg backdrop-blur">
                    <template x-for="(c, i) in chapters" :key="c.id">
                        <li><a :href="'#' + c.id" @click.prevent="document.getElementById(c.id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })" class="flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-slate-300 transition hover:bg-slate-50/10 hover:text-slate-50">
                            <span class="text-xs text-slate-500" x-text="i + 1"></span><span x-text="c.l"></span>
                        </a></li>
                    </template>
                </ol>
            </nav>

            <!-- Top 3 -->
            <section id="cap-podio" class="mt-10 scroll-mt-20" aria-labelledby="h-top">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.25em] text-brand-400">Capítulo 1</p>
                        <h2 id="h-top" class="text-2xl font-bold text-slate-50 sm:text-3xl">O pódio de <span x-text="d.year"></span></h2>
                        <p class="text-sm text-slate-400">Os melhores jogos que você zerou neste ano, não importa quando foram lançados.</p>
                    </div>
                    <button type="button" x-show="d.can_pick && !editing" x-cloak @click="openEditor()" class="btn"
                            x-text="filled ? 'Editar top 3' : 'Montar meu top 3'"></button>
                </div>
                <p x-show="!d.can_pick" x-cloak class="mt-4 empty !p-6">
                    <template x-if="d.games < minTop"><span>Zere pelo menos <strong class="text-slate-200" x-text="minTop"></strong> jogos em <span x-text="d.year"></span> para montar o seu top 3.</span></template>
                    <template x-if="d.games >= minTop"><span>Nenhum jogo de <span x-text="d.year"></span> tem nota 9, 10 ou 11, e só esses podem ser eleitos para o pódio.</span></template>
                </p>
                <p x-show="d.can_pick" x-cloak class="mt-2 text-xs text-slate-500">Regra do pódio: só jogos com nota 9, 10 ou 11 podem ser eleitos<span x-show="d.eligible < 3"> (neste ano há <span x-text="d.eligible"></span>)</span>.</p>

                <ol x-show="d.can_pick" class="mt-8 grid items-end gap-x-6 gap-y-8 md:grid-cols-3">
                    <template x-for="s in podium" :key="s.pos">
                        <li :class="s.order" class="mx-auto w-full" :style="`max-width:${s.pos === 1 ? '19rem' : '16rem'}`">
                            <!-- vaga ocupada -->
                            <template x-if="s.game">
                                <div>
                                    <div class="flex items-center justify-center pb-2">
                                        <span class="grid h-10 w-10 place-items-center rounded-full text-lg font-black shadow-lg ring-1 ring-black/30" :class="s.pos === 1 ? 'rating-gold' : ''" :style="s.pos === 1 ? 'color:#3b1d00' : s.medal" x-text="s.pos"></span>
                                    </div>
                                    <div class="rounded-xl p-[3px]" :class="s.pos === 1 ? 'rating-gold shadow-[0_0_44px_rgba(251,191,36,.3)]' : ''" :style="s.pos === 1 ? '' : 'background:' + s.frame">
                                        <div class="case !shadow-none">
                                            <div class="case-band"><span x-text="s.game.platform"></span><span class="case-date" x-text="UI.fmtDate(s.game.finished_at)"></span></div>
                                            <div class="case-art">
                                                <img x-show="s.game.cover_url" :src="s.game.cover_url" :alt="'Capa de ' + s.game.title" class="absolute inset-0 h-full w-full object-cover">
                                                <div x-show="!s.game.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center"><span class="text-6xl font-bold text-white/80" x-text="UI.initials(s.game.title)"></span></div>
                                                <span class="absolute left-2.5 top-2.5 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40" :class="UI.ratingClass(s.game.rating)" :style="UI.ratingStyle(s.game.rating)" x-text="s.game.rating"></span>
                                                <span class="absolute right-2.5 top-2.5 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40" :style="UI.diffStyle(s.game.difficulty)" x-text="s.game.difficulty"></span>
                                                <div class="absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 px-3 pb-2.5 pt-2 backdrop-blur-md">
                                                    <p class="truncate text-center text-sm font-bold leading-tight text-white" :title="s.game.title" x-text="s.game.title"></p>
                                                    <div class="mt-1 flex items-center justify-between gap-2 text-xs font-medium text-white/85">
                                                        <span class="min-w-0 truncate" x-text="s.game.genre || '—'"></span>
                                                        <span class="shrink-0 tabular-nums" x-text="UI.fmtTime(s.game.playtime_minutes)"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- pedestal -->
                                    <div class="mt-3 grid place-items-center rounded-t-lg text-3xl font-black tracking-tight ring-1 ring-black/20" :style="`height:${s.h}rem;background:${s.frame};color:${s.ink}`" x-text="s.pos + 'º'"></div>
                                </div>
                            </template>
                            <!-- vaga vazia -->
                            <template x-if="!s.game">
                                <div>
                                    <div class="flex items-center justify-center pb-2"><span class="grid h-10 w-10 place-items-center rounded-full bg-slate-50/10 text-lg font-black text-slate-400" x-text="s.pos"></span></div>
                                    <button type="button" @click="openEditor(s.pos - 1)" class="grid aspect-[16/25] w-full place-items-center rounded-xl border-2 border-dashed border-slate-50/20 text-center text-sm text-slate-400 transition hover:border-brand-400/60 hover:text-brand-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                        <span><span class="block text-3xl">+</span>Escolher o <span x-text="s.pos"></span>º lugar</span>
                                    </button>
                                    <div class="mt-3 grid place-items-center rounded-t-lg bg-slate-50/5 text-3xl font-black text-slate-500" :style="`height:${s.h}rem`" x-text="s.pos + 'º'"></div>
                                </div>
                            </template>
                        </li>
                    </template>
                </ol>

                <!-- Seletor do top 3 -->
                <div x-show="editing" x-cloak class="mt-8 panel p-5 sm:p-6" aria-label="Escolher o top 3">
                    <p class="t-section">Monte o seu top 3</p>
                    <p class="text-sm text-slate-400">Toque em uma posição e depois no jogo. Tocar de novo no mesmo jogo tira ele do top. <strong class="text-slate-200">Só jogos com nota 9, 10 ou 11 podem ser eleitos.</strong></p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Posição que está sendo preenchida">
                        <template x-for="(id, i) in slots" :key="i">
                            <div class="flex items-center gap-2 rounded-xl border p-2.5 transition" :class="active === i ? 'border-brand-400 bg-brand-500/10' : 'border-slate-50/10 bg-slate-950/40'">
                                <button type="button" role="radio" :aria-checked="active === i" @click="activate(i)" class="flex min-w-0 flex-1 items-center gap-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-base font-black ring-1 ring-black/20" :style="'background:' + medalBg(i + 1) + ';color:' + medalInk(i + 1)" x-text="i + 1"></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-50" x-text="id ? byId(id).title : 'Vazio'"></span>
                                        <span class="block truncate text-xs text-slate-400" x-text="id ? byId(id).platform : (active === i ? 'Escolha um jogo abaixo' : 'Toque para escolher')"></span>
                                    </span>
                                </button>
                                <button type="button" x-show="id" x-cloak @click="clearSlot(i)" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-50/10 hover:text-slate-50" :aria-label="'Tirar o ' + (i + 1) + 'º lugar'">
                                    <?= icon('close', 'h-4 w-4') ?>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <label class="relative min-w-[14rem] flex-1">
                            <span class="sr-only">Buscar jogo</span>
                            <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                            <input type="search" x-model="q" @input="limit = 40" placeholder="Buscar entre os jogos nota 9, 10 ou 11 deste ano…" class="w-full rounded-lg border border-slate-50/10 bg-slate-950/60 py-2.5 pl-9 pr-3 text-slate-50 placeholder-slate-500 outline-none transition focus:ring-2 focus:ring-brand-500">
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-400">Ordenar
                            <select x-model="sort" class="field">
                                <option value="rating">Maior nota</option>
                                <option value="recent">Mais recentes</option>
                                <option value="hours">Mais horas</option>
                                <option value="title">Nome A–Z</option>
                            </select>
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" aria-live="polite"><span x-text="filtered.length"></span> <span x-text="filtered.length === 1 ? 'jogo elegível' : 'jogos elegíveis'"></span> <span x-text="'(nota 9 a 11, de ' + d.games + ' zerados em ' + d.year + ')'"></span></p>

                    <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                        <template x-for="g in filtered.slice(0, limit)" :key="g.id">
                            <li>
                                <button type="button" @click="assign(g)" :aria-pressed="slots.includes(g.id)" class="group block w-full rounded-xl text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                                    <div class="case transition" :class="slots.includes(g.id) ? '!scale-[1.03] !shadow-[0_0_0_3px_#fbbf24,0_14px_30px_rgba(251,191,36,.35)]' : 'opacity-90'">
                                        <div class="case-band"><span x-text="g.platform"></span><span class="case-date" x-text="UI.fmtDate(g.finished_at)"></span></div>
                                        <div class="case-art">
                                            <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                            <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center"><span class="text-4xl font-bold text-white/80" x-text="UI.initials(g.title)"></span></div>
                                            <span class="absolute left-2 top-2 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                                            <span x-show="slots.includes(g.id)" class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full text-base font-black shadow-lg ring-1 ring-black/40" :style="'background:' + medalBg(slots.indexOf(g.id) + 1) + ';color:' + medalInk(slots.indexOf(g.id) + 1)" x-text="slots.indexOf(g.id) + 1"></span>
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
                    <div class="mt-5 text-center" x-show="filtered.length > limit" x-cloak>
                        <button type="button" @click="limit += 40" class="btn-ghost">Mostrar mais (<span x-text="filtered.length - limit"></span> restantes)</button>
                    </div>

                    <div class="sticky bottom-0 z-10 -mx-5 mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-50/10 bg-slate-900/95 px-5 py-3 pr-24 backdrop-blur sm:-mx-6 sm:px-6 sm:pr-24">
                        <p x-show="editErr" x-cloak role="alert" class="mr-auto text-sm text-danger" x-text="editErr"></p>
                        <button type="button" @click="closeEditor()" class="btn-ghost">Cancelar</button>
                        <button type="button" @click="saveTop()" :disabled="saving" class="rating-gold rounded-lg px-5 py-2.5 font-bold text-[#3b1d00] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300 disabled:opacity-60" x-text="saving ? 'Salvando…' : 'Salvar top 3'"></button>
                    </div>
                </div>
            </section>

            <!-- ===== Capítulo 2: a jornada, mês a mês, com as capas ===== -->
            <section id="cap-jornada" class="mt-14 scroll-mt-20" aria-labelledby="h-months">
                <p class="text-xs font-bold uppercase tracking-[.25em] text-brand-400">Capítulo 2</p>
                <h2 id="h-months" class="text-2xl font-bold text-slate-50 sm:text-3xl">A jornada</h2>
                <p class="text-sm text-slate-400">Cada mês do ano com os jogos que você terminou nele. O mês mais movimentado brilha em dourado.</p>
                <ol class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    <template x-for="m in d.months" :key="m.m">
                        <li class="relative min-w-0 overflow-hidden rounded-2xl border p-3" :class="isPeak(m) ? 'border-gold-400/60 bg-gold-400/10' : (m.games ? 'border-slate-50/10 bg-slate-900/70' : 'border-dashed border-slate-50/10')">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-lg font-black capitalize" :class="isPeak(m) ? 'text-gold-500' : (m.games ? 'text-slate-50' : 'text-slate-500')" x-text="m.label"></p>
                                <p class="text-xs tabular-nums text-slate-400" x-text="m.games ? m.games + (m.games === 1 ? ' jogo' : ' jogos') : 'pausa'"></p>
                            </div>
                            <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-50/10"><div class="h-full rounded-full" :class="isPeak(m) ? 'rating-gold' : 'bg-brand-500'" :style="'width:' + (m.games / maxMonth * 100) + '%'"></div></div>
                            <div class="mt-3 flex min-h-[3.5rem] -space-x-3">
                                <template x-for="(g, i) in monthGames(m.m).slice(0, 4)" :key="g.id">
                                    <span class="relative h-14 w-10 shrink-0 overflow-hidden rounded-md shadow-lg ring-2 ring-slate-900" :style="'z-index:' + (10 - i)" :title="g.title + ' · ' + UI.fmtDate(g.finished_at)">
                                        <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                        <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-2xs font-bold text-white/85" x-text="UI.initials(g.title)"></span>
                                    </span>
                                </template>
                                <span x-show="monthGames(m.m).length > 4" class="relative grid h-14 w-10 shrink-0 place-items-center rounded-md bg-slate-50/10 text-xs font-bold text-slate-300 ring-2 ring-slate-900" x-text="'+' + (monthGames(m.m).length - 4)"></span>
                            </div>
                            <p x-show="m.games" class="mt-2 truncate text-2xs text-slate-500" x-text="fmt(m.hours, 0) + 'h · melhor: ' + (monthGames(m.m).slice().sort((a, b) => b.rating - a.rating)[0]?.title ?? '')"></p>
                        </li>
                    </template>
                </ol>
            </section>

            <!-- ===== Capítulo 3: os recordes ===== -->
            <section id="cap-recordes" class="mt-14 scroll-mt-20" aria-labelledby="h-high">
                <p class="text-xs font-bold uppercase tracking-[.25em] text-brand-400">Capítulo 3</p>
                <h2 id="h-high" class="text-2xl font-bold text-slate-50 sm:text-3xl">Os recordes</h2>
                <ul class="mt-5 grid gap-4 md:grid-cols-3">
                    <template x-for="h in records" :key="h.label">
                        <li class="relative isolate flex min-h-[11rem] overflow-hidden rounded-2xl border border-slate-50/10 bg-hero text-white">
                            <img x-show="h.game.cover_url" :src="h.game.cover_url" alt="" loading="lazy" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-45">
                            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black via-black/70 to-black/20"></div>
                            <div class="mt-auto p-4">
                                <p class="kicker-hero" x-text="h.icon + ' ' + h.label"></p>
                                <p class="mt-1 text-3xl font-black tabular-nums" x-text="h.big"></p>
                                <p class="truncate font-semibold" :title="h.game.title" x-text="h.game.title"></p>
                                <p class="truncate text-xs text-white/60" x-text="h.sub"></p>
                            </div>
                        </li>
                    </template>
                </ul>
                <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <template x-for="h in minorRecords" :key="h.label">
                        <li class="min-w-0 panel p-4">
                            <p class="text-2xs font-medium uppercase tracking-wider text-slate-500" x-text="h.label"></p>
                            <p class="mt-1 truncate text-lg font-bold text-slate-50" :title="h.value" x-text="h.value"></p>
                            <p class="truncate text-xs text-slate-400" x-text="h.sub"></p>
                        </li>
                    </template>
                </ul>
            </section>

            <!-- ===== Capítulo 4: o perfil do ano ===== -->
            <section id="cap-perfil" class="mt-14 scroll-mt-20" aria-labelledby="h-prof">
                <p class="text-xs font-bold uppercase tracking-[.25em] text-brand-400">Capítulo 4</p>
                <h2 id="h-prof" class="text-2xl font-bold text-slate-50 sm:text-3xl">O seu perfil em <span x-text="d.year"></span></h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <template x-for="blk in [{ t: 'Gêneros favoritos', rows: d.genres }, { t: 'Sistemas mais jogados', rows: d.platforms }]" :key="blk.t">
                        <div class="panel p-5">
                            <h3 class="font-bold text-slate-50" x-text="blk.t"></h3>
                            <ol class="mt-4 space-y-3">
                                <template x-for="(r, i) in blk.rows" :key="r.name">
                                    <li>
                                        <div class="flex items-baseline justify-between gap-2 text-sm"><span class="truncate font-semibold text-slate-100"><span class="mr-2 text-slate-500" x-text="(i + 1) + '.'"></span><span x-text="r.name"></span></span><span class="shrink-0 tabular-nums text-slate-300" x-text="r.n"></span></div>
                                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-50/10"><div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="`width:${r.n / blk.rows[0].n * 100}%`"></div></div>
                                    </li>
                                </template>
                                <li x-show="!blk.rows.length" class="text-sm text-slate-500">Sem dados.</li>
                            </ol>
                        </div>
                    </template>
                    <div class="panel p-5">
                        <h3 class="font-bold text-slate-50">Notas e dificuldade</h3>
                        <p class="mt-4 text-xs text-slate-500">Como você avaliou o ano</p>
                        <div class="mt-2 flex h-16 items-end gap-1" aria-hidden="true">
                            <template x-for="n in 11" :key="n">
                                <div class="flex-1 rounded-t" :class="n === 11 ? 'rating-gold' : ''" :style="(n === 11 ? '' : 'background:' + UI.RATING_COLORS[n] + ';') + 'height:' + Math.max(2, ratingDist[n] / ratingMax * 64) + 'px'" :title="'Nota ' + n + ': ' + ratingDist[n]"></div>
                            </template>
                        </div>
                        <div class="mt-1 flex justify-between text-2xs text-slate-500"><span>1</span><span>11</span></div>
                        <p class="mt-5 text-xs text-slate-500">Dificuldade dos zerados</p>
                        <div class="mt-2 flex h-3 overflow-hidden rounded-full">
                            <template x-for="k in ['C', 'B', 'A', 'AA', 'AAA']" :key="k">
                                <span :style="UI.diffStyle(k) + ';width:' + (diffDist[k] / d.candidates.length * 100) + '%'" :title="k + ': ' + diffDist[k]"></span>
                            </template>
                        </div>
                        <p class="mt-2 flex flex-wrap gap-x-3 text-xs text-slate-400">
                            <template x-for="k in ['C', 'B', 'A', 'AA', 'AAA']" :key="k"><span x-show="diffDist[k]" x-text="k + ' ' + diffDist[k]"></span></template>
                        </p>
                    </div>
                </div>
            </section>

            <!-- ===== Modo história: o ano em slides ===== -->
            <div x-show="story.open" x-cloak x-transition.opacity class="fixed inset-0 z-viewer grid place-items-center bg-black p-4 text-white" role="dialog" aria-modal="true" aria-label="O seu ano em slides"
                 @keydown.escape.window="closeStory()" @keydown.arrow-right.window="story.open && nextSlide()" @keydown.arrow-left.window="story.open && prevSlide()">
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <img x-show="slide?.cover" :src="slide?.cover" alt="" class="h-full w-full scale-150 object-cover opacity-40 blur-3xl transition-all duration-700">
                    <div class="absolute inset-0 transition-all duration-700" :style="'background:' + (slide?.bg ?? 'transparent')"></div>
                </div>
                <div class="relative flex h-full max-h-[46rem] w-full max-w-md flex-col">
                    <!-- barras de progresso -->
                    <div class="flex gap-1">
                        <template x-for="(s, i) in slides" :key="i">
                            <span class="h-1 flex-1 overflow-hidden rounded-full bg-white/25"><span class="block h-full bg-white" :style="'width:' + (i < story.i ? 100 : i === story.i ? story.p : 0) + '%'"></span></span>
                        </template>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm text-white/70">
                        <span x-text="'Retrospectiva ' + d.year"></span>
                        <span class="flex gap-1">
                            <button type="button" @click="story.paused = !story.paused" class="rounded-full px-2 py-1 hover:bg-white/10" :aria-label="story.paused ? 'Continuar' : 'Pausar'" x-text="story.paused ? '▶' : '❚❚'"></button>
                            <button type="button" @click="closeStory()" class="rounded-full px-2 py-1 hover:bg-white/10" aria-label="Fechar">✕</button>
                        </span>
                    </div>
                    <!-- o slide -->
                    <div class="relative flex flex-1 flex-col items-center justify-center text-center" @click="nextSlide()">
                        <template x-if="slide">
                            <div class="w-full" :key="story.i">
                                <p class="text-sm font-bold uppercase tracking-[.3em] text-white/70" x-text="slide.kicker"></p>
                                <img x-show="slide.poster" :src="slide.poster" alt="" class="mx-auto mt-6 aspect-[3/4] w-44 rounded-xl object-cover shadow-2xl ring-4 ring-white/20">
                                <p class="mt-6 text-6xl font-black leading-none tracking-tight sm:text-7xl" x-text="slide.big"></p>
                                <p class="mx-auto mt-4 max-w-xs text-lg font-semibold leading-snug text-white/90" x-text="slide.text"></p>
                                <ol x-show="slide.list" class="mx-auto mt-6 max-w-xs space-y-2 text-left">
                                    <template x-for="(t, i) in slide.list ?? []" :key="i">
                                        <li class="flex items-center gap-3 rounded-xl bg-white/10 p-2 backdrop-blur">
                                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-black" :style="'background:' + medalBg(i + 1) + ';color:' + medalInk(i + 1)" x-text="i + 1"></span>
                                            <img x-show="t.cover_url" :src="t.cover_url" alt="" class="h-12 w-9 shrink-0 rounded object-cover">
                                            <span class="min-w-0 truncate font-semibold" x-text="t.title"></span>
                                        </li>
                                    </template>
                                </ol>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" @click="prevSlide()" :disabled="story.i === 0" class="rounded-full border border-white/25 px-4 py-2 text-sm font-semibold hover:bg-white/10 disabled:opacity-30">← Voltar</button>
                        <span class="text-xs text-white/50" x-text="(story.i + 1) + ' / ' + slides.length"></span>
                        <button type="button" @click="nextSlide()" class="rounded-full bg-white px-4 py-2 text-sm font-bold text-black hover:bg-white/85" x-text="story.i === slides.length - 1 ? 'Fechar' : 'Próximo →'"></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</main>

<script>
function retro(demo) {
    const base = window.APP.base;
    const MEDAL = {
        1: { bg: 'linear-gradient(135deg,#fde047,#f59e0b)', ink: '#3b1d00' },
        2: { bg: 'linear-gradient(135deg,#f3f4f6,#9ca3af)', ink: '#1f2937' },
        3: { bg: 'linear-gradient(135deg,#fdba74,#c2410c)', ink: '#2a1100' },
    };
    return {
        demo: !!demo,
        minTop: 3,
        years: [],
        d: null,                 // dados do ano em exibição
        ready: false,
        loading: false,
        error: '',
        // seletor do top 3
        editing: false, slots: [null, null, null], active: 0, q: '', sort: 'rating', limit: 40, saving: false, editErr: '',

        fmt: (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0),
        delta: (n) => (n > 0 ? '+' : n < 0 ? '−' : '±') + Math.abs(n) + (Math.abs(n) === 1 ? ' jogo' : ' jogos'),
        medalBg: (p) => MEDAL[p]?.bg ?? 'transparent',   // p = 0 quando o jogo ainda não está no top 3
        medalInk: (p) => MEDAL[p]?.ink ?? 'inherit',

        // ---------- navegação entre anos ----------
        neighbor(dir) { const i = this.years.findIndex((y) => y.year === this.d?.year); return this.years[i - dir] ?? null; },
        step(dir) { const n = this.neighbor(dir); if (n) this.go(n.year); },
        async go(year) {
            if (year === this.d?.year) return;
            history.replaceState(null, '', location.pathname + location.search + '#' + year);
            await this.loadYear(year);
        },
        async loadYear(year) {
            this.editing = false;
            if (this.demo) return; // o exemplo só tem um ano
            this.loading = true; this.error = '';
            try { this.d = (await api.get('/api/retrospective/' + year)).data; }
            catch (e) { this.error = e.message; }
            finally { this.loading = false; window.scrollTo({ top: 0 }); }
        },

        async init() {
            this.error = '';
            try {
                if (this.demo) { this.years = demo.years; this.d = demo.data; this.ready = true; return; }
                this.years = (await api.get('/api/retrospective')).data;
                this.ready = true;
                if (!this.years.length) return;
                const h = Number(location.hash.slice(1));
                const year = this.years.some((y) => y.year === h) ? h : this.years[0].year;
                this.d = (await api.get('/api/retrospective/' + year)).data;
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },

        // ---------- dados derivados ----------
        get filled() { return !!this.d.top.some(Boolean); },
        /** capa do fundo do destaque: o nº 1 do ano (ou o jogo de melhor nota) */
        get heroCover() { return this.d.top[0]?.cover_url ?? this.d.highlights?.best?.cover_url ?? null; },
        /** o ano numa frase */
        shareCard() {
            const d = this.d;
            ShareCard.download({ kicker: 'Retrospectiva', title: 'Meu ' + d.year + ' em jogos', sub: this.yearLine, file: 'retrospectiva-' + d.year,
                stats: [[this.fmt(d.games), 'jogos zerados'], [this.fmt(d.hours, 0) + 'h', 'jogadas'], [this.fmt(d.xp), 'XP'], [this.fmt(d.avg_rating, 1), 'nota média']] });
        },
        get yearLine() {
            const d = this.d, days = Math.round(d.hours / 24);
            const parts = [`${this.fmt(d.games)} ${d.games === 1 ? 'jogo zerado' : 'jogos zerados'} e ${this.fmt(d.hours, 0)} horas: o equivalente a ${days} ${days === 1 ? 'dia' : 'dias'} seguidos jogando.`];
            if (d.peak_month) { const full = { jan: 'janeiro', fev: 'fevereiro', mar: 'março', abr: 'abril', mai: 'maio', jun: 'junho', jul: 'julho', ago: 'agosto', set: 'setembro', out: 'outubro', nov: 'novembro', dez: 'dezembro' }; const l = d.peak_month.label.toLowerCase(); parts.push(`Seu mês mais forte foi ${full[l.slice(0, 3)] ?? l}.`); }
            if (d.top[0]) parts.push(`E o jogo do ano foi ${d.top[0].title}.`);
            else if (d.genres[0]) parts.push(`O gênero do ano: ${d.genres[0].name}.`);
            return parts.join(' ');
        },
        get maxMonth() { return Math.max(1, ...this.d.months.map((m) => m.games)); },
        isPeak(m) { return this.d.peak_month && m.label === this.d.peak_month.label && m.games > 0; },
        chapters: [{ id: 'cap-podio', l: 'O pódio' }, { id: 'cap-jornada', l: 'A jornada' }, { id: 'cap-recordes', l: 'Recordes' }, { id: 'cap-perfil', l: 'Seu perfil' }],
        /** jogos zerados em um mês (1–12), na ordem do calendário */
        monthGames(m) {
            return this.d.candidates.filter((g) => Number(g.finished_at.slice(5, 7)) === m).sort((a, b) => a.finished_at.localeCompare(b.finished_at));
        },
        get ratingDist() { const c = {}; for (let n = 1; n <= 11; n++) c[n] = 0; for (const g of this.d.candidates) c[g.rating] = (c[g.rating] ?? 0) + 1; return c; },
        get ratingMax() { return Math.max(1, ...Object.values(this.ratingDist)); },
        get diffDist() { const c = { C: 0, B: 0, A: 0, AA: 0, AAA: 0 }; for (const g of this.d.candidates) c[g.difficulty] = (c[g.difficulty] ?? 0) + 1; return c; },
        /** os três recordes com capa (cards grandes) */
        get records() {
            const h = this.d.highlights, t = UI.fmtTime;
            return [
                { icon: '⏳', label: 'A maior jornada', game: h.longest, big: t(h.longest.playtime_minutes), sub: h.longest.platform },
                { icon: '⚡', label: 'O mais rápido', game: h.shortest, big: t(h.shortest.playtime_minutes), sub: h.shortest.platform },
                { icon: '⭐', label: 'A maior nota', game: h.best, big: h.best.rating + ' / 11', sub: h.elevens > 1 ? '+' + (h.elevens - 1) + ' com nota 11' : h.best.platform },
            ];
        },
        get minorRecords() {
            const h = this.d.highlights, d = this.d;
            const date = (g) => UI.fmtDate(g.finished_at);
            return [
                { label: 'Primeiro do ano', value: h.first.title, sub: date(h.first) },
                { label: 'Último do ano', value: h.last.title, sub: date(h.last) },
                { label: 'Lançados em ' + d.year, value: h.released_now + (h.released_now === 1 ? ' jogo' : ' jogos'), sub: 'novidades zeradas no ano' },
                { label: 'Nota 11', value: h.elevens + (h.elevens === 1 ? ' jogo' : ' jogos'), sub: 'os lendários do ano' },
                { label: 'Dificuldade AAA', value: h.triple_a + (h.triple_a === 1 ? ' jogo' : ' jogos'), sub: 'os mais difíceis' },
            ];
        },

        // ---------- modo história ----------
        story: { open: false, i: 0, p: 0, paused: false, timer: null },
        get slides() {
            const d = this.d, h = d.highlights, days = Math.round(d.hours / 24);
            const full = { jan: 'janeiro', fev: 'fevereiro', mar: 'março', abr: 'abril', mai: 'maio', jun: 'junho', jul: 'julho', ago: 'agosto', set: 'setembro', out: 'outubro', nov: 'novembro', dez: 'dezembro' };
            const s = [
                { kicker: 'O seu ano gamer', big: String(d.year), text: 'Vamos relembrar tudo o que você jogou?', bg: 'radial-gradient(circle at 30% 20%,rgb(124 58 237/.7),rgb(0 0 0/.85))', cover: this.heroCover },
                { kicker: 'Você zerou', big: this.fmt(d.games), text: d.games === 1 ? 'jogo do começo ao fim.' : 'jogos do começo ao fim.' + (d.prev && d.prev.games ? ' ' + this.delta(d.games - d.prev.games) + ' que no ano anterior.' : ''), bg: 'linear-gradient(160deg,rgb(14 116 144/.8),rgb(0 0 0/.9))', cover: h.first.cover_url },
                { kicker: 'Foram', big: this.fmt(d.hours, 0) + 'h', text: `de jogo: o mesmo que ${days} ${days === 1 ? 'dia' : 'dias'} seguidos sem parar.`, bg: 'linear-gradient(160deg,rgb(190 18 60/.75),rgb(0 0 0/.9))', cover: h.longest.cover_url },
            ];
            if (d.peak_month) { const l = d.peak_month.label.toLowerCase(); s.push({ kicker: 'Seu mês mais forte', big: full[l.slice(0, 3)] ?? l, text: `${d.peak_month.games} jogos zerados em um mês só.`, bg: 'linear-gradient(160deg,rgb(217 119 6/.8),rgb(0 0 0/.9))', cover: this.monthGames(d.months.find((m) => this.isPeak(m))?.m)[0]?.cover_url }); }
            if (d.genres[0]) s.push({ kicker: 'O gênero do ano', big: d.genres[0].name, text: `${d.genres[0].n} jogos. ${d.platforms[0] ? 'E o sistema que mais ligou foi ' + d.platforms[0].name + '.' : ''}`, bg: 'linear-gradient(160deg,rgb(22 163 74/.75),rgb(0 0 0/.9))', cover: null });
            s.push({ kicker: 'A maior jornada', big: UI.fmtTime(h.longest.playtime_minutes), text: h.longest.title, poster: h.longest.cover_url, bg: 'linear-gradient(160deg,rgb(30 64 175/.8),rgb(0 0 0/.9))', cover: h.longest.cover_url });
            const top = d.top.filter(Boolean);
            if (top.length) s.push({ kicker: 'O seu pódio', big: '🏆', text: 'Os melhores de ' + d.year + ', escolhidos por você:', list: top, bg: 'linear-gradient(160deg,rgb(202 138 4/.8),rgb(0 0 0/.9))', cover: top[0].cover_url });
            else s.push({ kicker: 'A maior nota do ano', big: h.best.rating + '/11', text: h.best.title, poster: h.best.cover_url, bg: 'linear-gradient(160deg,rgb(202 138 4/.8),rgb(0 0 0/.9))', cover: h.best.cover_url });
            s.push({ kicker: 'Obrigado por jogar', big: d.year + ' ✓', text: 'Que venha o próximo ano. Bons jogos!', bg: 'radial-gradient(circle at 70% 80%,rgb(124 58 237/.6),rgb(0 0 0/.9))', cover: this.heroCover });
            return s;
        },
        get slide() { return this.slides[this.story.i] ?? null; },
        openStory() {
            Object.assign(this.story, { open: true, i: 0, p: 0, paused: matchMedia('(prefers-reduced-motion: reduce)').matches });
            document.documentElement.style.overflow = 'hidden';
            clearInterval(this.story.timer);
            this.story.timer = setInterval(() => {
                if (this.story.paused) return;
                this.story.p += 2;                       // 5 s por slide
                if (this.story.p >= 100) this.nextSlide();
            }, 100);
        },
        closeStory() { clearInterval(this.story.timer); this.story.open = false; document.documentElement.style.overflow = ''; },
        nextSlide() { if (this.story.i >= this.slides.length - 1) return this.closeStory(); this.story.i++; this.story.p = 0; },
        prevSlide() { if (this.story.i > 0) { this.story.i--; this.story.p = 0; } },
        /** Ordem visual no desktop: 2º - 1º - 3º; no celular: 1º, 2º, 3º. */
        get podium() {
            const cfg = {
                1: { order: 'order-1 md:order-2', h: 4.5, frame: MEDAL[1].bg, ink: MEDAL[1].ink, medal: 'background:' + MEDAL[1].bg + ';color:' + MEDAL[1].ink },
                2: { order: 'order-2 md:order-1', h: 3.25, frame: MEDAL[2].bg, ink: MEDAL[2].ink, medal: 'background:' + MEDAL[2].bg + ';color:' + MEDAL[2].ink },
                3: { order: 'order-3', h: 2.25, frame: MEDAL[3].bg, ink: MEDAL[3].ink, medal: 'background:' + MEDAL[3].bg + ';color:' + MEDAL[3].ink },
            };
            return [1, 2, 3].map((pos) => ({ pos, game: this.d.top[pos - 1], ...cfg[pos] }));
        },
        // ---------- seletor do top 3 ----------
        byId(id) { return this.d.candidates.find((g) => g.id === id); },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            const list = this.d.electable.filter((g) => !q || g.title.toLowerCase().includes(q));
            const cmp = {
                rating: null, // os candidatos já vêm por nota
                recent: (a, b) => b.finished_at.localeCompare(a.finished_at),
                hours: (a, b) => b.playtime_minutes - a.playtime_minutes,
                title: (a, b) => a.title.localeCompare(b.title, 'pt', { sensitivity: 'base' }),
            }[this.sort];
            return cmp ? [...list].sort(cmp) : list;
        },
        openEditor(slot = null) {
            this.slots = this.d.top.map((g) => g?.id ?? null);
            const firstEmpty = this.slots.findIndex((x) => x === null);
            this.active = slot ?? (firstEmpty === -1 ? 0 : firstEmpty);
            this.q = ''; this.sort = 'rating'; this.limit = 40; this.editErr = ''; this.editing = true;
            this.$nextTick(() => document.querySelector('[aria-label="Escolher o top 3"]')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },
        closeEditor() { this.editing = false; this.editErr = ''; },
        /** Só dá para preencher uma posição se as anteriores já estiverem preenchidas. */
        activate(i) {
            const firstEmpty = this.slots.findIndex((x) => x === null);
            this.active = firstEmpty !== -1 && i > firstEmpty ? firstEmpty : i;
        },
        assign(g) {
            const at = this.slots.indexOf(g.id);
            if (at === this.active) return this.clearSlot(at);          // tocar de novo tira do top
            if (at !== -1) this.slots[at] = this.slots[this.active];     // já estava em outra posição: troca de lugar
            this.slots[this.active] = g.id;
            const next = this.slots.findIndex((x) => x === null);
            if (next !== -1) this.active = next;
        },
        /** Tira uma posição e sobe quem estava abaixo (sem deixar buraco no pódio). */
        clearSlot(i) {
            const ids = this.slots.filter((x, k) => k !== i && x !== null);
            this.slots = [ids[0] ?? null, ids[1] ?? null, ids[2] ?? null];
            this.active = Math.min(this.active, ids.length);
        },
        async saveTop() {
            this.saving = true; this.editErr = '';
            try {
                if (this.demo) {
                    this.d.top = this.slots.map((id) => (id ? this.byId(id) : null));
                } else {
                    this.d = (await api.put(`/api/retrospective/${this.d.year}/top`, { slots: this.slots })).data;
                }
                this.editing = false;
            } catch (e) {
                this.editErr = Object.values(e.errors || {})[0] || e.message;
            } finally { this.saving = false; }
        },
    };
}
</script>
