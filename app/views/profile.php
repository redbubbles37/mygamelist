<?php /** @var array $me */ /** @var string $username */ ?>
<main class="page page-normal" x-data='profile(<?= e(json_encode($username)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <a href="<?= e(url('/ranking')) ?>" class="back-link">← Ranking</a>

    <div x-show="notFound" x-cloak class="mt-10 empty">
        <p class="t-section">Perfil não encontrado</p>
        <p class="mt-1 text-sm text-slate-400">Esse usuário não existe ou mantém o perfil oculto.</p>
        <a href="<?= e(url('/ranking')) ?>" class="btn mt-4 inline-block">Voltar ao ranking</a>
    </div>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <div x-show="!p && !notFound && !error" class="mt-5 animate-pulse space-y-4" aria-label="Carregando">
        <div class="h-72 rounded-3xl bg-slate-50/5"></div><div class="h-40 rounded-2xl bg-slate-50/5"></div>
    </div>

    <template x-if="p">
        <div>
            <!-- ========== Cartão de jogador ========== -->
            <section class="relative isolate mt-5 overflow-hidden rounded-3xl border border-slate-50/10 bg-hero text-white" aria-label="Cartão de jogador">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    <img x-show="showcase[0]?.cover_url" :src="showcase[0]?.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-30 blur-3xl">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/65 to-black/40"></div>
                    <div class="absolute -left-16 -top-16 h-64 w-64 rounded-full opacity-40 blur-3xl" :style="UI.avatarStyle(p.username)"></div>
                </div>

                <div class="grid gap-6 p-5 sm:p-8 md:grid-cols-[1fr_auto] md:items-center">
                    <div class="flex flex-wrap items-center gap-5 sm:gap-7">
                        <!-- avatar com o anel de XP -->
                        <div class="relative h-32 w-32 shrink-0">
                            <svg class="absolute inset-0 h-full w-full -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                                <circle cx="18" cy="18" r="16.5" fill="none" stroke="rgb(255 255 255 / .12)" stroke-width="1.6"/>
                                <circle cx="18" cy="18" r="16.5" fill="none" stroke="#fbbf24" stroke-width="1.6" stroke-linecap="round" :stroke-dasharray="(p.xp.progress_pct * 1.037) + ' 104'"/>
                            </svg>
                            <div class="absolute inset-[10px] grid place-items-center rounded-full text-5xl font-black shadow-2xl" :style="UI.avatarStyle(p.username)" aria-hidden="true" x-html="UI.avatarContent(p.username)"></div>
                            <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-gold-400 px-2.5 py-0.5 text-xs font-black text-black shadow-lg" x-text="'Nível ' + p.xp.level"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="kicker-hero" x-text="p.is_me ? 'Como os amigos te veem' : 'Perfil de jogador'"></p>
                            <h1 class="mt-1 flex flex-wrap items-center gap-2 t-hero">
                                <span class="truncate" x-text="p.username"></span>
                                <span x-show="p.is_me" class="rounded-md bg-white px-2 py-0.5 text-xs font-black uppercase tracking-wider text-black">Você</span>
                            </h1>
                            <p class="mt-1 text-sm text-white/70" x-text="'Jogando desde ' + memberSince + (yearsActive ? ' · ' + yearsActive : '')"></p>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                <a x-show="p.rank" href="<?= e(url('/ranking')) ?>" class="rounded-full border border-gold-300/40 bg-gold-400/15 px-3 py-1 font-bold text-gold-200 hover:bg-gold-400/25" x-text="medal(p.rank) + ' #' + p.rank + ' de ' + p.players + ' no ranking'"></a>
                                <span class="rounded-full bg-white/10 px-3 py-1 text-white/80" x-text="fmt(p.xp.total_xp) + ' XP' + (p.xp.next_level_xp ? ' · faltam ' + fmt(p.xp.next_level_xp - p.xp.total_xp) + ' para o nível ' + (p.xp.level + 1) : '')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- vitrine: os 3 favoritos em leque -->
                    <div x-show="showcase.length" class="relative mx-auto hidden h-44 w-56 sm:block" aria-label="Favoritos em destaque">
                        <template x-for="(g, i) in showcase" :key="i">
                            <div class="absolute top-2 h-40 w-28 overflow-hidden rounded-lg shadow-2xl ring-1 ring-white/20 transition duration-300 hover:-translate-y-2 hover:z-10 motion-reduce:transition-none"
                                 :style="['left:56px;z-index:3;transform:rotate(0deg)', 'left:0;z-index:2;transform:rotate(-9deg)', 'left:112px;z-index:1;transform:rotate(9deg)'][i]" :title="g.title + ' · nota ' + g.rating">
                                <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" class="h-full w-full object-cover">
                                <div x-show="!g.cover_url" class="grid h-full w-full place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-2xl font-bold text-white/80" x-text="UI.initials(g.title)"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- números -->
                <dl class="grid grid-cols-2 border-t border-white/10 bg-black/30 sm:grid-cols-4">
                    <template x-for="(s, i) in stats" :key="s.l">
                        <div class="px-5 py-4 sm:px-7" :class="[i % 2 ? 'border-l border-white/10' : '', i > 1 ? 'border-t border-white/10 sm:border-t-0' : '', i === 2 ? 'sm:border-l' : '']">
                            <dt class="text-xs text-white/55" x-text="s.l"></dt>
                            <dd class="mt-0.5 text-2xl font-black tabular-nums" x-html="s.v"></dd>
                        </div>
                    </template>
                </dl>
            </section>

            <!-- ========== Você × ele: lado a lado ========== -->
            <section x-show="p.versus" x-cloak class="mt-6 panel p-5 sm:p-6" aria-labelledby="h-vs">
                <template x-if="p.versus">
                    <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                        <div>
                            <h2 id="h-vs" class="flex items-center gap-2 text-lg font-bold text-slate-50">
                                <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-black" :style="UI.avatarStyle(p.versus.username)" x-html="UI.avatarContent(p.versus.username)"></span>
                                Você <span class="text-slate-500">×</span> <span x-text="p.username"></span>
                                <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-black" :style="UI.avatarStyle(p.username)" x-html="UI.avatarContent(p.username)"></span>
                            </h2>
                            <ul class="mt-4 space-y-3">
                                <template x-for="r in versusRows" :key="r.l">
                                    <li>
                                        <p class="flex justify-between text-xs text-slate-400"><span x-text="r.l"></span><span x-text="r.lead"></span></p>
                                        <div class="mt-1 grid grid-cols-2 gap-1">
                                            <div class="flex items-center justify-end gap-2">
                                                <span class="text-sm font-bold tabular-nums text-slate-50" x-text="r.a"></span>
                                                <div class="h-3 flex-1 overflow-hidden rounded-l-full bg-slate-50/5"><div class="ml-auto h-full rounded-l-full bg-brand-500" :style="'width:' + r.pa + '%'"></div></div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="h-3 flex-1 overflow-hidden rounded-r-full bg-slate-50/5"><div class="h-full rounded-r-full bg-gold-400" :style="'width:' + r.pb + '%'"></div></div>
                                                <span class="text-sm font-bold tabular-nums text-slate-50" x-text="r.b"></span>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                            <p class="mt-3 flex gap-4 text-xs text-slate-500"><span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-brand-500"></span>Você</span><span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-gold-400"></span><span x-text="p.username"></span></span></p>
                        </div>
                        <div class="self-start rounded-2xl border border-brand-400/30 bg-brand-500/10 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-brand-400">Em comum</p>
                            <p class="mt-1 text-4xl font-black tabular-nums text-slate-50" x-text="p.common?.count ?? 0"></p>
                            <p class="text-sm text-slate-300" x-text="(p.common?.count ?? 0) === 0 ? 'Vocês ainda não zeraram nenhum jogo em comum.' : ((p.common.count === 1 ? 'jogo que vocês dois zeraram' : 'jogos que vocês dois zeraram') + (affinity !== null ? ' · ' + affinity + '% de afinidade' : ''))"></p>
                            <ul x-show="p.common?.titles?.length" class="mt-3 flex flex-wrap gap-1.5">
                                <template x-for="t in p.common?.titles ?? []" :key="t"><li class="rounded-full bg-slate-50/10 px-2.5 py-1 text-xs text-slate-100" x-text="t"></li></template>
                            </ul>
                        </div>
                    </div>
                </template>
            </section>

            <!-- ========== Seu perfil: privacidade ========== -->
            <section x-show="p.is_me" x-cloak class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-5 py-4" :class="p.profile_public ? 'border-slate-50/10 bg-slate-900/70' : 'border-gold-400/40 bg-gold-400/10'">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-50" x-text="p.profile_public ? '👁 Esta é a página que os outros jogadores veem' : '🔒 Seu perfil está oculto'"></p>
                    <p class="text-xs text-slate-400" x-text="p.profile_public ? 'Desligue para sumir do ranking e fechar o perfil para os outros.' : 'Ninguém abre o seu perfil e você não aparece no ranking.'"></p>
                    <a href="<?= e(url('/conta')) ?>" class="mt-1 inline-block text-xs font-semibold text-brand-400 hover:underline">Gerenciar minha conta →</a>
                </div>
                <button type="button" role="switch" :aria-checked="p.profile_public" @click="togglePrivacy()" :disabled="saving"
                        class="relative h-7 w-12 shrink-0 rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 disabled:opacity-60"
                        :class="p.profile_public ? 'bg-brand-600' : 'bg-slate-50/20'">
                    <span class="absolute top-1 h-5 w-5 rounded-full bg-slate-50 transition-all" :class="p.profile_public ? 'left-6' : 'left-1'"></span>
                    <span class="sr-only" x-text="p.profile_public ? 'Perfil público' : 'Perfil oculto'"></span>
                </button>
            </section>

            <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <!-- ========== Coluna principal: os jogos ========== -->
                <div class="min-w-0 space-y-10">
                    <!-- favoritos -->
                    <section x-show="p.favorites.length" aria-labelledby="h-fav">
                        <h2 id="h-fav" class="t-section">Vitrine de favoritos</h2>
                        <p class="text-sm text-slate-400">Os jogos com as maiores notas.</p>
                        <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3">
                            <template x-for="(g, i) in p.favorites" :key="'f' + i">
                                <li>
                                    <a :href="g.id ? base + '/jogos/' + g.id : null" class="group block rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" :class="g.id ? '' : 'cursor-default'">
                                        <div class="case" :class="g.id ? '' : 'group-hover:!transform-none'">
                                            <div class="case-band" :style="UI.bandStyle(g.platform)"><span x-text="g.platform"></span><span class="case-date" x-text="'#' + (i + 1)"></span></div>
                                            <div class="case-art">
                                                <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                                <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center"><span class="text-4xl font-bold text-white/80" x-text="UI.initials(g.title)"></span></div>
                                                <span class="absolute left-2 top-2 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" :title="'Nota ' + g.rating + ' de 11'" x-text="g.rating"></span>
                                                <span class="absolute right-2 top-2 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40" :style="UI.diffStyle(g.difficulty)" :title="'Dificuldade ' + g.difficulty" x-text="g.difficulty"></span>
                                                <div x-show="!g.cover_url" class="absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 px-2.5 pb-2 pt-1.5 backdrop-blur-md"><p class="truncate text-center text-sm font-bold text-white" x-text="g.title"></p></div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </section>

                    <!-- zerados recentemente: diário -->
                    <section x-show="p.recent.length" aria-labelledby="h-recent">
                        <h2 id="h-recent" class="t-section">Últimos zerados</h2>
                        <ol class="mt-4 divide-y divide-slate-50/5 overflow-hidden panel">
                            <template x-for="(g, i) in p.recent" :key="'r' + i">
                                <li>
                                    <a :href="g.id ? base + '/jogos/' + g.id : null" class="flex items-center gap-3 px-4 py-2.5" :class="g.id ? 'hover:bg-slate-50/5' : ''">
                                        <span class="w-14 shrink-0 text-xs tabular-nums text-slate-500" x-text="shortDate(g.finished_at)"></span>
                                        <span class="relative h-11 w-8 shrink-0 overflow-hidden rounded bg-slate-950">
                                            <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-semibold text-slate-50" x-text="g.title"></span>
                                            <span class="block truncate text-xs text-slate-500" x-text="g.platform + (g.genre ? ' · ' + g.genre : '') + (g.playtime_minutes ? ' · ' + UI.fmtTime(g.playtime_minutes) : '')"></span>
                                        </span>
                                        <span class="shrink-0 rounded-md px-2 py-0.5 text-sm font-black" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" x-text="g.rating"></span>
                                    </a>
                                </li>
                            </template>
                        </ol>
                    </section>

                    <!-- GOTY: faixa dourada -->
                    <section x-show="p.goty.length" x-cloak aria-labelledby="h-goty">
                        <h2 id="h-goty" class="t-section">GOTY por ano</h2>
                        <p class="text-sm text-slate-400">O melhor jogo lançado em cada ano, segundo <span x-text="p.is_me ? 'você' : p.username"></span>.</p>
                        <ul class="mt-4 flex snap-x gap-4 overflow-x-auto pb-3">
                            <template x-for="e in p.goty" :key="e.year">
                                <li class="w-32 shrink-0 snap-start">
                                    <div class="rating-gold rounded-xl p-[3px] shadow-[0_0_24px_rgba(251,191,36,.2)]">
                                        <div class="relative aspect-[3/4] overflow-hidden rounded-[9px] bg-slate-950">
                                            <img x-show="e.game.cover_url" :src="e.game.cover_url" :alt="'Capa de ' + e.game.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                            <div x-show="!e.game.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-3xl font-bold text-white/80" x-text="UI.initials(e.game.title)"></div>
                                            <span class="absolute inset-x-0 top-0 bg-gradient-to-b from-black/80 to-transparent px-2 pb-4 pt-1.5 text-center text-lg font-black tabular-nums text-gold-200" x-text="e.year"></span>
                                        </div>
                                    </div>
                                    <p class="mt-2 truncate text-sm font-semibold text-slate-100" :title="e.game.title" x-text="e.game.title"></p>
                                    <p x-show="e.note" class="line-clamp-2 text-xs italic text-slate-400" x-text="'“' + e.note + '”'"></p>
                                </li>
                            </template>
                        </ul>
                    </section>

                    <!-- top 3 de cada ano -->
                    <section x-show="p.retro.length" x-cloak aria-labelledby="h-retro">
                        <h2 id="h-retro" class="t-section">Pódio de cada ano</h2>
                        <p class="text-sm text-slate-400">Os favoritos que <span x-text="p.is_me ? 'você' : p.username"></span> zerou em cada ano.</p>
                        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                            <template x-for="r in p.retro" :key="r.year">
                                <li class="panel p-4">
                                    <p class="text-3xl font-black tabular-nums leading-none text-slate-50" x-text="r.year"></p>
                                    <ol class="mt-3 space-y-2">
                                        <template x-for="(g, i) in r.games" :key="g.id">
                                            <li class="flex items-center gap-3">
                                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-sm font-black ring-1 ring-black/20" :style="podium[i]" x-text="i + 1"></span>
                                                <div class="relative h-12 w-8 shrink-0 overflow-hidden rounded ring-1 ring-black/40">
                                                    <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                                    <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60"><span class="text-2xs font-bold text-white/85" x-text="UI.initials(g.title)"></span></div>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-slate-50" :title="g.title" x-text="g.title"></p>
                                                    <p class="truncate text-xs text-slate-400" x-text="g.platform + ' · nota ' + g.rating"></p>
                                                </div>
                                            </li>
                                        </template>
                                    </ol>
                                </li>
                            </template>
                        </ul>
                    </section>

                    <p x-show="!p.games" class="empty" x-text="p.is_me ? 'Registre o primeiro jogo zerado e ele aparece aqui.' : p.username + ' ainda não registrou nenhum jogo zerado.'"></p>
                </div>

                <!-- ========== Lateral: o DNA gamer ========== -->
                <aside class="min-w-0 space-y-5" aria-label="DNA gamer">
                    <section x-show="dna.genres.length" class="panel p-5">
                        <h2 class="kicker">Gêneros preferidos</h2>
                        <ul class="mt-3 space-y-2.5">
                            <template x-for="g in dna.genres" :key="g.name">
                                <li>
                                    <p class="flex justify-between gap-2 text-sm"><span class="truncate text-slate-200" x-text="g.name"></span><span class="shrink-0 tabular-nums text-slate-500" x-text="g.n"></span></p>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-50/10"><div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="'width:' + (g.n / dna.genres[0].n * 100) + '%'"></div></div>
                                </li>
                            </template>
                        </ul>
                    </section>

                    <section x-show="dna.platforms.length" class="panel p-5">
                        <h2 class="kicker">Onde joga</h2>
                        <ul class="mt-3 flex flex-wrap gap-1.5">
                            <template x-for="s in dna.platforms" :key="s.name">
                                <li class="rounded-md px-2 py-1 text-xs font-bold" :style="UI.bandStyle(s.name) + ';background:var(--band-bg);color:var(--band-fg)'" x-text="s.name + ' · ' + s.n"></li>
                            </template>
                        </ul>
                    </section>

                    <section x-show="diffTotal" class="panel p-5">
                        <h2 class="kicker">Dificuldade dos zerados</h2>
                        <div class="mt-3 flex h-4 overflow-hidden rounded-full" aria-hidden="true">
                            <template x-for="d in diffs" :key="d">
                                <span :style="UI.diffStyle(d) + ';width:' + ((dna.difficulty[d] ?? 0) / diffTotal * 100) + '%'" :title="d + ': ' + (dna.difficulty[d] ?? 0)"></span>
                            </template>
                        </div>
                        <ul class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-400">
                            <template x-for="d in diffs" :key="d">
                                <li x-show="dna.difficulty[d]"><span class="mr-1 inline-block h-2 w-2 rounded-full" :style="UI.diffStyle(d)"></span><span x-text="d + ' ' + (dna.difficulty[d] ?? 0)"></span></li>
                            </template>
                        </ul>
                    </section>

                    <section x-show="dna.years.length > 1" class="panel p-5">
                        <h2 class="kicker">Ritmo por ano</h2>
                        <div class="mt-3 flex h-20 items-end gap-1" aria-hidden="true">
                            <template x-for="y in dna.years.slice(-12)" :key="y.year">
                                <div class="flex flex-1 flex-col items-center justify-end gap-1">
                                    <div class="w-full rounded-t bg-brand-500/80" :style="'height:' + Math.max(4, y.n / yearMax * 64) + 'px'" :title="y.year + ': ' + y.n"></div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-1 flex justify-between text-2xs tabular-nums text-slate-500"><span x-text="dna.years.slice(-12)[0]?.year"></span><span x-text="dna.years.at(-1)?.year"></span></div>
                        <p class="mt-2 text-xs text-slate-400" x-text="bestYear ? 'Melhor ano: ' + bestYear.year + ' (' + bestYear.n + (bestYear.n === 1 ? ' jogo)' : ' jogos)') : ''"></p>
                    </section>

                    <section x-show="p.franchises.length" class="panel p-5">
                        <h2 class="kicker" x-text="'Franquias · ' + p.franchises_done + ' de ' + p.franchises_total + ' completas'"></h2>
                        <ul class="mt-3 space-y-2.5">
                            <template x-for="f in p.franchises" :key="f.name">
                                <li>
                                    <p class="flex justify-between gap-2 text-sm"><span class="truncate text-slate-200" :title="f.name" x-text="(f.total && f.done === f.total ? '🏆 ' : '') + f.name"></span><span class="shrink-0 tabular-nums" :class="f.total && f.done === f.total ? 'font-bold text-ok' : 'text-slate-500'" x-text="f.done + '/' + f.total"></span></p>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-50/10"><div class="h-full rounded-full" :class="f.total && f.done === f.total ? 'bg-ok' : 'bg-brand-500'" :style="'width:' + (f.total ? f.done / f.total * 100 : 0) + '%'"></div></div>
                                </li>
                            </template>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </template>
</main>

<script>
function profile(username) {
    return {
        base: window.APP.base,
        p: null,
        notFound: false,
        error: '',
        saving: false,
        diffs: ['C', 'B', 'A', 'AA', 'AAA'],
        podium: ['background:linear-gradient(135deg,#fde047,#f59e0b);color:#3b1d00', 'background:linear-gradient(135deg,#f3f4f6,#9ca3af);color:#1f2937', 'background:linear-gradient(135deg,#fdba74,#c2410c);color:#2a1100'],
        fmt: (v, dec = 0) => new Intl.NumberFormat('pt-BR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v ?? 0),
        medal: (r) => (r === 1 ? '🥇' : r === 2 ? '🥈' : r === 3 ? '🥉' : '🏅'),
        shortDate(d) {
            const [y, m, dd] = String(d).slice(0, 10).split('-').map(Number);
            return new Date(y, m - 1, dd).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: '2-digit' }).replace(/ de /g, ' ').replace('.', '');
        },
        get showcase() { return (this.p?.favorites ?? []).slice(0, 3); },
        get dna() { return { genres: [], platforms: [], years: [], ...(this.p?.dna ?? {}), difficulty: this.p?.dna?.difficulty && !Array.isArray(this.p.dna.difficulty) ? this.p.dna.difficulty : {} }; },
        get diffTotal() { return Object.values(this.dna.difficulty).reduce((a, b) => a + Number(b), 0); },
        get yearMax() { return Math.max(1, ...this.dna.years.map((y) => y.n)); },
        get bestYear() { return [...this.dna.years].sort((a, b) => b.n - a.n)[0] ?? null; },
        get yearsActive() {
            const y = this.dna.years;
            if (!y.length) return '';
            const span = y.at(-1).year - y[0].year + 1;
            return span > 1 ? `${span} anos zerando jogos` : '';
        },
        shareCard() {
            const p = this.p;
            ShareCard.download({ kicker: 'Jogador', title: p.username, sub: 'Nível ' + p.xp.level + ' · ' + this.fmt(p.xp.total_xp) + ' XP', file: 'perfil-' + p.username,
                stats: [[this.fmt(p.games), 'jogos zerados'], [this.fmt(p.hours, 0) + 'h', 'jogadas'], [p.avg_rating !== null ? this.fmt(p.avg_rating, 1) : '—', 'nota média'], [p.franchises_done + '/' + p.franchises_total, 'franquias completas']] });
        },
        get stats() {
            const p = this.p;
            return [
                { l: 'Jogos zerados', v: this.fmt(p.games) },
                { l: 'Horas jogadas', v: this.fmt(p.hours, 0) + '<span class="text-base text-white/50">h</span>' },
                { l: 'Nota média', v: p.avg_rating !== null ? this.fmt(p.avg_rating, 1) : '—' },
                { l: 'Franquias completas', v: p.franchises_done + '<span class="text-base text-white/50"> / ' + p.franchises_total + '</span>' },
            ];
        },
        /** comparação: você (a) × o dono do perfil (b) */
        get versusRows() {
            const a = this.p.versus, b = this.p;
            if (!a) return [];
            const row = (l, va, vb, fmt) => {
                const max = Math.max(va ?? 0, vb ?? 0) || 1;
                const lead = va === vb ? 'empate' : ((va ?? 0) > (vb ?? 0) ? 'você na frente' : b.username + ' na frente');
                return { l, a: va === null ? '—' : fmt(va), b: vb === null ? '—' : fmt(vb), pa: (va ?? 0) / max * 100, pb: (vb ?? 0) / max * 100, lead };
            };
            return [
                row('Nível', a.level, b.xp.level, (v) => v),
                row('Jogos zerados', a.games, b.games, (v) => this.fmt(v)),
                row('Horas jogadas', a.hours, b.hours, (v) => this.fmt(v, 0) + 'h'),
                row('Nota média', a.avg_rating, b.avg_rating, (v) => this.fmt(v, 1)),
            ];
        },
        /** afinidade: jogos em comum sobre o menor catálogo dos dois */
        get affinity() {
            const c = this.p.common?.count ?? 0, min = Math.min(this.p.games, this.p.versus?.games ?? 0);
            return c && min ? Math.round(c / min * 100) : null;
        },
        get memberSince() {
            const [y, m] = this.p.member_since.split('-').map(Number);
            return new Date(y, m - 1, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
        },
        async init() { await this.load(); },
        async load() {
            this.error = '';
            try {
                this.p = (await api.get('/api/users/' + encodeURIComponent(username))).data;
            } catch (e) {
                if (e.status === 401) return (location.href = this.base + '/login');
                if (e.status === 404) this.notFound = true; else this.error = e.message;
            }
        },
        async togglePrivacy() {
            this.saving = true;
            try {
                this.p.profile_public = (await api.put('/api/me/privacy', { profile_public: !this.p.profile_public })).profile_public;
            } catch (e) { this.error = e.message; } finally { this.saving = false; }
        },
    };
}
</script>
