<?php
/**
 * Modal de fornecedor de A/V.
 *
 * Estes campos eram colunas dentro do hotel (fNome, fValor, fStatus...), o
 * que limitava a um fornecedor por evento. Como entidade propria, um evento
 * pode ter quantos precisar.
 *
 * @var array $eventos
 * @var string $token
 */
$campos = HotelSchema::campos('equipamento');
?>
<dialog class="gh-dialog" id="gh-modal-equipamento" aria-labelledby="gh-modal-equipamento-titulo">
    <form id="gh-form-equipamento" novalidate>
        <input type="hidden" name="id_equipamento" value="">
        <input type="hidden" name="_token" id="gh-token" value="<?= hesc($token) ?>">

        <div class="gh-dialog-cab">
            <h2 class="gh-dialog-titulo" id="gh-modal-equipamento-titulo">Novo fornecedor A/V</h2>
            <button type="button" class="gh-btn" data-fechar-modal aria-label="Fechar"><?= hicone('x') ?></button>
        </div>

        <div class="gh-dialog-corpo">
            <div class="gh-campo">
                <label for="eq-idHotel">Evento vinculado <span aria-hidden="true">*</span></label>
                <select name="idHotel_equipamento" id="eq-idHotel" data-busca required>
                    <option value="">Selecione o evento...</option>
                    <?php foreach ($eventos as $e): ?>
                        <option value="<?= hesc($e['id']) ?>">
                            <?= hesc(hdata($e['data'])) ?> · <?= hesc($e['cidade']) ?><?= $e['nome'] !== '' ? ' — ' . hesc($e['nome']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="eq-fornecedor">Fornecedor <span aria-hidden="true">*</span></label>
                    <input type="text" name="fornecedor_equipamento" id="eq-fornecedor" maxlength="180" required>
                </div>
                <div class="gh-campo">
                    <label for="eq-contato">Contato</label>
                    <input type="text" name="contato_equipamento" id="eq-contato" maxlength="120">
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="eq-valor">Valor (R$)</label>
                    <input type="text" name="valor_equipamento" id="eq-valor" inputmode="decimal" placeholder="3.500,00">
                </div>
                <div class="gh-campo">
                    <label for="eq-status">Status do pagamento</label>
                    <select name="status_equipamento" id="eq-status">
                        <?php foreach ($campos['status']['valores'] as $v): ?>
                            <option value="<?= hesc($v) ?>"><?= hesc(hbadge('equipamento', $v)[0]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo gh-campo-check">
                    <input type="checkbox" name="oc_equipamento" id="eq-oc" value="1">
                    <label for="eq-oc">OC aberta</label>
                </div>
                <div class="gh-campo">
                    <label for="eq-ocNum">Número da OC</label>
                    <input type="text" name="ocNum_equipamento" id="eq-ocNum" maxlength="60">
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="eq-dataOc">Data da OC</label>
                    <input type="date" name="dataOc_equipamento" id="eq-dataOc">
                </div>
                <div class="gh-campo">
                    <label for="eq-hora">Hora prevista / chegada</label>
                    <input type="text" name="hora_equipamento" id="eq-hora" maxlength="60" placeholder="07:00">
                </div>
            </div>

            <div class="gh-campo">
                <label for="eq-entrega">Chegou no evento?</label>
                <select name="entrega_equipamento" id="eq-entrega">
                    <?php foreach ($campos['entrega']['valores'] as $v): ?>
                        <option value="<?= hesc($v) ?>"><?= hesc($v === '' ? 'Não informado' : hbadge('equipamento', $v)[0]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <fieldset class="gh-campo gh-estrelas">
                <legend>Avaliação do fornecedor</legend>
                <div class="gh-estrelas-linha">
                    <input type="radio" name="rating_equipamento" id="eq-rating-0" value="0" class="sr-only" checked>
                    <label for="eq-rating-0" class="gh-estrela-zero">Sem avaliação</label>
                    <div class="gh-estrelas-grupo">
                        <?php for ($n = 5; $n >= 1; $n--): ?>
                            <input type="radio" name="rating_equipamento" id="eq-rating-<?= $n ?>" value="<?= $n ?>" class="sr-only">
                            <label for="eq-rating-<?= $n ?>" title="<?= $n ?> de 5">
                                <span class="sr-only"><?= $n ?> estrela<?= $n > 1 ? 's' : '' ?></span>
                                <?= hicone('estrela') ?>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </fieldset>

            <div class="gh-campo">
                <label for="eq-obs">Observações</label>
                <textarea name="obs_equipamento" id="eq-obs" rows="2"></textarea>
            </div>
        </div>

        <div class="gh-dialog-rodape">
            <button type="button" class="gh-btn gh-btn-excluir" id="gh-eq-excluir" hidden>
                <?= hicone('lixeira') ?> Excluir
            </button>
            <button type="button" class="gh-btn" data-fechar-modal>Cancelar</button>
            <button type="submit" class="gh-btn gh-btn-acao">Salvar</button>
        </div>
    </form>
</dialog>
