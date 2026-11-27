<?php
/**
 * Listas próprias de algumas páginas "Navegar por" (as demais usam os cartões de views/browse.php):
 *  - desenvolvedoras e publishers: ranking em linhas, com as parceiras de cada uma;
 *  - lançamento: anos agrupados por década.
 */
?>
<!-- ===== Ranking: desenvolvedoras / publishers ===== -->
<template x-if="mode === 'board'">
    <ol class="mt-6 divide-y divide-slate-50/10 overflow-hidden panel">
        <template x-for="(g, i) in shown" :key="g.value">
            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:flex-nowrap">
                <span class="w-8 shrink-0 text-right text-lg font-black tabular-nums" :class="i < 3 && sortKey === 'games:desc' && !q ? 'text-gold-500' : 'text-slate-500'" x-text="i + 1"></span>
                <a :href="href(g)" class="flex shrink-0 -space-x-3" :aria-label="'Ver os jogos de ' + g.value">
                    <template x-for="(c, k) in g.covers.slice(0, 3)" :key="k">
                        <span class="relative block aspect-[16/25] w-9 overflow-hidden rounded ring-2 ring-slate-900"><img :src="c" alt="" loading="lazy" class="h-full w-full object-cover"></span>
                    </template>
                    <span x-show="!g.covers.length" class="grid aspect-[16/25] w-9 place-items-center rounded bg-slate-800 text-xs font-bold text-slate-400" x-text="UI.initials(g.value)"></span>
                </a>
                <div class="min-w-0 flex-1 basis-40">
                    <a :href="href(g)" class="block truncate font-bold text-slate-50 hover:text-brand-400 hover:underline" :title="g.value" x-text="g.value"></a>
                    <p x-show="g.partners?.length" class="truncate text-xs text-slate-400">
                        <span x-text="cfg.dim === 'developer' ? 'Publicado por ' : 'Estúdios: '"></span>
                        <template x-for="(p, k) in g.partners" :key="p.name"><span><span x-text="k ? ', ' : ''"></span><a :href="partnerHref(p)" class="hover:text-brand-400" x-text="p.name"></a></span></template>
                    </p>
                    <p x-show="g.best" class="truncate text-xs"><span class="font-bold text-gold-500">🏆</span> <span class="text-slate-300" x-text="g.best?.title"></span></p>
                </div>
                <dl class="flex shrink-0 items-center gap-4 text-right text-sm">
                    <div><dt class="sr-only">Jogos</dt><dd><strong class="text-slate-50" x-text="g.games"></strong> <span class="text-xs text-slate-500" x-text="g.games === 1 ? 'jogo' : 'jogos'"></span></dd></div>
                    <div class="hidden sm:block"><dt class="sr-only">Horas</dt><dd class="tabular-nums text-slate-300" x-text="fmt(Math.round(g.hours)) + ' h'"></dd></div>
                    <div><dt class="sr-only">Nota média</dt><dd><span class="rounded-md px-1.5 py-0.5 text-xs font-black shadow ring-1 ring-black/30" :class="UI.ratingClass(Math.round(g.avg_rating))" :style="UI.ratingStyle(Math.round(g.avg_rating))" x-text="g.avg_rating.toLocaleString('pt-BR', { minimumFractionDigits: 1 })"></span></dd></div>
                </dl>
                <button type="button" x-show="hasBest && !isDemo && g.games >= bestMin" @click="openBest(g)" class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold text-gold-500 hover:bg-gold-400/10" :title="g.best ? 'Trocar o best of' : 'Escolher o best of'" x-text="g.best ? 'Trocar' : '🏆'"></button>
            </li>
        </template>
    </ol>
</template>

<!-- ===== Lançamento: anos por década ===== -->
<template x-if="mode === 'decades'">
    <div class="mt-6 space-y-8">
        <template x-for="d in decadesShown" :key="d.key">
            <section :aria-label="d.label">
                <header class="mb-3 flex items-baseline gap-3 border-b border-slate-50/10 pb-2">
                    <span class="h-3 w-3 rounded-full" :style="'background:' + decadeColor(d.start)"></span>
                    <h2 class="t-section" x-text="d.label"></h2>
                    <p class="text-sm text-slate-400" x-text="d.games + (d.games === 1 ? ' jogo' : ' jogos') + ' · ' + fmt(Math.round(d.hours)) + ' h'"></p>
                </header>
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <template x-for="g in d.groups" :key="g.value">
                        <li>
                            <a :href="href(g)" class="group relative block h-32 overflow-hidden rounded-xl border border-slate-50/10 bg-black transition hover:-translate-y-0.5 hover:border-brand-400/60 motion-reduce:hover:translate-y-0">
                                <span class="absolute inset-0 grid grid-cols-3 opacity-60 transition group-hover:opacity-80" aria-hidden="true">
                                    <template x-for="k in 3" :key="k"><span class="relative overflow-hidden"><img x-show="g.covers[k - 1]" :src="g.covers[k - 1]" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover"></span></template>
                                </span>
                                <span class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></span>
                                <span class="absolute bottom-2 left-3 text-white">
                                    <span class="block text-3xl font-black leading-none tracking-tight [text-shadow:0_2px_6px_rgb(0_0_0/.7)]" x-text="g.value"></span>
                                    <span class="text-xs text-white/75" x-text="g.games + (g.games === 1 ? ' jogo' : ' jogos') + ' · nota ' + String(g.avg_rating).replace('.', ',')"></span>
                                </span>
                                <span class="absolute right-2 top-2 h-1.5 w-8 rounded-full" :style="'background:' + decadeColor(Number(g.value))"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </section>
        </template>
    </div>
</template>
