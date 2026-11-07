<?php
/**
 * paginas/hotel/lista.php — cadastro de hotéis/eventos.
 *
 * Cada hotel é um cartão com o estado do contrato, das pendências e o custo
 * consolidado. O custo vem do mesmo cálculo da aba Valor total — no sistema
 * antigo o cartão somava só quatro parcelas e a aba somava sete, então os
 * dois números discordavam na mesma tela.
 *
 * @var MRPHP $this
 * @var array $shell
 * @var array $hoteis
 * @var string $token
 */
?>


<div class="gh-painel">
    <div class="gh-painel-cab">
        <div class="gh-painel-titulo">
            <span id="gh-contador"><?= hnum(count($hoteis)) ?></span>
            hot<?= count($hoteis) === 1 ? 'el' : 'éis' ?>
        </div>
    </div>

    <?php include __DIR__ . '/partes/tabela-barra.php'; ?>

    <div class="gh-cards" id="gh-lista-hoteis">
        <?php foreach ($hoteis as $h): ?>
            <?php
            [$rotuloStatus, $variante] = hbadge('hotel', $h['status_hotel']);
            $passado = $h['data_hotel'] && $h['data_hotel'] < date('Y-m-d');
            $terc    = $h['responsavelAv_hotel'] === 'terceirizado';
            $rating  = (int)$h['rating_hotel'];

            $tags = ['todos'];
            // Cancelados continuam na lista (e preciso poder edita-los), mas
            // ficam fora dos cartoes de resumo e da consolidacao de custos —
            // por isso ganham filtro proprio, em vez de se esconderem no meio.
            $tags[] = $h['status_hotel'];
            if (!$h['nf_hotel'])       { $tags[] = 'sem-nf'; }
            if ($terc)                 { $tags[] = 'terceirizado'; }
            if ($h['_pendencias'] > 0) { $tags[] = 'pendencias'; }
            if ($rating >= 4)          { $tags[] = 'top'; }

            $busca = mb_strtolower($h['nome_hotel'] . ' ' . $h['cidade_hotel'] . ' ' . $h['uf_hotel'], 'UTF-8');
            ?>
            <article class="gh-card<?= $passado ? ' passado' : '' ?>"
                     data-id="<?= hesc($h['id_hotel']) ?>"
                     data-tags="<?= hesc(implode(' ', $tags)) ?>"
                     data-busca="<?= hesc($busca) ?>">

                <div class="gh-card-topo">
                    <div>
                        <h3 class="gh-card-titulo"><?= hesc($h['nome_hotel'] !== '' ? $h['nome_hotel'] : '(sem nome)') ?></h3>
                        <div class="gh-card-sub">
                            <?= hesc($h['cidade_hotel']) ?><?= $h['uf_hotel'] !== '' ? ' · ' . hesc($h['uf_hotel']) : '' ?>
                            · <?= hesc(hdata($h['data_hotel'])) ?>
                            <?php if ($passado): ?><span class="gh-card-realizado">realizado</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="gh-card-valor"><?= hmoedaHtml($h['_custo']['total']) ?></div>
                </div>

                <?php if ($rating > 0): ?>
                    <div class="gh-card-estrelas" aria-label="<?= hesc($rating) ?> de 5">
                        <?php for ($n = 1; $n <= 5; $n++): ?>
                            <span class="<?= $n <= $rating ? 'cheia' : 'vazia' ?>" aria-hidden="true"><?= hicone('estrela') ?></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

                <?php
                /*
                 * Badge só para o estado do contrato e para a exceção. "NF
                 * recebida", "OC 4412", "A/V do hotel" e "Quartos ok" são o
                 * estado NORMAL: viram metadado em linha. Cinco badges fixos
                 * por cartão obrigavam a varrer cinco cores para descobrir
                 * que nada estava errado — e um badge verde para "está tudo
                 * como deveria" gasta a mesma atenção que um vermelho.
                 */
                $meta = [
                    $h['nf_hotel'] ? 'NF recebida' : null,
                    $h['oc_hotel']
                        ? 'OC ' . ($h['ocNum_hotel'] !== '' ? $h['ocNum_hotel'] : 'aberta')
                        : 'sem OC',
                    $terc ? 'A/V terceirizado' : 'A/V do hotel',
                    $h['quartosConfirmado_hotel'] ? 'quartos ok' : 'quartos a confirmar',
                ];
                $meta = array_values(array_filter($meta));
                ?>
                <div class="gh-card-badges">
                    <span class="gh-badge <?= hesc($variante) ?>"><?= hesc($rotuloStatus) ?></span>
                    <?php if (!$h['nf_hotel']): ?>
                        <span class="gh-badge warn">NF pendente</span>
                    <?php endif; ?>
                </div>

                <div class="gh-card-meta">
                    <?php foreach ($meta as $i => $m): ?>
                        <?php if ($i > 0): ?><span class="sep" aria-hidden="true">·</span><?php endif; ?>
                        <span><?= hesc($m) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($h['_equipes']): ?>
                    <div class="gh-card-linha">
                        <?= hicone('av') ?>
                        <?php
                        $nomes = array_map(
                            static fn(array $e): string => $e['fornecedor_equipamento'],
                            $h['_equipes']
                        );
                        ?>
                        <span><?= hesc(implode(', ', $nomes)) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($h['obs_hotel'] !== ''): ?>
                    <div class="gh-card-obs"><?= hesc($h['obs_hotel']) ?></div>
                <?php endif; ?>

                <div class="gh-card-rodape">
                    <?php if ($h['_pendencias'] > 0): ?>
                        <button type="button" class="gh-card-pend" data-checklist="<?= hesc($h['id_hotel']) ?>">
                            <?= hicone('alerta') ?> <?= hesc($h['_pendencias']) ?> pendência<?= $h['_pendencias'] > 1 ? 's' : '' ?>
                        </button>
                    <?php else: ?>
                        <button type="button" class="gh-card-ok" data-checklist="<?= hesc($h['id_hotel']) ?>">
                            <?= hicone('check') ?> Checklist completo
                        </button>
                    <?php endif; ?>

                    <div class="gh-card-rodape-acoes">
                        <?php if ($h['linkContrato_hotel'] !== ''): ?>
                            <a class="gh-btn gh-btn-mini" href="<?= hesc($h['linkContrato_hotel']) ?>"
                               target="_blank" rel="noopener noreferrer">
                                <?= hicone('link') ?> Contrato
                            </a>
                        <?php endif; ?>
                        <button type="button" class="gh-btn gh-btn-mini" data-editar="<?= hesc($h['id_hotel']) ?>">
                            <?= hicone('editar') ?> Editar
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!$hoteis): ?>
        <div class="gh-vazio">Nenhum hotel neste período.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partes/modal-hotel.php'; ?>

<script type="application/json" id="gh-boot"><?= hjson($boot) ?></script>

<script>
(function () {
    'use strict';

    var core = GH.core, ui = GH.ui, forms = GH.forms;

    var modal  = document.getElementById('gh-modal-hotel');
    var form   = document.getElementById('gh-form-hotel');
    var titulo = document.getElementById('gh-modal-hotel-titulo');
    var btnDel = document.getElementById('gh-h-excluir');
    var lista  = document.getElementById('gh-lista-hoteis');

    // A dica sobre a aba de equipamentos só faz sentido quando é terceirizado.
    var selAv  = form.elements.responsavelAv_hotel;
    var dicaAv = document.getElementById('h-av-dica');
    function ajustarAv() { dicaAv.hidden = selAv.value !== 'terceirizado'; }
    selAv.addEventListener('change', ajustarAv);

    // ── Evento do CRM ─────────────────────────────────────────────────
    // Cidade e endereço não são mais digitados: vêm do evento. Os campos
    // ficam à vista, em modo leitura, para o usuário conferir que escolheu
    // o evento certo antes de salvar.

    var selEvento = form.elements.idEventoCrm_hotel;
    var roCidade  = document.getElementById('h-cidade-ro');
    var roEnder   = document.getElementById('h-endereco-ro');
    var campoData = form.elements.data_hotel;
    var campoNome = form.elements.nome_hotel;

    function refletirEvento(preencherVazios) {
        var op = selEvento.options[selEvento.selectedIndex];
        var d  = (op && op.value) ? op.dataset : null;

        roCidade.value = d ? (d.cidade || '') : '';
        roEnder.value  = d ? (d.endereco || '') : '';

        // Só sugere data e nome quando estão em branco — nunca sobrescreve o
        // que o usuário já escreveu, nem o que veio de um registro salvo.
        if (d && preencherVazios) {
            if (!campoData.value && d.data) campoData.value = d.data;
            if (!campoNome.value && d.local) campoNome.value = d.local;
        }
    }

    /**
     * Desabilita os eventos que já têm hotel — é um por evento. O do registro
     * em edição continua habilitado, senão o próprio formulário não abriria
     * com o seu valor selecionado.
     */
    function marcarOcupados(idHotelAtual) {
        Array.prototype.forEach.call(selEvento.options, function (op) {
            if (!op.value) return;
            var ocupado = op.dataset.hotel !== '' && op.dataset.hotel !== String(idHotelAtual || '');
            op.disabled = ocupado;
            var base = op.dataset.rotulo || (op.dataset.rotulo = op.textContent.trim());
            op.textContent = ocupado ? base + '  (já tem hotel)' : base;
        });
    }

    selEvento.addEventListener('change', function () { refletirEvento(true); });
    function abrirNovo() {
        forms.limpar(form);
        form.elements.id_hotel.value = '';
        form.elements.rating_hotel.value = '0';
        titulo.textContent = 'Novo hotel';
        btnDel.hidden = true;
        marcarOcupados(null);
        refletirEvento(false);
        ajustarAv();
        ui.abrir(modal);
    }

    function abrirEdicao(id) {
        forms.carregar('hotel', id)
            .then(function (reg) {
                forms.limpar(form);
                forms.hidratar(form, reg);
                titulo.textContent = reg.nome_hotel || 'Editar hotel';
                btnDel.hidden = false;
                btnDel.dataset.id = id;
                marcarOcupados(id);
                refletirEvento(false);
                ajustarAv();
                ui.abrir(modal);
            })
            .catch(function (e) { ui.toast(e.message, 'erro'); });
    }

    document.getElementById('gh-h-novo').addEventListener('click', abrirNovo);
    core.on(lista, 'click', '[data-editar]', function (ev, el) { abrirEdicao(el.dataset.editar); });
    core.on(modal, 'click', '[data-fechar-modal]', function () { ui.fechar(modal); });

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        forms.salvar(form, 'hotel', {
            aoSalvar: function () { ui.fechar(modal); window.location.reload(); }
        }).catch(function () {});
    });

    btnDel.addEventListener('click', function () {
        forms.excluir('hotel', this.dataset.id, {
            mensagem: 'Excluir este hotel? Os custos vinculados deixam de aparecer nos totais.',
            aoExcluir: function () { ui.fechar(modal); window.location.reload(); }
        }).catch(function () {});
    });

})();
</script>
