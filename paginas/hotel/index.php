<?php
/**
 * paginas/hotel/index.php — visão geral.
 *
 * Os números vêm todos prontos do servidor, do mesmo cálculo que alimenta as
 * demais telas. O JS só desenha. No sistema antigo cada bloco refazia a sua
 * própria conta no cliente, e foi assim que as fórmulas divergiram.
 *
 * Ordem dos painéis, de cima para baixo:
 *
 *   1. Resumo executivo      | Composição do custo
 *   2. Eventos mais caros    | Projeção e nota fiscal
 *   3. Gasto por mês         | Situação dos contratos
 *   4. Pendências            | Tempos médios do processo
 *   5. Avaliação dos hotéis  | Avaliação dos fornecedores A/V
 *
 * O dinheiro vem primeiro e desce do agregado para o detalhe; o estado
 * operacional (pendência, prazo, avaliação) vem depois.
 *
 * @var MRPHP $this
 * @var array $shell
 * @var array $vg     agregados de HotelCalculo::visaoGeral()
 */

$k   = $vg['kpis'];
$pen = $vg['pendencias'];

/**
 * Rótulo e cor de cada parcela de custo, usados no breakdown e no gráfico.
 *
 * Os slots --gh-s1..s7 (css/hotel.css) espelham os de bootGraficos() no
 * controller: mesma parcela, mesmo slot, nos dois lugares. A cor segue a
 * PARCELA e não a posição — um filtro que zere uma delas não pode repintar
 * as outras.
 */
$parcelas = [
    'hotel'       => ['Hotel',      '--gh-s1'],
    'equipamento' => ['A/V',        '--gh-s2'],
    'videomaker'  => ['Videomaker', '--gh-s3'],
    'transporte'  => ['Transporte', '--gh-s4'],
    'frete'       => ['Frete',      '--gh-s5'],
    'material'    => ['Materiais',  '--gh-s6'],
    'custoExtra'  => ['Custos extras', '--gh-s7'],
];
?>

<?php if ($vg['eventos'] === 0): ?>
    <div class="gh-vazio">Nenhum evento neste período.</div>
<?php else: ?>

<!-- ── 1. Resumo executivo | Composição do custo ─────────────────────── -->
<div class="gh-grid-2">
    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div class="gh-painel-titulo">Resumo executivo</div>
            <div class="gh-painel-sub"><?= hnum($k['comCusto']) ?> evento(s) com custo lançado</div>
        </div>
        <div class="gh-painel-corpo">
            <?php
            /*
             * Total geral em cima, sozinho; os outros três numa linha abaixo.
             * Lado a lado sobravam ~260px para os três secundários e o "Mais
             * barato" caía para a linha seguinte — os quatro desalinhavam
             * justamente onde servem para ser comparados de relance.
             *
             * Números de destaque sem centavos: em ordem de grandeza eles não
             * decidem nada, e é o que fazia o herói estourar a largura.
             */
            ?>
            <div class="gh-resumo-destaque">
                <div class="gh-kpi">
                    <span class="gh-kpi-rotulo">Total geral</span>
                    <strong class="gh-kpi-valor heroi"><?= hmoedaHtml($k['total'], true, false) ?></strong>
                    <span class="gh-kpi-nota"><?= hesc($shell['rotuloFiltro']) ?></span>
                </div>
            </div>

            <div class="gh-resumo-secundarios">
                <div class="gh-kpi">
                    <span class="gh-kpi-rotulo">Média por evento</span>
                    <strong class="gh-kpi-valor"><?= hmoedaHtml($k['media'], true, false) ?></strong>
                    <span class="gh-kpi-nota"><?= hnum($k['comCusto']) ?> evento(s)</span>
                </div>
                <?php if ($k['maisCaro']): ?>
                    <div class="gh-kpi">
                        <span class="gh-kpi-rotulo">Mais caro</span>
                        <strong class="gh-kpi-valor vermelho"><?= hmoedaHtml($k['maisCaro']['total'], true, false) ?></strong>
                        <span class="gh-kpi-nota"><?= hesc($k['maisCaro']['cidade']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($k['maisBarato']): ?>
                    <div class="gh-kpi">
                        <span class="gh-kpi-rotulo">Mais barato</span>
                        <strong class="gh-kpi-valor verde"><?= hmoedaHtml($k['maisBarato']['total'], true, false) ?></strong>
                        <span class="gh-kpi-nota"><?= hesc($k['maisBarato']['cidade']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div class="gh-painel-titulo">Composição do custo</div>
            <div class="gh-painel-sub">As sete parcelas somam o total</div>
        </div>
        <div class="gh-painel-corpo">
            <?php foreach ($vg['breakdown'] as $b): ?>
                <?php [$rotulo, $token] = $parcelas[$b['chave']] ?? [$b['chave'], '--muted']; ?>
                <div class="gh-barra-linha">
                    <span class="gh-barra-rotulo"><?= hesc($rotulo) ?></span>
                    <span class="gh-barra-track">
                        <span class="gh-barra-fill" style="width:<?= hesc($b['pct']) ?>%;background:var(<?= hesc($token) ?>)"></span>
                    </span>
                    <span class="gh-barra-valor"><?= hesc(hmoeda($b['valor'])) ?></span>
                    <span class="gh-barra-pct"><?= hesc(number_format($b['pct'], 1, ',', '.')) ?>%</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── 2. Eventos mais caros | Projeção e nota fiscal ────────────────── -->
<div class="gh-grid-2">
    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div class="gh-painel-titulo">Eventos mais caros</div>
            <div class="gh-painel-sub">Os cinco maiores do recorte</div>
        </div>
        <div class="gh-painel-corpo">
            <?php $maior = $vg['top'] ? $vg['top'][0]['total'] : 1; ?>
            <?php foreach ($vg['top'] as $t): ?>
                <div class="gh-barra-linha">
                    <span class="gh-barra-rotulo" title="<?= hesc($t['nome']) ?>"><?= hesc($t['cidade']) ?></span>
                    <span class="gh-barra-track">
                        <span class="gh-barra-fill" style="width:<?= hesc($maior > 0 ? round($t['total'] / $maior * 100) : 0) ?>%;background:var(--gh-s1)"></span>
                    </span>
                    <span class="gh-barra-valor"><?= hesc(hmoeda($t['total'])) ?></span>
                    <span class="gh-barra-pct"><?= hesc(hdata($t['data'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div class="gh-painel-titulo">Projeção e nota fiscal</div>
        </div>
        <div class="gh-painel-corpo">
            <div class="gh-kpis">
                <div class="gh-kpi">
                    <span class="gh-kpi-rotulo">Custo projetado</span>
                    <strong class="gh-kpi-valor"><?= hmoedaHtml($vg['projecao']['projetado'], true, false) ?></strong>
                    <span class="gh-kpi-nota">
                        <?= hnum($vg['projecao']['futuros']) ?> evento(s) futuro(s) × média
                    </span>
                </div>
                <div class="gh-kpi">
                    <span class="gh-kpi-rotulo">NF pendentes</span>
                    <strong class="gh-kpi-valor <?= $vg['projecao']['semNf'] > 0 ? 'ambar' : 'verde' ?>">
                        <?= hnum($vg['projecao']['semNf']) ?>
                    </strong>
                    <span class="gh-kpi-nota"><?= hnum($vg['projecao']['comNf']) ?> já recebidas</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── 3. Gasto por mês | Situação dos contratos ─────────────────────── -->
<div class="gh-grid-2">
    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div>
                <div class="gh-painel-titulo">Gasto por mês</div>
                <div class="gh-painel-sub">Todas as sete parcelas, empilhadas</div>
            </div>
            <?php
            /*
             * Três cores da paleta ficam abaixo de 3:1 sobre a superfície
             * clara. A regra que vale nesse caso pede rótulo visível ou uma
             * visão em tabela — a tabela equivalente já existe aqui (era
             * revelada só quando o CDN do Chart.js caía) e este botão a torna
             * alcançável a qualquer momento.
             */
            ?>
            <button type="button" class="gh-btn gh-btn-mini" data-ver-tabela="gh-tab-meses"
                    aria-expanded="false" aria-controls="gh-tab-meses">Ver tabela</button>
        </div>
        <div class="gh-painel-corpo">
            <div class="gh-canvas-wrap alto"><canvas id="gh-chart-meses" height="250"></canvas></div>
            <div class="gh-tabela-wrap gh-fallback" id="gh-tab-meses" hidden>
                <table class="gh-tabela gh-tabela-grafico">
                    <caption class="sr-only">Gasto mensal por parcela</caption>
                    <thead>
                        <tr>
                            <th scope="col">Mês</th>
                            <?php
                            /* O "R$" vai uma vez ao cabeçalho, não em cada
                               célula: repetido em seis colunas era ele que
                               empurrava o número para a segunda linha. */
                            foreach ($parcelas as [$rotulo]): ?>
                                <th scope="col"><?= hesc($rotulo) ?> <span class="gh-th-unidade">(R$)</span></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vg['porMes'] as $m): ?>
                            <tr>
                                <?php
                                // 'AAAA-MM' vira '01/26', o mesmo rótulo do eixo
                                // do gráfico — a tabela é a mesma leitura, não
                                // outro jeito de escrever o mês.
                                $rotuloMes = substr($m['mes'], 5, 2) . '/' . substr($m['mes'], 2, 2);
                                ?>
                                <td><?= hesc($rotuloMes) ?></td>
                                <?php foreach (array_keys($parcelas) as $chave): ?>
                                    <?php $v = (float)($m['parcelas'][$chave] ?? 0); ?>
                                    <td><?= $v > 0 ? hesc(hnum($v, 2)) : '—' ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div>
                <div class="gh-painel-titulo">Situação dos contratos</div>
                <div class="gh-painel-sub">Cor de estado, a mesma nas duas</div>
            </div>
            <button type="button" class="gh-btn gh-btn-mini" data-ver-tabela="gh-tab-contratos"
                    aria-expanded="false" aria-controls="gh-tab-contratos">Ver tabela</button>
        </div>
        <div class="gh-painel-corpo">
            <?php
            /*
             * Duas roscas, não uma: hotel e fornecedor de A/V têm ciclos de
             * contrato próprios, e somá-los num gráfico só escondia que um
             * pode estar todo assinado enquanto o outro nem começou.
             */
            ?>
            <div class="gh-roscas">
                <?php foreach ([
                    ['contratos',   'gh-chart-contratos',   'Hotéis',          'hotel'],
                    ['contratosAv', 'gh-chart-contratos-av', 'Fornecedores A/V', 'equipamento'],
                ] as [$chave, $id, $rotulo, $ent]): ?>
                    <div class="gh-rosca">
                        <div class="gh-rosca-titulo"><?= hesc($rotulo) ?></div>
                        <?php if (!$vg[$chave]): ?>
                            <div class="gh-vazio">Nenhum registro no recorte.</div>
                        <?php else: ?>
                            <div class="gh-canvas-wrap"><canvas id="<?= hesc($id) ?>" height="190"></canvas></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="gh-tabela-wrap gh-fallback" id="gh-tab-contratos" hidden>
                <table class="gh-tabela gh-tabela-grafico">
                    <caption class="sr-only">Situação dos contratos, por tipo de fornecedor</caption>
                    <thead>
                        <tr><th scope="col">Situação</th><th scope="col">Hotéis</th><th scope="col">Fornecedores A/V</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        // União dos status que aparecem em qualquer um dos dois:
                        // uma situação que só existe de um lado precisa da linha
                        // para mostrar o zero do outro.
                        $situacoes = array_keys($vg['contratos'] + $vg['contratosAv']);
                        foreach ($situacoes as $st):
                        ?>
                            <tr>
                                <td><?= hesc(hbadge('hotel', (string)$st)[0]) ?></td>
                                <td><?= hesc($vg['contratos'][$st] ?? 0) ?></td>
                                <td><?= hesc($vg['contratosAv'][$st] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── 4. Pendências | Tempos médios ─────────────────────────────────── -->
<div class="gh-grid-2">
    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div class="gh-painel-titulo">Pendências</div>
            <div class="gh-painel-sub">sobre <?= hnum($pen['total']) ?> eventos do período</div>
        </div>
        <div class="gh-painel-corpo">
            <?php
            /*
             * Só nota fiscal e ordem de compra: pendência é documento que
             * falta. "Quartos reservados" e "A/V terceirizado" estavam aqui
             * mas não são pendência — o segundo nem sequer é algo a resolver,
             * é uma escolha de como o evento foi montado.
             *
             * Uma cor só: as duas linhas medem a mesma grandeza (quantos dos
             * N eventos já têm o documento). A comparação é entre
             * comprimentos, não entre categorias.
             */
            foreach ([
                ['NF recebida', $pen['nf']],
                ['OC aberta',   $pen['oc']],
            ] as [$rotulo, $n]):
                $pct = $pen['total'] > 0 ? round($n / $pen['total'] * 100) : 0;
            ?>
                <div class="gh-barra-linha">
                    <span class="gh-barra-rotulo"><?= hesc($rotulo) ?></span>
                    <span class="gh-barra-track">
                        <span class="gh-barra-fill" style="width:<?= hesc($pct) ?>%;background:var(--gh-s1)"></span>
                    </span>
                    <span class="gh-barra-valor"><?= hesc($n) ?>/<?= hesc($pen['total']) ?></span>
                    <span class="gh-barra-pct"><?= hesc($pct) ?>%</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="gh-painel">
        <div class="gh-painel-cab">
            <div class="gh-painel-titulo">Tempos médios do processo</div>
            <div class="gh-painel-sub">Em dias. Evento sem uma das datas fica de fora, não conta como zero.</div>
        </div>
        <div class="gh-painel-corpo">
            <div class="gh-tempos">
                <?php foreach ($vg['tempos'] as $t): ?>
                    <div class="gh-tempo">
                        <div class="gh-tempo-rotulo"><?= hesc($t['rotulo']) ?></div>
                        <?php if ($t['media'] === null): ?>
                            <div class="gh-tempo-valor sem">—</div>
                            <div class="gh-tempo-nota">Dados insuficientes</div>
                        <?php else: ?>
                            <div class="gh-tempo-valor"><?= hesc($t['media']) ?> <span>dias</span></div>
                            <div class="gh-tempo-nota">
                                mín <?= hesc($t['min']) ?> · máx <?= hesc($t['max']) ?> · <?= hesc($t['n']) ?> eventos
                            </div>
                            <?php if ($t['negativos'] > 0): ?>
                                <div class="gh-tempo-alerta" title="A segunda data é anterior à primeira">
                                    <?= hesc($t['negativos']) ?> fora de ordem
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── 5. Avaliações ─────────────────────────────────────────────────── -->
<div class="gh-grid-2">
    <?php foreach ([['hotel', 'Avaliação dos hotéis'], ['equipamento', 'Avaliação dos fornecedores A/V']] as [$chave, $titulo]): ?>
        <?php $a = $vg['avaliacoes'][$chave]; ?>
        <div class="gh-painel">
            <div class="gh-painel-cab">
                <div class="gh-painel-titulo"><?= hesc($titulo) ?></div>
                <div class="gh-painel-sub">
                    <?php if ($a['media'] !== null): ?>
                        média <?= hesc(number_format($a['media'], 1, ',', '.')) ?> · <?= hnum($a['avaliados']) ?> avaliado(s)
                    <?php else: ?>
                        nenhuma avaliação registrada
                    <?php endif; ?>
                </div>
            </div>
            <div class="gh-painel-corpo">
                <?php if ($a['avaliados'] === 0): ?>
                    <div class="gh-vazio">Sem avaliações neste período.</div>
                <?php else: ?>
                    <div class="gh-canvas-wrap"><canvas id="gh-chart-av-<?= hesc($chave) ?>" height="150"></canvas></div>
                    <table class="gh-tabela gh-fallback" hidden>
                        <caption class="sr-only"><?= hesc($titulo) ?></caption>
                        <thead><tr><th scope="col">Nota</th><th scope="col" class="num">Qtd</th></tr></thead>
                        <tbody>
                            <?php for ($n = 5; $n >= 1; $n--): ?>
                                <tr><td><?= hesc($n) ?>★</td><td class="num"><?= hesc($a['dist'][$n]) ?></td></tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>

<?php if ($vg['eventos'] > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= $this->site ?>js/hotel/charts.js?v=<?= @filemtime(__DIR__ . '/../../js/hotel/charts.js') ?: '1' ?>"></script>
<script>
(function () {
    'use strict';

    var dados = GH.core.boot('gh-boot');
    var semChart = false;

    function desenhar() {
        // Rede corporativa que bloqueie o CDN deixa Chart indefinido. Em vez
        // de uma área em branco, a tabela equivalente que acompanha cada
        // gráfico é revelada — e o botão "Ver tabela" some, porque não há mais
        // o que alternar.
        if (!GH.charts.disponivel()) {
            semChart = true;
            document.querySelectorAll('.gh-canvas-wrap').forEach(function (el) { el.hidden = true; });
            document.querySelectorAll('.gh-fallback').forEach(function (el) { el.hidden = false; });
            document.querySelectorAll('[data-ver-tabela]').forEach(function (b) { b.hidden = true; });
            return;
        }

        [
            ['gh-chart-contratos',    dados.contratos],
            ['gh-chart-contratos-av', dados.contratosAv]
        ].forEach(function (par) {
            var canvas = document.getElementById(par[0]);
            // Sem registro no recorte a view nem desenha o canvas.
            if (canvas) { GH.charts.rosca(canvas, par[1].rotulos, par[1].valores, par[1].cores); }
        });

        GH.charts.empilhada(
            document.getElementById('gh-chart-meses'),
            dados.meses.rotulos, dados.meses.series
        );

        ['hotel', 'equipamento'].forEach(function (k) {
            var canvas = document.getElementById('gh-chart-av-' + k);
            if (canvas) GH.charts.histograma(canvas, dados.avaliacoes[k].dist, dados.avaliacoes[k].cor);
        });
    }

    // Alterna a tabela equivalente do gráfico. O gráfico continua no lugar:
    // a tabela é acréscimo, não substituição.
    GH.core.on(document.body, 'click', '[data-ver-tabela]', function (ev, botao) {
        if (semChart) { return; }
        var tabela = document.getElementById(botao.dataset.verTabela);
        if (!tabela) { return; }
        var mostrar = tabela.hidden;
        tabela.hidden = !mostrar;
        botao.setAttribute('aria-expanded', mostrar ? 'true' : 'false');
        botao.textContent = mostrar ? 'Ocultar tabela' : 'Ver tabela';
    });

    desenhar();
    document.addEventListener('gh:redesenhar', desenhar);
})();
</script>
<?php endif; ?>
