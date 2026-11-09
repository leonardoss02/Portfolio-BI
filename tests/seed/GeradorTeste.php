<?php

require_once __DIR__ . '/apoio.php';

return [
    'a mesma data gera exatamente a mesma base' => function () {
        $assinatura = function (): string {
            $ctx = contextoDeTeste('2026-11-09', 0.05);
            Gerador::rodar($ctx);
            $partes = [];
            foreach ($ctx->base->tabelas() as $tabela) {
                $partes[$tabela] = md5(serialize($ctx->base->linhas($tabela)));
            }
            return json_encode($partes);
        };
        afirmarIgual($assinatura(), $assinatura(), 'duas gerações com a mesma data diferem');
    },
];
