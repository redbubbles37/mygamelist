<?php
/** Visualizador de imagens em tela cheia. Abre com: window.dispatchEvent(new CustomEvent('lightbox', {detail: {items: [{url, caption, meta}], index}})). */
?>
<div x-data="lightbox()" @lightbox.window="open($event.detail)" @keydown.window="key($event)">
    <div x-show="items.length" x-cloak class="fixed inset-0 z-viewer flex flex-col bg-black/95" role="dialog" aria-modal="true" aria-label="Visualizador de imagens" @click.self="close()">
        <div class="flex items-center justify-between gap-3 p-3 text-sm text-white/80">
            <span class="tabular-nums" x-text="(index + 1) + ' / ' + items.length"></span>
            <span class="min-w-0 flex-1 truncate text-center" x-text="cur?.meta || ''"></span>
            <a :href="cur?.url" target="_blank" rel="noopener" class="rounded px-2 py-1 hover:bg-white/10">Abrir original</a>
            <button type="button" x-ref="lbClose" @click="close()" aria-label="Fechar" class="grid h-10 w-10 place-items-center rounded-full hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                <?= icon('close', 'h-6 w-6') ?>
            </button>
        </div>
        <div class="relative flex min-h-0 flex-1 items-center justify-center px-14" @click.self="close()">
            <img :src="cur?.url" :alt="cur?.caption || 'Screenshot'" class="max-h-full max-w-full rounded object-contain shadow-2xl">
            <button type="button" x-show="items.length > 1" @click="go(-1)" aria-label="Anterior" class="absolute left-2 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">‹</button>
            <button type="button" x-show="items.length > 1" @click="go(1)" aria-label="Próxima" class="absolute right-2 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">›</button>
        </div>
        <p class="min-h-[3rem] p-3 text-center text-sm text-white/90" x-text="cur?.caption || ''"></p>
    </div>
</div>

<script>
function lightbox() {
    let opener = null;
    return {
        items: [], index: 0,
        get cur() { return this.items[this.index]; },
        open(d) {
            if (!d?.items?.length) return;
            opener = document.activeElement;
            this.items = d.items; this.index = Math.max(0, Math.min(d.items.length - 1, d.index || 0));
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.lbClose?.focus());
        },
        close() {
            this.items = [];
            document.documentElement.style.overflow = '';
            opener?.focus?.();
        },
        go(d) { this.index = (this.index + d + this.items.length) % this.items.length; },
        key(e) {
            if (!this.items.length) return;
            if (e.key === 'Escape') { e.preventDefault(); this.close(); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); this.go(-1); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); this.go(1); }
        },
    };
}
</script>
