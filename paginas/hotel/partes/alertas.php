<?php
/**
 * Banner de eventos próximos.
 *
 * Ignora o filtro de período de propósito: é aviso do que está chegando, e
 * sumir porque a pessoa está olhando outro semestre seria justamente o
 * momento em que ele faz falta.
 *
 * @var MRPHP $this
 * @var array $shell  ['alertas' => ['proximos', 'emBreve']]
 */
$proximos = $shell['alertas']['proximos'] ?? [];
$emBreve  = $shell['alertas']['emBreve'] ?? [];

if (!$proximos && !$emBreve) {
    return;
}
?>
<div class="gh-alerta" role="status">
    <?php if ($proximos): ?>
        <div class="gh-alerta-bloco">
            <div class="gh-alerta-titulo">
                <?= hicone('alerta') ?> Eventos nos próximos 7 dias
            </div>
            <ul class="gh-alerta-lista">
                <?php foreach ($proximos as $e): ?>
                    <li>
                        <strong><?= hesc($e['cidade'] !== '' ? $e['cidade'] : $e['nome']) ?></strong>
                        <span class="gh-alerta-quando<?= $e['dias'] <= 1 ? ' urgente' : '' ?>"><?= hesc($e['quando']) ?></span>
                        <?php if ($e['pendencias'] > 0): ?>
                            <span class="gh-alerta-pend"><?= hesc($e['pendencias']) ?> pendência<?= $e['pendencias'] > 1 ? 's' : '' ?></span>
                        <?php else: ?>
                            <span class="gh-alerta-ok">tudo certo</span>
                        <?php endif; ?>
                        <button type="button" class="gh-alerta-link" data-checklist="<?= hesc($e['id']) ?>">ver checklist</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($emBreve): ?>
        <div class="gh-alerta-bloco gh-alerta-breve">
            <div class="gh-alerta-titulo"><?= hicone('calendario') ?> Em breve (até 30 dias)</div>
            <div class="gh-alerta-chips">
                <?php foreach ($emBreve as $e): ?>
                    <span class="gh-chip"><?= hesc($e['cidade'] !== '' ? $e['cidade'] : $e['nome']) ?> · <?= hesc($e['quando']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
