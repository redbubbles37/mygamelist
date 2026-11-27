<?php
/**
 * Estojo ao vivo dos formulários (jogo, backlog, dropados): mostra como o jogo vai aparecer no site enquanto você preenche,
 * e serve de área para soltar/escolher a capa.
 * Usa do estado Alpine do formulário: form.title, form.platform, coverUrl, dragging, errors.cover, pickCover(), clearCover(), $refs.file.
 * @var string $caseBand    expressão JS do texto à direita da faixa (ex.: data de finalização)
 * @var string $caseBadges  HTML dos selos no canto da capa (nota, prioridade...)
 * @var string $caseHonor   expressão JS: verdadeiro quando o estojo deve ganhar o contorno dourado
 */
$caseBand = $caseBand ?? "''";
$caseBadges = $caseBadges ?? '';
$caseHonor = $caseHonor ?? 'false';
?>
<div class="card" @paste.window="const f = [...($event.clipboardData?.files ?? [])].find((x) => x.type.startsWith('image/')); if (f) { $event.preventDefault(); pickCover(f); }">
    <div class="flex items-baseline justify-between gap-2">
        <span class="lbl !mb-0">Como vai ficar</span>
        <span class="text-2xs text-slate-500">capa opcional</span>
    </div>
    <div class="mx-auto mt-3 w-full max-w-[11rem] sm:max-w-[13rem] lg:max-w-[15rem]"
         @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; pickCover($event.dataTransfer.files[0])">
        <div class="case transition" :class="[(<?= $caseHonor ?>) && 'case-honor', dragging && 'ring-4 ring-brand-400/70', errors.cover && 'ring-2 ring-danger/70']">
            <div class="case-band" :style="UI.bandStyle(form.platform.trim())"><span x-text="form.platform.trim() || 'Sistema'"></span><span class="case-date" x-text="<?= e($caseBand) ?>"></span></div>
            <div class="case-art">
                <img x-show="coverUrl" :src="coverUrl" alt="Pré-visualização da capa" class="absolute inset-0 h-full w-full object-cover">
                <!-- sem capa: iniciais e o título, como no catálogo -->
                <button type="button" x-show="!coverUrl" @click="$refs.file.click()" class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-gradient-to-br from-neutral to-brand-600/60 p-3 text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white" aria-label="Escolher a capa">
                    <span class="text-5xl font-bold text-white/80" x-text="form.title.trim() ? UI.initials(form.title.trim()) : '?'"></span>
                </button>
                <?= $caseBadges ?>
                <div class="pointer-events-none absolute inset-x-0 bottom-0 border-t border-white/15 bg-black/60 px-3 py-2 text-center backdrop-blur-md">
                    <p class="truncate text-sm font-bold text-white" x-text="form.title.trim() || 'Título do jogo'"></p>
                    <p x-show="!coverUrl" class="text-2xs text-white/70">Arraste a capa aqui ou toque para escolher</p>
                </div>
                <button type="button" x-show="coverUrl" x-cloak @click="clearCover()" aria-label="Remover capa" class="absolute bottom-14 right-2 grid h-8 w-8 place-items-center rounded-full bg-black/70 text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <?= icon('close', 'h-4 w-4') ?>
                </button>
            </div>
        </div>
    </div>
    <div class="mt-3 flex flex-col items-center gap-0.5 text-xs">
        <button type="button" @click="$refs.file.click()" class="font-semibold text-brand-400 hover:underline" x-text="coverUrl ? 'Trocar capa' : 'Escolher capa'"></button>
        <span class="text-slate-500">JPEG, PNG ou WebP · até 2 MB · ou cole com Ctrl+V</span>
    </div>
    <input x-ref="file" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pickCover($event.target.files[0]); $event.target.value = ''">
    <p x-show="errors.cover" x-cloak class="err text-center" x-text="errors.cover"></p>
</div>
