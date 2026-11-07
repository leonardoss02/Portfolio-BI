<?php
/**
 * Modal de cadastro de hotel/evento — o maior do sistema.
 *
 * Dividido nas mesmas seções do original, mas com duas diferenças que vêm do
 * modelo novo:
 *
 *  - o bloco de A/V virou a entidade `equipamento`, com aba própria; aqui
 *    resta só a escolha de quem é o responsável, e um atalho para a aba;
 *  - avaliação por estrelas é um <fieldset> de radios, não spans com onclick:
 *    funciona por teclado e é lida por leitor de tela;
 *  - o evento do CRM abre o formulário e é obrigatório: cidade e endereço
 *    deixaram de ser digitáveis e passaram a vir dele.
 *
 * @var MRPHP $this
 * @var array  $eventosCrm
 * @var string $token
 */
$campos = HotelSchema::campos('hotel');

/** Opções de um enum, com o rótulo de exibição. */
$opcoes = static function (array $valores, string $ctx): array {
    $out = [];
    foreach ($valores as $v) {
        $out[$v] = $v === '' ? 'Não informado' : hbadge($ctx, $v)[0];
    }
    return $out;
};
?>
<dialog class="gh-dialog gh-dialog-largo" id="gh-modal-hotel" aria-labelledby="gh-modal-hotel-titulo">
    <form id="gh-form-hotel" novalidate>
        <input type="hidden" name="id_hotel" value="">
        <input type="hidden" name="_token" id="gh-token" value="<?= hesc($token) ?>">

        <div class="gh-dialog-cab">
            <h2 class="gh-dialog-titulo" id="gh-modal-hotel-titulo">Novo hotel</h2>
            <button type="button" class="gh-btn" data-fechar-modal aria-label="Fechar"><?= hicone('x') ?></button>
        </div>

        <div class="gh-dialog-corpo">

            <div class="gh-secao">Evento</div>

            <?php
            // Primeiro campo, e obrigatório: é o evento que liga o hotel aos
            // seus custos no banco. Os eventos que já têm hotel aparecem
            // desabilitados — é um hotel por evento —, menos o do registro em
            // edição, que precisa continuar selecionável.
            ?>
            <div class="gh-campo">
                <label for="h-idEventoCrm">Evento do CRM <span aria-hidden="true">*</span></label>
                <select name="idEventoCrm_hotel" id="h-idEventoCrm" data-busca required>
                    <option value="">Selecione o evento...</option>
                    <?php foreach ($eventosCrm as $e): ?>
                        <option value="<?= hesc($e['id']) ?>"
                                data-cidade="<?= hesc($e['cidade']) ?>"
                                data-endereco="<?= hesc($e['endereco']) ?>"
                                data-data="<?= hesc($e['data']) ?>"
                                data-local="<?= hesc($e['local']) ?>"
                                data-hotel="<?= hesc((string)$e['idHotel']) ?>">
                            <?= hesc(hdata($e['data'])) ?> · <?= hesc($e['cidade']) ?><?= $e['nome'] !== '' ? ' — ' . hesc($e['nome']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="gh-campo-dica" id="h-evento-dica">
                    Cidade e endereço vêm do evento escolhido — são mantidos no CRM, não aqui.
                </span>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="h-cidade-ro">Cidade</label>
                    <input type="text" id="h-cidade-ro" readonly tabindex="-1" placeholder="—">
                </div>
                <div class="gh-campo">
                    <label for="h-endereco-ro">Endereço</label>
                    <input type="text" id="h-endereco-ro" readonly tabindex="-1" placeholder="—">
                </div>
            </div>

            <div class="gh-secao">Identificação</div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="h-nome">Nome do hotel <span aria-hidden="true">*</span></label>
                    <input type="text" name="nome_hotel" id="h-nome" maxlength="180" required>
                </div>
                <div class="gh-campo">
                    <label for="h-uf">Estado (UF)</label>
                    <input type="text" name="uf_hotel" id="h-uf" maxlength="2" style="text-transform:uppercase">
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="h-capacidade">Capacidade</label>
                    <input type="number" name="capacidade_hotel" id="h-capacidade" min="0" step="1">
                </div>
                <div class="gh-campo">
                    <label for="h-data">Data do evento</label>
                    <input type="date" name="data_hotel" id="h-data">
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="h-valor">Valor do evento (R$)</label>
                    <input type="text" name="valor_hotel" id="h-valor" inputmode="decimal" placeholder="15.000,00">
                </div>
                <div class="gh-campo">
                    <label for="h-tipoPag">Tipo de pagamento</label>
                    <select name="tipoPag_hotel" id="h-tipoPag">
                        <option value="">Não informado</option>
                        <option value="antecipado">Antecipado</option>
                        <option value="faturado">Faturado</option>
                        <option value="parcial">50/50</option>
                        <option value="credito">Carta de crédito</option>
                    </select>
                </div>
            </div>

            <?php
            // Estrelas acessíveis: radios reais escondidos visualmente, com
            // <label> clicável. O original usava spans com onclick, sem
            // teclado nem leitor de tela.
            ?>
            <fieldset class="gh-campo gh-estrelas">
                <legend>Avaliação do hotel</legend>
                <div class="gh-estrelas-linha">
                    <input type="radio" name="rating_hotel" id="h-rating-0" value="0" class="sr-only" checked>
                    <label for="h-rating-0" class="gh-estrela-zero">Sem avaliação</label>
                    <div class="gh-estrelas-grupo">
                        <?php for ($n = 5; $n >= 1; $n--): ?>
                            <input type="radio" name="rating_hotel" id="h-rating-<?= $n ?>" value="<?= $n ?>" class="sr-only">
                            <label for="h-rating-<?= $n ?>" title="<?= $n ?> de 5">
                                <span class="sr-only"><?= $n ?> estrela<?= $n > 1 ? 's' : '' ?></span>
                                <?= hicone('estrela') ?>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </fieldset>

            <div class="gh-secao">Contrato</div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="h-status">Situação</label>
                    <select name="status_hotel" id="h-status">
                        <?php foreach ($opcoes($campos['status']['valores'], 'hotel') as $v => $r): ?>
                            <option value="<?= hesc($v) ?>"><?= hesc($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gh-campo">
                    <label for="h-dataAss">Data da assinatura</label>
                    <input type="date" name="dataAss_hotel" id="h-dataAss">
                </div>
            </div>

            <div class="gh-campo">
                <label for="h-linkContrato">Link do contrato</label>
                <input type="url" name="linkContrato_hotel" id="h-linkContrato" maxlength="500"
                       placeholder="https://...">
                <span class="gh-campo-dica">Opcional. Ex.: link do PDF assinado no Drive ou DocuSign.</span>
            </div>

            <div class="gh-secao">Nota fiscal e ordem de compra</div>

            <div class="gh-campo-linha">
                <div class="gh-campo gh-campo-check">
                    <input type="checkbox" name="nf_hotel" id="h-nf" value="1">
                    <label for="h-nf">NF recebida</label>
                </div>
                <div class="gh-campo">
                    <label for="h-dataNf">Data da NF</label>
                    <input type="date" name="dataNf_hotel" id="h-dataNf">
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo gh-campo-check">
                    <input type="checkbox" name="oc_hotel" id="h-oc" value="1">
                    <label for="h-oc">OC aberta</label>
                </div>
                <div class="gh-campo">
                    <label for="h-dataOc">Data da OC</label>
                    <input type="date" name="dataOc_hotel" id="h-dataOc">
                </div>
            </div>

            <div class="gh-campo">
                <label for="h-ocNum">Número(s) da OC</label>
                <input type="text" name="ocNum_hotel" id="h-ocNum" maxlength="60" placeholder="893, 894">
                <span class="gh-campo-dica">Aceita mais de um número separado por vírgula.</span>
            </div>

            <div class="gh-secao">Reserva de quartos</div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="h-quartosReserva">Nº da reserva</label>
                    <input type="text" name="quartosReserva_hotel" id="h-quartosReserva" maxlength="120"
                           placeholder="Ex.: 123456 ou confirmado por e-mail">
                </div>
                <div class="gh-campo gh-campo-check">
                    <input type="checkbox" name="quartosConfirmado_hotel" id="h-quartosConfirmado" value="1">
                    <label for="h-quartosConfirmado">Quartos confirmados (2 single + 1 duplo)</label>
                </div>
            </div>

            <div class="gh-secao">Entrega de materiais</div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="h-entregaStatus">Status da entrega</label>
                    <select name="entregaStatus_hotel" id="h-entregaStatus">
                        <?php foreach ($opcoes($campos['entregaStatus']['valores'], 'hotel') as $v => $r): ?>
                            <option value="<?= hesc($v) ?>"><?= hesc($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gh-campo">
                    <label for="h-entregaData">Data da entrega</label>
                    <input type="date" name="entregaData_hotel" id="h-entregaData">
                </div>
            </div>

            <div class="gh-campo">
                <label for="h-entregaMateriais">Materiais entregues</label>
                <input type="text" name="entregaMateriais_hotel" id="h-entregaMateriais" maxlength="255"
                       placeholder="Ex.: 200 canetas, 200 blocos, banner">
            </div>

            <div class="gh-campo">
                <label for="h-entregaObs">Comentário sobre a entrega</label>
                <textarea name="entregaObs_hotel" id="h-entregaObs" rows="2"></textarea>
            </div>

            <div class="gh-secao">Equipamentos A/V</div>

            <div class="gh-campo">
                <label for="h-responsavelAv">Responsável</label>
                <select name="responsavelAv_hotel" id="h-responsavelAv">
                    <option value="hotel">Incluso com o hotel</option>
                    <option value="terceirizado">Fornecedor terceirizado</option>
                </select>
                <span class="gh-campo-dica" id="h-av-dica" hidden>
                    Os fornecedores de A/V são cadastrados na aba Equipamentos — lá cabe mais de um por evento.
                </span>
            </div>

            <div class="gh-secao">Observações</div>

            <div class="gh-campo">
                <label class="sr-only" for="h-obs">Observações</label>
                <textarea name="obs_hotel" id="h-obs" rows="2" placeholder="Informações adicionais..."></textarea>
            </div>
        </div>

        <div class="gh-dialog-rodape">
            <button type="button" class="gh-btn gh-btn-excluir" id="gh-h-excluir" hidden>
                <?= hicone('lixeira') ?> Excluir
            </button>
            <button type="button" class="gh-btn" data-fechar-modal>Cancelar</button>
            <button type="submit" class="gh-btn gh-btn-acao">Salvar</button>
        </div>
    </form>
</dialog>
