<?php
/**
 * Faixa de título — última linha do MESMO cartão de `abas.php` (.gh-nav),
 * não um cabeçalho à parte. Substituiu o antigo `<header class="header">`
 * escuro (padrão dos dashboards de BI, css/bi-theme.css): aqui o topo do
 * módulo já é a navegação, e ter dois cartões empilhados com linguagens
 * visuais diferentes (um claro, um escuro em gradiente) lia como dois
 * sistemas colados. O botão "Atualizar" migrou pra `.gh-topo-dir`, junto
 * do dark mode, dentro de `abas.php`.
 *
 * @var MRPHP $this
 * @var array $shell  ['titulo', 'subtitulo', 'stats']
 */
?>
<div class="gh-titulo-faixa">
    <div>
        <div class="gh-titulo-nome"><?= hesc($shell['titulo']) ?></div>
        <?php if (!empty($shell['subtitulo'])): ?>
            <div class="gh-titulo-sub"><?= hesc($shell['subtitulo']) ?></div>
        <?php endif; ?>
    </div>

    <?php if (empty($shell['semDados'])): ?>
        <span class="gh-titulo-badge">
            <?= hnum($shell['stats']['eventos']) ?> evento<?= (int)$shell['stats']['eventos'] === 1 ? '' : 's' ?>
        </span>
    <?php endif; ?>
</div>
