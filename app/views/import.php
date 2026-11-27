<?php
/** @var array|null $me */
/** @var array|null $demo  planilha fictícia já lida e validada (nulo na importação real) */
$demo = $demo ?? null;
$fields = \App\Services\ImportService::FIELDS;
?>
<main class="page page-normal" x-data='importer(<?= e(json_encode(['demo' => $demo, 'fields' => $fields], JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com uma planilha fictícia (de propósito, com datas em formatos misturados e algumas linhas com problema). Nada é salvo.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header>
        <h1 class="t-page">Importar jogos</h1>
        <p class="page-sub ">Traga sua lista de uma planilha (.csv ou .xlsx) ou de um backup JSON do MyGameList. Você confere tudo antes de gravar.</p>
    </header>

    <!-- Passos -->
    <ol class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="Etapas">
        <template x-for="(s, i) in steps" :key="i">
            <li class="flex items-center gap-2 rounded-full border px-3 py-1.5" :class="step === i + 1 ? 'border-brand-400 bg-brand-500/15 font-semibold text-slate-50' : (step > i + 1 ? 'border-ok/40 text-ok' : 'border-slate-50/10 text-slate-500')" :aria-current="step === i + 1 ? 'step' : null">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-slate-50/10 text-xs" x-text="step > i + 1 ? '✓' : i + 1"></span><span x-text="s"></span>
            </li>
        </template>
    </ol>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error" x-text="error"></div>

    <!-- 1) Arquivo -->
    <section x-show="step === 1" x-cloak class="mt-6">
        <label class="block cursor-pointer rounded-2xl border-2 border-dashed p-10 text-center transition"
               :class="drag ? 'border-brand-400 bg-brand-500/10' : 'border-slate-50/15 hover:border-slate-50/30'"
               @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="drag = false; pick($event.dataTransfer.files[0])">
            <span class="block text-lg font-semibold text-slate-50" x-text="busy ? 'Lendo o arquivo…' : 'Arraste o arquivo aqui ou clique para escolher'"></span>
            <span class="mt-1 block text-sm text-slate-400">.csv, .xlsx ou .json · até 5 MB · até 2000 jogos</span>
            <input type="file" class="sr-only" accept=".csv,.txt,.xlsx,.json" @change="pick($event.target.files[0]); $event.target.value = ''" :disabled="busy">
        </label>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="panel p-5 text-sm text-slate-300">
                <h2 class="font-bold text-slate-50">Como preparar a planilha</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-400">
                    <li>A primeira linha traz os nomes das colunas; cada jogo ocupa uma linha.</li>
                    <li>Obrigatórios: título, sistema, gênero, ano de lançamento, data de finalização, nota (1 a 11) e dificuldade (C, B, A, AA, AAA). Sistema, gênero, nota e dificuldade podem ter um valor padrão para todos.</li>
                    <li>Datas: 2026-03-14, 14/03/2026 ou data do Excel. Tempo: 20h30, 20:30, 20,5 ou minutos.</li>
                    <li>Tarefas: <em>Descrição (tier 2) | Outra (tier 1)</em>.</li>
                </ul>
            </div>
            <div class="panel p-5 text-sm text-slate-300">
                <h2 class="font-bold text-slate-50">Sem planilha ainda?</h2>
                <p class="mt-2 text-slate-400">Baixe o modelo, preencha e volte. Ele usa as mesmas colunas da exportação de <em>Minha conta</em>, então o CSV exportado também pode ser importado de volta.</p>
                <a href="<?= e(url('/api/import/template')) ?>" class="mt-3 inline-block rounded-lg border border-slate-50/15 px-4 py-2 font-semibold text-slate-50 hover:bg-slate-50/5" download>Baixar modelo (.csv)</a>
                <?php if ($demo === null): ?><a href="<?= e(url('/importar/exemplo')) ?>" class="ml-2 inline-block font-semibold text-brand-400 hover:underline">Ver um exemplo →</a><?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 2) Colunas -->
    <section x-show="step === 2" x-cloak class="mt-6 space-y-6">
        <p class="text-sm text-slate-400"><strong class="text-slate-50" x-text="fileName"></strong> · <span x-text="rows.length"></span> linhas · <span x-text="format.toUpperCase()"></span>
            <span x-show="truncated" class="text-gold-300"> · só as primeiras 2000 linhas serão lidas</span></p>

        <div class="overflow-x-auto panel">
            <table class="w-full min-w-[34rem] text-left text-sm">
                <caption class="sr-only">Associação das colunas da planilha aos campos do jogo</caption>
                <thead class="text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Coluna da planilha</th><th class="px-4 py-3">Exemplos</th><th class="px-4 py-3">Vai para</th></tr></thead>
                <tbody>
                    <template x-for="(h, i) in headers" :key="i">
                        <tr class="border-t border-slate-50/10">
                            <td class="px-4 py-2 font-semibold text-slate-50" x-text="h || ('Coluna ' + (i + 1))"></td>
                            <td class="max-w-[16rem] truncate px-4 py-2 text-slate-400" x-text="sample(i)"></td>
                            <td class="px-4 py-2">
                                <select class="w-full rounded-lg border border-slate-50/10 bg-slate-950 px-2 py-1.5 text-slate-50" :aria-label="'Campo para ' + h" x-model="mapping[i]" @change="dedupe(i)">
                                    <option value="">— ignorar —</option>
                                    <template x-for="(label, key) in fields" :key="key"><option :value="key" x-text="label"></option></template>
                                </select>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 panel p-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <h2 class="font-bold text-slate-50">Valores padrão</h2>
                <p class="text-sm text-slate-400">Usados quando a coluna não existe ou a célula está vazia.</p>
            </div>
            <label class="text-sm"><span class="text-slate-300">Sistema</span><input type="text" maxlength="60" x-model="opts.defaults.platform" placeholder="ex.: PC" class="mt-1 w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-slate-50"></label>
            <label class="text-sm"><span class="text-slate-300">Gênero</span><input type="text" maxlength="50" x-model="opts.defaults.genre" placeholder="ex.: RPG" class="mt-1 w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-slate-50"></label>
            <label class="text-sm"><span class="text-slate-300">Dificuldade</span>
                <select x-model="opts.defaults.difficulty" class="mt-1 w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-slate-50"><option value="">— nenhuma —</option><template x-for="d in ['C','B','A','AA','AAA']" :key="d"><option :value="d" x-text="d"></option></template></select></label>
            <label class="text-sm"><span class="text-slate-300">Nota</span>
                <select x-model="opts.defaults.rating" class="mt-1 w-full rounded-lg border border-slate-50/10 bg-slate-950 px-3 py-2 text-slate-50"><option value="">— nenhuma —</option><template x-for="n in 11" :key="n"><option :value="String(n)" x-text="n"></option></template></select></label>
            <fieldset class="text-sm md:col-span-2">
                <legend class="text-slate-300">Um número solto no tempo de jogo (ex.: 20) significa…</legend>
                <div class="mt-1 flex gap-4 text-slate-200">
                    <label class="flex items-center gap-2"><input type="radio" value="hours" x-model="opts.time_unit"> horas</label>
                    <label class="flex items-center gap-2"><input type="radio" value="minutes" x-model="opts.time_unit"> minutos</label>
                </div>
            </fieldset>
        </div>

        <p x-show="missing().length" x-cloak class="rounded-lg border border-gold-400/40 bg-gold-400/10 px-4 py-3 text-sm text-slate-200">Sem coluna nem padrão para: <strong x-text="missing().join(', ')"></strong>. Todas as linhas ficarão com erro até você resolver.</p>

        <div class="flex flex-wrap gap-3">
            <button type="button" @click="reset()" class="btn-ghost">← Outro arquivo</button>
            <button type="button" @click="review()" :disabled="busy" class="btn" x-text="busy ? 'Conferindo…' : 'Conferir linhas →'"></button>
        </div>
    </section>

    <!-- 3) Revisão -->
    <section x-show="step === 3" x-cloak class="mt-6">
        <div class="grid gap-3 sm:grid-cols-3">
            <template x-for="t in tabs" :key="t.v">
                <button type="button" @click="filter = filter === t.v ? '' : t.v" :aria-pressed="filter === t.v"
                        class="rounded-2xl border p-4 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                        :class="filter === t.v ? 'border-slate-50/60 bg-slate-900' : 'border-slate-50/10 bg-slate-900/70 hover:border-slate-50/30'">
                    <span class="block text-3xl font-black" :class="t.c" x-text="counts[t.v] || 0"></span>
                    <span class="block text-sm font-semibold text-slate-50" x-text="t.l"></span>
                    <span class="block text-xs text-slate-400" x-text="t.hint"></span>
                </button>
            </template>
        </div>

        <div class="mt-5 overflow-x-auto panel">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <caption class="sr-only">Linhas da planilha e o resultado da conferência</caption>
                <thead class="text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-3 py-3">Linha</th><th class="px-3 py-3">Situação</th><th class="px-3 py-3">Jogo</th><th class="px-3 py-3">Finalizado</th><th class="px-3 py-3">Tempo</th><th class="px-3 py-3">Nota</th><th class="px-3 py-3">Dif.</th></tr></thead>
                <tbody>
                    <template x-for="r in shown" :key="r.n">
                        <tr class="border-t border-slate-50/10 align-top">
                            <td class="px-3 py-2 text-slate-500" x-text="r.n"></td>
                            <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="badge(r.status).c" x-text="badge(r.status).l"></span></td>
                            <td class="px-3 py-2">
                                <template x-if="r.data"><span><span class="font-semibold text-slate-50" x-text="r.data.title"></span><span class="block text-xs text-slate-400" x-text="r.data.platform + ' · ' + r.data.genre + ' · ' + r.data.release_year"></span></span></template>
                                <template x-if="!r.data"><span class="text-slate-500">—</span></template>
                                <template x-for="m in r.errors" :key="m"><span class="mt-1 block text-xs text-danger" x-text="m"></span></template>
                                <template x-for="m in r.warnings" :key="m"><span class="mt-1 block text-xs text-gold-300" x-text="m"></span></template>
                            </td>
                            <td class="px-3 py-2 text-slate-300" x-text="r.data ? UI.fmtDate(r.data.finished_at) : ''"></td>
                            <td class="px-3 py-2 text-slate-300" x-text="r.data ? dur(r.data.playtime_minutes) : ''"></td>
                            <td class="px-3 py-2"><span x-show="r.data" class="rounded px-1.5 py-0.5 text-xs font-bold" :style="r.data ? UI.ratingStyle(r.data.rating) : ''" x-text="r.data ? r.data.rating : ''"></span></td>
                            <td class="px-3 py-2 text-slate-300" x-text="r.data ? r.data.difficulty : ''"></td>
                        </tr>
                    </template>
                    <tr x-show="!visible.length"><td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhuma linha nesse filtro.</td></tr>
                </tbody>
            </table>
        </div>
        <button type="button" x-show="visible.length > limit" @click="limit += 200" class="mt-3 text-sm font-semibold text-brand-400 hover:underline">Mostrar mais (<span x-text="visible.length - limit"></span> restantes)</button>

        <p x-show="isDemo" x-cloak class="mt-5 rounded-lg border border-gold-400/40 bg-gold-400/10 px-4 py-3 text-sm text-slate-200">Na página de exemplo nada é gravado. Na importação real, só as linhas “Prontas” entram; repetidas e com erro são ignoradas.</p>

        <div class="mt-5 flex flex-wrap items-center gap-3">
            <button type="button" @click="step = 2" class="btn-ghost">← Ajustar colunas</button>
            <button type="button" @click="commit()" :disabled="busy || isDemo || !(counts.ok > 0)" class="btn">
                <span x-text="busy ? ('Importando… ' + done + '/' + todo) : ('Importar ' + (counts.ok || 0) + ' jogos')"></span>
            </button>
        </div>
    </section>

    <!-- 4) Resultado -->
    <section x-show="step === 4" x-cloak class="mt-6 panel p-8 text-center" role="status">
        <p class="text-4xl font-black text-ok" x-text="result.imported"></p>
        <p class="mt-1 text-lg font-semibold text-slate-50" x-text="result.imported === 1 ? 'jogo importado' : 'jogos importados'"></p>
        <p class="mt-1 text-sm text-slate-400"><span x-text="result.skipped"></span> já existiam · <span x-text="result.errors.length"></span> com erro<span x-show="result.xp" class="xp-ui"> · agora você está no nível <strong class="text-slate-50" x-text="result.xp && result.xp.level"></strong></span></p>
        <ul x-show="result.errors.length" class="mx-auto mt-4 max-w-xl list-disc pl-5 text-left text-sm text-danger">
            <template x-for="e in result.errors" :key="e.n"><li><span x-text="'Linha ' + e.n + ': ' + e.messages.join(' ')"></span></li></template>
        </ul>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="<?= e(url('/jogos')) ?>" class="btn">Ver o catálogo</a>
            <button type="button" @click="reset()" class="btn-ghost">Importar outro arquivo</button>
        </div>
    </section>
</main>

<script>
function importMapArray(m, n) { const a = Array(n).fill(''); Object.entries(m || {}).forEach(([i, f]) => { a[+i] = f; }); return a; }

function importer(init) {
    const BATCH = 100;
    const demo = init.demo;
    const blankOpts = () => ({ time_unit: 'hours', defaults: { platform: '', genre: '', difficulty: '', rating: '' } });
    return {
        fields: init.fields, isDemo: !!demo,
        steps: ['Arquivo', 'Colunas', 'Conferir', 'Pronto'],
        step: demo ? 2 : 1, busy: false, error: '', drag: false,
        fileName: demo ? demo.fileName : '', format: demo ? demo.format : '', truncated: false,
        headers: demo ? demo.headers : [], rows: demo ? demo.rows : [],
        mapping: demo ? importMapArray(demo.mapping, demo.headers.length) : [],
        opts: demo ? demo.opts : blankOpts(),
        preview: demo ? demo.preview : { rows: [], counts: {} },
        filter: '', limit: 200, done: 0, todo: 0,
        result: { imported: 0, skipped: 0, errors: [], xp: null },
        tabs: [
            { v: 'ok', l: 'Prontas', hint: 'Entram no catálogo', c: 'text-ok' },
            { v: 'duplicate', l: 'Repetidas', hint: 'Já existem ou aparecem duas vezes', c: 'text-gold-300' },
            { v: 'error', l: 'Com erro', hint: 'Corrija na planilha ou nos padrões', c: 'text-danger' },
        ],
        get counts() { return this.preview.counts || {}; },
        get visible() { return this.filter ? this.preview.rows.filter(r => r.status === this.filter) : this.preview.rows; },
        get shown() { return this.visible.slice(0, this.limit); },

        badge(s) {
            return { ok: { l: 'Pronta', c: 'bg-ok/15 text-ok' }, duplicate: { l: 'Repetida', c: 'bg-gold-400/15 text-gold-300' }, error: { l: 'Erro', c: 'bg-danger/15 text-danger' } }[s];
        },
        dur(m) { return m >= 60 ? Math.floor(m / 60) + 'h' + (m % 60 ? String(m % 60).padStart(2, '0') : '') : m + 'min'; },
        sample(i) { return this.rows.slice(0, 3).map(r => r[i]).filter(Boolean).join(' · '); },
        dedupe(i) {
            const f = this.mapping[i];
            if (!f) return;
            this.mapping.forEach((v, j) => { if (j !== i && v === f) this.mapping[j] = ''; });
        },
        missing() {
            const used = new Set(this.mapping.filter(Boolean)), d = this.opts.defaults, out = [];
            for (const f of ['title', 'finished_at', 'release_year']) if (!used.has(f)) out.push(this.fields[f]);
            for (const f of ['platform', 'genre', 'difficulty', 'rating']) if (!used.has(f) && !d[f]) out.push(this.fields[f]);
            return out;
        },
        mapObj() { const o = {}; this.mapping.forEach((f, i) => { if (f) o[i] = f; }); return o; },

        async pick(file) {
            if (!file) return;
            this.error = '';
            if (file.size > 5 * 1024 * 1024) { this.error = 'O arquivo passa de 5 MB.'; return; }
            this.busy = true;
            try {
                const fd = new FormData();
                fd.append('file', file);
                const { data } = await api.post('/api/import/parse', fd);
                this.fileName = file.name; this.format = data.format; this.truncated = data.truncated;
                this.headers = data.headers; this.rows = data.rows;
                this.mapping = importMapArray(data.mapping, data.headers.length);
                this.opts = blankOpts(); this.opts.time_unit = data.time_unit;
                this.step = 2;
            } catch (e) { this.error = e.message; }
            this.busy = false;
        },

        async review() {
            this.error = '';
            if (this.isDemo) { this.step = 3; return; }
            this.busy = true;
            try {
                const { data } = await api.post('/api/import/preview', { rows: this.rows, mapping: this.mapObj(), opts: this.opts });
                this.preview = data; this.filter = ''; this.limit = 200; this.step = 3;
            } catch (e) { this.error = e.message; }
            this.busy = false;
        },

        async commit() {
            const todo = this.preview.rows.filter(r => r.status === 'ok');
            this.busy = true; this.error = ''; this.done = 0; this.todo = todo.length;
            const total = { imported: 0, skipped: 0, errors: [], xp: null };
            try {
                for (let i = 0; i < todo.length; i += BATCH) {
                    const chunk = todo.slice(i, i + BATCH).map(r => ({ n: r.n, cells: this.rows[r.n - 2] }));
                    const { data } = await api.post('/api/import/commit', { rows: chunk, mapping: this.mapObj(), opts: this.opts });
                    total.imported += data.imported; total.skipped += data.skipped; total.errors.push(...data.errors); total.xp = data.xp;
                    this.done = Math.min(i + BATCH, todo.length);
                }
                this.result = total; this.step = 4;
            } catch (e) {
                this.error = e.message + (total.imported ? ' Já foram importados ' + total.imported + ' jogos; refaça a conferência para continuar do ponto em que parou.' : '');
            }
            this.busy = false;
        },

        reset() {
            this.step = 1; this.error = ''; this.headers = []; this.rows = []; this.mapping = []; this.fileName = '';
            this.opts = blankOpts(); this.preview = { rows: [], counts: {} }; this.filter = '';
        },
    };
}
</script>
