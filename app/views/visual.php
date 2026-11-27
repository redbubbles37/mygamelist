<?php
/** @var array $me  username, xp, layout, calm */
$previews = [
    'case' => '<div class="flex h-full items-center justify-center gap-2 p-3"><template x-for="i in 4"><div class="h-full w-10 overflow-hidden rounded-[3px] bg-brand-600/70 ring-1 ring-black/30"><div class="h-2 bg-black/70"></div></div></template></div>',
    'shelf' => '<div class="relative flex h-full items-end justify-center gap-3 px-3 pb-3"><template x-for="i in 3"><div class="h-14 w-10 rounded-[2px] bg-brand-600/70 ring-1 ring-black/30" style="transform:perspective(200px) rotateY(16deg)"></div></template><div class="absolute inset-x-2 bottom-1.5 h-2 rounded-sm bg-slate-600"></div></div>',
    'terminal' => '<div class="space-y-1 p-3 font-mono text-[9px] leading-none text-slate-400"><div>06 <span class="text-brand-400">Resident Evil 4</span>  GameCube</div><div>09 <span class="text-brand-400">Hades</span>  PC  2026-02</div><div>11 <span class="text-brand-400">Super Mario 64</span>  N64</div><div>08 <span class="text-brand-400">Celeste</span>  Switch</div><div>$ _</div></div>',
];
?>
<main class="page page-narrow" x-data='visual(<?= e(json_encode(['layout' => $me['layout'] ?? null, 'calm' => (bool) ($me['calm'] ?? false)])) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>
    <header>
        <h1 class="t-page">Visual e modo calmo</h1>
        <p class="page-sub ">Escolha como o catálogo é mostrado e quanto de “jogo” você quer ver. As cores continuam vindo do tema.</p>
    </header>

    <p x-show="notice" x-cloak role="status" class="mt-5 rounded-lg border border-ok/30 bg-ok/10 px-4 py-3 text-sm text-ok" x-text="notice"></p>
    <div x-show="error" x-cloak role="alert" class="mt-5 alert-error" x-text="error"></div>

    <section class="mt-8" aria-labelledby="h-layout">
        <h2 id="h-layout" class="t-section">Visual do catálogo</h2>
        <p class="text-sm text-slate-400">Vale para o seu catálogo. Também dá para trocar a qualquer momento pela barra de filtros.</p>
        <ul class="mt-4 grid gap-4 sm:grid-cols-3" role="radiogroup" aria-label="Visual do catálogo">
            <?php foreach ([
                'case' => ['Estojo', 'Caixas de jogo, como na locadora.'],
                'shelf' => ['Estante 3D', 'Capas grandes e inteiras sobre a prateleira; nota, dificuldade e tempo abaixo de cada uma.'],
                'terminal' => ['Terminal', 'Tabela em fonte de máquina, leitura rápida.'],
            ] as $key => [$label, $desc]): ?>
            <li>
                <button type="button" role="radio" :aria-checked="layout === '<?= $key ?>'" @click="setLayout('<?= $key ?>')"
                        class="group block w-full rounded-2xl border p-3 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                        :class="layout === '<?= $key ?>' ? 'border-brand-400 bg-brand-500/10' : 'border-slate-50/10 bg-slate-900/70 hover:border-slate-50/30'">
                    <span class="layout-prev block"><?= $previews[$key] ?></span>
                    <span class="mt-3 flex items-center justify-between gap-2">
                        <span class="font-bold text-slate-50"><?= e($label) ?></span>
                        <span x-show="layout === '<?= $key ?>'" x-cloak class="rounded-full bg-brand-500/25 px-2 py-0.5 text-xs font-semibold text-brand-400">Em uso</span>
                    </span>
                    <span class="mt-0.5 block text-xs text-slate-400"><?= e($desc) ?></span>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
        <p class="mt-3 text-sm"><a href="<?= e(url('/jogos')) ?>" class="font-semibold text-brand-400 hover:underline">Ver o meu catálogo →</a></p>
    </section>

    <section class="mt-10 panel p-5" aria-labelledby="h-calm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="h-calm" class="t-section">Modo calmo</h2>
                <p class="mt-1 max-w-xl text-sm text-slate-400">Esconde XP, nível, metas do ano, ranking, estatísticas e os números do painel. Ficam só os seus jogos, o diário, as memórias e o backlog. O XP continua sendo calculado por baixo: se você desligar, tudo volta como estava.</p>
            </div>
            <button type="button" role="switch" :aria-checked="calm" @click="toggleCalm()" aria-labelledby="h-calm"
                    class="relative h-7 w-12 shrink-0 rounded-full border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    :class="calm ? 'border-brand-400 bg-brand-600' : 'border-slate-50/20 bg-slate-950'">
                <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="calm ? 'left-6' : 'left-0.5'"></span>
            </button>
        </div>
    </section>
</main>

<script>
function visual(init) {
    return {
        layout: init.layout || 'case', calm: init.calm, notice: '', error: '',
        async setLayout(v) {
            this.error = ''; this.notice = '';
            const prev = this.layout; this.layout = v;
            try {
                await api.put('/api/me/layout', { layout: v });
                try { localStorage.setItem('mgl-layout', v); } catch { /* sem storage */ }
                this.notice = 'Visual do catálogo atualizado.';
            } catch (e) { this.layout = prev; this.error = e.message; }
        },
        async toggleCalm() {
            this.error = ''; this.notice = '';
            const next = !this.calm;
            try {
                await api.put('/api/me/calm', { calm: next });
                this.calm = next;
                location.reload(); // o modo vale para a página inteira (menu incluído)
            } catch (e) { this.error = e.message; }
        },
    };
}
</script>
