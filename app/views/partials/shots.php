<?php
/**
 * Galeria de screenshots de um jogo do catálogo ('games') ou do backlog ('backlog'). Use junto com partials/lightbox.php.
 * @var string $shotsKind  'games' | 'backlog' | 'dropped'
 * @var int    $shotsId
 */
?>
<section class="mt-4 panel transition-[padding]" :class="compact ? 'px-5 py-3' : 'p-5'" aria-labelledby="h-shots" x-data='shotGallery(<?= e(json_encode(['kind' => $shotsKind, 'id' => $shotsId])) ?>)'
         @shots-changed.window="$event.detail?.from !== 'gallery' && load()">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="h-shots" class="text-sm font-medium text-slate-300"><span x-show="compact" aria-hidden="true">🖼 </span>Screenshots <span x-show="shots.length" class="text-slate-500" x-text="'(' + shots.length + ')'"></span><span x-show="compact" class="font-normal text-slate-500"> · nenhuma ainda (arraste imagens aqui)</span></h2>
        <button type="button" @click="$refs.shotFiles.click()" :disabled="busy" class="btn-ghost btn-sm">+ Enviar imagens</button>
        <input x-ref="shotFiles" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" @change="upload($event.target.files); $event.target.value = ''">
    </div>

    <div @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="drag = false; upload($event.dataTransfer.files)"
         class="rounded-xl border-2 border-dashed transition" :class="[drag ? 'border-brand-400 bg-brand-500/10' : 'border-transparent', compact && !drag ? 'mt-0 p-0' : 'mt-3 p-1']">
        <p x-show="drag && !shots.length" x-cloak class="rounded-lg p-6 text-center text-sm text-slate-500">
            Arraste imagens para cá ou use “Enviar imagens”. JPEG, PNG ou WebP, até 8 MB cada. As imagens anexadas no diário aparecem aqui também.
        </p>
        <p x-show="busy" x-cloak role="status" class="p-2 text-sm text-slate-300" x-text="progress"></p>
        <p x-show="error" x-cloak role="alert" class="p-2 text-sm text-danger" x-text="error"></p>

        <ul x-show="shots.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <template x-for="(s, i) in shots" :key="s.id">
                <li class="group relative">
                    <button type="button" @click="view(i)" class="block aspect-video w-full overflow-hidden rounded-lg bg-slate-950 ring-1 ring-slate-50/10 transition hover:ring-brand-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                            :aria-label="'Ampliar screenshot ' + (i + 1) + (s.caption ? ': ' + s.caption : '')">
                        <img :src="s.thumb" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                    </button>
                    <span x-show="s.note_id" class="pointer-events-none absolute left-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-2xs font-semibold text-white" x-text="'Diário · ' + (s.note_date ? UI.fmtDate(s.note_date) : '')"></span>
                    <div class="mt-1 flex items-start gap-1">
                        <template x-if="editId !== s.id">
                            <p class="min-w-0 flex-1 truncate text-xs" :class="s.caption ? 'text-slate-300' : 'italic text-slate-500'" x-text="s.caption || 'Sem legenda'"></p>
                        </template>
                        <template x-if="editId === s.id">
                            <form class="flex min-w-0 flex-1 gap-1" @submit.prevent="saveCaption(s)">
                                <input type="text" maxlength="200" x-model="captionDraft" x-init="$nextTick(() => $el.focus())" @keydown.escape.stop="editId = null" class="min-w-0 flex-1 rounded border border-slate-50/15 bg-slate-950 px-2 py-1 text-xs text-slate-50" aria-label="Legenda">
                                <button type="submit" class="rounded bg-brand-600 px-2 text-xs font-semibold text-on-brand">OK</button>
                            </form>
                        </template>
                        <button type="button" x-show="editId !== s.id" @click="editCaption(s)" class="grid h-6 w-6 shrink-0 place-items-center rounded text-slate-400 hover:bg-slate-50/10 hover:text-slate-100" :aria-label="'Editar legenda da screenshot ' + (i + 1)"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg></button>
                        <button type="button" x-show="editId !== s.id" @click="remove(s)" class="grid h-6 min-w-6 shrink-0 place-items-center rounded px-1 text-xs hover:bg-slate-50/10" :class="confirmId === s.id ? 'font-bold text-danger' : 'text-slate-400 hover:text-danger'"
                                :aria-label="confirmId === s.id ? 'Confirmar: apagar a screenshot' : 'Apagar screenshot ' + (i + 1)">
                            <span x-show="confirmId === s.id">Apagar?</span>
                            <svg x-show="confirmId !== s.id" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg>
                        </button>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</section>

<script>
function shotGallery({ kind, id }) {
    const OK = ['image/jpeg', 'image/png', 'image/webp'];
    const changed = () => window.dispatchEvent(new CustomEvent('shots-changed', { detail: { from: 'gallery' } }));
    return {
        shots: [], loaded: false, get compact() { return this.loaded && !this.shots.length && !this.busy && !this.error; }, busy: false, drag: false, error: '', progress: '', editId: null, captionDraft: '', confirmId: null,
        async init() { await this.load(); },
        async load() {
            try { this.shots = (await api.get(`/api/${kind}/${id}/shots`)).data; } catch (e) { this.error = e.message; }
            this.loaded = true;
            this.emit();
        },
        async upload(list) {
            const files = [...list];
            if (!files.length) return;
            this.busy = true; this.error = '';
            const failed = [];
            for (let i = 0; i < files.length; i++) {
                const f = files[i];
                this.progress = `Enviando ${i + 1} de ${files.length}: ${f.name}`;
                if (!OK.includes(f.type)) { failed.push(f.name + ': use JPEG, PNG ou WebP'); continue; }
                if (f.size > 8 * 1024 * 1024) { failed.push(f.name + ': passa de 8 MB'); continue; }
                const fd = new FormData();
                fd.append('shots', f);
                try {
                    const { data } = await api.post(`/api/${kind}/${id}/shots`, fd);
                    this.shots = [...data.saved, ...this.shots];
                } catch (e) { failed.push(f.name + ': ' + e.message); }
            }
            if (failed.length) this.error = 'Não foram enviadas: ' + failed.join(' · ');
            this.busy = false; this.progress = '';
            this.emit();
            changed();
        },
        /** a página em volta pode usar as screenshots (ex.: a linha do tempo do jogo) */
        emit() { window.dispatchEvent(new CustomEvent('shots-loaded', { detail: { kind, id, shots: this.shots } })); },
        editCaption(s) { this.editId = s.id; this.captionDraft = s.caption || ''; },
        async saveCaption(s) {
            try {
                const { data } = await api.put('/api/shots/' + s.id, { caption: this.captionDraft });
                this.shots = this.shots.map((x) => (x.id === s.id ? { ...x, caption: data.caption } : x));
                this.editId = null;
                changed();
            } catch (e) { this.error = e.message; }
        },
        async remove(s) {
            if (this.confirmId !== s.id) { this.confirmId = s.id; setTimeout(() => { if (this.confirmId === s.id) this.confirmId = null; }, 4000); return; }
            try {
                await api.del('/api/shots/' + s.id);
                this.shots = this.shots.filter((x) => x.id !== s.id);
                this.emit();
                changed();
            } catch (e) { this.error = e.message; }
            this.confirmId = null;
        },
        view(i) {
            window.dispatchEvent(new CustomEvent('lightbox', { detail: {
                index: i,
                items: this.shots.map((s) => ({ url: s.url, caption: s.caption, meta: s.note_id ? 'Diário · ' + UI.fmtDate(s.note_date) : 'Enviada em ' + UI.fmtDate(s.created_at) })),
            } }));
        },
    };
}
</script>
