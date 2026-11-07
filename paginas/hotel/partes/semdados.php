<?php
/**
 * Aviso de base vazia.
 *
 * Só aparece se `fornecedorEvento` não tiver nenhum hotel. Com a base em
 * banco isso não deveria acontecer em uso normal — quando acontece, é sinal
 * de problema (tabela não criada, conexão apontando para o lugar errado),
 * não de ambiente por configurar. Por isso o texto aponta para o que
 * verificar, em vez de oferecer um botão de carga.
 *
 * @var MRPHP $this
 * @var array $shell
 */
?>
<div class="gh-semdados" role="status">
    <div class="gh-semdados-icone" aria-hidden="true"><?= hicone('alerta') ?></div>
    <div>
        <div class="gh-semdados-titulo">Nenhum hotel cadastrado</div>
        <p class="gh-semdados-texto">
            A tela está vazia porque não há nenhum registro em
            <code>fornecedorEvento</code>. Se isso não era o esperado, vale
            conferir se a tabela existe no banco em uso e se a conexão do
            sistema aponta para ele.
        </p>
        <p class="gh-semdados-texto">
            Para começar, cadastre um hotel na aba <strong>Hotéis</strong> —
            escolhendo antes o evento do CRM ao qual ele pertence.
        </p>
    </div>
</div>
