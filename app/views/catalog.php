<?php
/** @var array|null $me */
/** @var array|null $demo  jogos fictícios da página de exemplo (nulo no catálogo real) */
$demo = $demo ?? null;
?>
<main class="page page-wide" x-data='catalog(<?= e(json_encode($me ?? null, JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <div class="demo-note" role="note">
        <p><strong class="text-slate-50">Página de exemplo</strong> com <?= count($demo) ?> jogos fictícios. A busca, os filtros e a ordenação funcionam de verdade; as capas são ilustrações de demonstração.</p>
        <a href="<?= e(url(($me ?? null) ? '/jogos' : '/login')) ?>" class="font-semibold text-brand-400 hover:underline"><?= ($me ?? null) ? 'Ir para o Catálogo →' : 'Entrar →' ?></a>
    </div>
    <?php endif; ?>

    <!-- ============ Destaque: o catálogo (ou o recorte filtrado) em números ============ -->
    <header class="hero-box p-5 text-white sm:p-7">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="heroCover" :src="heroCover" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/65 to-brand-700/30"></div>
        </div>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="kicker-hero" x-text="hasFilters ? 'Recorte do catálogo' : 'Todos os jogos que você zerou'"></p>
                <h1 class="mt-1 t-hero">Catálogo</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" x-show="games.length > 1" x-cloak @click="surprise()" class="rounded-xl border border-white/25 bg-black/30 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-white/10" title="Sorteia um jogo do recorte atual">🎲 Me surpreenda</button>
                <button type="button" x-show="games.length" x-cloak @click="exportCsv()" class="rounded-xl border border-white/25 bg-black/30 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-white/10" title="Baixa o recorte atual em CSV (abre no Excel)">⬇ CSV</button>
                <button type="button" x-show="!isDemo && games.length" x-cloak @click="selecting ? stopSelecting() : selecting = true" :aria-pressed="selecting"
                        class="rounded-xl border px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                        :class="selecting ? 'border-white bg-white/15 text-white' : 'border-white/25 bg-black/30 text-white hover:bg-white/10'" x-text="selecting ? 'Cancelar seleção' : 'Selecionar vários'"></button>
                <a href="<?= e(url('/jogos/novo')) ?>" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black transition hover:bg-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">+ Adicionar jogo</a>
            </div>
        </div>
        <dl x-show="ready" x-cloak class="mt-6 grid grid-cols-3 gap-4 sm:max-w-xl" aria-live="polite">
            <div><dt class="text-xs text-white/55" x-text="summary.games === 1 ? 'jogo' : 'jogos'"></dt><dd class="text-3xl font-black tabular-nums" x-text="fmt(summary.games)"></dd></div>
            <div><dt class="text-xs text-white/55">horas</dt><dd class="text-3xl font-black tabular-nums" x-text="fmt(Math.round(summary.minutes / 60))"></dd></div>
            <div><dt class="text-xs text-white/55">nota média</dt><dd class="text-3xl font-black tabular-nums" x-text="summary.avg_rating != null ? String(summary.avg_rating).replace('.', ',') : '—'"></dd></div>
        </dl>
    </header>

    <p x-show="notice" x-cloak role="status" class="mt-5 rounded-lg border border-ok/30 bg-ok/10 px-4 py-3 text-sm text-ok" x-text="notice"></p>
    <ul x-show="skipped.length" x-cloak class="mt-3 list-disc rounded-lg border border-gold-400/40 bg-gold-400/10 py-3 pl-8 pr-4 text-sm text-slate-200">
        <template x-for="s in skipped" :key="s.id"><li><strong x-text="s.title"></strong>: <span x-text="s.reason"></span></li></template>
    </ul>

    <!-- Barra: busca, filtros (painel), ordenação, densidade e visual -->
    <section class="mt-6 panel p-3 sm:p-4" aria-label="Busca e filtros">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <label class="relative min-w-[12rem] flex-1 basis-full sm:basis-auto">
                <span class="sr-only">Buscar por título</span>
                <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                <input type="search" x-ref="search" x-model="filters.q" @input.debounce.300ms="apply()" @keydown.escape="if (filters.q) { filters.q = ''; apply(); } else $el.blur()" placeholder="Buscar título, sistema, gênero, empresa…"
                       class="w-full rounded-lg border border-slate-50/10 bg-slate-950/60 py-2.5 pl-9 pr-10 text-slate-50 placeholder-slate-500 outline-none transition focus:ring-2 focus:ring-brand-500">
                <kbd class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded border border-slate-50/15 px-1.5 text-2xs font-semibold text-slate-500 sm:block" title="Aperte / para buscar">/</kbd>
            </label>
            <button type="button" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen" aria-controls="cat-filters"
                    class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    :class="filtersOpen || activeCount ? 'border-brand-400/60 bg-brand-500/15 text-slate-50' : 'border-slate-50/15 text-slate-200 hover:bg-slate-50/5'">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
                Filtros
                <span x-show="activeCount" x-cloak class="grid h-5 min-w-5 place-items-center rounded-full bg-brand-600 px-1.5 text-2xs font-bold text-on-brand" x-text="activeCount"></span>
            </button>
            <!-- visões salvas (ficam neste navegador) -->
            <div class="relative" @click.outside="viewsOpen = false" @keydown.escape="viewsOpen = false">
                <button type="button" @click="viewsOpen = !viewsOpen" :aria-expanded="viewsOpen" class="btn-ghost inline-flex items-center gap-1.5">
                    ⭐ <span class="hidden sm:inline">Visões</span><span x-show="savedViews.length" class="text-xs text-slate-500" x-text="savedViews.length"></span>
                </button>
                <div x-show="viewsOpen" x-cloak x-transition.opacity class="absolute left-0 top-12 z-fab w-72 rounded-xl border border-slate-50/15 bg-slate-900 p-3 shadow-2xl sm:left-auto sm:right-0">
                    <p class="kicker">Visões salvas</p>
                    <p x-show="!savedViews.length" class="mt-2 text-xs text-slate-400">Monte um recorte (filtros e ordenação) e salve com um nome para reabrir com um clique.</p>
                    <ul class="mt-2 space-y-1">
                        <template x-for="(v, i) in savedViews" :key="v.name + i">
                            <li class="flex items-center gap-1">
                                <button type="button" @click="openView(v)" class="min-w-0 flex-1 truncate rounded-md px-2 py-1.5 text-left text-sm text-slate-100 hover:bg-slate-50/10" :title="v.desc" x-text="v.name"></button>
                                <button type="button" @click="deleteView(i)" class="grid h-7 w-7 shrink-0 place-items-center rounded-md text-slate-500 hover:bg-danger/10 hover:text-danger" :aria-label="'Apagar a visão ' + v.name">✕</button>
                            </li>
                        </template>
                    </ul>
                    <form x-show="hasFilters || sortKey !== 'finished_at:desc'" @submit.prevent="saveView()" class="mt-3 flex gap-1.5 border-t border-slate-50/10 pt-3">
                        <label class="sr-only" for="view-name">Nome da visão</label>
                        <input id="view-name" type="text" maxlength="40" x-model="viewName" placeholder="Nome (ex.: RPGs nota 9+)" class="min-w-0 flex-1 rounded-lg border border-slate-50/10 bg-slate-950/60 px-2.5 py-1.5 text-sm text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                        <button type="submit" :disabled="!viewName.trim()" class="btn btn-sm">Salvar</button>
                    </form>
                    <p x-show="!(hasFilters || sortKey !== 'finished_at:desc')" class="mt-3 border-t border-slate-50/10 pt-3 text-xs text-slate-500">Aplique algum filtro ou ordenação para salvar uma visão.</p>
                </div>
            </div>
            <label class="flex min-w-[8.5rem] flex-1 items-center gap-2 text-sm text-slate-400 sm:flex-none"><span class="hidden md:inline">Ordenar</span>
                <select x-model="sortKey" @change="apply()" aria-label="Ordenar por" class="field w-full min-w-0 sm:w-auto sm:max-w-[15rem] text-sm">
                    <template x-for="s in sorts" :key="s.v"><option :value="s.v" x-text="s.l"></option></template>
                </select>
            </label>
            <div x-show="layout === 'case'" class="inline-flex rounded-lg bg-slate-50/5 p-1 text-xs font-semibold" role="group" aria-label="Densidade da grade">
                <button type="button" @click="setDensity('comfortable')" :aria-pressed="density === 'comfortable'" title="Confortável: capas grandes" class="rounded-md px-2.5 py-1.5 transition" :class="density === 'comfortable' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
                    <span class="sr-only">Confortável</span></button>
                <button type="button" @click="setDensity('compact')" :aria-pressed="density === 'compact'" title="Compacto: mais jogos por linha" class="rounded-md px-2.5 py-1.5 transition" :class="density === 'compact' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="2" y="2" width="5.5" height="5.5" rx="1"/><rect x="9.25" y="2" width="5.5" height="5.5" rx="1"/><rect x="16.5" y="2" width="5.5" height="5.5" rx="1"/><rect x="2" y="9.25" width="5.5" height="5.5" rx="1"/><rect x="9.25" y="9.25" width="5.5" height="5.5" rx="1"/><rect x="16.5" y="9.25" width="5.5" height="5.5" rx="1"/><rect x="2" y="16.5" width="5.5" height="5.5" rx="1"/><rect x="9.25" y="16.5" width="5.5" height="5.5" rx="1"/><rect x="16.5" y="16.5" width="5.5" height="5.5" rx="1"/></svg>
                    <span class="sr-only">Compacto</span></button>
            </div>
            <label class="hidden items-center gap-2 text-sm text-slate-400 sm:flex"><span class="hidden md:inline">Visual</span>
                <select :value="layout" @change="setLayout($event.target.value)" aria-label="Visual do catálogo" class="field text-sm">
                    <template x-for="l in layouts" :key="l.v"><option :value="l.v" :selected="layout === l.v" x-text="l.l"></option></template>
                </select>
            </label>
        </div>

        <!-- etiquetas dos filtros ativos (e atalhos quando não há filtro) -->
        <div class="mt-3 flex flex-wrap items-center gap-2" x-show="activeCount || quick.length" x-cloak>
            <template x-for="c in chips" :key="c.k">
                <button type="button" @click="removeFilter(c.k)" class="inline-flex items-center gap-1.5 rounded-full border border-brand-400/40 bg-brand-500/15 py-1 pl-3 pr-2 text-xs font-semibold text-slate-100 transition hover:border-danger/60 hover:bg-danger/10" :aria-label="'Remover filtro ' + c.l">
                    <span x-text="c.l"></span><span aria-hidden="true" class="text-slate-400">✕</span>
                </button>
            </template>
            <button type="button" x-show="activeCount > 1" @click="clear()" class="text-xs font-semibold text-brand-400 hover:underline">Limpar tudo</button>
            <template x-if="!activeCount">
                <span class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-500">Atalhos:</span>
                    <template x-for="a in quick" :key="a.k + a.v">
                        <button type="button" @click="setQuick(a)" class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                                :class="a.gold ? 'border-gold-400/40 text-gold-500 hover:bg-gold-400/10' : 'border-slate-50/15 text-slate-300 hover:bg-slate-50/5'" x-text="a.l"></button>
                    </template>
                </span>
            </template>
        </div>
    </section>

    <!-- Painel de filtros: abaixo da barra no computador; gaveta de baixo no celular -->
    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-fab bg-black/60 sm:hidden" @click="filtersOpen = false" aria-hidden="true"></div>
    <section id="cat-filters" x-show="filtersOpen" x-cloak x-transition.opacity @keydown.escape.window="filtersOpen = false"
             class="fixed inset-x-0 bottom-0 z-modal max-h-[85vh] overflow-y-auto rounded-t-2xl border border-slate-50/15 bg-slate-900 p-4 shadow-2xl sm:static sm:z-auto sm:mt-3 sm:max-h-none sm:rounded-2xl sm:border-slate-50/10 sm:bg-slate-900/70 sm:shadow-none"
             aria-label="Filtros">
        <div class="mb-3 flex items-center justify-between sm:hidden">
            <p class="text-base font-bold text-slate-50">Filtros</p>
            <button type="button" @click="filtersOpen = false" class="icon-btn" aria-label="Fechar filtros"><?= icon('close', 'h-5 w-5') ?></button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="f in filterSelects" :key="f.k">
                <label class="text-xs font-medium text-slate-400"><span x-text="f.l"></span>
                    <select x-model="filters[f.k]" @change="apply()" class="field mt-1 block w-full text-sm">
                        <option value="" x-text="f.all"></option>
                        <template x-for="o in f.options()" :key="o.v"><option :value="o.v" x-text="o.l"></option></template>
                    </select>
                </label>
            </template>
        </div>
        <!-- no celular, o visual fica aqui (na barra não cabe) -->
        <label class="mt-3 block text-xs font-medium text-slate-400 sm:hidden">Visual
            <select :value="layout" @change="setLayout($event.target.value)" class="mt-1 block w-full rounded-lg border border-slate-50/10 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-50">
                <template x-for="l in layouts" :key="l.v"><option :value="l.v" :selected="layout === l.v" x-text="l.l"></option></template>
            </select>
        </label>
        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-50/10 pt-4">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gold-400/40 px-3.5 py-1.5 text-sm font-semibold text-gold-500 transition hover:bg-gold-400/10" :class="filters.honors && 'bg-gold-400/15'">
                <input type="checkbox" class="h-4 w-4 accent-gold-500" :checked="!!filters.honors" @change="filters.honors = $event.target.checked ? '1' : ''; apply()">
                🏆 Só o hall da fama (GOTY e best of)
            </label>
            <button type="button" @click="clear()" x-show="activeCount" class="text-sm font-semibold text-brand-400 hover:underline">Limpar filtros</button>
            <button type="button" @click="filtersOpen = false" class="btn ml-auto w-full sm:w-auto"
                    x-text="ready ? 'Ver ' + meta.total + (meta.total === 1 ? ' jogo' : ' jogos') : 'Ver jogos'"></button>
        </div>
    </section>

    <!-- Extras do recorte filtrado (os números já estão no destaque): best of da categoria e completar dados -->
    <section x-show="ready && hasFilters && meta.total > 0 && (bestOf?.game || filters.missing)" x-cloak class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3 rounded-2xl border border-slate-50/10 bg-gradient-to-r from-brand-500/10 to-transparent px-5 py-3.5" aria-label="Destaques do recorte">
        <!-- best of da categoria filtrada -->
        <template x-if="bestOf?.game">
            <a :href="isDemo ? null : base + '/jogos/' + bestOf.game.id" class="flex min-w-0 items-center gap-2.5 rounded-xl border border-gold-400/35 bg-gold-400/10 py-1.5 pl-1.5 pr-3 transition hover:border-gold-400/70">
                <span class="best-frame h-10 w-7 shrink-0 !p-[2px]"><img x-show="bestOf.game.cover_url" :src="bestOf.game.cover_url" alt=""></span>
                <span class="min-w-0 text-sm"><span class="best-kicker !text-2xs">🏆 Best of <span x-text="bestOf.value"></span></span><span class="block truncate font-bold text-slate-50" x-text="bestOf.game.title"></span></span>
            </a>
        </template>
        <!-- dados faltando: corrigir todos de uma vez -->
        <button type="button" x-show="!isDemo && filters.missing && filters.missing !== 'cover'" @click="fixMissing()" class="btn ml-auto">Preencher todos de uma vez</button>
        <p x-show="filters.missing === 'cover'" class="ml-auto text-xs text-slate-400">Capas são enviadas jogo a jogo: abra cada um para adicionar.</p>
    </section>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="fetchAll()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>

    <!-- Carregando -->
    <div x-show="!ready && !error" class="mt-6 grid animate-pulse grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4" aria-label="Carregando">
        <template x-for="i in 8"><div class="aspect-[16/25] rounded-lg bg-slate-50/5"></div></template>
    </div>

    <!-- Vazio: catálogo sem jogos -->
    <section x-show="ready && meta.total === 0 && !hasFilters" x-cloak class="mt-6 empty">
        <p class="t-section">Seu catálogo está vazio</p>
        <p class="mt-1 text-sm text-slate-400">Registre o primeiro jogo que você zerou.<span class="xp-ui"> E comece a ganhar XP.</span></p>
        <a href="<?= e(url('/jogos/novo')) ?>" class="btn mt-4 inline-block">Adicionar o primeiro jogo</a>
    </section>

    <!-- Vazio: filtros sem resultado -->
    <section x-show="ready && meta.total === 0 && hasFilters" x-cloak class="mt-6 empty">
        <p class="t-section">Nenhum jogo encontrado</p>
        <p class="mt-1 text-sm text-slate-400">Tente outros filtros ou outro termo de busca.</p>
        <button type="button" @click="clear()" class="btn-ghost mt-4">Limpar filtros</button>
    </section>

    <!-- Índice: pular para uma divisão (ano, nota, letra...) -->
    <nav x-show="games.length && groups.length > 1" x-cloak class="sticky top-0 z-20 -mx-4 mt-5 bg-slate-950/85 px-4 py-2 backdrop-blur sm:-mx-6 sm:px-6" aria-label="Ir para">
        <div class="flex items-center gap-2 overflow-x-auto pb-0.5">
            <span class="kicker shrink-0">Ir para</span>
            <template x-for="grp in groups" :key="grp.key">
                <button type="button" @click="jumpTo(grp)" class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold tabular-nums transition"
                        :class="grp.key === currentGroup ? 'bg-brand-600 text-on-brand' : 'bg-slate-50/5 text-slate-300 hover:bg-slate-50/10'" x-text="grp.short"></button>
            </template>
        </div>
    </nav>

    <!-- Grade por divisões (o visual vem da preferência do usuário) -->
    <div x-show="games.length" x-cloak :class="loading && 'opacity-60 transition-opacity'" class="mt-4">
        <template x-for="grp in shownGroups" :key="grp.key">
            <section class="mb-8 scroll-mt-16" :id="'grp-' + grp.key" :data-group="grp.key" :aria-label="grp.label">
                <header x-show="grouped" class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-slate-50/10 pb-2">
                    <h2 class="t-section" x-text="grp.label"></h2>
                    <p class="text-sm text-slate-400" x-text="grp.count + (grp.count === 1 ? ' jogo' : ' jogos') + ' · ' + fmt(Math.round(grp.minutes / 60)) + ' h' + (grp.count > 1 ? ' · nota média ' + (grp.ratingSum / grp.count).toFixed(1).replace('.', ',') : '')"></p>
                    <!-- o melhor da divisão -->
                    <a x-show="grp.best && grp.count > 1" :href="grp.best ? itemHref(grp.best) : null" @click="grp.best && isDemo && $event.preventDefault()" class="ml-auto flex min-w-0 items-center gap-2 rounded-full border border-slate-50/10 bg-slate-900/70 py-0.5 pl-0.5 pr-3 text-xs text-slate-300 hover:border-gold-400/50" :title="grp.best ? 'O de maior nota em ' + grp.label : ''">
                        <span class="relative h-7 w-5 shrink-0 overflow-hidden rounded-sm bg-slate-800"><img x-show="grp.best?.cover_url" :src="grp.best?.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover"></span>
                        <span class="text-gold-500">★</span><span class="max-w-[12rem] truncate font-semibold" x-text="grp.best?.title"></span>
                        <span class="rounded px-1 text-2xs font-black" :class="UI.ratingClass(grp.best?.rating)" :style="grp.best ? UI.ratingStyle(grp.best.rating) : ''" x-text="grp.best?.rating"></span>
                    </a>
                </header>
                <template x-if="layout === 'case'">
                    <ul class="grid" :class="density === 'compact' ? 'grid-cols-3 gap-x-3 gap-y-4 sm:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6' : 'grid-cols-2 gap-x-5 gap-y-7 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5'">
                        <template x-for="g in grp.games" :key="g.id">
                            <li class="group/item relative" :id="'g-' + g.id">
                                <a :href="itemHref(g)" @click="itemClick(g, $event)" @keydown.space="selecting && ($event.preventDefault(), toggle(g))"
                                   :role="selecting ? 'button' : null" :aria-pressed="selecting ? String(isSel(g)) : null" :tabindex="isDemo || selecting ? 0 : null"
                                   :class="[isDemo ? 'cursor-default' : '', selecting && isSel(g) ? 'ring-2 ring-brand-400 ring-offset-2 ring-offset-slate-950' : '', flashId === g.id ? 'ring-4 ring-gold-400 ring-offset-4 ring-offset-slate-950' : '']" class="group relative block rounded-xl transition-shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">
                                    <span x-show="selecting" x-cloak class="absolute bottom-16 left-1/2 z-10 grid h-9 w-9 -translate-x-1/2 place-items-center rounded-full text-lg font-black shadow-lg ring-2 ring-white/70"
                                          :class="isSel(g) ? 'bg-brand-600 text-on-brand' : 'bg-black/60 text-transparent'" aria-hidden="true">✓</span>
                                    <!-- caixa de jogo: faixa com o sistema no topo, capa e brilho de plástico -->
                                    <div class="case" :class="g.honors?.length && 'case-honor'">
                                        <!-- faixa: sistema à esquerda, data de finalização à direita -->
                                        <div class="case-band" :style="UI.bandStyle(g.platform)"><span x-text="g.platform"></span><span x-show="density !== 'compact'" class="case-date" :title="'Finalizado em ' + UI.fmtDate(g.finished_at)" x-text="UI.fmtDate(g.finished_at)"></span></div>
                                        <div class="case-art">
                                            <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
                                            <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center">
                                                <span class="font-bold text-white/80" :class="density === 'compact' ? 'text-3xl' : 'text-5xl'" x-text="UI.initials(g.title)"></span>
                                            </div>

                                            <!-- nota (cor própria por valor), dificuldade e selo do hall da fama -->
                                            <span class="absolute left-2 top-2 grid place-items-center rounded-lg font-black leading-none shadow-lg ring-1 ring-black/40"
                                                  :class="[UI.ratingClass(g.rating), density === 'compact' ? 'h-6 min-w-6 px-1 text-xs' : 'h-8 min-w-8 px-1.5 text-sm']" :style="UI.ratingStyle(g.rating)" :title="'Nota ' + g.rating + ' de 11'" x-text="g.rating"></span>
                                            <span class="absolute right-2 top-2 grid place-items-center rounded-lg font-black leading-none tracking-wide shadow-lg ring-1 ring-black/40"
                                                  :class="density === 'compact' ? 'h-6 min-w-6 px-1 text-2xs' : 'h-8 min-w-8 px-1.5 text-sm'" :style="UI.diffStyle(g.difficulty)" :title="'Dificuldade ' + g.difficulty" x-text="g.difficulty"></span>
                                            <span x-show="g.honors?.length" x-cloak class="best-badge absolute left-1/2 top-2 -translate-x-1/2 !px-1.5 !py-0.5 !text-2xs" :title="g.honors?.join(' · ')" :aria-label="'Hall da fama: ' + (g.honors?.join(', ') ?? '')">🏆<span x-show="density !== 'compact' && g.honors?.length > 1" x-text="g.honors?.length"></span></span>

                                            <!-- etiqueta inferior: gênero (some no celular e no modo compacto) e tempo -->
                                            <div class="absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 backdrop-blur-md" :class="density === 'compact' ? 'px-1.5 py-1.5' : 'px-3 pb-2.5 pt-2'">
                                                <p x-show="!g.cover_url" class="mb-1.5 truncate text-center text-sm font-bold leading-tight text-white [text-shadow:0_1px_2px_rgb(0_0_0/.7)]" :title="g.title" x-text="g.title"></p>
                                                <div class="flex items-center justify-center gap-2" :class="density !== 'compact' && 'sm:justify-between'">
                                                    <span x-show="density !== 'compact'" class="hidden min-w-0 items-center gap-1.5 rounded-md bg-white/20 px-2 py-1 text-xs font-bold text-white ring-1 ring-white/15 sm:inline-flex" title="Gênero">
                                                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 01-2.8 0L3 13V3h10l7.6 7.6a2 2 0 010 2.8zM7.5 7.5h.01"/></svg>
                                                        <span class="truncate" x-text="g.genre || 'Sem gênero'"></span>
                                                    </span>
                                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-white/20 font-bold tabular-nums text-white ring-1 ring-white/15" :class="density === 'compact' ? 'px-1.5 py-0.5 text-2xs' : 'px-2 py-1 text-xs'" title="Tempo de jogo">
                                                        <svg x-show="density !== 'compact'" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                                        <span x-text="UI.fmtTime(g.playtime_minutes)"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <!-- espiar os detalhes sem sair da página -->
                                <button type="button" x-show="!selecting" @click="peek(g)" class="absolute bottom-14 right-2 z-10 grid h-8 w-8 place-items-center rounded-full bg-black/70 text-sm text-white shadow-lg ring-1 ring-white/20 transition hover:bg-black focus:opacity-100 sm:opacity-0 sm:group-hover/item:opacity-100" :class="density === 'compact' && '!bottom-9 !h-7 !w-7 !text-xs'" :aria-label="'Espiar ' + g.title" title="Espiar">👁</button>
                            </li>
                        </template>
                    </ul>
                </template>
                <?php require __DIR__ . '/partials/catalog_layouts.php'; ?>
            </section>
        </template>
        <!-- quando aparece na tela, desenha o próximo lote de jogos -->
        <div x-ref="more" class="h-px" aria-hidden="true"></div>
    </div>

    <!-- Total -->
    <div x-show="games.length" x-cloak class="mt-4 text-center">
        <p class="text-xs text-slate-500" aria-live="polite" x-text="loadingMore ? 'Carregando… ' + games.length + ' de ' + meta.total : (shownCount < games.length ? shownCount + ' de ' + games.length + ' jogos (role para ver mais)' : games.length + (games.length === 1 ? ' jogo' : ' jogos'))"></p>
        <button type="button" x-show="shownCount < games.length" @click="showMore()" class="btn-ghost mt-2">Mostrar mais</button>
    </div>

    <!-- Barra de edição em massa -->
    <div x-show="selecting" x-cloak class="fixed inset-x-3 bottom-3 z-pop mr-20 rounded-2xl border border-brand-400/50 bg-slate-900/95 p-4 shadow-2xl backdrop-blur sm:inset-x-6" role="region" aria-label="Edição em massa">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
            <p class="font-semibold text-slate-50"><span x-text="sel.length"></span> <span x-text="sel.length === 1 ? 'selecionado' : 'selecionados'"></span></p>
            <button type="button" @click="selectAll()" class="font-semibold text-brand-400 hover:underline">Marcar <span x-text="games.length === meta.total ? 'todos os' : 'os'"></span> <span x-text="games.length"></span></button>
            <button type="button" x-show="sel.length" @click="sel = []" class="text-slate-300 hover:underline">Limpar</button>
        </div>
        <div x-show="sel.length" class="mt-3 flex flex-wrap items-end gap-3">
            <label class="text-xs text-slate-400">Alterar
                <select x-model="bulk.field" @change="bulk.value = ''" class="mt-1 block rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-sm text-slate-50">
                    <template x-for="f in bulkFields" :key="f.v"><option :value="f.v" x-text="f.l"></option></template>
                </select></label>
            <label class="min-w-[10rem] flex-1 text-xs text-slate-400">Novo valor
                <template x-if="bulk.field === 'difficulty'"><select x-model="bulk.value" class="mt-1 block w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-sm text-slate-50"><option value="">Escolha…</option><template x-for="d in ['C','B','A','AA','AAA']" :key="d"><option :value="d" x-text="d"></option></template></select></template>
                <template x-if="bulk.field === 'rating'"><select x-model="bulk.value" class="mt-1 block w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-sm text-slate-50"><option value="">Escolha…</option><template x-for="n in 11" :key="n"><option :value="String(n)" x-text="n"></option></template></select></template>
                <template x-if="bulk.field === 'release_year'"><input type="number" min="1950" :max="new Date().getFullYear()" x-model="bulk.value" class="mt-1 block w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-sm text-slate-50"></template>
                <template x-if="['platform','genre','developer','publisher'].includes(bulk.field)"><input type="text" x-model="bulk.value" list="bulk-list" maxlength="100" :placeholder="optional ? 'Vazio remove' : ''" class="mt-1 block w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-sm text-slate-50"></template>
            </label>
            <datalist id="bulk-list"><template x-for="o in bulkOptions" :key="o"><option :value="o"></option></template></datalist>
            <button type="button" @click="applyBulk()" :disabled="busy || (!optional && !String(bulk.value).trim())" class="btn">Aplicar</button>
            <button type="button" @click="confirmDelete = true" class="rounded-lg border border-danger/50 px-4 py-2 text-sm font-semibold text-danger hover:bg-danger/10">Excluir…</button>
        </div>
    </div>

    <!-- Confirmar exclusão -->
    <div x-show="confirmDelete" x-cloak class="modal-back" role="alertdialog" aria-modal="true" aria-labelledby="bulk-del" @keydown.escape.window="confirmDelete = false">
        <div class="w-full max-w-md rounded-2xl border border-slate-50/15 bg-slate-900 p-6 shadow-2xl">
            <h2 id="bulk-del" class="t-section">Excluir <span x-text="sel.length"></span> <span x-text="sel.length === 1 ? 'jogo' : 'jogos'"></span>?</h2>
            <p class="mt-2 text-sm text-slate-300">Os jogos, as capas e o XP que eles deram serão apagados. Isso não pode ser desfeito.</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="confirmDelete = false" class="btn-ghost">Cancelar</button>
                <button type="button" @click="deleteBulk()" :disabled="busy" class="rounded-lg bg-danger-solid px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50" x-text="busy ? 'Excluindo…' : 'Excluir'"></button>
            </div>
        </div>
    </div>

    <!-- Espiar: os detalhes do jogo num painel lateral -->
    <div x-show="peekGame" x-cloak class="fixed inset-0 z-modal bg-black/60" @click="closePeek()" aria-hidden="true"></div>
    <aside x-show="peekGame" x-cloak x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-ref="peekBox" @keydown.escape.window="peekGame && closePeek()" @keydown.tab="trapFocus($event, $refs.peekBox)"
           class="fixed inset-y-0 right-0 z-modal flex w-full max-w-md flex-col overflow-y-auto border-l border-slate-50/15 bg-slate-900 shadow-2xl motion-reduce:transition-none" role="dialog" aria-modal="true" aria-labelledby="peek-title">
        <template x-if="peekGame">
            <div class="flex flex-1 flex-col">
                <div class="relative isolate overflow-hidden bg-hero p-5 text-white">
                    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                        <img x-show="peekGame.cover_url" :src="peekGame.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-30 blur-3xl">
                        <div class="absolute inset-0 bg-gradient-to-b from-black/50 to-black/85"></div>
                    </div>
                    <button type="button" x-ref="peekClose" @click="closePeek()" class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-black/40 text-white hover:bg-black/70" aria-label="Fechar">✕</button>
                    <div class="flex gap-4">
                        <span class="relative block aspect-[16/25] w-24 shrink-0 overflow-hidden rounded-lg bg-black shadow-2xl ring-1 ring-white/15">
                            <img x-show="peekGame.cover_url" :src="peekGame.cover_url" :alt="'Capa de ' + peekGame.title" class="absolute inset-0 h-full w-full object-cover">
                            <span x-show="!peekGame.cover_url" class="absolute inset-0 grid place-items-center text-2xl font-bold text-white/70" x-text="UI.initials(peekGame.title)"></span>
                        </span>
                        <div class="min-w-0 pr-8">
                            <h2 id="peek-title" class="text-xl font-black leading-tight" x-text="peekGame.title"></h2>
                            <p class="mt-1 text-sm text-white/70" x-text="[peekGame.platform, peekGame.genre, peekGame.release_year].filter(Boolean).join(' · ')"></p>
                            <p class="mt-2 flex flex-wrap gap-1.5">
                                <span class="rounded-md px-2 py-0.5 text-sm font-black" :class="UI.ratingClass(peekGame.rating)" :style="UI.ratingStyle(peekGame.rating)" x-text="'nota ' + peekGame.rating"></span>
                                <span class="rounded-md px-2 py-0.5 text-sm font-black" :style="UI.diffStyle(peekGame.difficulty)" x-text="peekGame.difficulty"></span>
                            </p>
                            <ul x-show="peekGame.honors?.length" class="mt-2 flex flex-wrap gap-1"><template x-for="h in peekGame.honors ?? []" :key="h"><li class="best-badge !px-2 !py-0.5 !text-2xs" x-text="'🏆 ' + h"></li></template></ul>
                        </div>
                    </div>
                </div>
                <div class="flex-1 space-y-5 p-5 text-sm">
                    <p x-show="peekLoading" class="animate-pulse text-slate-400">Carregando os detalhes…</p>
                    <dl class="grid grid-cols-2 gap-3">
                        <div><dt class="text-xs text-slate-500">Zerado em</dt><dd class="font-semibold text-slate-100" x-text="UI.fmtDate(peekGame.finished_at)"></dd></div>
                        <div><dt class="text-xs text-slate-500">Tempo</dt><dd class="font-semibold text-slate-100" x-text="UI.fmtTime(peekGame.playtime_minutes)"></dd></div>
                        <div x-show="peekGame.started_at"><dt class="text-xs text-slate-500">Começou em</dt><dd class="font-semibold text-slate-100" x-text="peekGame.started_at ? UI.fmtDate(peekGame.started_at) : ''"></dd></div>
                        <div x-show="peekGame.developer"><dt class="text-xs text-slate-500">Desenvolvedora</dt><dd class="font-semibold text-slate-100" x-text="peekGame.developer"></dd></div>
                        <div x-show="peekGame.publisher"><dt class="text-xs text-slate-500">Publisher</dt><dd class="font-semibold text-slate-100" x-text="peekGame.publisher"></dd></div>
                    </dl>
                    <div x-show="peekMemories.length" class="rounded-xl border border-slate-50/10 bg-slate-950/40 p-3">
                        <p class="kicker">Memórias</p>
                        <ul class="mt-2 space-y-1 text-slate-300"><template x-for="m in peekMemories" :key="m"><li x-text="m"></li></template></ul>
                    </div>
                    <div x-show="peekGame.tasks?.length" class="rounded-xl border border-slate-50/10 bg-slate-950/40 p-3">
                        <p class="kicker">Tarefas extras</p>
                        <ul class="mt-2 space-y-1 text-slate-300"><template x-for="t in peekGame.tasks ?? []" :key="t.description"><li x-text="'✓ ' + t.description + ' (tier ' + t.tier + ')'"></li></template></ul>
                    </div>
                    <blockquote x-show="peekNote" class="border-l-2 border-brand-400 pl-3 italic text-slate-300">
                        <span class="block text-xs not-italic font-semibold text-brand-400" x-text="peekNote ? 'Diário · ' + UI.fmtDate(peekNote.note_date) : ''"></span>
                        <span x-text="peekNote?.body"></span>
                    </blockquote>
                </div>
                <div class="sticky bottom-0 flex gap-2 border-t border-slate-50/10 bg-slate-900 p-4">
                    <a x-show="!isDemo" :href="base + '/jogos/' + peekGame.id" class="btn flex-1 text-center">Abrir o jogo</a>
                    <a x-show="!isDemo" :href="base + '/jogos/' + peekGame.id + '/editar'" class="btn-ghost">Editar</a>
                    <p x-show="isDemo" class="text-xs text-slate-500">No exemplo, os jogos não abrem.</p>
                </div>
            </div>
        </template>
    </aside>
</main>

<script>
function catalog(me, demo) {
    const blank = () => ({ q: '', platform: '', genre: '', difficulty: '', rating: '', year: '', release_year: '', developer: '', publisher: '', honors: '', missing: '' });
    let reqId = 0;
    const PAGE = 60; // jogos desenhados por vez (os demais entram conforme a rolagem)
    const MISSING = { developer: 'sem desenvolvedora', publisher: 'sem publisher', genre: 'sem gênero', cover: 'sem capa', release_year: 'sem ano de lançamento' };

    return {
        me,
        isDemo: !!demo,
        base: window.APP.base,
        filters: blank(),
        sortKey: 'finished_at:desc',
        sorts: [
            { v: 'finished_at:desc', l: 'Finalização · mais recentes' },
            { v: 'finished_at:asc',  l: 'Finalização · mais antigos' },
            { v: 'title:asc',        l: 'Título · A–Z' },
            { v: 'title:desc',       l: 'Título · Z–A' },
            { v: 'rating:desc',      l: 'Nota · maior' },
            { v: 'playtime:desc',    l: 'Tempo de jogo · maior' },
            { v: 'difficulty:desc',  l: 'Dificuldade · maior' },
            { v: 'release:desc',     l: 'Lançamento · mais novos' },
            { v: 'release:asc',      l: 'Lançamento · mais antigos' },
            { v: 'created:desc',     l: 'Adicionados recentemente' },
        ],
        options: { platforms: [], genres: [], years: [], release_years: [], developers: [], publishers: [], missing: {} },
        games: [],
        meta: { page: 0, pages: 0, total: 0 },
        ready: false,
        loading: false,
        loadingMore: false,
        error: '',

        // painel de filtros, resumo do recorte e best of da categoria filtrada
        filtersOpen: false,
        serverSummary: null,
        bestOf: null,
        fmt: (v) => new Intl.NumberFormat('pt-BR').format(v ?? 0),

        /** Os filtros do painel: rótulo, texto de "todos" e as opções. */
        get filterSelects() {
            const o = this.options, list = (a) => (a ?? []).map((v) => ({ v: String(v), l: String(v) }));
            return [
                { k: 'platform', l: 'Sistema', all: 'Todos os sistemas', options: () => list(o.platforms) },
                { k: 'genre', l: 'Gênero', all: 'Todos os gêneros', options: () => list(o.genres) },
                { k: 'rating', l: 'Nota', all: 'Toda nota', options: () => [11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1].map((n) => ({ v: String(n), l: 'Nota ' + n })) },
                { k: 'difficulty', l: 'Dificuldade', all: 'Toda dificuldade', options: () => ['C', 'B', 'A', 'AA', 'AAA'].map((d) => ({ v: d, l: 'Dificuldade ' + d })) },
                { k: 'year', l: 'Finalizado em', all: 'Todos os anos', options: () => list(o.years) },
                { k: 'release_year', l: 'Lançado em', all: 'Todos os anos', options: () => list(o.release_years) },
                { k: 'developer', l: 'Desenvolvedora', all: 'Todas as desenvolvedoras', options: () => list(o.developers) },
                { k: 'publisher', l: 'Publisher', all: 'Todos os publishers', options: () => list(o.publishers) },
                { k: 'missing', l: 'Faltando dados', all: 'Sem filtro', options: () => Object.entries(MISSING).map(([v, l]) => ({ v, l: l[0].toUpperCase() + l.slice(1) + (o.missing?.[v] != null ? ` (${o.missing[v]})` : '') })) },
            ];
        },
        get activeCount() { return Object.values(this.filters).filter(Boolean).length; },
        /** Etiquetas removíveis dos filtros ativos. */
        get chips() {
            const f = this.filters, names = { platform: '', genre: '', developer: '', publisher: '', year: 'Zerado em ', release_year: 'Lançado em ', rating: 'Nota ', difficulty: 'Dificuldade ' };
            return Object.entries(f).filter(([, v]) => v).map(([k, v]) => ({
                k, l: k === 'q' ? `"${v}"` : k === 'honors' ? '🏆 Hall da fama' : k === 'missing' ? 'Faltando: ' + (MISSING[v] ?? v).replace('sem ', '') : (names[k] ?? '') + v,
            }));
        },
        /** Atalhos quando não há filtro: hall da fama e o dado que mais falta. */
        get quick() {
            const out = [{ k: 'honors', v: '1', l: '🏆 Hall da fama', gold: true }];
            const m = Object.entries(this.options.missing ?? {}).filter(([k, n]) => n > 0 && k !== 'cover').sort((a, b) => b[1] - a[1])[0];
            if (m) out.push({ k: 'missing', v: m[0], l: `⚠ ${m[1]} ${MISSING[m[0]]}` });
            if (this.options.missing?.cover) out.push({ k: 'missing', v: 'cover', l: `🖼 ${this.options.missing.cover} sem capa` });
            return this.isDemo ? out.slice(0, 1) : out;
        },
        setQuick(a) { this.filters[a.k] = a.v; this.apply(); },
        removeFilter(k) { this.filters[k] = ''; this.apply(); },
        /** Resumo do recorte: do servidor (todas as páginas) ou calculado no exemplo. */
        get summary() {
            if (this.serverSummary && !this.isDemo) return this.serverSummary;
            const n = this.games.length;
            return { games: n, minutes: this.games.reduce((s, g) => s + g.playtime_minutes, 0), avg_rating: n ? Math.round(this.games.reduce((s, g) => s + g.rating, 0) / n * 10) / 10 : null };
        },
        /** Preenche em massa o dado que falta: seleciona todos do recorte e abre a barra já no campo certo. */
        fixMissing() {
            this.selecting = true;
            this.selectAll();
            this.bulk = { field: this.filters.missing, value: '' };
            this.filtersOpen = false;
        },

        // ---- divisões (ano, nota, letra...) e desenho em lotes ----
        density: 'comfortable',
        visibleCount: PAGE,
        currentGroup: null,
        setDensity(v) { this.density = v; try { localStorage.setItem('mgl-density', v); } catch { /* sem storage */ } },
        get grouped() { return !this.sortKey.startsWith('created'); },
        /**
         * Divide os jogos (já ordenados) conforme a ordenação escolhida. Guardado em `groups` e refeito só quando
         * a lista ou a ordenação mudam (antes era recalculado várias vezes a cada desenho).
         */
        groups: [],
        regroup() {
            const sort = this.sortKey.split(':')[0];
            const of = (g) => {
                switch (sort) {
                    case 'finished_at': { const y = g.finished_at.slice(0, 4); return [y, y, y]; }
                    case 'release': {
                        if (!g.release_year) return ['dnone', 'Sem ano de lançamento', '?'];
                        const d = Math.floor(g.release_year / 10) * 10;
                        return ['d' + d, 'Anos ' + d, "'" + String(d).slice(2) + 's'];
                    }
                    case 'rating': return ['r' + g.rating, 'Nota ' + g.rating, String(g.rating)];
                    case 'difficulty': return ['d' + g.difficulty, 'Dificuldade ' + g.difficulty, g.difficulty];
                    case 'title': {
                        const c = g.title.normalize('NFD').replace(/[\u0300-\u036f]/g, '').charAt(0).toUpperCase();
                        return /[A-Z]/.test(c) ? [c, c, c] : ['num', '0–9 e símbolos', '#'];
                    }
                    case 'playtime': {
                        const h = g.playtime_minutes / 60;
                        return h >= 50 ? ['p50', '50 horas ou mais', '50h+'] : h >= 20 ? ['p20', '20 a 50 horas', '20–50h'] : h >= 10 ? ['p10', '10 a 20 horas', '10–20h'] : h >= 5 ? ['p5', '5 a 10 horas', '5–10h'] : ['p0', 'Até 5 horas', '<5h'];
                    }
                    default: return ['all', '', ''];
                }
            };
            const by = new Map();
            for (const g of this.games) {
                const [key, label, short] = this.grouped ? of(g) : ['all', '', ''];
                if (!by.has(key)) by.set(key, { key, label, short, games: [], count: 0, minutes: 0, ratingSum: 0, best: null });
                const grp = by.get(key);
                grp.games.push(g); grp.count++; grp.minutes += g.playtime_minutes; grp.ratingSum += g.rating;
                // o melhor da divisão: maior nota; empate → o zerado mais recentemente
                if (!grp.best || g.rating > grp.best.rating || (g.rating === grp.best.rating && g.finished_at > grp.best.finished_at)) grp.best = g;
            }
            this.groups = [...by.values()];
        },
        /** As divisões cortadas no lote atual (desenhar 336 estojos de uma vez pesa; vão entrando conforme a rolagem). */
        get shownGroups() {
            let left = this.visibleCount;
            const out = [];
            for (const grp of this.groups) {
                if (left <= 0) break;
                out.push(left >= grp.games.length ? grp : { ...grp, games: grp.games.slice(0, left) });
                left -= grp.games.length;
            }
            return out;
        },
        get shownCount() { return Math.min(this.visibleCount, this.games.length); },
        showMore() { if (this.visibleCount < this.games.length) this.visibleCount += PAGE; },
        /** Pula para uma divisão, desenhando os jogos até ela se ainda não estiverem na tela. */
        jumpTo(grp) {
            let start = 0;
            for (const x of this.groups) { if (x.key === grp.key) break; start += x.count; }
            if (this.visibleCount < start + grp.count) this.visibleCount = start + Math.max(grp.count, PAGE);
            this.$nextTick(() => document.getElementById('grp-' + grp.key)?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }));
        },
        /** Destaca no índice a divisão que está no topo da tela. */
        trackGroup() {
            let cur = null;
            for (const el of document.querySelectorAll('[data-group]')) { if (el.getBoundingClientRect().top < 140) cur = el.dataset.group; else break; }
            this.currentGroup = cur ?? this.groups[0]?.key ?? null;
        },

        // visual do catálogo
        layout: 'case',
        layouts: [{ v: 'case', l: 'Estojo' }, { v: 'shelf', l: 'Estante 3D' }, { v: 'terminal', l: 'Terminal' }, { v: 'mosaic', l: 'Mosaico' }],
        initLayout() {
            let v = new URLSearchParams(location.search).get('visual') || me?.layout; // ?visual=shelf abre direto (sem salvar)
            if (!v) { try { v = localStorage.getItem('mgl-layout'); } catch { /* sem storage */ } }
            if (this.layouts.some((l) => l.v === v)) this.layout = v;
        },
        async setLayout(v) {
            if (!this.layouts.some((l) => l.v === v)) return;
            this.layout = v;
            try { localStorage.setItem('mgl-layout', v); } catch { /* sem storage */ }
            if (!this.isDemo) { try { await api.put('/api/me/layout', { layout: v }); } catch { /* fica só neste navegador */ } }
        },
        itemHref(g) { return this.isDemo ? null : this.base + '/jogos/' + g.id; },
        itemClick(g, e) { if (this.selecting) { e.preventDefault(); this.toggle(g); } else if (this.isDemo) e.preventDefault(); },
        /** fundo do destaque: o jogo zerado mais recente do recorte que tenha capa */
        get heroCover() { return this.games.find((g) => g.cover_url)?.cover_url ?? null; },

        // ---- espiar (painel lateral com os detalhes) ----
        peekGame: null, peekLoading: false, peekNote: null, peekOpener: null,
        async peek(g) {
            this.peekOpener = document.activeElement;
            this.peekGame = { ...g };
            this.peekNote = null;
            this.$nextTick(() => this.$refs.peekClose?.focus());
            if (this.isDemo) return;
            this.peekLoading = true;
            try {
                const [d, n] = await Promise.all([api.get('/api/games/' + g.id), api.get('/api/games/' + g.id + '/notes').catch(() => ({ data: [] }))]);
                if (this.peekGame?.id !== g.id) return;
                this.peekGame = { ...d.data, honors: g.honors };
                this.peekNote = [...(n.data ?? [])].sort((a, b) => String(b.note_date).localeCompare(String(a.note_date)))[0] ?? null;
            } catch { /* fica com o que a grade já tinha */ }
            this.peekLoading = false;
        },
        closePeek() { this.peekGame = null; this.$nextTick(() => this.peekOpener?.focus?.()); },
        get peekMemories() {
            const m = this.peekGame?.memory;
            if (!m) return [];
            return [
                m.intro_by && 'Apresentado por ' + m.intro_by, m.intro_where && 'Conseguido: ' + m.intro_where,
                m.intro_age != null && m.intro_age !== '' && 'Idade na época: ' + m.intro_age, m.played_where && 'Onde jogou: ' + m.played_where,
                m.people?.length && 'Com: ' + m.people.join(', '), m.music_title && '♪ ' + m.music_title,
            ].filter(Boolean);
        },
        /** Tab e Shift+Tab ficam circulando dentro do painel aberto */
        trapFocus(e, box) {
            const els = [...box.querySelectorAll('a[href], button:not([disabled])')].filter((el) => el.offsetParent !== null);
            if (!els.length) return;
            const first = els[0], last = els[els.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },

        // ---- me surpreenda: sorteia um jogo do recorte, rola até ele e destaca ----
        flashId: null,
        surprise() {
            if (!this.games.length) return;
            const g = this.games[Math.floor(Math.random() * this.games.length)];
            const idx = this.games.indexOf(g);
            if (this.visibleCount <= idx) this.visibleCount = idx + PAGE;
            this.flashId = g.id;
            this.$nextTick(() => {
                const el = document.getElementById('g-' + g.id) ?? document.querySelector(`[data-gid="${g.id}"]`);
                el?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
                el?.querySelector('a')?.focus({ preventScroll: true });
            });
            setTimeout(() => { if (this.flashId === g.id) this.flashId = null; }, 2600);
        },

        // ---- exportar o recorte em CSV (gerado aqui no navegador) ----
        exportCsv() {
            const cols = [['Título', (g) => g.title], ['Sistema', (g) => g.platform], ['Gênero', (g) => g.genre ?? ''], ['Lançamento', (g) => g.release_year ?? ''],
                ['Zerado em', (g) => g.finished_at], ['Horas', (g) => (g.playtime_minutes / 60).toFixed(1).replace('.', ',')], ['Nota', (g) => g.rating],
                ['Dificuldade', (g) => g.difficulty], ['Hall da fama', (g) => (g.honors ?? []).join(' / ')]];
            const cell = (v) => { const s = String(v ?? ''); return /[;"\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s; };
            const lines = [cols.map((c) => c[0]).join(';'), ...this.games.map((g) => cols.map((c) => cell(c[1](g))).join(';'))];
            const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'meu-catalogo' + (this.hasFilters ? '-recorte' : '') + '.csv';
            a.click();
            setTimeout(() => URL.revokeObjectURL(a.href), 2000);
        },

        // ---- visões salvas (neste navegador) ----
        viewsOpen: false, viewName: '', savedViews: [],
        loadViews() { try { this.savedViews = JSON.parse(localStorage.getItem('mgl-catalog-views')) ?? []; } catch { this.savedViews = []; } },
        storeViews() { try { localStorage.setItem('mgl-catalog-views', JSON.stringify(this.savedViews)); } catch { /* sem storage */ } },
        saveView() {
            const name = this.viewName.trim();
            if (!name) return;
            const desc = [...this.chips.map((c) => c.l), this.sorts.find((s) => s.v === this.sortKey)?.l].filter(Boolean).join(' · ');
            this.savedViews = [...this.savedViews.filter((v) => v.name !== name), { name, filters: { ...this.filters }, sortKey: this.sortKey, desc }];
            this.storeViews();
            this.viewName = '';
            this.notice = `Visão "${name}" salva.`;
        },
        openView(v) {
            this.filters = { ...blank(), ...v.filters };
            this.sortKey = this.sorts.some((s) => s.v === v.sortKey) ? v.sortKey : 'finished_at:desc';
            this.viewsOpen = false;
            this.apply();
        },
        deleteView(i) { this.savedViews = this.savedViews.filter((_, k) => k !== i); this.storeViews(); },

        // edição em massa
        selecting: false, sel: [], busy: false, confirmDelete: false, notice: '', skipped: [],
        bulk: { field: 'platform', value: '' },
        bulkFields: [
            { v: 'platform', l: 'Sistema' }, { v: 'genre', l: 'Gênero' }, { v: 'difficulty', l: 'Dificuldade' }, { v: 'rating', l: 'Nota' },
            { v: 'release_year', l: 'Ano de lançamento' }, { v: 'developer', l: 'Desenvolvedora' }, { v: 'publisher', l: 'Publisher' },
        ],
        get optional() { return ['developer', 'publisher'].includes(this.bulk.field); },
        get bulkOptions() { return { platform: this.options.platforms, genre: this.options.genres, developer: this.options.developers, publisher: this.options.publishers }[this.bulk.field] ?? []; },
        isSel(g) { return this.sel.includes(g.id); },
        toggle(g) { this.sel = this.isSel(g) ? this.sel.filter(i => i !== g.id) : [...this.sel, g.id]; },
        selectAll() { this.sel = this.games.map(g => g.id); },
        stopSelecting() { this.selecting = false; this.sel = []; this.confirmDelete = false; },

        async applyBulk() {
            if (this.sel.length > 500) { this.error = 'Selecione no máximo 500 jogos por vez.'; return; }
            this.busy = true; this.error = ''; this.notice = ''; this.skipped = [];
            try {
                const { data } = await api.post('/api/games/bulk', { ids: this.sel, action: 'update', field: this.bulk.field, value: this.bulk.value });
                const f = this.bulkFields.find(x => x.v === this.bulk.field).l.toLowerCase();
                this.notice = data.updated + (data.updated === 1 ? ' jogo atualizado' : ' jogos atualizados') + ' (' + f + ').';
                this.skipped = data.skipped;
                this.bulk.value = ''; this.sel = [];
                await this.refresh();
            } catch (e) { this.error = e.message; }
            this.busy = false;
        },

        async deleteBulk() {
            this.busy = true; this.error = ''; this.notice = ''; this.skipped = [];
            try {
                const { data } = await api.post('/api/games/bulk', { ids: this.sel, action: 'delete' });
                this.notice = data.deleted + (data.deleted === 1 ? ' jogo excluído.' : ' jogos excluídos.');
                this.confirmDelete = false; this.sel = [];
                await this.refresh();
            } catch (e) { this.error = e.message; this.confirmDelete = false; }
            this.busy = false;
        },

        // recarrega a lista e as opções dos filtros (nomes novos / nomes que sumiram)
        async refresh() {
            try { this.options = await api.get('/api/me/filters'); } catch { /* mantém as opções */ }
            await this.fetchAll();
        },

        get hasFilters() { return Object.values(this.filters).some(Boolean); },

        async init() {
            this.initLayout();
            this.loadViews();
            // "/" leva à busca (quando não se está digitando em outro campo)
            window.addEventListener('keydown', (e) => {
                if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey || /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName) || this.peekGame) return;
                e.preventDefault();
                this.$refs.search?.focus();
            });
            try { const d = localStorage.getItem('mgl-density'); if (d === 'compact' || d === 'comfortable') this.density = d; } catch { /* padrão */ }
            // lotes: desenha mais quando o fim da grade aparece; índice acompanha a rolagem
            if ('IntersectionObserver' in window) {
                this.$nextTick(() => new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting)) this.showMore(); }, { rootMargin: '800px' }).observe(this.$refs.more));
            } else this.visibleCount = Infinity;
            let ticking = false;
            window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(() => { this.trackGroup(); ticking = false; }); } }, { passive: true });
            if (this.isDemo) this.markDemoHonors();
            if (this.isDemo) this.options = this.demoOptions();
            else try { this.options = await api.get('/api/me/filters'); } catch { /* filtros ficam vazios */ }
            await this.$nextTick(); // renderiza as <option> antes de aplicar o valor salvo na URL
            // restaura os filtros da URL (compartilhável / botão voltar)
            const p = new URLSearchParams(location.search);
            for (const k of Object.keys(this.filters)) this.filters[k] = p.get(k) ?? '';
            if (p.get('sort')) this.sortKey = `${p.get('sort')}:${p.get('dir') === 'asc' ? 'asc' : 'desc'}`;
            await this.fetchAll();
        },

        apply() { this.fetchAll(); },
        /** Exemplo: o hall da fama é o jogo de maior nota de cada sistema (com 2+ jogos). */
        markDemoHonors() {
            for (const [field, prefix] of [['platform', 'Best of ']]) {
                const by = {};
                for (const g of demo) if (g[field]) (by[g[field]] ??= []).push(g);
                for (const [v, list] of Object.entries(by)) {
                    if (list.length < 2) continue;
                    const top = [...list].sort((a, b) => b.rating - a.rating || b.id - a.id)[0];
                    (top.honors ??= []).push(prefix + v);
                }
            }
            for (const g of demo) g.honors ??= [];
        },
        clear() { this.filters = blank(); this.fetchAll(); },

        syncUrl() {
            const [sort, dir] = this.sortKey.split(':');
            const p = new URLSearchParams();
            for (const [k, v] of Object.entries(this.filters)) if (v) p.set(k, v);
            if (this.sortKey !== 'finished_at:desc') { p.set('sort', sort); p.set('dir', dir); }
            const qs = p.toString();
            history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
        },

        // ---- modo exemplo: os mesmos filtros e ordenações da API, mas sobre os dados locais ----
        demoOptions() {
            const uniq = (f) => [...new Set(demo.map(f).filter(Boolean))];
            const by = (a, b) => String(a).localeCompare(String(b), 'pt', { sensitivity: 'base' });
            return {
                platforms: uniq((g) => g.platform).sort(by), genres: uniq((g) => g.genre).sort(by),
                developers: uniq((g) => g.developer).sort(by), publishers: uniq((g) => g.publisher).sort(by),
                years: uniq((g) => Number(g.finished_at.slice(0, 4))).sort((x, y) => y - x),
                release_years: uniq((g) => g.release_year).sort((x, y) => y - x),
            };
        },
        demoQuery() {
            const key = (s) => String(s ?? '').trim().replace(/\s+/g, ' ').toLowerCase();
            const f = this.filters, q = key(f.q);
            const list = demo.filter((g) =>
                (!q || [g.title, g.platform, g.genre, g.developer, g.publisher].some((x) => key(x).includes(q))) &&
                (!f.platform || key(g.platform) === key(f.platform)) &&
                (!f.genre || key(g.genre) === key(f.genre)) &&
                (!f.difficulty || g.difficulty === f.difficulty) &&
                (!f.rating || String(g.rating) === f.rating) &&
                (!f.year || g.finished_at.slice(0, 4) === f.year) &&
                (!f.release_year || String(g.release_year) === f.release_year) &&
                (!f.developer || key(g.developer) === key(f.developer)) &&
                (!f.publisher || key(g.publisher) === key(f.publisher)) &&
                (!f.honors || g.honors?.length) &&
                (!f.missing || !(f.missing === 'cover' ? g.cover_url : g[f.missing])));
            const [sort, dir] = this.sortKey.split(':');
            const mul = dir === 'asc' ? 1 : -1;
            const order = { C: 0, B: 1, A: 2, AA: 3, AAA: 4 };
            const val = {
                finished_at: (g) => g.finished_at, title: (g) => key(g.title), rating: (g) => g.rating,
                playtime: (g) => g.playtime_minutes, difficulty: (g) => order[g.difficulty], created: (g) => g.id,
                release: (g) => g.release_year ?? 0,
            }[sort] ?? ((g) => g.finished_at);
            return list.sort((a, b) => ((val(a) < val(b) ? -1 : val(a) > val(b) ? 1 : 0) * mul) || (b.id - a.id));
        },

        /** Traz todos os jogos do filtro atual: o primeiro lote aparece na hora e os demais entram em seguida. */
        async fetchAll() {
            if (this.isDemo) {
                const all = this.demoQuery();
                this.games = all;
                this.regroup();
                this.visibleCount = PAGE;
                // best of de exemplo do sistema filtrado (o de maior nota)
                const top = this.filters.platform ? [...all].sort((a, b) => b.rating - a.rating)[0] : null;
                this.bestOf = top && all.length >= 2 ? { kind: 'platform', value: this.filters.platform, game: top } : null;
                this.meta = { page: 1, per_page: all.length, total: all.length, pages: 1 };
                this.ready = true; this.error = '';
                this.syncUrl();
                return;
            }
            const id = ++reqId; // descarta respostas antigas se o usuário mudar o filtro rápido
            this.loading = true;
            this.loadingMore = false;
            this.error = '';
            const [sort, dir] = this.sortKey.split(':');
            const p = new URLSearchParams({ sort, dir, per_page: 250, view: 'grid' }); // lista enxuta: só o que a grade usa
            for (const [k, v] of Object.entries(this.filters)) if (v) p.set(k, v);
            try {
                for (let page = 1; ; page++) {
                    p.set('page', page);
                    const res = await api.get('/api/games?' + p);
                    if (id !== reqId) return;
                    this.games = page === 1 ? res.data : [...this.games, ...res.data];
                    this.regroup();
                    this.meta = res.meta;
                    this.ready = true;
                    if (page === 1) {
                        this.syncUrl(); this.loading = false; this.visibleCount = PAGE;
                        this.serverSummary = res.meta.summary ?? null; this.bestOf = res.meta.best ?? null;
                        this.$nextTick(() => this.trackGroup());
                    }
                    if (page >= res.meta.pages) break;
                    this.loadingMore = true;
                }
            } catch (e) {
                if (id !== reqId) return;
                if (e.status === 401) return (location.href = this.base + '/login');
                this.error = e.message;
            } finally {
                if (id === reqId) { this.loading = false; this.loadingMore = false; }
            }
        },
    };
}
</script>
