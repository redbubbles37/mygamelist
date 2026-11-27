<?php /** Aviso padrão das páginas de exemplo. @var string $demoText  texto depois de "Página de exemplo" (HTML já escapado) */ ?>
<div class="demo-note" role="note">
    <p><strong class="text-slate-50">Página de exemplo</strong><?= $demoText ?></p>
    <a href="<?= e(url('/exemplos')) ?>" class="font-semibold text-brand-400 hover:underline">Todos os exemplos →</a>
</div>
