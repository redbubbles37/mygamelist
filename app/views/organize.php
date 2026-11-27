<?php
/** @var array|null $me */
/** @var array|null $demo  listas fictícias por tipo (nulo na página real) */
$demo = $demo ?? null;
?>
<main class="page page-narrow" x-data='organize(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com nomes bagunçados de propósito. Dá para juntar de verdade na tela, mas nada é salvo.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header>
        <h1 class="t-page">Organizar nomes</h1>
        <p class="page-sub ">Como sistema, gênero, desenvolvedora e publisher são texto livre, o mesmo nome pode aparecer escrito de formas diferentes. Junte as variações em um nome só, ou renomeie. Só os seus dados mudam.</p>
    </header>

    <div class="mt-6 flex flex-wrap gap-2" role="tablist" aria-label="Tipo de nome">
        <template x-for="k in kinds" :key="k.v">
            <button type="button" role="tab" :aria-selected="kind === k.v" @click="open(k.v)"
                    class="rounded-full border px-4 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    :class="kind === k.v ? 'border-brand-400 bg-brand-500/15 text-slate-50' : 'border-slate-50/10 text-slate-400 hover:text-slate-100'" x-text="k.l"></button>
        </template>
    </div>

    <p x-show="notice" x-cloak role="status" class="mt-5 rounded-lg border border-ok/30 bg-ok/10 px-4 py-3 text-sm text-ok" x-text="notice"></p>
    <div x-show="error" x-cloak role="alert" class="mt-5 alert-error" x-text="error"></div>
    <ul x-show="skipped.length" x-cloak class="mt-3 list-disc rounded-lg border border-gold-400/40 bg-gold-400/10 py-3 pl-8 pr-4 text-sm text-slate-200">
        <template x-for="s in skipped" :key="s.title"><li><strong x-text="s.title"></strong>: <span x-text="s.reason"></span></li></template>
    </ul>

    <p x-show="loading" class="mt-6 text-sm text-slate-400">Carregando…</p>

    <template x-if="!loading">
        <div>
            <!-- Sugestões automáticas -->
            <section x-show="cur.suggestions.length" class="mt-6" aria-labelledby="h-sug">
                <h2 id="h-sug" class="t-section">Parecem ser a mesma coisa</h2>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <template x-for="(g, i) in cur.suggestions" :key="g.ids.join('-')">
                        <div class="panel p-4">
                            <p class="flex flex-wrap gap-1.5">
                                <template x-for="n in g.names" :key="n"><span class="rounded-md bg-slate-50/10 px-2 py-0.5 text-sm text-slate-100" x-text="n"></span></template>
                            </p>
                            <label class="mt-3 block text-xs text-slate-400">Juntar em
                                <input type="text" x-model="g.suggested" :maxlength="max" class="mt-1 w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-1.5 text-sm text-slate-50">
                            </label>
                            <button type="button" @click="ask(g.ids, g.suggested)" class="btn mt-3 btn-sm">Juntar</button>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Lista completa -->
            <section class="mt-8" aria-labelledby="h-all">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 id="h-all" class="t-section">Todos os nomes <span class="text-sm font-normal text-slate-400" x-text="'(' + cur.items.length + ')'"></span></h2>
                    <label class="relative">
                        <span class="sr-only">Filtrar nomes</span>
                        <input type="search" x-model="q" placeholder="Filtrar…" class="w-48 rounded-lg border border-slate-50/10 bg-slate-900/70 px-3 py-1.5 text-sm text-slate-50 placeholder-slate-500">
                    </label>
                </div>

                <p x-show="!cur.items.length" class="mt-4 empty">Nada por aqui ainda.</p>

                <div x-show="cur.items.length" class="mt-3 overflow-x-auto panel">
                    <table class="w-full min-w-[30rem] text-left text-sm">
                        <caption class="sr-only">Nomes usados e quantos jogos usam cada um</caption>
                        <thead class="text-xs uppercase tracking-wider text-slate-500"><tr><th class="w-10 px-4 py-2"><span class="sr-only">Selecionar</span></th><th class="px-2 py-2">Nome</th><th class="px-3 py-2">Catálogo</th><th class="px-3 py-2" x-show="hasOthers">Backlog / dropados</th></tr></thead>
                        <tbody>
                            <template x-for="it in shown" :key="it.id">
                                <tr class="border-t border-slate-50/10">
                                    <td class="px-4 py-2"><input type="checkbox" :value="it.id" x-model.number="sel" :aria-label="'Selecionar ' + it.name" class="h-4 w-4 rounded"></td>
                                    <td class="px-2 py-2 font-semibold text-slate-100" x-text="it.name"></td>
                                    <td class="px-3 py-2 tabular-nums text-slate-300" x-text="it.games"></td>
                                    <td class="px-3 py-2 tabular-nums text-slate-400" x-show="hasOthers" x-text="(it.backlog + it.dropped) || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Ação manual -->
                <div x-show="sel.length" x-cloak class="mt-4 rounded-2xl border border-brand-400/40 bg-slate-900 p-4">
                    <p class="text-sm text-slate-300"><strong class="text-slate-50" x-text="sel.length"></strong> <span x-text="sel.length === 1 ? 'nome selecionado' : 'nomes selecionados'"></span> · <span x-text="selTotal"></span> registros. Escreva o nome final (um existente ou um novo) para juntar ou renomear.</p>
                    <div class="mt-3 flex flex-wrap items-end gap-3">
                        <label class="min-w-[14rem] flex-1 text-xs text-slate-400">Nome final
                            <input type="text" x-model="target" :maxlength="max" list="org-names" class="mt-1 w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-sm text-slate-50">
                        </label>
                        <datalist id="org-names"><template x-for="it in cur.items" :key="it.id"><option :value="it.name"></option></template></datalist>
                        <button type="button" @click="ask(sel, target)" :disabled="!target.trim()" class="btn">Juntar / renomear</button>
                        <button type="button" @click="sel = []" class="btn-ghost">Limpar seleção</button>
                    </div>
                </div>
            </section>
        </div>
    </template>

    <!-- Confirmação -->
    <div x-show="pending" x-cloak class="modal-back" role="dialog" aria-modal="true" aria-labelledby="org-confirm" @keydown.escape.window="pending = null">
        <div class="w-full max-w-md rounded-2xl border border-slate-50/15 bg-slate-900 p-6 shadow-2xl">
            <h2 id="org-confirm" class="t-section">Confirmar</h2>
            <p class="mt-2 text-sm text-slate-300" x-show="pending">
                Juntar <strong x-text="pending && pending.names.join(', ')"></strong> em <strong class="text-brand-400" x-text="pending && pending.to"></strong>?
                <span class="block pt-2 text-slate-400" x-text="pending && ('Isso muda ' + pending.count + ' registros seus. ' + (isDemo ? '' : 'Não dá para desfazer automaticamente.'))"></span>
            </p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="pending = null" class="btn-ghost">Cancelar</button>
                <button type="button" @click="confirm()" :disabled="busy" class="btn" x-text="busy ? 'Juntando…' : 'Juntar'"></button>
            </div>
        </div>
    </div>
</main>

<script>
function organize(demo) {
    const MAX = { platform: 60, genre: 50, developer: 100, publisher: 100 };
    return {
        isDemo: !!demo,
        kinds: [{ v: 'platform', l: 'Sistemas' }, { v: 'genre', l: 'Gêneros' }, { v: 'developer', l: 'Desenvolvedoras' }, { v: 'publisher', l: 'Publishers' }],
        kind: 'platform', store: demo ? JSON.parse(JSON.stringify(demo)) : {}, loading: !demo, busy: false,
        sel: [], target: '', q: '', pending: null, notice: '', error: '', skipped: [],
        get cur() { return this.store[this.kind] ?? { items: [], suggestions: [] }; },
        get max() { return MAX[this.kind]; },
        get hasOthers() { return this.cur.items.some(i => i.backlog + i.dropped > 0); },
        get shown() {
            const q = this.q.trim().toLowerCase();
            return q ? this.cur.items.filter(i => i.name.toLowerCase().includes(q)) : this.cur.items;
        },
        get selTotal() { return this.cur.items.filter(i => this.sel.includes(i.id)).reduce((s, i) => s + i.total, 0); },

        async init() { if (!this.isDemo) await this.open('platform'); },

        async open(kind) {
            this.kind = kind; this.sel = []; this.target = ''; this.q = ''; this.error = '';
            if (this.isDemo || this.store[kind]) return;
            this.loading = true;
            try { this.store[kind] = (await api.get('/api/names/' + kind)).data; }
            catch (e) { this.error = e.message; this.store[kind] = { items: [], suggestions: [] }; }
            this.loading = false;
        },

        ask(ids, to) {
            to = String(to ?? '').trim();
            if (!to) return;
            const items = this.cur.items.filter(i => ids.includes(i.id));
            const target = this.cur.items.find(i => i.name.toLowerCase() === to.toLowerCase());
            const from = items.filter(i => !target || i.id !== target.id);
            if (!from.length) { this.notice = ''; this.error = 'Nada a juntar: o nome final já é o único escolhido.'; return; }
            this.error = '';
            this.pending = { ids: from.map(i => i.id), names: from.map(i => i.name), to, count: from.reduce((s, i) => s + i.total, 0) };
        },

        async confirm() {
            const p = this.pending;
            this.busy = true; this.error = ''; this.skipped = [];
            try {
                let moved = p.count, skipped = [];
                if (!this.isDemo) {
                    const { data } = await api.post('/api/names/' + this.kind + '/merge', { from: p.ids, to: p.to });
                    moved = data.moved; skipped = data.skipped;
                    this.store[this.kind] = (await api.get('/api/names/' + this.kind)).data;
                } else {
                    this.mergeLocal(p);
                }
                this.skipped = skipped;
                this.notice = moved + (moved === 1 ? ' registro agora usa ' : ' registros agora usam ') + '“' + p.to + '”.';
                this.sel = []; this.target = ''; this.pending = null;
            } catch (e) { this.error = e.message; this.pending = null; }
            this.busy = false;
        },

        // exemplo: aplica a junção só na memória
        mergeLocal(p) {
            const d = this.store[this.kind];
            const from = d.items.filter(i => p.ids.includes(i.id));
            let t = d.items.find(i => i.name.toLowerCase() === p.to.toLowerCase());
            if (!t) { t = { id: Math.max(0, ...d.items.map(i => i.id)) + 1, name: p.to, games: 0, backlog: 0, dropped: 0, total: 0 }; d.items.push(t); }
            for (const i of from) { t.games += i.games; t.backlog += i.backlog; t.dropped += i.dropped; t.total += i.total; }
            d.items = d.items.filter(i => !p.ids.includes(i.id)).sort((a, b) => a.name.localeCompare(b.name, 'pt', { sensitivity: 'base' }));
            d.suggestions = d.suggestions.filter(g => !g.ids.some(x => p.ids.includes(x)));
        },
    };
}
</script>
