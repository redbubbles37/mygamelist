<?php
/** @var array $me */
/** @var array|null $item  jogo do backlog em edição (nulo = novo) */
$item = $item ?? null;
$editing = $item !== null;
/** @var int $playing_others  quantos outros jogos já estão em "jogando agora" */
$playingOthers = (int) ($playing_others ?? 0);
$maxPlaying = \App\Services\BacklogService::MAX_PLAYING;
?>
<main class="page page-narrow" x-data='backlogForm(<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>, <?= $playingOthers ?>, <?= $maxPlaying ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>
    <a href="<?= e(url('/backlog')) ?>" class="back-link mb-3">← Backlog</a>

    <!-- ========== Destaque: o jogo entrando na fila ========== -->
    <header class="hero-box p-5 text-white sm:p-7">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="coverUrl" :src="coverUrl" alt="" class="h-full w-full scale-150 object-cover opacity-30 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/65 to-cyan-800/30"></div>
        </div>
        <p class="kicker-hero"><?= $editing ? 'Editar jogo do backlog' : 'Adicionar ao backlog' ?> · não dá XP: só conta quando você zerar</p>
        <h1 class="mt-1 line-clamp-2 break-words t-hero" :class="bulk ? '' : (form.title.trim() ? '' : 'text-white/40')"
            x-text="bulk ? 'Vários jogos de uma vez' : (form.title.trim() || 'Qual jogo vai para a fila?')"></h1>
        <p class="mt-3 flex flex-wrap items-center gap-2 text-sm">
            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold" :class="form.status === 'playing' ? 'bg-ok text-black' : 'bg-white/10 text-white/85'" x-text="form.status === 'playing' ? '▶ Jogando agora' : 'Na fila'"></span>
            <span x-show="form.status === 'want'" class="rounded-full px-2.5 py-0.5 text-xs font-bold" :style="UI.priorityStyle(form.priority)" x-text="'Prioridade ' + UI.priorityLabel(form.priority).toLowerCase()"></span>
            <span x-show="form.platform.trim()" class="rounded-full px-2.5 py-0.5 text-xs font-bold" :style="UI.bandStyle(form.platform.trim()) + ';background:var(--band-bg);color:var(--band-fg)'" x-text="form.platform"></span>
            <span x-show="queueLabel" class="text-white/75" x-text="queueLabel"></span>
        </p>
    </header>

    <!-- Rascunho guardado neste navegador -->
    <div x-show="draftOffer" x-cloak role="status" class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-400/40 bg-brand-500/10 px-4 py-3 text-sm">
        <p class="text-slate-200">📝 Você deixou <strong class="text-slate-50" x-text="draftOffer?.form.title"></strong> pela metade. Continuar de onde parou?</p>
        <span class="flex gap-2">
            <button type="button" @click="restoreDraft()" class="btn btn-sm">Recuperar</button>
            <button type="button" @click="discardDraft()" class="btn-ghost btn-sm">Descartar</button>
        </span>
    </div>

    <p x-show="notice" x-cloak role="status" class="mt-6 rounded-xl border border-ok/30 bg-ok/10 px-4 py-3 text-sm text-ok" x-text="notice"></p>

    <form @submit.prevent="save()" novalidate class="mt-6 grid gap-6 lg:grid-cols-[16rem_1fr]">
        <!-- computador: o estojo ao vivo e o que o site já sabe sobre o título -->
        <aside class="hidden space-y-4 lg:sticky lg:top-6 lg:block lg:self-start">
            <?php
            $caseBand = "form.status === 'playing' ? 'Jogando' : 'Na fila'";
            $caseBadges = '<span x-show="form.status === \'want\'" class="absolute left-2.5 top-2.5 rounded-lg px-2.5 py-1.5 text-2xs font-black uppercase leading-none tracking-wide shadow-lg ring-1 ring-black/40" :style="UI.priorityStyle(form.priority)" x-text="\'Prioridade \' + UI.priorityLabel(form.priority).toLowerCase()"></span>'
                . '<span x-show="form.status === \'playing\'" class="absolute left-2.5 top-2.5 rounded-lg bg-ok px-2.5 py-1.5 text-2xs font-black uppercase leading-none tracking-wide text-black shadow-lg ring-1 ring-black/40">Jogando agora</span>';
            require __DIR__ . '/partials/form_case.php';
            $checkMode = 'backlog'; $checkSelf = $editing ? (int) $item['id'] : null;
            require __DIR__ . '/partials/title_check.php';
            ?>
        </aside>

        <div class="flex min-w-0 flex-col gap-6">
            <div x-show="formError" x-cloak x-ref="formError" role="alert" class="alert-error" x-text="formError"></div>

            <!-- 1. O jogo (ou vários) -->
            <section class="card space-y-4" aria-labelledby="h-b1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="h-b1" class="flex items-center gap-2 text-base font-bold text-slate-50"><span class="grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-xs text-on-brand">1</span><span x-text="bulk ? 'Os jogos' : 'O jogo'"></span></h2>
                    <?php if (!$editing): ?>
                    <div class="inline-flex rounded-lg bg-slate-50/5 p-1 text-xs font-semibold" role="group" aria-label="Quantos jogos">
                        <button type="button" @click="bulk = false" :aria-pressed="!bulk" class="seg-btn" :class="!bulk ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Um jogo</button>
                        <button type="button" @click="bulk = true" :aria-pressed="bulk" class="seg-btn" :class="bulk ? 'bg-brand-600 text-on-brand' : 'text-slate-400 hover:text-slate-50'">Vários de uma vez</button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- vários: um por linha -->
                <div x-show="bulk" x-cloak>
                    <label for="bulk-list" class="lbl">Um jogo por linha</label>
                    <textarea id="bulk-list" x-model="bulkText" rows="7" class="field font-mono text-sm" placeholder="Hades II | 2025&#10;Hollow Knight: Silksong&#10;Metaphor: ReFantazio | 2024"></textarea>
                    <p class="mt-1 text-xs text-slate-500">Formato: <code class="rounded bg-slate-50/10 px-1">Título | Ano</code> (o ano é opcional). O sistema, o gênero e a prioridade abaixo valem para todos. <strong class="text-slate-300" x-text="bulkLines.length + (bulkLines.length === 1 ? ' jogo' : ' jogos')"></strong></p>
                    <ul x-show="bulkResults.length" class="mt-3 space-y-1 text-sm">
                        <template x-for="r in bulkResults" :key="r.title">
                            <li class="flex items-center gap-2" :class="r.ok ? 'text-ok' : (r.skip ? 'text-gold-500' : 'text-danger')"><span aria-hidden="true" x-text="r.ok ? '✓' : (r.skip ? '↷' : '✕')"></span><span class="text-slate-200" x-text="r.title"></span><span class="text-xs" x-text="r.msg"></span></li>
                        </template>
                    </ul>
                </div>

                <!-- um jogo -->
                <div x-show="!bulk" class="space-y-4">
                    <!-- celular: a capa numa linha compacta -->
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
                        <input id="title" type="text" maxlength="200" x-model="form.title" class="field" :class="errors.title && 'border-danger/60'" placeholder="Ex.: Clair Obscur: Expedition 33">
                        <p x-show="errors.title" x-cloak class="err" x-text="errors.title"></p>
                    </div>
                    <div class="lg:hidden"><?php require __DIR__ . '/partials/title_check.php'; ?></div>

                    <div x-show="fillSource" x-cloak class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-brand-400/30 bg-brand-500/10 px-3 py-2 text-sm">
                        <span class="text-slate-200" x-text="fillSource ? 'Você já zerou ' + fillSource.title + ' (' + fillSource.platform + ').' : ''"></span>
                        <button type="button" @click="fillFrom()" :disabled="filling" class="btn btn-sm" x-text="filling ? 'Preenchendo…' : 'Usar gênero e ano'"></button>
                    </div>
                </div>

                <div class="grid gap-4" :class="bulk ? 'sm:grid-cols-2' : 'sm:grid-cols-3'">
                    <div>
                        <label for="platform" class="lbl">Sistema <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="platform" type="text" maxlength="60" list="dl-platforms" x-model="form.platform" @input="suggest('platforms', form.platform)" class="field" :class="errors.platform && 'border-danger/60'" placeholder="Ex.: PC" autocomplete="off">
                        <datalist id="dl-platforms"><template x-for="s in suggestions.platforms" :key="s"><option :value="s"></option></template></datalist>
                        <p x-show="errors.platform" x-cloak class="err" x-text="errors.platform"></p>
                    </div>
                    <div>
                        <label for="genre" class="lbl">Gênero <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="genre" type="text" maxlength="50" list="dl-genres" x-model="form.genre" @input="suggest('genres', form.genre)" class="field" :class="errors.genre && 'border-danger/60'" placeholder="Ex.: JRPG" autocomplete="off">
                        <datalist id="dl-genres"><template x-for="s in suggestions.genres" :key="s"><option :value="s"></option></template></datalist>
                    </div>
                    <div x-show="!bulk">
                        <label for="release_year" class="lbl">Lançamento <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="release_year" type="number" inputmode="numeric" min="1950" :max="maxYear" x-model="form.release_year" @blur="checkYear()" class="field" :class="errors.release_year && 'border-danger/60'" placeholder="Ex.: 2025">
                        <p x-show="errors.release_year" x-cloak class="err" x-text="errors.release_year"></p>
                    </div>
                </div>
            </section>

            <!-- 2. Situação -->
            <section class="card space-y-5" aria-labelledby="h-b2">
                <h2 id="h-b2" class="flex items-center gap-2 text-base font-bold text-slate-50"><span class="grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-xs text-on-brand">2</span>Situação</h2>
                <div>
                    <span class="lbl sr-only" id="l-status">Situação</span>
                    <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-labelledby="l-status" @keydown="radioKeys($event)">
                        <template x-for="s in statuses" :key="s.v">
                            <button type="button" role="radio" :aria-checked="form.status === s.v" @click="form.status = s.v" :tabindex="form.status === s.v ? 0 : -1"
                                    :disabled="s.v === 'playing' && (full || bulk)" class="rounded-xl border p-3.5 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="form.status === s.v ? 'border-slate-50/60 bg-slate-900' : 'border-slate-50/10 bg-slate-950/40 hover:border-slate-50/30'">
                                <span class="block text-base font-bold text-slate-50" x-text="s.l"></span>
                                <span class="mt-0.5 block text-sm text-slate-400" x-text="s.v === 'playing' && bulk ? 'Vários de uma vez entram só na fila' : (s.v === 'playing' && full ? 'Sem vaga: você já joga ' + max + ' jogos' : s.hint)"></span>
                            </button>
                        </template>
                    </div>
                    <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-400">
                        <span class="flex gap-1" aria-hidden="true"><template x-for="i in max" :key="i"><span class="h-2 w-5 rounded-full" :class="i <= others ? 'bg-ok' : (i === others + 1 && form.status === 'playing' ? 'bg-ok/50' : 'bg-slate-50/15')"></span></template></span>
                        <span x-text="others + ' de ' + max + ' vagas de “jogando agora” ocupadas' + (form.status === 'playing' && !full ? ' (este ocupa mais uma)' : '')"></span>
                    </p>
                    <p x-show="errors.status" x-cloak class="err" x-text="errors.status"></p>
                </div>
                <div x-show="form.status === 'want'" x-cloak>
                    <span class="lbl" id="l-prio">Prioridade</span>
                    <div class="inline-flex flex-wrap gap-2" role="radiogroup" aria-labelledby="l-prio" @keydown="radioKeys($event)">
                        <template x-for="p in priorities" :key="p.v">
                            <button type="button" role="radio" :aria-checked="form.priority === p.v" @click="form.priority = p.v" :tabindex="form.priority === p.v ? 0 : -1" class="rounded-lg border px-4 py-2 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                    :style="form.priority === p.v ? UI.priorityStyle(p.v) : ''" :class="form.priority === p.v ? 'border-transparent' : 'border-slate-50/10 text-slate-300 hover:border-slate-50/30'" x-text="p.l"></button>
                        </template>
                    </div>
                </div>

                <!-- onde ele entra: as capas vizinhas na fila (ou na mesa) -->
                <div x-show="!editing && !bulk && queue" x-cloak class="rounded-xl border border-slate-50/10 bg-slate-950/40 p-3">
                    <p class="kicker" x-text="form.status === 'playing' ? 'Na mesa com' : 'Lugar na fila'"></p>
                    <p class="mt-1 text-sm text-slate-200" x-text="queueLabel"></p>
                    <ol class="mt-3 flex items-end gap-2 overflow-x-auto pb-1">
                        <template x-for="g in (queue?.before ?? [])" :key="g.id">
                            <li class="w-12 shrink-0 opacity-70" :title="g.title">
                                <span class="relative block aspect-[3/4] overflow-hidden rounded bg-slate-900 ring-1 ring-slate-50/10">
                                    <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center text-2xs font-bold text-slate-400" x-text="UI.initials(g.title)"></span>
                                </span>
                            </li>
                        </template>
                        <li class="w-14 shrink-0">
                            <span class="relative block aspect-[3/4] overflow-hidden rounded bg-gradient-to-br from-neutral to-brand-600/60 ring-2 ring-brand-400">
                                <img x-show="coverUrl" :src="coverUrl" alt="" class="absolute inset-0 h-full w-full object-cover">
                                <span x-show="!coverUrl" class="absolute inset-0 grid place-items-center text-sm font-bold text-white/85" x-text="form.title.trim() ? UI.initials(form.title.trim()) : '?'"></span>
                            </span>
                            <span class="mt-1 block text-center text-2xs font-bold text-brand-400">este</span>
                        </li>
                    </ol>
                </div>

                <div x-show="form.status === 'playing'" x-cloak>
                    <label for="started_at" class="lbl">Comecei em</label>
                    <input id="started_at" type="date" :max="today" x-model="form.started_at" class="field !w-56 [color-scheme:dark]" :class="errors.started_at && 'border-danger/60'">
                    <p x-show="errors.started_at" x-cloak class="err" x-text="errors.started_at"></p>
                </div>
                <div x-show="!bulk">
                    <label for="note" class="lbl">Anotação <span class="font-normal text-slate-500">(opcional)</span></label>
                    <textarea id="note" rows="3" maxlength="300" x-model="form.note" class="field" placeholder="Ex.: Quem me indicou, por que quero jogar…"></textarea>
                    <p class="mt-1 text-right text-xs text-slate-500"><span x-text="form.note.length"></span>/300</p>
                    <p x-show="errors.note" x-cloak class="err" x-text="errors.note"></p>
                </div>
            </section>

            <div class="sticky bottom-0 z-20 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-50/10 bg-slate-950/90 px-4 py-3 pr-24 backdrop-blur sm:-mx-0 sm:rounded-2xl sm:border sm:px-5">
                <p class="min-w-0 text-sm" aria-live="polite">
                    <template x-if="bulk"><span :class="bulkLines.length ? 'font-semibold text-ok' : 'text-slate-400'" x-text="bulkLines.length ? '✓ ' + bulkLines.length + (bulkLines.length === 1 ? ' jogo vai' : ' jogos vão') + ' para a fila' : 'Falta: a lista de jogos'"></span></template>
                    <template x-if="!bulk"><span>
                        <span x-show="!form.title.trim()" class="text-slate-400">Falta: <strong class="text-slate-200">título</strong></span>
                        <span x-show="form.title.trim()" class="font-semibold text-ok" x-text="'✓ ' + (form.status === 'playing' ? 'Vai direto para “jogando agora”' : 'Entra na fila com prioridade ' + UI.priorityLabel(form.priority).toLowerCase())"></span>
                    </span></template>
                </p>
                <div class="flex gap-2">
                    <?php if (!$editing): ?>
                    <button type="button" x-show="!bulk" @click="save(true)" :disabled="loading" class="rounded-xl border border-slate-50/15 px-4 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-slate-50/5 disabled:opacity-60" title="Salva e já abre o próximo"><span class="sm:hidden">+ Outro</span><span class="hidden sm:inline">Adicionar e continuar</span></button>
                    <?php endif; ?>
                    <button type="submit" :disabled="loading" class="btn btn-lg">
                        <span x-text="loading ? (bulk ? 'Adicionando ' + bulkDone + '/' + bulkLines.length + '…' : 'Salvando…') : (bulk ? 'Adicionar ' + bulkLines.length + ' ao backlog' : '<?= $editing ? 'Salvar alterações' : 'Adicionar ao backlog' ?>')"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>

<?php if ($editing): $diaryKind = 'backlog'; $diaryId = (int) $item['id']; $diaryHint = 'Anote enquanto joga: onde parou, o que está achando. Quando você zerar ou dropar, as notas e as imagens vão junto com o jogo.'; require __DIR__ . '/partials/diary.php';
    $shotsKind = 'backlog'; $shotsId = (int) $item['id']; require __DIR__ . '/partials/shots.php'; require __DIR__ . '/partials/lightbox.php'; endif; ?>
</main>

<script src="<?= e(asset('/assets/js/form-helpers.js')) ?>"></script>
<script>
function backlogForm(item, others, max) {
    const base = window.APP.base;
    const todayStr = () => FormKit.todayStr();
    const RANK = { high: 3, medium: 2, low: 1 };
    const blank = () => ({ title: '', platform: '', genre: '', release_year: '', status: 'want', priority: 'medium', started_at: todayStr(), note: '' });
    return FormKit.mix({
        item, editing: !!item, removeCover: false, today: todayStr(), others, max,
        get full() { return this.others >= this.max; },
        get maxYear() { return new Date().getFullYear() + 1; },
        form: blank(),
        statuses: [{ v: 'want', l: 'Quero jogar', hint: 'Fica na fila do backlog' }, { v: 'playing', l: 'Jogando agora', hint: 'Já comecei' }],
        priorities: [{ v: 'high', l: 'Alta' }, { v: 'medium', l: 'Média' }, { v: 'low', l: 'Baixa' }],
        cover: null, coverUrl: null, dragging: false, errors: {}, formError: '', loading: false, notice: '',
        suggestions: { platforms: [], genres: [] },
        draftOffer: null, sources: [], filling: false,
        list: null,              // o backlog atual (para mostrar o lugar na fila)
        bulk: false, bulkText: '', bulkResults: [], bulkDone: 0,

        init() {
            if (item) {
                this.form = {
                    title: item.title, platform: item.platform || '', genre: item.genre || '', release_year: item.release_year || '', status: item.status,
                    priority: item.priority, started_at: item.started_at || todayStr(), note: item.note || '',
                };
                this.coverUrl = item.cover_url;
            }
            this.markClean();
            this.guardLeave();
            if (!item) {
                this.draftOffer = this.readDraft();
                this.$watch('form', () => this.writeDraft());
                this.loadQueue();
            }
            window.addEventListener('title-check', (e) => {
                this.sources = (e.detail?.catalog ?? []).map((g) => ({ id: g.id, title: g.title, platform: g.platform }));
            });
        },

        // ---- rascunho ----
        restoreDraft() { if (this.draftOffer) { this.form = { ...blank(), ...this.draftOffer.form }; if (this.full && this.form.status === 'playing') this.form.status = 'want'; } this.draftOffer = null; },
        discardDraft() { this.dropDraft(); this.draftOffer = null; },

        // ---- usar os dados de um jogo que você já zerou ----
        get fillSource() {
            if (this.bulk || !this.sources.length || (this.form.genre.trim() && this.form.release_year)) return null;
            return this.sources[0];
        },
        async fillFrom() {
            const s = this.fillSource;
            if (!s) return;
            this.filling = true;
            try {
                const { data } = await api.get('/api/games/' + s.id);
                if (!this.form.genre.trim() && data.genre) this.form.genre = data.genre;
                if (!this.form.release_year && data.release_year) this.form.release_year = data.release_year;
                this.sources = this.sources.slice(1);
            } catch { /* segue sem */ }
            this.filling = false;
        },

        // ---- lugar na fila ----
        async loadQueue() {
            try { this.list = (await api.get('/api/backlog')).data; } catch { this.list = null; }
        },
        /** o novo jogo entra no fim do grupo da prioridade dele (a fila é por prioridade e depois por data de entrada) */
        get queue() {
            if (!this.list) return null;
            if (this.form.status === 'playing') {
                const on = this.list.filter((g) => g.status === 'playing');
                return { pos: on.length + 1, before: on.slice(0, 4) };
            }
            const since = (g) => g.queued_at ?? g.added_at ?? '';
            const want = this.list.filter((g) => g.status === 'want')
                .sort((a, b) => RANK[b.priority] - RANK[a.priority] || since(a).localeCompare(since(b)) || a.id - b.id);
            const ahead = want.filter((g) => RANK[g.priority] >= RANK[this.form.priority]);
            const same = ahead.filter((g) => g.priority === this.form.priority);
            return { pos: ahead.length + 1, inGroup: same.length + 1, total: want.length + 1, before: ahead.slice(-4) };
        },
        get queueLabel() {
            const q = this.queue;
            if (!q || this.editing || this.bulk) return '';
            if (this.form.status === 'playing') return q.pos > this.max ? '' : `Vai para a mesa: ${q.pos}º de ${this.max} jogos ao mesmo tempo.`;
            return `Entra como ${q.inGroup}º da prioridade ${UI.priorityLabel(this.form.priority).toLowerCase()} · ${q.pos}º de ${q.total} na fila.`;
        },

        // ---- vários de uma vez ----
        get bulkLines() {
            return this.bulkText.split('\n').map((l) => l.trim()).filter(Boolean).map((l) => {
                const [t, y] = l.split('|').map((x) => x.trim());
                return { title: t.slice(0, 200), year: /^\d{4}$/.test(y || '') ? Number(y) : '' };
            }).filter((x) => x.title);
        },
        async saveBulk() {
            const lines = this.bulkLines;
            if (!lines.length) { this.formError = 'Escreva pelo menos um jogo (um por linha).'; return; }
            this.loading = true; this.bulkResults = []; this.bulkDone = 0; this.formError = '';
            for (const l of lines) {
                const fd = new FormData();
                const payload = { title: l.title, platform: this.form.platform.trim(), genre: this.form.genre.trim(), release_year: l.year, status: 'want', priority: this.form.priority, started_at: '', note: '' };
                for (const [k, v] of Object.entries(payload)) fd.append(k, v);
                try { await api.post('/api/backlog', fd); this.bulkResults.push({ title: l.title, ok: true, msg: '' }); }
                catch (err) {
                    if (err.status === 401) return (location.href = base + '/login');
                    this.bulkResults.push({ title: l.title, ok: false, skip: err.status === 409, msg: err.status === 409 ? 'já estava no backlog' : (Object.values(err.errors || {})[0] || err.message) });
                }
                this.bulkDone++;
            }
            this.loading = false;
            const ok = this.bulkResults.filter((r) => r.ok).length;
            this.notice = `${ok} de ${lines.length} ${lines.length === 1 ? 'jogo adicionado' : 'jogos adicionados'} ao backlog.`;
            this.bulkText = this.bulkResults.filter((r) => !r.ok && !r.skip).map((r) => r.title).join('\n');   // fica só o que falhou
            this.markClean();
            this.loadQueue();
        },

        checkYear() {
            const y = String(this.form.release_year).trim();
            if (y !== '' && !(Number(y) >= 1950 && Number(y) <= this.maxYear)) { this.errors.release_year = `Informe um ano entre 1950 e ${this.maxYear}.`; return false; }
            delete this.errors.release_year;
            return true;
        },

        /** again = "Adicionar e continuar": salva e limpa o formulário, mantendo o sistema, a situação e a prioridade. */
        async save(again = false) {
            if (this.bulk) return this.saveBulk();
            this.formError = ''; this.errors = {}; this.notice = '';
            const f = this.form;
            if (!f.title.trim()) { this.errors = { title: 'Informe o título.' }; return this.focusFirstError(); }
            if (!this.checkYear()) return this.focusFirstError();
            const payload = {
                title: f.title.trim(), platform: f.platform.trim(), genre: f.genre.trim(), release_year: String(f.release_year).trim() === '' ? '' : Number(f.release_year),
                status: f.status, priority: f.priority, started_at: f.status === 'playing' ? f.started_at : '', note: f.note.trim(),
            };
            this.loading = true;
            try {
                if (this.editing) {
                    await api.put('/api/backlog/' + this.item.id, payload);
                    try {
                        if (this.cover) { const fd = new FormData(); fd.append('cover', this.cover); await api.post(`/api/backlog/${this.item.id}/cover`, fd); }
                        else if (this.removeCover) await api.del(`/api/backlog/${this.item.id}/cover`);
                    } catch (err) {
                        this.errors = { cover: err.errors?.cover || err.message };
                        this.formError = 'Os dados foram salvos, mas a capa não pôde ser atualizada.';
                        return this.focusFirstError();
                    }
                } else {
                    const fd = new FormData();
                    for (const [k, v] of Object.entries(payload)) fd.append(k, v);
                    if (this.cover) fd.append('cover', this.cover);
                    await api.post('/api/backlog', fd);
                }
                this.dropDraft();
                if (again) {
                    if (f.status === 'playing') this.others++;
                    this.notice = `✓ ${payload.title} foi para o backlog. Pode adicionar o próximo.`;
                    this.clearCover();
                    this.form = { ...blank(), platform: f.platform, priority: f.priority, status: f.status === 'playing' && !this.full ? 'playing' : 'want' };
                    this.sources = [];
                    this.markClean();
                    this.loadQueue();
                    this.$nextTick(() => document.getElementById('title')?.focus());
                    return;
                }
                this.allowLeave();
                location.href = base + '/backlog';
            } catch (err) {
                if (err.status === 401) return (location.href = base + '/login');
                this.errors = { ...err.errors };
                if (!Object.keys(this.errors).length) this.formError = err.message;
                this.focusFirstError();
            } finally { this.loading = false; }
        },
    }, { draftKey: item ? null : 'mgl.draft.backlog' });
}
</script>
