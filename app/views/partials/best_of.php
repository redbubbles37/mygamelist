<?php
/**
 * Best of: janela para escolher o melhor jogo de uma categoria (sistema, gênero, desenvolvedora, publisher ou franquia).
 * Abra com: $dispatch('best-open', { kind, value, label, current })
 * Ao salvar, dispara 'best-saved' na window com { kind, value, best } (best nulo = escolha removida).
 */
?>
<div x-data="bestPicker()" @best-open.window="open($event.detail)" @keydown.escape.window="close()">
    <div x-show="visible" x-cloak x-transition.opacity class="fixed inset-0 z-modal grid place-items-center bg-black/75 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="best-title" @click.self="close()">
        <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-gold-400/30 bg-slate-900 shadow-2xl">
            <!-- cabeçalho -->
            <div class="relative overflow-hidden border-b border-slate-50/10 px-5 py-4 sm:px-6">
                <div class="pointer-events-none absolute -right-10 -top-16 h-40 w-40 rounded-full bg-gold-400/20 blur-3xl" aria-hidden="true"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="best-kicker">🏆 Best of</p>
                        <h2 id="best-title" class="mt-0.5 truncate text-xl font-bold text-slate-50 sm:text-2xl" x-text="label"></h2>
                        <p class="mt-1 text-sm text-slate-400" x-show="ready" x-text="subtitle"></p>
                    </div>
                    <button type="button" @click="close()" class="icon-btn shrink-0" aria-label="Fechar">
                        <?= icon('close', 'h-5 w-5') ?>
                    </button>
                </div>
                <label x-show="ready && list.length > 8" class="relative mt-3 block max-w-xs">
                    <span class="sr-only">Buscar jogo</span>
                    <?= icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500') ?>
                    <input type="search" x-model="q" placeholder="Buscar jogo…" class="w-full rounded-lg border border-slate-50/10 bg-slate-950/60 py-2 pl-9 pr-3 text-sm text-slate-50 placeholder-slate-500 outline-none focus:ring-2 focus:ring-gold-400/60">
                </label>
            </div>

            <!-- candidatos -->
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div x-show="!ready && !error" class="grid animate-pulse grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-5" aria-label="Carregando">
                    <template x-for="i in 10"><div class="aspect-[3/4] rounded-xl bg-slate-50/5"></div></template>
                </div>
                <p x-show="error" x-cloak role="alert" class="alert-error" x-text="error"></p>
                <p x-show="ready && tooFew" x-cloak class="rounded-xl border border-dashed border-slate-50/15 p-6 text-center text-sm text-slate-400">
                    O best of fica disponível com pelo menos <strong class="text-slate-200" x-text="min"></strong> jogos zerados aqui. Por enquanto há <span x-text="total"></span>.
                </p>
                <p x-show="ready && noneEligible" x-cloak class="rounded-xl border border-dashed border-slate-50/15 p-6 text-center text-sm text-slate-400">
                    Nenhum dos <span x-text="total"></span> jogos daqui tem nota 9, 10 ou 11, e só esses podem ser eleitos best of.
                </p>
                <p x-show="ready && !tooFew && !noneEligible && !shown.length" x-cloak class="py-6 text-center text-sm text-slate-400">Nenhum jogo encontrado.</p>

                <ul x-show="ready && !tooFew && !noneEligible" class="grid grid-cols-3 gap-x-4 gap-y-5 sm:grid-cols-4 md:grid-cols-5" role="radiogroup" :aria-label="'Melhor jogo de ' + label">
                    <template x-for="g in shown" :key="g.id">
                        <li>
                            <button type="button" role="radio" :aria-checked="selected === g.id" @click="selected = g.id"
                                    class="group/pick block w-full text-left focus:outline-none">
                                <span class="best-pick-cover relative block aspect-[3/4] overflow-hidden rounded-xl bg-slate-950 transition duration-200"
                                      :class="selected === g.id ? 'is-selected' : 'opacity-80 group-hover/pick:opacity-100 group-focus-visible/pick:ring-2 group-focus-visible/pick:ring-gold-400'">
                                    <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    <span x-show="!g.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/50 text-2xl font-bold text-white/70" x-text="UI.initials(g.title)"></span>
                                    <span class="absolute left-1.5 top-1.5 grid h-7 min-w-7 place-items-center rounded-md px-1 text-xs font-black leading-none shadow ring-1 ring-black/40"
                                          :class="UI.ratingClass(g.rating)" :style="UI.ratingStyle(g.rating)" :title="'Sua nota: ' + g.rating" x-text="g.rating"></span>
                                    <span x-show="selected === g.id" class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-gold-500/95 to-gold-400/70 py-1 text-center text-2xs font-black uppercase tracking-wider text-black">🏆 Best of</span>
                                    <span x-show="current && current.id === g.id && selected !== g.id" class="absolute inset-x-0 bottom-0 bg-black/70 py-1 text-center text-2xs font-bold uppercase tracking-wider text-gold-300">Escolha atual</span>
                                </span>
                                <span class="mt-1.5 block truncate text-xs font-semibold text-slate-100" :title="g.title" x-text="g.title"></span>
                                <span class="block truncate text-2xs text-slate-500" x-text="g.platform + (g.finished_at ? ' · ' + g.finished_at.slice(0, 4) : '')"></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- rodapé -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-50/10 bg-slate-950/40 px-5 py-3 sm:px-6">
                <p class="text-xs text-slate-500">Ordenados pela sua nota. Só concorrem jogos zerados desta categoria com nota 9, 10 ou 11.</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" x-show="current" @click="save(null)" :disabled="saving" class="rounded-lg px-3 py-2 text-sm font-medium text-danger hover:bg-danger/10 disabled:opacity-50">Remover best of</button>
                    <button type="button" @click="close()" class="btn-ghost">Cancelar</button>
                    <button type="button" @click="save(selected)" :disabled="saving || !selected || (current && current.id === selected)"
                            class="rounded-lg bg-gradient-to-r from-gold-400 to-gold-500 px-5 py-2 text-sm font-bold text-black shadow transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40"
                            x-text="saving ? 'Salvando…' : 'Confirmar best of'"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bestPicker() {
    return {
        visible: false, ready: false, saving: false, error: '',
        kind: '', value: '', label: '', current: null, selected: null,
        list: [], total: 0, min: 2, minRating: 9, q: '', opener: null,

        get tooFew() { return this.total < this.min; },
        get noneEligible() { return !this.tooFew && this.list.length === 0; },
        get shown() {
            const q = this.q.trim().toLowerCase();
            return q ? this.list.filter((g) => g.title.toLowerCase().includes(q)) : this.list;
        },
        get subtitle() {
            const n = this.list.length;
            return this.tooFew || this.noneEligible ? '' : `Escolha o melhor entre ${n} ${n === 1 ? 'jogo' : 'jogos'} com nota ${this.minRating} a 11 (de ${this.total} zerados aqui).`;
        },

        async open(d) {
            Object.assign(this, { kind: d.kind, value: String(d.value), label: d.label, current: d.current ?? null, selected: d.current?.id ?? null });
            Object.assign(this, { visible: true, ready: false, error: '', q: '', list: [] });
            this.opener = document.activeElement;
            try {
                const r = await api.get(`/api/best/candidates?kind=${encodeURIComponent(this.kind)}&value=${encodeURIComponent(this.value)}`);
                this.list = r.data;
                this.min = r.min;
                this.total = r.total ?? r.data.length;
                this.minRating = r.min_rating ?? 9;
                this.ready = true;
                this.$nextTick(() => this.$root.querySelector('[role=radio][aria-checked=true], [role=radio]')?.focus());
            } catch (e) {
                if (e.status === 401) return (location.href = window.APP.base + '/login');
                this.error = e.message;
            }
        },
        close() {
            if (!this.visible) return;
            this.visible = false;
            this.opener?.focus?.();
        },
        async save(gameId) {
            this.saving = true;
            this.error = '';
            try {
                const r = await api.put('/api/best', { kind: this.kind, value: this.value, game_id: gameId });
                window.dispatchEvent(new CustomEvent('best-saved', { detail: { kind: this.kind, value: this.value, best: r.data } }));
                this.current = r.data;
                this.close();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
