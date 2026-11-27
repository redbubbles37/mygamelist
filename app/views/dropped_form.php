<?php
/** @var array $me */
/** @var array|null $item  jogo dropado em edição (nulo = novo) */
$item = $item ?? null;
$editing = $item !== null;
?>
<main class="page page-narrow" x-data='droppedForm(<?= e(json_encode($item, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>
    <a href="<?= e(url('/dropados')) ?>" class="back-link mb-3">← Dropados</a>
    <header class="hero-box p-5 text-white sm:p-7">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="coverUrl" :src="coverUrl" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl grayscale-[.4]">
            <div class="absolute inset-0 bg-gradient-to-br from-black/85 via-black/70 to-rose-900/30"></div>
        </div>
        <p class="kicker-hero"><?= $editing ? 'Editar dropado' : 'Dropar jogo' ?> · ficha de abandono</p>
        <h1 class="mt-1 truncate t-hero" :class="form.title.trim() ? '' : 'text-white/40'" x-text="form.title.trim() || 'Qual jogo ficou pelo caminho?'"></h1>
        <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-white/70">
            <span x-show="form.platform.trim()" class="rounded-full bg-white/10 px-2.5 py-0.5" x-text="form.platform"></span>
            <span x-show="minutesTotal" class="rounded-full bg-white/10 px-2.5 py-0.5" x-text="'⏱ ' + UI.fmtTime(minutesTotal) + ' jogadas'"></span>
            <span x-show="form.dropped_at" class="rounded-full bg-white/10 px-2.5 py-0.5" x-text="'parou em ' + (form.dropped_at ? UI.fmtDate(form.dropped_at) : '')"></span>
            <span x-show="form.return_chance" class="rounded-full px-2.5 py-0.5 font-bold" :style="UI.chanceStyle(form.return_chance)" x-text="'Chance de voltar: ' + UI.chanceLabel(form.return_chance).toLowerCase()"></span>
            <span x-show="!form.platform.trim() && !form.return_chance">Dropados não dão XP nem entram no catálogo de zerados.</span>
        </p>
    </header>

    <form @submit.prevent="save" novalidate class="mt-8 grid gap-6 lg:grid-cols-[16rem_1fr]">
        <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
            <?php
            $caseBand = "form.dropped_at ? 'Largado ' + UI.fmtDate(form.dropped_at) : ''";
            $caseBadges = '<span x-show="form.return_chance" class="absolute left-2.5 top-2.5 rounded-lg px-2.5 py-1.5 text-2xs font-black uppercase leading-none tracking-wide shadow-lg ring-1 ring-black/40" :style="UI.chanceStyle(form.return_chance)" x-text="\'Volta: \' + ({ high: \'alta\', medium: \'média\', low: \'baixa\' }[form.return_chance] ?? \'\')"></span>'
                . '<span x-show="minutesTotal" class="absolute right-2.5 top-2.5 rounded-lg bg-black/70 px-2 py-1.5 text-2xs font-bold leading-none text-white shadow ring-1 ring-black/40" x-text="UI.fmtTime(minutesTotal)"></span>';
            require __DIR__ . '/partials/form_case.php';
            $checkMode = 'dropped'; $checkSelf = $editing ? (int) $item['id'] : null;
            require __DIR__ . '/partials/title_check.php';
            ?>
        </aside>

        <div class="flex flex-col gap-6">
            <div x-show="formError" x-cloak x-ref="formError" role="alert" class="alert-error" x-text="formError"></div>

            <section class="card space-y-4" aria-label="O jogo">
                <div class="flex items-center gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-50/10 text-sm font-black text-slate-200">1</span><div><h2 class="font-bold text-slate-50">O jogo</h2><p class="text-xs text-slate-500">Qual é e onde você jogava.</p></div></div>
                <div>
                    <label for="title" class="lbl">Título</label>
                    <input id="title" type="text" maxlength="200" x-model="form.title" class="field" :class="errors.title && 'border-danger/60'" placeholder="Ex.: Baldur's Gate 3">
                    <p x-show="errors.title" x-cloak class="err" x-text="errors.title"></p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="platform" class="lbl">Sistema</label>
                        <input id="platform" type="text" maxlength="60" list="dl-platforms" x-model="form.platform" @input="suggest('platforms', form.platform)" class="field" :class="errors.platform && 'border-danger/60'" placeholder="Ex.: PC, PS5" autocomplete="off">
                        <datalist id="dl-platforms"><template x-for="s in suggestions.platforms" :key="s"><option :value="s"></option></template></datalist>
                        <p x-show="errors.platform" x-cloak class="err" x-text="errors.platform"></p>
                    </div>
                    <div>
                        <label for="genre" class="lbl">Gênero <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="genre" type="text" maxlength="50" list="dl-genres" x-model="form.genre" @input="suggest('genres', form.genre)" class="field" :class="errors.genre && 'border-danger/60'" placeholder="Ex.: RPG" autocomplete="off">
                        <datalist id="dl-genres"><template x-for="s in suggestions.genres" :key="s"><option :value="s"></option></template></datalist>
                        <p x-show="errors.genre" x-cloak class="err" x-text="errors.genre"></p>
                    </div>
                    <div>
                        <label for="release_year" class="lbl">Ano de lançamento <span class="font-normal text-slate-500">(opcional)</span></label>
                        <input id="release_year" type="number" inputmode="numeric" min="1950" x-model="form.release_year" class="field" :class="errors.release_year && 'border-danger/60'" placeholder="Ex.: 2023">
                        <p x-show="errors.release_year" x-cloak class="err" x-text="errors.release_year"></p>
                    </div>
                </div>
            </section>

            <section class="card space-y-4" aria-label="A parada">
                <div class="flex items-center gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-50/10 text-sm font-black text-slate-200">2</span><div><h2 class="font-bold text-slate-50">A parada</h2><p class="text-xs text-slate-500">Quando, quanto tempo e por quê.</p></div></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="dropped_at" class="lbl">Quando dropou</label>
                        <input id="dropped_at" type="date" :max="today" x-model="form.dropped_at" class="field [color-scheme:dark]" :class="errors.dropped_at && 'border-danger/60'">
                        <p x-show="errors.dropped_at" x-cloak class="err" x-text="errors.dropped_at"></p>
                    </div>
                </div>
                <div>
                    <span class="lbl" id="l-time">Quanto você jogou <span class="font-normal text-slate-500">(opcional)</span></span>
                    <div class="flex items-center gap-2" role="group" aria-labelledby="l-time">
                        <input type="number" min="0" max="16000" inputmode="numeric" x-model.number="form.hours" class="field !w-28" placeholder="0" aria-label="Horas"><span class="text-sm text-slate-400">h</span>
                        <input type="number" min="0" max="59" inputmode="numeric" x-model.number="form.minutes" class="field !w-24" placeholder="0" aria-label="Minutos"><span class="text-sm text-slate-400">min</span>
                    </div>
                    <p x-show="errors.playtime_minutes" x-cloak class="err" x-text="errors.playtime_minutes"></p>
                </div>
                <div>
                    <label for="reason" class="lbl">Por que largou? <span class="font-normal text-slate-500">(opcional)</span></label>
                    <textarea id="reason" rows="3" maxlength="300" x-model="form.reason" class="field" :class="errors.reason && 'border-danger/60'" placeholder="Ex.: Perdi o ritmo no meio da história."></textarea>
                    <div class="mt-2 flex flex-wrap gap-1.5" aria-label="Motivos comuns">
                        <template x-for="r in reasons" :key="r">
                            <button type="button" @click="form.reason = form.reason.trim() ? form.reason.trim().replace(/\.?$/, '. ') + r : r" class="rounded-full border border-slate-50/10 px-2.5 py-1 text-xs text-slate-300 transition hover:border-brand-400/60 hover:text-brand-400" x-text="'+ ' + r"></button>
                        </template>
                    </div>
                    <p class="mt-1 text-right text-xs text-slate-500"><span x-text="form.reason.length"></span>/300</p>
                    <p x-show="errors.reason" x-cloak class="err" x-text="errors.reason"></p>
                </div>
            </section>

            <section class="card space-y-5" aria-label="Vai voltar?">
                <div class="flex items-center gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-50/10 text-sm font-black text-slate-200">3</span><div><h2 class="font-bold text-slate-50">Vai voltar?</h2><p class="text-xs text-slate-500">A chance define a coluna do jogo no quadro dos dropados.</p></div></div>
                <div>
                    <span class="lbl sr-only" id="l-chance">Chance de voltar a jogar</span>
                    <div class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-labelledby="l-chance">
                        <template x-for="c in chances" :key="c.v">
                            <button type="button" role="radio" :aria-checked="form.return_chance === c.v" @click="form.return_chance = c.v"
                                    class="rounded-xl border p-3.5 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                    :class="form.return_chance === c.v ? 'border-slate-50/60 bg-slate-900' : 'border-slate-50/10 bg-slate-950/40 hover:border-slate-50/30'">
                                <span class="inline-block rounded-md px-2.5 py-1 text-xs font-black uppercase tracking-wide ring-1 ring-black/30" :style="UI.chanceStyle(c.v)" x-text="c.l"></span>
                                <span class="mt-1.5 block text-sm text-slate-300" x-text="c.hint"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="errors.return_chance" x-cloak class="err" x-text="errors.return_chance"></p>
                </div>
            </section>

            <div class="sticky bottom-0 z-20 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-50/10 bg-slate-950/90 px-4 py-3 pr-24 backdrop-blur sm:-mx-0 sm:rounded-2xl sm:border sm:px-5">
                <p class="text-sm" aria-live="polite">
                    <span x-show="missingLabels.length" class="text-slate-400">Falta: <strong class="text-slate-200" x-text="missingLabels.join(', ')"></strong></span>
                    <span x-show="!missingLabels.length" class="font-semibold text-ok">✓ Tudo pronto<span x-show="!editing" class="font-normal text-slate-400"> · o diário e as screenshots vão junto</span></span>
                </p>
                <button type="submit" :disabled="loading" class="btn btn-lg">
                    <span x-text="loading ? 'Salvando…' : '<?= $editing ? 'Salvar alterações' : 'Adicionar aos dropados' ?>'"></span>
                </button>
            </div>
        </div>
    </form>
<?php if ($editing): $diaryKind = 'dropped'; $diaryId = (int) $item['id']; $diaryHint = 'As notas e as imagens de quando você jogava vieram junto. Se você voltar a jogar, elas voltam para o backlog com o jogo.'; require __DIR__ . '/partials/diary.php';
    $shotsKind = 'dropped'; $shotsId = (int) $item['id']; require __DIR__ . '/partials/shots.php'; require __DIR__ . '/partials/lightbox.php'; endif; ?>
</main>

<script src="<?= e(asset('/assets/js/form-helpers.js')) ?>"></script>
<script>
function droppedForm(item) {
    const base = window.APP.base;
    const todayStr = () => FormKit.todayStr();
    return FormKit.mix({
        item, editing: !!item, removeCover: false,
        today: todayStr(),
        form: { title: '', platform: '', genre: '', release_year: '', dropped_at: todayStr(), hours: '', minutes: '', return_chance: '', reason: '' },
        chances: [
            { v: 'high', l: 'Alta', hint: 'Quero muito voltar' },
            { v: 'medium', l: 'Média', hint: 'Talvez, um dia' },
            { v: 'low', l: 'Baixa', hint: 'Dificilmente volto' },
        ],
        cover: null, coverUrl: null, dragging: false,
        reasons: ['Perdi o ritmo', 'Ficou repetitivo', 'Difícil demais', 'Saiu um jogo novo', 'História não prendeu', 'Problemas técnicos'],
        errors: {}, formError: '', loading: false,
        suggestions: { platforms: [], genres: [] },

        fromBacklog: null,
        backlogCover: false,   // a capa mostrada é a do backlog (o servidor copia ao salvar)

        /** "Dropei": traz do backlog a capa do jogo. */
        async loadFromBacklog() {
            try {
                const { data } = await api.get('/api/backlog/' + this.fromBacklog);
                if (data.cover_url && !this.cover) { this.coverUrl = data.cover_url; this.backlogCover = true; }
            } catch { /* segue sem a capa */ }
            this.markClean();
        },

        init() {
            this.guardLeave();
            this.$nextTick(() => this.markClean());
            if (!item) {
                // aberto a partir do backlog ("Dropei"): vem preenchido e, ao salvar, o jogo sai de lá
                const q = new URLSearchParams(location.search);
                for (const k of ['title', 'platform', 'genre']) if (q.get(k)) this.form[k] = q.get(k).slice(0, 200);
                const y = parseInt(q.get('release_year'), 10);
                if (y) this.form.release_year = y;
                if (/^\d+$/.test(q.get('from_backlog') || '')) { this.fromBacklog = q.get('from_backlog'); this.loadFromBacklog(); }
                return;
            }
            this.form = {
                title: item.title, platform: item.platform, genre: item.genre || '', release_year: item.release_year || '', dropped_at: item.dropped_at,
                hours: Math.floor(item.playtime_minutes / 60) || '', minutes: item.playtime_minutes % 60 || '', return_chance: item.return_chance, reason: item.reason || '',
            };
            this.coverUrl = item.cover_url;
        },

        get minutesTotal() { return (Number(this.form.hours) || 0) * 60 + (Number(this.form.minutes) || 0); },
        /** Campos obrigatórios ainda sem resposta (barra de salvar). */
        get missingLabels() {
            const f = this.form, out = [];
            if (!f.title.trim()) out.push('título');
            if (!f.platform.trim()) out.push('sistema');
            if (!f.dropped_at) out.push('data');
            if (!f.return_chance) out.push('chance de voltar');
            return out;
        },
        validate() {
            const e = {}, f = this.form;
            if (!f.title.trim()) e.title = 'Informe o título.';
            if (!f.platform.trim()) e.platform = 'Informe o sistema.';
            if (!f.dropped_at) e.dropped_at = 'Informe a data.'; else if (f.dropped_at > this.today) e.dropped_at = 'A data não pode ser futura.';
            if (!f.return_chance) e.return_chance = 'Escolha a chance de voltar a jogar.';
            if (String(f.release_year).trim() !== '' && !(Number(f.release_year) >= 1950 && Number(f.release_year) <= new Date().getFullYear() + 1)) e.release_year = 'Ano inválido.';
            return e;
        },

        async save() {
            this.formError = '';
            this.errors = this.validate();
            if (Object.keys(this.errors).length) return this.focusFirstError();
            const f = this.form;
            const payload = {
                title: f.title.trim(), platform: f.platform.trim(), genre: f.genre.trim(), release_year: String(f.release_year).trim() === '' ? '' : Number(f.release_year),
                playtime_minutes: this.minutesTotal, dropped_at: f.dropped_at, return_chance: f.return_chance, reason: f.reason.trim(),
            };
            this.loading = true;
            try {
                if (this.editing) {
                    await api.put('/api/dropped/' + this.item.id, payload);
                    try {
                        if (this.cover) { const fd = new FormData(); fd.append('cover', this.cover); await api.post(`/api/dropped/${this.item.id}/cover`, fd); }
                        else if (this.removeCover) await api.del(`/api/dropped/${this.item.id}/cover`);
                    } catch (err) {
                        this.errors = { cover: err.errors?.cover || err.message };
                        this.formError = 'Os dados foram salvos, mas a capa não pôde ser atualizada.';
                        return this.focusFirstError();
                    }
                } else {
                    const fd = new FormData();
                    for (const [k, v] of Object.entries(payload)) fd.append(k, v);
                    if (this.cover) fd.append('cover', this.cover);
                    fd.append('from_backlog_id', this.fromBacklog ?? '');   // o servidor leva a capa e tira o jogo do backlog
                    if (!this.backlogCover && !this.cover) fd.append('skip_backlog_cover', '1');
                    await api.post('/api/dropped', fd);
                }
                this.allowLeave();
                location.href = base + '/dropados';
            } catch (err) {
                if (err.status === 401) return (location.href = base + '/login');
                this.errors = { ...err.errors };
                if (!Object.keys(this.errors).length) this.formError = err.message;
                this.focusFirstError();
            } finally { this.loading = false; }
        },

    });
}
</script>
