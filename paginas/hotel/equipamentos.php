<?php
/**
 * paginas/hotel/equipamentos.php — fornecedores de A/V.
 *
 * Funde as duas abas do sistema antigo ("Equipamentos A/V", uma lista sem
 * filtros, e "Fornecedores A/V", a mesma coisa com busca): as duas eram
 * views sobre o mesmo bloco de colunas dentro do hotel. Com a entidade
 * própria, uma tela só resolve — e passa a caber mais de um fornecedor por
 * evento.
 *
 * @var MRPHP $this
 * @var array $shell
 * @var array $dados
 * @var array $semFornecedor  eventos terceirizados ainda sem fornecedor
 * @var array $eventos
 * @var string $token
 */

$itens  = $dados['itens'];
$totais = $dados['totais'];
$status = HotelSchema::campos('equipamento')['status']['valores'];
?>

<?php if ($semFornecedor): ?>
    <div class="gh-alerta" role="status">
        <div class="gh-alerta-bloco">
            <div class="gh-alerta-titulo">
                <?= hicone('alerta') ?>
                <?= hnum(count($semFornecedor)) ?> evento<?= count($semFornecedor) === 1 ? '' : 's' ?>
                marcado<?= count($semFornecedor) === 1 ? '' : 's' ?> como A/V terceirizado, sem fornecedor cadastrado
            </div>
            <div class="gh-alerta-chips">
                <?php foreach ($semFornecedor as $e): ?>
                    <button type="button" class="gh-chip gh-chip-acao" data-novo-para="<?= hesc($e['id']) ?>">
                        <?= hesc($e['cidade']) ?> · <?= hesc(hdata($e['data'])) ?>
                        <?= hicone('mais') ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>


<div class="gh-painel">
    <div class="gh-painel-cab">
        <div class="gh-painel-titulo">
            <span id="gh-contador"><?= hnum($totais['registros']) ?></span>
            fornecedor<?= $totais['registros'] === 1 ? '' : 'es' ?>
        </div>
        <div class="gh-painel-total">
            <span class="gh-painel-total-rotulo">Total</span>
            <strong><?= hmoedaHtml($totais['valor']) ?></strong>
        </div>
    </div>

    <?php include __DIR__ . '/partes/tabela-barra.php'; ?>

    <div class="gh-tabela-wrap">
        <table class="gh-tabela" id="gh-tabela-equipamentos">
            <caption class="sr-only">Fornecedores de equipamentos A/V</caption>
            <thead>
                <tr>
                    <th scope="col">Fornecedor</th>
                    <th scope="col">Evento</th>
                    <th scope="col">Status</th>
                    <th scope="col">OC</th>
                    <th scope="col">Entrega</th>
                    <th scope="col" class="num">Valor</th>
                    <th scope="col"><span class="sr-only">Ações</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $e): ?>
                    <?php
                    $ev = $e['_evento'];
                    [$rotuloStatus, $variante] = hbadge('equipamento', $e['status_equipamento']);
                    [$rotuloEntrega, $varEntrega] = hbadge('equipamento', $e['entrega_equipamento']);
                    $rating = (int)$e['rating_equipamento'];
                    $busca  = mb_strtolower(
                        $e['fornecedor_equipamento'] . ' ' . $e['ocNum_equipamento'] . ' ' . ($ev['cidade'] ?? ''),
                        'UTF-8'
                    );
                    ?>
                    <tr data-id="<?= hesc($e['id_equipamento']) ?>"
                        data-status="<?= hesc($e['status_equipamento']) ?>"
                        data-busca="<?= hesc($busca) ?>">
                        <td>
                            <div class="gh-ev-cidade"><?= hesc($e['fornecedor_equipamento']) ?></div>
                            <div class="gh-ev-meta">
                                <?= $e['contato_equipamento'] !== '' ? hesc($e['contato_equipamento']) : 'sem contato' ?>
                                <?php if ($rating > 0): ?>
                                    · <?= hesc($rating) ?><?= hicone('estrela', 'i i-mini') ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($ev): ?>
                                <div class="gh-ev-cidade"><?= hesc($ev['cidade']) ?></div>
                                <div class="gh-ev-meta"><?= hesc(hdata($ev['data'])) ?></div>
                            <?php else: ?>
                                <span class="gh-badge neutro">Sem evento</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="gh-badge <?= hesc($variante) ?>"><?= hesc($rotuloStatus) ?></span></td>
                        <td>
                            <?= $e['oc_equipamento']
                                ? hesc($e['ocNum_equipamento'] !== '' ? $e['ocNum_equipamento'] : 'aberta')
                                : '<span class="gh-ev-meta">—</span>' ?>
                        </td>
                        <td>
                            <?php if ($e['entrega_equipamento'] !== ''): ?>
                                <span class="gh-badge <?= hesc($varEntrega) ?>"><?= hesc($rotuloEntrega) ?></span>
                            <?php else: ?>
                                <span class="gh-ev-meta">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= hesc(hmoeda($e['valor_equipamento'])) ?></td>
                        <td>
                            <button type="button" class="gh-btn gh-btn-mini" data-editar="<?= hesc($e['id_equipamento']) ?>">
                                <?= hicone('editar') ?> Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$itens): ?>
            <div class="gh-vazio">Nenhum fornecedor de A/V neste recorte.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/partes/modal-equipamento.php'; ?>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>

<script>
(function () {
    'use strict';

    var core = GH.core, ui = GH.ui, forms = GH.forms;

    var modal  = document.getElementById('gh-modal-equipamento');
    var form   = document.getElementById('gh-form-equipamento');
    var titulo = document.getElementById('gh-modal-equipamento-titulo');
    var btnDel = document.getElementById('gh-eq-excluir');
    var tabela = document.getElementById('gh-tabela-equipamentos');

    function abrirNovo(idHotel) {
        forms.limpar(form);
        form.elements.id_equipamento.value = '';
        form.elements.rating_equipamento.value = '0';
        if (idHotel) form.elements.idHotel_equipamento.value = idHotel;
        titulo.textContent = 'Novo fornecedor A/V';
        btnDel.hidden = true;
        ui.abrir(modal);
    }

    function abrirEdicao(id) {
        forms.carregar('equipamento', id)
            .then(function (reg) {
                forms.limpar(form);
                forms.hidratar(form, reg);
                titulo.textContent = reg.fornecedor_equipamento || 'Editar fornecedor';
                btnDel.hidden = false;
                btnDel.dataset.id = id;
                ui.abrir(modal);
            })
            .catch(function (e) { ui.toast(e.message, 'erro'); });
    }

    document.getElementById('gh-eq-novo').addEventListener('click', function () { abrirNovo(null); });
    core.on(tabela, 'click', '[data-editar]', function (ev, el) { abrirEdicao(el.dataset.editar); });
    core.on(modal, 'click', '[data-fechar-modal]', function () { ui.fechar(modal); });

    // Atalho do aviso: já abre o modal com o evento pendente selecionado.
    core.on(document.body, 'click', '[data-novo-para]', function (ev, el) {
        abrirNovo(el.dataset.novoPara);
    });

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        forms.salvar(form, 'equipamento', {
            aoSalvar: function () { ui.fechar(modal); window.location.reload(); }
        }).catch(function () {});
    });

    btnDel.addEventListener('click', function () {
        forms.excluir('equipamento', this.dataset.id, {
            mensagem: 'Excluir este fornecedor de A/V?',
            aoExcluir: function () { ui.fechar(modal); window.location.reload(); }
        }).catch(function () {});
    });

})();
</script>
