<?php
/**
 * Faixa de ferramentas da tabela — busca à esquerda, criar à direita.
 *
 * As duas coisas moravam longe do lugar onde agem: a busca no painel de
 * filtros lá em cima, junto de Ano e Mês, e o botão de criar no cabeçalho da
 * página. Só que a busca não recorta o conjunto como Ano e Mês recortam — ela
 * filtra as linhas já renderizadas, e por isso pertence à tabela. E o botão
 * cria um registro que vai aparecer aqui embaixo, não lá em cima.
 *
 * O input mantém o id `ff-busca`, que é como js/hotel/filtros.js o encontra
 * (`getElementById`): mudar de lugar no HTML não muda o wiring.
 *
 * Some por inteiro quando a tela não declara nem busca nem ação — o
 * calendário, por exemplo. O relatório não tem busca, mas tem as ações de
 * exportar e imprimir.
 *
 * @var MRPHP $this
 * @var array $shell  ['buscaLabel', 'acoes']
 */

$temBusca = !empty($shell['buscaLabel']);
$acoes    = $shell['acoes'] ?? [];

if (!$temBusca && !$acoes) {
    return;
}
?>
<div class="gh-tabela-barra">
    <?php if ($temBusca): ?>
        <div class="gh-tabela-busca">
            <svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.5-3.5"></path>
            </svg>
            <label class="sr-only" for="ff-busca">Buscar</label>
            <input type="search" id="ff-busca" placeholder="<?= hesc($shell['buscaLabel']) ?>"
                   autocomplete="off" spellcheck="false">
        </div>
    <?php endif; ?>

    <?php foreach ($acoes as $acao): ?>
        <?php
        /*
         * A secundária é btn-clear, o par do btn-apply nos filtros (mesma
         * altura e fonte). Era btn-tab-switch, feito para o cabeçalho escuro
         * de onde as ações saíram: texto claro sobre fundo branco, sumia
         * nesta faixa clara.
         */
        $classe = !empty($acao['destaque']) ? 'btn-apply' : 'btn-clear';
        ?>
        <?php if (!empty($acao['href'])): ?>
            <a class="<?= $classe ?> gh-tabela-acao" href="<?= hesc($acao['href']) ?>">
                <?= hesc($acao['rotulo']) ?>
            </a>
        <?php else: ?>
            <button type="button" class="<?= $classe ?> gh-tabela-acao" id="<?= hesc($acao['id']) ?>">
                <?= hesc($acao['rotulo']) ?>
            </button>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
