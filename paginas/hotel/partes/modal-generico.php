<?php
/**
 * Formulário gerado a partir do Schema.
 *
 * Transportes, Frete, Materiais e Outros são o mesmo formulário com campos
 * diferentes — escrever quatro modais quase idênticos é exatamente como as
 * regras divergiram no sistema antigo. Aqui o widget de cada campo é
 * inferido do tipo declarado:
 *
 *   texto/textoLongo -> input | textarea      data     -> input[type=date]
 *   inteiro          -> input[type=number]    booleano -> checkbox
 *   decimal          -> input com inputmode   enum     -> select
 *   fk (idHotel)     -> select de eventos
 *
 * Campos marcados como internos (carimbos, id de origem, ponte com o CRM)
 * ficam de fora.
 *
 * @var MRPHP  $this
 * @var string $ent      entidade
 * @var array  $eventos  eventos ativos para o vínculo
 * @var string $token
 * @var string $tituloModal
 */
$campos = HotelSchema::camposDeFormulario($ent);
$pk     = HotelSchema::pk($ent);
$largos = ['obs', 'descricao', 'passageiro', 'trecho', 'endereco'];
?>
<dialog class="gh-dialog" id="gh-modal-<?= hesc($ent) ?>" aria-labelledby="gh-modal-<?= hesc($ent) ?>-titulo">
    <form id="gh-form-<?= hesc($ent) ?>" novalidate>
        <input type="hidden" name="<?= hesc($pk) ?>" value="">
        <input type="hidden" name="_token" id="gh-token" value="<?= hesc($token) ?>">

        <div class="gh-dialog-cab">
            <h2 class="gh-dialog-titulo" id="gh-modal-<?= hesc($ent) ?>-titulo"><?= hesc($tituloModal) ?></h2>
            <button type="button" class="gh-btn" data-fechar-modal aria-label="Fechar"><?= hicone('x') ?></button>
        </div>

        <div class="gh-dialog-corpo">
            <?php
            // Campos estreitos são emparelhados de dois em dois; os largos
            // ocupam a linha inteira. Sem isso, um formulário de doze campos
            // vira uma coluna interminável.
            $fila = [];

            $render = function (string $nome, array $def) use ($ent, $eventos): void {
                $col = HotelSchema::campo($ent, $nome);
                $id  = $ent . '-' . $nome;
                $req = !empty($def['req']);
                ?>
                <div class="gh-campo<?= $def['tipo'] === 'booleano' ? ' gh-campo-check' : '' ?>">
                    <?php if ($def['tipo'] === 'booleano'): ?>
                        <input type="checkbox" name="<?= hesc($col) ?>" id="<?= hesc($id) ?>" value="1">
                        <label for="<?= hesc($id) ?>"><?= hesc($def['rotulo']) ?></label>
                    <?php else: ?>
                        <label for="<?= hesc($id) ?>">
                            <?= hesc($def['rotulo']) ?><?= $req ? ' <span aria-hidden="true">*</span>' : '' ?>
                        </label>

                        <?php if ($def['tipo'] === 'fk'): ?>
                            <select name="<?= hesc($col) ?>" id="<?= hesc($id) ?>" data-busca<?= $req ? ' required' : '' ?>>
                                <option value=""><?= $req ? 'Selecione o evento...' : 'Sem evento vinculado' ?></option>
                                <?php foreach ($eventos as $e): ?>
                                    <option value="<?= hesc($e['id']) ?>">
                                        <?= hesc(hdata($e['data'])) ?> · <?= hesc($e['cidade']) ?><?= $e['nome'] !== '' ? ' — ' . hesc($e['nome']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($def['tipo'] === 'enum'): ?>
                            <select name="<?= hesc($col) ?>" id="<?= hesc($id) ?>">
                                <?php foreach ($def['valores'] as $v): ?>
                                    <option value="<?= hesc($v) ?>"<?= $v === ($def['padrao'] ?? '') ? ' selected' : '' ?>>
                                        <?= hesc($v === '' ? 'Não informado' : hbadge($ent, $v)[0]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($def['tipo'] === 'textoLongo'): ?>
                            <textarea name="<?= hesc($col) ?>" id="<?= hesc($id) ?>" rows="2"></textarea>

                        <?php elseif ($def['tipo'] === 'data'): ?>
                            <input type="date" name="<?= hesc($col) ?>" id="<?= hesc($id) ?>">

                        <?php elseif ($def['tipo'] === 'inteiro'): ?>
                            <input type="number" name="<?= hesc($col) ?>" id="<?= hesc($id) ?>"
                                   min="<?= hesc($def['min'] ?? 0) ?>" step="1">

                        <?php elseif ($def['tipo'] === 'decimal'): ?>
                            <input type="text" name="<?= hesc($col) ?>" id="<?= hesc($id) ?>"
                                   inputmode="decimal" placeholder="1.200,00">

                        <?php else: ?>
                            <input type="text" name="<?= hesc($col) ?>" id="<?= hesc($id) ?>"
                                   maxlength="<?= hesc($def['max'] ?? 255) ?>"<?= $req ? ' required' : '' ?>>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php
            };

            foreach ($campos as $nome => $def) {
                $largo = in_array($nome, $largos, true) || $def['tipo'] === 'textoLongo' || $def['tipo'] === 'fk';

                if ($largo) {
                    // Fecha a fila pendente antes de abrir um campo largo.
                    if ($fila) {
                        echo '<div class="gh-campo-linha">';
                        foreach ($fila as [$n, $d]) { $render($n, $d); }
                        echo '</div>';
                        $fila = [];
                    }
                    $render($nome, $def);
                    continue;
                }

                $fila[] = [$nome, $def];
                if (count($fila) === 2) {
                    echo '<div class="gh-campo-linha">';
                    foreach ($fila as [$n, $d]) { $render($n, $d); }
                    echo '</div>';
                    $fila = [];
                }
            }

            if ($fila) {
                echo '<div class="gh-campo-linha">';
                foreach ($fila as [$n, $d]) { $render($n, $d); }
                echo '</div>';
            }
            ?>
        </div>

        <div class="gh-dialog-rodape">
            <button type="button" class="gh-btn gh-btn-excluir" data-excluir hidden>
                <?= hicone('lixeira') ?> Excluir
            </button>
            <button type="button" class="gh-btn" data-fechar-modal>Cancelar</button>
            <button type="submit" class="gh-btn gh-btn-acao">Salvar</button>
        </div>
    </form>
</dialog>
