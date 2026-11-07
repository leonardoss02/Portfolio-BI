<?php
/**
 * Modal de cadastro de videomaker.
 *
 * Marcação no servidor, não string montada em JS: assim os valores já saem
 * escapados por PHP, o formulário é acessível e não vira um template literal
 * gigante dentro do JavaScript.
 *
 * <dialog> nativo entrega ESC, backdrop, retenção de foco e inert no resto
 * da página sem reimplementar acessibilidade.
 *
 * @var MRPHP $this
 * @var array $eventos  eventos ativos para o vínculo
 * @var string $token
 */
$campos = HotelSchema::campos('videomaker');
?>
<dialog class="gh-dialog" id="gh-modal-videomaker" aria-labelledby="gh-modal-videomaker-titulo">
    <form id="gh-form-videomaker" novalidate>
        <input type="hidden" name="id_videomaker" value="">
        <input type="hidden" name="_token" id="gh-token" value="<?= hesc($token) ?>">

        <div class="gh-dialog-cab">
            <h2 class="gh-dialog-titulo" id="gh-modal-videomaker-titulo">Novo videomaker</h2>
            <button type="button" class="gh-btn" data-fechar-modal aria-label="Fechar">
                <?= hicone('x') ?>
            </button>
        </div>

        <div class="gh-dialog-corpo">
            <div class="gh-campo">
                <label for="vm-idHotel">Evento vinculado</label>
                <select name="idHotel_videomaker" id="vm-idHotel" data-busca>
                    <option value="">Sem evento vinculado</option>
                    <?php foreach ($eventos as $e): ?>
                        <option value="<?= hesc($e['id']) ?>">
                            <?= hesc(hdata($e['data'])) ?> · <?= hesc($e['cidade']) ?><?= $e['nome'] !== '' ? ' — ' . hesc($e['nome']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="gh-campo-dica">Cidade e data são preenchidas a partir do evento.</span>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="vm-nome">Nome do videomaker <span aria-hidden="true">*</span></label>
                    <input type="text" name="nome_videomaker" id="vm-nome" maxlength="180" required>
                </div>
                <div class="gh-campo">
                    <label for="vm-contato">Contato</label>
                    <input type="text" name="contato_videomaker" id="vm-contato" maxlength="120"
                           placeholder="(11) 90000-0000">
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="vm-cidade">Cidade</label>
                    <input type="text" name="cidade_videomaker" id="vm-cidade" maxlength="120">
                </div>
                <div class="gh-campo">
                    <label for="vm-data">Data do evento</label>
                    <input type="date" name="data_videomaker" id="vm-data">
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="vm-valor">Valor (R$)</label>
                    <input type="text" name="valor_videomaker" id="vm-valor" inputmode="decimal"
                           placeholder="1.200,00">
                    <span class="gh-campo-dica">Aceita 1.200,00 ou 1200.00.</span>
                </div>
                <div class="gh-campo">
                    <label for="vm-status">Status do pagamento</label>
                    <select name="status_videomaker" id="vm-status">
                        <?php foreach ($campos['status']['valores'] as $v): ?>
                            <option value="<?= hesc($v) ?>"<?= $v === $campos['status']['padrao'] ? ' selected' : '' ?>>
                                <?= hesc(hbadge('videomaker', $v)[0]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="gh-campo-linha">
                <div class="gh-campo">
                    <label for="vm-oc">Número da OC</label>
                    <input type="text" name="oc_videomaker" id="vm-oc" maxlength="60" placeholder="OC 1030">
                </div>
                <div class="gh-campo">
                    <label for="vm-tipo">Tipo</label>
                    <select name="tipo_videomaker" id="vm-tipo">
                        <option value="video">Vídeo</option>
                        <option value="foto">Foto</option>
                        <option value="video+foto">Vídeo + foto</option>
                    </select>
                </div>
            </div>

            <div class="gh-campo">
                <label for="vm-obs">Observações</label>
                <textarea name="obs_videomaker" id="vm-obs" rows="2"
                          placeholder="Ex.: 50% na assinatura, 50% na entrega."></textarea>
            </div>
        </div>

        <div class="gh-dialog-rodape">
            <button type="button" class="gh-btn gh-btn-excluir" id="gh-vm-excluir" hidden>
                <?= hicone('lixeira') ?> Excluir
            </button>
            <button type="button" class="gh-btn" data-fechar-modal>Cancelar</button>
            <button type="submit" class="gh-btn gh-btn-acao">Salvar</button>
        </div>
    </form>
</dialog>
