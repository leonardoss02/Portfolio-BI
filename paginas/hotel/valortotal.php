<?php
/**
 * paginas/hotel/valortotal.php — consolidação de custos por evento.
 *
 * A tabela é renderizada no servidor: chega pronta na primeira pintura, sem
 * depender de fetch. O JS cuida só do que é interação local — busca textual e
 * ordenação de coluna —, sobre um conjunto de dezenas de linhas.
 *
 * @var MRPHP $this
 * @var array $shell
 * @var array $dados  ['eventos', 'cidades', 'totais', 'cidade']
 */

$eventos  = $dados['eventos'];
$totais   = $dados['totais'];
$parcelas = [
    'hotel'       => ['Hotel', 'hotel'],
    'equipamento' => ['A/V', 'av'],
    'videomaker'  => ['Videomaker', 'video'],
    'transporte'  => ['Transporte', 'aviao'],
    'frete'       => ['Frete', 'caminhao'],
    'material'    => ['Materiais', 'caixa'],
    'custoExtra'  => ['Custos extras', 'dinheiro'],
];
?>


<div class="gh-painel">
    <div class="gh-painel-cab">
        <div>
            <div class="gh-painel-titulo">
                <?= hnum(count($eventos)) ?> evento<?= count($eventos) === 1 ? '' : 's' ?>
            </div>
            <div class="gh-painel-sub">
                Hotel + A/V + videomaker + transporte + frete + materiais.
                Eventos cancelados não entram. Valores em R$.
            </div>
        </div>
        <div class="gh-painel-total">
            <span class="gh-painel-total-rotulo">Total</span>
            <strong><?= hmoedaHtml($totais['total']) ?></strong>
        </div>
    </div>

    <?php if (!$eventos): ?>
        <div class="gh-vazio">Nenhum evento neste recorte.</div>
    <?php else: ?>

    <?php include __DIR__ . '/partes/tabela-barra.php'; ?>

        <div class="gh-tabela-wrap">
            <table class="gh-tabela gh-tabela-total" id="gh-tabela-valortotal">
                <caption class="sr-only">Custo consolidado por evento</caption>
                <thead>
                    <tr>
                        <th scope="col">Evento</th>
                        <?php foreach ($parcelas as [$rotulo, $icone]): ?>
                            <th scope="col" class="num"><?= hesc($rotulo) ?></th>
                        <?php endforeach; ?>
                        <th scope="col" class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventos as $e): ?>
                        <tr data-busca="<?= hesc(mb_strtolower($e['nome'] . ' ' . $e['cidade'], 'UTF-8')) ?>">
                            <td>
                                <div class="gh-ev-cidade">
                                    <?= hesc($e['cidade'] !== '' ? $e['cidade'] : '(sem cidade)') ?>
                                    <?php if ($e['uf'] !== ''): ?><span class="gh-ev-uf"><?= hesc($e['uf']) ?></span><?php endif; ?>
                                </div>
                                <div class="gh-ev-meta">
                                    <?= hesc(hdata($e['data'])) ?>
                                    <?php if ($e['nome'] !== ''): ?> · <?= hesc($e['nome']) ?><?php endif; ?>
                                    <?php if ($e['pendentes'] > 0): ?>
                                        <span class="gh-ev-pend"><?= hesc($e['pendentes']) ?> transporte<?= $e['pendentes'] > 1 ? 's' : '' ?> pendente<?= $e['pendentes'] > 1 ? 's' : '' ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php foreach (array_keys($parcelas) as $chave): ?>
                                <?php $v = (float)($e['parcelas'][$chave] ?? 0); ?>
                                <td class="num<?= $v > 0 ? '' : ' zero' ?>"><?= $v > 0 ? hesc(hnum($v, 2)) : '—' ?></td>
                            <?php endforeach; ?>
                            <td class="num forte"><?= hesc(hnum($e['total'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row">Total do período</th>
                        <?php foreach (array_keys($parcelas) as $chave): ?>
                            <?php $v = (float)($totais['parcelas'][$chave] ?? 0); ?>
                            <td class="num"><?= $v > 0 ? hesc(hnum($v, 2)) : '—' ?></td>
                        <?php endforeach; ?>
                        <td class="num forte"><?= hesc(hnum($totais['total'], 2)) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>

