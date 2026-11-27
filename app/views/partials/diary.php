<?php
/**
 * Diário de bordo: notas datadas de um jogo do catálogo ('games') ou do backlog ('backlog'), com imagens anexadas.
 * As imagens anexadas também aparecem na galeria de screenshots (partials/shots.php). Use junto com partials/lightbox.php.
 * @var string $diaryKind  'games' | 'backlog' | 'dropped'
 * @var int    $diaryId
 * @var string $diaryHint  texto de apoio opcional
 */
$diaryHint = $diaryHint ?? 'Anote como foi: onde parou, o que achou, um momento marcante. Não precisa ser bonito.';
?>
<section id="diary" class="mt-4 scroll-mt-6 panel transition-[padding]" :class="compact ? 'px-5 py-3' : 'p-5'" aria-labelledby="h-diary" x-data='diary(<?= e(json_encode(['kind' => $diaryKind, 'id' => $diaryId])) ?>)'
         @shots-changed.window="$event.detail?.from !== 'diary' && reload()" @diary-new.window="startAdd(); $el.scrollIntoView({ behavior: 'smooth', block: 'start' })">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="h-diary" class="text-sm font-medium text-slate-300"><span x-show="compact" aria-hidden="true">📝 </span>Diário de bordo<span x-show="compact" class="font-normal text-slate-500"> · nenhuma nota ainda</span></h2>
        <button type="button" x-show="!adding" @click="startAdd()" class="btn-ghost btn-sm">+ Nova nota</button>
    </div>
    <p x-show="!compact" class="mt-1 text-xs text-slate-500"><?= e($diaryHint) ?></p>

    <form x-show="adding" x-cloak @submit.prevent="save()" class="mt-4 space-y-3 rounded-xl border border-slate-50/10 bg-slate-950/40 p-4">
        <div>
            <label for="note-date" class="lbl">Data</label>
            <input id="note-date" type="date" :max="today" x-model="draft.note_date" class="field !w-auto [color-scheme:dark]">
        </div>
        <div>
            <label for="note-body" class="lbl">Nota</label>
            <textarea id="note-body" rows="4" maxlength="2000" x-model="draft.body" x-ref="body" class="field" placeholder="Hoje eu…"></textarea>
            <p class="mt-1 text-right text-xs text-slate-500"><span x-text="draft.body.length"></span>/2000</p>
        </div>
        <div>
            <span class="lbl">Imagens <span class="font-normal text-slate-500">(opcional · JPEG, PNG ou WebP, até 8 MB cada)</span></span>
            <div class="flex flex-wrap items-center gap-2">
                <template x-for="(f, i) in files" :key="f.key">
                    <span class="relative block h-16 w-24 overflow-hidden rounded-md ring-1 ring-slate-50/15">
                        <img :src="f.preview" alt="" class="h-full w-full object-cover">
                        <button type="button" @click="dropFile(i)" :aria-label="'Tirar ' + f.file.name" class="absolute right-0.5 top-0.5 grid h-5 w-5 place-items-center rounded-full bg-black/70 text-xs text-white hover:bg-black">✕</button>
                    </span>
                </template>
                <button type="button" @click="$refs.noteFiles.click()" class="grid h-16 w-24 place-items-center rounded-md border border-dashed border-slate-50/20 text-xs text-slate-400 hover:border-brand-400 hover:text-slate-100">+ Imagem</button>
                <input x-ref="noteFiles" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" @change="addFiles($event.target.files); $event.target.value = ''">
            </div>
            <p x-show="draft.id && draft.shots.length" x-cloak class="mt-1 text-xs text-slate-500">Esta nota já tem <span x-text="draft.shots.length"></span> <span x-text="draft.shots.length === 1 ? 'imagem' : 'imagens'"></span>; as novas são somadas a elas.</p>
        </div>
        <p x-show="error" x-cloak role="alert" class="err" x-text="error"></p>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" :disabled="busy || !draft.body.trim()" class="btn"
                    x-text="busy ? (progress || 'Salvando…') : (draft.id ? 'Salvar nota' : 'Anotar')"></button>
            <button type="button" @click="cancel()" :disabled="busy" class="btn-ghost">Cancelar</button>
        </div>
    </form>

    <ol x-show="notes.length" class="mt-4 space-y-4 border-l border-slate-50/10 pl-4">
        <template x-for="n in notes" :key="n.id">
            <li class="relative">
                <span class="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full bg-brand-400 ring-4 ring-slate-900" aria-hidden="true"></span>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <time class="text-xs font-semibold uppercase tracking-wider text-brand-400" :datetime="n.note_date" x-text="UI.fmtDate(n.note_date)"></time>
                    <span class="flex gap-3 text-xs">
                        <button type="button" @click="edit(n)" class="text-slate-400 hover:text-slate-100 hover:underline">Editar</button>
                        <button type="button" @click="remove(n)" class="hover:underline" :class="confirmId === n.id ? 'font-semibold text-danger' : 'text-slate-400 hover:text-danger'" x-text="confirmId === n.id ? 'Apagar mesmo?' : 'Apagar'"></button>
                    </span>
                </div>
                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-200" x-text="n.body"></p>
                <ul x-show="n.shots?.length" class="mt-2 flex flex-wrap gap-2" :aria-label="'Imagens da nota de ' + UI.fmtDate(n.note_date)">
                    <template x-for="(s, i) in (n.shots ?? [])" :key="s.id">
                        <li>
                            <button type="button" @click="view(n, i)" class="block h-20 overflow-hidden rounded-md ring-1 ring-slate-50/15 transition hover:ring-brand-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                    :style="'aspect-ratio:' + s.width + '/' + s.height" :aria-label="'Ampliar imagem ' + (i + 1) + (s.caption ? ': ' + s.caption : '')">
                                <img :src="s.thumb" alt="" loading="lazy" class="h-full w-full object-cover">
                            </button>
                        </li>
                    </template>
                </ul>
            </li>
        </template>
    </ol>
    <p x-show="notes.length" x-cloak class="mt-3 text-xs text-slate-500">Apagar uma nota não apaga as imagens dela: elas continuam na galeria de screenshots.</p>
</section>

<script>
function diary({ kind, id }) {
    const today = () => { const d = new Date(); return new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
    const blank = () => ({ id: null, note_date: today(), body: '', shots: [] });
    const OK = ['image/jpeg', 'image/png', 'image/webp'];
    let seq = 0;
    return {
        notes: [], loaded: false, get compact() { return this.loaded && !this.notes.length && !this.adding; }, adding: false, busy: false, error: '', progress: '', confirmId: null, draft: blank(), today: today(), files: [],
        async init() { await this.reload(); },
        async reload() {
            try { this.notes = (await api.get(`/api/${kind}/${id}/notes`)).data; } catch { /* mostra vazio */ }
            this.loaded = true;
            // a página em volta pode usar as notas (ex.: a linha do tempo do jogo)
            window.dispatchEvent(new CustomEvent('notes-changed', { detail: { kind, id, notes: this.notes } }));
        },
        startAdd() { this.draft = blank(); this.clearFiles(); this.error = ''; this.adding = true; this.$nextTick(() => this.$refs.body?.focus()); },
        edit(n) { this.draft = { id: n.id, note_date: n.note_date, body: n.body, shots: n.shots ?? [] }; this.clearFiles(); this.error = ''; this.adding = true; this.$nextTick(() => this.$refs.body?.focus()); },
        cancel() { this.adding = false; this.error = ''; this.clearFiles(); },

        // ---- imagens escolhidas (ainda não enviadas) ----
        addFiles(list) {
            this.error = '';
            for (const f of list) {
                if (!OK.includes(f.type)) { this.error = f.name + ': use JPEG, PNG ou WebP.'; continue; }
                if (f.size > 8 * 1024 * 1024) { this.error = f.name + ': passa de 8 MB.'; continue; }
                if (this.files.length >= 10) { this.error = 'No máximo 10 imagens por nota de cada vez.'; break; }
                this.files.push({ key: ++seq, file: f, preview: URL.createObjectURL(f) });
            }
        },
        dropFile(i) { URL.revokeObjectURL(this.files[i].preview); this.files.splice(i, 1); },
        clearFiles() { this.files.forEach((f) => URL.revokeObjectURL(f.preview)); this.files = []; },

        async save() {
            this.busy = true; this.error = ''; this.progress = '';
            try {
                const body = { note_date: this.draft.note_date, body: this.draft.body };
                const { data } = this.draft.id ? await api.put('/api/notes/' + this.draft.id, body) : await api.post(`/api/${kind}/${id}/notes`, body);
                // uma imagem por requisição: arquivos grandes não estouram o limite do servidor
                const failed = [];
                for (let i = 0; i < this.files.length; i++) {
                    this.progress = `Enviando imagem ${i + 1} de ${this.files.length}…`;
                    const fd = new FormData();
                    fd.append('shots', this.files[i].file);
                    fd.append('note_id', data.id);
                    try { await api.post(`/api/${kind}/${id}/shots`, fd); } catch (e) { failed.push(this.files[i].file.name + ': ' + e.message); }
                }
                const sent = this.files.length > 0;
                this.clearFiles();
                await this.reload();
                if (sent) window.dispatchEvent(new CustomEvent('shots-changed', { detail: { from: 'diary' } }));
                if (failed.length) { this.error = 'A nota foi salva, mas algumas imagens não: ' + failed.join(' · '); this.draft = { ...this.draft, id: data.id }; }
                else this.adding = false;
            } catch (e) { this.error = e.errors?.body || e.errors?.note_date || e.message; }
            this.busy = false; this.progress = '';
        },
        async remove(n) {
            if (this.confirmId !== n.id) { this.confirmId = n.id; setTimeout(() => { if (this.confirmId === n.id) this.confirmId = null; }, 4000); return; }
            try {
                await api.del('/api/notes/' + n.id);
                this.notes = this.notes.filter(x => x.id !== n.id);
                window.dispatchEvent(new CustomEvent('notes-changed', { detail: { kind, id, notes: this.notes } }));
                if (n.shots?.length) window.dispatchEvent(new CustomEvent('shots-changed', { detail: { from: 'diary' } }));
            } catch (e) { this.error = e.message; }
            this.confirmId = null;
        },
        view(n, i) {
            window.dispatchEvent(new CustomEvent('lightbox', { detail: {
                index: i,
                items: n.shots.map((s) => ({ url: s.url, caption: s.caption, meta: 'Diário · ' + UI.fmtDate(n.note_date) })),
            } }));
        },
    };
}
</script>
