<?php
/**
 * paginas/hotel/relatorio.php — tabelas completas e exportação.
 *
 * Duas abas sobre a mesma linha de dados: a geral, com contrato e valores, e
 * a de entregas, com A/V e materiais. As colunas de ambas — e as 43 do CSV —
 * saem de HotelCalculo::COLUNAS_RELATORIO, uma definição só.
 *
 * @var MRPHP  $this
 * @var array  $shell
 * @var string $sub      'geral' | 'entrega'
 * @var array  $rel      ['linhas', 'resumo']
 * @var array  $colunas  chave => rótulo da aba atual
 */

$linhas = $rel['linhas'];
$r      = $rel['resumo'];

$MESES = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
          'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

/** Formata uma célula conforme a natureza da coluna. */
$celula = function (string $chave, mixed $v): string {
    if (is_bool($v)) {
        return $v
            ? '<span class="gh-badge ok">Sim</span>'
            : '<span class="gh-badge warn">Não</span>';
    }

    if (str_starts_with($chave, 'valor') || $chave === 'total') {
        return hesc(hmoeda($v));
    }

    if (str_starts_with($chave, 'data') || $chave === 'dataAss') {
        return hesc(hdata($v));
    }

    if (in_array($chave, ['statusContrato', 'statusAv', 'statusVm', 'entregaStatus', 'entregaAv'], true)) {
        $partes = array_filter(array_map('trim', explode(',', (string)$v)));
        if (!$partes) {
            return '<span class="gh-ev-meta">—</span>';
        }
        $out = '';
        foreach ($partes as $p) {
            [$rotulo, $variante] = hbadge('hotel', $p);
            $out .= '<span class="gh-badge ' . hesc($variante) . '">' . hesc($rotulo) . '</span> ';
        }
        return trim($out);
    }

    if ($chave === 'responsavelAv') {
        return $v === 'terceirizado' ? 'Terceirizado' : 'Do hotel';
    }

    // Listas de OC vêm sem espaço ("738,739,896", "1196/1197/1198"): sem um
    // ponto de quebra a coluna não encolhe, e era ela que fazia a tabela
    // passar da largura da folha impressa.
    if ($chave === 'ocNum' && (string)$v !== '') {
        return preg_replace('#[,/]#', '$0<wbr>', hesc($v));
    }

    return (string)$v !== '' ? hesc($v) : '<span class="gh-ev-meta">—</span>';
};

$numerica = static fn(string $chave): bool =>
    str_starts_with($chave, 'valor') || $chave === 'total' || $chave === 'capacidade';
?>

<div class="gh-filtros gh-nao-imprime">
    <div class="gh-seg">
        <a class="gh-periodo<?= $sub === 'geral' ? ' ativo' : '' ?>"
           href="<?= hesc(hurl($this, 'relatorio')) ?>">Relatório geral</a>
        <a class="gh-periodo<?= $sub === 'entrega' ? ' ativo' : '' ?>"
           href="<?= hesc(hurl($this, 'relatorio/entregas')) ?>">Entregas e A/V</a>
    </div>

</div>

<!-- Cabeçalho que só aparece no papel: sem ele a folha sai sem contexto. -->
<div class="gh-print-cab" aria-hidden="true">
    <strong>Acme EUA · On The Road — <?= hesc($sub === 'entrega' ? 'Entregas e A/V' : 'Relatório geral') ?></strong>
    <span>
        <?= hesc($shell['rotuloFiltro']) ?>
        · <?= hnum(count($linhas)) ?> evento(s)
        · emitido em <?= hesc(date('d/m/Y')) ?>
    </span>
</div>

<?php if ($sub === 'entrega'): ?>
    <div class="gh-grid-2">
        <div class="gh-painel">
            <div class="gh-painel-cab"><div class="gh-painel-titulo">Fornecedores de A/V</div></div>
            <div class="gh-painel-corpo">
                <div class="gh-kpis">
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">Com fornecedor</span><strong class="gh-kpi-valor"><?= hnum($r['comAv']) ?></strong></div>
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">No prazo</span><strong class="gh-kpi-valor verde"><?= hnum($r['avNoPrazo']) ?></strong></div>
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">Atrasados</span><strong class="gh-kpi-valor ambar"><?= hnum($r['avAtrasado']) ?></strong></div>
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">Com problema</span><strong class="gh-kpi-valor"><?= hnum($r['avProblema']) ?></strong></div>
                </div>
            </div>
        </div>
        <div class="gh-painel">
            <div class="gh-painel-cab"><div class="gh-painel-titulo">Entrega de materiais</div></div>
            <div class="gh-painel-corpo">
                <div class="gh-kpis">
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">No prazo</span><strong class="gh-kpi-valor verde"><?= hnum($r['entNoPrazo']) ?></strong></div>
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">Atrasadas</span><strong class="gh-kpi-valor ambar"><?= hnum($r['entAtrasado']) ?></strong></div>
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">Com problema</span><strong class="gh-kpi-valor"><?= hnum($r['entProblema']) ?></strong></div>
                    <div class="gh-kpi"><span class="gh-kpi-rotulo">Aguardando</span><strong class="gh-kpi-valor"><?= hnum($r['entPendente']) ?></strong></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="gh-painel">
    <div class="gh-painel-cab gh-nao-imprime">
        <div>
            <div class="gh-painel-titulo"><?= hnum(count($linhas)) ?> evento(s)</div>
            <div class="gh-painel-sub">
                <?= hnum(count($colunas)) ?> colunas nesta aba · <?= hnum(count(HotelCalculo::COLUNAS_RELATORIO)) ?> na exportação
            </div>
        </div>
        <?php if ($sub === 'geral'): ?>
            <div class="gh-painel-total">
                <span class="gh-painel-total-rotulo">Total</span>
                <strong><?= hesc(hmoeda($r['total'])) ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$linhas): ?>
        <div class="gh-vazio">Nenhum evento neste período.</div>
    <?php else: ?>

    <?php include __DIR__ . '/partes/tabela-barra.php'; ?>

        <div class="gh-tabela-wrap">
            <table class="gh-tabela gh-tabela-rel">
                <caption class="sr-only">
                    <?= hesc($sub === 'entrega' ? 'Entregas e equipamentos A/V' : 'Relatório geral de eventos') ?>
                </caption>
                <thead>
                    <tr>
                        <?php foreach ($colunas as $chave => $rotulo): ?>
                            <th scope="col"<?= $numerica($chave) ? ' class="num"' : '' ?>><?= hesc($rotulo) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linhas as $l): ?>
                        <tr>
                            <?php foreach (array_keys($colunas) as $chave): ?>
                                <td<?= $numerica($chave) ? ' class="num"' : '' ?>><?= $celula($chave, $l[$chave] ?? '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if ($sub === 'geral'): ?>
                    <tfoot>
                        <tr>
                            <?php $i = 0; foreach (array_keys($colunas) as $chave): ?>
                                <?php if ($i === 0): ?>
                                    <th scope="row">Total</th>
                                <?php elseif ($chave === 'total'): ?>
                                    <td class="num forte"><?= hesc(hmoeda($r['total'])) ?></td>
                                <?php else: ?>
                                    <td></td>
                                <?php endif; ?>
                            <?php $i++; endforeach; ?>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>
