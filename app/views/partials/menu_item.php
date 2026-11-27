<?php
/**
 * Um item do menu (dentro de um x-for com "t"). $mode: 'tile' (bloco grande, mais acessados) ou 'row' (linha compacta).
 * $keyExpr: expressão JS da chave única do item na tela (a mesma usada em flat[]).
 * @var string $mode
 * @var string $keyExpr
 */
?>
<?php if ($mode === 'icon'): /* $idxExpr: expressão JS do índice (para a animação escalonada) */ ?>
<div class="menu-cell" :style="'--i:' + (<?= $idxExpr ?>)">
    <button type="button" :data-key="<?= e($keyExpr) ?>" @click="activateKey(<?= e($keyExpr) ?>)" @mouseenter="hover(<?= e($keyExpr) ?>)" @focus="hover(<?= e($keyExpr) ?>)"
            :aria-current="t.current ? 'page' : null" :title="t.current ? 'Você está aqui' : descOf(t)" class="menu-tile" :class="[isSel(<?= e($keyExpr) ?>) ? 'is-sel' : '', t.current ? 'is-here' : '']">
        <span class="menu-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconOf(t)"/></svg></span>
        <span class="menu-lbl" x-text="labelOf(t)"></span>
    </button>
</div>
<?php elseif ($mode === 'tile'): ?>
<button type="button" :data-key="<?= e($keyExpr) ?>" @click="activateKey(<?= e($keyExpr) ?>)" @mouseenter="hover(<?= e($keyExpr) ?>)" @focus="hover(<?= e($keyExpr) ?>)"
        :aria-current="t.current ? 'page' : null"
        class="relative flex aspect-[16/11] w-full flex-col justify-between overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 to-accent p-3.5 text-left text-on-tile ring-1 ring-slate-50/10 transition duration-150 focus:outline-none motion-reduce:transition-none"
        :class="isSel(<?= e($keyExpr) ?>) ? 'scale-[1.04] ring-4 ring-slate-50/80 shadow-2xl motion-reduce:scale-100' : 'opacity-90 hover:opacity-100'">
    <span class="grid h-10 w-10 place-items-center rounded-xl bg-black/25">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconOf(t)"/></svg>
    </span>
    <span class="block text-sm font-semibold leading-tight" x-text="labelOf(t)"></span>
    <span x-show="t.current" x-cloak class="absolute right-2.5 top-2.5 rounded-full bg-slate-50/90 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-900">Aqui</span>
</button>
<?php else: ?>
<button type="button" :data-key="<?= e($keyExpr) ?>" @click="activateKey(<?= e($keyExpr) ?>)" @mouseenter="hover(<?= e($keyExpr) ?>)" @focus="hover(<?= e($keyExpr) ?>)"
        :aria-current="t.current ? 'page' : null"
        class="flex w-full items-center gap-3 rounded-xl px-2.5 py-2 text-left transition focus:outline-none"
        :class="isSel(<?= e($keyExpr) ?>) ? 'bg-brand-500/20 ring-2 ring-brand-400' : 'hover:bg-slate-50/5'">
    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg" :class="t.current ? 'bg-brand-600 text-on-brand' : 'bg-slate-50/10 text-slate-200'">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconOf(t)"/></svg>
    </span>
    <span class="min-w-0">
        <span class="block truncate text-sm font-semibold text-slate-50" x-text="labelOf(t)"></span>
        <span class="block truncate text-xs text-slate-400" x-text="t.current ? 'Você está aqui' : descOf(t)"></span>
    </span>
</button>
<?php endif; ?>
