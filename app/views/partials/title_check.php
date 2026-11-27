<?php
/**
 * "No seu catálogo" dos formulários: enquanto você digita o título, mostra o que o site já sabe sobre ele
 * (já zerado, no backlog, nos dropados, franquias). Lê form.title / form.platform do formulário em volta.
 * @var string $checkMode  'game' | 'backlog' | 'dropped'
 * @var int|null $checkSelf id do próprio registro em edição (para não se apontar como repetido)
 */
$checkMode = $checkMode ?? 'game';
$checkSelf = $checkSelf ?? null;
?>
<section x-data='titleCheck(<?= e(json_encode(['mode' => $checkMode, 'self' => $checkSelf])) ?>)' x-show="hints.length" x-cloak class="card" aria-live="polite" aria-labelledby="h-check">
    <h2 id="h-check" class="kicker">No seu catálogo</h2>
    <ul class="mt-3 space-y-2.5">
        <template x-for="h in hints" :key="h.text">
            <li class="flex gap-2.5 rounded-lg p-2.5 text-sm leading-snug"
                :class="{ 'bg-gold-400/10 text-slate-100': h.tone === 'warn', 'bg-ok/10 text-slate-100': h.tone === 'good', 'bg-slate-950/40 text-slate-300': !h.tone }">
                <span class="shrink-0 leading-none" aria-hidden="true" x-text="h.icon"></span>
                <span class="min-w-0"><span x-text="h.text"></span>
                    <a x-show="h.href" :href="h.href" class="ml-1 whitespace-nowrap font-semibold text-brand-400 hover:underline" x-text="h.cta"></a></span>
            </li>
        </template>
    </ul>
</section>

<?php if (!defined('MGL_TITLE_CHECK_JS')): define('MGL_TITLE_CHECK_JS', 1); ?>
<script>
function titleCheck({ mode, self }) {
    const base = window.APP.base;
    let timer = null, seq = 0;
    return {
        data: null,
        init() {
            const run = () => { clearTimeout(timer); timer = setTimeout(() => this.check(), 400); };
            this.$watch('form.title', run);
            this.$watch('form.platform', () => { /* as dicas usam o sistema, mas não precisam de nova busca */ });
            if (this.form.title?.trim()) this.check();
        },
        async check() {
            const t = this.form.title.trim(), id = ++seq;
            if (t.length < 2) { this.data = null; return; }
            try {
                const r = await api.get('/api/me/title-check?title=' + encodeURIComponent(t));
                if (id === seq) {
                    this.data = r.data;
                    // o formulário em volta pode usar (ex.: "usar os dados de outra versão"); quem não escuta, ignora
                    window.dispatchEvent(new CustomEvent('title-check', { detail: r.data }));
                }
            } catch { /* sem dicas */ }
        },
        /** Dicas conforme o formulário: avisos (repetido), boas notícias (completa a franquia) e informações. */
        get hints() {
            const d = this.data; if (!d) return [];
            const plat = (this.form.platform || '').trim().toLowerCase();
            const out = [];
            const catalog = d.catalog.filter((g) => !(mode === 'game' && g.id === self));
            const same = catalog.find((g) => plat && g.platform.toLowerCase() === plat);
            if (mode === 'game') {
                if (same) out.push({ tone: 'warn', icon: '⚠', text: `Você já registrou ${same.title} no ${same.platform} (zerado em ${UI.fmtDate(same.finished_at)}). Se for outra versão, informe o ano de lançamento.`, href: base + '/jogos/' + same.id, cta: 'Ver' });
                else if (catalog.length) out.push({ icon: '🔁', text: `Você também zerou este jogo no ${catalog.map((g) => g.platform).join(', ')}.`, href: base + '/jogos/' + catalog[0].id, cta: 'Ver' });
            } else if (catalog.length) {
                const g = catalog[0];
                out.push({ icon: '✅', text: `Você já zerou ${g.title} (${g.platform}, ${g.finished_at.slice(0, 4)}, nota ${g.rating}).` + (mode === 'backlog' ? ' Vai rejogar?' : ''), href: base + '/jogos/' + g.id, cta: 'Ver' });
            }
            const backlog = d.backlog.filter((b) => !(mode === 'backlog' && b.id === self) && !(mode !== 'backlog' && String(b.id) === String(this.fromBacklog ?? '')));
            if (backlog.length) {
                const b = backlog[0];
                out.push(mode === 'backlog'
                    ? { tone: 'warn', icon: '⚠', text: `Já está no seu backlog (${b.status === 'playing' ? 'jogando agora' : 'na fila'}).`, href: base + '/backlog/' + b.id + '/editar', cta: 'Abrir' }
                    : { icon: '📋', text: `Está no seu backlog (${b.status === 'playing' ? 'jogando agora' : 'na fila'}). Para levar o diário junto, use "${mode === 'game' ? 'Zerei!' : 'Dropei'}" no backlog.`, href: base + '/backlog', cta: 'Backlog' });
            }
            const dropped = d.dropped.filter((x) => !(mode === 'dropped' && x.id === self));
            if (dropped.length) {
                out.push({ icon: '⏸', text: mode === 'backlog'
                    ? `Está nos seus dropados. "Voltei a jogar" traz de volta com o diário.`
                    : `Está nos seus dropados (largado em ${UI.fmtDate(dropped[0].dropped_at)}).`, href: base + '/dropados', cta: 'Dropados' });
            }
            for (const f of d.franchises) {
                // só conta como "mais um" se nenhum jogo com este título (nem o próprio, na edição) já cumpre o item
                const counts = mode === 'game' && !d.catalog.length;
                out.push(counts && f.done + 1 === f.total
                    ? { tone: 'good', icon: '🎉', text: `Com este, ${f.name} fica completa (${f.total}/${f.total})!`, href: base + '/franquias/' + f.id, cta: 'Franquia' }
                    : { icon: '🧩', text: `Faz parte de ${f.name} (${f.done}/${f.total}${counts ? ' → ' + (f.done + 1) + '/' + f.total : ''}).`, href: base + '/franquias/' + f.id, cta: 'Franquia' });
            }
            return out;
        },
    };
}
</script>
<?php endif; ?>
