<?php
/**
 * Faixa de indicadores do recorte ativo.
 *
 * Eram quatro cartões com borda e sombra cada um. Viraram uma faixa dividida
 * por réguas (ver .gh-stats): mesma informação, um quarto das molduras.
 *
 * @var array $shell  ['stats']
 */
$s = $shell['stats'];
?>
<div class="gh-stats">
    <div class="gh-stat">
        <div class="gh-stat-rotulo">Eventos</div>
        <div class="gh-stat-valor"><?= hnum($s['eventos']) ?></div>
        <div class="gh-stat-nota">
            <?= hnum($s['cidades']) ?> cidade<?= $s['cidades'] === 1 ? '' : 's' ?>
            <?php if ($s['cancelados'] > 0): ?>
                · <?= hnum($s['cancelados']) ?> cancelado<?= $s['cancelados'] === 1 ? '' : 's' ?> fora da conta
            <?php endif; ?>
        </div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat-rotulo">A/V terceirizado</div>
        <div class="gh-stat-valor azul"><?= hnum($s['terceirizado']) ?></div>
        <div class="gh-stat-nota">de <?= hnum($s['eventos']) ?> eventos</div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat-rotulo">NF pendentes</div>
        <div class="gh-stat-valor <?= $s['semNf'] > 0 ? 'ambar' : 'verde' ?>"><?= hnum($s['semNf']) ?></div>
        <div class="gh-stat-nota"><?= $s['semNf'] > 0 ? 'aguardando emissão' : 'todas recebidas' ?></div>
    </div>
    <div class="gh-stat">
        <div class="gh-stat-rotulo">Custo total</div>
        <div class="gh-stat-valor"><?= hmoedaHtml($s['custoTotal']) ?></div>
        <div class="gh-stat-nota">hotel + A/V + logística</div>
    </div>
</div>
