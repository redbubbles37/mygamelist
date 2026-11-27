// Peças comuns dos formulários de jogo (zerado, backlog e dropados).
// FormKit.common() devolve métodos para mesclar no estado Alpine do formulário (só métodos: getters ficam no formulário).
window.FormKit = {
    /** Mescla os métodos comuns no estado do formulário mantendo getters como getters (um spread os congelaria). */
    mix(state, opts) { return Object.defineProperties(state, Object.getOwnPropertyDescriptors(this.common(opts))); },

    /** Hoje no fuso do navegador, no formato AAAA-MM-DD. */
    todayStr() { const d = new Date(); return new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); },

    /**
     * Métodos compartilhados. O formulário precisa ter: form, errors, formError, cover, coverUrl, removeCover
     * e o registro em edição em `game` ou `item` (para saber se já havia capa salva).
     * @param {object} o  draftKey: chave do rascunho no navegador (null = sem rascunho)
     */
    common({ draftKey = null } = {}) {
        const timers = {};
        let snapshot = null, leaving = false, draftTimer = null;
        return {
            // ---------- sugestões (sistemas, gêneros, empresas) ----------
            suggest(type, q) {
                clearTimeout(timers[type]);
                q = (q || '').trim();
                if (!q) { this.suggestions[type] = []; return; }
                timers[type] = setTimeout(async () => {
                    try { this.suggestions[type] = (await api.get(`/api/lookups/${type}?q=${encodeURIComponent(q)}`)).data; } catch { /* sem sugestões */ }
                }, 200);
            },

            // ---------- capa ----------
            pickCover(file) {
                delete this.errors.cover;
                if (!file) return;
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { this.errors.cover = 'Use uma imagem JPEG, PNG ou WebP.'; return; }
                if (file.size > 2 * 1024 * 1024) { this.errors.cover = 'A imagem passa de 2 MB.'; return; }
                this.releaseCover();
                this.cover = file;
                this.coverUrl = URL.createObjectURL(file);
                this.removeCover = false;
                if ('backlogCover' in this) this.backlogCover = false;
            },
            /** Descarta o arquivo escolhido (a pré-visualização local), sem mexer na capa já salva. */
            releaseCover() {
                if (this.coverUrl?.startsWith('blob:')) URL.revokeObjectURL(this.coverUrl);
                this.cover = null;
            },
            /** Botão "X": fica sem capa. Se já havia uma salva, ela será removida ao salvar. */
            clearCover() {
                this.releaseCover();
                this.coverUrl = null;
                this.removeCover = !!(this.game ?? this.item)?.cover_url;
                if ('backlogCover' in this) this.backlogCover = false;
            },

            // ---------- erros ----------
            focusFirstError() {
                this.$nextTick(() => {
                    const el = this.formError ? this.$refs.formError : document.querySelector('.err:not([style*="display: none"])');
                    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            },

            // ---------- aviso ao sair sem salvar ----------
            /** Marca o estado atual como "salvo" (chame depois de preencher o formulário). */
            markClean() { snapshot = JSON.stringify([this.form, this.tasks ?? null, !!this.cover]); },
            get isDirty() { return snapshot !== null && snapshot !== JSON.stringify([this.form, this.tasks ?? null, !!this.cover]); },
            /** Liga o aviso do navegador ao fechar ou sair da página com alterações. */
            guardLeave() {
                window.addEventListener('beforeunload', (e) => {
                    if (leaving || !this.isDirty) return;
                    e.preventDefault();
                    e.returnValue = '';
                });
            },
            /** Antes de sair de propósito (depois de salvar). */
            allowLeave() { leaving = true; },

            // ---------- rascunho (só no seu navegador) ----------
            readDraft() {
                if (!draftKey) return null;
                try { const d = JSON.parse(localStorage.getItem(draftKey)); return d && d.form?.title?.trim() ? d : null; } catch { return null; }
            },
            writeDraft() {
                if (!draftKey) return;
                clearTimeout(draftTimer);
                draftTimer = setTimeout(() => {
                    try {
                        if (this.isDirty && this.form.title.trim()) localStorage.setItem(draftKey, JSON.stringify({ form: this.form, tasks: this.tasks ?? null, at: Date.now() }));
                    } catch { /* sem storage */ }
                }, 600);
            },
            dropDraft() { clearTimeout(draftTimer); if (draftKey) try { localStorage.removeItem(draftKey); } catch { /* sem storage */ } },

            // ---------- grupos de opções pelo teclado ----------
            /** ←/→ (e ↑/↓) andam entre as opções de um role="radiogroup", pulando as bloqueadas; Home/End vão às pontas. */
            radioKeys(e) {
                const keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1, Home: 'first', End: 'last' };
                if (!(e.key in keys)) return;
                const group = e.currentTarget;
                const opts = [...group.querySelectorAll('[role="radio"]')].filter((b) => b.getAttribute('aria-disabled') !== 'true' && !b.disabled);
                if (!opts.length) return;
                e.preventDefault();
                const i = opts.indexOf(document.activeElement);
                const k = keys[e.key];
                const next = k === 'first' ? opts[0] : k === 'last' ? opts[opts.length - 1] : opts[(i + k + opts.length) % opts.length];
                next.focus();
                next.click();
            },
        };
    },
};
