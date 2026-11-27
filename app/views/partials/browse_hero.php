<?php
/**
 * Destaque do topo das páginas "Navegar por": cada uma tem o seu (escada, linha do tempo, nuvem, pódio...).
 * Usa o estado Alpine de views/browse.php (cfg, groups, withGames, total, fmt, href...).
 */
?>
<section x-show="ready && withGames.length" x-cloak class="relative isolate mt-6 overflow-hidden rounded-3xl border border-slate-50/10 bg-hero p-5 text-white sm:p-7" :aria-label="'Destaque de ' + cfg.title">
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <img x-show="heroCover" :src="heroCover" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
        <div class="absolute inset-0 bg-gradient-to-br from-black/75 via-black/65 to-black/40"></div>
    </div>

    <!-- ===== Sistemas: o sistema de cabeceira e a divisão por família ===== -->
    <template x-if="cfg.dim === 'platform'">
        <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr] lg:items-center">
            <div>
                <p class="kicker-hero">Seu sistema de cabeceira</p>
                <p class="mt-2 inline-block rounded-xl px-4 py-2 text-3xl font-black tracking-wide sm:text-4xl" :style="'background:var(--band-bg);color:var(--band-fg);' + UI.bandStyle(top.value)" x-text="top.value"></p>
                <p class="mt-3 text-white/80" x-text="fmt(top.games) + ' jogos · ' + fmt(Math.round(top.hours)) + ' h · de ' + top.years[0] + (top.years[1] !== top.years[0] ? ' a ' + top.years[1] : '')"></p>
                <p class="mt-1 text-sm text-white/55" x-text="pct(top.games) + '% de tudo o que você zerou.'"></p>
            </div>
            <div>
                <p class="kicker-hero">Por família</p>
                <div class="mt-3 flex h-4 overflow-hidden rounded-full bg-white/10" role="img" :aria-label="families.map((f) => f.label + ' ' + f.games).join(', ')">
                    <template x-for="f in families" :key="f.key"><span class="h-full" :style="'width:' + (f.games / total * 100) + '%;background:' + f.color" :title="f.label + ': ' + f.games"></span></template>
                </div>
                <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm sm:grid-cols-3">
                    <template x-for="f in families" :key="f.key">
                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="'background:' + f.color"></span><span class="truncate text-white/85" x-text="f.label"></span><span class="ml-auto tabular-nums text-white/55" x-text="f.games"></span></li>
                    </template>
                </ul>
            </div>
        </div>
    </template>

    <!-- ===== Gêneros: nuvem (tamanho = jogos, cor = nota média) ===== -->
    <template x-if="cfg.dim === 'genre'">
        <div>
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <p class="kicker-hero">Seu mapa de gostos</p>
                <p class="text-xs text-white/55">tamanho = quantos jogos · cor = sua nota média</p>
            </div>
            <ul class="mt-4 flex flex-wrap items-center justify-center gap-x-2 gap-y-2.5">
                <template x-for="g in cloud" :key="g.value">
                    <li><a :href="href(g)" class="inline-block rounded-full px-3 py-1 font-bold leading-tight shadow ring-1 ring-black/30 transition hover:scale-105 motion-reduce:hover:scale-100"
                           :class="UI.ratingClass(Math.round(g.avg_rating))" :style="UI.ratingStyle(Math.round(g.avg_rating)) + ';font-size:' + cloudSize(g) + 'rem'"
                           :title="g.value + ': ' + g.games + ' jogos, nota média ' + g.avg_rating" x-text="g.value"></a></li>
                </template>
            </ul>
            <div class="mt-5 grid gap-3 text-sm sm:grid-cols-3">
                <p class="rounded-xl bg-white/[.06] p-3"><span class="block text-xs text-white/55">O que você mais zera</span><strong x-text="top.value"></strong> <span class="text-white/60" x-text="'· ' + top.games + ' jogos'"></span></p>
                <p class="rounded-xl bg-white/[.06] p-3" x-show="bestRated"><span class="block text-xs text-white/55">O que você mais ama <span class="text-white/40">(3+ jogos)</span></span><strong x-text="bestRated?.value"></strong> <span class="text-white/60" x-text="bestRated ? '· nota ' + String(bestRated.avg_rating).replace('.', ',') : ''"></span></p>
                <p class="rounded-xl bg-white/[.06] p-3"><span class="block text-xs text-white/55">Variedade</span><strong x-text="withGames.length + ' gêneros'"></strong> <span class="text-white/60" x-text="'· ' + withGames.filter((g) => g.games === 1).length + ' com um jogo só'"></span></p>
            </div>
        </div>
    </template>

    <!-- ===== Desenvolvedoras e publishers: pódio ===== -->
    <template x-if="cfg.dim === 'developer' || cfg.dim === 'publisher'">
        <div>
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <p class="kicker-hero" x-text="cfg.dim === 'developer' ? '🏢 Os estúdios da sua vida' : '📦 Quem trouxe seus jogos até você'"></p>
                <p class="text-xs text-white/55" x-text="withGames.length + (cfg.dim === 'developer' ? ' estúdios' : ' publishers') + ' · ' + withGames.filter((g) => g.games === 1).length + ' com um jogo só'"></p>
            </div>
            <ol class="mt-5 grid items-end gap-3 sm:grid-cols-3">
                <template x-for="p in podium" :key="p.g.value">
                    <li class="flex flex-col items-center text-center" :class="p.order">
                        <a :href="href(p.g)" class="group flex w-full flex-col items-center">
                            <span class="flex -space-x-5">
                                <template x-for="(c, i) in p.g.covers.slice(0, 3)" :key="i">
                                    <span class="relative block aspect-[16/25] w-14 overflow-hidden rounded-md shadow-xl ring-2 ring-black/60 transition group-hover:-translate-y-1 motion-reduce:group-hover:translate-y-0" :style="'transform:rotate(' + ((i - 1) * 6) + 'deg)'"><img :src="c" alt="" loading="lazy" class="h-full w-full object-cover"></span>
                                </template>
                            </span>
                            <span class="mt-3 text-2xl" aria-hidden="true" x-text="p.medal"></span>
                            <span class="mt-1 line-clamp-2 font-bold leading-tight group-hover:underline" x-text="p.g.value"></span>
                            <span class="mt-0.5 text-xs text-white/60" x-text="p.g.games + ' jogos · ' + fmt(Math.round(p.g.hours)) + ' h · nota ' + String(p.g.avg_rating).replace('.', ',')"></span>
                        </a>
                        <span class="mt-3 block w-full rounded-t-xl" :class="p.bar" :style="'height:' + p.h + 'rem'"></span>
                    </li>
                </template>
            </ol>
        </div>
    </template>

    <!-- ===== Lançamento: linha do tempo por ano ===== -->
    <template x-if="cfg.dim === 'release_year'">
        <div>
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <p class="kicker-hero">Sua linha do tempo de lançamentos</p>
                <p class="text-xs text-white/55" x-text="'de ' + timeline.from + ' a ' + timeline.to"></p>
            </div>
            <div class="mt-4 flex h-36 items-end gap-px" role="img" :aria-label="'Jogos por ano de lançamento, de ' + timeline.from + ' a ' + timeline.to">
                <template x-for="y in timeline.years" :key="y.year">
                    <a :href="y.games ? href({ value: String(y.year) }) : null" class="group relative flex h-full flex-1 items-end" :title="y.year + ': ' + y.games + (y.games === 1 ? ' jogo' : ' jogos')">
                        <span class="block w-full rounded-t-sm transition group-hover:brightness-125" :style="'height:' + (y.games ? Math.max(4, y.games / timeline.max * 100) : 1) + '%;background:' + (y.games ? decadeColor(y.year) : 'rgb(255 255 255 / .08)')"></span>
                    </a>
                </template>
            </div>
            <div class="mt-1 flex justify-between text-2xs tabular-nums text-white/45"><span x-text="timeline.from"></span><span x-text="Math.round((timeline.from + timeline.to) / 2)"></span><span x-text="timeline.to"></span></div>
            <div class="mt-5 grid gap-3 text-sm sm:grid-cols-3">
                <p class="rounded-xl bg-white/[.06] p-3"><span class="block text-xs text-white/55">Sua década</span><strong x-text="decades[0]?.label"></strong> <span class="text-white/60" x-text="decades[0] ? '· ' + decades[0].games + ' jogos' : ''"></span></p>
                <p class="rounded-xl bg-white/[.06] p-3"><span class="block text-xs text-white/55">Ano com mais jogos</span><strong x-text="top.value"></strong> <span class="text-white/60" x-text="'· ' + top.games + ' jogos'"></span></p>
                <p class="rounded-xl bg-white/[.06] p-3"><span class="block text-xs text-white/55">O mais antigo que você zerou</span><strong x-text="timeline.from"></strong> <span class="text-white/60" x-text="'· ' + (new Date().getFullYear() - timeline.from) + ' anos atrás'"></span></p>
            </div>
        </div>
    </template>

    <!-- ===== Notas: distribuição de 1 a 11 ===== -->
    <template x-if="cfg.dim === 'rating'">
        <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
            <div class="flex flex-col justify-between gap-4">
                <p class="kicker-hero">Como você dá notas</p>
                <div class="flex h-40 items-end gap-1.5 sm:gap-2.5" role="img" :aria-label="'Distribuição das notas: ' + ratingBars.map((r) => 'nota ' + r.n + ' ' + r.games).join(', ')">
                    <template x-for="r in ratingBars" :key="r.n">
                        <a :href="r.games ? href({ value: String(r.n) }) : null" class="group flex h-full flex-1 flex-col items-center justify-end" :title="'Nota ' + r.n + ': ' + r.games + ' jogos'">
                            <span class="mb-1 text-2xs font-bold tabular-nums text-white/70" x-text="r.games || ''"></span>
                            <span class="block w-full rounded-t-md shadow ring-1 ring-black/30 transition group-hover:brightness-110" :class="UI.ratingClass(r.n)" :style="UI.ratingStyle(r.n) + ';height:' + (r.games ? Math.max(5, r.games / ratingMax * 100) : 2) + '%'"></span>
                            <span class="mt-1.5 text-xs font-black" x-text="r.n"></span>
                        </a>
                    </template>
                </div>
            </div>
            <dl class="grid grid-cols-3 gap-3 text-center lg:grid-cols-1 lg:text-left">
                <div class="rounded-xl bg-white/[.06] p-3"><dt class="text-xs text-white/55">Média</dt><dd class="text-2xl font-black" x-text="ratingStats.avg"></dd></div>
                <div class="rounded-xl bg-white/[.06] p-3"><dt class="text-xs text-white/55">Mediana</dt><dd class="text-2xl font-black" x-text="ratingStats.median"></dd></div>
                <div class="rounded-xl bg-white/[.06] p-3"><dt class="text-xs text-white/55">A que você mais dá</dt><dd class="text-2xl font-black" x-text="ratingStats.mode"></dd></div>
            </dl>
        </div>
    </template>

    <!-- ===== Dificuldades: a escada de C a AAA ===== -->
    <template x-if="cfg.dim === 'difficulty'">
        <div>
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <p class="kicker-hero">Sua escada de desafio</p>
                <p class="text-sm text-white/80"><strong class="text-white" x-text="hardPct + '%'"></strong> dos seus jogos foram A ou mais difíceis</p>
            </div>
            <ol class="mt-5 grid grid-cols-5 items-end gap-2 sm:gap-3">
                <template x-for="(g, i) in groups" :key="g.value">
                    <li>
                        <a :href="g.games ? href(g) : null" class="group flex flex-col items-center">
                            <span class="text-center text-xs text-white/60" x-text="g.games ? g.games + (g.games === 1 ? ' jogo' : ' jogos') : '—'"></span>
                            <span class="mt-1.5 flex w-full flex-col items-center justify-start rounded-t-xl pt-3 shadow-lg ring-1 ring-black/40 transition group-hover:brightness-110" :style="UI.diffStyle(g.value) + ';height:' + (3.5 + i * 1.6) + 'rem;opacity:' + (g.games ? 1 : .35)">
                                <span class="text-lg font-black sm:text-2xl" x-text="g.value"></span>
                            </span>
                            <span class="mt-1.5 hidden text-center text-2xs leading-tight text-white/55 sm:block" x-text="g.games ? 'média ' + fmtH(g.hours / g.games) + ' · nota ' + String(g.avg_rating).replace('.', ',') : ''"></span>
                        </a>
                    </li>
                </template>
            </ol>
        </div>
    </template>
</section>
