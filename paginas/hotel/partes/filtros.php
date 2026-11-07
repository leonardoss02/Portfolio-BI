<?php
/**
 * Painel de filtros — o mesmo vocabulário dos demais dashboards de BI
 * (.filters / .filter-pair / .multi-dd-* / .btn-apply, de css/bi-theme.css).
 *
 * Substituiu a barra de semestres e, em cada tela, os chips de situação e o
 * campo de busca que viviam soltos dentro do conteúdo. Agora todos os campos
 * de uma tela ficam no mesmo painel, aplicados pelo mesmo botão.
 *
 * Dois tipos de campo convivem aqui:
 *
 *   - de SERVIDOR (ano, mês, cidade): viajam na querystring. "Aplicar"
 *     navega, então o botão voltar e o link compartilhável continuam
 *     funcionando — a razão de o recorte morar na URL desde o começo.
 *   - de TELA (situação, busca textual): filtram as linhas já renderizadas.
 *     Cada um declara o atributo data-* que as linhas carregam.
 *
 * A configuração vai para o JS como JSON em vez de ser montada em PHP inline:
 * js/hotel/filtros.js monta os dropdowns com createMultiDropdown(), a mesma
 * factory de js/bi-dashboard.js usada pelo Executive e pelo Marketing.
 *
 * @var MRPHP $this
 * @var array $shell  ['anos','mesesDoAno','periodoAtivo','filtrosTela','buscaLabel','aba']
 */

$aba    = $shell['aba'] === 'index' ? '' : $shell['aba'];
$filtro = $shell['periodoAtivo'];

$cfg = [
    // Rota base para o "Aplicar" montar a URL. Sem os parâmetros de filtro:
    // o JS os reescreve a partir do que estiver selecionado.
    'rota'     => hurl($this, $aba, ['anos' => null, 'meses' => null, 'cidade' => null]),
    'anos'     => array_map(
        static fn(array $a): array => ['v' => $a['ano'], 'r' => $a['ano'], 'n' => $a['eventos']],
        $shell['anos']
    ),
    'anosSel'  => $filtro['anos'] ?? [],
    'meses'    => [],
    'mesesSel' => $filtro['meses'] ?? [],
    'tela'     => [],
];

foreach (HotelCalculo::MESES as $i => $rotulo) {
    $mes = $i + 1;
    $cfg['meses'][] = ['v' => $mes, 'r' => $rotulo, 'n' => $shell['mesesDoAno'][$mes] ?? 0];
}

foreach ($shell['filtrosTela'] as $f) {
    $cfg['tela'][] = [
        'chave'    => $f['chave'],
        'dataset'  => $f['dataset'] ?? null,
        'servidor' => !empty($f['servidor']),
        'valores'  => array_values($f['valores'] ?? []),
        'opcoes'   => array_map(
            static fn(array $o): array => ['v' => $o['valor'], 'r' => $o['rotulo'], 'n' => $o['n'] ?? null],
            $f['opcoes']
        ),
    ];
}
?>
<section class="filters" aria-label="Filtros">

    <div class="filter-pair">
        <span class="filter-label" id="ff-lbl-anos">Ano</span>
        <div class="multi-dd-wrap" id="ff-anos-wrap">
            <button class="multi-dd-btn" id="ff-anos-btn" type="button"
                    aria-labelledby="ff-lbl-anos ff-anos-btn">Todos <span class="dd-caret">▾</span></button>
            <div class="multi-dd-list" id="ff-anos-list"></div>
        </div>
    </div>

    <div class="filter-pair">
        <span class="filter-label" id="ff-lbl-meses">Mês</span>
        <div class="multi-dd-wrap" id="ff-meses-wrap">
            <button class="multi-dd-btn" id="ff-meses-btn" type="button"
                    aria-labelledby="ff-lbl-meses ff-meses-btn">Todos <span class="dd-caret">▾</span></button>
            <div class="multi-dd-list" id="ff-meses-list"></div>
        </div>
    </div>

    <?php if ($shell['filtrosTela']): ?>
        <div class="filter-sep"></div>
    <?php endif; ?>

    <?php foreach ($shell['filtrosTela'] as $f): ?>
        <div class="filter-pair">
            <span class="filter-label" id="ff-lbl-<?= hesc($f['chave']) ?>"><?= hesc($f['rotulo']) ?></span>
            <div class="multi-dd-wrap" id="ff-<?= hesc($f['chave']) ?>-wrap">
                <button class="multi-dd-btn" id="ff-<?= hesc($f['chave']) ?>-btn" type="button"
                        aria-labelledby="ff-lbl-<?= hesc($f['chave']) ?> ff-<?= hesc($f['chave']) ?>-btn">Todos <span class="dd-caret">▾</span></button>
                <div class="multi-dd-list" id="ff-<?= hesc($f['chave']) ?>-list"></div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="filter-pair gh-filtro-acoes">
        <button type="button" class="btn-apply" id="ff-aplicar">Aplicar</button>
        <button type="button" class="btn-clear" id="ff-limpar">Limpar</button>
        <span class="gh-filtro-resumo" id="ff-resumo"><?= hesc($shell['rotuloFiltro']) ?></span>
    </div>

</section>

<script type="application/json" id="gh-filtros-cfg"><?= hjson($cfg) ?></script>
