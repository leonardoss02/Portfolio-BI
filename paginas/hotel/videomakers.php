<?php
/**
 * paginas/hotel/videomakers.php — cadastro de videomakers.
 *
 * Primeira tela com gravação. A lista chega renderizada do servidor; o JS
 * cuida do filtro local, do modal e da comunicação com os endpoints.
 *
 * @var MRPHP $this
 * @var array $shell
 * @var array $dados    ['itens', 'totais', 'semVinculo']
 * @var array $eventos
 * @var string $token
 */

$itens  = $dados['itens'];
$totais = $dados['totais'];
$status = HotelSchema::campos('videomaker')['status']['valores'];
?>


<div class="gh-painel">
    <div class="gh-painel-cab">
        <div>
            <div class="gh-painel-titulo">
                <span id="gh-contador"><?= hnum($totais['registros']) ?></span>
                registro<?= $totais['registros'] === 1 ? '' : 's' ?>
            </div>
            <?php if ($dados['semVinculo'] > 0): ?>
                <div class="gh-painel-sub">
                    <?= hnum($dados['semVinculo']) ?> sem evento vinculado
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
        <table class="gh-tabela" id="gh-tabela-videomakers">
            <caption class="sr-only">Videomakers contratados</caption>
            <thead>
                <tr>
                    <th scope="col">Videomaker</th>
                    <th scope="col">Evento</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Status</th>
                    <th scope="col">OC</th>
                    <th scope="col" class="num">Valor</th>
                    <th scope="col"><span class="sr-only">Ações</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $v): ?>
                    <?php
                    $ev = $v['_evento'];
                    [$rotuloStatus, $variante] = hbadge('videomaker', $v['status_videomaker']);
                    $busca = mb_strtolower(
                        $v['nome_videomaker'] . ' ' . $v['cidade_videomaker'] . ' ' .
                        $v['oc_videomaker'] . ' ' . ($ev['cidade'] ?? ''),
                        'UTF-8'
                    );
                    ?>
                    <tr data-id="<?= hesc($v['id_videomaker']) ?>"
                        data-status="<?= hesc($v['status_videomaker']) ?>"
                        data-busca="<?= hesc($busca) ?>">
                        <td>
                            <div class="gh-ev-cidade"><?= hesc($v['nome_videomaker']) ?></div>
                            <?php if ($v['contato_videomaker'] !== ''): ?>
                                <div class="gh-ev-meta"><?= hesc($v['contato_videomaker']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ev): ?>
                                <div class="gh-ev-cidade"><?= hesc($ev['cidade']) ?></div>
                                <div class="gh-ev-meta"><?= hesc(hdata($ev['data'])) ?></div>
                            <?php else: ?>
                                <span class="gh-badge neutro">Sem evento</span>
                            <?php endif; ?>
                        </td>
                        <td><?= hesc($v['tipo_videomaker']) ?></td>
                        <td><span class="gh-badge <?= hesc($variante) ?>"><?= hesc($rotuloStatus) ?></span></td>
                        <td><?= $v['oc_videomaker'] !== '' ? hesc($v['oc_videomaker']) : '<span class="gh-ev-meta">—</span>' ?></td>
                        <td class="num"><?= hesc(hmoeda($v['valor_videomaker'])) ?></td>
                        <td>
                            <button type="button" class="gh-btn gh-btn-mini" data-editar="<?= hesc($v['id_videomaker']) ?>">
                                <?= hicone('editar') ?> Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$itens): ?>
            <div class="gh-vazio">Nenhum videomaker neste recorte.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/partes/modal-videomaker.php'; ?>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>

<script>
(function () {
    'use strict';

    var core = GH.core, ui = GH.ui, forms = GH.forms;

    var modal  = document.getElementById('gh-modal-videomaker');
    var form   = document.getElementById('gh-form-videomaker');
    var titulo = document.getElementById('gh-modal-videomaker-titulo');
    var btnDel = document.getElementById('gh-vm-excluir');
    var tabela = document.getElementById('gh-tabela-videomakers');

    // ── Modal ─────────────────────────────────────────────────────────

    function abrirNovo() {
        forms.limpar(form);
        form.elements.id_videomaker.value = '';
        titulo.textContent = 'Novo videomaker';
        btnDel.hidden = true;
        ui.abrir(modal);
    }

    function abrirEdicao(id) {
        forms.carregar('videomaker', id)
            .then(function (reg) {
                forms.limpar(form);
                forms.hidratar(form, reg);
                titulo.textContent = 'Editar videomaker';
                btnDel.hidden = false;
                btnDel.dataset.id = id;
                ui.abrir(modal);
            })
            .catch(function (e) { ui.toast(e.message, 'erro'); });
    }

    document.getElementById('gh-vm-novo').addEventListener('click', abrirNovo);
    core.on(tabela, 'click', '[data-editar]', function (ev, el) { abrirEdicao(el.dataset.editar); });
    core.on(modal, 'click', '[data-fechar-modal]', function () { ui.fechar(modal); });

    // Preenche cidade e data a partir do evento escolhido — no original isso
    // gravava o SLUG da cidade no filho, quebrando o vínculo por nome.
    var eventos = <?= hjson($eventos) ?>;
    form.elements.idHotel_videomaker.addEventListener('change', function () {
        var id = this.value;
        var ev = eventos.filter(function (e) { return String(e.id) === String(id); })[0];
        if (!ev) return;
        form.elements.cidade_videomaker.value = ev.cidade;
        form.elements.data_videomaker.value = ev.data;
    });

    // ── Gravação ──────────────────────────────────────────────────────

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        forms.salvar(form, 'videomaker', {
            botao: form.querySelector('[type=submit]'),
            aoSalvar: function () {
                ui.fechar(modal);
                // Recarrega para a lista, os totais e os cartões do topo
                // saírem todos do mesmo cálculo do servidor.
                window.location.reload();
            }
        }).catch(function () { /* erro já exibido no formulário */ });
    });

    btnDel.addEventListener('click', function () {
        forms.excluir('videomaker', this.dataset.id, {
            mensagem: 'Excluir este videomaker? O registro deixa de aparecer nos totais.',
            aoExcluir: function () { ui.fechar(modal); window.location.reload(); }
        }).catch(function () {});
    });

})();
</script>
