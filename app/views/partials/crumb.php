<?php
/**
 * Faixa "onde você está": o nome da seção e as páginas irmãs (a atual destacada). O menu em tela cheia continua sendo o caminho
 * principal; isto só dá um lugar à página. Vem de NavService, a mesma lista do menu.
 * @var string|null $navPath  caminho da página (preenchido por PageController::view)
 * @var array|null $me
 */
$here = \App\Services\NavService::locate($navPath ?? '/');
if ($here === null) {
    return;
}
[$crumbGroup, $crumbItem] = $here;
$crumbMe = $me['username'] ?? null;
$crumbLinks = array_values(array_filter($crumbGroup['items'], fn($t) => isset($t['href']) && !($t['id'] === 'profile' && !$crumbMe)));
if (count($crumbLinks) < 2) {
    return;
}
?>
<nav class="crumb no-print" aria-label="Você está em: <?= e($crumbGroup['title']) ?>">
    <span class="crumb-title"><?= e($crumbGroup['title']) ?></span>
    <ul class="crumb-list">
        <?php foreach ($crumbLinks as $t):
            $on = $t['id'] === $crumbItem['id'];
            $href = str_replace('{me}', rawurlencode((string) $crumbMe), $t['href']); ?>
        <li><a href="<?= e(url($href)) ?>" class="crumb-link<?= $on ? ' is-on' : '' ?>"<?= $on ? ' aria-current="location"' : '' ?>><?= e($t['label']) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php $canShare = in_array($crumbItem['id'], ['stats', 'goals', 'retro', 'profile'], true);
          $canPresent = in_array($crumbItem['id'], ['stats', 'goals', 'retro', 'goty', 'timeline', 'heat', 'time'], true); ?>
    <?php if ($canShare || $canPresent): ?>
    <div class="ml-auto hidden shrink-0 items-center gap-1.5 sm:flex">
        <?php if ($canShare): ?><button type="button" class="crumb-link" @click="$data.shareCard?.()" title="Baixar um cartão em imagem para compartilhar">🖼 Cartão</button><?php endif; ?>
        <?php if ($canPresent): ?><button type="button" class="crumb-link" onclick="Present.toggle()" title="Tela cheia, sem menus">▶ Apresentar</button><?php endif; ?>
    </div>
    <?php endif; ?>
</nav>
