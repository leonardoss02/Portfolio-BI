<?php

return [
    'ids sequenciais por tabela' => function () {
        $b = new Base();
        afirmarIgual(1, $b->inserir('evento', ['nome_evento' => 'A']));
        afirmarIgual(2, $b->inserir('evento', ['nome_evento' => 'B']));
        afirmarIgual(1, $b->inserir('cambio', ['valor_cambio' => 5.4]));
        afirmarIgual(2, $b->linha('evento', 2)['id_evento']);
        afirmarIgual(2, $b->total('evento'));
    },

    'novoIdBitrix começa em 100001 para deal e não repete' => function () {
        $b = new Base();
        afirmarIgual('100001', $b->novoIdBitrix('deal'));
        afirmarIgual('100002', $b->novoIdBitrix('deal'));
        afirmarIgual('500001', $b->novoIdBitrix('lead'));
        afirmarIgual('300001', $b->novoIdBitrix('contato'));
        afirmarIgual('70001', $b->novoIdBitrix('orcamento'));
        afirmarIgual('1', $b->novoIdBitrix('conversa'));
        afirmarIgual('4001', $b->novoIdBitrix('clienteQb'));
    },

    'série desconhecida é erro' => function () {
        $b = new Base();
        try {
            $b->novoIdBitrix('inexistente');
        } catch (InvalidArgumentException $e) {
            return;
        }
        throw new FalhaDeTeste('não lançou InvalidArgumentException');
    },

    'atualizar altera só os campos pedidos' => function () {
        $b = new Base();
        $id = $b->inserir('dealV2', ['nome_dealV2' => 'Ana', 'fase_dealV2' => 'Nova']);
        $b->atualizar('dealV2', $id, ['fase_dealV2' => 'Ganha']);
        afirmarIgual(['nome_dealV2' => 'Ana', 'fase_dealV2' => 'Ganha', 'id_dealV2' => 1], $b->linha('dealV2', $id));
    },

    'linhas() de tabela vazia devolve array vazio' => function () {
        $b = new Base();
        afirmarIgual([], $b->linhas('deal'));
        afirmarIgual(0, $b->total('deal'));
    },
];
