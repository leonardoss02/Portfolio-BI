<?php
/**
 * paginas/hotel/cadastro.php — tela genérica de lista + modal.
 *
 * Serve Transportes, Frete, Materiais e Outros: são o mesmo par lista+modal
 * com colunas diferentes. As colunas vêm da configuração do controller; o
 * formulário é gerado do Schema.
 *
 * @var MRPHP  $this
 * @var array  $shell
 * @var string $ent
 * @var array  $cfg      ['titulo', 'singular', 'plural', 'colunas', 'semVinculoRotulo']
 * @var array  $dados    ['itens', 'totais', 'semVinculo']
 * @var array  $eventos
 * @var string $token
 */

$itens   = $dados['itens'];
$totais  = $dados['totais'];
$campos  = HotelSchema::campos($ent);
$status  = $campos['status']['valores'] ?? [];

/** Valor de uma coluna, já formatado conforme o tipo declarado. */
$celula = function (array $reg, array $col) use ($ent): string {
    $valor = $col['campo'] !== null ? ($reg[HotelSchema::campo($ent, $col['campo'])] ?? null) : null;

    switch ($col['tipo']) {
        case 'evento':
            $ev = $reg['_evento'];
            if (!$ev) {
                // Sem evento, a descrição é o único rótulo que a linha tem —
                // é o caso das compras avulsas de material, que se distinguem
                // só pela nota fiscal. Antes ela vinha numa coluna própria.
                $obs = trim((string)($reg[HotelSchema::campo($ent, 'obs')] ?? ''));
                $badge = '<span class="gh-badge neutro">Sem evento</span>';
                return $obs === ''
                    ? $badge
                    : $badge . '<div class="gh-ev-meta">' . hesc(mb_strimwidth($obs, 0, 60, '…', 'UTF-8')) . '</div>';
            }
            return '<div class="gh-ev-cidade">' . hesc($ev['cidade']) . '</div>'
                 . '<div class="gh-ev-meta">' . hesc(hdata($ev['data'])) . '</div>';

        case 'badge':
            if ((string)$valor === '') {
                return '<span class="gh-ev-meta">—</span>';
            }
            [$rotulo, $variante] = hbadge($ent, (string)$valor);
            return '<span class="gh-badge ' . hesc($variante) . '">' . hesc($rotulo) . '</span>';

        case 'moeda':
            return hesc(hmoeda($valor));

        case 'data':
            return hesc(hdata($valor));

        case 'inteiro':
            return hesc(hnum($valor));

        case 'principal':
            $sub = '';
            foreach ($col['sub'] ?? [] as $campoSub) {
                $v = $reg[HotelSchema::campo($ent, $campoSub)] ?? '';
                if ($v !== '' && $v !== null) {
                    $sub .= ($sub !== '' ? ' · ' : '') . hesc($v);
                }
            }
            $titulo = (string)$valor !== '' ? hesc($valor) : '<span class="gh-ev-meta">(sem descrição)</span>';
            return '<div class="gh-ev-cidade">' . $titulo . '</div>'
                 . ($sub !== '' ? '<div class="gh-ev-meta">' . $sub . '</div>' : '');

        default:
            return (string)$valor !== '' ? hesc($valor) : '<span class="gh-ev-meta">—</span>';
    }
};
?>


<div class="gh-painel">
    <div class="gh-painel-cab">
        <div>
            <div class="gh-painel-titulo">
                <span id="gh-contador"><?= hnum($totais['registros']) ?></span>
                <?= hesc($totais['registros'] === 1 ? $cfg['singular'] : $cfg['plural']) ?>
            </div>
            <?php if ($dados['semVinculo'] > 0): ?>
                <div class="gh-painel-sub">
                    <?= hnum($dados['semVinculo']) ?> <?= hesc($cfg['semVinculoRotulo']) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="gh-painel-total">
            <span class="gh-painel-total-rotulo">Total</span>
            <strong><?= hmoedaHtml($totais['valor']) ?></strong>
        </div>
    </div>

    <?php include __DIR__ . '/partes/tabela-barra.php'; ?>

    <div class="gh-tabela-wrap">
        <table class="gh-tabela" id="gh-tabela">
            <caption class="sr-only"><?= hesc($cfg['titulo']) ?></caption>
            <thead>
                <tr>
                    <?php foreach ($cfg['colunas'] as $col): ?>
                        <th scope="col"<?= in_array($col['tipo'], ['moeda', 'inteiro'], true) ? ' class="num"' : '' ?>>
                            <?= hesc($col['rotulo']) ?>
                        </th>
                    <?php endforeach; ?>
                    <th scope="col"><span class="sr-only">Ações</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $reg): ?>
                    <?php
                    $id = $reg[HotelSchema::pk($ent)];

                    // Índice de busca: os campos de texto do registro mais a
                    // cidade do evento, para o filtro local achar por qualquer um.
                    $partes = [];
                    foreach ($campos as $nome => $def) {
                        if (in_array($def['tipo'], ['texto', 'textoLongo', 'enum'], true) && empty($def['interno'])) {
                            $partes[] = (string)($reg[HotelSchema::campo($ent, $nome)] ?? '');
                        }
                    }
                    $partes[] = $reg['_evento']['cidade'] ?? '';
                    $busca    = mb_strtolower(implode(' ', array_filter($partes)), 'UTF-8');
                    ?>
                    <tr data-id="<?= hesc($id) ?>"
                        data-status="<?= hesc($reg[HotelSchema::campo($ent, 'status')] ?? '') ?>"
                        data-busca="<?= hesc($busca) ?>">
                        <?php foreach ($cfg['colunas'] as $col): ?>
                            <td<?= in_array($col['tipo'], ['moeda', 'inteiro'], true) ? ' class="num"' : '' ?>>
                                <?= $celula($reg, $col) ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <button type="button" class="gh-btn gh-btn-mini" data-editar="<?= hesc($id) ?>">
                                <?= hicone('editar') ?> Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$itens): ?>
            <div class="gh-vazio">Nenhum registro neste recorte.</div>
        <?php endif; ?>
    </div>
</div>

<?php
$tituloModal = $cfg['botaoNovo'];
include __DIR__ . '/partes/modal-generico.php';
?>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>

<script>
(function () {
    'use strict';

    var core = GH.core, ui = GH.ui, forms = GH.forms;
    var ENT = <?= hjson($ent) ?>;
    var NOVO = <?= hjson($cfg['botaoNovo']) ?>;

    var modal  = document.getElementById('gh-modal-' + ENT);
    var form   = document.getElementById('gh-form-' + ENT);
    var titulo = document.getElementById('gh-modal-' + ENT + '-titulo');
    var btnDel = modal.querySelector('[data-excluir]');
    var tabela = document.getElementById('gh-tabela');
    var pk     = 'id_' + ENT;

    function abrirNovo() {
        forms.limpar(form);
        form.elements[pk].value = '';
        titulo.textContent = NOVO;
        btnDel.hidden = true;
        ui.abrir(modal);
    }

    function abrirEdicao(id) {
        forms.carregar(ENT, id)
            .then(function (reg) {
                forms.limpar(form);
                forms.hidratar(form, reg);
                titulo.textContent = 'Editar registro';
                btnDel.hidden = false;
                btnDel.dataset.id = id;
                ui.abrir(modal);
            })
            .catch(function (e) { ui.toast(e.message, 'erro'); });
    }

    document.getElementById('gh-novo').addEventListener('click', abrirNovo);
    core.on(tabela, 'click', '[data-editar]', function (ev, el) { abrirEdicao(el.dataset.editar); });
    core.on(modal, 'click', '[data-fechar-modal]', function () { ui.fechar(modal); });

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        forms.salvar(form, ENT, {
            aoSalvar: function () { ui.fechar(modal); window.location.reload(); }
        }).catch(function () {});
    });

    btnDel.addEventListener('click', function () {
        forms.excluir(ENT, this.dataset.id, {
            aoExcluir: function () { ui.fechar(modal); window.location.reload(); }
        }).catch(function () {});
    });

})();
</script>
