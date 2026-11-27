<?php
/** @var array|null $me */
/** @var array|null $demo  {data, counts} fictícios da página de exemplo (nulo no backlog real) */
$demo = $demo ?? null;
$max = \App\Services\BacklogService::MAX_PLAYING;

/** Botões de prioridade de um jogo (usados no menu das capas e na linha da lista). */
$prioButtons = function (string $wrap, string $btn, bool $inMenu): void { ?>
<div class="<?= $wrap ?>" role="group" :aria-label="'Prioridade de ' + g.title">
    <template x-for="p in priorities" :key="p.v">
        <button type="button" <?= $inMenu ? "role=\"menuitemradio\" :aria-checked" : ":aria-pressed" ?>="g.priority === p.v" @click="setPriority(g, p.v)" :disabled="g.busy"
                class="<?= $btn ?> transition" :style="g.priority === p.v ? UI.priorityStyle(p.v) : ''" :class="g.priority === p.v ? '' : 'text-slate-400 hover:text-slate-50'" x-text="p.l"></button>
    </template>
</div>
<?php };
?>
<main class="page page-wide" x-data='backlog(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)' @click.window="closeMenu(false)">
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com <?= count($demo['data']) ?> jogos fictícios. Dá para começar a jogar, sortear, arrastar entre prioridades e voltar para a fila; nada é salvo.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <!-- ============ Destaque: a fila em números, o sorteio e o adicionar rápido ============ -->
    <header class="hero-box p-5 text-white sm:p-7">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="queueAll[0]?.cover_url" :src="queueAll[0]?.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/65 to-cyan-800/30"></div>
        </div>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="kicker-hero">A fila dos seus próximos jogos</p>
                <h1 class="mt-1 t-hero">Backlog</h1>
                <button type="button" @click="howOpen = !howOpen" :aria-expanded="howOpen" class="mt-1 text-sm font-semibold text-white/70 underline-offset-2 hover:text-white hover:underline">Como funciona?</button>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" x-show="queueAll.length > 1" @click="openRoll()" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black shadow transition hover:bg-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">🎲 O que eu jogo agora?</button>
                <?php if ($demo === null): ?>
                <a href="<?= e(url('/backlog/novo')) ?>" class="rounded-xl border border-white/25 bg-black/30 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">+ Adicionar com detalhes</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- números -->
        <dl x-show="ready" x-cloak class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4">
            <div>
                <dt class="text-xs text-white/55">Na mesa</dt>
                <dd class="mt-1 flex items-center gap-2"><span class="text-2xl font-black tabular-nums" x-text="playing.length + '/' + max"></span>
                    <span class="flex gap-1" aria-hidden="true"><template x-for="i in max" :key="i"><span class="h-2 w-3 rounded-full" :class="i <= playing.length ? 'bg-ok' : 'bg-white/20'"></span></template></span></dd>
            </div>
            <div><dt class="text-xs text-white/55">Na fila</dt><dd class="text-2xl font-black tabular-nums" x-text="queueAll.length"></dd><dd class="text-2xs text-white/55" x-text="counts.high ? counts.high + ' com prioridade alta' : ''"></dd></div>
            <div><dt class="text-xs text-white/55">Espera média</dt><dd class="text-2xl font-black tabular-nums" x-text="avgWaitLabel"></dd><dd class="truncate text-2xs text-white/55" x-text="oldest ? 'mais antigo: ' + oldest.title : ''"></dd></div>
            <div><dt class="text-xs text-white/55">No seu ritmo</dt><dd class="text-2xl font-black tabular-nums" x-text="forecast.big"></dd><dd class="text-2xs text-white/55" x-text="forecast.sub"></dd></div>
        </dl>

        <!-- adicionar rápido -->
        <?php if ($demo === null): ?>
        <form @submit.prevent="quickAdd" class="mt-6 flex flex-wrap items-start gap-2 rounded-2xl border border-white/10 bg-black/40 p-2.5 backdrop-blur" novalidate>
            <div class="min-w-[12rem] flex-1">
                <label class="sr-only" for="qa-title">Título do jogo</label>
                <input id="qa-title" type="text" maxlength="200" x-model="qa.title" placeholder="Quero jogar… (digite o título e aperte Enter)" class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm text-white placeholder-white/40 outline-none focus:ring-2 focus:ring-white/60" :class="qa.err && '!border-danger/70'" autocomplete="off">
            </div>
            <div class="w-36">
                <label class="sr-only" for="qa-platform">Sistema (opcional)</label>
                <input id="qa-platform" type="text" maxlength="60" list="dl-platforms" x-model="qa.platform" @input="suggestPlatforms(qa.platform)" placeholder="Sistema" class="w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm text-white placeholder-white/40 outline-none focus:ring-2 focus:ring-white/60" autocomplete="off">
                <datalist id="dl-platforms"><template x-for="s in platformSuggestions" :key="s"><option :value="s"></option></template></datalist>
            </div>
            <button type="submit" :disabled="qa.saving" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-black hover:bg-white/85 disabled:opacity-60" x-text="qa.saving ? '…' : 'Adicionar à fila'"></button>
            <p x-show="qa.err" x-cloak class="w-full text-sm text-red-300" x-text="qa.err"></p>
        </form>
        <?php endif; ?>
    </header>

    <!-- como funciona: recolhido por padrão -->
    <ol x-show="howOpen" x-cloak x-transition.opacity class="mt-4 grid gap-3 panel p-5 text-sm text-slate-300 sm:grid-cols-3">
        <li><span class="font-bold text-slate-50">1. Quero jogar.</span> Anote o jogo na fila e escolha a prioridade. Dá para arrastar a capa de um grupo de prioridade para outro.</li>
        <li><span class="font-bold text-slate-50">2. Jogando agora.</span> Até <strong class="text-slate-50"><?= $max ?> jogos ao mesmo tempo</strong>, “em cima da mesa” do seu painel.</li>
        <li><span class="font-bold text-slate-50">3. Terminou ou largou.</span> <em>Zerei!</em> leva o jogo para o catálogo e <em>Dropei</em> para os dropados. É assim que abre vaga.</li>
    </ol>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="error = ''; load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>
    <div x-show="!ready && !error" class="mt-6 h-72 animate-pulse rounded-3xl bg-slate-50/5" aria-label="Carregando"></div>

    <section x-show="ready && list.length === 0" x-cloak class="mt-8 empty">
        <p class="t-section">Seu backlog está vazio</p>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Anote aqui os jogos que você quer jogar. Quando começar um, ele vai para "Jogando agora"; quando zerar, vira um registro no catálogo.</p>
    </section>

    <template x-if="ready && list.length">
        <div>
            <!-- ============ A mesa: jogando agora, com as vagas livres ============ -->
            <section class="relative isolate mt-6 overflow-hidden rounded-3xl border border-slate-50/10 bg-hero p-4 sm:p-6" aria-labelledby="h-play">
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    <img x-show="playing[0]?.cover_url" :src="playing[0]?.cover_url" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
                    <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/60 to-ok/10"></div>
                </div>
                <div class="flex flex-wrap items-baseline justify-between gap-2 text-white">
                    <h2 id="h-play" class="flex items-baseline gap-3 text-xl font-bold">Jogando agora <span class="rounded-md bg-ok/20 px-2 py-0.5 text-sm font-bold text-ok" x-text="playing.length + '/' + max"></span></h2>
                    <p class="text-xs text-white/60" x-text="full ? 'Mesa cheia: zere, drope ou devolva um à fila para começar outro.' : (max - playing.length) + (max - playing.length === 1 ? ' vaga livre' : ' vagas livres')"></p>
                </div>

                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- em andamento -->
                    <template x-for="g in playing" :key="g.id">
                        <li class="flex min-w-0 flex-col rounded-2xl border bg-white/[.04] p-3 text-white" :class="stale(g) ? 'border-gold-400/50' : 'border-white/10'">
                            <div class="flex gap-3">
                                <span class="relative block aspect-[16/25] w-20 shrink-0 overflow-hidden rounded-md bg-black shadow-lg ring-1 ring-white/10">
                                    <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-xl font-bold text-white/80" x-text="UI.initials(g.title)"></span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 font-bold leading-snug" :title="g.title" x-text="g.title"></p>
                                    <p class="mt-0.5 truncate text-xs text-white/60" x-text="[g.platform, g.genre].filter(Boolean).join(' · ') || 'Sem sistema'"></p>
                                    <p class="mt-1.5 inline-block rounded-md bg-ok/20 px-2 py-0.5 text-2xs font-bold text-ok" x-text="g.days_playing === 0 ? 'Começou hoje' : 'Há ' + g.days_playing + (g.days_playing === 1 ? ' dia' : ' dias')"></p>
                                    <a x-show="g.franchise && !isDemo" :href="g.franchise && base + '/franquias/' + g.franchise.id" class="mt-1 block truncate text-2xs text-white/60 hover:text-white" x-text="g.franchise ? '🧩 ' + g.franchise.name : ''"></a>
                                </div>
                            </div>
                            <!-- parado há muito tempo, sem notas no diário -->
                            <div x-show="stale(g)" class="mt-3 rounded-lg bg-gold-400/15 px-2.5 py-2 text-xs text-gold-200">
                                <p x-text="'⏸ Parado há ' + staleDays(g) + ' dias sem notas no diário. Ainda está jogando?'"></p>
                                <p class="mt-1.5 flex gap-3 font-semibold">
                                    <button type="button" @click="queueBack(g)" class="underline hover:text-white">Devolver à fila</button>
                                    <a :href="isDemo ? null : dropUrl(g)" class="underline hover:text-white">Dropei</a>
                                </p>
                            </div>
                            <!-- a última nota do diário (ou a anotação do item) -->
                            <p x-show="!stale(g) && (g.last_note || g.note)" class="mt-3 line-clamp-3 border-l-2 border-ok/60 pl-2.5 text-xs italic leading-relaxed text-white/75"
                               x-text="g.last_note ? UI.fmtDate(g.last_note.date) + ' · ' + g.last_note.body : '“' + g.note + '”'"></p>
                            <div class="mt-auto flex items-center gap-2 pt-3">
                                <a :href="isDemo ? null : doneUrl(g)" :title="isDemo ? 'No exemplo, nada é salvo' : 'Registrar no catálogo'" class="flex-1 rounded-lg bg-ok px-3 py-2 text-center text-sm font-black text-black transition hover:opacity-90">Zerei!</a>
                                <a :href="isDemo ? null : dropUrl(g)" :title="isDemo ? 'No exemplo, nada é salvo' : 'Mover para os dropados'" class="rounded-lg border border-white/20 px-3 py-2 text-sm font-semibold text-white/85 transition hover:bg-white/10">Dropei</a>
                                <div class="relative">
                                    <button type="button" @click.stop="toggleMenu(g.id, $el)" :aria-expanded="menuId === g.id" aria-haspopup="menu" :aria-label="'Mais ações para ' + g.title" class="grid h-9 w-9 place-items-center rounded-lg border border-white/20 text-white/80 transition hover:bg-white/10">⋯</button>
                                    <div x-show="menuId === g.id" x-cloak @click.stop @keydown="menuKeys($event)" class="absolute bottom-11 right-0 z-pop w-48 overflow-hidden rounded-xl border border-slate-50/15 bg-slate-900 py-1 text-sm shadow-2xl" role="menu" :aria-label="'Ações de ' + g.title">
                                        <button type="button" role="menuitem" @click="queueBack(g); closeMenu()" class="block w-full px-4 py-2 text-left text-slate-200 hover:bg-slate-50/10 focus:bg-slate-50/10 focus:outline-none">↩ Voltar à fila</button>
                                        <?php if ($demo === null): ?>
                                        <a role="menuitem" :href="base + '/backlog/' + g.id + '/editar'" class="block px-4 py-2 text-slate-200 hover:bg-slate-50/10 focus:bg-slate-50/10 focus:outline-none">✎ Editar e diário</a>
                                        <button type="button" role="menuitem" @click="askRemove(g)" class="block w-full px-4 py-2 text-left text-danger hover:bg-danger/10 focus:bg-danger/10 focus:outline-none">🗑 Excluir…</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </template>
                    <!-- vagas livres: sugerem o próximo da fila (no celular viram uma linha só, abaixo) -->
                    <template x-for="(s, i) in freeSlots" :key="'free-' + i">
                        <li class="hidden min-h-[11rem] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-white/15 p-4 text-center text-white/70 sm:flex">
                            <span class="kicker-hero">Vaga livre</span>
                            <template x-if="s">
                                <div class="mt-3 flex w-full flex-col items-center">
                                    <span class="relative block aspect-[16/25] w-14 overflow-hidden rounded bg-black/60 ring-1 ring-white/10">
                                        <img x-show="s.cover_url" :src="s.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                        <span x-show="!s.cover_url" class="absolute inset-0 grid place-items-center text-sm font-bold text-white/70" x-text="UI.initials(s.title)"></span>
                                    </span>
                                    <p class="mt-2 text-xs text-white/50">Próximo da fila</p>
                                    <p class="line-clamp-2 text-sm font-bold text-white" x-text="s.title"></p>
                                    <button type="button" @click="start(s)" :disabled="s.busy" class="btn mt-3 btn-sm">▶ Começar</button>
                                </div>
                            </template>
                            <p x-show="!s" class="mt-2 text-xs text-white/50">Adicione jogos à fila para começar.</p>
                        </li>
                    </template>
                </ul>
                <!-- celular: as vagas livres numa linha só -->
                <div x-show="freeSlots.length" class="mt-3 flex items-center gap-3 rounded-xl border-2 border-dashed border-white/15 p-2.5 text-sm text-white/80 sm:hidden">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/10 text-lg" aria-hidden="true">＋</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold" x-text="freeSlots.length + (freeSlots.length === 1 ? ' vaga livre' : ' vagas livres')"></span>
                        <span x-show="freeSlots[0]" class="block truncate text-xs text-white/60" x-text="freeSlots[0] ? 'próximo: ' + freeSlots[0].title : ''"></span>
                    </span>
                    <button type="button" x-show="freeSlots[0]" @click="start(freeSlots[0])" class="btn shrink-0 btn-sm">▶ Começar</button>
                </div>
            </section>

            <!-- ============ Esquecidos: na fila há mais de 1 ano ============ -->
            <section x-show="forgotten.length" x-cloak class="mt-8 rounded-2xl border border-gold-400/30 bg-gold-400/5" aria-labelledby="h-forgot">
                <button type="button" @click="forgotOpen = !forgotOpen" :aria-expanded="forgotOpen" class="flex w-full items-center justify-between gap-3 p-4 text-left">
                    <span>
                        <span id="h-forgot" class="block font-bold text-slate-50" x-text="'🕸 Esquecidos · ' + forgotten.length + (forgotten.length === 1 ? ' jogo espera' : ' jogos esperam') + ' há mais de 1 ano'"></span>
                        <span class="block text-xs text-slate-400">Ainda quer jogar? Suba a prioridade ou tire da fila.</span>
                    </span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition" :class="forgotOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <ul x-show="forgotOpen" x-cloak class="divide-y divide-slate-50/10 border-t border-gold-400/20">
                    <template x-for="g in forgotten" :key="'f' + g.id">
                        <li class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                            <span class="relative block aspect-[16/25] w-8 shrink-0 overflow-hidden rounded bg-slate-950 ring-1 ring-black/40">
                                <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-slate-50" x-text="g.title"></span>
                                <span class="block text-xs text-gold-500" x-text="waitLabel(g) + ' · prioridade ' + UI.priorityLabel(g.priority).toLowerCase()"></span>
                            </span>
                            <button type="button" x-show="g.priority !== 'high'" @click="setPriority(g, 'high')" :disabled="g.busy" class="btn-ghost">↑ Subir para alta</button>
                            <button type="button" @click="askRemove(g)" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10">Tirar da fila</button>
                        </li>
                    </template>
                </ul>
            </section>

            <!-- ============ A fila ============ -->
            <section class="mt-10" aria-labelledby="h-want" x-show="queueAll.length" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="h-want" class="flex items-baseline gap-3 text-xl font-bold text-slate-50">Quero jogar <span class="rounded-md bg-slate-50/10 px-2 py-0.5 text-sm font-bold text-slate-300" x-text="queueAll.length"></span></h2>
                        <p x-show="oldest" class="mt-1 text-xs text-slate-400" x-text="queueSummary"></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="relative">
                            <span class="sr-only">Buscar na fila</span>
                            <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                            <input type="search" x-model="q" placeholder="Buscar…" class="w-36 rounded-lg border border-slate-50/10 bg-slate-900/70 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-brand-500">
                        </label>
                        <select x-show="queuePlatforms.length > 1" x-model="platform" aria-label="Sistema" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">Todos os sistemas</option>
                            <template x-for="p in queuePlatforms" :key="p"><option :value="p" x-text="p"></option></template>
                        </select>
                        <select x-model="sort" aria-label="Ordenar" class="rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-50 outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="priority">Por prioridade</option><option value="old">Esperando há mais tempo</option><option value="recent">Mais recentes</option><option value="title">Título A–Z</option>
                        </select>
                        <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-xs font-semibold" role="group" aria-label="Como mostrar">
                            <button type="button" @click="setView('grid')" :aria-pressed="view === 'grid'" class="rounded-md px-2.5 py-1.5 transition" :class="view === 'grid' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Capas</button>
                            <button type="button" @click="setView('list')" :aria-pressed="view === 'list'" class="rounded-md px-2.5 py-1.5 transition" :class="view === 'list' ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Lista</button>
                        </div>
                        <button type="button" @click="toggleSelecting()" :aria-pressed="selecting" class="rounded-lg border px-3 py-2 text-xs font-semibold transition" :class="selecting ? 'border-brand-400 bg-brand-500/15 text-slate-50' : 'border-slate-50/15 text-slate-300 hover:bg-slate-50/5'" x-text="selecting ? 'Cancelar seleção' : '☑ Selecionar'"></button>
                    </div>
                </div>

                <!-- filtro rápido por prioridade -->
                <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Filtrar por prioridade">
                    <button type="button" @click="prioFilter = ''" :aria-pressed="prioFilter === ''" class="rounded-full border px-3 py-1 text-xs font-semibold transition" :class="prioFilter === '' ? 'border-slate-50/60 bg-slate-50/10 text-slate-50' : 'border-slate-50/15 text-slate-400 hover:text-slate-50'" x-text="'Todas · ' + queueAll.length"></button>
                    <template x-for="p in priorities" :key="p.v">
                        <button type="button" @click="prioFilter = prioFilter === p.v ? '' : p.v" :aria-pressed="prioFilter === p.v" x-show="counts[p.v]" class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition"
                                :class="prioFilter === p.v ? 'border-slate-50/60 bg-slate-50/10 text-slate-50' : 'border-slate-50/15 text-slate-400 hover:text-slate-50'">
                            <span class="h-2.5 w-2.5 rounded-full" :style="UI.priorityStyle(p.v)"></span><span x-text="p.l + ' · ' + counts[p.v]"></span>
                        </button>
                    </template>
                </div>

                <p x-show="queue.length === 0" x-cloak class="mt-6 text-center text-slate-400">Nada na fila com esses filtros.</p>

                <!-- divisões: por prioridade (ou uma só nas outras ordenações). Arraste uma capa para outro grupo para mudar a prioridade. -->
                <template x-for="grp in queueGroups" :key="grp.key">
                    <div class="mt-6 rounded-2xl transition" :class="dropOver === grp.key && dragId ? 'bg-brand-500/10 ring-2 ring-brand-400 ring-offset-4 ring-offset-slate-950' : ''"
                         @dragover.prevent="grp.key !== 'all' && (dropOver = grp.key)" @dragleave.self="dropOver === grp.key && (dropOver = null)" @drop.prevent="dropPriority(grp.key)">
                        <h3 x-show="grp.label" class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-400">
                            <span class="h-2.5 w-2.5 rounded-full" :style="UI.priorityStyle(grp.key)"></span><span x-text="grp.label"></span><span class="font-semibold normal-case tracking-normal text-slate-500" x-text="'· ' + grp.games.length"></span>
                            <span x-show="dragId && dropOver === grp.key" class="font-semibold normal-case tracking-normal text-brand-400">solte para mudar a prioridade</span>
                        </h3>

                        <!-- capas -->
                        <ul x-show="view === 'grid'" class="grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            <template x-for="g in grp.games" :key="g.id">
                                <li class="flex min-w-0 flex-col" :draggable="sort === 'priority' && !selecting" @dragstart="dragId = g.id; $event.dataTransfer.effectAllowed = 'move'" @dragend="dragId = null; dropOver = null" :class="dragId === g.id && 'opacity-40'">
                                    <div class="case relative" :class="selecting && isSel(g) && 'ring-4 ring-brand-400'" @click="selecting && toggleSel(g)">
                                        <div class="case-band" :style="UI.bandStyle(g.platform)"><span x-text="g.platform || 'Sem sistema'"></span></div>
                                        <div class="case-art">
                                            <img x-show="g.cover_url" :src="g.cover_url" :alt="'Capa de ' + g.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover" draggable="false">
                                            <div x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center"><span class="text-4xl font-bold text-white/80" x-text="UI.initials(g.title)"></span></div>
                                            <span x-show="sort !== 'priority'" class="absolute left-2 top-2 rounded-md px-2 py-1 text-2xs font-black uppercase leading-none tracking-wide shadow ring-1 ring-black/40" :style="UI.priorityStyle(g.priority)" x-text="UI.priorityLabel(g.priority)"></span>
                                            <span x-show="isNext(g)" class="absolute right-2 top-2 rounded-md bg-ok px-2 py-1 text-2xs font-black uppercase leading-none text-black shadow">Próximo</span>
                                            <label x-show="selecting" class="absolute left-2 top-2 grid h-7 w-7 cursor-pointer place-items-center rounded-md bg-black/70 ring-1 ring-white/30" @click.stop>
                                                <input type="checkbox" :checked="isSel(g)" @change="toggleSel(g)" class="h-4 w-4" :aria-label="'Selecionar ' + g.title">
                                            </label>
                                            <div class="absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 px-2 py-1.5 text-center backdrop-blur-md">
                                                <p x-show="!g.cover_url" class="mb-1 truncate text-xs font-bold text-white" :title="g.title" x-text="g.title"></p>
                                                <p class="truncate text-2xs font-semibold text-white/75" :class="waitDays(g) >= 180 && '!text-gold-300'" x-text="waitLabel(g)"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <p x-show="g.franchise && !isDemo" class="mt-1.5 truncate text-2xs text-slate-400"><a :href="g.franchise && base + '/franquias/' + g.franchise.id" class="hover:text-brand-400" x-text="g.franchise ? '🧩 ' + g.franchise.name : ''"></a></p>
                                    <p x-show="g.note" class="mt-1 line-clamp-2 text-xs italic text-slate-400" x-text="'“' + g.note + '”'"></p>
                                    <!-- sempre no pé do cartão: os botões ficam alinhados na linha -->
                                    <div x-show="!selecting" class="mt-auto flex items-center gap-1.5 pt-2">
                                        <button type="button" @click="start(g)" :disabled="g.busy || full" :title="full ? 'Mesa cheia: zere, drope ou devolva um à fila para abrir vaga.' : 'Começar a jogar'" class="btn flex-1" x-text="full ? 'Mesa cheia' : '▶ Começar'"></button>
                                        <div class="relative">
                                            <button type="button" @click.stop="toggleMenu(g.id, $el)" :aria-expanded="menuId === g.id" aria-haspopup="menu" :aria-label="'Mais ações para ' + g.title" class="grid h-8 w-8 place-items-center rounded-lg border border-slate-50/15 text-slate-300 transition hover:bg-slate-50/10">⋯</button>
                                            <div x-show="menuId === g.id" x-cloak @click.stop @keydown="menuKeys($event)" class="absolute bottom-10 right-0 z-pop w-52 overflow-hidden rounded-xl border border-slate-50/15 bg-slate-900 py-1 text-sm shadow-2xl" role="menu" :aria-label="'Ações de ' + g.title">
                                                <p class="kicker px-4 pb-1 pt-2">Prioridade</p>
                                                <?php $prioButtons('flex gap-1 px-3 pb-2', 'flex-1 rounded-md py-1 text-xs font-bold bg-slate-50/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400', true); ?>
                                                <?php if ($demo === null): ?>
                                                <a role="menuitem" :href="base + '/backlog/' + g.id + '/editar'" class="block border-t border-slate-50/10 px-4 py-2 text-slate-200 hover:bg-slate-50/10 focus:bg-slate-50/10 focus:outline-none">✎ Editar</a>
                                                <button type="button" role="menuitem" @click="askRemove(g)" class="block w-full px-4 py-2 text-left text-danger hover:bg-danger/10 focus:bg-danger/10 focus:outline-none">🗑 Excluir…</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <!-- lista -->
                        <ul x-show="view === 'list'" class="divide-y divide-slate-50/10 overflow-hidden panel">
                            <template x-for="g in grp.games" :key="'l' + g.id">
                                <li class="flex items-center gap-3 px-3 py-2.5 sm:px-4" :class="selecting && isSel(g) && 'bg-brand-500/10'" :draggable="sort === 'priority' && !selecting" @dragstart="dragId = g.id" @dragend="dragId = null; dropOver = null">
                                    <input x-show="selecting" type="checkbox" :checked="isSel(g)" @change="toggleSel(g)" class="h-4 w-4 shrink-0" :aria-label="'Selecionar ' + g.title">
                                    <span class="relative block aspect-[16/25] w-9 shrink-0 overflow-hidden rounded bg-slate-950 ring-1 ring-black/40">
                                        <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" draggable="false">
                                        <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center text-2xs font-bold text-slate-400" x-text="UI.initials(g.title)"></span>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold text-slate-50"><span x-text="g.title"></span> <span x-show="isNext(g)" class="ml-1 rounded bg-ok px-1.5 py-0.5 text-2xs font-black uppercase text-black">Próximo</span></p>
                                        <p class="truncate text-xs text-slate-400">
                                            <span x-text="[g.platform, g.release_year].filter(Boolean).join(' · ') || 'Sem sistema'"></span>
                                            <span :class="waitDays(g) >= 180 && 'text-gold-500'" x-text="' · ' + waitLabel(g)"></span>
                                            <span x-show="g.franchise && !isDemo" x-text="g.franchise ? ' · 🧩 ' + g.franchise.name : ''"></span>
                                        </p>
                                    </div>
                                    <?php $prioButtons('hidden shrink-0 rounded-lg bg-slate-50/5 p-0.5 text-2xs font-bold sm:inline-flex', 'rounded-md px-2 py-1', false); ?>
                                    <button type="button" x-show="!selecting" @click="start(g)" :disabled="g.busy || full" :title="full ? 'Mesa cheia' : 'Começar a jogar'" :aria-label="'Começar ' + g.title" class="btn shrink-0 btn-sm">▶</button>
                                    <?php if ($demo === null): ?>
                                    <a x-show="!selecting" :href="base + '/backlog/' + g.id + '/editar'" class="hidden shrink-0 text-xs text-slate-400 hover:text-slate-50 sm:inline" :aria-label="'Editar ' + g.title">Editar</a>
                                    <?php endif; ?>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </section>
        </div>
    </template>

    <!-- barra da seleção -->
    <div x-show="selecting" x-cloak class="fixed inset-x-0 bottom-4 z-fab mx-auto flex w-fit max-w-[calc(100%-2rem)] flex-wrap items-center justify-center gap-2 rounded-2xl border border-slate-50/15 bg-slate-900/95 px-4 py-3 shadow-2xl backdrop-blur" role="region" aria-label="Ações para os selecionados">
        <span class="text-sm font-semibold text-slate-100" x-text="selected.length + (selected.length === 1 ? ' selecionado' : ' selecionados')"></span>
        <button type="button" @click="selectAllShown()" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-400 hover:bg-brand-500/10">Todos visíveis</button>
        <span class="text-xs text-slate-500">Prioridade:</span>
        <template x-for="p in priorities" :key="p.v">
            <button type="button" @click="bulkPriority(p.v)" :disabled="!selected.length || bulkBusy" class="rounded-lg px-2.5 py-1.5 text-xs font-bold disabled:opacity-40" :style="UI.priorityStyle(p.v)" x-text="p.l"></button>
        </template>
        <button type="button" @click="askRemoveMany()" :disabled="!selected.length || bulkBusy" class="rounded-lg border border-danger/40 px-2.5 py-1.5 text-xs font-semibold text-danger hover:bg-danger/10 disabled:opacity-40">Excluir…</button>
    </div>

    <!-- sorteio: o que eu jogo agora? -->
    <div x-show="roll.open" x-cloak class="modal-back" @click.self="closeRoll()" @keydown.escape.window="roll.open && closeRoll()" role="dialog" aria-modal="true" aria-labelledby="roll-title">
        <div x-ref="rollBox" @keydown.tab="trapFocus($event, $refs.rollBox)" class="w-full max-w-sm rounded-3xl border border-slate-50/15 bg-hero p-6 text-center text-white shadow-2xl">
            <h2 id="roll-title" class="kicker-hero">🎲 O que eu jogo agora?</h2>
            <p class="mt-1 text-xs text-white/45">Prioridade alta e quem espera há mais tempo têm mais chance.</p>
            <div class="mx-auto mt-5 w-40">
                <span class="relative block aspect-[16/25] overflow-hidden rounded-xl bg-slate-900 shadow-2xl ring-2 transition" :class="roll.spinning ? 'ring-white/20 blur-[1px]' : 'ring-gold-400 shadow-[0_0_40px_rgba(251,191,36,.35)]'">
                    <img x-show="roll.shown?.cover_url" :src="roll.shown?.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                    <span x-show="roll.shown && !roll.shown.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-3xl font-bold text-white/80" x-text="roll.shown ? UI.initials(roll.shown.title) : ''"></span>
                </span>
            </div>
            <p class="mt-4 min-h-[3.5rem] text-xl font-black leading-tight" aria-live="polite" x-text="roll.spinning ? '…' : roll.pick?.title"></p>
            <p x-show="!roll.spinning && roll.pick" class="text-xs text-white/60" x-text="roll.pick ? UI.priorityLabel(roll.pick.priority) + ' · ' + waitLabel(roll.pick).toLowerCase() : ''"></p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <button type="button" x-ref="rollStart" @click="startRolled()" :disabled="roll.spinning || full" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-black hover:bg-white/85 disabled:opacity-50" x-text="full ? 'Mesa cheia' : '▶ Começar este'"></button>
                <button type="button" @click="spin()" :disabled="roll.spinning" class="rounded-xl border border-white/25 px-4 py-2 text-sm font-semibold hover:bg-white/10 disabled:opacity-50">Sortear de novo</button>
                <button type="button" @click="closeRoll()" class="rounded-xl px-3 py-2 text-sm text-white/60 hover:text-white">Fechar</button>
            </div>
        </div>
    </div>

    <!-- confirmação de exclusão (um ou vários) -->
    <div x-show="removing" x-cloak class="modal-back" role="alertdialog" aria-modal="true" aria-labelledby="bl-del" @click.self="cancelRemove()" @keydown.escape.window="removing && cancelRemove()">
        <div x-ref="delBox" @keydown.tab="trapFocus($event, $refs.delBox)" class="w-full max-w-sm rounded-2xl border border-slate-50/15 bg-slate-900 p-6 shadow-2xl">
            <h2 id="bl-del" class="t-section" x-text="removing?.many ? 'Tirar ' + removing.many.length + ' jogos do backlog?' : 'Tirar do backlog?'"></h2>
            <p class="mt-2 text-sm text-slate-300">
                <template x-if="!removing?.many"><span><strong class="text-slate-50" x-text="removing?.title"></strong> sai da lista, junto com as notas e screenshots dele.</span></template>
                <template x-if="removing?.many"><span>Eles saem da lista, junto com as notas e screenshots de cada um.</span></template>
            </p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" x-ref="delCancel" @click="cancelRemove()" class="btn-ghost">Cancelar</button>
                <button type="button" @click="removing?.many ? removeMany() : remove(removing)" :disabled="bulkBusy" class="rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60" x-text="bulkBusy ? 'Excluindo…' : 'Excluir'"></button>
            </div>
        </div>
    </div>
</main>

<script>
function backlog(demo) {
    const base = window.APP.base;
    const RANK = { high: 3, medium: 2, low: 1 };
    const timers = {};
    const today = demo ? Date.UTC(2026, 9, 18) : Date.now();
    const daysSince = (s) => (s ? Math.max(0, Math.floor((today - Date.UTC(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10))) / 86400000)) : 0);
    const since = (g) => g.queued_at ?? g.added_at;
    const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    return {
        isDemo: !!demo, base,
        list: [], ready: false, error: '', q: '', platform: '', sort: 'priority', view: 'grid', prioFilter: '',
        menuId: null, menuBtn: null, removing: null, removeOpener: null, howOpen: false, forgotOpen: false,
        qa: { title: '', platform: '', saving: false, err: '' }, platformSuggestions: [],
        priorities: [{ v: 'high', l: 'Alta' }, { v: 'medium', l: 'Média' }, { v: 'low', l: 'Baixa' }],
        // a fila ordenada fica guardada e só é refeita quando a lista muda (antes era recalculada a cada cartão)
        queueAll: [], counts: { high: 0, medium: 0, low: 0 },
        dragId: null, dropOver: null,
        selecting: false, selected: [], bulkBusy: false,
        roll: { open: false, spinning: false, pick: null, shown: null, opener: null },
        pace: null,              // jogos zerados por mês (últimos 12 meses), para a previsão

        max: <?= $max ?>,
        get full() { return this.playing.length >= this.max; },
        /** Jogando agora: quem recebeu uma anotação no diário mais recentemente vem primeiro; sem nota, do que começou há menos tempo. */
        get playing() {
            return this.list.filter((g) => g.status === 'playing').sort((a, b) =>
                (b.last_note ? (b.last_note.id ?? 1) : 0) - (a.last_note ? (a.last_note.id ?? 1) : 0)
                || (b.last_note?.date ?? '').localeCompare(a.last_note?.date ?? '')
                || b.started_at.localeCompare(a.started_at));
        },
        /** Refaz a fila ordenada (prioridade e depois data de entrada) e as contagens. */
        resort() {
            const q = this.list.filter((g) => g.status === 'want')
                .sort((a, b) => RANK[b.priority] - RANK[a.priority] || since(a).localeCompare(since(b)) || a.id - b.id);
            this.queueAll = q;
            this.counts = { high: 0, medium: 0, low: 0 };
            for (const g of q) this.counts[g.priority]++;
            this.selected = this.selected.filter((id) => q.some((g) => g.id === id));
        },
        get queue() {
            const q = this.q.trim().toLowerCase();
            const rows = this.queueAll.filter((g) => (!q || g.title.toLowerCase().includes(q)) && (!this.platform || g.platform === this.platform) && (!this.prioFilter || g.priority === this.prioFilter));
            const cmp = {
                priority: null,
                old: (a, b) => since(a).localeCompare(since(b)) || a.id - b.id,
                recent: (a, b) => since(b).localeCompare(since(a)) || b.id - a.id,
                title: (a, b) => a.title.localeCompare(b.title, 'pt', { sensitivity: 'base' }),
            }[this.sort];
            return cmp ? [...rows].sort(cmp) : rows;
        },
        /** Por prioridade: três divisões; nas outras ordenações, uma só. */
        get queueGroups() {
            const rows = this.queue;
            if (this.sort !== 'priority') return [{ key: 'all', label: '', games: rows }];
            const names = { high: 'Prioridade alta', medium: 'Prioridade média', low: 'Prioridade baixa' };
            // durante o arraste, os grupos vazios aparecem também, para dar onde soltar
            return ['high', 'medium', 'low'].map((p) => ({ key: p, label: names[p], games: rows.filter((g) => g.priority === p) })).filter((g) => g.games.length || (this.dragId && !this.prioFilter));
        },
        get queuePlatforms() { return [...new Set(this.queueAll.map((g) => g.platform).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'pt')); },
        /** Vagas livres: cada uma sugere o próximo da fila que ainda não foi sugerido. */
        get freeSlots() {
            const n = Math.max(0, this.max - this.playing.length);
            return Array.from({ length: n }, (_, i) => this.queueAll[i] ?? null);
        },
        isNext(g) { return !this.full && this.queueAll[0]?.id === g.id; },
        waitDays(g) { return daysSince(since(g)); },
        waitLabel(g) {
            const d = this.waitDays(g);
            if (d === 0) return 'Entrou hoje';
            if (d < 30) return 'Na fila há ' + d + (d === 1 ? ' dia' : ' dias');
            const m = Math.round(d / 30.4);
            return d < 365 ? 'Esperando há ' + m + (m === 1 ? ' mês' : ' meses') : 'Esperando há ' + (Math.floor(d / 365) === 1 ? '1 ano' : Math.floor(d / 365) + ' anos');
        },
        get oldest() { return this.queueAll.reduce((o, g) => (!o || this.waitDays(g) > this.waitDays(o) ? g : o), null); },
        get queueSummary() {
            const o = this.oldest; if (!o) return '';
            const high = this.counts.high;
            return (high ? high + ' com prioridade alta · ' : '') + 'quem espera há mais tempo: ' + o.title + ' (' + this.waitLabel(o).replace(/^Na fila há |^Esperando há /, '') + ')';
        },
        get avgWaitLabel() {
            if (!this.queueAll.length) return '—';
            const d = Math.round(this.queueAll.reduce((s, g) => s + this.waitDays(g), 0) / this.queueAll.length);
            return d < 30 ? d + (d === 1 ? ' dia' : ' dias') : d < 365 ? Math.round(d / 30.4) + ' meses' : (d / 365).toFixed(1).replace('.', ',') + ' anos';
        },
        /** No seu ritmo (jogos zerados por mês nos últimos 12 meses), quando a fila atual acaba. */
        get forecast() {
            const n = this.queueAll.length;
            if (!n) return { big: '—', sub: 'a fila está vazia' };
            if (!this.pace) return { big: '—', sub: this.pace === 0 ? 'zere jogos para calcular' : 'calculando…' };
            const m = n / this.pace;
            const big = m < 1 ? '< 1 mês' : m < 24 ? '~' + Math.round(m) + (Math.round(m) === 1 ? ' mês' : ' meses') : '~' + (m / 12).toFixed(1).replace('.', ',') + ' anos';
            return { big, sub: 'para zerar a fila (' + this.pace.toFixed(1).replace('.', ',') + ' por mês)' };
        },
        /** "Esquecidos": na fila há mais de 1 ano. */
        get forgotten() { return this.queueAll.filter((g) => this.waitDays(g) >= 365).sort((a, b) => this.waitDays(b) - this.waitDays(a)); },
        /** Jogando há mais de 45 dias e sem nota no diário nos últimos 45 dias. */
        staleDays(g) { return g.last_note ? daysSince(g.last_note.date) : (g.days_playing ?? 0); },
        stale(g) { return (g.days_playing ?? 0) > 45 && this.staleDays(g) > 45; },
        setView(v) { this.view = v; try { localStorage.setItem('mgl-backlog-view', v); } catch { /* sem storage */ } },

        /** "Zerei!": abre o cadastro de jogo já preenchido; ao salvar, o jogo sai do backlog. */
        doneUrl(g) { return `${base}/jogos/novo?${this.params(g)}`; },
        /** "Dropei": abre o cadastro de dropados já preenchido; ao salvar, sai do backlog. */
        dropUrl(g) { return `${base}/dropados/novo?${this.params(g)}`; },
        params(g) {
            const p = new URLSearchParams({ title: g.title, from_backlog: g.id });
            if (g.platform) p.set('platform', g.platform);
            if (g.genre) p.set('genre', g.genre);
            if (g.release_year) p.set('release_year', g.release_year);
            return p;
        },

        async init() {
            try { const v = localStorage.getItem('mgl-backlog-view'); if (v === 'list' || v === 'grid') this.view = v; } catch { /* padrão */ }
            await this.load();
            this.loadPace();
            // vindo da busca do menu ("Sortear o próximo do backlog")
            if (this.ready && new URLSearchParams(location.search).has('sortear') && this.queueAll.length > 1) this.openRoll();
        },
        async load() {
            this.error = '';
            try {
                const r = demo ?? await api.get('/api/backlog');
                this.list = r.data.map((g) => ({ last_note: null, franchise: null, ...g, busy: false }));
                this.resort();
                this.ready = true;
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },
        /** Ritmo: jogos zerados nos últimos 12 meses (este ano + o fim do ano passado). */
        async loadPace() {
            if (demo) { this.pace = 2.5; return; }
            try {
                const now = new Date(), y = now.getFullYear(), m = now.getMonth();
                const [a, b] = await Promise.all([api.get('/api/me/stats?year=' + y), api.get('/api/me/stats?year=' + (y - 1))]);
                const games = a.months.slice(0, m + 1).reduce((s, x) => s + x.games, 0) + b.months.slice(m + 1).reduce((s, x) => s + x.games, 0);
                this.pace = games / 12;
            } catch { this.pace = 0; }
        },

        replace(g, fresh) { Object.assign(g, fresh, { busy: false }); },
        async act(g, fn, local) {
            if (demo) { local?.(); this.resort(); return; }
            g.busy = true;
            try { this.replace(g, (await fn()).data); this.resort(); } catch (e) { this.error = e.message; } finally { g.busy = false; }
        },
        start(g) {
            if (this.full) { this.error = 'Você já joga ' + this.max + ' jogos ao mesmo tempo. Zere, drope ou devolva um à fila antes de começar outro.'; return; }
            this.error = '';
            const day = new Date(today).toISOString().slice(0, 10);
            return this.act(g, () => api.post(`/api/backlog/${g.id}/start`, {}), () => { g.status = 'playing'; g.started_at = day; g.days_playing = 0; });
        },
        queueBack(g) { return this.act(g, () => api.post(`/api/backlog/${g.id}/queue`, {}), () => { g.status = 'want'; g.started_at = null; g.days_playing = null; g.queued_at = new Date(today).toISOString().slice(0, 10); }); },
        setPriority(g, p) { if (g.priority !== p) return this.act(g, () => api.put(`/api/backlog/${g.id}/priority`, { priority: p }), () => { g.priority = p; }); },

        // ---------- menu ⋯ (teclado: o foco entra no menu, ↑↓ navegam, Esc fecha e volta ao botão) ----------
        toggleMenu(id, btn) {
            if (this.menuId === id) return this.closeMenu();
            this.menuId = id; this.menuBtn = btn;
            this.$nextTick(() => btn.nextElementSibling?.querySelector('[role^="menuitem"]:not([disabled])')?.focus());
        },
        closeMenu(refocus = true) {
            if (this.menuId === null) return;
            this.menuId = null;
            if (refocus) this.menuBtn?.focus();
        },
        menuKeys(e) {
            const items = [...e.currentTarget.querySelectorAll('[role^="menuitem"]:not([disabled])')];
            const i = items.indexOf(document.activeElement);
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { e.preventDefault(); items[(i + 1) % items.length]?.focus(); }
            else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { e.preventDefault(); items[(i - 1 + items.length) % items.length]?.focus(); }
            else if (e.key === 'Home') { e.preventDefault(); items[0]?.focus(); }
            else if (e.key === 'End') { e.preventDefault(); items[items.length - 1]?.focus(); }
            else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); this.closeMenu(); }
            else if (e.key === 'Tab') this.closeMenu(false);
        },

        // ---------- excluir (um ou vários) ----------
        askRemove(g) { this.closeMenu(false); this.removeOpener = document.activeElement; this.removing = g; this.$nextTick(() => this.$refs.delCancel?.focus()); },
        askRemoveMany() { this.removeOpener = document.activeElement; this.removing = { many: [...this.selected] }; this.$nextTick(() => this.$refs.delCancel?.focus()); },
        cancelRemove() { this.removing = null; this.$nextTick(() => this.removeOpener?.focus?.()); },
        async remove(g) {
            try {
                if (!demo) await api.del('/api/backlog/' + g.id);
                this.list = this.list.filter((x) => x.id !== g.id); this.resort();
            } catch (e) { this.error = e.message; }
            this.removing = null;
        },
        async removeMany() {
            const ids = this.removing?.many ?? [];
            this.bulkBusy = true;
            const failed = [];
            for (const id of ids) {
                try { if (!demo) await api.del('/api/backlog/' + id); this.list = this.list.filter((x) => x.id !== id); }
                catch { failed.push(id); }
            }
            this.resort();
            this.bulkBusy = false;
            this.removing = null;
            this.selected = failed;
            if (failed.length) this.error = failed.length + ' não puderam ser excluídos. Tente de novo.';
            else this.selecting = false;
        },

        // ---------- selecionar vários ----------
        toggleSelecting() { this.selecting = !this.selecting; this.selected = []; this.closeMenu(false); },
        isSel(g) { return this.selected.includes(g.id); },
        toggleSel(g) { this.selected = this.isSel(g) ? this.selected.filter((x) => x !== g.id) : [...this.selected, g.id]; },
        selectAllShown() { this.selected = [...new Set([...this.selected, ...this.queue.map((g) => g.id)])]; },
        async bulkPriority(p) {
            this.bulkBusy = true;
            for (const id of this.selected) {
                const g = this.list.find((x) => x.id === id);
                if (g && g.priority !== p) await this.setPriority(g, p);
            }
            this.bulkBusy = false;
        },

        // ---------- arrastar entre prioridades ----------
        dropPriority(key) {
            const g = this.list.find((x) => x.id === this.dragId);
            this.dragId = null; this.dropOver = null;
            if (g && key !== 'all' && RANK[key]) this.setPriority(g, key);
        },

        // ---------- sorteio ----------
        /** peso: prioridade (alta pesa mais) + tempo de espera (até +6, a cada 2 meses) */
        weightedPick() {
            const pool = this.queueAll.map((g) => ({ g, w: RANK[g.priority] * 2 + Math.min(6, this.waitDays(g) / 60) }));
            let r = Math.random() * pool.reduce((s, x) => s + x.w, 0);
            for (const x of pool) { r -= x.w; if (r <= 0) return x.g; }
            return pool[pool.length - 1]?.g ?? null;
        },
        openRoll() { this.roll.opener = document.activeElement; this.roll.open = true; this.spin(); },
        spin() {
            const pick = this.weightedPick();
            this.roll.pick = pick;
            if (reduced() || this.queueAll.length < 2) { this.roll.shown = pick; this.roll.spinning = false; this.$nextTick(() => this.$refs.rollStart?.focus()); return; }
            this.roll.spinning = true;
            let n = 0;
            const tick = () => {
                this.roll.shown = this.queueAll[Math.floor(Math.random() * this.queueAll.length)];
                if (++n < 16) setTimeout(tick, 60 + n * 8);
                else { this.roll.shown = pick; this.roll.spinning = false; this.$nextTick(() => this.$refs.rollStart?.focus()); }
            };
            tick();
        },
        closeRoll() { this.roll.open = false; this.$nextTick(() => this.roll.opener?.focus?.()); },
        startRolled() { const g = this.roll.pick; this.closeRoll(); if (g) this.start(g); },
        /** Tab e Shift+Tab ficam circulando dentro da janela aberta */
        trapFocus(e, box) {
            const els = [...box.querySelectorAll('button:not([disabled]), a[href]')].filter((el) => el.offsetParent !== null);
            if (!els.length) return;
            const first = els[0], last = els[els.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },

        suggestPlatforms(q) {
            clearTimeout(timers.p); q = (q || '').trim();
            if (!q) { this.platformSuggestions = []; return; }
            timers.p = setTimeout(async () => { try { this.platformSuggestions = (await api.get('/api/lookups/platforms?q=' + encodeURIComponent(q))).data; } catch { /* sem sugestões */ } }, 200);
        },
        async quickAdd() {
            const qa = this.qa; qa.err = '';
            if (!qa.title.trim()) { qa.err = 'Digite o título do jogo.'; return; }
            qa.saving = true;
            try {
                const r = await api.post('/api/backlog', { title: qa.title.trim(), platform: qa.platform.trim(), status: 'want' });
                this.list.unshift({ last_note: null, franchise: null, ...r.data, busy: false });
                this.resort();
                qa.title = ''; // mantém o sistema: facilita adicionar vários seguidos
            } catch (e) { qa.err = Object.values(e.errors || {})[0] || e.message; } finally { qa.saving = false; }
        },
    };
}
</script>
