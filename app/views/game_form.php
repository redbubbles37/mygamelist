<?php /** @var array $me */ /** @var array|null $game  jogo em edição (nulo = novo) */
$game = $game ?? null;
$editing = $game !== null;
?>
<style>
    /* comemoração do registro: o estojo "cai" na estante e alguns confetes (desligados com movimento reduzido e no modo calmo) */
    @keyframes mgl-drop { 0% { transform: translateY(-48px) rotate(-8deg) scale(.9); opacity: 0 } 60% { transform: translateY(4px) rotate(2deg) scale(1.03); opacity: 1 } 100% { transform: none } }
    .celebrate-in { animation: mgl-drop .7s cubic-bezier(.2, .8, .2, 1) both }
    @keyframes mgl-confetti { 0% { transform: translate3d(0, -10px, 0) rotate(0); opacity: 1 } 100% { transform: translate3d(var(--dx), 190px, 0) rotate(540deg); opacity: 0 } }
    .confetti span { position: absolute; top: 0; width: 8px; height: 12px; border-radius: 2px; animation: mgl-confetti 1.5s ease-out forwards }
    @keyframes mgl-glow { 0%, 100% { box-shadow: 0 0 0 0 rgb(251 191 36 / 0) } 50% { box-shadow: 0 0 34px 6px rgb(251 191 36 / .45) } }
    .level-glow { animation: mgl-glow 1.6s ease-in-out 2 }
    @media (prefers-reduced-motion: reduce) { .celebrate-in, .level-glow { animation: none } .confetti { display: none } }
    [data-calm] .confetti { display: none }
</style>
<main class="page page-narrow" x-data='gameForm(<?= e(json_encode($me, JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode($game, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <a :href="back.href" class="back-link mb-3" x-text="'← ' + back.label"></a>

    <!-- ========== Destaque: o registro ao vivo ========== -->
    <header x-show="!done" class="hero-box p-5 text-white sm:p-7">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="coverUrl" :src="coverUrl" alt="" class="h-full w-full scale-150 object-cover opacity-30 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/65 to-brand-700/30"></div>
        </div>
        <p class="kicker-hero"><?= $editing ? 'Editar jogo · o XP é recalculado ao salvar' : 'Registrar jogo zerado' ?></p>
        <h1 class="mt-1 line-clamp-2 break-words t-hero" :class="form.title.trim() ? '' : 'text-white/40'" x-text="form.title.trim() || 'Qual jogo você zerou?'"></h1>
        <p class="mt-3 flex flex-wrap items-center gap-2 text-sm">
            <span x-show="form.platform.trim()" class="rounded-full px-2.5 py-0.5 text-xs font-bold" :style="UI.bandStyle(form.platform.trim()) + ';background:var(--band-bg);color:var(--band-fg)'" x-text="form.platform"></span>
            <span x-show="form.finished_at" class="rounded-full bg-white/10 px-2.5 py-0.5 text-white/80" x-text="'zerado em ' + (form.finished_at ? UI.fmtDate(form.finished_at) : '')"></span>
            <span x-show="minutes > 0" class="rounded-full bg-white/10 px-2.5 py-0.5 text-white/80" x-text="'⏱ ' + UI.fmtTime(minutes)"></span>
            <span x-show="form.rating" class="rounded-md px-2 py-0.5 text-xs font-black" :class="UI.ratingClass(form.rating)" :style="form.rating ? UI.ratingStyle(form.rating) : ''" x-text="'nota ' + form.rating"></span>
            <span x-show="form.difficulty" class="rounded-md px-2 py-0.5 text-xs font-black" :style="form.difficulty ? UI.diffStyle(form.difficulty) : ''" x-text="form.difficulty"></span>
            <span x-show="xpPreview" class="xp-ui rounded-full bg-brand-500/30 px-2.5 py-0.5 text-xs font-bold text-white" x-text="'+' + (xpPreview?.total ?? 0) + ' XP'"></span>
            <span x-show="!form.platform.trim() && !form.rating" class="text-white/60">Preencha os campos e veja o registro tomar forma aqui.</span>
        </p>
    </header>

    <!-- Rascunho guardado neste navegador -->
    <div x-show="draftOffer && !done" x-cloak role="status" class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-400/40 bg-brand-500/10 px-4 py-3 text-sm">
        <p class="text-slate-200">📝 Você deixou um rascunho de <strong class="text-slate-50" x-text="draftOffer?.form.title"></strong> pela metade. Quer continuar de onde parou?</p>
        <span class="flex gap-2">
            <button type="button" @click="restoreDraft()" class="btn btn-sm">Recuperar</button>
            <button type="button" @click="discardDraft()" class="btn-ghost btn-sm">Descartar</button>
        </span>
    </div>

    <!-- Sucesso -->
    <section x-show="done" x-cloak class="card relative mt-2 overflow-hidden text-center" aria-live="polite">
        <template x-if="done">
            <div>
                <div class="confetti pointer-events-none absolute inset-x-0 top-0 h-56" aria-hidden="true">
                    <template x-for="(c, i) in done.confetti" :key="i"><span :style="c"></span></template>
                </div>
                <div class="celebrate-in mx-auto w-40">
                    <div class="case rounded-xl" :class="done.leveledUp && 'level-glow'">
                        <div class="case-band" :style="UI.bandStyle(done.game.platform)"><span x-text="done.game.platform"></span></div>
                        <div class="case-art">
                            <img x-show="done.game.cover_url" :src="done.game.cover_url" alt="" class="absolute inset-0 h-full w-full object-cover">
                            <div x-show="!done.game.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60"><span class="text-4xl font-bold text-white/80" x-text="UI.initials(done.game.title)"></span></div>
                            <span class="absolute left-2 top-2 grid h-7 min-w-7 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow ring-1 ring-black/40" :class="UI.ratingClass(done.game.rating)" :style="UI.ratingStyle(done.game.rating)" x-text="done.game.rating"></span>
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-400">Jogo registrado</p>
                <h2 class="t-section" x-text="done.game.title"></h2>
                <p class="mt-1 text-sm text-slate-400" x-text="done.game.platform"></p>
                <p class="xp-ui mt-4 text-3xl font-bold text-brand-400">+<span x-text="done.game.xp"></span> XP</p>
                <p x-show="done.leveledUp" x-cloak class="xp-ui mt-2 inline-block rounded-full bg-ok/15 px-3 py-1 text-sm font-semibold text-ok">
                    🎉 Subiu para o nível <span x-text="done.xp.level"></span>!
                </p>
                <p x-show="droppedRemoved" x-cloak class="mt-2 text-sm text-slate-400">Removido da sua lista de dropados.</p>
                <p x-show="backlogRemoved" x-cloak class="mt-2 text-sm text-slate-400">Removido do seu backlog.</p>
                <div class="xp-ui mx-auto mt-4 max-w-sm">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span>Nível <span x-text="done.xp.level"></span></span>
                        <span><span x-text="fmt(done.xp.total_xp)"></span><template x-if="done.xp.next_level_xp"><span> / <span x-text="fmt(done.xp.next_level_xp)"></span></span></template> XP</span>
                    </div>
                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-50/10">
                        <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent" :style="`width:${done.xp.progress_pct}%`"></div>
                    </div>
                </div>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a :href="window.APP.base + '/jogos/' + done.game.id" class="btn">Ver o jogo</a>
                    <button type="button" @click="reset()" class="rounded-lg border border-slate-50/15 px-5 py-2.5 font-semibold text-slate-100 hover:bg-slate-50/5" :title="'Mantém o sistema (' + done.game.platform + ') e a data'">Adicionar outro</button>
                    <a x-show="fromFranchise" x-cloak :href="window.APP.base + '/franquias/' + fromFranchise" class="rounded-lg border border-brand-400/50 px-5 py-2.5 font-medium text-brand-400 hover:bg-brand-500/10">Voltar à franquia</a>
                    <a href="<?= e(url('/')) ?>" class="btn-ghost">Ver o painel</a>
                </div>
            </div>
        </template>
    </section>

    <!-- Formulário -->
    <form x-show="!done" @submit.prevent="submit" novalidate class="mt-6 grid gap-6 lg:grid-cols-[18rem_1fr]">

        <!-- Coluna esquerda (computador): o estojo ao vivo, o que o site já sabe sobre o título e o XP -->
        <aside class="hidden space-y-4 lg:sticky lg:top-6 lg:block lg:self-start">
            <?php
            $caseBand = "form.finished_at ? UI.fmtDate(form.finished_at) : ''";
            $caseBadges = '<span x-show="form.rating" class="absolute left-2.5 top-2.5 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40" :class="UI.ratingClass(form.rating)" :style="UI.ratingStyle(form.rating)" x-text="form.rating"></span>'
                . '<span x-show="form.difficulty" class="absolute right-2.5 top-2.5 grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-black leading-none shadow-lg ring-1 ring-black/40" :style="UI.diffStyle(form.difficulty)" x-text="form.difficulty"></span>';
            require __DIR__ . '/partials/form_case.php';
            $checkMode = 'game'; $checkSelf = $editing ? (int) $game['id'] : null;
            require __DIR__ . '/partials/title_check.php';
            ?>

            <div class="card xp-ui" x-show="xpPreview" x-cloak aria-live="polite">
                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">XP desta finalização</p>
                <ul class="mt-2 space-y-1 text-sm">
                    <template x-for="(it, i) in (xpPreview?.items ?? [])" :key="i">
                        <li class="flex justify-between gap-3 text-slate-400"><span x-text="it.label"></span><span class="tabular-nums text-slate-200" x-text="'+' + it.xp"></span></li>
                    </template>
                </ul>
                <p class="mt-3 border-t border-slate-50/10 pt-3 text-2xl font-bold text-brand-400">+<span x-text="xpPreview?.total ?? 0"></span> XP</p>
            </div>
        </aside>

        <!-- Coluna direita: campos -->
        <div class="flex min-w-0 flex-col gap-6">
            <div x-show="formError" x-cloak x-ref="formError" role="alert" class="alert-error" x-text="formError"></div>

            <section class="card space-y-4" aria-labelledby="h-s1">
                <h2 id="h-s1" class="flex items-center gap-2 text-base font-bold text-slate-50"><span class="grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-xs text-on-brand">1</span>O jogo</h2>

                <!-- celular: a capa numa linha compacta (o estojo grande fica só no computador) -->
                <div class="flex items-center gap-3 rounded-xl border border-slate-50/10 bg-slate-950/40 p-2.5 lg:hidden">
                    <button type="button" @click="$refs.file.click()" class="relative h-20 w-14 shrink-0 overflow-hidden rounded-md bg-gradient-to-br from-neutral to-brand-600/60 ring-1 ring-black/40" aria-label="Escolher a capa">
                        <img x-show="coverUrl" :src="coverUrl" alt="Pré-visualização da capa" class="absolute inset-0 h-full w-full object-cover">
                        <span x-show="!coverUrl" class="absolute inset-0 grid place-items-center text-lg font-bold text-white/80" x-text="form.title.trim() ? UI.initials(form.title.trim()) : '?'"></span>
                    </button>
                    <span class="min-w-0 flex-1 text-sm">
                        <span class="block font-semibold text-slate-200">Capa <span class="font-normal text-slate-500">(opcional)</span></span>
                        <span class="block text-xs text-slate-500">JPEG, PNG ou WebP · até 2 MB · ou cole com Ctrl+V</span>
                        <span class="mt-1 flex gap-3 text-xs font-semibold">
                            <button type="button" @click="$refs.file.click()" class="text-brand-400 hover:underline" x-text="coverUrl ? 'Trocar' : 'Escolher'"></button>
                            <button type="button" x-show="coverUrl" @click="clearCover()" class="text-slate-400 hover:text-danger">Remover</button>
                        </span>
                        <span x-show="errors.cover" x-cloak class="err block" x-text="errors.cover"></span>
                    </span>
                </div>

                <div>
                    <label for="title" class="lbl">Título</label>
                    <input id="title" type="text" maxlength="200" x-model="form.title" @blur="validate('title')" class="field" :class="errors.title && 'border-danger/60'" placeholder="Ex.: Resident Evil 4 (Wii)">
                    <p x-show="errors.title" x-cloak class="err" x-text="errors.title"></p>
                    <p class="mt-1 text-xs text-slate-500">O mesmo título só se repete se o sistema ou o ano de lançamento forem diferentes (ex.: Doom 1993 e Doom 2016).</p>
                </div>
                <!-- celular: o que o site já sabe sobre o título, logo abaixo dele -->
                <div class="lg:hidden"><?php require __DIR__ . '/partials/title_check.php'; ?></div>

                <!-- preencher com os dados de outra versão que você já registrou -->
                <div x-show="fillSource" x-cloak class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-brand-400/30 bg-brand-500/10 px-3 py-2 text-sm">
                    <span class="text-slate-200" x-text="fillSource ? 'Você já tem ' + fillSource.title + (fillSource.platform ? ' (' + fillSource.platform + ')' : '') + ' ' + fillSource.where + '.' : ''"></span>
                    <button type="button" @click="fillFrom()" :disabled="filling" class="btn btn-sm" x-text="filling ? 'Preenchendo…' : 'Usar gênero, ano e empresas'"></button>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="platform" class="lbl">Sistema</label>
                        <input id="platform" type="text" maxlength="60" list="dl-platforms" x-model="form.platform" @input="suggest('platforms', form.platform)" @blur="validate('platform')"
                               class="field" :class="errors.platform && 'border-danger/60'" placeholder="Ex.: PS5, Switch, PC" autocomplete="off">
                        <datalist id="dl-platforms"><template x-for="s in suggestions.platforms" :key="s"><option :value="s"></option></template></datalist>
                        <p x-show="errors.platform" x-cloak class="err" x-text="errors.platform"></p>
                    </div>
                    <div>
                        <label for="genre" class="lbl">Gênero</label>
                        <input id="genre" type="text" maxlength="50" list="dl-genres" x-model="form.genre" @input="suggest('genres', form.genre)" @blur="validate('genre')"
                               class="field" :class="errors.genre && 'border-danger/60'" placeholder="Ex.: RPG, Ação" autocomplete="off">
                        <datalist id="dl-genres"><template x-for="s in suggestions.genres" :key="s"><option :value="s"></option></template></datalist>
                        <p x-show="errors.genre" x-cloak class="err" x-text="errors.genre"></p>
                    </div>
                    <div>
                        <label for="release_year" class="lbl">Ano de lançamento</label>
                        <input id="release_year" type="number" inputmode="numeric" min="1950" :max="today.slice(0, 4)" x-model.number="form.release_year" @blur="validate('release_year')"
                               class="field" :class="errors.release_year && 'border-danger/60'" placeholder="Ex.: 2005">
                        <p x-show="errors.release_year" x-cloak class="err" x-text="errors.release_year"></p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="developer" class="lbl">Desenvolvedora <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="developer" type="text" maxlength="100" list="dl-companies" x-model="form.developer" @input="suggest('companies', form.developer)" class="field" :class="errors.developer && 'border-danger/60'" autocomplete="off">
                        <p x-show="errors.developer" x-cloak class="err" x-text="errors.developer"></p>
                    </div>
                    <div>
                        <label for="publisher" class="lbl">Publisher <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="publisher" type="text" maxlength="100" list="dl-companies" x-model="form.publisher" @input="suggest('companies', form.publisher)" class="field" :class="errors.publisher && 'border-danger/60'" autocomplete="off">
                        <p x-show="errors.publisher" x-cloak class="err" x-text="errors.publisher"></p>
                    </div>
                    <datalist id="dl-companies"><template x-for="s in suggestions.companies" :key="s"><option :value="s"></option></template></datalist>
                </div>
            </section>

            <section class="card space-y-5" aria-labelledby="h-s2">
                <h2 id="h-s2" class="flex items-center gap-2 text-base font-bold text-slate-50"><span class="grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-xs text-on-brand">2</span>Como foi</h2>

                <!-- quando: as duas datas lado a lado -->
                <div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="finished_at" class="lbl">Zerei em</label>
                            <input id="finished_at" type="date" :max="today" x-model="form.finished_at" @blur="validate('finished_at'); if (form.release_year) validate('release_year')" class="field [color-scheme:dark]" :class="errors.finished_at && 'border-danger/60'">
                            <p x-show="errors.finished_at" x-cloak class="err" x-text="errors.finished_at"></p>
                        </div>
                        <div>
                            <label for="started_at" class="lbl">Comecei em <span class="font-normal text-slate-500">(opcional)</span></label>
                            <input id="started_at" type="date" :max="form.finished_at || today" x-model="form.started_at" @blur="validate('started_at')" class="field [color-scheme:dark]" :class="errors.started_at && 'border-danger/60'">
                            <p x-show="errors.started_at" x-cloak class="err" x-text="errors.started_at"></p>
                        </div>
                    </div>
                    <p x-show="playedDays !== null && !errors.started_at" x-cloak class="mt-1 text-xs text-slate-400" x-text="playedDays === 0 ? 'Começou e zerou no mesmo dia.' : 'Do início ao fim: ' + playedDays + (playedDays === 1 ? ' dia' : ' dias') + '.'"></p>
                    <p x-show="startedFromBacklog" x-cloak class="mt-1 text-xs text-brand-400">Data de início trazida do backlog (“jogando agora”).</p>
                </div>

                <div>
                    <span class="lbl" id="l-time">Tempo de jogo</span>
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-labelledby="l-time">
                        <input type="number" min="0" max="16000" inputmode="numeric" x-model.number="form.hours" @blur="validate('playtime')" class="field !w-28" :class="errors.playtime && 'border-danger/60'" placeholder="0" aria-label="Horas">
                        <span class="text-sm text-slate-400">h</span>
                        <input type="number" min="0" max="59" inputmode="numeric" x-model.number="form.minutes" @blur="validate('playtime')" class="field !w-24" :class="errors.playtime && 'border-danger/60'" placeholder="0" aria-label="Minutos">
                        <span class="text-sm text-slate-400">min</span>
                    </div>
                    <!-- atalhos para valores comuns -->
                    <div class="mt-2 flex flex-wrap gap-1.5" aria-label="Atalhos de tempo">
                        <template x-for="h in [5, 10, 20, 40, 80]" :key="h">
                            <button type="button" @click="form.hours = h; form.minutes = ''; validate('playtime')" class="rounded-full border px-2.5 py-1 text-xs font-semibold transition"
                                    :class="Number(form.hours) === h && !Number(form.minutes) ? 'border-brand-400 bg-brand-500/15 text-brand-400' : 'border-slate-50/10 text-slate-300 hover:border-brand-400/60 hover:text-brand-400'" x-text="'~' + h + 'h'"></button>
                        </template>
                    </div>
                    <p x-show="errors.playtime" x-cloak class="err" x-text="errors.playtime"></p>
                </div>

                <div>
                    <span class="lbl" id="l-rating">Nota <span class="font-normal text-slate-500">· de 1 a 11 (o 11 é para os lendários)</span></span>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="l-rating" @keydown="radioKeys($event)">
                        <template x-for="n in 11" :key="n">
                            <button type="button" role="radio" :aria-checked="form.rating === n" @click="if (!locked(n)) { form.rating = n; validate('rating'); calibrate(n) }"
                                    :tabindex="(form.rating ? form.rating === n : n === 1) ? 0 : -1"
                                    :aria-disabled="locked(n) ? 'true' : null" :title="locked(n) ? lockText(n) : null"
                                    class="relative grid h-11 w-11 place-items-center rounded-lg border text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                    :style="!locked(n) && form.rating === n ? UI.ratingStyle(n) : ''"
                                    :class="locked(n) ? 'cursor-not-allowed border-slate-50/10 bg-slate-950/30 text-slate-600' : form.rating === n
                                        ? ['border-transparent scale-110 font-black shadow-lg ring-2 ring-slate-50/40', UI.ratingClass(n)]
                                        : 'border-slate-50/10 bg-slate-950/60 text-slate-300 hover:border-slate-50/30'">
                                <span x-text="n"></span>
                                <svg x-show="locked(n)" x-cloak class="absolute -right-1 -top-1 h-4 w-4 rounded-full bg-slate-800 p-0.5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                            </button>
                        </template>
                    </div>
                    <p x-show="form.rating" x-cloak class="mt-2 text-sm font-semibold text-slate-200" x-text="form.rating ? 'Nota ' + form.rating + ' · ' + ratingWord(form.rating) : ''"></p>
                    <!-- calibrar: outros jogos que levaram a mesma nota -->
                    <div x-show="form.rating && peers.length" x-cloak class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400" aria-live="polite">
                        <span x-text="'Você também deu ' + form.rating + ' para:'"></span>
                        <template x-for="p in peers" :key="p.id">
                            <a :href="window.APP.base + '/jogos/' + p.id" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-slate-50/5 py-0.5 pl-0.5 pr-2 text-slate-200 hover:bg-slate-50/10">
                                <span class="h-6 w-4 overflow-hidden rounded-sm bg-slate-800"><img x-show="p.cover_url" :src="p.cover_url" alt="" class="h-full w-full object-cover"></span>
                                <span class="max-w-[10rem] truncate" x-text="p.title"></span>
                            </a>
                        </template>
                    </div>
                    <p x-show="form.rating && peersLoaded && !peers.length" x-cloak class="mt-2 text-xs text-slate-500" x-text="'É o primeiro jogo com nota ' + form.rating + ' no seu catálogo.'"></p>
                    <p x-show="quota" x-cloak class="mt-1 text-xs text-slate-400">
                        <span x-text="quotaLine(10)"></span> · <span x-text="quotaLine(11)"></span>
                    </p>
                    <p x-show="errors.rating" x-cloak class="err" x-text="errors.rating"></p>
                </div>

                <div>
                    <span class="lbl" id="l-diff">Dificuldade <span class="font-normal text-slate-500">· como foi para você (C é a mais fácil, AAA a mais difícil)</span></span>
                    <div class="inline-flex flex-wrap gap-2" role="radiogroup" aria-labelledby="l-diff" @keydown="radioKeys($event)">
                        <template x-for="d in ['C','B','A','AA','AAA']" :key="d">
                            <button type="button" role="radio" :aria-checked="form.difficulty === d" @click="form.difficulty = d; validate('difficulty')"
                                    :tabindex="(form.difficulty ? form.difficulty === d : d === 'C') ? 0 : -1"
                                    class="min-w-[3.5rem] rounded-lg border px-4 py-2.5 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                    :style="form.difficulty === d ? UI.diffStyle(d) : ''"
                                    :class="form.difficulty === d ? 'border-transparent scale-105 shadow-lg ring-2 ring-slate-50/40' : 'border-slate-50/10 bg-slate-950/60 text-slate-300 hover:border-slate-50/30'" x-text="d"></button>
                        </template>
                    </div>
                    <p x-show="errors.difficulty" x-cloak class="err" x-text="errors.difficulty"></p>
                </div>

                <div class="rounded-xl border border-slate-50/10 bg-slate-950/40 p-3.5">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" x-model="form.used_save_states" class="mt-1 h-4 w-4 shrink-0 rounded">
                        <span>
                            <span class="text-sm font-medium text-slate-200">Usei save state para zerar</span>
                            <span class="xp-ui block text-xs text-slate-500">Sem problema nenhum! Só que o XP deste jogo cai pela metade, para ficar justo com quem jogou sem.</span>
                            <span class="hidden text-xs text-slate-500 [[data-calm]_&]:block">Fica marcado no jogo; é só uma anotação.</span>
                        </span>
                    </label>
                </div>
            </section>

            <section class="card" aria-labelledby="h-mem">
                <button type="button" @click="openMemories()" :aria-expanded="memOpen" class="flex w-full items-center justify-between gap-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                    <span>
                        <span id="h-mem" class="flex items-center gap-2 text-base font-bold text-slate-50"><span class="grid h-6 w-6 place-items-center rounded-full bg-slate-50/15 text-xs text-slate-200">3</span>Memórias <span class="text-sm font-normal text-slate-500">(opcional)</span></span>
                        <span class="block text-xs text-slate-500">Como esse jogo entrou na sua vida, com quem e onde você jogou.</span>
                    </span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition" :class="memOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div x-show="memOpen" x-cloak class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="intro_by" class="lbl">Quem te apresentou</label>
                            <input id="intro_by" type="text" maxlength="100" x-model="form.intro_by" class="field" placeholder="Ex.: meu irmão, uma revista, um amigo da escola">
                        </div>
                        <div>
                            <label for="intro_where" class="lbl">Onde você conseguiu</label>
                            <input id="intro_where" type="text" maxlength="100" x-model="form.intro_where" class="field" placeholder="Ex.: locadora, presente de Natal, promoção">
                        </div>
                        <div>
                            <label for="intro_age" class="lbl">Idade na época</label>
                            <input id="intro_age" type="number" min="0" max="120" inputmode="numeric" x-model="form.intro_age" class="field !w-28" :class="errors.intro_age && 'border-danger/60'">
                            <p x-show="errors.intro_age" x-cloak class="err" x-text="errors.intro_age"></p>
                        </div>
                        <div>
                            <label for="played_where" class="lbl">Onde você jogou</label>
                            <input id="played_where" type="text" maxlength="100" list="dl-places" x-model="form.played_where" class="field" placeholder="Ex.: sala de casa, casa do primo, no ônibus" autocomplete="off">
                            <datalist id="dl-places"><template x-for="s in circles.places" :key="s"><option :value="s"></option></template></datalist>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="music_title" class="lbl">Música do momento</label>
                            <input id="music_title" type="text" maxlength="150" x-model="form.music_title" class="field" :class="errors.music_title && 'border-danger/60'" placeholder="Ex.: Se não fosse tão tarde, Lou Garcia">
                            <p x-show="errors.music_title" x-cloak class="err" x-text="errors.music_title"></p>
                            <p class="mt-1 text-xs text-slate-500">O que estava tocando na sua vida nessa época. Não precisa ter a ver com o jogo: é a música que faz você lembrar dele.</p>
                        </div>
                        <div>
                            <label for="music_url" class="lbl">Link da música <span class="font-normal text-slate-500">(opcional)</span></label>
                            <input id="music_url" type="url" maxlength="300" x-model="form.music_url" class="field" :class="errors.music_url && 'border-danger/60'" placeholder="https://…" autocomplete="off">
                            <p x-show="errors.music_url" x-cloak class="err" x-text="errors.music_url"></p>
                        </div>
                    </div>
                    <div>
                        <label for="person" class="lbl">Com quem você jogou</label>
                        <div class="flex gap-2">
                            <input id="person" type="text" maxlength="60" list="dl-people" x-model="personInput" @keydown.enter.prevent="addPerson()" class="field" placeholder="Digite um nome e aperte Enter" autocomplete="off">
                            <button type="button" @click="addPerson()" :disabled="!personInput.trim()" class="btn-ghost shrink-0">Adicionar</button>
                        </div>
                        <datalist id="dl-people"><template x-for="s in circles.people" :key="s"><option :value="s"></option></template></datalist>
                        <ul class="mt-2 flex flex-wrap gap-2" aria-label="Pessoas adicionadas">
                            <template x-for="(p, i) in form.people" :key="p">
                                <li class="inline-flex items-center gap-1.5 rounded-full bg-brand-500/20 py-1 pl-3 pr-1.5 text-sm text-slate-100">
                                    <span x-text="p"></span>
                                    <button type="button" @click="form.people.splice(i, 1)" :aria-label="'Remover ' + p" class="grid h-5 w-5 place-items-center rounded-full hover:bg-slate-50/15">
                                        <?= icon('close', 'h-3 w-3', '2.5') ?>
                                    </button>
                                </li>
                            </template>
                        </ul>
                        <p x-show="errors.people" x-cloak class="err" x-text="errors.people"></p>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-bold text-slate-50"><span class="grid h-6 w-6 place-items-center rounded-full bg-slate-50/15 text-xs text-slate-200">4</span>Tarefas extras <span class="text-sm font-normal text-slate-500">(opcional)</span></h2>
                        <p class="text-xs text-slate-500">Ex.: "Terminei no Profissional". Tier 1 a 3: quanto maior, mais XP.</p>
                    </div>
                    <button type="button" @click="addTask()" :disabled="tasks.length >= 20" class="btn-ghost shrink-0 btn-sm">+ Tarefa</button>
                </div>
                <ul class="mt-4 space-y-3">
                    <template x-for="(t, i) in tasks" :key="t.id">
                        <li class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                            <input type="text" maxlength="200" x-model="t.description" :aria-label="'Descrição da tarefa ' + (i + 1)" class="field min-w-0 flex-1" placeholder="Descreva a tarefa">
                            <select x-model.number="t.tier" :aria-label="'Tier da tarefa ' + (i + 1)" class="field !w-auto">
                                <option value="1" x-text="tierLabel(1)"></option>
                                <option value="2" x-text="tierLabel(2)"></option>
                                <option value="3" x-text="tierLabel(3)"></option>
                            </select>
                            <button type="button" @click="tasks.splice(i, 1)" :aria-label="'Remover tarefa ' + (i + 1)" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-50/5 hover:text-slate-50">
                                <?= icon('close', 'h-4 w-4') ?>
                            </button>
                        </li>
                    </template>
                </ul>
                <p x-show="errors.tasks" x-cloak class="err" x-text="errors.tasks"></p>
            </section>

            <!-- barra fixa: o que ainda falta e o XP, com o botão sempre à mão -->
            <div class="sticky bottom-0 z-20 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-50/10 bg-slate-950/90 px-4 py-3 pr-24 backdrop-blur sm:-mx-0 sm:rounded-2xl sm:border sm:px-5">
                <p class="min-w-0 text-sm" aria-live="polite">
                    <span x-show="missingLabels.length" class="text-slate-400">Falta: <strong class="text-slate-200" x-text="missingLabels.join(', ')"></strong></span>
                    <span x-show="!missingLabels.length" class="font-semibold text-ok">✓ Tudo pronto<span class="xp-ui font-normal text-slate-400" x-show="xpPreview" x-text="xpPreview ? ' · +' + xpPreview.total + ' XP' : ''"></span></span>
                </p>
                <button type="submit" :disabled="loading"
                        class="btn flex items-center justify-center gap-2 btn-lg">
                    <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="opacity-75"/></svg>
                    <span x-text="loading ? 'Salvando…' : '<?= $editing ? 'Salvar alterações' : 'Registrar jogo' ?>'"></span>
                </button>
            </div>
        </div>
    </form>
</main>

<script src="<?= e(asset('/assets/js/form-helpers.js')) ?>"></script>
<script>
function gameForm(me, game) {
    const todayStr = () => FormKit.todayStr();
    const blank = () => ({ title: '', platform: '', developer: '', publisher: '', genre: '', release_year: '', finished_at: todayStr(), started_at: '', hours: '', minutes: '', rating: null, difficulty: null, intro_by: '', intro_where: '', intro_age: '', played_where: '', music_title: '', music_url: '', people: [], used_save_states: false });
    let taskSeq = 0;
    const peerCache = {};

    return FormKit.mix({
        me,
        game,                    // jogo em edição (null ao criar)
        editing: !!game,
        removeCover: false,      // editando: o usuário tirou a capa atual
        rules: null,
        today: todayStr(),
        form: blank(),
        tasks: [],
        memOpen: false,
        personInput: '',
        circles: { people: [], places: [] },
        cover: null,
        coverUrl: null,
        dragging: false,
        errors: {},
        formError: '',
        loading: false,
        done: null,
        suggestions: { platforms: [], genres: [], companies: [] },
        fromFranchise: null,     // veio de uma franquia (atalho "Registrar este jogo")
        fromDropped: null,       // veio da lista de dropados ("Registrar como zerado"): ao salvar, sai de lá
        fromBacklog: null,       // veio do backlog ("Zerei!"): ao salvar, sai de lá
        quota: null,             // cota de notas altas (1 nota 10 a cada 10 jogos, 1 nota 11 a cada 50)
        backlogCover: false,     // a capa mostrada é a do backlog (o servidor copia ao salvar)
        startedFromBacklog: false,
        backlogRemoved: false,
        droppedRemoved: false,
        draftOffer: null,        // rascunho guardado neste navegador
        sources: [],             // outras versões do mesmo título (catálogo/backlog) para preencher os dados
        filling: false,
        peers: [], peersLoaded: false,

        fmt: (v) => new Intl.NumberFormat('pt-BR').format(v ?? 0),
        ratingWord(n) { return { 11: 'Lendário', 10: 'Obra-prima', 9: 'Excelente', 8: 'Muito bom', 7: 'Bom', 6: 'Ok', 5: 'Mediano', 4: 'Fraco', 3: 'Ruim', 2: 'Muito ruim', 1: 'O fundo do poço' }[n] ?? ''; },
        /** Campos obrigatórios ainda sem resposta (barra de salvar). */
        get missingLabels() {
            const f = this.form, out = [];
            if (!f.title.trim()) out.push('título');
            if (!f.platform.trim()) out.push('sistema');
            if (!f.genre.trim()) out.push('gênero');
            if (!f.release_year) out.push('lançamento');
            if (this.minutes < 1) out.push('tempo');
            if (!f.rating) out.push('nota');
            if (!f.difficulty) out.push('dificuldade');
            return out;
        },
        tierLabel(n) { return this.rules && !document.documentElement.dataset.calm ? `Tier ${n} · +${this.rules.tier[n]} XP` : `Tier ${n}`; },
        /** Link de voltar conforme de onde você veio. */
        get back() {
            const b = window.APP.base;
            if (this.editing) return { href: b + '/jogos/' + this.game.id, label: 'Voltar ao jogo' };
            if (this.fromBacklog) return { href: b + '/backlog', label: 'Backlog' };
            if (this.fromDropped) return { href: b + '/dropados', label: 'Dropados' };
            if (this.fromFranchise) return { href: b + '/franquias/' + this.fromFranchise, label: 'Franquia' };
            return { href: b + '/jogos', label: 'Catálogo' };
        },

        async init() {
            const prefilled = !this.game && new URLSearchParams(location.search).has('title');
            if (this.game) this.prefill(this.game); else this.prefillFromQuery();
            this.markClean();
            this.guardLeave();
            // rascunho: só ao cadastrar do zero
            if (!this.editing && !prefilled) this.draftOffer = this.readDraft();
            if (!this.editing) {
                this.$watch('form', () => this.writeDraft());
                this.$watch('tasks', () => this.writeDraft());
            }
            window.addEventListener('title-check', (e) => this.onTitleCheck(e.detail));
            try { this.rules = await api.get('/api/xp-rules'); } catch { /* prévia fica oculta */ }
            try { this.quota = (await api.get('/api/me/rating-quota')).data; } catch { /* sem cota: o servidor ainda confere */ }
            if (this.form.rating) this.calibrate(this.form.rating);
        },

        /** Cadastro aberto a partir de uma franquia, do backlog ou dos dropados: ?title=...&platform=...&from=ID */
        prefillFromQuery() {
            const q = new URLSearchParams(location.search);
            for (const k of ['title', 'platform', 'genre', 'developer', 'publisher']) if (q.get(k)) this.form[k] = q.get(k).slice(0, 200);
            const y = parseInt(q.get('release_year'), 10);
            if (y) this.form.release_year = y;
            if (/^\d+$/.test(q.get('from') || '')) this.fromFranchise = q.get('from');
            if (/^\d+$/.test(q.get('from_dropped') || '')) this.fromDropped = q.get('from_dropped');
            if (/^\d+$/.test(q.get('from_backlog') || '')) { this.fromBacklog = q.get('from_backlog'); this.loadFromBacklog(); }
        },

        /** "Zerei!": traz do backlog a capa e a data em que você começou a jogar. */
        async loadFromBacklog() {
            try {
                const { data } = await api.get('/api/backlog/' + this.fromBacklog);
                if (data.started_at && !this.form.started_at) { this.form.started_at = data.started_at; this.startedFromBacklog = true; }
                if (data.cover_url && !this.cover) { this.coverUrl = data.cover_url; this.backlogCover = true; }
            } catch { /* segue sem: o resto do formulário já veio pela URL */ }
            this.markClean();   // o que veio do backlog não conta como alteração sua
        },
        get playedDays() {
            const a = this.form.started_at, b = this.form.finished_at;
            if (!a || !b || a > b) return null;
            return Math.round((Date.UTC(+b.slice(0, 4), +b.slice(5, 7) - 1, +b.slice(8, 10)) - Date.UTC(+a.slice(0, 4), +a.slice(5, 7) - 1, +a.slice(8, 10))) / 86400000);
        },

        prefill(g) {
            this.form = {
                title: g.title, platform: g.platform, developer: g.developer || '', publisher: g.publisher || '', genre: g.genre || '', release_year: g.release_year || '',
                finished_at: g.finished_at, started_at: g.started_at || '', hours: Math.floor(g.playtime_minutes / 60) || '', minutes: g.playtime_minutes % 60 || '',
                rating: g.rating, difficulty: g.difficulty,
                used_save_states: !!g.save_states,
                intro_by: g.memory?.intro_by || '', intro_where: g.memory?.intro_where || '', intro_age: g.memory?.intro_age ?? '',
                played_where: g.memory?.played_where || '', music_title: g.memory?.music_title || '', music_url: g.memory?.music_url || '', people: [...(g.memory?.people || [])],
            };
            this.memOpen = !!(g.memory && (g.memory.intro_by || g.memory.intro_where || g.memory.intro_age !== null || g.memory.played_where || g.memory.music_title || g.memory.people.length));
            this.tasks = g.tasks.map((t) => ({ id: ++taskSeq, description: t.description, tier: t.tier }));
            this.coverUrl = g.cover_url;
        },

        // ---- rascunho ----
        restoreDraft() {
            const d = this.draftOffer;
            if (!d) return;
            this.form = { ...blank(), ...d.form };
            this.tasks = (d.tasks ?? []).map((t) => ({ id: ++taskSeq, description: t.description, tier: t.tier }));
            this.draftOffer = null;
            if (this.form.rating) this.calibrate(this.form.rating);
        },
        discardDraft() { this.dropDraft(); this.draftOffer = null; },

        // ---- preencher com outra versão do mesmo título ----
        onTitleCheck(d) {
            const out = [];
            for (const g of d?.catalog ?? []) if (!(this.editing && g.id === this.game.id)) out.push({ kind: 'game', id: g.id, title: g.title, platform: g.platform, where: 'no catálogo' });
            for (const b of d?.backlog ?? []) out.push({ kind: 'backlog', id: b.id, title: b.title, platform: b.platform ?? '', where: 'no backlog' });
            this.sources = out;
        },
        /** a primeira fonte, só quando ainda há algum campo vazio que ela pode preencher */
        get fillSource() {
            const f = this.form;
            if (!this.sources.length || (f.genre.trim() && f.release_year && f.developer.trim() && f.publisher.trim())) return null;
            return this.sources[0];
        },
        async fillFrom() {
            const s = this.fillSource;
            if (!s) return;
            this.filling = true;
            try {
                const { data } = await api.get((s.kind === 'game' ? '/api/games/' : '/api/backlog/') + s.id);
                const f = this.form;
                if (!f.genre.trim() && data.genre) f.genre = data.genre;
                if (!f.release_year && data.release_year) f.release_year = data.release_year;
                if (!f.developer.trim() && data.developer) f.developer = data.developer;
                if (!f.publisher.trim() && data.publisher) f.publisher = data.publisher;
                if (!f.platform.trim() && data.platform) f.platform = data.platform;
                this.sources = this.sources.slice(1);   // a próxima fonte (se houver) ainda pode completar o resto
                for (const k of ['genre', 'release_year', 'platform']) if (this.errors[k]) this.validate(k);
            } catch { /* segue sem preencher */ }
            this.filling = false;
        },

        // ---- calibrar a nota ----
        async calibrate(n) {
            this.peersLoaded = false;
            if (!peerCache[n]) {
                try { peerCache[n] = (await api.get(`/api/games?rating=${n}&per_page=4&sort=finished_at&dir=desc`)).data; } catch { peerCache[n] = []; }
            }
            if (this.form.rating !== n) return;   // trocou de nota enquanto buscava
            this.peers = peerCache[n].filter((g) => !(this.editing && g.id === this.game.id)).slice(0, 3);
            this.peersLoaded = true;
        },

        // ---- cota de notas altas ----
        /** Quantas notas n ainda cabem. Ao cadastrar, o próprio jogo entra na conta. Quem já tinha a nota (edição) mantém. */
        free(n) {
            const q = this.quota;
            if (!q || !q.per[n]) return Infinity;
            const games = q.games + (this.editing ? 0 : 1);
            const mine = this.editing && this.game?.rating === n ? 1 : 0;
            return Math.floor(games / q.per[n]) - (q.used[n] - mine);
        },
        locked(n) { return this.free(n) <= 0 && !(this.editing && this.game?.rating === n); },
        nextAt(n) {
            const q = this.quota, mine = this.editing && this.game?.rating === n ? 1 : 0;
            return (q.used[n] - mine + 1) * q.per[n];
        },
        /** Quantos jogos ainda faltam zerar para liberar a próxima nota n (o jogo que está sendo cadastrado conta como um deles). */
        missing(n) { return Math.max(1, this.nextAt(n) - this.quota.games); },
        lockText(n) { const m = this.missing(n); return `Nota ${n} bloqueada (1 a cada ${this.quota.per[n]} jogos zerados): faltam ${m} ${m === 1 ? 'jogo' : 'jogos'} para liberar.`; },
        quotaLine(n) {
            if (!this.quota) return '';
            const f = this.free(n);
            const m = this.missing(n);
            return f > 0 ? `Nota ${n}: ${f} ${f === 1 ? 'livre' : 'livres'}` : `Nota ${n}: faltam ${m} ${m === 1 ? 'jogo' : 'jogos'}`;
        },

        // ---- XP (prévia) ----
        get xpPreview() {
            if (!this.rules) return null;
            const r = this.rules;
            const items = [{ label: 'Jogo finalizado', xp: r.base }];
            if (this.form.difficulty && r.difficulty[this.form.difficulty] > 0) items.push({ label: 'Dificuldade ' + this.form.difficulty, xp: r.difficulty[this.form.difficulty] });
            for (const t of this.tasks) {
                if (t.description.trim() && r.tier[t.tier] > 0) items.push({ label: 'Tarefa tier ' + t.tier, xp: r.tier[t.tier] });
            }
            const full = items.reduce((s, i) => s + i.xp, 0);
            if (this.form.used_save_states) { // mesma regra do servidor: metade do total
                const half = Math.round(full / 2);
                return { items: [{ label: 'Total sem save state', xp: full }, { label: 'Save state: 50%', xp: half - full }], total: half };
            }
            return { items, total: full };
        },

        // ---- memórias ----
        async openMemories() {
            this.memOpen = !this.memOpen;
            if (this.memOpen && !this.circles.loaded) {
                this.circles.loaded = true;
                try {
                    const c = (await api.get('/api/circles')).data;
                    this.circles = { loaded: true, people: c.people.map((p) => p.name), places: c.places.map((p) => p.name) };
                } catch { /* sem sugestões */ }
            }
        },
        addPerson() {
            const n = this.personInput.trim().replace(/\s+/g, ' ');
            if (n && this.form.people.length < 15 && !this.form.people.some((p) => p.toLowerCase() === n.toLowerCase())) this.form.people.push(n.slice(0, 60));
            this.personInput = '';
        },

        // ---- tarefas ----
        addTask() { if (this.tasks.length < 20) this.tasks.push({ id: ++taskSeq, description: '', tier: 1 }); },

        // ---- validação (espelha as regras do servidor) ----
        get minutes() { return (Number(this.form.hours) || 0) * 60 + (Number(this.form.minutes) || 0); },
        checks: {
            title: (s) => { const v = s.form.title.trim(); return !v ? 'Informe o título.' : v.length > 200 ? 'Máximo de 200 caracteres.' : ''; },
            genre: (s) => { const v = s.form.genre.trim(); return !v ? 'Informe o gênero.' : v.length > 50 ? 'Máximo de 50 caracteres.' : ''; },
            release_year: (s) => {
                const y = Number(s.form.release_year), max = Number(s.today.slice(0, 4));
                if (!Number.isInteger(y) || y < 1950 || y > max) return `Informe um ano entre 1950 e ${max}.`;
                const fy = Number((s.form.finished_at || '').slice(0, 4));
                return fy && y > fy ? 'Não pode ser depois da finalização.' : '';
            },
            platform: (s) => { const v = s.form.platform.trim(); return !v ? 'Informe o sistema.' : v.length > 60 ? 'Máximo de 60 caracteres.' : ''; },
            finished_at: (s) => !s.form.finished_at ? 'Informe a data.' : s.form.finished_at > s.today ? 'A data não pode ser futura.' : '',
            started_at: (s) => !s.form.started_at ? '' : s.form.started_at > s.today ? 'A data não pode ser futura.' : (s.form.finished_at && s.form.started_at > s.form.finished_at) ? 'O início não pode ser depois da finalização.' : '',
            playtime: (s) => s.minutes < 1 ? 'Informe o tempo de jogo.' : s.minutes > 1000000 ? 'Tempo inválido.' : '',
            rating: (s) => s.form.rating ? '' : 'Escolha uma nota de 1 a 11.',
            difficulty: (s) => s.form.difficulty ? '' : 'Escolha a dificuldade.',
        },
        validate(field) {
            const msg = this.checks[field](this);
            if (msg) this.errors[field] = msg; else delete this.errors[field];
            return !msg;
        },

        // ---- envio ----
        async submit() {
            this.formError = '';
            const ok = Object.keys(this.checks).map((f) => this.validate(f)).every(Boolean);
            if (!ok) return this.focusFirstError();

            this.addPerson();
            const f = this.form;
            const payload = {
                title: f.title.trim(), platform: f.platform.trim(), developer: f.developer.trim(), publisher: f.publisher.trim(),
                genre: f.genre.trim(), release_year: f.release_year, finished_at: f.finished_at, started_at: f.started_at || '', playtime_minutes: this.minutes, rating: f.rating, difficulty: f.difficulty,
                tasks: this.tasks.filter((t) => t.description.trim()).map((t) => ({ description: t.description.trim(), tier: t.tier })),
                intro_by: f.intro_by.trim(), intro_where: f.intro_where.trim(), used_save_states: f.used_save_states, intro_age: f.intro_age, played_where: f.played_where.trim(), music_title: f.music_title.trim(), music_url: f.music_url.trim(), people: f.people,
            };

            this.loading = true;
            try {
                if (this.editing) return await this.saveEdit(payload);

                const fd = new FormData();
                for (const [k, v] of Object.entries(payload)) {
                    if (k === 'used_save_states') fd.append(k, v ? '1' : '0');
                    else if (k === 'tasks') v.forEach((t, i) => { fd.append(`tasks[${i}][description]`, t.description); fd.append(`tasks[${i}][tier]`, t.tier); });
                    else if (k === 'people') v.forEach((p, i) => fd.append(`people[${i}]`, p));
                    else fd.append(k, v);
                }
                if (this.fromBacklog) {
                    fd.append('from_backlog_id', this.fromBacklog);
                    if (!this.backlogCover && !this.cover) fd.append('skip_backlog_cover', '1');
                }
                if (this.cover) fd.append('cover', this.cover);

                const res = await api.post('/api/games', fd);
                if (this.fromBacklog) { // o jogo foi zerado: sai do backlog
                    try { await api.del('/api/backlog/' + this.fromBacklog); this.backlogRemoved = true; } catch { /* já tinha sido removido */ }
                }
                if (this.fromDropped) { // o jogo foi zerado: sai da lista de dropados
                    try { await api.del('/api/dropped/' + this.fromDropped); this.droppedRemoved = true; } catch { /* já tinha sido removido */ }
                }
                this.dropDraft();
                this.markClean();
                const colors = ['#fbbf24', '#22c55e', '#06b6d4', '#a855f7', '#ef4444', '#f97316'];
                const confetti = Array.from({ length: 26 }, (_, i) => `left:${4 + Math.random() * 92}%;background:${colors[i % colors.length]};--dx:${Math.round(Math.random() * 120 - 60)}px;animation-delay:${(Math.random() * 0.35).toFixed(2)}s`);
                this.done = { game: res.data, xp: res.xp, leveledUp: res.xp.level > this.me.xp.level, confetti };
                this.me.xp = res.xp;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (err) {
                if (err.status === 401) return (location.href = window.APP.base + '/login');
                const e = { ...err.errors };
                if (e.playtime_minutes) { e.playtime = e.playtime_minutes; delete e.playtime_minutes; }
                this.errors = e;
                if (!Object.keys(e).length) this.formError = err.message;
                this.focusFirstError();
            } finally {
                this.loading = false;
            }
        },

        /** Edição: salva os dados (PUT) e depois troca/remove a capa, se preciso. */
        async saveEdit(payload) {
            const id = this.game.id, base = window.APP.base;
            await api.put('/api/games/' + id, payload);
            try {
                if (this.cover) {
                    const fd = new FormData();
                    fd.append('cover', this.cover);
                    await api.post(`/api/games/${id}/cover`, fd);
                } else if (this.removeCover) {
                    await api.del(`/api/games/${id}/cover`);
                }
            } catch (err) {
                // os dados já foram salvos; só a capa falhou
                this.errors = { cover: err.errors?.cover || err.message };
                this.formError = 'Os dados foram salvos, mas a capa não pôde ser atualizada.';
                this.focusFirstError();
                return;
            }
            this.allowLeave();
            location.href = base + '/jogos/' + id;
        },

        /** "Adicionar outro": começa do zero, mas mantém o sistema e a data (costumam se repetir). */
        reset() {
            const keep = { platform: this.done?.game.platform ?? this.form.platform, finished_at: this.form.finished_at };
            this.clearCover();
            this.form = { ...blank(), ...keep };
            this.tasks = [];
            this.errors = {};
            this.formError = '';
            this.done = null;
            this.peers = []; this.peersLoaded = false; this.sources = [];
            this.fromBacklog = null; this.fromDropped = null; this.backlogCover = false; this.startedFromBacklog = false;
            this.backlogRemoved = false; this.droppedRemoved = false;
            this.markClean();
            window.scrollTo({ top: 0 });
            this.$nextTick(() => document.getElementById('title')?.focus());
        },
    }, { draftKey: game ? null : 'mgl.draft.game' });
}
</script>
