<?php
/** @var int $status */
/** @var string $message */
/** @var bool $signedIn */
$is404 = $status === 404;
$headline = $is404 ? 'Esta fase não existe' : ($status === 401 ? 'Esta fase pede login' : 'O jogo travou por um instante');
$text = $is404 ? 'O endereço que você abriu não leva a lugar nenhum. Pode ter sido digitado errado ou a página mudou de lugar.'
    : ($status === 401 ? 'Entre na sua conta para continuar.' : 'Não foi culpa sua. Tente de novo em alguns segundos; se repetir, avise quem cuida do site.');
?>
<main class="page page-narrow grid min-h-screen place-items-center !pb-10 text-center">
    <div class="hero-box w-full max-w-xl p-8 sm:p-12">
        <p class="kicker-hero">Erro <?= (int) $status ?></p>
        <p class="mt-3 text-7xl font-black tracking-tight text-white sm:text-8xl" aria-hidden="true"><?= $is404 ? '404' : (int) $status ?></p>
        <h1 class="t-hero mt-2"><?= e($headline) ?></h1>
        <p class="mx-auto mt-3 max-w-md text-sm text-white/75"><?= e($text) ?></p>
        <?php if (!$is404 && $message !== ''): ?><p class="mx-auto mt-2 max-w-md text-xs text-white/50"><?= e($message) ?></p><?php endif; ?>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="<?= e(url($signedIn ? '/' : '/login')) ?>" class="btn-light"><?= $signedIn ? 'Voltar ao Painel' : 'Entrar' ?></a>
            <?php if ($signedIn): ?><a href="<?= e(url('/jogos')) ?>" class="btn-ghost !border-white/30 !text-white">Abrir o Catálogo</a><?php endif; ?>
        </div>
        <?php if ($signedIn): ?><p class="mt-6 text-xs text-white/50">Dica: aperte <kbd class="rounded border border-white/25 px-1">M</kbd> para abrir o menu e buscar a página que você queria.</p><?php endif; ?>
    </div>
</main>
