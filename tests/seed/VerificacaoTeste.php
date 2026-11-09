<?php

require_once __DIR__ . '/apoio.php';

return [
    'a base completa passa em todas as checagens' => function () {
        $falhas = Verificacao::checar(gerarAte(null));
        afirmarIgual([], $falhas);
    },

    'custo físico diferente da soma dos fornecedores é detectado' => function () {
        $ctx = contextoDeTeste('2026-11-09', 0.05);
        Gerador::rodar($ctx);
        $id = array_key_first($ctx->base->linhas('custoEvento'));
        $ctx->base->atualizar('custoEvento', $id, ['valor_custoEvento' => 1.0]);
        $falhas = Verificacao::checar($ctx);
        afirmarIgual(1, count($falhas), 'esperava exatamente uma falha');
        afirmar(str_contains($falhas[0], 'custoEvento'), "mensagem não cita custoEvento: {$falhas[0]}");
    },

    'resumo mostra as tabelas e o funil' => function () {
        $resumo = Verificacao::resumo(gerarAte(null)->base);
        afirmar(str_contains($resumo, 'dealV2'), 'resumo sem a tabela dealV2');
        afirmar(str_contains($resumo, 'Funil:'), 'resumo sem a linha do funil');
    },
];
