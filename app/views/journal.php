<?php
/** @var array|null $me */
/** @var bool $demo  página de exemplo (dados fictícios, sem escrita) */
$demo = !empty($demo);
?>
<main class="page page-normal" x-data="journal(<?= $demo ? 'true' : 'false' ?>)">
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo): ?>
    <?php ob_start(); ?> com uma vida gamer fictícia. Filtre por tipo, clique em uma pessoa ou siga a história de um jogo só.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="t-page">Diário</h1>
            <p class="page-sub max-w-2xl ">Tudo o que aconteceu na sua vida gamer, dia a dia: o que entrou na fila, o que você começou, zerou ou largou, e o que escreveu pelo caminho.</p>
        </div>
        <?php if (!$demo): ?>
        <button type="button" x-show="!writing" @click="startWrite()" class="btn flex items-center gap-2">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Escrever
        </button>
        <?php endif; ?>
    </header>

    <?php if (!$demo): ?>
    <!-- ============ Escrever ============ -->
    <form x-show="writing" x-cloak @submit.prevent="save()" class="mt-6 space-y-4 rounded-2xl border border-brand-400/30 bg-slate-900/80 p-5" aria-labelledby="h-write">
        <h2 id="h-write" class="text-base font-semibold text-slate-50" x-text="draft.id ? 'Editar registro' : 'Novo registro'"></h2>

        <div x-show="!draft.id">
            <span class="lbl">Sobre o quê?</span>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="draft.ref = null; picking = false" :aria-pressed="draft.ref === null && !picking"
                        class="rounded-full border px-3.5 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                        :class="draft.ref === null && !picking ? 'border-brand-400 bg-brand-500/15 text-slate-50' : 'border-slate-50/10 text-slate-400 hover:text-slate-100'">Só o dia, sem jogo</button>
                <template x-for="t in quickTargets" :key="t.ref">
                    <button type="button" @click="draft.ref = t.ref; picking = false" :aria-pressed="draft.ref === t.ref"
                            class="max-w-[16rem] truncate rounded-full border px-3.5 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                            :class="draft.ref === t.ref ? 'border-brand-400 bg-brand-500/15 text-slate-50' : 'border-slate-50/10 text-slate-400 hover:text-slate-100'" x-text="t.title"></button>
                </template>
                <button type="button" @click="picking = true; $nextTick(() => $refs.pick?.focus())" :aria-pressed="picking"
                        class="rounded-full border px-3.5 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                        :class="picking ? 'border-brand-400 bg-brand-500/15 text-slate-50' : 'border-slate-50/10 text-slate-400 hover:text-slate-100'">Outro jogo…</button>
            </div>
            <div x-show="picking" x-cloak class="mt-3">
                <label for="j-pick" class="sr-only">Buscar jogo</label>
                <input id="j-pick" x-ref="pick" type="search" x-model="pickQ" autocomplete="off" placeholder="Digite o nome do jogo (catálogo ou backlog)" class="field">
                <ul class="mt-2 grid gap-1 sm:grid-cols-2">
                    <template x-for="t in pickResults" :key="t.ref">
                        <li>
                            <button type="button" @click="draft.ref = t.ref; picking = false; pickQ = ''" class="flex w-full items-baseline justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-200 hover:bg-slate-50/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                <span class="truncate font-semibold" x-text="t.title"></span>
                                <span class="shrink-0 text-xs text-slate-500" x-text="(t.platform || 'sem sistema') + ' · ' + stateLabel[t.state]"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                <p x-show="pickQ.trim() && !pickResults.length" class="mt-2 text-sm text-slate-500">Nenhum jogo com esse nome no catálogo ou no backlog.</p>
            </div>
        </div>
        <p class="text-sm text-slate-300">
            <span x-show="draft.ref">Nota no diário de <strong class="text-slate-50" x-text="games[draft.ref]?.title"></strong>. Ela também aparece na página do jogo.</span>
            <span x-show="!draft.ref">Um registro do dia, sem ligação com um jogo.</span>
        </p>

        <div>
            <label for="j-date" class="lbl">Data</label>
            <input id="j-date" type="date" :max="today" x-model="draft.note_date" class="field !w-auto [color-scheme:dark]">
        </div>
        <div>
            <label for="j-body" class="lbl">Texto</label>
            <textarea id="j-body" rows="5" maxlength="2000" x-model="draft.body" x-ref="body" class="field" placeholder="Hoje eu…"></textarea>
            <p class="mt-1 text-right text-xs text-slate-500"><span x-text="draft.body.length"></span>/2000</p>
        </div>
        <div x-show="draft.ref">
            <span class="lbl">Imagens <span class="font-normal text-slate-500">(opcional · JPEG, PNG ou WebP, até 8 MB cada)</span></span>
            <div class="flex flex-wrap items-center gap-2">
                <template x-for="(f, i) in files" :key="f.key">
                    <span class="relative block h-16 w-24 overflow-hidden rounded-md ring-1 ring-slate-50/15">
                        <img :src="f.preview" alt="" class="h-full w-full object-cover">
                        <button type="button" @click="dropFile(i)" :aria-label="'Tirar ' + f.file.name" class="absolute right-0.5 top-0.5 grid h-5 w-5 place-items-center rounded-full bg-black/70 text-xs text-white hover:bg-black">✕</button>
                    </span>
                </template>
                <button type="button" @click="$refs.jFiles.click()" class="grid h-16 w-24 place-items-center rounded-md border border-dashed border-slate-50/20 text-xs text-slate-400 hover:border-brand-400 hover:text-slate-100">+ Imagem</button>
                <input x-ref="jFiles" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" @change="addFiles($event.target.files); $event.target.value = ''">
            </div>
        </div>
        <p x-show="formError" x-cloak role="alert" class="err" x-text="formError"></p>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" :disabled="busy || !draft.body.trim() || picking" class="btn" x-text="busy ? (progress || 'Salvando…') : (draft.id ? 'Salvar' : 'Registrar')"></button>
            <button type="button" @click="cancelWrite()" :disabled="busy" class="btn-ghost">Cancelar</button>
        </div>
    </form>
    <?php endif; ?>

    <p x-show="!ready && !error" class="mt-8 text-sm text-slate-400">Carregando…</p>
    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error" x-text="error"></div>

    <!-- Diário vazio -->
    <section x-show="ready && !events.length" x-cloak class="mt-8 empty">
        <p class="t-section">Seu diário começa aqui</p>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Cada jogo que você colocar na fila, começar, zerar ou largar aparece nesta página sozinho. E você pode escrever o que quiser em qualquer dia.</p>
    </section>

    <div x-show="ready && events.length" x-cloak>
        <!-- ============ Filtros ============ -->
        <div class="mt-6 space-y-3">
            <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por tipo">
                <template x-for="t in typeTabs" :key="t.v">
                    <button type="button" @click="type = t.v" :aria-pressed="type === t.v" x-show="t.v === 'all' || counts[t.v]"
                            class="flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                            :class="type === t.v ? 'border-brand-400 bg-brand-500/15 text-slate-50' : 'border-slate-50/10 text-slate-400 hover:text-slate-100'">
                        <span x-show="t.v !== 'all'" class="h-2 w-2 rounded-full" :class="META[t.v]?.dot" aria-hidden="true"></span>
                        <span x-text="t.l"></span>
                        <span class="xp-ui text-xs font-normal opacity-70" x-text="t.v === 'all' ? events.length : counts[t.v]"></span>
                    </button>
                </template>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <label class="relative block min-w-[14rem] flex-1">
                    <span class="sr-only">Buscar no diário</span>
                    <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400') ?>
                    <input type="search" x-model.debounce.250ms="q" autocomplete="off" placeholder="Buscar jogo ou texto" class="field !py-2 !pl-9">
                </label>
                <label x-show="people.length" class="flex items-center gap-2 text-sm text-slate-400">
                    <span>Com</span>
                    <select x-model="person" class="field !w-auto !py-2">
                        <option value="">qualquer pessoa</option>
                        <template x-for="p in people" :key="p.name"><option :value="p.name" x-text="p.name"></option></template>
                    </select>
                </label>
                <button type="button" @click="oldestFirst = !oldestFirst" class="btn-ghost !py-2" :aria-pressed="oldestFirst"
                        x-text="oldestFirst ? 'Do começo para hoje' : 'De hoje para o começo'"></button>
            </div>
        </div>

        <!-- História de um jogo só -->
        <div x-show="focusRef" x-cloak class="mt-4 flex flex-wrap items-center gap-4 rounded-2xl border border-brand-400/40 bg-brand-500/10 p-4">
            <span class="relative block aspect-[16/25] w-12 shrink-0 overflow-hidden rounded bg-slate-950 ring-1 ring-black/40">
                <img x-show="games[focusRef]?.cover_url" :src="games[focusRef]?.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                <span x-show="!games[focusRef]?.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-sm font-bold text-white/80" x-text="UI.initials(games[focusRef]?.title ?? '')"></span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium uppercase tracking-wider text-brand-400">A história de um jogo</p>
                <p class="truncate text-lg font-bold text-slate-50" x-text="games[focusRef]?.title"></p>
                <p class="text-sm text-slate-300" x-text="arc"></p>
            </div>
            <button type="button" @click="focusRef = null" class="btn-ghost !py-2">Ver o diário inteiro</button>
        </div>

        <p class="mt-4 text-sm text-slate-400" aria-live="polite">
            <span x-show="!filtered.length">Nada encontrado com esses filtros.</span>
            <span x-show="filtered.length && filtered.length !== events.length" class="xp-ui"><span x-text="filtered.length"></span> de <span x-text="events.length"></span> registros</span>
        </p>

        <div class="mt-2 grid gap-8 lg:grid-cols-[minmax(0,1fr)_12rem]">
            <!-- ============ A linha do tempo ============ -->
            <div class="min-w-0">
                <template x-for="m in months" :key="m.key">
                    <section :id="'m-' + m.key" class="mb-10 scroll-mt-4" :aria-label="m.label + ' de ' + m.year">
                        <h2 class="sticky top-0 z-10 -mx-2 flex flex-wrap items-baseline gap-x-3 gap-y-1 bg-slate-950/85 px-2 py-3 backdrop-blur">
                            <span class="text-2xl font-black tracking-tight text-slate-50" x-text="m.label"></span>
                            <span class="text-2xl font-light text-slate-500" x-text="m.year"></span>
                            <span class="xp-ui ml-auto text-xs text-slate-400" x-text="m.summary"></span>
                        </h2>

                        <ol class="mt-2 space-y-6">
                            <template x-for="d in m.days" :key="d.date">
                                <li>
                                    <p x-show="d.gap" class="xp-ui mb-6 flex items-center gap-3 text-xs text-slate-500" aria-hidden="true">
                                        <span class="h-px flex-1 bg-slate-50/10"></span><span x-text="d.gap + ' dias sem registros'"></span><span class="h-px flex-1 bg-slate-50/10"></span>
                                    </p>
                                    <div class="grid grid-cols-[3rem_minmax(0,1fr)] gap-3 sm:grid-cols-[4.5rem_minmax(0,1fr)] sm:gap-5">
                                        <time :datetime="d.date" class="pt-1 text-right" :title="longDate(d.date)">
                                            <span class="block text-3xl font-black leading-none tabular-nums text-slate-50 sm:text-4xl" x-text="d.day"></span>
                                            <span class="kicker mt-1 block" x-text="d.weekday"></span>
                                        </time>

                                        <ul class="space-y-4 border-l border-slate-50/10 pl-5 sm:pl-6">
                                            <template x-for="ev in d.events" :key="ev.key">
                                                <li class="relative">
                                                    <span class="absolute -left-[1.6rem] top-2 h-3 w-3 rounded-full ring-4 ring-slate-950 sm:-left-[1.85rem]" :class="META[ev.type].dot" aria-hidden="true"></span>

                                                    <!-- Zerou -->
                                                    <template x-if="ev.type === 'finished'">
                                                        <article class="flex gap-4 rounded-2xl border border-brand-400/30 bg-gradient-to-br from-brand-500/15 to-transparent p-4">
                                                            <a :href="games[ev.ref].href" class="relative block aspect-[16/25] w-20 shrink-0 self-start overflow-hidden rounded-md bg-slate-950 ring-1 ring-black/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 sm:w-24" :aria-label="'Abrir ' + games[ev.ref].title" :tabindex="games[ev.ref].href ? 0 : -1">
                                                                <img x-show="games[ev.ref].cover_url" :src="games[ev.ref].cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                                                <span x-show="!games[ev.ref].cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-xl font-bold text-white/80" x-text="UI.initials(games[ev.ref].title)"></span>
                                                                <span class="absolute left-1 top-1 grid h-6 min-w-6 place-items-center rounded px-1 text-xs font-black leading-none ring-1 ring-black/40" :class="UI.ratingClass(ev.rating)" :style="UI.ratingStyle(ev.rating)" :title="'Nota ' + ev.rating" x-text="ev.rating"></span>
                                                            </a>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="text-xs font-bold uppercase tracking-wider text-brand-400">Zerou</p>
                                                                <h3 class="text-lg font-bold leading-snug text-slate-50">
                                                                    <a :href="games[ev.ref].href" class="hover:text-brand-400 focus:outline-none focus-visible:underline" x-text="games[ev.ref].title"></a>
                                                                </h3>
                                                                <p class="text-sm text-slate-400" x-text="games[ev.ref].platform"></p>
                                                                <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-300">
                                                                    <span class="rounded px-1.5 py-0.5 font-bold" :class="UI.diffClass(ev.difficulty)" x-text="'Dificuldade ' + ev.difficulty"></span>
                                                                    <span x-show="ev.playtime_minutes" x-text="UI.fmtTime(ev.playtime_minutes) + ' de jogo'"></span>
                                                                    <span x-show="ev.days !== null" x-text="ev.days === 0 ? 'começou e terminou no mesmo dia' : 'em ' + ev.days + (ev.days === 1 ? ' dia' : ' dias')"></span>
                                                                    <span x-show="ev.save_states">com save states</span>
                                                                </p>
                                                                <p x-show="ev.people.length || ev.played_where" class="mt-2 flex flex-wrap items-center gap-1.5 text-sm text-slate-300">
                                                                    <span x-show="ev.people.length">Com</span>
                                                                    <template x-for="p in ev.people" :key="p">
                                                                        <button type="button" @click="person = p" class="rounded-full bg-slate-50/10 px-2.5 py-0.5 text-xs font-semibold text-slate-100 transition hover:bg-brand-500/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" :title="'Ver tudo o que você jogou com ' + p" x-text="p"></button>
                                                                    </template>
                                                                    <span x-show="ev.played_where" class="text-slate-400" x-text="(ev.people.length ? '· ' : '') + 'em ' + ev.played_where"></span>
                                                                </p>
                                                                <p x-show="ev.music_title" class="mt-1.5 text-sm text-slate-400">
                                                                    ♪ <a x-show="ev.music_url" :href="ev.music_url" target="_blank" rel="noopener" class="text-slate-200 hover:underline" x-text="ev.music_title"></a><span x-show="!ev.music_url" class="text-slate-200" x-text="ev.music_title"></span>
                                                                </p>
                                                                <button type="button" x-show="storyCount[ev.ref] > 1 && focusRef !== ev.ref" @click="focusOn(ev.ref)" class="mt-2 text-xs font-semibold text-brand-400 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">Ver a história deste jogo →</button>
                                                            </div>
                                                        </article>
                                                    </template>

                                                    <!-- Entrou na fila / Começou / Largou -->
                                                    <template x-if="ev.type === 'queued' || ev.type === 'started' || ev.type === 'dropped'">
                                                        <article>
                                                            <div class="flex items-center gap-3">
                                                                <span class="relative block aspect-[16/25] w-9 shrink-0 overflow-hidden rounded bg-slate-950 ring-1 ring-black/40" aria-hidden="true">
                                                                    <img x-show="games[ev.ref].cover_url" :src="games[ev.ref].cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                                                    <span x-show="!games[ev.ref].cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-2xs font-bold text-white/80" x-text="UI.initials(games[ev.ref].title)"></span>
                                                                </span>
                                                                <p class="min-w-0 text-sm text-slate-300">
                                                                    <span class="font-bold" :class="META[ev.type].text" x-text="META[ev.type].verb"></span>
                                                                    <a :href="games[ev.ref].href" class="font-semibold text-slate-50 hover:text-brand-400 focus:outline-none focus-visible:underline" x-text="games[ev.ref].title"></a>
                                                                    <span class="text-slate-500" x-text="games[ev.ref].platform ? '· ' + games[ev.ref].platform : ''"></span>
                                                                    <span x-show="ev.type === 'dropped' && ev.playtime_minutes" class="text-slate-500" x-text="'· depois de ' + UI.fmtTime(ev.playtime_minutes || 0)"></span>
                                                                    <span x-show="ev.type === 'started' && games[ev.ref].state === 'playing'" class="ml-1 rounded-full bg-ok/15 px-2 py-0.5 text-2xs font-semibold text-ok">jogando agora</span>
                                                                </p>
                                                            </div>
                                                            <p x-show="ev.text" class="mt-1.5 pl-12 text-sm italic text-slate-400" x-text="'“' + ev.text + '”'"></p>
                                                            <p x-show="ev.type === 'dropped'" class="mt-1 pl-12 text-xs text-slate-500" x-text="'Chance de voltar: ' + chanceLabel[ev.return_chance]"></p>
                                                            <button type="button" x-show="storyCount[ev.ref] > 1 && focusRef !== ev.ref" @click="focusOn(ev.ref)" class="ml-12 mt-1 text-xs font-semibold text-brand-400 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">Ver a história deste jogo →</button>
                                                        </article>
                                                    </template>

                                                    <!-- Anotação de um jogo / registro livre do dia / screenshots soltas -->
                                                    <template x-if="ev.type === 'note' || ev.type === 'entry' || ev.type === 'shots'">
                                                        <article class="rounded-xl p-4" :class="ev.type === 'entry' ? 'border border-dashed border-slate-50/20' : 'bg-slate-50/[0.05]'">
                                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                                <p class="min-w-0 text-xs text-slate-400">
                                                                    <span class="font-bold uppercase tracking-wider" :class="META[ev.type].text" x-text="ev.type === 'shots' ? ev.shots.length + (ev.shots.length === 1 ? ' screenshot' : ' screenshots') : META[ev.type].verb"></span>
                                                                    <template x-if="ev.ref"><span>
                                                                        · <a :href="games[ev.ref].href" class="font-semibold text-slate-200 hover:text-brand-400 focus:outline-none focus-visible:underline" x-text="games[ev.ref].title"></a>
                                                                        <button type="button" x-show="storyCount[ev.ref] > 1 && focusRef !== ev.ref" @click="focusOn(ev.ref)" class="ml-1 font-semibold text-brand-400 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">história →</button>
                                                                    </span></template>
                                                                </p>
                                                                <?php if (!$demo): ?>
                                                                <span x-show="ev.type !== 'shots'" class="flex gap-3 text-xs">
                                                                    <button type="button" @click="edit(ev)" class="text-slate-400 hover:text-slate-100 hover:underline">Editar</button>
                                                                    <button type="button" @click="remove(ev)" class="hover:underline" :class="confirmKey === ev.key ? 'font-semibold text-danger' : 'text-slate-400 hover:text-danger'" x-text="confirmKey === ev.key ? 'Apagar mesmo?' : 'Apagar'"></button>
                                                                </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <p x-show="ev.text" class="mt-2 whitespace-pre-line text-[15px] leading-relaxed text-slate-100" x-text="ev.text"></p>
                                                            <ul x-show="ev.shots?.length" class="mt-3 flex flex-wrap gap-2">
                                                                <template x-for="(s, i) in (ev.shots ?? [])" :key="s.id">
                                                                    <li>
                                                                        <button type="button" @click="view(ev, i)" class="block h-24 overflow-hidden rounded-md ring-1 ring-slate-50/15 transition hover:ring-brand-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                                                                :style="'aspect-ratio:' + s.width + '/' + s.height" :aria-label="'Ampliar imagem ' + (i + 1) + (s.caption ? ': ' + s.caption : '')">
                                                                            <img :src="s.thumb" alt="" loading="lazy" class="h-full w-full object-cover">
                                                                        </button>
                                                                    </li>
                                                                </template>
                                                            </ul>
                                                        </article>
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </li>
                            </template>
                        </ol>
                    </section>
                </template>
                <div x-ref="more" class="h-px" aria-hidden="true"></div>
                <p x-show="filtered.length && shownDays >= totalDays" class="mt-4 text-center text-xs text-slate-500" x-text="oldestFirst ? 'Você chegou a hoje.' : 'Aqui começa o seu diário.'"></p>
            </div>

            <!-- ============ Índice por mês ============ -->
            <nav class="hidden lg:block" aria-label="Ir para um mês">
                <div class="sticky top-4 max-h-[calc(100vh-2rem)] overflow-y-auto pr-1">
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Ir para</p>
                    <template x-for="y in rail" :key="y.year">
                        <div class="mt-3">
                            <p class="text-sm font-bold text-slate-200" x-text="y.year"></p>
                            <ul class="mt-1 space-y-0.5">
                                <template x-for="mo in y.months" :key="mo.key">
                                    <li>
                                        <button type="button" @click="jump(mo.key)" class="group flex w-full items-center gap-2 rounded px-1.5 py-1 text-left text-xs text-slate-400 hover:bg-slate-50/10 hover:text-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                            <span class="w-8 shrink-0 capitalize" x-text="mo.short"></span>
                                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-50/5" aria-hidden="true"><span class="block h-full rounded-full bg-brand-400/70 group-hover:bg-brand-400" :style="'width:' + mo.pct + '%'"></span></span>
                                            <span class="sr-only" x-text="mo.count + ' registros'"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </nav>
        </div>
    </div>

    <?php require __DIR__ . '/partials/lightbox.php'; ?>
</main>

<script>
function journal(isDemo) {
    const MONTHS = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const WEEK = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    const STEP = 40;   // dias desenhados por vez; o resto entra sozinho ao rolar a página
    const OK = ['image/jpeg', 'image/png', 'image/webp'];
    const norm = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const localToday = () => { const d = new Date(); return new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
    const dayNumber = (s) => { const [y, m, d] = s.split('-').map(Number); return Date.UTC(y, m - 1, d) / 86400000; };
    const blank = () => ({ id: null, kind: null, ref: null, note_date: localToday(), body: '' });
    let seq = 0;

    return {
        isDemo, base: window.APP.base, ready: false, error: '',
        games: {}, events: [], targets: [], today: localToday(),
        type: 'all', q: '', person: '', focusRef: null, oldestFirst: false,
        filtered: [], months: [], rail: [], totalDays: 0, shownDays: 0, limit: STEP,
        writing: false, picking: false, pickQ: '', draft: blank(), files: [], busy: false, progress: '', formError: '', confirmKey: null,

        META: {
            finished: { dot: 'bg-brand-400', text: 'text-brand-400', verb: 'Zerou' },
            started:  { dot: 'bg-ok', text: 'text-ok', verb: 'Começou a jogar' },
            queued:   { dot: 'bg-slate-400', text: 'text-slate-300', verb: 'Entrou na fila:' },
            dropped:  { dot: 'bg-danger', text: 'text-danger', verb: 'Largou' },
            note:     { dot: 'bg-accent', text: 'text-accent', verb: 'Anotação' },
            entry:    { dot: 'bg-c3', text: 'text-c3', verb: 'Registro do dia' },
            shots:    { dot: 'bg-slate-200', text: 'text-slate-200', verb: 'Screenshots' },
        },
        typeTabs: [
            { v: 'all', l: 'Tudo' }, { v: 'finished', l: 'Zerados' }, { v: 'note', l: 'Anotações' }, { v: 'entry', l: 'Registros do dia' },
            { v: 'started', l: 'Começados' }, { v: 'queued', l: 'Fila' }, { v: 'dropped', l: 'Largados' }, { v: 'shots', l: 'Screenshots' },
        ],
        stateLabel: { playing: 'jogando', want: 'na fila', finished: 'zerado' },
        chanceLabel: { low: 'baixa', medium: 'média', high: 'alta' },

        async init() {
            const p = new URLSearchParams(location.search);
            await this.load();
            if (p.get('jogo') && this.games[p.get('jogo')]) this.focusRef = p.get('jogo');
            for (const k of ['type', 'q', 'person', 'focusRef', 'oldestFirst']) this.$watch(k, () => { this.limit = STEP; this.rebuild(); });
            this.rebuild();
            new IntersectionObserver((es) => { if (es[0].isIntersecting) this.grow(); }, { rootMargin: '900px' }).observe(this.$refs.more);
        },
        async load() {
            try {
                const d = (await api.get(this.isDemo ? '/api/demo/journal' : '/api/journal')).data;
                this.games = d.games; this.events = d.events; this.targets = d.targets;
                if (this.isDemo) this.today = d.today;
                this.error = '';
            } catch (e) {
                if (e.status === 401) return (location.href = this.base + '/login');
                this.error = e.message;
            }
            this.ready = true;
        },

        // ---------- números e listas derivados de todos os eventos ----------
        get counts() {
            const c = {};
            for (const ev of this.events) c[ev.type] = (c[ev.type] || 0) + 1;
            return c;
        },
        /** Quantos eventos cada jogo tem: só vale "ver a história" quando há mais de um. */
        get storyCount() {
            const c = {};
            for (const ev of this.events) if (ev.ref) c[ev.ref] = (c[ev.ref] || 0) + 1;
            return c;
        },
        get people() {
            const by = new Map();
            for (const ev of this.events) for (const p of (ev.people ?? [])) by.set(p, (by.get(p) || 0) + 1);
            return [...by].map(([name, n]) => ({ name, n })).sort((a, b) => b.n - a.n || a.name.localeCompare(b.name, 'pt-BR'));
        },
        /** Resumo da jornada do jogo em foco: fila, começo e fim. */
        get arc() {
            if (!this.focusRef) return '';
            const at = (t) => this.events.find((e) => e.ref === this.focusRef && e.type === t);
            const parts = [];
            if (at('queued')) parts.push('entrou na fila em ' + UI.fmtDate(at('queued').date));
            if (at('started')) parts.push('começou em ' + UI.fmtDate(at('started').date));
            const fin = at('finished');
            if (fin) parts.push('zerou em ' + UI.fmtDate(fin.date) + (fin.days ? ' (' + fin.days + (fin.days === 1 ? ' dia' : ' dias') + ' depois de começar)' : ''));
            if (at('dropped')) parts.push('largou em ' + UI.fmtDate(at('dropped').date));
            const notes = this.events.filter((e) => e.ref === this.focusRef && e.type === 'note').length;
            if (notes) parts.push(notes + (notes === 1 ? ' anotação' : ' anotações'));
            const s = parts.join(' · ');
            return s.charAt(0).toUpperCase() + s.slice(1);
        },

        // ---------- filtro e montagem dos meses/dias ----------
        rebuild() {
            const q = norm(this.q.trim());
            let list = this.events.filter((ev) => {
                if (this.focusRef && ev.ref !== this.focusRef) return false;
                if (this.type !== 'all' && ev.type !== this.type) return false;
                if (this.person && !(ev.people ?? []).includes(this.person)) return false;
                if (q) {
                    const g = ev.ref ? this.games[ev.ref] : null;
                    if (!norm((g ? g.title + ' ' + (g.platform ?? '') : '') + ' ' + (ev.text ?? '') + ' ' + (ev.people ?? []).join(' ') + ' ' + (ev.played_where ?? '')).includes(q)) return false;
                }
                return true;
            });
            if (this.oldestFirst) list = [...list].reverse();
            this.filtered = list;

            // índice lateral: todos os meses do resultado
            const perMonth = new Map();
            for (const ev of list) perMonth.set(ev.date.slice(0, 7), (perMonth.get(ev.date.slice(0, 7)) || 0) + 1);
            const max = Math.max(1, ...perMonth.values());
            const years = new Map();
            for (const [key, count] of perMonth) {
                const y = key.slice(0, 4);
                if (!years.has(y)) years.set(y, { year: y, months: [] });
                years.get(y).months.push({ key, count, short: MONTHS[+key.slice(5) - 1].slice(0, 3).toLowerCase(), pct: Math.max(6, Math.round(count / max * 100)) });
            }
            this.rail = [...years.values()];

            // dias (só os primeiros `limit` são desenhados)
            const days = [];
            let cur = null;
            for (const ev of list) {
                if (!cur || cur.date !== ev.date) {
                    if (days.length >= this.limit) { cur = null; break; }
                    const [y, m, d] = ev.date.split('-').map(Number);
                    const gap = cur ? Math.abs(dayNumber(cur.date) - dayNumber(ev.date)) - 1 : 0;
                    cur = { date: ev.date, day: d, weekday: WEEK[new Date(y, m - 1, d).getDay()], gap: gap >= 30 ? gap : 0, events: [] };
                    days.push(cur);
                }
                cur.events.push(ev);
            }
            this.totalDays = new Set(list.map((e) => e.date)).size;
            this.shownDays = days.length;

            const months = [];
            for (const d of days) {
                const key = d.date.slice(0, 7);
                let m = months[months.length - 1];
                if (!m || m.key !== key) { m = { key, label: MONTHS[+key.slice(5) - 1], year: key.slice(0, 4), days: [], summary: '' }; months.push(m); }
                m.days.push(d);
            }
            const word = { finished: ['zerado', 'zerados'], note: ['anotação', 'anotações'], entry: ['registro', 'registros'], started: ['começado', 'começados'],
                queued: ['na fila', 'na fila'], dropped: ['largado', 'largados'], shots: ['dia com screenshots', 'dias com screenshots'] };
            for (const m of months) {
                const c = {};
                for (const d of m.days) for (const ev of d.events) c[ev.type] = (c[ev.type] || 0) + 1;
                m.summary = Object.keys(word).filter((t) => c[t]).map((t) => c[t] + ' ' + word[t][c[t] === 1 ? 0 : 1]).join(' · ');
            }
            this.months = months;
        },
        grow() {
            if (this.shownDays >= this.totalDays) return;
            this.limit += STEP;
            this.rebuild();
            // se o fim da lista ainda está perto da tela, continua desenhando
            this.$nextTick(() => { if (this.$refs.more.getBoundingClientRect().top < innerHeight + 900) this.grow(); });
        },
        jump(key) {
            const dates = [...new Set(this.filtered.map((e) => e.date))];
            const last = dates.findLastIndex((d) => d.startsWith(key));
            if (last + 1 > this.limit) { this.limit = last + 1 + STEP; this.rebuild(); }
            this.$nextTick(() => document.getElementById('m-' + key)?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }));
        },
        focusOn(ref) {
            this.type = 'all'; this.q = ''; this.person = ''; this.focusRef = ref;
            scrollTo({ top: 0, behavior: 'auto' });
        },
        longDate(s) {
            const [y, m, d] = s.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },
        view(ev, i) {
            const g = ev.ref ? this.games[ev.ref] : null;
            window.dispatchEvent(new CustomEvent('lightbox', { detail: {
                index: i, items: ev.shots.map((s) => ({ url: s.url, caption: s.caption, meta: (g ? g.title + ' · ' : '') + UI.fmtDate(ev.date) })),
            } }));
        },

        // ---------- escrever, editar e apagar ----------
        get quickTargets() { return this.targets.filter((t) => t.state === 'playing').slice(0, 6); },
        get pickResults() {
            const q = norm(this.pickQ.trim());
            return (q ? this.targets.filter((t) => norm(t.title).includes(q)) : this.targets).slice(0, 8);
        },
        startWrite() {
            this.draft = blank(); this.draft.ref = this.focusRef;
            this.clearFiles(); this.formError = ''; this.picking = false; this.pickQ = ''; this.writing = true;
            this.$nextTick(() => this.$refs.body?.focus());
        },
        edit(ev) {
            this.draft = { id: ev.id, kind: ev.type, ref: ev.ref, note_date: ev.date, body: ev.text };
            this.clearFiles(); this.formError = ''; this.picking = false; this.writing = true;
            this.$nextTick(() => { this.$refs.body?.focus(); this.$refs.body?.scrollIntoView({ block: 'center' }); });
        },
        cancelWrite() { this.writing = false; this.formError = ''; this.clearFiles(); },
        addFiles(list) {
            this.formError = '';
            for (const f of list) {
                if (!OK.includes(f.type)) { this.formError = f.name + ': use JPEG, PNG ou WebP.'; continue; }
                if (f.size > 8 * 1024 * 1024) { this.formError = f.name + ': passa de 8 MB.'; continue; }
                if (this.files.length >= 10) { this.formError = 'No máximo 10 imagens de cada vez.'; break; }
                this.files.push({ key: ++seq, file: f, preview: URL.createObjectURL(f) });
            }
        },
        dropFile(i) { URL.revokeObjectURL(this.files[i].preview); this.files.splice(i, 1); },
        clearFiles() { this.files.forEach((f) => URL.revokeObjectURL(f.preview)); this.files = []; },
        async save() {
            this.busy = true; this.formError = ''; this.progress = '';
            const body = { note_date: this.draft.note_date, body: this.draft.body };
            const g = this.draft.ref ? this.games[this.draft.ref] : null;
            try {
                let saved;
                if (g) saved = (this.draft.id ? await api.put('/api/notes/' + this.draft.id, body) : await api.post(`/api/${g.kind}/${g.id}/notes`, body)).data;
                else saved = (this.draft.id ? await api.put('/api/journal/entries/' + this.draft.id, body) : await api.post('/api/journal/entries', body)).data;
                const failed = [];
                for (let i = 0; g && i < this.files.length; i++) {   // uma imagem por requisição
                    this.progress = `Enviando imagem ${i + 1} de ${this.files.length}…`;
                    const fd = new FormData();
                    fd.append('shots', this.files[i].file);
                    fd.append('note_id', saved.id);
                    try { await api.post(`/api/${g.kind}/${g.id}/shots`, fd); } catch (e) { failed.push(this.files[i].file.name + ': ' + e.message); }
                }
                this.clearFiles();
                await this.load();
                this.rebuild();
                if (failed.length) { this.formError = 'O texto foi salvo, mas algumas imagens não: ' + failed.join(' · '); this.draft = { ...this.draft, id: saved.id }; }
                else this.writing = false;
            } catch (e) { this.formError = e.errors?.body || e.errors?.note_date || e.message; }
            this.busy = false; this.progress = '';
        },
        async remove(ev) {
            if (this.confirmKey !== ev.key) { this.confirmKey = ev.key; setTimeout(() => { if (this.confirmKey === ev.key) this.confirmKey = null; }, 4000); return; }
            try {
                await api.del(ev.type === 'entry' ? '/api/journal/entries/' + ev.id : '/api/notes/' + ev.id);
                await this.load();
                this.rebuild();
            } catch (e) { this.error = e.message; }
            this.confirmKey = null;
        },
    };
}
</script>
