<?php
/**
 * Visuais alternativos do catálogo (o "case" fica em catalog.php). Todos usam o mesmo estado Alpine:
 * grp (a divisão atual: ano, nota, letra...), layout, selecting, itemHref(g), itemClick(g, $event), isSel(g), toggle(g), flashId.
 * No modo de seleção, cada item vira um botão de marcar (role="button" + aria-pressed; a barra de espaço também marca).
 */
?>

<!-- ============ Estante 3D: capas grandes e inteiras; as informações ficam abaixo, nunca sobre a arte ============ -->
<template x-if="layout === 'shelf'">
    <ul class="shelf-grid" aria-label="Estante">
        <template x-for="g in grp.games" :key="g.id">
            <li class="shelf-cell" :data-gid="g.id">
                <a :href="itemHref(g)" @click="itemClick(g, $event)" @keydown.space="selecting && ($event.preventDefault(), toggle(g))" class="shelf-link block rounded-xl focus:outline-none" :aria-label="g.title + ', ' + g.platform"
                   :role="selecting ? 'button' : null" :aria-pressed="selecting ? String(isSel(g)) : null" :tabindex="isDemo || selecting ? 0 : null"
                   :class="flashId === g.id && 'ring-4 ring-gold-400 ring-offset-4 ring-offset-slate-950'">
                    <div class="shelf-stage">
                        <div class="shelf-box">
                            <div class="shelf-front" :class="selecting && isSel(g) && 'sel-ring'">
                                <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy">
                                <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center"><span class="text-5xl font-bold text-white/80" x-text="UI.initials(g.title)"></span></div>
                            </div>
                            <div class="shelf-side" :style="UI.spineStyle(g.platform)"><span x-text="g.platform"></span></div>
                        </div>
                        <span x-show="selecting" x-cloak class="sel-mark" style="bottom:1.2rem" :class="isSel(g) && 'on'" aria-hidden="true">&#10003;</span>
                    </div>
                    <div class="shelf-info">
                        <p class="shelf-title" x-text="g.title"></p>
                        <p class="shelf-sub"><span x-text="g.platform"></span> &middot; <span x-text="UI.fmtDate(g.finished_at)"></span></p>
                        <p class="mt-1.5 flex flex-wrap items-center justify-center gap-1.5">
                            <span class="grid h-6 min-w-6 place-items-center rounded px-1 text-xs font-black ring-1 ring-black/30" :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" :title="'Nota ' + g.rating" x-text="g.rating"></span>
                            <span class="grid h-6 min-w-6 place-items-center rounded px-1 text-xs font-black ring-1 ring-black/30" :style="UI.diffStyle(g.difficulty)" :title="'Dificuldade ' + g.difficulty" x-text="g.difficulty"></span>
                            <span class="text-xs tabular-nums text-slate-400" x-text="UI.fmtTime(g.playtime_minutes)"></span>
                        </p>
                    </div>
                </a>
                <button type="button" x-show="!selecting" @click="peek(g)" class="mx-auto mt-1 block rounded-full px-2.5 py-0.5 text-xs font-semibold text-slate-400 hover:bg-slate-50/10 hover:text-slate-50" :aria-label="'Espiar ' + g.title">👁 Espiar</button>
            </li>
        </template>
    </ul>
</template>

<!-- ============ Terminal ============ -->
<template x-if="layout === 'terminal'">
    <div class="term" role="table" aria-label="Jogos zerados">
        <div class="term-row term-head" role="row"><span role="columnheader">Nt</span><span role="columnheader">Título</span><span role="columnheader">Sistema</span><span role="columnheader">Data</span><span role="columnheader">Tempo</span><span role="columnheader">Dif</span></div>
        <template x-for="g in grp.games" :key="g.id">
            <!-- no terminal a linha continua sendo linha de tabela; na seleção, o estado vai em aria-selected -->
            <a :href="itemHref(g)" @click="itemClick(g, $event)" @keydown.space="selecting && ($event.preventDefault(), toggle(g))" class="term-row" role="row" :data-gid="g.id" :tabindex="isDemo || selecting ? 0 : null"
               :class="[selecting && isSel(g) && '!bg-brand-500/25', flashId === g.id && '!bg-gold-400/25']" :aria-selected="selecting ? String(isSel(g)) : null">
                <span role="cell" x-text="String(g.rating).padStart(2, '0')"></span>
                <span role="cell" class="term-title" x-text="(selecting ? (isSel(g) ? '[x] ' : '[ ] ') : '') + g.title"></span>
                <span role="cell" x-text="g.platform"></span>
                <span role="cell" x-text="g.finished_at"></span>
                <span role="cell" x-text="Math.floor(g.playtime_minutes / 60) + 'h' + String(g.playtime_minutes % 60).padStart(2, '0')"></span>
                <span role="cell" x-text="g.difficulty"></span>
            </a>
        </template>
        <div x-show="grp.key === shownGroups[shownGroups.length - 1]?.key" class="term-prompt" x-text="'$ ls jogos | wc -l  →  ' + games.length + ' de ' + meta.total"></div>
    </div>
</template>

<!-- ============ Mosaico: só as capas, lado a lado, como um pôster da coleção ============ -->
<template x-if="layout === 'mosaic'">
    <ul class="grid grid-cols-[repeat(auto-fill,minmax(4.25rem,1fr))] gap-1 sm:grid-cols-[repeat(auto-fill,minmax(5.5rem,1fr))]" aria-label="Mosaico de capas">
        <template x-for="g in grp.games" :key="g.id">
            <li :data-gid="g.id">
                <a :href="itemHref(g)" @click="itemClick(g, $event)" @keydown.space="selecting && ($event.preventDefault(), toggle(g))" :title="g.title + ' · ' + g.platform + ' · nota ' + g.rating"
                   :aria-label="g.title + ', ' + g.platform + ', nota ' + g.rating" :role="selecting ? 'button' : null" :aria-pressed="selecting ? String(isSel(g)) : null" :tabindex="isDemo || selecting ? 0 : null"
                   class="group relative block aspect-[3/4] overflow-hidden rounded-md bg-slate-900 ring-1 ring-black/40 transition hover:z-10 hover:scale-110 focus:z-10 focus:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:hover:scale-100"
                   :class="[selecting && isSel(g) && '!ring-4 !ring-brand-400', flashId === g.id && '!ring-4 !ring-gold-400 z-10 scale-110', g.honors?.length && 'ring-2 ring-gold-400/70']">
                    <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
                    <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-1 text-center text-2xs font-bold leading-tight text-white/85" x-text="g.title"></span>
                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent px-1 pb-1 pt-4 text-2xs font-bold leading-tight text-white opacity-0 transition group-hover:opacity-100 group-focus:opacity-100" x-text="g.title"></span>
                    <span x-show="selecting && isSel(g)" class="absolute right-1 top-1 grid h-5 w-5 place-items-center rounded-full bg-brand-600 text-2xs font-black text-on-brand" aria-hidden="true">✓</span>
                </a>
            </li>
        </template>
    </ul>
</template>
