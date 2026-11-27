<?php
/** @var array $me */
/** @var array|null $franchise  franquia em edição (nulo = nova) */
$franchise = $franchise ?? null;
$editing = $franchise !== null;
?>
<main class="page page-normal"
      x-data='franchiseForm(<?= e(json_encode($franchise, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <a href="<?= e(url($editing ? '/franquias/' . (int) $franchise['id'] : '/franquias')) ?>" @click="confirmLeave($event)" class="back-link mb-3">← <?= $editing ? 'Voltar à franquia' : 'Franquias' ?></a>

    <!-- Prévia ao vivo do banner: muda enquanto você edita -->
    <header class="hero-box text-white">
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <img x-show="effUrl('hero')" :src="effUrl('hero')" alt="" class="h-full w-full object-cover" :style="UI.focusStyle(focus.hero)">
            <div x-show="!effUrl('hero')" class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgb(var(--brand-600)/.45),transparent_60%)]"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/55 to-black/15"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-transparent"></div>
        </div>
        <div class="flex min-h-[13rem] flex-col justify-end p-5 sm:min-h-[15rem] sm:p-8">
            <p class="kicker-hero"><?= $editing ? 'Editando a franquia' : 'Nova franquia' ?> · prévia ao vivo</p>
            <h1 class="mt-1 text-3xl font-black leading-tight tracking-tight drop-shadow-lg sm:text-4xl" :class="form.name ? '' : 'text-white/40'" x-text="form.name || 'Nome da franquia'"></h1>
            <p class="mt-1 text-sm text-white/75" x-text="form.description || 'Monte a lista de jogos da franquia. O que já estiver no seu catálogo conta como feito automaticamente.'"></p>
            <div x-show="totals.entries" x-cloak class="mt-4 flex gap-1.5" aria-hidden="true">
                <template x-for="s in sections" :key="s.key">
                    <div x-show="s.entries.length" class="flex min-w-0 gap-[3px]" :style="'flex:' + Math.max(s.entries.length, 1)">
                        <template x-for="e in s.entries" :key="e.key">
                            <span class="h-2.5 min-w-[3px] flex-1 rounded-sm" :class="status(e).done ? 'bg-ok' : (e.title.trim() ? 'bg-white/25' : 'bg-white/10')"></span>
                        </template>
                    </div>
                </template>
            </div>
            <p class="mt-2 text-sm text-white/70" aria-live="polite">
                <span x-text="totals.entries + (totals.entries === 1 ? ' jogo' : ' jogos') + ' em ' + sections.length + (sections.length === 1 ? ' seção' : ' seções')"></span>
                <span x-show="indexReady" x-cloak x-text="' · ' + totals.done + ' já no catálogo (' + (totals.entries ? Math.round(totals.done / totals.entries * 100) : 0) + '%)'"></span>
            </p>
        </div>
    </header>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_17rem]">
    <form @submit.prevent="save" novalidate class="min-w-0 space-y-6">
        <div x-show="formError" x-cloak x-ref="formError" role="alert" class="alert-error" x-text="formError"></div>

        <!-- Dados gerais -->
        <section class="card space-y-4">
            <div>
                <label for="f-name" class="lbl">Nome da franquia</label>
                <input id="f-name" type="text" maxlength="80" x-model="form.name" class="field" :class="err('name') && 'border-danger/60'" placeholder="Ex.: Resident Evil">
                <p x-show="err('name')" x-cloak class="err" x-text="err('name')"></p>
            </div>
            <div>
                <label for="f-desc" class="lbl">Descrição <span class="font-normal text-slate-500">(opcional)</span></label>
                <input id="f-desc" type="text" maxlength="300" x-model="form.description" class="field" :class="err('description') && 'border-danger/60'" placeholder="Ex.: Survival horror da Capcom">
                <p x-show="err('description')" x-cloak class="err" x-text="err('description')"></p>
            </div>
            <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-400" aria-live="polite">
                <span><strong class="text-slate-50" x-text="totals.entries"></strong> <span x-text="totals.entries === 1 ? 'jogo' : 'jogos'"></span> em <strong class="text-slate-50" x-text="sections.length"></strong> <span x-text="sections.length === 1 ? 'seção' : 'seções'"></span></span>
                <span x-show="indexReady" x-cloak><strong class="text-ok" x-text="totals.done"></strong> já no seu catálogo · <strong class="text-slate-200" x-text="totals.entries - totals.done"></strong> pendentes</span>
            </p>
        </section>

        <!-- Imagens: card da lista e banner da página (cada um com imagem e enquadramento próprios) -->
        <section class="card" aria-labelledby="f-cover-h"
                 @dragover.prevent="dragging = true" @dragleave.self.prevent="dragging = false" @drop.prevent="dragging = false; pickCover($event.dataTransfer.files[0])">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="f-cover-h" class="lbl !mb-0">Imagens da franquia <span class="font-normal text-slate-500">(opcionais)</span></h2>
                <p class="text-xs text-slate-500">Se só uma for escolhida, ela vale para os dois lugares.</p>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div class="seg" role="group" aria-label="Qual imagem editar">
                    <template x-for="t in [{ v: 'card', l: 'Card da lista' }, { v: 'hero', l: 'Banner da página' }]" :key="t.v">
                        <button type="button" @click="focusTab = t.v; natural = null" :aria-pressed="focusTab === t.v" class="flex items-center gap-2 seg-btn"
                                :class="focusTab === t.v ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">
                            <span x-text="t.l"></span>
                            <!-- indicador: imagem própria (●), herdada da outra (◐) ou nenhuma (○) -->
                            <span class="text-2xs" aria-hidden="true" x-text="imgs[t.v].url ? '●' : (effUrl(t.v) ? '◐' : '○')"></span>
                        </button>
                    </template>
                </div>
                <div x-show="effUrl(focusTab)" x-cloak class="flex gap-2">
                    <button type="button" @click="$refs.file.click()" class="btn-ghost btn-sm"
                            x-text="imgs[focusTab].url ? 'Trocar imagem' : 'Enviar imagem própria'"></button>
                    <button type="button" x-show="imgs[focusTab].url" @click="clearCover()" class="rounded-lg px-3 py-1.5 text-sm font-medium text-danger hover:bg-danger/10">Remover</button>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500" x-text="focusTab === 'card'
                ? 'Aparece no card da lista de franquias. Formato largo (cerca de 12:5); sem imagem, o card mostra as capas dos jogos zerados.'
                : 'Aparece no topo da página da franquia, com o nome por cima. Formato bem largo: ideal 2400×600 ou maior.'"></p>
            <p x-show="!imgs[focusTab].url && effUrl(focusTab)" x-cloak class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-400"
               x-text="focusTab === 'card' ? 'Usando a imagem do banner. Envie outra para o card ficar diferente.' : 'Usando a imagem do card. Envie outra para o banner ficar diferente.'"></p>

            <div class="mt-3 grid gap-4" :class="effUrl(focusTab) ? '' : 'sm:grid-cols-[minmax(0,20rem)_1fr]'">
                <!-- sem nenhuma imagem: área para soltar/escolher o arquivo -->
                <div x-show="!effUrl(focusTab)"
                     class="relative overflow-hidden rounded-xl border-2 border-dashed transition"
                     :class="[focusTab === 'card' ? 'aspect-[12/5]' : 'aspect-[4/1] sm:col-span-2', dragging ? 'border-brand-400 bg-brand-500/10' : 'border-slate-50/15 bg-slate-950/40', err('cover') ? '!border-danger/60' : '']">
                    <div x-show="focusTab === 'card'" class="absolute inset-0 grid grid-cols-4 opacity-40" aria-hidden="true">
                        <template x-for="i in 4" :key="i">
                            <div class="relative overflow-hidden border-r border-black/40 last:border-r-0">
                                <img x-show="coverChoices[i - 1]" :src="coverChoices[i - 1]?.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                                <div x-show="!coverChoices[i - 1]" class="absolute inset-0 bg-gradient-to-br from-neutral to-brand-600/30"></div>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="$refs.file.click()" class="absolute inset-0 flex flex-col items-center justify-center gap-1 p-4 text-center text-sm text-slate-200 hover:text-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 16l4-4 4 4 4-6 4 6M4 5h16v14H4z"/></svg>
                        <span>Arraste a imagem ou <span class="font-semibold text-brand-400">escolha um arquivo</span></span>
                        <span class="text-xs text-slate-400">JPEG, PNG ou WebP · até 8 MB</span>
                    </button>
                </div>

                <!-- com imagem: enquadramento -->
                <div x-show="effUrl(focusTab)" x-cloak class="min-w-0">
                    <div class="flex flex-wrap items-start gap-4">
                        <!-- moldura no formato real: arraste para posicionar -->
                        <div x-ref="frame" tabindex="0" role="application"
                             :aria-label="'Enquadramento do ' + (focusTab === 'card' ? 'card' : 'banner') + '. Arraste ou use as setas para mover; + e − para zoom.'"
                             @pointerdown="dragStart($event)" @pointermove="dragMove($event)" @pointerup="dragEnd($event)" @pointercancel="dragEnd($event)"
                             @keydown="focusKey($event)" @wheel.prevent="nudgeZoom($event.deltaY < 0 ? 0.1 : -0.1)"
                             class="group/frame relative w-full touch-none select-none overflow-hidden rounded-xl border bg-black outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                             :class="[focusTab === 'card' ? 'aspect-[12/5] max-w-md' : 'aspect-[17/4]', drag ? 'cursor-grabbing' : 'cursor-grab', dragging ? 'border-brand-400' : 'border-slate-50/15']">
                            <img :src="effUrl(focusTab)" alt="Prévia do enquadramento" draggable="false" @load="natural = { w: $event.target.naturalWidth, h: $event.target.naturalHeight }"
                                 class="pointer-events-none absolute inset-0 h-full w-full object-cover" :style="UI.focusStyle(focus[focusTab])">
                            <!-- simula o banner: degradê e título por cima -->
                            <template x-if="focusTab === 'hero'">
                                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/45 to-black/10"></div>
                                    <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent"></div>
                                    <p class="absolute bottom-[12%] left-[3%] text-[clamp(0.9rem,2.6vw,1.8rem)] font-bold leading-tight text-white drop-shadow-lg" x-text="form.name || 'Nome da franquia'"></p>
                                </div>
                            </template>
                            <span class="pointer-events-none absolute left-2 top-2 rounded-full bg-black/60 px-2.5 py-1 text-2xs font-semibold text-white opacity-90 transition group-hover/frame:opacity-100" x-show="!drag">✥ Arraste para posicionar</span>
                        </div>

                        <!-- banner no celular: mais alto, mesmo ponto focal -->
                        <figure x-show="focusTab === 'hero'" class="w-32 shrink-0">
                            <div class="relative aspect-[8/5] overflow-hidden rounded-lg border border-slate-50/15 bg-black">
                                <img :src="effUrl('hero')" alt="" class="absolute inset-0 h-full w-full object-cover" :style="UI.focusStyle(focus.hero)">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                            </div>
                            <figcaption class="mt-1 text-center text-2xs text-slate-500">No celular</figcaption>
                        </figure>
                    </div>

                    <div class="mt-3 flex max-w-2xl flex-wrap items-center gap-x-3 gap-y-2">
                        <label class="flex min-w-[16rem] flex-1 items-center gap-3 text-sm text-slate-400">Zoom
                            <input type="range" min="1" max="3" step="0.05" x-model.number="focus[focusTab].z" class="flex-1 accent-brand-500">
                            <span class="w-10 text-right tabular-nums text-slate-300" x-text="Math.round(focus[focusTab].z * 100) + '%'"></span>
                        </label>
                        <button type="button" @click="focus[focusTab] = { x: 50, y: 50, z: 1 }" class="rounded-lg px-3 py-1.5 text-sm font-medium text-brand-400 hover:bg-brand-500/10">Centralizar</button>
                    </div>
                </div>
                <input x-ref="file" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pickCover($event.target.files[0]); $event.target.value = ''">

                <!-- ou a capa de um jogo da franquia -->
                <div class="min-w-0" :class="!effUrl(focusTab) && focusTab === 'hero' ? 'sm:col-span-2' : ''">
                    <p class="text-sm text-slate-400">Ou use a capa de um jogo da franquia que está no seu catálogo<span x-text="focusTab === 'card' ? ' (no card)' : ' (no banner)'"></span>:</p>
                    <p class="mt-0.5 text-xs text-slate-500">As capas são pequenas e em pé, então ficam menos nítidas que uma imagem larga enviada, principalmente no banner.</p>
                    <p x-show="!coverChoices.length" x-cloak class="mt-2 text-xs text-slate-500" x-text="indexReady ? 'Nenhum jogo desta lista com capa no catálogo ainda.' : 'Carregando o catálogo…'"></p>
                    <ul x-show="coverChoices.length" x-cloak class="mt-2 flex flex-wrap gap-2">
                        <template x-for="g in coverChoices" :key="g.id">
                            <li>
                                <button type="button" @click="pickGame(g)" :aria-pressed="imgs[focusTab].game === g.id" :title="g.title + ' · ' + g.platform" :aria-label="'Usar a capa de ' + g.title"
                                        class="block h-20 w-14 overflow-hidden rounded-md border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                        :class="imgs[focusTab].game === g.id ? 'border-brand-400 ring-2 ring-brand-400/40' : 'border-transparent opacity-80 hover:opacity-100'">
                                    <img :src="g.cover_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
            <p x-show="err('cover')" x-cloak class="err" x-text="err('cover')"></p>
        </section>

        <p x-show="err('sections')" x-cloak class="err" x-text="err('sections')"></p>

        <!-- Seções -->
        <template x-for="(s, si) in sections" :key="s.key">
            <section class="card scroll-mt-24" :id="'sec-' + s.key" :aria-label="'Seção ' + (s.name || (si + 1))">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="icon-btn" @click="s.collapsed = !s.collapsed" :aria-expanded="!s.collapsed" :aria-label="s.collapsed ? 'Expandir seção' : 'Recolher seção'">
                        <svg class="h-4 w-4 transition" :class="s.collapsed ? '-rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="min-w-[10rem] flex-1">
                        <input type="text" maxlength="80" x-model="s.name" :aria-label="'Nome da seção ' + (si + 1)" class="field !py-2 font-semibold" :class="err(`sections.${si}.name`) && 'border-danger/60'" placeholder="Nome da seção (ex.: Principal)">
                    </div>
                    <span class="rounded-md bg-slate-50/10 px-2 py-1 text-xs font-bold text-slate-300" x-text="sectionDone(s) + '/' + s.entries.length" title="Já no catálogo / total"></span>
                    <button type="button" class="icon-btn" @click="moveSection(si, -1)" :disabled="si === 0" aria-label="Mover seção para cima">↑</button>
                    <button type="button" class="icon-btn" @click="moveSection(si, 1)" :disabled="si === sections.length - 1" aria-label="Mover seção para baixo">↓</button>
                    <button type="button" class="icon-btn hover:!text-danger" @click="removeSection(si)" aria-label="Excluir seção">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                    </button>
                </div>
                <p x-show="err(`sections.${si}.name`)" x-cloak class="err ml-11" x-text="err(`sections.${si}.name`)"></p>
                <p x-show="err(`sections.${si}.entries`)" x-cloak class="err ml-11" x-text="err(`sections.${si}.entries`)"></p>

                <div x-show="!s.collapsed" class="mt-4">
                    <p x-show="!s.entries.length" x-cloak class="rounded-lg border border-dashed border-slate-50/15 px-4 py-6 text-center text-sm text-slate-500">Nenhum jogo nesta seção ainda.</p>

                    <ul class="space-y-2">
                        <template x-for="(e, ei) in s.entries" :key="e.key">
                            <li class="rounded-xl border border-slate-50/10 bg-slate-950/40 p-2.5">
                                <div class="flex flex-wrap items-start gap-2 sm:flex-nowrap">
                                    <!-- status ao vivo: já está no catálogo? -->
                                    <span class="mt-1.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-black" aria-hidden="true"
                                          :class="status(e).done ? 'bg-ok text-black' : 'bg-slate-50/10 text-slate-500'"
                                          :title="status(e).done ? 'No catálogo: ' + status(e).game.title + ' (' + status(e).game.platform + ')' : 'Ainda não está no catálogo'"
                                          x-text="status(e).done ? '✓' : '·'"></span>

                                    <div class="min-w-0 flex-1 basis-full sm:basis-auto">
                                        <input type="text" maxlength="200" list="dl-titles" x-model="e.title" :aria-label="'Título do jogo ' + (ei + 1)" class="field !py-2" :class="err(`sections.${si}.entries.${ei}.title`) && 'border-danger/60'" placeholder="Título do jogo" autocomplete="off">
                                        <p x-show="err(`sections.${si}.entries.${ei}.title`)" x-cloak class="err" x-text="err(`sections.${si}.entries.${ei}.title`)"></p>
                                        <p x-show="status(e).done" x-cloak class="mt-1 text-xs text-ok">
                                            ✓ <span x-text="e.game_id ? 'Vinculado a' : 'No catálogo:'"></span> <span class="font-medium" x-text="status(e).game ? status(e).game.title + ' · ' + status(e).game.platform : ''"></span>
                                        </p>
                                    </div>
                                    <div class="w-20 shrink-0">
                                        <input type="number" inputmode="numeric" min="1950" x-model="e.release_year" :aria-label="'Ano do jogo ' + (ei + 1)" class="field !py-2" :class="err(`sections.${si}.entries.${ei}.release_year`) && 'border-danger/60'" placeholder="Ano">
                                    </div>
                                    <div class="flex shrink-0 items-center">
                                        <button type="button" class="icon-btn" @click="moveEntry(si, ei, -1)" :disabled="ei === 0" aria-label="Mover para cima">↑</button>
                                        <button type="button" class="icon-btn" @click="moveEntry(si, ei, 1)" :disabled="ei === s.entries.length - 1" aria-label="Mover para baixo">↓</button>
                                        <button type="button" class="icon-btn" :class="e.game_id || e.linking ? '!text-brand-400' : ''" @click="e.linking = !e.linking; e.linkQ = ''" :aria-expanded="e.linking" title="Vincular manualmente a um jogo do catálogo" aria-label="Vincular a um jogo do catálogo">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/></svg>
                                        </button>
                                        <button type="button" class="icon-btn hover:!text-danger" @click="removeEntry(si, ei)" aria-label="Remover jogo">
                                            <?= icon('close', 'h-4 w-4') ?>
                                        </button>
                                    </div>
                                </div>
                                <p x-show="err(`sections.${si}.entries.${ei}.release_year`)" x-cloak class="err ml-8" x-text="err(`sections.${si}.entries.${ei}.release_year`)"></p>

                                <!-- vínculo manual -->
                                <div x-show="e.linking" x-cloak class="mt-2 rounded-lg border border-slate-50/10 bg-slate-900/70 p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                                        <span>Escolha o jogo do seu catálogo que cumpre este item (útil quando o nome é diferente).</span>
                                        <button type="button" x-show="e.game_id" x-cloak @click="e.game_id = null" class="font-semibold text-danger hover:underline">Remover vínculo</button>
                                    </div>
                                    <input type="search" x-model="e.linkQ" placeholder="Buscar no catálogo…" class="field mt-2 !py-2" aria-label="Buscar jogo no catálogo">
                                    <ul class="mt-2 max-h-48 divide-y divide-slate-50/5 overflow-y-auto rounded-lg">
                                        <template x-for="g in linkMatches(e.linkQ)" :key="g.id">
                                            <li>
                                                <button type="button" @click="e.game_id = g.id; e.linking = false" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50/10"
                                                        :class="e.game_id === g.id ? 'bg-brand-500/15' : ''">
                                                    <span class="truncate text-slate-50" x-text="g.title"></span>
                                                    <span class="shrink-0 text-xs text-slate-400" x-text="g.platform + (g.release_year ? ' · ' + g.release_year : '')"></span>
                                                </button>
                                            </li>
                                        </template>
                                        <li x-show="!linkMatches(e.linkQ).length" x-cloak class="px-3 py-2 text-sm text-slate-500">Nenhum jogo encontrado.</li>
                                    </ul>
                                </div>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button type="button" @click="addEntry(si)" :disabled="s.entries.length >= limits.entries" class="btn-ghost">+ Adicionar jogo</button>
                        <button type="button" @click="s.bulk = !s.bulk" :aria-expanded="s.bulk" class="rounded-lg px-3 py-2 text-sm font-medium text-brand-400 transition hover:bg-brand-500/10">Adicionar vários de uma vez</button>
                    </div>

                    <!-- adição em massa -->
                    <div x-show="s.bulk" x-cloak class="mt-3 rounded-xl border border-slate-50/10 bg-slate-950/40 p-3">
                        <label class="lbl" :for="'bulk-' + s.key">Um jogo por linha</label>
                        <textarea :id="'bulk-' + s.key" x-model="s.bulkText" rows="5" class="field font-mono text-sm" placeholder="Resident Evil 4 | 2005&#10;Resident Evil 5 | 2009&#10;Resident Evil Village"></textarea>
                        <p class="mt-1 text-xs text-slate-500">Formato: <code class="rounded bg-slate-50/10 px-1">Título | Ano</code>. O ano é opcional (use para diferenciar remakes de mesmo título).</p>
                        <div class="mt-2 flex gap-2">
                            <button type="button" @click="applyBulk(si)" class="btn">Adicionar à seção</button>
                            <button type="button" @click="s.bulk = false; s.bulkText = ''" class="rounded-lg px-3 py-2 text-sm text-slate-400 hover:text-slate-50">Cancelar</button>
                        </div>
                    </div>
                </div>
            </section>
        </template>

        <!-- Nova seção -->
        <section class="card" aria-label="Adicionar seção">
            <p class="lbl">Adicionar seção</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="name in suggestedSections" :key="name">
                    <button type="button" @click="addSection(name)" class="rounded-full border border-slate-50/10 px-3.5 py-1.5 text-sm font-medium text-slate-200 transition hover:border-brand-400/60 hover:bg-brand-500/10 hover:text-brand-400" x-text="'+ ' + name"></button>
                </template>
                <button type="button" @click="addSection('')" :disabled="sections.length >= limits.sections" class="rounded-full border border-dashed border-slate-50/20 px-3.5 py-1.5 text-sm font-medium text-slate-300 transition hover:border-brand-400/60 hover:text-brand-400 disabled:opacity-40">+ Seção personalizada</button>
            </div>
        </section>

        <!-- Barra de ações -->
        <div class="sticky bottom-0 -mx-4 flex items-center justify-between gap-3 border-t border-slate-50/10 bg-slate-950/90 px-4 py-3 pr-24 backdrop-blur sm:-mx-6 sm:px-6 sm:pr-24">
            <p class="text-xs text-slate-500" x-show="dirty" x-cloak>Alterações não salvas</p>
            <p class="text-xs text-slate-500" x-show="!dirty" x-cloak>&nbsp;</p>
            <div class="flex gap-3">
                <a href="<?= e(url($editing ? '/franquias/' . (int) $franchise['id'] : '/franquias')) ?>" @click="confirmLeave($event)" class="btn-ghost">Cancelar</a>
                <button type="submit" :disabled="loading" class="btn flex items-center gap-2 btn-lg">
                    <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="opacity-75"/></svg>
                    <span x-text="loading ? 'Salvando…' : '<?= $editing ? 'Salvar alterações' : 'Criar franquia' ?>'"></span>
                </button>
            </div>
        </div>
    </form>

    <!-- Lateral: prévia do card, checklist e atalhos das seções -->
    <aside class="hidden space-y-5 lg:sticky lg:top-24 lg:block lg:self-start" aria-label="Prévia e checklist">
        <section>
            <p class="kicker">Como fica na lista</p>
            <div class="mt-2 overflow-hidden panel">
                <div class="relative h-28 overflow-hidden bg-slate-950">
                    <img x-show="effUrl('card')" :src="effUrl('card')" alt="" class="h-full w-full object-cover" :style="UI.focusStyle(focus.card)">
                    <div x-show="!effUrl('card')" class="grid h-full grid-cols-4">
                        <template x-for="i in 4" :key="i">
                            <div class="relative overflow-hidden border-r border-black/40 last:border-r-0">
                                <img x-show="coverChoices[i - 1]" :src="coverChoices[i - 1]?.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                                <div x-show="!coverChoices[i - 1]" class="absolute inset-0 bg-gradient-to-br from-neutral to-brand-600/30"></div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="p-3">
                    <p class="truncate font-bold text-slate-50" x-text="form.name || 'Nome da franquia'"></p>
                    <p class="mt-1 text-xs text-slate-400" x-text="totals.done + ' de ' + totals.entries + ' jogos'"></p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-50/10"><div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="'width:' + (totals.entries ? totals.done / totals.entries * 100 : 0) + '%'"></div></div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-50/10 p-4">
            <p class="kicker">Checklist</p>
            <ul class="mt-3 space-y-2 text-sm">
                <template x-for="c in checklist" :key="c.l">
                    <li class="flex items-center gap-2">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-2xs font-black" :class="c.ok ? 'bg-ok text-black' : (c.optional ? 'bg-slate-50/10 text-slate-500' : 'bg-gold-400/20 text-gold-400')" x-text="c.ok ? '✓' : (c.optional ? '·' : '!')"></span>
                        <span :class="c.ok ? 'text-slate-300' : 'text-slate-400'" x-text="c.l"></span>
                    </li>
                </template>
            </ul>
        </section>

        <nav x-show="sections.length > 1" class="rounded-2xl border border-slate-50/10 p-4" aria-label="Ir para a seção">
            <p class="kicker">Seções</p>
            <ul class="mt-3 space-y-1.5 text-sm">
                <template x-for="(s, si) in sections" :key="s.key">
                    <li><button type="button" @click="s.collapsed = false; $nextTick(() => document.getElementById('sec-' + s.key)?.scrollIntoView({ behavior: 'smooth', block: 'start' }))" class="flex w-full justify-between gap-2 rounded-md px-2 py-1 text-left hover:bg-slate-50/5">
                        <span class="truncate text-slate-200" x-text="s.name || 'Seção ' + (si + 1)"></span>
                        <span class="shrink-0 text-xs tabular-nums text-slate-500" x-text="sectionDone(s) + '/' + s.entries.length"></span>
                    </button></li>
                </template>
            </ul>
        </nav>
    </aside>
    </div>

    <datalist id="dl-titles"><template x-for="t in titleOptions" :key="t"><option :value="t"></option></template></datalist>
</main>

<script>
function franchiseForm(franchise) {
    const base = window.APP.base;
    const limits = { sections: 20, entries: 100, total: 300 };
    const norm = (s) => String(s ?? '').trim().replace(/\s+/g, ' ').toLowerCase(); // espelha Str::key() do servidor
    let seq = 0;
    // índice do catálogo (fora do estado reativo: pode ter milhares de jogos)
    const byTitle = new Map(), byId = new Map();

    const newEntry = (o = {}) => ({ key: ++seq, title: '', release_year: '', game_id: null, linking: false, linkQ: '', ...o });
    const newSection = (name = '', entries = []) => ({ key: ++seq, name, collapsed: false, bulk: false, bulkText: '', entries });

    return {
        id: franchise?.id ?? null,
        editing: !!franchise,
        limits,
        form: { name: '', description: '' },
        sections: [],
        suggestedSectionNames: ['Principal', 'Remakes', 'Remasters', 'Spin-offs', 'Expansões', 'Outros'],
        titleOptions: [],
        indexReady: false,
        errors: {},
        formError: '',
        loading: false,
        saving: false,
        dirty: false,
        // imagens do card e do banner: arquivo novo (file), capa de um jogo do catálogo (game) ou remoção da atual (remove)
        imgs: {
            card: { file: null, game: null, remove: false, url: franchise?.cover_url ?? null },
            hero: { file: null, game: null, remove: false, url: franchise?.hero_url ?? null },
        },
        dragging: false,
        // enquadramento (ponto focal em % + zoom) do card e do banner
        focus: JSON.parse(JSON.stringify(franchise?.cover_focus ?? { card: { x: 50, y: 50, z: 1 }, hero: { x: 50, y: 50, z: 1 } })),
        focusTab: 'card', natural: null, drag: null,

        err(path) { return this.errors[path] || ''; },

        // ---------- inicialização ----------
        async init() {
            if (franchise) {
                this.form = { name: franchise.name, description: franchise.description || '' };
                this.sections = franchise.sections.map((s) => newSection(s.name, s.entries.map((e) => newEntry({
                    title: e.title, release_year: e.release_year || '', game_id: e.game_id,
                }))));
            } else {
                this.sections = [newSection('Principal', [newEntry()])];
            }
            try {
                const games = (await api.get('/api/me/game-index')).data;
                for (const g of games) {
                    byId.set(g.id, g);
                    const k = norm(g.title);
                    if (!byTitle.has(k)) byTitle.set(k, []);
                    byTitle.get(k).push(g);
                }
                this.titleOptions = [...new Set(games.map((g) => g.title))].slice(0, 1500);
                this.catalog = games;
            } catch { /* sem índice: o status ao vivo fica oculto, o servidor continua validando */ }
            this.indexReady = !!this.catalog;

            // alterações não salvas
            this.$nextTick(() => {
                this.$watch('form', () => (this.dirty = true));
                this.$watch('sections', () => (this.dirty = true));
                this.$watch('focus', () => (this.dirty = true));
                this.$watch('imgs', () => (this.dirty = true));
            });
            window.addEventListener('beforeunload', (ev) => { if (this.dirty && !this.saving) { ev.preventDefault(); ev.returnValue = ''; } });
        },
        catalog: null,

        confirmLeave(ev) {
            if (this.dirty && !confirm('Você tem alterações não salvas. Sair mesmo assim?')) ev.preventDefault();
        },

        // ---------- status ao vivo (mesma regra do servidor) ----------
        status(e) {
            void this.indexReady;
            if (e.game_id && byId.has(e.game_id)) return { done: true, game: byId.get(e.game_id), linked: true };
            const t = norm(e.title);
            if (!t || !this.indexReady) return { done: false };
            const y = Number(e.release_year) || null; // o sistema não conta: só título (+ ano, se informado)
            const hit = (byTitle.get(t) || []).find((g) => !y || !g.release_year || g.release_year === y);
            return hit ? { done: true, game: hit } : { done: false };
        },
        sectionDone(s) { return s.entries.filter((e) => this.status(e).done).length; },
        /** o que falta para a franquia ficar redondinha (lateral) */
        get checklist() {
            const titled = this.sections.reduce((n, s) => n + s.entries.filter((e) => e.title.trim()).length, 0);
            return [
                { l: 'Nome da franquia', ok: !!this.form.name.trim() },
                { l: 'Pelo menos 1 jogo na lista', ok: titled > 0 },
                { l: 'Todas as seções com nome', ok: this.sections.length > 0 && this.sections.every((s) => s.name.trim()) },
                { l: 'Imagem do card', ok: !!this.effUrl('card'), optional: true },
                { l: 'Imagem do banner', ok: !!this.effUrl('hero'), optional: true },
                { l: 'Descrição', ok: !!this.form.description.trim(), optional: true },
            ];
        },
        get totals() {
            const all = this.sections.flatMap((s) => s.entries);
            return { entries: all.length, done: all.filter((e) => this.status(e).done).length };
        },
        linkMatches(q) {
            const n = norm(q);
            const list = this.catalog || [];
            return (n ? list.filter((g) => norm(g.title).includes(n)) : list).slice(0, 30);
        },
        /** Jogos da lista que já estão no catálogo e têm capa (opções para a imagem do card). */
        get coverChoices() {
            const seen = new Set(), out = [];
            for (const s of this.sections) for (const e of s.entries) {
                const g = this.status(e).game;
                if (g?.cover_url && !seen.has(g.id)) { seen.add(g.id); out.push(g); }
            }
            return out.slice(0, 16);
        },

        // ---------- imagens (card e banner) ----------
        /** Imagem exibida num lugar: a própria ou, sem ela, a do outro lugar. */
        effUrl(slot) { return this.imgs[slot].url || this.imgs[slot === 'card' ? 'hero' : 'card'].url; },
        pickCover(file) {
            delete this.errors.cover;
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { this.errors = { ...this.errors, cover: 'Use uma imagem JPEG, PNG ou WebP.' }; return; }
            if (file.size > 8 * 1024 * 1024) { this.errors = { ...this.errors, cover: 'A imagem passa de 8 MB.' }; return; }
            this.setImg({ file, game: null, url: URL.createObjectURL(file) });
        },
        pickGame(g) { this.setImg({ file: null, game: g.id, url: g.cover_url }); },
        clearCover() {
            const slot = this.focusTab;
            this.setImg({ file: null, game: null, url: null, remove: !!(slot === 'card' ? franchise?.cover_url : franchise?.hero_url) });
        },
        /** Troca a imagem do lugar em edição e recentraliza o enquadramento dele. */
        setImg(next) {
            const cur = this.imgs[this.focusTab];
            if (cur.url?.startsWith('blob:')) URL.revokeObjectURL(cur.url);
            this.imgs[this.focusTab] = { remove: false, ...next };
            this.focus[this.focusTab] = { x: 50, y: 50, z: 1 };
            this.natural = null;
            this.dirty = true;
        },

        /** Quanto a imagem (object-cover + zoom) sobra além da moldura, em px, em cada eixo: é o curso de 0% a 100%. */
        overflow() {
            const r = this.$refs.frame.getBoundingClientRect(), z = this.focus[this.focusTab].z;
            const ratio = this.natural ? this.natural.w / this.natural.h : r.width / r.height;
            // tamanho desenhado pelo object-cover (cobre a moldura mantendo a proporção), vezes o zoom
            const w = Math.max(r.width, r.height * ratio) * z;
            const h = Math.max(r.height, r.width / ratio) * z;
            return { x: Math.max(1, w - r.width), y: Math.max(1, h - r.height) };
        },
        dragStart(ev) {
            if (ev.button !== 0) return;
            try { ev.currentTarget.setPointerCapture(ev.pointerId); } catch { /* sem captura: segue arrastando dentro da moldura */ }
            const p = this.focus[this.focusTab];
            this.drag = { sx: ev.clientX, sy: ev.clientY, x: p.x, y: p.y, o: this.overflow() };
        },
        dragMove(ev) {
            if (!this.drag) return;
            const d = this.drag, p = this.focus[this.focusTab];
            const clamp = (v) => Math.round(Math.min(100, Math.max(0, v)) * 10) / 10;
            // arrastar para a direita mostra mais do lado esquerdo: o ponto focal anda no sentido contrário
            p.x = clamp(d.x - (ev.clientX - d.sx) / d.o.x * 100);
            p.y = clamp(d.y - (ev.clientY - d.sy) / d.o.y * 100);
        },
        dragEnd() { this.drag = null; },
        nudgeZoom(d) { const p = this.focus[this.focusTab]; p.z = Math.round(Math.min(3, Math.max(1, p.z + d)) * 100) / 100; },
        focusKey(ev) {
            const p = this.focus[this.focusTab], step = ev.shiftKey ? 10 : 2;
            const move = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] }[ev.key];
            if (move) { p.x = Math.min(100, Math.max(0, p.x + move[0])); p.y = Math.min(100, Math.max(0, p.y + move[1])); }
            else if (ev.key === '+' || ev.key === '=') this.nudgeZoom(0.1);
            else if (ev.key === '-') this.nudgeZoom(-0.1);
            else return;
            ev.preventDefault();
        },

        /** Envia a imagem depois que a franquia existe (precisa do id). */
        async saveCover(id) {
            for (const [slot, path] of [['card', 'cover'], ['hero', 'hero']]) {
                const im = this.imgs[slot], url = `/api/franchises/${id}/${path}`;
                if (im.file) { const fd = new FormData(); fd.append('cover', im.file); await api.post(url, fd); }
                else if (im.game) await api.post(url, { game_id: im.game });
                else if (im.remove) await api.del(url);
                // já enviado: numa nova tentativa (se a outra imagem falhar), não reenvia
                im.file = null; im.game = null; im.remove = false;
            }
        },

        get suggestedSections() {
            const have = new Set(this.sections.map((s) => norm(s.name)));
            return this.suggestedSectionNames.filter((n) => !have.has(norm(n)));
        },

        // ---------- seções ----------
        addSection(name) {
            if (this.sections.length >= limits.sections) return;
            this.sections.push(newSection(name, [newEntry()]));
            this.$nextTick(() => {
                const inputs = document.querySelectorAll('input[aria-label^="Nome da seção"]');
                inputs[inputs.length - 1]?.focus();
            });
        },
        removeSection(i) {
            const s = this.sections[i];
            const filled = s.entries.some((e) => e.title.trim());
            if (filled && !confirm(`Excluir a seção "${s.name || i + 1}" e os jogos dela da lista?`)) return;
            this.sections.splice(i, 1);
        },
        moveSection(i, d) {
            const j = i + d;
            if (j < 0 || j >= this.sections.length) return;
            [this.sections[i], this.sections[j]] = [this.sections[j], this.sections[i]];
        },

        // ---------- itens ----------
        addEntry(si) {
            const s = this.sections[si];
            if (s.entries.length >= limits.entries) return;
            s.entries.push(newEntry());
            this.$nextTick(() => {
                const rows = this.$root.querySelectorAll(`section[aria-label^="Seção"]`)[si]?.querySelectorAll('input[list="dl-titles"]');
                rows?.[rows.length - 1]?.focus();
            });
        },
        removeEntry(si, ei) { this.sections[si].entries.splice(ei, 1); },
        moveEntry(si, ei, d) {
            const list = this.sections[si].entries, j = ei + d;
            if (j < 0 || j >= list.length) return;
            [list[ei], list[j]] = [list[j], list[ei]];
        },

        /** Linhas "Título | Ano" viram itens (o ano é opcional). */
        applyBulk(si) {
            const s = this.sections[si];
            const room = limits.entries - s.entries.length;
            const added = [];
            for (const line of s.bulkText.split(/\r?\n/)) {
                const [title, ...rest] = line.split('|').map((x) => x.trim());
                if (!title) continue;
                const e = newEntry({ title });
                const year = rest.find((part) => /^\d{4}$/.test(part));
                if (year) e.release_year = year;
                added.push(e);
            }
            // aproveita a linha em branco do começo, se existir
            const blank = s.entries.length === 1 && !s.entries[0].title.trim() ? s.entries.splice(0, 1) : [];
            s.entries.push(...added.slice(0, room + blank.length));
            if (added.length > room + blank.length) this.formError = `Só cabem ${limits.entries} jogos por seção; alguns da lista ficaram de fora.`;
            s.bulk = false;
            s.bulkText = '';
        },

        // ---------- validação e envio ----------
        /** Monta o payload ignorando linhas totalmente vazias; devolve também o mapa para traduzir erros do servidor. */
        buildPayload() {
            const map = [];
            const sections = this.sections.map((s, si) => {
                map[si] = [];
                const entries = [];
                s.entries.forEach((e, ei) => {
                    if (!e.title.trim() && !String(e.release_year).trim() && !e.game_id) return; // linha vazia
                    map[si].push(ei);
                    entries.push({ title: e.title.trim(), release_year: String(e.release_year).trim() === '' ? null : Number(e.release_year), game_id: e.game_id });
                });
                return { name: s.name.trim(), entries };
            });
            return { payload: { name: this.form.name.trim(), description: this.form.description.trim(), sections, cover_focus: this.focus }, map };
        },

        validate({ payload }) {
            const e = {};
            if (!payload.name) e.name = 'Informe o nome da franquia.';
            if (!payload.sections.length) e.sections = 'Adicione pelo menos uma seção.';
            const names = new Set();
            payload.sections.forEach((s, si) => {
                if (!s.name) e[`sections.${si}.name`] = 'Dê um nome à seção.';
                else if (names.has(norm(s.name))) e[`sections.${si}.name`] = 'Já existe uma seção com esse nome.';
                names.add(norm(s.name));
                const seen = new Set();
                s.entries.forEach((x, ei) => {
                    if (!x.title) e[`sections.${si}.entries.${ei}.title`] = 'Informe o título.';
                    const sig = `${norm(x.title)}|${x.release_year ?? ''}`;
                    if (x.title && seen.has(sig)) e[`sections.${si}.entries.${ei}.title`] = 'Item duplicado nesta seção (mesmo título e ano).';
                    seen.add(sig);
                    if (x.release_year !== null && !(Number.isInteger(x.release_year) && x.release_year >= 1950 && x.release_year <= new Date().getFullYear() + 1)) {
                        e[`sections.${si}.entries.${ei}.release_year`] = 'Ano inválido.';
                    }
                });
            });
            return e;
        },

        /** Os erros do servidor usam o índice do payload; traduz de volta para o índice da tela. */
        translate(errors, map) {
            const out = {};
            for (const [k, v] of Object.entries(errors)) {
                const m = k.match(/^sections\.(\d+)\.entries\.(\d+)\.(\w+)$/);
                out[m ? `sections.${m[1]}.entries.${map[m[1]]?.[m[2]] ?? m[2]}.${m[3]}` : k] = v;
            }
            return out;
        },

        async save() {
            this.formError = '';
            const built = this.buildPayload();
            // o servidor usa o índice do payload; a validação local também, então traduz em ambos os casos
            const local = this.translate(this.validate(built), built.map);
            this.errors = local;
            if (Object.keys(local).length) return this.focusFirstError();

            this.loading = true;
            try {
                const res = this.editing
                    ? await api.put('/api/franchises/' + this.id, built.payload)
                    : await api.post('/api/franchises', built.payload);
                try {
                    await this.saveCover(res.data.id);
                } catch (err) {
                    // a franquia já foi salva; continua na edição dela para tentar a imagem de novo
                    if (!this.editing) history.replaceState(null, '', `${base}/franquias/${res.data.id}/editar`);
                    this.id = res.data.id;
                    this.editing = true;
                    this.errors = { cover: err.errors?.cover || err.message };
                    this.formError = 'A franquia foi salva, mas a imagem do card não: ' + (err.errors?.cover || err.message);
                    this.focusFirstError();
                    this.loading = false;
                    return;
                }
                this.saving = true;
                location.href = `${base}/franquias/${res.data.id}`;
            } catch (err) {
                if (err.status === 401) return (location.href = base + '/login');
                this.errors = this.translate(err.errors || {}, built.map);
                if (!Object.keys(this.errors).length) this.formError = err.message;
                this.focusFirstError();
                this.loading = false;
            }
        },

        focusFirstError() {
            this.$nextTick(() => {
                const el = this.formError ? this.$refs.formError : document.querySelector('.err:not([style*="display: none"])');
                el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },
    };
}
</script>
