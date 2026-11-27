<?php
/**
 * Escolha da imagem do usuário: ícones prontos, uma foto do computador ou nenhuma (inicial do nome).
 * Deve ficar dentro de um componente com saveAvatar({ file, preset }).
 * @var list<string> $presets
 */
?>
<div class="flex flex-wrap gap-2" role="group" aria-label="Ícones prontos">
    <?php foreach ($presets as $p): ?>
    <button type="button" @click="saveAvatar({ preset: '<?= e($p) ?>' })"
            class="grid h-12 w-12 place-items-center rounded-xl bg-slate-50/10 text-2xl transition hover:scale-110 hover:bg-brand-500/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 motion-reduce:hover:scale-100"
            aria-label="Usar o ícone <?= e($p) ?>"><?= e($p) ?></button>
    <?php endforeach; ?>
</div>
<div class="mt-4 flex flex-wrap items-center gap-3">
    <label class="btn cursor-pointer focus-within:ring-2 focus-within:ring-brand-400">
        📷 Enviar uma imagem do computador
        <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="$event.target.files[0] && saveAvatar({ file: $event.target.files[0] }); $event.target.value = ''">
    </label>
    <button type="button" class="btn-ghost" @click="saveAvatar({})">Usar a inicial do nome</button>
    <span class="caption">JPEG, PNG ou WebP, até 8 MB. O centro vira um quadrado.</span>
</div>
